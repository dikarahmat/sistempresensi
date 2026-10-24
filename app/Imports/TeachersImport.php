<?php

namespace App\Imports;

use App\Models\SchoolClass;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TeachersImport implements ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;
    protected int $skippedCount = 0;
    protected array $errors = [];

    /**
     * Normalize phone number: handle +62, 0, and spaces/dashes.
     * Returns normalized phone with leading 0 (e.g., 081234567890).
     */
    private function normalizePhone(string $raw): string
    {
        $value = trim($raw);

        // Remove spaces, dashes, parentheses
        $value = str_replace([' ', '-', '(', ')'], '', $value);

        // Handle +62 prefix
        if (str_starts_with($value, '+62')) {
            $value = '0' . substr($value, 3);
        }

        // Remove any remaining non-digits
        $value = preg_replace('/[^0-9]/', '', $value);

        return $value;
    }

    public function collection(Collection $rows)
    {
        $rowIndex = 1;

        // Cache kelas untuk validasi tugas kelas
        $classMap = SchoolClass::pluck('id', 'name')->toArray();
        $classNameMap = SchoolClass::pluck('name', 'id')->toArray();

        // Track wali kelas yang sudah di-assign dalam file ini
        $assignedWaliKelasInFile = [];

        foreach ($rows as $row) {
            $rowIndex++;

            $nip = trim((string) ($row['nip'] ?? $row['nomor_induk_pegawai'] ?? $row['id_guru'] ?? ''));
            $name = trim((string) ($row['nama'] ?? $row['nama_lengkap'] ?? $row['nama_guru'] ?? $row['name'] ?? ''));

            if (empty($nip)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Kolom NIP wajib diisi (kosong).";
                continue;
            }

            // NIP (18 digit, PNS/PPPK) atau NUPTK (16 digit, guru non-PNS)
            if (!preg_match('/^([0-9]{16}|[0-9]{18})$/', $nip)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Format NIP/NUPTK tidak valid (harus 16 atau 18 digit angka). Baris dilewati.";
                continue;
            }

            if (empty($name)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} (NIP {$nip}): Nama Guru wajib diisi (kosong).";
                continue;
            }

            // Parse Gender
            $rawGender = strtoupper(trim((string) ($row['jenis_kelamin'] ?? $row['gender'] ?? $row['jk'] ?? 'L')));
            if (str_starts_with($rawGender, 'P') || str_contains($rawGender, 'PEREMPUAN') || str_contains($rawGender, 'WANITA')) {
                $gender = 'Perempuan';
            } else {
                $gender = 'Laki-laki';
            }

            // Parse Tanggal Lahir Fleksibel
            $rawBirthDate = $row['tanggal_lahir'] ?? $row['tgl_lahir'] ?? $row['birth_date'] ?? null;
            $birthDate = null;

            if (!empty($rawBirthDate)) {
                try {
                    if (is_numeric($rawBirthDate)) {
                        $birthDate = ExcelDate::excelToDateTimeObject($rawBirthDate)->format('Y-m-d');
                    } else {
                        $birthDate = Carbon::parse(str_replace('/', '-', $rawBirthDate))->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    $birthDate = null;
                }
            }

            $birthPlace = trim((string) ($row['tempat_lahir'] ?? $row['birth_place'] ?? ''));
            $phone = $this->normalizePhone((string) ($row['no_hp'] ?? $row['phone_number'] ?? $row['no_wa'] ?? $row['telepon'] ?? $row['phone'] ?? ''));
            $address = trim((string) ($row['alamat'] ?? $row['address'] ?? ''));

            // Parse Tugas Kelas (school_class_id)
            $schoolClassId = null;
            $rawClass = trim((string) ($row['kelas'] ?? $row['tugas_kelas'] ?? $row['school_class_id'] ?? $row['class_id'] ?? ''));

            if ($rawClass !== '') {
                // Cari kelas berdasarkan nama (case-insensitive, dengan prefiks "Kelas ")
                $cleanRawClass = strtolower(str_replace(['kelas', ' ', '-'], '', $rawClass));
                $matchedClass = null;
                foreach ($classMap as $className => $classId) {
                    $cleanClassName = strtolower(str_replace(['kelas', ' ', '-'], '', $className));
                    if ($cleanClassName === $cleanRawClass || strcasecmp($className, $rawClass) === 0) {
                        $matchedClass = $classId;
                        break;
                    }
                }

                if ($matchedClass) {
                    $schoolClassId = $matchedClass;
                }
            }

            // Validasi wali kelas unik (di database + dalam file ini)
            if ($schoolClassId) {
                $existingWaliInDb = \App\Models\SchoolClass::where('teacher_id', '!=', null)
                    ->where('id', $schoolClassId)
                    ->exists();

                if ($existingWaliInDb) {
                    $this->skippedCount++;
                    $className = $classNameMap[$schoolClassId] ?? 'ID ' . $schoolClassId;
                    $this->errors[] = "Baris {$rowIndex} (NIP {$nip}): Kelas {$className} sudah memiliki wali kelas. Baris dilewati.";
                    continue;
                }

                // Cek di dalam file ini
                if (in_array($schoolClassId, $assignedWaliKelasInFile)) {
                    $this->skippedCount++;
                    $className = $classNameMap[$schoolClassId] ?? 'ID ' . $schoolClassId;
                    $this->errors[] = "Baris {$rowIndex} (NIP {$nip}): Kelas {$className} sudah ditugaskan wali kelas di baris lain dalam file ini. Baris dilewati.";
                    continue;
                }

                $assignedWaliKelasInFile[] = $schoolClassId;
            }

            try {
                // Hanya simpan data guru ke tabel teachers.
                // Akun User tidak lagi dibuat otomatis melalui import —
                // login hanya melalui username + password (lihat AuthController).
                Teacher::updateOrCreate(
                    ['nip' => $nip],
                    [
                        'name' => $name,
                        'gender' => $gender,
                        'birth_place' => $birthPlace ?: null,
                        'birth_date' => $birthDate ?: null,
                        'phone_number' => $phone ?: null,
                        'phone' => $phone ?: null,
                        'school_class_id' => $schoolClassId,
                    ]
                );

                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} (NIP {$nip}): " . $e->getMessage();
            }
        }
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}