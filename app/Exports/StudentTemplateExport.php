<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'nis',
            'nisn',
            'nama',
            'kelas',
            'jenis_kelamin',
            'tempat_lahir',
            'tanggal_lahir',
            'alamat',
            'nama_orang_tua',
            'no_hp_orang_tua',
        ];
    }

    public function array(): array
    {
        return [
            [
                '2024001',
                '0081234567',
                'Ahmad Rizky Pratama',
                '7A',
                'Laki-laki',
                'Bandung',
                '2012-04-15',
                'Jl. Melati No. 12, Bandung',
                'Bambang Sutrisno',
                '081234567890',
            ],
            [
                '2024002',
                '0081234568',
                'Siti Nurhaliza',
                '7A',
                'Perempuan',
                'Jakarta',
                '2012-08-21',
                'Jl. Mawar No. 45, Bandung',
                'Hartono',
                '081298765432',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2563EB'],
                ],
            ],
        ];
    }
}
