<?php

namespace App\Imports;

use App\Models\AcademicYear;
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

    /** @var array<string, true> */
    private array $seenNisnInFile = [];
    /** @var array<string, true> */
    private array $seenEmptyNisn = [];

    /** @var array<string, true> */
    private array $existingDbKeys = [];

    /** @var array<string, string> */
    private array $errorsByRow = [];

    /** @var array<int, array{0: int, 1: string}> */
    private array $orderedErrors = [];

    /** @var array<string, SchoolClass> */
    private array $classMap = [];

    private static function normalizeClassName(string $s): string
    {
        return mb_strtolower(preg_replace('/[\s\-_]+/u', '', trim((string) $s)), 'UTF-8');
    }

    public function __construct(
        private bool $allowAutoCreateClass = true,
    ) {
    }

    /** @return array<int, array{0: int, 1: string}> */
    public function getOrderedErrors(): array
    {
        return $this->orderedErrors;
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
        return array_column($this->orderedErrors, 1);
    }

    private function normalizeNisn($raw): string
    {
        $value = trim((string) $raw);

        // Buang separator yang sering muncul dari Excel (. , spasi)
        $value = str_replace([' ', '.', ',', chr(34), chr(39)], '', $value);

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

    private static function normalizeName(string $s): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $s)), 'UTF-8');
    }

    private function dupKey(?string $nisn, string $name, ?int $classId): string
    {
        return ((string) ($nisn ?? '')) . '|' . self::normalizeName($name) . '|' . ((string) ($classId ?? ''));
    }

    private function registerError(int $rowIndex, string $reason): void
    {
        $this->errorsByRow[$rowIndex] = $reason;
        $this->orderedErrors[] = [$rowIndex, $reason];
    }

    private function resolveClassForRow(string $rawClass, int $rowIndex): ?SchoolClass
    {
        if ($rawClass === '') {
            return null;
        }

        $key = self::normalizeClassName($rawClass);
        $class = $this->classMap[$key] ?? null;

        if ($class !== null) {
            return $class;
        }

        if ($this->allowAutoCreateClass) {
            if (!AcademicYear::getActive()) {
                $this->registerError($rowIndex, 'Tahun ajaran aktif belum diatur. Buat dan aktifkan tahun ajaran di Pengaturan lebih dulu.');
                return null;
            }

            $grade = '7';
            if (str_contains($key, '8') || str_contains($key, 'viii')) {
                $grade = '8';
            } elseif (str_contains($key, '9') || str_contains($key, 'ix')) {
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

            if ($class !== null) {
                $this->classMap[$key] = $class;
            }

            return $class;
        }

        $this->registerError($rowIndex, "Kelas '{$rawClass}' tidak ditemukan.");
        return null;
    }

    public function collection(Collection $rows): void
    {
        $activeYear = AcademicYear::getActive();

        if (!$activeYear) {
            $this->registerError(0, 'Tahun ajaran aktif belum diatur. Buat dan aktifkan tahun ajaran di Pengaturan lebih dulu.');
            return;
        }

        $this->classMap = [];
        foreach (SchoolClass::all() as $class) {
            if ($class->name === null) {
                continue;
            }
            $this->classMap[self::normalizeClassName($class->name)] = $class;
        }

        // Muat kunci duplikat yang sudah ada di database SEKALI (bukan query per baris),
        // agar import cepat dan deteksi duplikasi lintas import akurat.
        $this->existingDbKeys = [];
        foreach (Student::withTrashed()->get(['nisn', 'name', 'school_class_id']) as $s) {
            $this->existingDbKeys[$this->dupKey($s->nisn, $s->name, $s->school_class_id)] = true;
        }

        $rowIndex = 1;

        foreach ($rows as $row) {
            $rowIndex++;

            $nisnInput = $row['nisn'] ?? '';
            $nisn = $this->normalizeNisn($nisnInput);
            $name = trim((string) ($row['nama'] ?? $row['nama_siswa'] ?? $row['nama_lengkap'] ?? $row['name'] ?? ''));

            if ($nisn === '') {
                // NISN kosong: simpan NULL ke database
                if (empty($name)) {
                    $this->registerError($rowIndex, "Baris {$rowIndex}: Nama Siswa wajib diisi (kosong).");
                    continue;
                }

                // Deteksi duplikat: kunci = nama + kelas (NISN kosong), case-insensitive,
                // spasi dirapikan. Resolve kelas dulu agar kita tahu school_class_id.
                $rawClass = trim((string) ($row['kelas'] ?? $row['class'] ?? $row['nama_kelas'] ?? ''));
                $class = $this->resolveClassForRow($rawClass, $rowIndex);

                if (!$class) {
                    continue;
                }

                $classId = $class->id;

                $key = $this->dupKey(null, $name, $classId);
                if (isset($this->existingDbKeys[$key])) {
                    $this->skippedCount++;
                    $this->registerError($rowIndex, "Baris {$rowIndex}: Siswa tanpa NISN dengan nama dan kelas sama sudah terdaftar di database. Baris dilewati.");
                    continue;
                }
                if (isset($this->seenEmptyNisn[$key])) {
                    $this->skippedCount++;
                    $this->registerError($rowIndex, "Baris {$rowIndex}: Duplikat di dalam file (nama: {$name}, kelas: {($rawClass ?: '-')}). Baris dilewati.");
                    continue;
                }

                $this->seenEmptyNisn[$key] = $rowIndex;

                // Lewati validasi NISN 10 digit ketika kosong, simpan NULL
                $rawGender = strtoupper(trim((string) ($row['jenis_kelamin'] ?? $row['gender'] ?? $row['jk'] ?? 'L')));
                if (str_starts_with($rawGender, 'P') || str_contains($rawGender, 'PEREMPUAN') || str_contains($rawGender, 'WANITA')) {
                    $gender = 'Perempuan';
                } else {
                    $gender = 'Laki-laki';
                }

                $rawClass = trim((string) ($row['kelas'] ?? $row['class'] ?? $row['nama_kelas'] ?? ''));
                $class = $this->resolveClassForRow($rawClass, $rowIndex);

                if (!$class) {
                    continue;
                }

                $classId = $class->id;

                try {
                    Student::create([
                        'nisn' => null,
                        'name' => $name,
                        'school_class_id' => $classId,
                        'gender' => $gender,
                        'status' => 'Aktif',
                        'qr_token' => (string) Str::uuid(),
                    ]);

                    $this->importedCount++;
                } catch (\Throwable $e) {
                    $this->skippedCount++;
                    $this->registerError($rowIndex, "Baris {$rowIndex} (NISN kosong): " . $e->getMessage());
                }
                continue;
            }

            // NISN terisi: validasi 10 digit
            if (!preg_match('/^[0-9]{10}$/', $nisn)) {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: NISN harus 10 digit angka. Baris dilewati.");
                continue;
            }

            // Resolve kelas dulu agar kita tahu school_class_id untuk kunci duplikat.
            $rawClass = trim((string) ($row['kelas'] ?? $row['class'] ?? $row['nama_kelas'] ?? ''));
            $class = $this->resolveClassForRow($rawClass, $rowIndex);

            if (!$class) {
                continue;
            }

            $classId = $class->id;

            // Duplikat hanya jika NISN + nama + kelas semuanya sama.
            // NISN sama tapi nama atau kelas beda tetap diimpor.
            $key = $this->dupKey($nisn, $name, $classId);
            if (isset($this->existingDbKeys[$key])) {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: NISN {$nisn} dengan nama dan kelas sama sudah terdaftar di database. Baris dilewati.");
                continue;
            }
            if (isset($this->seenNisnInFile[$key])) {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: Duplikat di dalam file (NISN {$nisn}, nama: {$name}, kelas: {($rawClass ?: '-')}). Baris dilewati.");
                continue;
            }

            $this->seenNisnInFile[$key] = $rowIndex;

            $rawGender = strtoupper(trim((string) ($row['jenis_kelamin'] ?? $row['gender'] ?? $row['jk'] ?? 'L')));
            if (str_starts_with($rawGender, 'P') || str_contains($rawGender, 'PEREMPUAN') || str_contains($rawGender, 'WANITA')) {
                $gender = 'Perempuan';
            } else {
                $gender = 'Laki-laki';
            }

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
                $this->registerError($rowIndex, "Baris {$rowIndex} (NISN {$nisn}): " . $e->getMessage());
            }
        }
    }
}