<?php

namespace App\Exports;

use App\Models\Setting;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel rekap presensi GURU. Tampilannya disamakan dengan Excel rekap siswa
 * (MultiPeriodAttendanceExport): judul + sub-judul, tabel berwarna, keterangan,
 * dan blok tanda tangan Kepala Sekolah.
 *
 * Kolom NIP (B) dipaksa bertipe teks agar tidak tampil sebagai 9,16E+15.
 */
class TeacherAttendanceExport extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithStyles, WithColumnWidths, WithCustomValueBinder
{
    public const NAME_PLACEHOLDER = '( ................................. )';

    protected int $dataStartRow = 5;
    protected int $dataEndRow = 5;
    protected int $legendRow = 0;
    protected int $signRow = 0;
    protected int $columnCount;

    public function __construct(
        protected array $headings,
        protected array $rows,
        protected string $heading,
        protected string $subtitle,
        protected string $infoLine = ''
    ) {
        $this->columnCount = max(count($headings), 3);
    }

    /** NIP (kolom B) selalu teks; sel lain mengikuti perilaku bawaan. */
    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getColumn() === 'B' && $cell->getRow() >= $this->dataStartRow && is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function array(): array
    {
        Carbon::setLocale('id');

        $out = [];
        $out[] = [$this->heading];
        $out[] = [$this->subtitle];
        $out[] = [''];
        $out[] = $this->headings;

        foreach ($this->rows as $row) {
            $row[1] = ($row[1] ?? '') !== '' && $row[1] !== null ? (string) $row[1] : '-';
            $out[] = $row;
        }
        $this->dataEndRow = count($out);

        // Legenda
        $out[] = [''];
        $this->legendRow = count($out) + 1;
        foreach (MultiPeriodAttendanceExport::legendLines() as $line) {
            $out[] = [$line];
        }
        if ($this->infoLine !== '') {
            $out[] = [$this->infoLine];
        }

        // Tanda tangan: kiri Kepala Sekolah, kanan petugas
        $out[] = [''];
        $this->signRow = count($out) + 1;
        foreach ($this->signatureRows() as $line) {
            $out[] = $line;
        }

        return $out;
    }

    protected function signatureRows(): array
    {
        $right = $this->columnCount;
        $blank = array_fill(0, $this->columnCount, '');
        $make = function (array $values) use ($blank) {
            $row = $blank;
            foreach ($values as $idx => $value) {
                $row[$idx - 1] = $value;
            }

            return $row;
        };

        $headmasterName = Setting::getHeadmasterName();
        $headmasterNip = Setting::getHeadmasterNip();

        // Sama dengan PDF guru: guru tidak punya wali kelas, jadi hanya
        // tanda tangan Kepala Sekolah di sisi kanan.
        return [
            $make([$right => 'Parung Panjang, ' . Carbon::now('Asia/Jakarta')->translatedFormat('d F Y')]),
            $make([$right => 'Kepala Sekolah']),
            $blank,
            $blank,
            $make([$right => $headmasterName ?: self::NAME_PLACEHOLDER]),
            $make([$right => 'NIP. ' . ($headmasterNip ?: '-')]),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = Coordinate::stringFromColumnIndex($this->columnCount);

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('1D4ED8'));
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new Color('475569'));

        $header = "A4:{$lastCol}4";
        $sheet->getStyle($header)->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
        $sheet->getStyle($header)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('2563EB');
        $sheet->getStyle($header)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        if ($this->dataEndRow >= $this->dataStartRow) {
            $sheet->getStyle("A4:{$lastCol}{$this->dataEndRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('CBD5E1');
            $sheet->getStyle("A5:B{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D5:{$lastCol}{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // JK (kolom D): L biru, P merah
            for ($r = $this->dataStartRow; $r <= $this->dataEndRow; $r++) {
                $jk = strtoupper(trim((string) $sheet->getCell('D' . $r)->getValue()));
                if ($jk === 'L' || $jk === 'P') {
                    $sheet->getStyle('D' . $r)->getFont()->setColor(new Color($jk === 'L' ? '3B82F6' : 'EF4444'));
                }
            }
        }

        if ($this->legendRow > 0) {
            $sheet->getStyle("A{$this->legendRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$this->legendRow}:{$lastCol}" . ($this->legendRow + 3))
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        if ($this->signRow > 0) {
            $start = $this->signRow;
            $end = $start + 5;
            foreach ([$start + 1, $start + 4] as $r) {
                $sheet->getStyle($lastCol . $r)->getFont()->setBold(true);
            }
            $sheet->getStyle("{$lastCol}{$start}:{$lastCol}{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        return [];
    }

    /** Kolom tanda tangan dikunci lebarnya agar teks panjang tidak melebarkan kolom No/JK. */
    public function columnWidths(): array
    {
        $widths = [];
        $widths[Coordinate::stringFromColumnIndex($this->columnCount)] = 28.0;
        // NIP (18 digit) & nama guru butuh ruang cukup.
        $widths['B'] = 24.0;
        $widths['C'] = 38.0;

        return $widths;
    }
}