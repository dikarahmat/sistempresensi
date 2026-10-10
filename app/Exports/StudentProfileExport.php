<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ekspor profil presensi per siswa (satu sheet per siswa).
 * Setiap sheet berisi:
 * - Kop sekolah (logo, nama, alamat)
 * - Identitas siswa (Nama, NISN, Kelas, JK)
 * - Ringkasan statistik (Hadir, Terlambat, Sakit, Izin, Alfa, Libur, Persentase)
 * - Tabel riwayat presensi per tanggal (Tanggal, Hari, Jam Masuk, Jam Pulang, Status, Keterlambatan, Keterangan)
 * - Tanda tangan Wali Kelas dan Kepala Sekolah
 */
class StudentProfileExport implements FromArray, WithMultipleSheets, WithTitle, WithStyles, WithColumnWidths
{
    protected array $studentsData;
    protected string $periodType;
    protected string $periodLabel;
    protected $activeYear;
    protected string $schoolName;
    protected string $schoolAddress;
    protected int $dataStartRow = 0;
    protected int $dataEndRow = 0;
    protected int $columnCount = 0;
    protected ?string $sheetTitle = null;

    public function __construct(array $studentsData, string $periodType, string $periodLabel, $activeYear, string $schoolName, string $schoolAddress)
    {
        $this->studentsData = $studentsData;
        $this->periodType = $periodType;
        $this->periodLabel = $periodLabel;
        $this->activeYear = $activeYear;
        $this->schoolName = $schoolName;
        $this->schoolAddress = $schoolAddress;
    }

    public function sheets(): array
    {
        $sheets = [];
        $used = [];
        foreach ($this->studentsData as $data) {
            $child = new self(
                [$data],
                $this->periodType,
                $this->periodLabel,
                $this->activeYear,
                $this->schoolName,
                $this->schoolAddress
            );

            $base = $child->buildSheetTitle();
            $title = $base;
            $suffix = 2;
            while (isset($used[strtoupper($title)])) {
                $tail = '_' . $suffix;
                $title = mb_substr($base, 0, 31 - mb_strlen($tail)) . $tail;
                $suffix++;
            }
            $used[strtoupper($title)] = true;

            $child->sheetTitle = $title;
            $sheets[] = $child;
        }
        return $sheets;
    }

    public function title(): string
    {
        return $this->sheetTitle ?? $this->buildSheetTitle();
    }

    /**
     * Nama sheet dasar: {Kelas}_{NISN}_{Nama}, dibersihkan dari karakter yang
     * dilarang Excel dan dipotong maksimal 31 karakter.
     */
    protected function buildSheetTitle(): string
    {
        $data = $this->studentsData[0] ?? null;
        if (!$data) {
            return 'Profil';
        }

        $student = $data['student'];
        $className = $student->schoolClass?->name ?? 'TanpaKelas';
        $name = Str::slug($student->name);
        $nisn = $student->nisn ?: 'TanpaNISN';

        $raw = "{$className}_{$nisn}_{$name}";

        // Karakter terlarang pada nama sheet Excel: \ / ? * [ ] :
        $raw = preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $raw) ?? $raw;
        $raw = trim($raw, " '");

        if ($raw === '') {
            $raw = 'Profil';
        }

        return mb_substr($raw, 0, 31);
    }

    public function array(): array
    {
        Carbon::setLocale('id');

        $data = $this->studentsData[0] ?? null;
        if (!$data) {
            return [];
        }

        $student = $data['student'];
        $rows = $data['rows'];
        $stats = $data['stats'];
        $totalEffective = $data['totalEffective'];
        $totalHadir = $data['totalHadir'];
        $percentage = $data['percentage'];

        $output = [];

        // School header - merge across all columns
        $output[] = [$this->schoolName];
        $output[] = [$this->schoolAddress];
        $output[] = ['NPSN: ' . (Setting::get('school_npsn', '20102030'))];
        $output[] = []; // empty row

        // Report title
        $output[] = ['LAPORAN RIWAYAT PRESENSI SISWA'];
        $output[] = ['Periode: ' . $this->periodLabel];
        $output[] = ['Tahun Ajaran: ' . ($this->activeYear?->name ?? '-')];
        $output[] = []; // empty row

        // Student identity
        $output[] = ['Identitas Siswa'];
        $output[] = ['Nama', $student->name];
        $output[] = ['NISN', $student->nisn ?: '-'];
        $output[] = ['Kelas', $student->schoolClass?->name ?? '-'];
        $output[] = ['Jenis Kelamin', $student->gender ?? '-'];
        $output[] = []; // empty row

        // Summary stats
        $output[] = ['Ringkasan Kehadiran'];
        $output[] = ['Hadir (Tepat Waktu)', $stats['hadir']];
        $output[] = ['Terlambat', $stats['terlambat']];
        $output[] = ['Sakit', $stats['sakit']];
        $output[] = ['Izin', $stats['izin']];
        $output[] = ['Alfa', $stats['alfa']];
        $output[] = ['Libur', $stats['libur']];
        $output[] = ['Total Hari Efektif', $totalEffective];
        $output[] = ['Persentase Kehadiran', $percentage . '%'];
        $output[] = []; // empty row

        // History table header
        $output[] = ['Riwayat Presensi'];
        $headerRow = ['No', 'Tanggal', 'Hari', 'Jam Masuk', 'Jam Pulang', 'Status', 'Keterlambatan', 'Keterangan'];
        $output[] = $headerRow;
        $this->columnCount = count($headerRow);
        // Header is at current row (after push), data starts at next row
        $this->dataStartRow = count($output); // This points to header row

        // History data rows
        foreach ($rows as $index => $row) {
            $output[] = [
                $index + 1,
                $row['tanggal_raw'],
                Carbon::parse($row['tanggal_raw'])->translatedFormat('l'),
                $row['jam_masuk'],
                $row['jam_pulang'],
                $row['status'],
                $row['keterlambatan'],
                $row['keterangan'],
            ];
        }

        $this->dataEndRow = count($output);

        // Empty row before signature
        $output[] = [];
        $output[] = [];

        // Signature block
        $signedAt = Carbon::now('Asia/Jakarta')->translatedFormat('d F Y');
        $headmasterName = Setting::getHeadmasterName();
        $headmasterNip = Setting::getHeadmasterNip();
        $waliName = $student->schoolClass?->teacher?->name;
        $waliNip = $student->schoolClass?->teacher?->nip;
        $placeholder = '( ................................. )';

        $output[] = ['Mengetahui,', '', '', 'Wali Kelas', '', 'Parung Panjang, ' . $signedAt];
        $output[] = ['Kepala Sekolah', '', '', '', '', 'Petugas Presensi'];
        $output[] = [];
        $output[] = [];
        $output[] = [
            $headmasterName ?: '( ................................. )',
            '',
            '',
            $waliName ?: '( ................................. )',
            '',
            '( ................................. )'
        ];
        $output[] = [
            'NIP. ' . ($headmasterNip ?: '-'),
            '',
            '',
            'NIP. ' . ($waliNip ?: '-'),
            '',
            'NIP. -'
        ];

        return $output;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = Coordinate::stringFromColumnIndex($this->columnCount);

        // Title rows (A1:A3) - merge across all columns, wrap text
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('1D4ED8'));
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new Color('475569'));
        $sheet->getStyle('A3')->getFont()->setSize(10)->setColor(new Color('475569'));

        // Report title row (A5) - merge
        $sheet->mergeCells("A5:{$lastCol}5");
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(12)->setColor(new Color('0F172A'));

        // Report subtitle rows (A6:A7) - merge
        $sheet->mergeCells("A6:{$lastCol}6");
        $sheet->mergeCells("A7:{$lastCol}7");
        $sheet->getStyle('A6')->getFont()->setSize(10);
        $sheet->getStyle('A7')->getFont()->setSize(10);

        // Student identity header (A9)
        $sheet->getStyle('A9')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A10:B13')->getFont()->setSize(10);

        // Summary stats header (A15)
        $sheet->getStyle('A15')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A16:B22')->getFont()->setSize(10);
        // Persentase Kehadiran row - dynamically find it (row 23 in current structure)
        // We'll find it by checking the label in column A
        $lastDataRow = $this->dataEndRow;
        for ($row = 1; $row <= $lastDataRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            if (is_string($cellValue) && str_starts_with($cellValue, 'Persentase Kehadiran')) {
                $sheet->getStyle('B' . $row)->getFont()->setBold(true)->setSize(12)->setColor(new Color('1D4ED8'));
                break;
            }
        }

        // Table header
        $tableHeaderRow = $this->dataStartRow;
        $tableHeaderRange = "A{$tableHeaderRow}:{$lastCol}{$tableHeaderRow}";
        $sheet->getStyle($tableHeaderRange)->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
        $sheet->getStyle($tableHeaderRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('2563EB');
        $sheet->getStyle($tableHeaderRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // Freeze pane below table header
        $sheet->freezePane("A" . ($tableHeaderRow + 1));

        // Table data
        if ($this->dataEndRow >= $this->dataStartRow) {
            $dataRange = "A{$this->dataStartRow}:{$lastCol}{$this->dataEndRow}";
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('CBD5E1');
            $sheet->getStyle("A{$this->dataStartRow}:A{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$this->dataStartRow}:C{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Status column coloring (column F = 6)
            for ($row = $this->dataStartRow; $row <= $this->dataEndRow; $row++) {
                $status = $sheet->getCell('F' . $row)->getValue(); // Status is column F (6)
                $color = match ($status) {
                    'Hadir' => '059669',
                    'Terlambat' => 'D97706',
                    'Sakit' => '2563EB',
                    'Izin' => '7E22CE',
                    'Alfa' => 'EF4444',
                    'Libur' => '94A3B8',
                    default => '1E293B',
                };
                $sheet->getStyle('F' . $row)->getFont()->setBold(true)->setColor(new Color($color));
            }
        }

        // Signature block
        $signStart = $this->dataEndRow + 3;
        $signEnd = $signStart + 4;

        // Bold for titles and names
        $row1 = $signStart + 1;
        $row4 = $signStart + 4;
        $sheet->getStyle("A{$signStart}")->getFont()->setBold(true);
        $sheet->getStyle("D{$signStart}")->getFont()->setBold(true);
        $sheet->getStyle("F{$signStart}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row1}")->getFont()->setBold(true);
        $sheet->getStyle("F{$row1}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row4}")->getFont()->setBold(true);
        $sheet->getStyle("D{$row4}")->getFont()->setBold(true);
        $sheet->getStyle("F{$row4}")->getFont()->setBold(true);

        // Wrap text for keterangan column (H)
        $sheet->getStyle("H{$this->dataStartRow}:H{$this->dataEndRow}")->getAlignment()->setWrapText(true);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // No
            'B' => 18,  // Tanggal
            'C' => 15,  // Hari
            'D' => 15,  // Jam Masuk
            'E' => 15,  // Jam Pulang
            'F' => 15,  // Status
            'G' => 18,  // Keterlambatan
            'H' => 40,  // Keterangan
        ];
    }

    // Freeze pane is applied directly in styles() via $sheet->freezePane()
}