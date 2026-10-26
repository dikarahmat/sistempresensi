@php
    $teacherList = collect($teachers ?? []);
    $paper = $paper ?? 'a4';

    $sizes = ['a4' => [297, 210], 'a3' => [420, 297]];
    [$pageWidth, $pageHeight] = $sizes[$paper] ?? $sizes['a4'];

    $cardWidth = 53.9;
    $cardHeight = 85.6;
    $gap = 3;
    $columns = 5;
    $rows = 2;
    $perSheet = $columns * $rows;

    $gridWidth = round($columns * $cardWidth + ($columns + 1) * $gap, 2);
    $gridHeight = round($rows * $cardHeight + ($rows + 1) * $gap, 2);

    $offsetX = round(($pageWidth - $gridWidth) / 2, 2);
    $offsetY = round(($pageHeight - $gridHeight) / 2, 2);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Cetak Kartu Presensi Guru') }} - {{ $schoolName ?? '' }}</title>
    <style>
        @page { size: {{ strtoupper($paper) }} landscape; margin: 0; background: #ffffff; }
        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-size: 0;
            line-height: 0;
            font-family: Arial, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .page {
            margin: 0;
            padding: {{ $offsetY }}mm 0 0 {{ $offsetX }}mm;
        }
        .grid {
            width: {{ $gridWidth }}mm;
            border-collapse: separate;
            border-spacing: {{ $gap }}mm;
            table-layout: fixed;
            margin: 0;
            padding: 0;
            border: 0;
            page-break-inside: avoid;
        }
        .grid td {
            width: {{ $cardWidth }}mm;
            height: {{ $cardHeight }}mm;
            padding: 0;
            margin: 0;
            border: 0;
            vertical-align: top;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
@foreach($teacherList->chunk($perSheet) as $pageTeachers)
<div class="page"@if(!$loop->first) style="page-break-before: always;"@endif>
    <table class="grid" cellpadding="0" cellspacing="0">
        @foreach($pageTeachers->chunk($columns) as $rowTeachers)
        <tr>
            @foreach($rowTeachers as $item)
            <td>@include('admin.teachers.partials.card-single', [
                'teacherItem' => $item,
                'logoSrc' => $logoBase64 ?? null,
                'qrSrc' => $item->qr_base64 ?? null,
                'schoolName' => $schoolName ?? '',
                'teacherName' => $item->name,
                'teacherNip' => $item->nip,
                'isPdf' => true,
            ])</td>
            @endforeach
            @for($i = $rowTeachers->count(); $i < $columns; $i++)
            <td></td>
            @endfor
        </tr>
        @endforeach
    </table>
</div>
@endforeach
</body>
</html>
