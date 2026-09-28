<?php

namespace App\Imports;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class HolidaysImport implements ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;
    protected int $skippedCount = 0;
    protected array $errors = [];

    public function collection(Collection $rows)
    {
        $rowIndex = 1; // baris 1 adalah header

        foreach ($rows as $row) {
            $rowIndex++;

            $rawDate = $row['tanggal'] ?? $row['date'] ?? null;
            $description = trim((string) ($row['keterangan'] ?? $row['description'] ?? $row['nama'] ?? ''));
            $rawUntil = $row['sampai'] ?? $row['end_date'] ?? null;

            // Parse tanggal mulai (mendukung serial Excel & teks)
            $startDate = null;
            if (!empty($rawDate)) {
                try {
                    $startDate = is_numeric($rawDate)
                        ? ExcelDate::excelToDateTimeObject($rawDate)->format('Y-m-d')
                        : Carbon::parse(str_replace('/', '-', $rawDate))->format('Y-m-d');
                } catch (\Throwable $e) {
                    $startDate = null;
                }
            }

            if ($startDate === null) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Kolom tanggal kosong atau tidak valid.";
                continue;
            }

            if ($description === '') {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: Kolom keterangan wajib diisi.";
                continue;
            }

            // Parse tanggal akhir (opsional) untuk rentang libur
            $endDate = $startDate;
            if (!empty($rawUntil)) {
                try {
                    $parsedUntil = is_numeric($rawUntil)
                        ? ExcelDate::excelToDateTimeObject($rawUntil)->format('Y-m-d')
                        : Carbon::parse(str_replace('/', '-', $rawUntil))->format('Y-m-d');

                    if ($parsedUntil && $parsedUntil >= $startDate) {
                        $endDate = $parsedUntil;
                    }
                } catch (\Throwable $e) {
                    // abaikan, tetap pakai tanggal mulai
                }
            }

            try {
                // Lewati duplikat berdasarkan tanggal yang sama
                if (Holiday::where('date', $startDate)->exists()) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowIndex}: Tanggal {$startDate} sudah terdata, dilewati.";
                    continue;
                }

                Holiday::create([
                    'date' => $startDate,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'description' => $description,
                ]);

                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->skippedCount++;
                $this->errors[] = "Baris {$rowIndex}: " . $e->getMessage();
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
