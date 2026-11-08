<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 9pt;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .school-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1d4ed8;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }
        .school-subtitle {
            font-size: 8.5pt;
            color: #475569;
            margin-top: 2px;
        }
        .report-title {
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            margin-top: 4px;
            margin-bottom: 2px;
            color: #0f172a;
        }
        .report-subtitle {
            font-size: 8.5pt;
            text-align: center;
            color: #64748b;
            margin-bottom: 12px;
        }
        .data-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            text-align: center;
        }
        .data-table th {
            background-color: #2563eb;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5pt;
        }
        .data-table thead {
            display: table-header-group;
        }
        .data-table tr {
            page-break-inside: avoid;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-left { text-align: left !important; }
        .text-center { text-align: center !important; }
        .badge-h { color: #059669; font-weight: bold; }
        .badge-s { color: #2563eb; font-weight: bold; }
        .badge-i { color: #7e22ce; font-weight: bold; }
        .badge-a { color: #e11d48; font-weight: bold; }
        .badge-l { color: #94a3b8; }
        .jk-l { color: #3B82F6; font-weight: 500; }
        .jk-p { color: #EF4444; font-weight: 500; }
        td, td * { color: #000 !important; font-weight: 400 !important; }
        .badge-h, .badge-t, .badge-s, .badge-i, .badge-a, .badge-l,
        .jk-l, .jk-p,
        th { color: inherit !important; font-weight: inherit !important; }
        th { color: #ffffff !important; font-weight: bold !important; }
        .badge-h { color: #059669 !important; font-weight: bold !important; }
        .badge-s { color: #2563eb !important; font-weight: bold !important; }
        .badge-i { color: #7e22ce !important; font-weight: bold !important; }
        .badge-a { color: #e11d48 !important; font-weight: bold !important; }
        .badge-l { color: #94a3b8 !important; font-weight: normal !important; }
        .jk-l { color: #3B82F6 !important; font-weight: 500 !important; }
        .jk-p { color: #EF4444 !important; font-weight: 500 !important; }
        .school-title { color: #1d4ed8 !important; font-weight: bold !important; }
        .school-subtitle, .report-subtitle { color: #000 !important; font-weight: 400 !important; }
        .printed-at, .printed-at strong { color: #000 !important; font-weight: 400 !important; }
        .legend-title { color: #000 !important; font-weight: 400 !important; }
        .header-table td { color: #000 !important; font-weight: 400 !important; }
        .section-footer {
            page-break-inside: avoid;
            page-break-before: avoid;
        }
        .pdf-legend {
            margin-top: 10px;
            margin-bottom: 10px;
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background-color: #f8fafc;
        }
        .signature-table {
            width: 100%;
            margin-top: 24px;
            page-break-inside: avoid;
        }
        .signature-table td {
            text-align: left;
            font-size: 8.5pt;
            width: 50%;
            vertical-align: top;
        }
        .signature-table td.sig-right {
            text-align: right;
        }
        .signature-table td.sig-right .sig-inline {
            display: inline-block;
            text-align: left;
        }
        .signature-space { height: 50px; }
    </style>
</head>
<body>

@php
    $logoSrc = $logoBase64 ?? null;
    if (!$logoSrc) {
        $lPath = public_path(\App\Models\Setting::getLogo());
        if (file_exists($lPath)) {
            $mime = mime_content_type($lPath) ?: 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($lPath));
        }
    }
    $npsn = \App\Models\Setting::get('school_npsn', '20102030');
    $resolvedActiveYear = $activeYear ?? \App\Models\AcademicYear::getActive();
    $signPlaceholder = \App\Exports\MultiPeriodAttendanceExport::NAME_PLACEHOLDER;
    $legendLines = \App\Exports\MultiPeriodAttendanceExport::legendLines();
    $printedAt = \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i');
    $signedAt = \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y');
    $sections = $sections ?: [];
@endphp

@foreach($sections as $section)
    @if(!$loop->first)
        <div class="page-break" style="page-break-after: always;"></div>
    @endif

    <?php $rows = $section['rows'] ?? []; ?>
    <?php $heading = $section['heading'] ?? ''; ?>
    <?php $subtitle = $section['subtitle'] ?? ''; ?>

    <!-- KOP SEKOLAH -->
    <table class="header-table">
        <tr>
            <td style="width: 60px;">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" style="width: 54px; height: 54px; object-fit: contain;">
                @endif
            </td>
            <td style="padding-left: 10px;">
                <div class="school-title">{{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</div>
                <div class="school-subtitle">
                    {{ $schoolAddress ?? \App\Models\Setting::getSchoolAddress() }} &bull; NPSN: {{ $npsn }} &bull; {!! __('Tahun Ajaran') !!} : {{ $resolvedActiveYear?->name ?? '2025/2026' }}
                </div>
            </td>
            <td style="text-align: right; font-size: 8pt; color: #64748b;">
                {{ __('Dicetak pada:') }}<br>
                <strong>{{ $printedAt }} WIB</strong>
            </td>
        </tr>
    </table>

    <div class="report-title">{{ $heading }}</div>
    <div class="report-subtitle">{{ $subtitle }}</div>

    <!-- TABEL DATA LAPORAN -->
    @if($type === 'harian')
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">{!! __('No') !!}</th>
                <th style="width: 9%;">{!! __('NIP') !!}</th>
                <th style="text-align: left; width: 20%;">{!! __('Nama Guru') !!}</th>
                <th style="width: 3%;">{!! __('JK') !!}</th>
                <th style="width: 10%;">{!! __('Jam Masuk') !!}</th>
                <th style="width: 8%;">{!! __('Status') !!}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td>{{ $row['nip'] ?: '-' }}</td>
                <td class="text-left"><strong>{{ mb_strtoupper($row['name'] ?? '', 'UTF-8') }}</strong></td>
                <td class="{!! $row['gender'] === 'Laki-laki' ? 'jk-l' : 'jk-p' !!}">{{ $row['gender'] === 'Laki-laki' ? 'L' : 'P' }}</td>
                <td>{!! $row['check_in'] !!}</td>
                <td>
                    @if($row['status'] === 'Hadir')
                        <span class="badge-h">{!! __('Hadir') !!}</span>
                    @elseif($row['status'] === 'Sakit')
                        <span class="badge-s">{!! __('Sakit') !!}</span>
                    @elseif($row['status'] === 'Izin')
                        <span class="badge-i">{!! __('Izin') !!}</span>
                    @elseif($row['status'] === 'Alfa')
                        <span class="badge-a">{!! __('Alfa') !!}</span>
                    @elseif($row['status'] === 'Libur')
                        <span class="badge-l">{!! __('Libur') !!}</span>
                    @elseif(str_contains($row['status'], 'Belum'))
                        <span class="badge-l">-</span>
                    @else
                        <span class="badge-l">{{ $row['status'] }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="padding: 16px; color: #64748b;">{!! __('Tidak ada data presensi.') !!}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @elseif($type === 'mingguan')
    @php
        $wNo = 4; $wNip = 9; $wNama = 20; $wJk = 3;
        $wMingguanTetap = $wNo + $wNip + $wNama + $wJk;
        $wKolomTanggalMingguan = (100 - $wMingguanTetap) / max(1, count($dateColumns));
    @endphp
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: {{ $wNo }}%;">{!! __('No') !!}</th>
                <th style="width: {{ $wNip }}%;">{!! __('NIP') !!}</th>
                <th style="text-align: left; width: {{ $wNama }}%;">{!! __('Nama Guru') !!}</th>
                <th style="width: {{ $wJk }}%;">{!! __('JK') !!}</th>
                @foreach($dateColumns as $d)
                    <th style="width: {{ $wKolomTanggalMingguan }}%;">{{ $d['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td>{{ $row['nip'] ?: '-' }}</td>
                <td class="text-left"><strong>{{ mb_strtoupper($row['name'] ?? '', 'UTF-8') }}</strong></td>
                <td class="{!! $row['gender'] === 'Laki-laki' ? 'jk-l' : 'jk-p' !!}">{{ $row['gender'] === 'Laki-laki' ? 'L' : 'P' }}</td>
                @foreach($dateColumns as $d)
                    @php
                        $code = $row['days'][$d['date']] ?? '-';
                        $cls = match($code) {
                            'H' => 'badge-h',
                            'S' => 'badge-s',
                            'I' => 'badge-i',
                            'A' => 'badge-a',
                            'L' => 'badge-l',
                            default => 'badge-l'
                        };
                        $val = in_array($code, ['H','S','I','A','L']) ? $code : '-';
                    @endphp
                    <td class="{{ $cls }}">{{ $val }}</td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($dateColumns) + 7 }}" style="padding: 16px; color: #64748b;">{!! __('Tidak ada data presensi.') !!}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @elseif(in_array($type, ['semester', 'tahunan'], true))
    @php
        $wNo = 4; $wNip = 9; $wNama = 20; $wJk = 3;
        $wBulanTetap = $wNo + $wNip + $wNama + $wJk;
        $wKolomBulan = (100 - $wBulanTetap) / max(1, count($months));
    @endphp
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: {{ $wNo }}%;">{!! __('No') !!}</th>
                <th style="width: {{ $wNip }}%;">{!! __('NIP') !!}</th>
                <th style="text-align: left; width: {{ $wNama }}%;">{!! __('Nama Guru') !!}</th>
                <th style="width: {{ $wJk }}%;">{!! __('JK') !!}</th>
                @foreach($months as $mInfo)
                    <th style="padding: 2px 1px; width: {{ $wKolomBulan }}%;">
                        {{ $mInfo['label'] }}
                        <div style="font-size: 6pt; font-weight: normal; text-transform: none;">{{ $mInfo['year'] }}</div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td>{{ $row['nip'] ?: '-' }}</td>
                <td class="text-left"><strong>{{ mb_strtoupper($row['name'] ?? '', 'UTF-8') }}</strong></td>
                <td class="{!! $row['gender'] === 'Laki-laki' ? 'jk-l' : 'jk-p' !!}">{{ $row['gender'] === 'Laki-laki' ? 'L' : 'P' }}</td>
                @foreach($months as $mInfo)
                    @php $cell = $row['days'][$mInfo['key']] ?? null; @endphp
                    <td>{{ $cell === null ? '-' : $cell }}</td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($months) + 7 }}" style="padding: 16px; color: #64748b;">{!! __('Tidak ada data presensi.') !!}</td>
            </tr>
            @endforelse
    @else
    @php
        $wNo = 4; $wNip = 9; $wNama = 20; $wJk = 3;
        $wBulananTetap = $wNo + $wNip + $wNama + $wJk;
        $wKolomTanggalBulanan = (100 - $wBulananTetap) / max(1, (int) $daysInMonth);
    @endphp
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: {{ $wNo }}%;">{!! __('No') !!}</th>
                <th style="width: {{ $wNip }}%;">{!! __('NIP') !!}</th>
                <th style="text-align: left; width: {{ $wNama }}%;">{!! __('Nama Guru') !!}</th>
                <th style="width: {{ $wJk }}%;">{!! __('JK') !!}</th>
                @for($d = 1; $d <= $daysInMonth; $d++)
                    <th style="padding: 2px 1px; width: {{ $wKolomTanggalBulanan }}%;">{{ $d }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td>{{ $row['nip'] ?: '-' }}</td>
                <td class="text-left" style="font-size: 7pt;"><strong>{{ mb_strtoupper($row['name'] ?? '', 'UTF-8') }}</strong></td>
                <td class="{!! $row['gender'] === 'Laki-laki' ? 'jk-l' : 'jk-p' !!}">{{ $row['gender'] === 'Laki-laki' ? 'L' : 'P' }}</td>
                @for($d = 1; $d <= $daysInMonth; $d++)
                    @php $code = $row['days'][$d] ?? '-'; @endphp
                    <td class="{{ match($code) { 'H' => 'badge-h', 'S' => 'badge-s', 'I' => 'badge-i', 'A' => 'badge-a', 'L' => 'badge-l', default => 'badge-l' } }}" style="padding: 2px 1px; font-size: 7pt;">{{ $code }}</td>
                @endfor
            </tr>
            @empty
            <tr>
                <td colspan="{{ $daysInMonth + 7 }}" style="padding: 16px; color: #64748b;">{!! __('Tidak ada data presensi.') !!}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @endif

    <!-- LEGENDA + TANDA TANGAN -->
    <div class="section-footer">
        <div class="pdf-legend">
            <div style="font-size: 9pt; font-weight: bold; color: #1e293b; margin-bottom: 3px;">{{ $legendLines[0] }}</div>
            <div style="font-size: 9pt; color: #334155; line-height: 1.6;">{{ $legendLines[1] }}</div>
            <div style="font-size: 8pt; color: #64748b; line-height: 1.5; margin-top: 2px;">{{ $legendLines[2] }}</div>
        </div>

        <!-- TANDA TANGAN: 2 KOLOM (Kiri: Kepala Sekolah, Kanan: Guru). -->
        <table class="signature-table">
            <tr>
                <td>
                    {!! __('Mengetahui,') !!}<br>
                    {!! __('Kepala Sekolah') !!}
                    <div class="signature-space"></div>
                    <strong><u>{{ $headmasterName ?: $signPlaceholder }}</u></strong><br>
                    NIP. {{ $headmasterNip ?: '-' }}
                </td>
                <td class="sig-right">
                    <div class="sig-inline">
                        Parung Panjang, {{ $signedAt }}<br>
                        {!! __('Guru') !!}<br>
                        <div class="signature-space"></div>
                        <strong><u>{{ $teacherName ?: $signPlaceholder }}</u></strong><br>
                        NIP. {{ $teacherNip ?: '-' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endforeach

</body>
</html>

