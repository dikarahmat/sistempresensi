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

        .signature-table {
            width: 100%;
            margin-top: 24px;
            page-break-inside: avoid;
        }
        .signature-table td {
            text-align: center;
            font-size: 8.5pt;
            width: 50%;
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
                    {{ $schoolAddress ?? \App\Models\Setting::getSchoolAddress() }} &bull; NPSN: {{ $npsn }} &bull; Tahun Ajaran: {{ $resolvedActiveYear?->name ?? '2025/2026' }}
                </div>
            </td>
            <td style="text-align: right; font-size: 8pt; color: #64748b;">
                Dicetak pada:<br>
                <strong>{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB</strong>
            </td>
        </tr>
    </table>

    <div class="report-title">{{ $reportHeading }}</div>
    <div class="report-subtitle">{{ $reportSubheading }}</div>

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
            @forelse($dataRows as $idx => $row)
            <tr>
                <td>{{ $idx + 1 }}</td>
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
    @php
        $groupedClasses = collect($dataRows)->groupBy(fn($r) => $r['student']->schoolClass?->name ?? 'Lainnya');
    @endphp
    @foreach($groupedClasses as $classNameGroup => $classRows)
    @if(count($groupedClasses) > 1)
        <div style="font-size: 10pt; font-weight: bold; margin-top: 8px; margin-bottom: 4px; color: #1d4ed8;">
            Kelas: {{ $classNameGroup }}
        </div>
    @endif
    <table class="data-table" style="margin-bottom: 12px;">
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
            @forelse($classRows as $idx => $row)
            <tr>
                <td>{{ $idx + 1 }}</td>
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
    @if(!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
    @endforeach

    @else
    <!-- BULANAN -->
    @php
        $groupedClasses = collect($dataRows)->groupBy(fn($r) => $r['student']->schoolClass?->name ?? 'Lainnya');
    @endphp
    @foreach($groupedClasses as $classNameGroup => $classRows)
    @if(count($groupedClasses) > 1)
        <div style="font-size: 10pt; font-weight: bold; margin-top: 8px; margin-bottom: 4px; color: #1d4ed8;">
            Kelas: {{ $classNameGroup }}
        </div>
    @endif
    <table class="data-table" style="margin-bottom: 12px;">
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
            @forelse($classRows as $idx => $row)
            <tr>
                <td>{{ $idx + 1 }}</td>
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
    @if(!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
    @endforeach
    @endif

    <!-- TANDA TANGAN -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                Kepala Sekolah
                <div style="height: 50px;"></div>
                <strong><u>{{ $headmasterName ?? \App\Models\Setting::getHeadmasterName() }}</u></strong><br>
                NIP. {{ $headmasterNip ?? \App\Models\Setting::getHeadmasterNip() }}
            </td>
            <td>
                Parung Panjang, {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y') }}<br>
                {{ $rightSignatoryTitle ?? (!empty($teacherName) ? 'Wali Kelas' : 'Petugas Presensi') }}
                <div style="height: 50px;"></div>
                <strong><u>{{ $teacherName ?? '( .................................................. )' }}</u></strong><br>
                NIP. {{ $teacherNip ?? '-' }}
            </td>
        </tr>
    </table>

</body>
</html>