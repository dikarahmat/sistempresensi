<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Presensi Siswa</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 14mm; }
        * { font-family: Helvetica, Arial, sans-serif; }
        body { margin: 0; color: #1e293b; font-size: 9pt; line-height: 1.35; }

        /* ===== KOP (sama dengan Per Kelas) ===== */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #0f172a; margin-bottom: 10px; }
        .header-table td { vertical-align: middle; padding-bottom: 8px; }
        .school-title { font-size: 15pt; font-weight: bold; color: #1d4ed8; text-transform: uppercase; letter-spacing: 0.5px; }
        .school-subtitle { font-size: 8pt; color: #475569; margin-top: 2px; }
        .printed-at { text-align: right; font-size: 7.5pt; color: #64748b; }
        .printed-at strong { color: #0f172a; }

        .report-title { font-size: 11.5pt; font-weight: bold; text-align: center; text-transform: uppercase; color: #0f172a; margin: 4px 0 2px; }
        .report-subtitle { font-size: 8.5pt; text-align: center; color: #64748b; margin-bottom: 10px; }

        /* ===== IDENTITAS SISWA =====
           Dulu blok ini melebar 100% halaman, sehingga kelompok kiri
           (Nama & NISN) terdorong ke ujung kiri dan kelompok kanan
           (Kelas & Jenis Kelamin) terdorong ke ujung kanan, menyisakan
           lubang kosong besar di tengah.

           CATATAN Teknis: DomPDF TIDAK mendukung "margin: 0 auto" untuk
           memusatkan tabel (hasilnya tetap mepet kiri). Karena itu posisi
           tengah dipaksa dengan margin kiri/kanan eksplisit yang sama besar:
               74% (tabel) + 13% + 13% = 100% lebar area halaman.
           Ukuran 74% tetap dalam rentang 70%-75% yang diminta, dan blok
           jadi simetris terhadap tabel utama di bawahnya.

           Proporsi kolom (sama di kedua baris supaya lurus):
             19% label | 4% ":" | 31% nilai | 7% gutter | 21% label | 4% ":" | 14% nilai = 100% */
        .identity {
            width: 74%;
            margin-left: 13%;
            margin-right: 13%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .identity td { border: none; padding: 1.5px 0; font-size: 9.5pt; vertical-align: top; }
        .identity .lbl, .identity .lbl2, .identity .sep { white-space: nowrap; color: #475569; }
        /* Label digeser sedikit ke kanan supaya "Nama" tidak menempel batas kiri */
        .identity .lbl { padding-left: 4px; }
        .identity .val { font-weight: normal; color: #0f172a; overflow-wrap: break-word; }

        /* ===== TABEL RIWAYAT (header biru, border tipis) ===== */
        .tbl { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 6px; }
        .tbl th { background: #2563eb; color: #ffffff; font-weight: bold; text-transform: uppercase; font-size: 7pt; padding: 5px 3px; border: 1px solid #2563eb; text-align: center; }
        .tbl td { border: 1px solid #cbd5e1; padding: 3px 4px; font-size: 8pt; text-align: center; word-wrap: break-word; }
        .tbl tbody tr:nth-child(even) td { background: #f8fafc; }
        .tbl thead { display: table-header-group; }
        .tbl tr { page-break-inside: avoid; }
        .text-left { text-align: left !important; }

        /* simbol status berwarna */
        .st { font-weight: bold; font-size: 9pt; }
        .st-H { color: #059669; }
        .st-T { color: #d97706; }
        .st-S { color: #2563eb; }
        .st-I { color: #7e22ce; }
        .st-A { color: #e11d48; }
        .st-L { color: #94a3b8; }

        /* ===== KETERANGAN + TANDA TANGAN (satu blok, tidak terpisah halaman) ===== */
        .closing { page-break-inside: avoid; margin-top: 8px; }
        .legend-box { border: 1px solid #e2e8f0; background: #f8fafc; padding: 7px 10px; }
        .legend-title { font-size: 9pt; font-weight: bold; margin-bottom: 3px; }
        .legend-text { font-size: 8.5pt; color: #334155; }
        .legend-note { font-size: 7.5pt; color: #2563eb; margin-top: 3px; }

        /* Tanda tangan: 2 KOLOM PENUH (tanpa border). Kolom kiri (Kepala
           Sekolah) rata kiri. Kolom kanan diposisikan ke tepi kanan oleh
           text-align:right pada td, tetapi ISI teksnya sendiri rata kiri
           lewat wrapper inline-block supaya "Wali Kelas" dan NIP sejajar tepat
           di bawah huruf pertama "Parung Panjang". */
        .signature-table { width: 100%; margin-top: 16px; border-collapse: collapse; }
        .signature-table td { width: 50%; font-size: 9pt; vertical-align: top; text-align: left; }
        .signature-table td.sig-right { text-align: right; }
        .signature-table td.sig-right .sig-inline { display: inline-block; text-align: left; }
        .signature-line { height: 46px; }
    </style>
</head>
<body>

@php
    $logoSrc = $logoBase64 ?? null;
    if (!$logoSrc) {
        $logoRel = \App\Models\Setting::getLogo();
        $lPath = $logoRel ? public_path($logoRel) : null;
        if ($lPath && is_file($lPath)) {
            $mime = @mime_content_type($lPath) ?: 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($lPath));
        }
    }
    $npsn = \App\Models\Setting::get('school_npsn', '20102030');
    $resolvedActiveYear = $activeYear ?? \App\Models\AcademicYear::getActive();
    $yearName = $resolvedActiveYear?->name ?? '-';
    $signPlaceholder = \App\Exports\MultiPeriodAttendanceExport::NAME_PLACEHOLDER;
    $printedAt = $printedAt ?? \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i') . ' WIB';
    $signedAt = \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y');
    $schoolName = $schoolName ?? '';
    $schoolAddress = $schoolAddress ?? '';
    $periodLabel = $periodLabel ?? '-';
    // Jenis periode untuk judul: HARIAN / MINGGUAN / BULANAN / SEMESTER / TAHUNAN
    $periodTitle = !empty($periodType ?? null) ? strtoupper($periodType) : '';
    $headmasterName = $headmasterName ?? \App\Models\Setting::getHeadmasterName();
    $headmasterNip = $headmasterNip ?? \App\Models\Setting::getHeadmasterNip();

    // Status -> simbol
    $statusSymbol = [
        'Hadir' => 'H', 'Terlambat' => 'T', 'Sakit' => 'S',
        'Izin' => 'I', 'Alfa' => 'A', 'Libur' => 'L',
    ];
@endphp

@foreach($studentsData as $data)
    @if(!$loop->first)
        <div style="page-break-after: always;"></div>
    @endif

    @php
        $student = $data['student'];
        $rows = $data['rows'];
        $className = $student->schoolClass?->name ?? '-';
        // Wali kelas: utamakan data dari controller (guru yang menjabat wali kelas)
        $waliName = $data['wali_name'] ?? $student->schoolClass?->teacher?->name;
        $waliNip = $data['wali_nip'] ?? $student->schoolClass?->teacher?->nip;
    @endphp

    <!-- KOP SEKOLAH -->
    <table class="header-table">
        <tr>
            <td style="width: 60px;">
                @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" style="width: 52px; height: 52px;">
                @endif
            </td>
            <td style="padding-left: 8px;">
                <div class="school-title">{{ $schoolName }}</div>
                <div class="school-subtitle">{{ $schoolAddress }} &bull; NPSN: {{ $npsn }} &bull; Tahun Ajaran: {{ $yearName }}</div>
            </td>
            <td class="printed-at" style="width: 150px;">
                Dicetak pada:<br>
                <strong>{{ $printedAt }}</strong>
            </td>
        </tr>
    </table>

    <div class="report-title">Laporan Riwayat Presensi Siswa{{ $periodTitle ? ' - ' . $periodTitle : '' }}</div>
    <div class="report-subtitle">Periode: {{ $periodLabel }} | Kelas: {{ $className }} | Tahun Ajaran: {{ $yearName }}</div>

    <!-- IDENTITAS SISWA: blok rapat 74% lebar, dipusatkan pakai margin
         kiri/kanan 13% (DomPDF tidak mendukung margin: 0 auto). -->
    <table class="identity">
        <tr>
            <td class="lbl" style="width: 19%">Nama</td>
            <td class="sep" style="width: 4%">:</td>
            <td class="val" style="width: 31%">{{ $student->name }}</td>
            <td style="width: 7%"></td>
            <td class="lbl2" style="width: 21%">Kelas</td>
            <td class="sep" style="width: 4%">:</td>
            <td class="val" style="width: 14%">{{ $className }}</td>
        </tr>
        <tr>
            <td class="lbl" style="width: 19%">NISN</td>
            <td class="sep" style="width: 4%">:</td>
            <td class="val" style="width: 31%">{{ $student->nisn ?: '-' }}</td>
            <td style="width: 7%"></td>
            <td class="lbl2" style="width: 21%">Jenis Kelamin</td>
            <td class="sep" style="width: 4%">:</td>
            <td class="val" style="width: 14%">{{ $student->gender ?? '-' }}</td>
        </tr>
    </table>

    <!-- RIWAYAT PRESENSI -->
    <table class="tbl">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 12%;">Hari</th>
                <th style="width: 14%;">Tanggal</th>
                <th style="width: 14%;">Jam Masuk</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 17%;">Keterlambatan</th>
                <th style="width: 28%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                @php $sym = $statusSymbol[$row['status']] ?? null; @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($row['tanggal_raw'])->translatedFormat('l') }}</td>
                    <td>{{ \Carbon\Carbon::parse($row['tanggal_raw'])->translatedFormat('d/m/Y') }}</td>
                    <td>{{ $row['jam_masuk'] }}</td>
                    <td>
                        @if($sym)
                            <span class="st st-{{ $sym }}">{{ $sym }}</span>
                        @else
                            {{ $row['status'] ?: '-' }}
                        @endif
                    </td>
                    <td>{{ $row['keterlambatan'] }}</td>
                    <td class="text-left">{{ $row['keterangan'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding: 14px; color: #64748b;">Tidak ada data presensi untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- KETERANGAN + TANDA TANGAN (satu blok) -->
    <div class="closing">
        <div class="legend-box">
            <div class="legend-title">KETERANGAN:</div>
            <div class="legend-text">
                <span class="st st-H">H</span> = Hadir,
                <span class="st st-T">T</span> = Terlambat,
                <span class="st st-S">S</span> = Sakit,
                <span class="st st-I">I</span> = Izin,
                <span class="st st-A">A</span> = Alfa,
                <span class="st st-L">L</span> = Libur
            </div>
            <div class="legend-note">Format Keterlambatan: kurang dari 60 menit ditulis &quot;15 MNT&quot;, 60 menit atau lebih ditulis &quot;1 JAM 5 MNT&quot;.</div>
        </div>

        <table class="signature-table">
            <tr>
                <td>
                    Mengetahui,<br>
                    Kepala Sekolah
                    <div class="signature-line"></div>
                    <strong><u>{{ $headmasterName ?: $signPlaceholder }}</u></strong><br>
                    NIP. {{ $headmasterNip ?: '-' }}
                </td>
                <td class="sig-right">
                    {{-- td tetap text-align:right supaya bloknya menempel kanan halaman,
                         wrapper inline-block membuat isinya rata KIRI sehingga
                         "Wali Kelas" sejajar di bawah huruf pertama "Parung Panjang". --}}
                    <div class="sig-inline">
                        Parung Panjang, {{ $signedAt }}<br>
                        Wali Kelas
                        <div class="signature-line"></div>
                        <strong><u>{{ $waliName ?: $signPlaceholder }}</u></strong><br>
                        NIP. {{ $waliNip ?: '-' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endforeach

</body>
</html>