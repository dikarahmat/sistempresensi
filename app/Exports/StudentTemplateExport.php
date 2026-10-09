<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
/**
     * Template Excel siswa. Data siswa hanya 4 kolom, jadi template ini hanya
     * punya 4 header dan HARUS sama persis dengan yang dibaca StudentsImport
     * (heading 'nisn', 'nama', 'kelas', 'jenis_kelamin').
     */
    public function headings(): array
    {
        return [
            'nisn',
            'nama',
            'kelas',
            'jenis_kelamin',
        ];
    }

    public function array(): array
    {
        return [
            [
                '0081234567',
                'Ahmad Rizky Pratama',
                '7A',
                'Laki-laki',
            ],
            [
                '0081234568',
                'Siti Nurhaliza',
                '7A',
                'Perempuan',
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
