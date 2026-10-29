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

    private function normalizePhone(string $raw): string
    {
        $value = trim($raw);

        $value = str_replace([' ', '-', '(', ')'], '', $value);

        if (str_starts_with($value, '+62')) {
            $value = '0' . substr($value, 3);
        }

        $value = preg_replace('/[^0-9]/', '', $value);

        return $value;
    }

    public function collection(Collection $rows): void
    {
        $rowIndex = 1;

        $classMap = SchoolClass::pluck('id', 'name')->toArray();
        $classNameMap = SchoolClass::pluck('name', 'id')->toArray();

        $assignedWaliKelasInFile = [];

        foreach ($rows as $row) {
            $rowIndex++;

            $nip = trim((string) ($row['nip'] ?? $row['nomor_induk_pegawai'] ?? $row['id_guru'] ?? ''));
            $name = trim((string) ($row['nama'] ?? $row['nama_lengkap'] ?? $row['nama_guru'] ?? $row['name'] ?? ''));

            if (!empty($nip)) {
                if (!preg_match('/^(\d{16}|\d{18})$/', $nip)) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex}: NIP harus 16 atau 18 digit angka. Baris dilewati.";
                    continue;
                }
            }

            $nip = $nip !== '' ? $nip : null;

            if (empty($name)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} (NIP {$nip}): Nama Guru wajib diisi (kosong).";
                continue;
            }

            if (preg_match('/\p{Nd}/u', $name) === 1) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} (NIP {$nip}): Nama tidak boleh mengandung angka. Baris dilewati.";
                continue;
            }

            if (! empty($row['nip'] ?? '')) {
                if (! preg_match('/^(\d{16}|\d{18})$/', trim((string) ($row['nip'] ?? '')))) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex}: NIP harus 16 atau 18 digit angka. Baris dilewati.";
                }
            }

            $rawGender = strtoupper(trim((string) ($row['jenis_kelamin'] ?? $row['gender'] ?? $row['jk'] ?? 'L')));
            if (str_starts_with($rawGender, 'P') || str_contains($rawGender, 'PEREMPUAN') || str_contains($rawGender, 'WANITA')) {
                $gender = 'Perempuan';
            } else {
                $gender = 'Laki-laki';
            }

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

            $schoolClassId = null;
            $rawClass = trim((string) ($row['kelas'] ?? $row['tugas_kelas'] ?? $row['school_class_id'] ?? $row['class_id'] ?? ''));

            if ($rawClass !== '') {
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
                } else {
                    $this->errors[] = "Baris {$rowIndex} (NIP {$nip}): Kelas '{$rawClass}' tidak ditemukan, guru diimpor tanpa penugasan kelas.";
                }
            }

            try {
                $data = [
                    'name' => $name,
                    'gender' => $gender,
                    'birth_place' => $birthPlace ?: null,
                    'birth_date' => $birthDate ?: null,
                    'phone_number' => $phone ?: null,
                    'phone' => $phone ?: null,
                    'school_class_id' => $schoolClassId,
                ];

                if ($nip !== null) {
                    Teacher::updateOrCreate(['nip' => $nip], $data);
                } else {
                    $teacher = Teacher::withTrashed()
                        ->whereNull('nip')
                        ->where('name', $name)
                        ->first();

                    if ($teacher) {
                        if ($teacher->trashed()) {
                            $teacher->restore();
                        }
                        $teacher->update($data);
                    } else {
                        Teacher::create($data + ['nip' => null]);
                    }
                }

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
