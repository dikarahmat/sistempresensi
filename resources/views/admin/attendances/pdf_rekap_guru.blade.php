<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Rekap Presensi Guru' }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 12mm; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b; font-size: 9pt; line-height: 1.3;
        }
        .header-table { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
        .header-table td { vertical-align: middle; }
        .school-title { font-size: 14pt; font-weight: bold; color: #1d4ed8; letter-spacing: 0.5px; margin: 0; text-transform: uppercase; }
        .school-subtitle { font-size: 8.5pt; color: #475569; margin-top: 2px; }
        .report-title { font-size: 11pt; font-weight: bold; text-align: center; margin-top: 4px; margin-bottom: 2px; color: #0f172a; }
        .report-subtitle { font-size: 8.5pt; text-align: center; color: #64748b; margin-bottom: 12px; }

        .data-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 8pt; }
        .data-table th, .data-table td { border: 1px solid #cbd5e1; padding: 3px 5px; text-align: center; }
        .data-table th { background-color: #2563eb; color: #ffffff; font-weight: bold; text-transform: uppercase; font-size: 7.5pt; }
        .data-table thead { display: table-header-group; }
        /* Jangan pakai page-break-inside: avoid pada tr: DomPDF jadi memindahkan seluruh tabel ke halaman berikutnya */
        .data-table tr:nth-child(even) td { background-color: #f8fafc; }
        .text-left { text-align: left !important; }

        /* Harian: makin banyak guru, makin rapat supaya tetap 1 halaman */
        .data-table.compact th, .data-table.compact td { padding: 2px 5px; font-size: 7.5pt; }
        .data-table.tight th, .data-table.tight td { padding: 1px 4px; font-size: 7pt; line-height: 1.2; }
        .data-table.tight th { font-size: 7pt; }

        .badge-h { color: #059669; font-weight: bold; }
        .badge-s { color: #2563eb; font-weight: bold; }
        .badge-i { color: #7e22ce; font-weight: bold; }
        .badge-a { color: #e11d48; font-weight: bold; }
        .badge-l { color: #94a3b8; }
        .jk-l { color: #3B82F6; font-weight: 500; }
        .jk-p { color: #EF4444; font-weight: 500; }

        td, td * { color: #000 !important; font-weight: 400 !important; }
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
        .header-table td { color: #000 !important; font-weight: 400 !important; }

        .header-table, .report-title, .report-subtitle { page-break-after: avoid; }
        .data-table { page-break-before: avoid; page-break-inside: auto; }
        .section-footer { page-break-inside: avoid; }
        .pdf-legend { margin: 0; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 4px; background-color: #f8fafc; }
        .footer-table { width: 100%; margin-top: 8px; page-break-inside: avoid; }
        .footer-table td { vertical-align: top; font-size: 8.5pt; }
        .footer-table td.f-left { width: 58%; padding-right: 20px; }
        .footer-table td.f-right { width: 42%; text-align: right; }
        .footer-table td.f-right .sig-inline { display: inline-block; text-align: left; }
        .signature-space { height: 34px; }
    
        /* Warna keterangan sama dengan warna status di tabel */
        .pdf-legend .lg-h { color: #059669 !important; }
        .pdf-legend .lg-t { color: #d97706 !important; }
        .pdf-legend .lg-s { color: #2563eb !important; }
        .pdf-legend .lg-i { color: #7e22ce !important; }
        .pdf-legend .lg-a { color: #e11d48 !important; }
        .pdf-legend .lg-l { color: #94a3b8 !important; }
    </style>
</head>
<body>

@php
    $logoSrc = $logoBase64 ?? null;
    if (!$logoSrc) {
        $lPath = public_path(\App\Models\Setting::getLogo());
        if (is_file($lPath)) {
            $mime = mime_content_type($lPath) ?: 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($lPath));
        }
    }
    $npsn = \App\Models\Setting::get('school_npsn', '20102030');
    $resolvedActiveYear = $activeYear ?? \App\Models\AcademicYear::getActive();
    $signPlaceholder = \App\Exports\MultiPeriodAttendanceExport::NAME_PLACEHOLDER;
    $legendLines = array_pad(array_values(\App\Exports\MultiPeriodAttendanceExport::pdfLegendLines(false)), 3, '');
    $printedAt = $printedAt ?? \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i');
    $signedAt = $signedAt ?? \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y');
    $dateColumns = $dateColumns ?? [];
@endphp

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
                {{ $schoolAddress ?? \App\Models\Setting::getSchoolAddress() }} &bull; NPSN: {{ $npsn }} &bull; {!! __('Tahun Ajaran') !!} : {{ $resolvedActiveYear?->name ?? '-' }}
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

@if($type === 'harian')
@php
    $_n = count($dataRows ?? []);
    $_density = $_n <= 16 ? '' : ($_n <= 24 ? 'compact' : ($_n <= 34 ? 'tight' : 'tight'));
@endphp
<table class="data-table {{ $_density }}">
    <thead>
        <tr>
            <th style="width: 4%;">{!! __('No') !!}</th>
            <th style="width: 16%;">{!! __('NIP') !!}</th>
            <th style="text-align: left; width: 34%;">{!! __('Nama Guru') !!}</th>
            <th style="width: 4%;">{!! __('JK') !!}</th>
            <th style="width: 12%;">{!! __('Jam Masuk') !!}</th>
            <th style="width: 12%;">{!! __('Status') !!}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($dataRows as $row)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $row['nip'] ?: '-' }}</td>
            <td class="text-left"><strong>{{ mb_strtoupper($row['name'] ?? '', 'UTF-8') }}</strong></td>
            <td class="{{ ($row['jk'] ?? 'L') === 'P' ? 'jk-p' : 'jk-l' }}">{{ $row['jk'] ?? 'L' }}</td>
            <td>{{ $row['check_in'] }}</td>
            <td>
                @php $st = $row['status']; @endphp
                @if($st === 'Hadir') <span class="badge-h">{!! __('Hadir') !!}</span>
                @elseif($st === 'Sakit') <span class="badge-s">{!! __('Sakit') !!}</span>
                @elseif($st === 'Izin') <span class="badge-i">{!! __('Izin') !!}</span>
                @elseif($st === 'Alfa') <span class="badge-a">{!! __('Alfa') !!}</span>
                @elseif($st === 'Libur') <span class="badge-l">{!! __('Libur') !!}</span>
                @else <span class="badge-l">-</span> @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="padding: 16px; color: #64748b;">{!! __('Tidak ada data presensi.') !!}</td>
        </tr>
        @endforelse
    </tbody>
</table>
@elseif(in_array($type, ['semester', 'tahunan'], true))
@php
    $wNo = 4; $wNip = 14; $wNama = 22; $wJk = 3;
    $wTetap = $wNo + $wNip + $wNama + $wJk;
    $wRingkasan = (2.6 * 5) + 4;
    $wBulan = (100 - $wTetap - $wRingkasan) / max(1, count($months));
@endphp
<table class="data-table">
    <thead>
        <tr>
            <th style="width: {{ $wNo }}%;">{!! __('No') !!}</th>
            <th style="width: {{ $wNip }}%;">{!! __('NIP') !!}</th>
            <th style="text-align: left; width: {{ $wNama }}%;">{!! __('Nama Guru') !!}</th>
            <th style="width: {{ $wJk }}%;">{!! __('JK') !!}</th>
            @foreach($months as $mInfo)
                <th style="padding: 2px 1px; width: {{ $wBulan }}%;">
                    {{ $mInfo['label'] }}
                    <div style="font-size: 6pt; font-weight: normal; text-transform: none;">{{ $mInfo['year'] }}</div>
                </th>
            @endforeach
            <th style="width: 2.6%; background-color: #059669;">H</th>
            <th style="width: 2.6%; background-color: #2563eb;">S</th>
            <th style="width: 2.6%; background-color: #7e22ce;">I</th>
            <th style="width: 2.6%; background-color: #e11d48;">A</th>
            <th style="width: 4%;">%</th>
        </tr>
    </thead>
    <tbody>
        @forelse($dataRows as $row)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $row['nip'] ?: '-' }}</td>
            <td class="text-left"><strong>{{ mb_strtoupper($row['name'] ?? '', 'UTF-8') }}</strong></td>
            <td class="{{ ($row['jk'] ?? 'L') === 'P' ? 'jk-p' : 'jk-l' }}">{{ $row['jk'] ?? 'L' }}</td>
            @foreach($months as $mInfo)
                @php $cell = $row['monthly'][$mInfo['key']] ?? null; @endphp
                <td>{{ $cell === null ? '-' : $cell }}</td>
            @endforeach
            <td class="badge-h">{{ $row['hadir'] ?? 0 }}</td>
            <td class="badge-s">{{ $row['sakit'] ?? 0 }}</td>
            <td class="badge-i">{{ $row['izin'] ?? 0 }}</td>
            <td class="badge-a">{{ $row['alfa'] ?? 0 }}</td>
            <td><strong>{{ $row['percentage'] ?? 0 }}%</strong></td>
        </tr>
        @empty
        <tr>
            <td colspan="{{ count($months) + 9 }}" style="padding: 16px; color: #64748b;">{!! __('Tidak ada data presensi.') !!}</td>
        </tr>
        @endforelse
    </tbody>
</table>
@else
@php
    $wNo = 4; $wNip = 14; $wNama = 22; $wJk = 3;
    $wTetap = $wNo + $wNip + $wNama + $wJk;
    $wRingkasan = (2.6 * 5) + 4;
    $wTanggal = (100 - $wTetap - $wRingkasan) / max(1, count($dateColumns));
    $isBulanan = $type === 'bulanan';
@endphp
<table class="data-table">
    <thead>
        <tr>
            <th style="width: {{ $wNo }}%;">{!! __('No') !!}</th>
            <th style="width: {{ $wNip }}%;">{!! __('NIP') !!}</th>
            <th style="text-align: left; width: {{ $wNama }}%;">{!! __('Nama Guru') !!}</th>
            <th style="width: {{ $wJk }}%;">{!! __('JK') !!}</th>
            @foreach($dateColumns as $d)
                <th style="padding: 2px 1px; width: {{ $wTanggal }}%;">{{ $d['label'] }}</th>
            @endforeach
            <th style="width: 2.6%; background-color: #059669;">H</th>
            <th style="width: 2.6%; background-color: #2563eb;">S</th>
            <th style="width: 2.6%; background-color: #7e22ce;">I</th>
            <th style="width: 2.6%; background-color: #e11d48;">A</th>
            <th style="width: 4%;">%</th>
        </tr>
    </thead>
    <tbody>
        @forelse($dataRows as $row)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $row['nip'] ?: '-' }}</td>
            <td class="text-left" @if($isBulanan) style="font-size: 7pt;" @endif><strong>{{ mb_strtoupper($row['name'] ?? '', 'UTF-8') }}</strong></td>
            <td class="{{ ($row['jk'] ?? 'L') === 'P' ? 'jk-p' : 'jk-l' }}">{{ $row['jk'] ?? 'L' }}</td>
            @foreach($dateColumns as $d)
                @php
                    $code = $row['days'][$d['date']] ?? '-';
                    $cls = match($code) {
                        'H' => 'badge-h', 'S' => 'badge-s',
                        'I' => 'badge-i', 'A' => 'badge-a', default => 'badge-l',
                    };
                @endphp
                <td class="{{ $cls }}" style="padding: 2px 1px; @if($isBulanan) font-size: 7pt; @endif">{{ $code }}</td>
            @endforeach
            <td class="badge-h">{{ $row['hadir'] ?? 0 }}</td>
            <td class="badge-s">{{ $row['sakit'] ?? 0 }}</td>
            <td class="badge-i">{{ $row['izin'] ?? 0 }}</td>
            <td class="badge-a">{{ $row['alfa'] ?? 0 }}</td>
            <td><strong>{{ $row['percentage'] ?? 0 }}%</strong></td>
        </tr>
        @empty
        <tr>
            <td colspan="{{ count($dateColumns) + 9 }}" style="padding: 16px; color: #64748b;">{!! __('Tidak ada data presensi.') !!}</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endif

<!-- LEGENDA (kiri) + TANDA TANGAN (kanan), satu baris agar muat 1 halaman -->
<div class="section-footer">
    <table class="footer-table" @if(($type ?? '') === 'harian' && count($dataRows ?? []) > 24) style="margin-top: 4px;" @endif>
        <tr>
            <td class="f-left">
                <div class="pdf-legend">
                    <div style="font-size: 9pt; font-weight: bold; color: #1e293b; margin-bottom: 3px;">{{ $legendLines[0] ?? '' }}</div>
                    @php
                        $_lgClass = ['H' => 'lg-h', 'T' => 'lg-t', 'S' => 'lg-s', 'I' => 'lg-i', 'A' => 'lg-a', 'L' => 'lg-l'];
                        $_lgParts = array_values(array_filter(array_map('trim', explode(',', (string) ($legendLines[1] ?? ''))), 'strlen'));
                    @endphp
                    <div style="font-size: 9pt; color: #334155; line-height: 1.5;">
                        @foreach($_lgParts as $_lgPart)
                            @php $_lgCode = strtoupper(trim(strtok($_lgPart, '='))); @endphp
                            <span class="{{ $_lgClass[$_lgCode] ?? '' }}">{{ $_lgPart }}</span>@if(!$loop->last), @endif
                        @endforeach
                    </div>
                </div>
            </td>
            <td class="f-right">
                {{-- Guru tidak punya wali kelas: hanya tanda tangan Kepala Sekolah (kanan). --}}
                <div class="sig-inline">
                    Parung Panjang, {{ $signedAt }}<br>
                    {!! __('Kepala Sekolah') !!}
                    <div class="signature-space"></div>
                    <strong><u>{{ $headmasterName ?: $signPlaceholder }}</u></strong><br>
                    NIP. {{ $headmasterNip ?: '-' }}
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>