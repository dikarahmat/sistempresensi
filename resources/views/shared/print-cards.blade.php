@php
    $studentsList = collect($studentsForView ?? $students ?? []);
    $paper    = $paper ?? 'a4';
    $sizes    = ['a4' => [297, 210], 'a3' => [420, 297]];
    [$pw, $ph] = $sizes[$paper] ?? $sizes['a4'];
    $cols     = $cols ?? max(1, (int) floor($pw / 53.98));
    $rows     = $rows ?? max(1, (int) floor($ph / 85.6));
    $perSheet = $perSheet ?? ($cols * $rows);
    $gridW    = round($cols * 53.98, 2);
    $pageH    = round($rows * 85.6, 2);
    // Offset supaya grid di tengah kertas
    $offX     = round(($pw - $gridW) / 2, 2);
    $offY     = round(($ph - $pageH) / 2, 2);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Cetak Kartu Presensi Siswa') }} - {{ $schoolName ?? '' }}</title>
    <style>
        @page { size: {{ strtoupper($paper) }} landscape; margin: 0; }
        html, body { margin: 0; padding: 0; background: #ffffff; font-size: 0; line-height: 0; font-family: Arial, Helvetica, sans-serif; }
        .page { width: {{ $gridW }}mm; height: {{ $pageH }}mm; margin: 0; padding: {{ $offY }}mm 0 0 {{ $offX }}mm; overflow: hidden; }
        .page-break { page-break-after: always; }
        .grid { width: {{ $gridW }}mm; border-collapse: collapse; border-spacing: 0; table-layout: fixed; margin: 0; padding: 0; border: 0; }
        .grid td { width: 53.98mm; height: 85.6mm; padding: 0; margin: 0; border: 0; vertical-align: top; }
    </style>
</head>
<body>
@foreach($studentsList->chunk($perSheet) as $pageStudents)
<div class="page{{ $loop->last ? '' : ' page-break' }}">
    <table class="grid" cellpadding="0" cellspacing="0">
        @foreach($pageStudents->chunk($cols) as $rowStudents)
        <tr>
            @foreach($rowStudents as $item)
            <td>@include('admin.students.partials.card-single', [
                'studentItem'  => $item,
                'logoSrc'      => $logoBase64 ?? null,
                'qrSrc'        => $item->qr_base64 ?? null,
                'schoolName'   => $schoolName ?? '',
                'studentName'  => $item->name,
                'studentNisn'  => $item->nisn,
                'studentClass' => $item->schoolClass->name ?? '-',
                'isPdf'        => true,
            ])</td>
            @endforeach
            @for($i = $rowStudents->count(); $i < $cols; $i++)
            <td></td>
            @endfor
        </tr>
        @endforeach
    </table>
</div>
@endforeach
</body>
</html>