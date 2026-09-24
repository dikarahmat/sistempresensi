<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Presensi Kesiswaan - {{ $className }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 8.5pt;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        /* Kop Surat Instansi / Sekolah */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .kop-table td {
            vertical-align: middle;
        }

        .kop-logo {
            width: 65px;
            text-align: center;
        }

        .kop-logo img {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        .kop-text {
            text-align: center;
            padding-right: 65px; /* Mengimbangi logo di kiri agar judul tepat di tengah */
        }

        .kop-title {
            font-size: 13pt;
            font-weight: bold;
            color: #1d4ed8;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }

        .kop-subtitle {
            font-size: 9pt;
            font-weight: bold;
            color: #0f172a;
            margin: 2px 0 0;
            text-transform: uppercase;
        }

        .kop-address {
            font-size: 8pt;
            color: #475569;
            margin-top: 2px;
        }

        /* Garis Ganda Kop */
        .kop-divider {
            border-top: 2.5px solid #0f172a;
            border-bottom: 0.75px solid #0f172a;
            height: 3px;
            margin-bottom: 10px;
        }

        /* Judul Laporan */
        .report-title-box {
            text-align: center;
            margin-bottom: 12px;
        }

        .report-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0;
        }

        .report-subtitle {
            font-size: 8.5pt;
            color: #475569;
            margin-top: 3px;
        }



        /* Tabel Rekapitulasi Data */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-bottom: 16px;
        }

        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            text-align: center;
            vertical-align: middle;
        }

        .data-table th {
            background-color: #2563eb;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.2pt;
            padding: 5px 4px;
        }

        .data-table tbody tr:nth-child(even) td {
            background-color: #fbfdff;
        }

        .text-left { text-align: left !important; }
        .text-center { text-align: center !important; }

        /* Badge persentase */
        .badge-pct {
            font-weight: bold;
            padding: 1px 4px;
            border-radius: 3px;
        }

        /* Kolom Tanda Tangan */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
            font-size: 8.5pt;
            line-height: 1.35;
        }

        .signature-space {
            height: 52px;
        }
    </style>
</head>
<body>

    <!-- KOP INSTANSI / SEKOLAH -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo">
                @endif
            </td>
            <td class="kop-text">
                <div class="kop-title">{{ $schoolName }}</div>
                <div class="kop-subtitle">BAGIAN KESISWAAN & KEMAHASISWAAN</div>
                <div class="kop-address">
                    NPSN: {{ $schoolNpsn }} | Alamat: {{ $schoolAddress }}
                </div>
            </td>
        </tr>
    </table>

    <!-- GARIS PEMISAH KOP -->
    <div class="kop-divider"></div>

    <!-- JUDUL LAPORAN -->
    <div class="report-title-box">
        <div class="report-title">LAPORAN REKAPITULASI PRESENSI SISWA</div>
        <div class="report-subtitle">
            Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}</strong> 
            ({{ $effectiveDaysCount }} Hari Efektif) | Rombel: <strong>{{ $className }}</strong> | Tahun Ajaran: <strong>{{ $activeYear ? $activeYear->name : (\App\Models\AcademicYear::getActive()?->name ?? '-') }}</strong>
        </div>
    </div>

    <!-- TABEL DATA REKAPITULASI -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 60px;">NIS</th>
                <th style="width: 70px;">NISN</th>
                <th class="text-left">Nama Siswa</th>
                <th style="width: 55px;">Kelas</th>
                <th class="text-left" style="width: 120px;">Wali Kelas</th>
                <th style="width: 25px;">JK</th>
                <th style="width: 35px;">H</th>
                <th style="width: 35px;">T</th>
                <th style="width: 35px;">S</th>
                <th style="width: 35px;">I</th>
                <th style="width: 35px;">A</th>
                <th style="width: 45px;">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $r)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $r['student']->nis }}</td>
                <td>{{ $r['student']->nisn ?? '-' }}</td>
                <td class="text-left"><strong>{{ $r['student']->name }}</strong></td>
                <td>{{ $r['student']->schoolClass?->name ?? '-' }}</td>
                <td class="text-left">{{ $r['student']->schoolClass?->teacher?->name ?? '-' }}</td>
                <td>{{ $r['student']->gender === 'Laki-laki' ? 'L' : 'P' }}</td>
                <td style="color: #15803d; font-weight: bold;">{{ $r['hadir'] }}</td>
                <td style="color: #a16207;">{{ $r['terlambat'] }}</td>
                <td style="color: #b45309;">{{ $r['sakit'] }}</td>
                <td style="color: #0369a1;">{{ $r['izin'] }}</td>
                <td style="color: #b91c1c; font-weight: bold;">{{ $r['alfa'] }}</td>
                <td>
                    <span class="badge-pct">{{ $r['percentage'] }}%</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="13" style="padding: 15px; color: #64748b;">
                    Tidak ada data rekap presensi pada periode tanggal yang dipilih.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if(count($rows) > 0)
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="7" style="text-align: center;">TOTAL AKUMULASI KESELURUHAN</td>
                <td style="color: #15803d;">{{ $totalHadir }}</td>
                <td style="color: #a16207;">{{ $totalTerlambat }}</td>
                <td style="color: #b45309;">{{ $totalSakit }}</td>
                <td style="color: #0369a1;">{{ $totalIzin }}</td>
                <td style="color: #b91c1c;">{{ $totalAlfa }}</td>
                <td style="color: #1d4ed8;">{{ $avgPercentage }}%</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- KOLOM TANDA TANGAN -->
    <table class="signature-table">
        <tr>
            <!-- Tanda Tangan Kiri: Kepala Sekolah -->
            <td style="padding-left: 20px;">
                Mengetahui,<br>
                Kepala Sekolah
                <div class="signature-space"></div>
                <strong><u>{{ $headmasterName ?? \App\Models\Setting::getHeadmasterName() }}</u></strong><br>
                NIP. {{ $headmasterNip ?? \App\Models\Setting::getHeadmasterNip() }}
            </td>

            <!-- Tanda Tangan Kanan: Bagian Kesiswaan -->
            <td style="text-align: right; padding-right: 20px;">
                Parung Panjang, {{ $printedAt }}<br>
                Bagian Kesiswaan
                <div class="signature-space"></div>
                <strong>{{ $currentUser->name ?? 'Staf Kesiswaan' }}</strong><br>
                NIP/ID: {{ $currentUser->id ? 'KSW-' . str_pad($currentUser->id, 4, '0', STR_PAD_LEFT) : '-' }}
            </td>
        </tr>
    </table>

</body>
</html>
