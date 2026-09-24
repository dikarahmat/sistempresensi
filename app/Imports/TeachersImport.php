<?php

namespace App\Imports;

use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TeachersImport implements ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;
    protected int $skippedCount = 0;
    protected array $errors = [];

    public function collection(Collection $rows)
    {
        $rowIndex = 1;

        foreach ($rows as $row) {
            $rowIndex++;

            $nip = trim((string) ($row['nip'] ?? $row['nomor_induk_pegawai'] ?? $row['id_guru'] ?? ''));
            $name = trim((string) ($row['nama'] ?? $row['nama_lengkap'] ?? $row['nama_guru'] ?? $row['nama_wali_kelas'] ?? $row['name'] ?? ''));

            if (empty($nip)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Kolom NIP wajib diisi (kosong).";
                continue;
            }

            if (empty($name)) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex} (NIP {$nip}): Nama Wali Kelas wajib diisi (kosong).";
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
            $phone = trim((string) ($row['no_hp'] ?? $row['phone_number'] ?? $row['no_wa'] ?? $row['telepon'] ?? $row['phone'] ?? ''));
            $address = trim((string) ($row['alamat'] ?? $row['address'] ?? ''));

            try {
                $userEmail = $nip . '@guru.smppresensipgri.sch.id';
                $user = User::where('email', $userEmail)->first();

                if ($user) {
                    // Jika user sudah terdaftar di database, cukup perbarui nama saja tanpa menimpa password
                    $user->update([
                        'name' => $name,
                    ]);
                } else {
                    // Password default (NIP) hanya untuk akun guru yang baru dibuat
                    $user = User::create([
                        'name' => $name,
                        'email' => $userEmail,
                        'password' => Hash::make($nip),
                        'role' => 'guru',
                        'email_verified_at' => now(),
                    ]);
                }

                Teacher::updateOrCreate(
                    ['nip' => $nip],
                    [
                        'name' => $name,
                        'gender' => $gender,
                        'birth_place' => $birthPlace ?: null,
                        'birth_date' => $birthDate ?: null,
                        'phone_number' => $phone ?: null,
                        'phone' => $phone ?: null,
                        'user_id' => $user->id,
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
