<?php

namespace App\Imports;

use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TeachersImport implements ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;
    protected int $skippedCount = 0;

    /** @var array<string, true> */
    private array $seenNipInFile = [];
    /** @var array<string, true> */
    private array $existingNip = [];
    /** @var array<string, true> */
    private array $seenNamePhoneInFile = [];
    /** @var array<string, true> */
    private array $existingNamePhone = [];

    /** @var array<int, array{0: int, 1: string}> */
    private array $orderedErrors = [];

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

    private function normalizeNip($raw): string
    {
        $value = trim((string) $raw);

        // Buang separator yang sering muncul dari Excel (. , spasi)
        $value = str_replace([' ', '.', ',', "'", '-'], '', $value);

        // scientific notation dari Excel (mis. 1.985E+17)
        if (stripos($value, 'e') !== false && is_numeric($value)) {
            $value = sprintf('%.0f', (float) $value);
        }

        return $value;
    }

    private function normalizePhone(string $raw): string
    {
        $value = trim($raw);
        $value = str_replace([' ', '-', '(', ')', '.', "'"], '', $value);

        if (str_starts_with($value, '+62')) {
            $value = '0' . substr($value, 3);
        } elseif (str_starts_with($value, '62') && strlen($value) > 10) {
            $value = '0' . substr($value, 2);
        }

        return preg_replace('/[^0-9]/', '', $value);
    }

    private static function normalizeNamePhoneKey(string $name, string $phone): string
    {
        $name = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $name)), 'UTF-8');
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return $name . '|' . $phone;
    }

    private function registerError(int $rowIndex, string $reason): void
    {
        $this->orderedErrors[] = [$rowIndex, $reason];
    }

    public function collection(Collection $rows): void
    {
        $this->existingNip = Teacher::withTrashed()
            ->get(['nip'])
            ->filter(fn ($t) => $t->nip !== null && trim((string) $t->nip) !== '')
            ->map(fn ($t) => trim((string) $t->nip))
            ->reduce(function (array $carry, string $nip) {
                $carry[$nip] = true;
                return $carry;
            }, []);

        $this->existingNamePhone = Teacher::withTrashed()
            ->get(['name', 'phone'])
            ->filter(fn ($t) => trim((string) $t->name) !== '')
            ->reduce(function (array $carry, $t) {
                $key = self::normalizeNamePhoneKey((string) $t->name, (string) $t->phone);
                if ($key !== '') {
                    $carry[$key] = true;
                }
                return $carry;
            }, []);

        $rowIndex = 1;

        foreach ($rows as $row) {
            $rowIndex++;

            $nip = $this->normalizeNip($row['nip'] ?? $row['nomor_induk_pegawai'] ?? $row['id_guru'] ?? '');
            $name = trim((string) ($row['nama_lengkap'] ?? $row['nama'] ?? $row['nama_guru'] ?? $row['name'] ?? ''));
            $rawGender = strtoupper(trim((string) ($row['jenis_kelamin'] ?? $row['gender'] ?? $row['jk'] ?? '')));
            $phone = $this->normalizePhone((string) ($row['no_telepon'] ?? $row['no_hp'] ?? $row['phone_number'] ?? $row['phone'] ?? $row['telepon'] ?? ''));

            // Abaikan jika seluruh kolom penting kosong
            if ($nip === '' && $name === '' && $rawGender === '' && $phone === '') {
                continue;
            }

            // Validasi NIP (opsional di Excel, tapi jika diisi harus 16 atau 18 digit dan unik)
            $namePhoneKey = self::normalizeNamePhoneKey($name, $phone);
            if ($nip !== '') {
                if (!preg_match('/^(\d{16}|\d{18})$/', $nip)) {
                    $this->skippedCount++;
                    $this->registerError($rowIndex, "Baris {$rowIndex}: NIP/NUPTK harus 16 atau 18 digit angka. Baris dilewati.");
                    continue;
                }

                if (isset($this->seenNipInFile[$nip])) {
                    $this->skippedCount++;
                    $this->registerError($rowIndex, "Baris {$rowIndex}: NIP {$nip} duplikat di dalam file (sudah dipakai baris {$this->seenNipInFile[$nip]}). Baris dilewati.");
                    continue;
                }

                if (isset($this->existingNip[$nip])) {
                    $this->skippedCount++;
                    $this->registerError($rowIndex, "Baris {$rowIndex}: NIP sudah terdaftar. Baris dilewati.");
                    continue;
                }

                $this->seenNipInFile[$nip] = $rowIndex;
                $this->existingNip[$nip] = true;

                // Juga catat nama+telepon agar baris kosong-NIP berikutnya
                // dengan nama+telepon sama tetap terdeteksi sebagai duplikat.
                if ($namePhoneKey !== '|') {
                    $this->seenNamePhoneInFile[$namePhoneKey] = true;
                    $this->existingNamePhone[$namePhoneKey] = true;
                }
            }

            // Jika NIP kosong: deteksi duplikat berdasarkan nama (dinormalisasi) + no. telepon.
            if ($nip === '' && $namePhoneKey !== '|') {
                if (isset($this->seenNamePhoneInFile[$namePhoneKey])) {
                    $this->skippedCount++;
                    $this->registerError($rowIndex, "Baris {$rowIndex}: Nama sudah terdaftar. Baris dilewati.");
                    continue;
                }

                if (isset($this->existingNamePhone[$namePhoneKey])) {
                    $this->skippedCount++;
                    $this->registerError($rowIndex, "Baris {$rowIndex}: Nama sudah terdaftar. Baris dilewati.");
                    continue;
                }

                $this->seenNamePhoneInFile[$namePhoneKey] = true;
                $this->existingNamePhone[$namePhoneKey] = true;
            }

            // Validasi Nama Lengkap
            if ($name === '') {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: Nama Guru wajib diisi (kosong).");
                continue;
            }

            if (!preg_match('/^[\pL\s.,\'\-]+$/u', $name)) {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: Format nama hanya boleh huruf dan tanda baca. Baris dilewati.");
                continue;
            }

            // Validasi Jenis Kelamin
            if ($rawGender === '') {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: Jenis kelamin wajib diisi. Baris dilewati.");
                continue;
            }

            if (str_starts_with($rawGender, 'P') || str_contains($rawGender, 'PEREMPUAN') || str_contains($rawGender, 'WANITA')) {
                $gender = 'Perempuan';
            } else {
                $gender = 'Laki-laki';
            }

            // Validasi Nomor Telepon
            if ($phone === '') {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: No. telepon wajib diisi. Baris dilewati.");
                continue;
            }

            if (strlen($phone) < 10 || strlen($phone) > 15) {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: No. telepon harus 10-15 digit angka. Baris dilewati.");
                continue;
            }

            try {
                Teacher::create([
                    'nip' => $nip !== '' ? $nip : null,
                    'name' => $name,
                    'gender' => $gender,
                    'phone' => $phone,
                    'phone_number' => $phone,
                    'status' => 'Aktif',
                    'qr_token' => (string) Str::uuid(),
                ]);

                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->skippedCount++;
                $this->registerError($rowIndex, "Baris {$rowIndex}: " . $e->getMessage());
            }
        }
    }
}
