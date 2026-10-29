<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeacherTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    /**
     * Template Excel guru (kolom: NIP, Nama Lengkap, Jenis Kelamin, No. Telepon).
     * NIP boleh kosong.
     */
    public function headings(): array
    {
        return [
            'nip',
            'nama_lengkap',
            'jenis_kelamin',
            'no_telepon',
        ];
    }

    public function array(): array
    {
        return [
            [
                '198501012010011001',
                'Drs. Muhammad Hidayat, M.Pd.',
                'Laki-laki',
                '081334567890',
            ],
            [
                '',
                'Sri Wahyuni, S.Pd.',
                'Perempuan',
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
                    'startColor' => ['argb' => 'FF2563EB'],
                ],
            ],
        ];
    }
}
