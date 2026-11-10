<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;
    protected int $skippedCount = 0;

    /** Kunci duplikat yang sudah ada di DB + yang baru saja diimpor (satu set untuk semua). */
    /** @var array<string, true> */
    private array $knownKeys = [];

    /** @var array<int, array{0: int, 1: string}> */
    private array $orderedErrors = [];

    /** @var array<string, SchoolClass> */
    private array $classMap = [];

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

    private static function normalizeClassName(string $s): string
    {
        return mb_strtolower(preg_replace('/[\s\-_]+/u', '', trim($s)), 'UTF-8');
    }

    private static function normalizeName(string $s): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($s)), 'UTF-8');
    }

    private function normalizeNisn($raw): string
    {
        $value = trim((string) $raw);
        $value = str_replace([' ', '.', ',', '"', "'"], '', $value);

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

    private function dupKey(?string $nisn, string $name, ?int $classId): string
    {
        return ((string) ($nisn ?? '')) . '|' . self::normalizeName($name) . '|' . ((string) ($classId ?? ''));
    }

    private function registerError(int $rowIndex, string $reason): void
    {
        $this->orderedErrors[] = [$rowIndex, $reason];
    }

    private function resolveGender(array|\ArrayAccess $row): string
    {
        $raw = strtoupper(trim((string) ($row['jenis_kelamin'] ?? $row['gender'] ?? $row['jk'] ?? 'L')));

        if (str_starts_with($raw, 'P') || str_contains($raw, 'PEREMPUAN') || str_contains($raw, 'WANITA')) {
            return 'Perempuan';
        }

        return 'Laki-laki';
    }

    private function resolveClassForRow(string $rawClass, int $rowIndex): ?SchoolClass
    {
        $key = self::normalizeClassName($rawClass);
        $class = $this->classMap[$key] ?? null;

        if ($class !== null) {
            return $class;
        }

        if (!$this->allowAutoCreateClass) {
            $this->registerError($rowIndex, "Baris {$rowIndex}: Kelas '{$rawClass}' tidak ditemukan.");
            return null;
        }

        $grade = '7';
        if (str_contains($key, 'viii') || str_contains($key, '8')) {
            $grade = '8';
        } elseif (str_contains($key, 'ix') || str_contains($key, '9')) {
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

        $this->classMap[$key] = $class;

        return $class;
    }

    public function collection(Collection $rows): void
    {
        // Cegah import ganda akibat klik dua kali / request paralel:
        // hanya satu proses import yang boleh berjalan pada satu waktu.
        $lock = Cache::lock('students-import-lock', 600);

        if (!$lock->get()) {
            $this->registerError(0, 'Import siswa lain sedang berjalan. Tunggu selesai lalu coba lagi.');
            return;
        }

        try {
            $this->process($rows);
        } finally {
            $lock->release();
        }
    }

    private function process(Collection $rows): void
    {
        if (!AcademicYear::getActive()) {
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

        // Muat kunci yang sudah ada di DB (termasuk soft-deleted) SEKALI.
        // NISN dinormalisasi supaya cocok dengan format baris Excel (padding 10 digit).
        $this->knownKeys = [];
        foreach (Student::withTrashed()->get(['nisn', 'name', 'school_class_id']) as $s) {
            $nisn = $s->nisn === null ? '' : $this->normalizeNisn($s->nisn);
            $this->knownKeys[$this->dupKey($nisn, $s->name, $s->school_class_id)] = true;
        }

        $rowIndex = 1;

        foreach ($rows as $row) {
            $rowIndex++;

            // Lewati baris yang benar-benar kosong
            if ($row->filter(fn ($v) => trim((string) $v) !== '')->isEmpty()) {
                continue;
            }

            $nisn = $this->normalizeNisn($row['nisn'] ?? '');
            $name = trim((string) ($row['nama'] ?? $row['nama_siswa'] ?? $row['nama_lengkap'] ?? $row['name'] ?? ''));
            $rawClass = trim((string) ($row['kelas'] ?? $row['class'] ?? $row['nama_kelas'] ?? ''));

            if ($name === '') {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: Nama Siswa wajib diisi (kosong).");
                continue;
            }

            if ($nisn !== '' && !preg_match('/^[0-9]{10}$/', $nisn)) {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: NISN harus 10 digit angka. Baris dilewati.");
                continue;
            }

            if ($rawClass === '') {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: Kelas wajib diisi (kosong).");
                continue;
            }

            $class = $this->resolveClassForRow($rawClass, $rowIndex);
            if (!$class) {
                $this->skippedCount++;
                continue;
            }

            // Duplikat = NISN + nama + kelas sama (NISN kosong ikut jadi bagian kunci).
            // Satu set kunci dipakai untuk DB, antar-baris dalam file, dan hasil import barusan.
            $key = $this->dupKey($nisn, $name, $class->id);
            if (isset($this->knownKeys[$key])) {
                $this->skippedCount++;
                $label = $nisn === '' ? 'Siswa tanpa NISN' : "NISN {$nisn}";
                $this->registerError(
                    $rowIndex,
                    "Baris {$rowIndex}: {$label} dengan nama '{$name}' dan kelas '{$rawClass}' sudah terdaftar (di database atau baris sebelumnya). Baris dilewati."
                );
                continue;
            }

            try {
                Student::create([
                    'nisn' => $nisn === '' ? null : $nisn,
                    'name' => $name,
                    'school_class_id' => $class->id,
                    'gender' => $this->resolveGender($row),
                    'status' => 'Aktif',
                    'qr_token' => (string) Str::uuid(),
                ]);

                // Tandai hanya SETELAH berhasil disimpan
                $this->knownKeys[$key] = true;
                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: " . $e->getMessage());
            }
        }
    }
}