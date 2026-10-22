<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SchoolClassesImport implements ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;
    protected int $skippedCount = 0;
    protected array $errors = [];

    public function collection(Collection $rows)
    {
        $activeYear = AcademicYear::getActive();
        $rowIndex = 1; // baris 1 adalah header

        foreach ($rows as $row) {
            $rowIndex++;

            // Lewati baris yang benar-benar kosong (sisa baris di Excel).
            // Maatwebsite memberi tiap baris berupa Collection, bukan array biasa.
            $hasData = false;
            $values = $row instanceof Collection ? $row->all() : (array) $row;
            foreach ($values as $value) {
                if (trim((string) $value) !== '') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) {
                continue;
            }

            // 1. Kolom nama kelas (template memakai "nama", modal juga menyebut "Nama Kelas")
            $name = strtoupper(trim((string) (
                $row['nama'] ?? $row['nama_kelas'] ?? $row['kelas'] ?? $row['name'] ?? ''
            )));

            if ($name === '') {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Kolom nama kelas wajib diisi (kosong).";
                continue;
            }

            if (mb_strlen($name) > 50) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Nama kelas \"{$name}\" melebihi 50 karakter.";
                continue;
            }

            // Validasi konsistensi nama kelas dengan tingkat (akan divalidasi setelah resolveGrade)
            // 2. Kolom tingkat (opsional: 7/8/9 atau VII/VIII/IX), fallback derive dari nama
            $rawGrade = trim((string) ($row['tingkat'] ?? $row['grade'] ?? $row['level'] ?? ''));
            $grade = $this->resolveGrade($rawGrade, $name);

            if ($grade === null) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} ({$name}): tingkat kelas tidak dikenali. Isi kolom tingkat dengan 7, 8, atau 9.";
                continue;
            }

            // Validasi konsistensi nama dengan tingkat
            $nameGrade = substr($name, 0, 1);
            if (!in_array($nameGrade, ['7', '8', '9']) || $nameGrade !== $grade) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} ({$name}): nama kelas harus dimulai dengan angka tingkat yang sesuai (contoh: 7A untuk tingkat 7).";
                continue;
            }

            $level = match ($grade) {
                '8' => 'VIII',
                '9' => 'IX',
                default => 'VII',
            };

            if ($activeYear === null) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} ({$name}): tahun ajaran aktif belum ditentukan di Pengaturan.";
                continue;
            }

            // 3. Kolom wali kelas (opsional: nama atau NIP guru)
            $teacherId = null;
            $rawTeacher = trim((string) ($row['wali_kelas'] ?? $row['walikelas'] ?? $row['teacher'] ?? $row['guru'] ?? ''));
            if ($rawTeacher !== '') {
                // Cari guru berdasarkan NIP (18 digit) atau nama
                if (preg_match('/^[0-9]{18}$/', $rawTeacher)) {
                    $teacher = Teacher::where('nip', $rawTeacher)->first();
                } else {
                    $teacher = Teacher::where('name', $rawTeacher)->first();
                }

                if (!$teacher) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex} ({$name}): wali kelas \"{$rawTeacher}\" tidak ditemukan.";
                    continue;
                }

                // Cek apakah guru sudah menjadi wali kelas lain (kecuali jika kelas ini sudah punya guru itu)
                $existingClass = SchoolClass::withTrashed()
                    ->where('teacher_id', $teacher->id)
                    ->where('name', '!=', $name)
                    ->first();
                if ($existingClass) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex} ({$name}): guru {$teacher->name} sudah menjadi wali kelas {$existingClass->name}. Satu guru hanya bisa menjadi wali satu kelas.";
                    continue;
                }

                $teacherId = $teacher->id;
            }

            try {
                // updateOrCreate berdasarkan nama kelas (termasuk yang ter-arsip agar
                // tidak melanggar unique (name + academic_year_id))
                $candidates = SchoolClass::withTrashed()->where('name', $name)->get();
                $schoolClass = $candidates->firstWhere('academic_year_id', $activeYear->id)
                    ?? $candidates->first();

                if ($schoolClass) {
                    $schoolClass->grade = $grade;
                    $schoolClass->level = $level;
                    $schoolClass->teacher_id = $teacherId;
                    if ($schoolClass->trashed()) {
                        $schoolClass->restore();
                    } else {
                        $schoolClass->save();
                    }
                } else {
                    $schoolClass = SchoolClass::create([
                        'name' => $name,
                        'grade' => $grade,
                        'level' => $level,
                        'academic_year_id' => $activeYear->id,
                        'teacher_id' => $teacherId,
                    ]);
                }

                // Sinkronkan wali kelas ke Data Guru (TUGAS KELAS)
                if ($teacherId) {
                    // Lepaskan guru lama dari kelas ini (jika ada)
                    SchoolClass::where('id', $schoolClass->id)->update(['teacher_id' => $teacherId]);
                    // Lepaskan kelas lama dari guru ini (jika ada)
                    SchoolClass::where('teacher_id', $teacherId)->where('id', '!=', $schoolClass->id)->update(['teacher_id' => null]);
                    // Update Teacher
                    Teacher::where('id', $teacherId)->update(['school_class_id' => $schoolClass->id]);
                }

                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} ({$name}): " . $e->getMessage();
            }
        }
    }

    /**
     * Tentukan tingkat kelas (7/8/9) dari kolom tingkat atau dari nama kelas.
     * Contoh nama yang dikenali: 7A, 8B, 9C, VII-A, VIII B, IX.
     */
    protected function resolveGrade(string $rawGrade, string $name): ?string
    {
        $candidates = [strtoupper(trim($rawGrade)), strtoupper($name)];

        foreach ($candidates as $value) {
            if ($value === '') {
                continue;
            }

            if (in_array($value, ['7', '8', '9'], true)) {
                return $value;
            }

            if (preg_match('/^[789]/', $value)) {
                return substr($value, 0, 1);
            }

            if (in_array($value, ['VII', 'VIII', 'IX'], true)) {
                return match ($value) {
                    'VIII' => '8',
                    'IX' => '9',
                    default => '7',
                };
            }

            // Nama seperti "VIII-A" atau "KELAS VII"
            $clean = strtoupper(str_replace(['KELAS', ' ', '-', '.', '_'], '', $value));
            if (str_starts_with($clean, 'VIII')) {
                return '8';
            }
            if (str_starts_with($clean, 'VII')) {
                return '7';
            }
            if (str_starts_with($clean, 'IX')) {
                return '9';
            }
            if (preg_match('/[789]/', $clean)) {
                return preg_replace('/[^789]/', '', $clean)[0];
            }
        }

        return null;
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
