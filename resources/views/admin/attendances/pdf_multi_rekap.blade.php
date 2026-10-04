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



        /* Data Table */
        .data-table {
            width: 100%;
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
        /* Judul kolom DIULANG di setiap halaman saat tabel terpecah. */
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
        .badge-t { color: #d97706; font-weight: bold; }
        .badge-s { color: #2563eb; font-weight: bold; }
        .badge-i { color: #0d9488; font-weight: bold; }
        .badge-a { color: #e11d48; font-weight: bold; }
        .badge-l { color: #94a3b8; }

        /* Legenda + tanda tangan per kelas: tidak boleh terpisah halaman. */
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
            width: 33.33%;
            vertical-align: top;
        }
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
    // Satu blok = satu kelas. Blok pertama langsung menyusul blok sebelumnya,
    // blok berikutnya DIMULAI DI HALAMAN BARU (kop + judul ikut diulang).
    $sections = $sections ?: [];
@endphp

@foreach($sections as $section)
    @if(!$loop->first)
        <div class="page-break" style="page-break-after: always;"></div>
    @endif

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
                    {{ $schoolAddress ?? \App\Models\Setting::getSchoolAddress() }} &bull; NPSN: {{ $npsn }} &bull; Tahun Ajaran: {{ $resolvedActiveYear?->name ?? '2025/2026' }}
                </div>
            </td>
            <td style="text-align: right; font-size: 8pt; color: #64748b;">
                Dicetak pada:<br>
                <strong>{{ $printedAt }} WIB</strong>
            </td>
        </tr>
    </table>

    <div class="report-title">{{ $section['heading'] }}</div>
    <div class="report-subtitle">{{ $section['subtitle'] }}</div>

    <!-- TABEL DATA LAPORAN -->
    @if($type === 'harian')
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 70px;">NIS</th>
                <th style="text-align: left;">Nama Siswa</th>
                <th style="width: 60px;">Kelas</th>
                <th style="width: 30px;">JK</th>
                <th style="width: 70px;">Jam Masuk</th>
                <th style="width: 90px;">Keterlambatan</th>
                <th style="width: 70px;">Status</th>
                <th style="text-align: left;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($section['rows'] as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td>{{ $row['student']->nis }}</td>
                <td class="text-left"><strong>{{ $row['student']->name }}</strong></td>
                <td>{{ $row['student']->schoolClass?->name ?? '-' }}</td>
                <td>{{ $row['student']->gender === 'Laki-laki' ? 'L' : 'P' }}</td>
                <td>{{ $row['check_in'] }}</td>
                <td>{{ $row['late_text'] }}</td>
                <td>
                    @if($row['status'] === 'Hadir')
                        <span class="badge-h">Hadir</span>
                    @elseif($row['status'] === 'Terlambat')
                        <span class="badge-t">Terlambat</span>
                    @elseif($row['status'] === 'Sakit')
                        <span class="badge-s">Sakit</span>
                    @elseif($row['status'] === 'Izin')
                        <span class="badge-i">Izin</span>
                    @elseif($row['status'] === 'Alfa')
                        <span class="badge-a">Alfa</span>
                    @else
                        <span class="badge-l">{{ $row['status'] }}</span>
                    @endif
                </td>
                <td class="text-left" style="font-size: 7.5pt;">{{ $row['notes'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="padding: 16px; color: #64748b;">Tidak ada data presensi.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @elseif($type === 'mingguan')
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 65px;">NIS</th>
                <th style="text-align: left; width: 140px;">Nama Siswa</th>
                <th style="width: 50px;">Kelas</th>
                <th style="width: 25px;">JK</th>
                @foreach($dateColumns as $d)
                    <th>{{ $d['label'] }}</th>
                @endforeach
                <th style="width: 24px; background-color: #059669;">H</th>
                <th style="width: 24px; background-color: #d97706;">T</th>
                <th style="width: 24px; background-color: #2563eb;">S</th>
                <th style="width: 24px; background-color: #0d9488;">I</th>
                <th style="width: 24px; background-color: #e11d48;">A</th>
                <th style="width: 38px;">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse($section['rows'] as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td>{{ $row['student']->nis }}</td>
                <td class="text-left"><strong>{{ $row['student']->name }}</strong></td>
                <td>{{ $row['student']->schoolClass?->name ?? '-' }}</td>
                <td>{{ $row['student']->gender === 'Laki-laki' ? 'L' : 'P' }}</td>
                @foreach($dateColumns as $d)
                    @php
                        $code = $row['days'][$d['date']] ?? '-';
                        $cls = match($code) {
                            'H' => 'badge-h',
                            'T' => 'badge-t',
                            'S' => 'badge-s',
                            'I' => 'badge-i',
                            'A' => 'badge-a',
                            default => 'badge-l'
                        };
                        $val = in_array($code, ['H','T','S','I','A']) ? $code : '-';
                    @endphp
                    <td class="{{ $cls }}">{{ $val }}</td>
                @endforeach
                <td class="badge-h">{{ $row['hadir'] }}</td>
                <td class="badge-t">{{ $row['terlambat'] }}</td>
                <td class="badge-s">{{ $row['sakit'] }}</td>
                <td class="badge-i">{{ $row['izin'] }}</td>
                <td class="badge-a">{{ $row['alfa'] }}</td>
                <td><strong>{{ $row['percentage'] }}%</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($dateColumns) + 11 }}" style="padding: 16px; color: #64748b;">Tidak ada data presensi.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @else
    <!-- BULANAN -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 22px;">No</th>
                <th style="width: 60px;">NIS</th>
                <th style="text-align: left; width: 130px;">Nama Siswa</th>
                <th style="width: 45px;">Kelas</th>
                <th style="width: 20px;">JK</th>
                @for($d = 1; $d <= $daysInMonth; $d++)
                    <th style="padding: 2px 1px; width: 14px;">{{ $d }}</th>
                @endfor
                <th style="width: 20px; background-color: #059669;">H</th>
                <th style="width: 20px; background-color: #d97706;">T</th>
                <th style="width: 20px; background-color: #2563eb;">S</th>
                <th style="width: 20px; background-color: #0d9488;">I</th>
                <th style="width: 20px; background-color: #e11d48;">A</th>
                <th style="width: 32px;">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse($section['rows'] as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td>{{ $row['student']->nis }}</td>
                <td class="text-left" style="font-size: 7pt;"><strong>{{ $row['student']->name }}</strong></td>
                <td>{{ $row['student']->schoolClass?->name ?? '-' }}</td>
                <td>{{ $row['student']->gender === 'Laki-laki' ? 'L' : 'P' }}</td>
                @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $code = $row['days'][$d] ?? '-';
                        $cls = match($code) {
                            'H' => 'badge-h',
                            'T' => 'badge-t',
                            'S' => 'badge-s',
                            'I' => 'badge-i',
                            'A' => 'badge-a',
                            default => 'badge-l'
                        };
                    @endphp
                    <td class="{{ $cls }}" style="padding: 2px 1px; font-size: 7pt;">{{ $code }}</td>
                @endfor
                <td class="badge-h">{{ $row['hadir'] }}</td>
                <td class="badge-t">{{ $row['terlambat'] }}</td>
                <td class="badge-s">{{ $row['sakit'] }}</td>
                <td class="badge-i">{{ $row['izin'] }}</td>
                <td class="badge-a">{{ $row['alfa'] }}</td>
                <td><strong>{{ $row['percentage'] }}%</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $daysInMonth + 11 }}" style="padding: 16px; color: #64748b;">Tidak ada data presensi.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @endif

    <!-- LEGENDA + TANDA TANGAN KELAS INI
         Dibungkus satu blok supaya legenda dan tanda tangan tidak terpisah
         halaman. Isi, urutan baris, dan teksnya SAMA dengan ekspor Excel. -->
    <div class="section-footer">
        <div class="pdf-legend">
            <div style="font-size: 9pt; font-weight: bold; color: #1e293b; margin-bottom: 3px;">{{ $legendLines[0] }}</div>
            <div style="font-size: 9pt; color: #334155; line-height: 1.6;">
                {{ $legendLines[1] }}
            </div>
            <div style="font-size: 8pt; color: #64748b; line-height: 1.5; margin-top: 2px;">
                {{ $legendLines[2] }}
            </div>
        </div>

        <table class="signature-table">
            <tr>
                <td>
                    Mengetahui,<br>
                    Kepala Sekolah
                    <div style="height: 50px;"></div>
                    <strong><u>{{ $headmasterName ?: $signPlaceholder }}</u></strong><br>
                    NIP. {{ $headmasterNip ?: '-' }}
                </td>
                <td>
                    Wali Kelas
                    <div style="height: 50px;"></div>
                    <strong><u>{{ $section['waliName'] ?: $signPlaceholder }}</u></strong><br>
                    NIP. {{ $section['waliNip'] ?: '-' }}
                </td>
                <td>
                    Parung Panjang, {{ $signedAt }}<br>
                    Petugas Presensi
                    <div style="height: 50px;"></div>
                    <strong><u>{{ $signPlaceholder }}</u></strong><br>
                    NIP. -
                </td>
            </tr>
        </table>
    </div>
@endforeach

</body>
</html>
