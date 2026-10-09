<?php

namespace App\Imports;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;
    protected int $skippedCount = 0;
    protected array $errors = [];

    /**
     *NISN selalu 10 digit angka. Bila Excel membaca sel angka (mis. 0081234567
     * tersimpan sebagai 81234567) nol di depan hilang, sehingga nilainya
     * dipad dengan nol di depan sampai 10 digit.
     */
    private function normalizeNisn($raw): string
    {
        $value = trim((string) $raw);

        // Buang separator yang sering muncul dari Excel (. , spasi)
        $value = str_replace([' ', '.', ',', "'"], '', $value);

        // scientific notation dari Excel (mis. 8,1234567E+8)
        if (stripos($value, 'e') !== false && is_numeric($value)) {
            $value = sprintf('%.0f', (float) $value);
        }

        if ($value === '') {
            return '';
        }

        if (!ctype_digit($value)) {
            return $value;
        }

        return str_pad($value, 10, '0', STR_PAD_LEFT);
    }

    public function collection(Collection $rows)
    {
        $allClasses = SchoolClass::all();
        $rowIndex = 1; // Row 1 is header, data starts at row 2

        // NISN yang sudah dipakai file ini (deteksi duplikat di dalam file) dan
        // NISN yang sudah ada di database (deteksi duplikat terhadap data lama).
        $seenNisnInFile = [];
        $existingNisn = Student::withTrashed()->pluck('nisn')
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (string) $v)
            ->flip()
            ->all();

        foreach ($rows as $row) {
            $rowIndex++;

            // 1. Kolom NISN (identitas tunggal siswa)
            $nisn = $this->normalizeNisn($row['nisn'] ?? '');
            // 2. Kolom Nama
            $name = trim((string) ($row['nama'] ?? $row['nama_siswa'] ?? $row['nama_lengkap'] ?? $row['name'] ?? ''));

            if ($nisn === '') {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Kolom NISN wajib diisi (kosong).";
                continue;
            }

            if (!preg_match('/^[0-9]{10}$/', $nisn)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: NISN harus 10 digit angka. Baris dilewati.";
                continue;
            }

            if (isset($seenNisnInFile[$nisn])) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: NISN {$nisn} duplikat di dalam file (sudah dipakai baris {$seenNisnInFile[$nisn]}). Baris dilewati.";
                continue;
            }

            if (isset($existingNisn[$nisn])) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: NISN sudah terdaftar. Baris dilewati.";
                continue;
            }

            if (empty($name)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} (NISN {$nisn}): Nama Siswa wajib diisi (kosong).";
                continue;
            }

            $seenNisnInFile[$nisn] = $rowIndex;
            $existingNisn[$nisn] = true;

            // 3. Kolom Gender / Jenis Kelamin
            $rawGender = strtoupper(trim((string) ($row['jenis_kelamin'] ?? $row['gender'] ?? $row['jk'] ?? 'L')));
            if (str_starts_with($rawGender, 'P') || str_contains($rawGender, 'PEREMPUAN') || str_contains($rawGender, 'WANITA')) {
                $gender = 'Perempuan';
            } else {
                $gender = 'Laki-laki';
            }

            // 4. Kolom Kelas
            $rawClass = trim((string) ($row['kelas'] ?? $row['class'] ?? $row['nama_kelas'] ?? ''));
            $class = null;
            if ($rawClass !== '') {
                $cleanSearch = strtolower(str_replace(['kelas', ' ', '-'], '', $rawClass));
                $class = $allClasses->first(function ($c) use ($cleanSearch, $rawClass) {
                    $cleanName = strtolower(str_replace(['kelas', ' ', '-'], '', $c->name));
                    return $cleanName === $cleanSearch || strcasecmp($c->name, $rawClass) === 0;
                });

                // Jika kelas belum ada di DB, buat kelas baru secara otomatis
                if (!$class) {
                    $grade = '7';
                    if (str_contains($cleanSearch, '8') || str_contains($cleanSearch, 'viii')) {
                        $grade = '8';
                    } elseif (str_contains($cleanSearch, '9') || str_contains($cleanSearch, 'ix')) {
                        $grade = '9';
                    }

                    $level = match ($grade) {
                        '8' => 'VIII',
                        '9' => 'IX',
                        default => 'VII',
                    };

                    $class = SchoolClass::create([
                        'name' => strtoupper($rawClass),
                        'grade' => $grade,
                        'level' => $level,
                    ]);
                    $allClasses->push($class);
                }
            }

            if (!$class) {
                $class = $allClasses->first();
                if (!$class) {
                    $class = SchoolClass::create([
                        'name' => '7A',
                        'grade' => '7',
                        'level' => 'VII',
                    ]);
                    $allClasses->push($class);
                }
            }

            $classId = $class->id;

            // 5. Simpan Data Siswa.
            // Data siswa hanya 4 kolom (NISN, Nama, Kelas, Jenis Kelamin).
            // Kolom lain di file Excel (tempat/tanggal lahir, alamat, nama
            // wali, nomor WhatsApp) sengaja DIABAIKAN dan tidak disimpan lagi.
            // NISN wajib & unik -> selalu create, bukan update.
            try {
                Student::create([
                    'nisn' => $nisn,
                    'name' => $name,
                    'school_class_id' => $classId,
                    'gender' => $gender,
                    'status' => 'Aktif',
                    'qr_token' => (string) Str::uuid(),
                ]);

                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} (NISN {$nisn}): " . $e->getMessage();
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
