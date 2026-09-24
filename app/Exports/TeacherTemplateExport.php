<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeacherTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function headings(): array
    {
        return [
            'nip',
            'nama',
            'jenis_kelamin',
            'tempat_lahir',
            'tanggal_lahir',
            'no_hp',
        ];
    }

    public function array(): array
    {
        return [
            [
                '198501012010011001',
                'Drs. Muhammad Hidayat, M.Pd.',
                'Laki-laki',
                'Surabaya',
                '1985-01-01',
                '081334567890',
            ],
            [
                '199002152015022002',
                'Sri Wahyuni, S.Pd.',
                'Perempuan',
                'Semarang',
                '1990-02-15',
                '081223344556',
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
                    'startColor' => ['argb' => 'FF059669'],
                ],
            ],
        ];
    }
}
