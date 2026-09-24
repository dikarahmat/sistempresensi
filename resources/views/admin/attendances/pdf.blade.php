<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Presensi - {{ $className }} - {{ $monthName }} {{ $year }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.2cm 1.5cm 1.2cm 1.5cm;
        }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 8px;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }
        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .kop-logo {
            width: 50px;
            vertical-align: middle;
        }
        .kop-text {
            text-align: center;
            vertical-align: middle;
        }
        .school-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .school-info {
            font-size: 9px;
            color: #475569;
        }

        /* Judul Dokumen */
        .doc-title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .doc-meta {
            text-align: center;
            font-size: 8.5px;
            color: #475569;
            margin-bottom: 12px;
        }

        /* Tabel Rekap Presensi */
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
        }
        .matrix-table th, .matrix-table td {
            border: 0.5px solid #cbd5e1;
            padding: 3px 1.5px;
            text-align: center;
            vertical-align: middle;
        }
        .matrix-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #1e293b;
        }
        .matrix-table th.th-holiday {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .matrix-table td.td-name {
            text-align: left;
            padding-left: 4px;
            white-space: nowrap;
            font-weight: 500;
        }
        .matrix-table td.td-holiday {
            background-color: #f8fafc;
            color: #94a3b8;
        }

        .code-H { color: #166534; font-weight: bold; }
        .code-T { color: #b45309; font-weight: bold; }
        .code-S { color: #1d4ed8; font-weight: bold; }
        .code-I { color: #7e22ce; font-weight: bold; }
        .code-A { color: #dc2626; font-weight: bold; }

        /* Tanda Tangan */
        .signature-table {
            width: 100%;
            margin-top: 25px;
            page-break-inside: avoid;
            font-size: 8.5px;
        }
        .signature-table td {
            vertical-align: top;
            width: 50%;
        }
        .sig-space {
            height: 50px;
        }
    </style>
</head>
<body>

    @php
        $logoSrc = $logoBase64 ?? null;
        if (!$logoSrc) {
            $logoPath = public_path(\App\Models\Setting::getLogo());
            if (file_exists($logoPath)) {
                $mime = mime_content_type($logoPath) ?: 'image/png';
                $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            }
        }
    @endphp

    <!-- Kop Surat -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" style="width: 46px; height: 46px; object-fit: contain;">
                @endif
            </td>
            <td class="kop-text">
                <div class="school-name">{{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</div>
                <div class="school-info">{{ $schoolAddress ?? \App\Models\Setting::getSchoolAddress() }} | Sistem Presensi Digital Siswa</div>
            </td>
            <td style="width: 50px;"></td>
        </tr>
    </table>

    <!-- Judul & Meta -->
    <div class="doc-title">REKAPITULASI LAPORAN PRESENSI SISWA</div>
    <div class="doc-meta">
        Bulan: <strong>{{ $monthName }} {{ $year }}</strong> &nbsp;|&nbsp;
        Kelas: <strong>{{ $className }}</strong> &nbsp;|&nbsp;
        Tahun Ajaran: <strong>{{ $activeYear ? $activeYear->name : (\App\Models\AcademicYear::getActive()?->name ?? '-') }}</strong>
    </div>

    <!-- Matriks Presensi -->
    <table class="matrix-table">
        <thead>
            <tr>
                <th style="width: 20px;">No</th>
                <th style="width: 45px;">NIS</th>
                <th style="min-width: 120px; text-align: left; padding-left: 4px;">Nama Siswa</th>
                <th style="width: 18px;">JK</th>
                @for($d = 1; $d <= $daysInMonth; $d++)
                    <th class="{{ isset($holidayMap[$d]) ? 'th-holiday' : '' }}" style="width: 16px;">{{ $d }}</th>
                @endfor
                <th style="width: 18px; background-color: #dcfce7; color: #166534;">H</th>
                <th style="width: 18px; background-color: #fef3c7; color: #b45309;">T</th>
                <th style="width: 18px; background-color: #dbeafe; color: #1d4ed8;">S</th>
                <th style="width: 18px; background-color: #f3e8ff; color: #7e22ce;">I</th>
                <th style="width: 18px; background-color: #fee2e2; color: #b91c1c;">A</th>
                <th style="width: 25px; background-color: #f1f5f9;">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recap as $index => $row)
            @php $student = $row['student']; @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student->nis }}</td>
                <td class="td-name">{{ $student->name }}</td>
                <td>{{ $student->gender == 'Perempuan' ? 'P' : 'L' }}</td>

                @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $code = $row['days'][$d] ?? '-';
                        $isHol = isset($holidayMap[$d]);
                    @endphp
                    <td class="{{ $isHol ? 'td-holiday' : '' }}">
                        <span class="code-{{ $code }}">{{ $code }}</span>
                    </td>
                @endfor

                <td style="font-weight: bold; color: #166534;">{{ $row['h'] }}</td>
                <td style="font-weight: bold; color: #b45309;">{{ $row['t'] }}</td>
                <td style="font-weight: bold; color: #1d4ed8;">{{ $row['s'] }}</td>
                <td style="font-weight: bold; color: #7e22ce;">{{ $row['i'] }}</td>
                <td style="font-weight: bold; color: #dc2626;">{{ $row['a'] }}</td>
                <td style="font-weight: bold;">{{ $row['pct'] }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ 4 + $daysInMonth + 6 }}" style="padding: 20px; color: #64748b;">
                    Tidak ada data presensi pada periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Keterangan & Tanda Tangan -->
    <table class="signature-table">
        <tr>
            <td style="text-align: left; padding-left: 20px;">
                <div>Keterangan:</div>
                <div style="font-size: 7.5px; color: #475569; margin-top: 2px;">
                    H = Hadir Tepat &nbsp;|&nbsp; T = Terlambat &nbsp;|&nbsp; S = Sakit &nbsp;|&nbsp; I = Izin &nbsp;|&nbsp; A = Alpha &nbsp;|&nbsp; L = Libur/Akhir Pekan
                </div>
            </td>
            <td style="text-align: right; padding-right: 20px;">
                Dicetak pada: {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td style="text-align: center;">
                <div>Mengetahui,</div>
                <div style="font-weight: bold;">Kepala Sekolah</div>
                <div class="sig-space"></div>
                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $headmasterName ?? \App\Models\Setting::getHeadmasterName() }}
                </div>
                <div style="font-size: 7.5px; color: #64748b;">
                    NIP. {{ $headmasterNip ?? \App\Models\Setting::getHeadmasterNip() }}
                </div>
            </td>
            <td style="text-align: center;">
                <div>Menyetujui,</div>
                <div style="font-weight: bold;">{{ $rightSignatoryTitle ?? ($selectedClass ? 'Wali Kelas ' . $selectedClass->name : 'Petugas Presensi') }}</div>
                <div class="sig-space"></div>
                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $teacherName ?? ($selectedClass?->teacher?->name ?? '( .................................................... )') }}
                </div>
                <div style="font-size: 7.5px; color: #64748b;">
                    NIP. {{ $teacherNip ?? ($selectedClass?->teacher?->nip ?? '-') }}
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
