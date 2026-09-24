<?php

namespace App\Imports;

use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StudentsImport implements ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;
    protected int $skippedCount = 0;
    protected array $errors = [];

    public function collection(Collection $rows)
    {
        $allClasses = SchoolClass::all();
        $rowIndex = 1; // Row 1 is header, data starts at row 2

        foreach ($rows as $row) {
            $rowIndex++;

            // 1. Kolom NIS
            $nis = trim((string) ($row['nis'] ?? $row['no_induk'] ?? $row['nomor_induk'] ?? ''));
            // 2. Kolom Nama
            $name = trim((string) ($row['nama'] ?? $row['nama_siswa'] ?? $row['nama_lengkap'] ?? $row['name'] ?? ''));

            if (empty($nis)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Kolom NIS wajib diisi (kosong).";
                continue;
            }

            if (empty($name)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} (NIS {$nis}): Nama Siswa wajib diisi (kosong).";
                continue;
            }

            // Kolom NISN opsional: set null jika kosong agar tidak melanggar unique constraint
            $rawNisn = trim((string) ($row['nisn'] ?? ''));
            $nisn = !empty($rawNisn) ? $rawNisn : null;

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

            // 5. Tanggal Lahir (Mendukung Excel Serial & Format Tanggal Teks)
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

            // 6. Data Pelengkap
            $birthPlace = trim((string) ($row['tempat_lahir'] ?? $row['birth_place'] ?? ''));
            $address = trim((string) ($row['alamat'] ?? $row['address'] ?? ''));
            $parentName = trim((string) ($row['nama_orang_tua'] ?? $row['nama_wali'] ?? $row['wali'] ?? $row['parent_name'] ?? ''));
            $parentPhone = trim((string) ($row['no_hp_orang_tua'] ?? $row['no_hp'] ?? $row['no_wa'] ?? $row['telepon'] ?? $row['parent_phone'] ?? ''));

            // 7. Simpan atau Update Data Siswa
            try {
                $student = Student::where('nis', $nis)->first();

                if ($student) {
                    $student->update([
                        'name' => $name,
                        'nisn' => $nisn ?: $student->nisn,
                        'school_class_id' => $classId,
                        'gender' => $gender,
                        'birth_place' => $birthPlace ?: $student->birth_place,
                        'birth_date' => $birthDate ?: $student->birth_date,
                        'address' => $address ?: $student->address,
                        'parent_name' => $parentName ?: $student->parent_name,
                        'parent_phone' => $parentPhone ?: $student->parent_phone,
                        'status' => 'Aktif',
                    ]);
                } else {
                    Student::create([
                        'nis' => $nis,
                        'nisn' => $nisn,
                        'name' => $name,
                        'school_class_id' => $classId,
                        'gender' => $gender,
                        'birth_place' => $birthPlace ?: null,
                        'birth_date' => $birthDate ?: null,
                        'address' => $address ?: null,
                        'parent_name' => $parentName ?: null,
                        'parent_phone' => $parentPhone ?: null,
                        'status' => 'Aktif',
                        'qr_token' => (string) Str::uuid(),
                    ]);
                }

                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} (NIS {$nis}): " . $e->getMessage();
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
