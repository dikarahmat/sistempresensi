<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SchoolClassTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([
            [
                'nama' => '7A',
            ],
            [
                'nama' => '7B',
            ],
            [
                'nama' => '7C',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'nama'
        ];
    }
}