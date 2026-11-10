<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Kartu Presensi Guru' }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #e8ecef; }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 16px;
            background: #14232b;
            color: #fff;
            font: 14px "Segoe UI", Roboto, Arial, sans-serif;
        }
        .toolbar .actions { display: flex; gap: 8px; }
        .toolbar button, .toolbar a {
            padding: 7px 14px;
            border: 0;
            border-radius: 6px;
            background: #1f8a70;
            color: #fff;
            font: inherit;
            cursor: pointer;
            text-decoration: none;
        }
        .toolbar a { background: #3a4a53; }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 12px auto;
            padding: 10mm;
            background: #fff;
            display: grid;
            grid-template-columns: repeat(3, 53.9mm);
            grid-auto-rows: 85.6mm;
            gap: 5mm;
            justify-content: center;
            align-content: start;
            page-break-after: always;
            break-after: page;
        }
        .sheet:last-of-type { page-break-after: auto; break-after: auto; }

        .card-cell {
            width: 53.9mm;
            height: 85.6mm;
            overflow: hidden;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        @media print {
            html, body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
@php
    $schoolName = $schoolName ?? config('app.name', '');
    $chunks = collect($teachers)->chunk(9);
@endphp

<div class="toolbar">
    <span>{{ $pageTitle ?? 'Kartu Presensi Guru' }} ({{ collect($teachers)->count() }} kartu)</span>
    <div class="actions">
        <a href="{{ url()->previous() }}">Kembali</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
</div>

@foreach ($chunks as $chunk)
    <div class="sheet">
        @foreach ($chunk as $teacher)
            <div class="card-cell">
                @include('admin.teachers.partials.card-single', [
                    'teacherItem' => $teacher,
                    'schoolName'  => $schoolName,
                    'teacherName' => $teacher->name ?? '',
                    'teacherNip'  => $teacher->nip ?? '',
                    'isPdf'       => true,
                ])
            </div>
        @endforeach
    </div>
@endforeach

@if (!empty($autoPrint))
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 600);
    });
</script>
@endif
</body>
</html>