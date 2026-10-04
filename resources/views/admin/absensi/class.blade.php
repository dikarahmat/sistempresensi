@extends('layouts.app')

@section('title', 'Presensi Kelas ' . ($selectedClass->name ?? ''))
@section('page_title', 'Presensi Kelas ' . ($selectedClass->name ?? '-'))
@section('page_subtitle', \Carbon\Carbon::parse($tanggal ?? now())->translatedFormat('l, d F Y'))

@push('styles')
<style>
    .page-subtitle-date {
        font-size: 0.9rem;
        font-weight: 500;
        color: #475569;
    }
    
    /* Badge Ringkasan Minimalis: pill kecil seperti tombol */
    .status-badge-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.4rem 0.7rem;
        border-radius: 9999px !important;
        font-size: 0.78rem;
        font-weight: 600;
        background-color: #ffffff;
        border: none;
        color: #1e293b;
        line-height: 1.2;
    }
    .icon-hadir { color: #10b981; }
    .icon-terlambat { color: #f59e0b; }
    .icon-sakit { color: #3b82f6; }
    .icon-izin { color: #8b5cf6; }
    .icon-alfa { color: #ef4444; }
    .icon-belum { color: #64748b; }

    /* Badge Status Tabel Solid Jelas */
    .badge-status-solid {
        padding: 0.35rem 0.75rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        display: inline-block;
    }
    .badge-hadir { background-color: #10b981; color: #ffffff; }
    .badge-terlambat { background-color: #f59e0b; color: #ffffff; }
    .badge-sakit { background-color: #3b82f6; color: #ffffff; }
    .badge-izin { background-color: #8b5cf6; color: #ffffff; }
    .badge-alfa { background-color: #ef4444; color: #ffffff; }
    .badge-belum { background-color: #94a3b8; color: #ffffff; }

    /* Tabel Enterprise & Zebra */
    .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
    }
    .table-enterprise {
        min-width: 720px;
    }
    .table-enterprise thead th {
        background-color: #f8fafc;
        color: #000000 !important;
        font-weight: 700 !important;
        font-size: 0.75rem;
        padding: 0.8rem 1rem;
        border-bottom: 1.5px solid #edf2f7;
        white-space: nowrap !important;
    }
    /* Zebra Striping Khusus (Sesuai Benchmark Data Siswa) */
    .table-enterprise tbody tr:nth-child(even) > td,
    .table-enterprise tbody tr.baris-abu > td,
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f1f5f9 !important;
    }
    .table-enterprise tbody tr:nth-child(odd) > td,
    .table-enterprise tbody tr.baris-putih > td,
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-enterprise tbody tr.baris-abu:hover > td,
    .table-enterprise tbody tr.baris-putih:hover > td,
    .table-enterprise tbody tr:hover > td,
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td {
        background-color: #e2e8f0 !important;
    }
    .table-enterprise tbody td {
        padding: 0.7rem 1rem;
        font-size: 0.86rem;
        color: #000000 !important;
        font-weight: 400 !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Dropdown & Tombol Aksi */
    .select-quick-status {
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 6px;
        padding: 0.3rem 0.5rem;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        min-width: 95px;
    }
    .btn-save-quick {
        background-color: #2563eb;
        color: #ffffff;
        border: none;
        border-radius: 6px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .btn-save-quick:hover {
        background-color: #1d4ed8;
    }
    .btn-edit-modal {
        background-color: #f8fafc;
        color: #475569;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .btn-edit-modal:hover {
        background-color: #e2e8f0;
        color: #0f172a;
    }

    /* Card Scanner Mode Gerbang Style di Halaman Detail Kelas */
    .scanner-kiosk-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        padding: 20px 16px;
        text-align: center;
        position: relative;
    }
    @media (min-width: 992px) {
        .scanner-kiosk-card {
            position: sticky;
            top: 24px;
            z-index: 15;
        }
    }

    /* Switcher Pill di Atas Card */
    .kiosk-switcher {
        display: inline-flex;
        background: #f8fafc;
        padding: 4px;
        border-radius: 50rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 1.15rem;
    }

    .kiosk-switch-btn {
        background: transparent;
        border: none;
        padding: 0.42rem 1.1rem;
        border-radius: 50rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.15s ease;
        cursor: pointer;
    }

    .kiosk-switch-btn.active {
        background: #2563eb;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
    }

    /* Viewport / Area Scanner — SATU SUMBER GAYA: presensi-tokens.css
       (#scannerColumn #scannerBox/#reader + .panel-scan-frame, SAMA PERSIS
       dengan kiosk: radius 16px, shadow, aspect 1/1, tanpa mirror,
       bracket 28px/5px/70%). Di view ini hanya state idle (belum kamera
       aktif); saat kamera aktif seluruh gaya diambil dari tokens agar tidak
       ada duplikat/border biru. */
    .scanner-viewport-container {
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        background: #fafbfd;
        padding: 16px;
        min-height: 230px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        transition: all 0.2s ease;
    }

    /* ==========================================================================
       PERBAIKAN MOBILE: Toolbar, Ringkasan Status, dan Kotak Scanner
       ========================================================================== */

    /* --- 1. Ringkasan status: grid kecil-kecil, 3 kolom di mobile --- */
    .status-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 6px;
    }

    /* Scanner QR tetap full width, tidak ikut terpotong grid */
    .status-summary-grid .class-attendance-scan {
        grid-column: 1 / -1;
        width: 100% !important;
        padding: 0.6rem 1rem !important;
        font-size: 0.82rem !important;
    }

    /* Scanner QR tetap full width, tidak ikut terpotong grid */
    .status-summary-grid .class-attendance-scan {
        grid-column: 1 / -1;
        width: 100% !important;
        padding: 0.6rem 1rem !important;
        font-size: 0.82rem !important;
    }

    /* Scanner QR tetap full width, tidak ikut terpotong grid */
    .status-summary-grid .class-attendance-scan {
        grid-column: 1 / -1;
        width: 100% !important;
        padding: 0.6rem 1rem !important;
        font-size: 0.82rem !important;
    }

    .status-summary-grid .status-badge-pill {
        width: 100%;
        min-width: 0;
        padding: 0.35rem 0.45rem;
        gap: 0.25rem;
        font-size: 0.72rem;
        line-height: 1.2;
        border-radius: 8px !important;
        justify-content: center;
        display: flex;
        align-items: center;
    }

    .status-summary-grid .status-badge-pill span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media (min-width: 576px) {
        .status-summary-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 8px;
        }

        .status-summary-grid .status-badge-pill {
            padding: 0.4rem 0.5rem;
            font-size: 0.75rem;
        }
    }

    /* --- 2. Toolbar Kembali / Tanggal / Buka Scanner: satu baris, no wrap ---- */
    @media (max-width: 575.98px) {
        .class-attendance-toolbar {
            gap: 6px !important;
        }

        .class-attendance-toolbar .class-attendance-back,
        .class-attendance-toolbar .class-attendance-scan {
            min-height: 36px;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
            gap: 2px;
        }

        /* Input tanggal boleh menyusut, tapi tidak pernah melebihi baris */
        .class-attendance-toolbar .class-attendance-date {
            min-width: 0;
        }

        .class-attendance-toolbar .class-attendance-date .form-control {
            min-height: 36px;
            padding-left: 0.4rem;
            padding-right: 0.4rem;
        }

        /* Di layar sangat sempit, label tombol Scanner dipersingkat agar
           ketiga kontrol tetap muat dalam satu baris tanpa saling tumpang tindih. */
        @media (max-width: 389.98px) {
            .class-attendance-toolbar .class-attendance-back span {
                display: none;
            }
        }
    }

    /* --- 3. Kotak Scanner: persegi rapi, bukan melonjong memanjang ---------- */
    @media (max-width: 991.98px) {
        .scanner-kiosk-card {
            padding: 14px 12px;
            border-radius: 14px;
        }

        .kiosk-switcher {
            margin-bottom: 0.75rem;
            width: 100%;
            justify-content: center;
        }

        .kiosk-switch-btn {
            flex: 1 1 0;
            justify-content: center;
            padding: 0.42rem 0.6rem;
            font-size: 0.78rem;
        }

        /* Kotak utama (idle) tetap rapi di HP; saat kamera aktif gaya diambil
           dari presensi-tokens.css (#scannerColumn, SAMA PERSIS dengan kiosk). */
        .scanner-viewport-container {
            width: 100%;
            max-width: 380px;
            margin-left: auto;
            margin-right: auto;
            min-height: 0;
            padding: 12px;
            border-radius: 14px;
        }

        #cameraPlaceholder {
            padding-top: 0.75rem !important;
            padding-bottom: 0.75rem !important;
        }

        #cameraPlaceholder i {
            font-size: 2.25rem !important;
        }
    }

    /* ==========================================================================
       RAPIRAN TABEL PRESENSI KELAS
       Semua aturan di bawah HANYA berlaku di halaman ini (blok styles milik
       view ini), jadi halaman lain tidak ikut berubah.

       Warna status TIDAK dibuat baru - semuanya warna teks yang sudah dipakai
       di public/css/presensi-tokens.css (baris .presensi-badge.*), ditambah
       #1d4ed8 yang sudah dipakai di sistem sebagai biru tua. Dipilih versi
       GELAP supaya kontrasnya tetap terbaca di proyektor / layar terang.
       ========================================================================== */

    /* --- 1. KOLOM NAMA SISWA --------------------------------------------- */
    /* Nama tidak bold, satu baris, uppercase. Label kelas ("7A") dihapus dari
       markup (tidak lagi dirender di bawah nama). */
    .presensi-student-cell {
        gap: 0;
    }

    .presensi-student-cell .student-name {
        font-weight: 400 !important;
        text-transform: uppercase;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* --- 2. KOLOM STATUS: TEKS MURNI, TANPA CONTAINER -------------------- */
    /* Hanya teks berwarna: tanpa background, border, padding kotak, shadow. */
    .presensi-badge {
        background: none !important;
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        border-radius: 0 !important;
        min-width: 0 !important;
        font-weight: 600 !important;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .presensi-badge.hadir     { color: #065f46 !important; } /* hijau tua  */
    .presensi-badge.terlambat { color: #92400e !important; } /* oranye    */
    .presensi-badge.sakit     { color: #1d4ed8 !important; } /* biru tua  */
    .presensi-badge.izin      { color: #5b21b6 !important; } /* ungu      */
    .presensi-badge.alfa      { color: #991b1b !important; } /* merah     */
    .presensi-badge.belum     { color: #475569 !important; } /* abu-abu   */
    .presensi-badge.libur     { color: #475569 !important; } /* abu-abu   */

    /* --- 3. UPPERCASE UNTUK SEMUA DATA TABEL ------------------------------ */
    /* Hanya <tbody>: header kolom (thead) dibiarkan persis seperti sekarang. */
    .table-enterprise tbody td {
        text-transform: uppercase;
    }

    /* Isi dropdown Aksi juga uppercase (label, bukan nilai tersimpan). */
    .select-quick-status,
    .select-quick-status option {
        text-transform: uppercase;
    }

    /* --- 4. TABEL MENGISI SAMPAI BAWAH, SCROLL HANYA DI DALAM TABEL ------- */
    /* Rantai flex: kolom -> card -> .table-responsive. .table-responsive
       memakai flex:1 + min-height:0 supaya jadi tempat scroll, sedangkan kartu
       ringkasan & tombol di atasnya tetap diam (tidak ikut scroll).
       Tinggi kolom memakai dvh (ikut address bar browser) dikurangi offset
       rem untuk header + ringkasan + padding halaman. */
    #tableColumn {
        display: flex;
        flex-direction: column;
        height: calc(100dvh - 12rem);
        min-height: 20rem;
    }

    #tableColumn > .card {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    /* max-height bawaan layout (65vh) dilepas supaya tinggi mengikuti flex,
       sehingga tabel benar-benar mentok ke bawah tanpa ruang kosong. */
    #tableColumn .table-responsive {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none !important;
    }

    /* Di layar kecil header layout lebih tinggi (sticky + safe-area) dan kartu
       ringkasan jadi 3 kolom, jadi offset-nya diperbesar. */
    @media (max-width: 767.98px) {
        #tableColumn {
            height: calc(100dvh - 22rem);
        }
    }
</style>
@endpush

@section('content')
<div class="pt-1 space-y-3">

    <!-- RINGKASAN STATUS + SCANNER: satu container -->
    <div class="status-summary-grid mb-3">
        <button type="button" id="btnToggleScanner" class="btn btn-primary fw-semibold py-1.5 rounded-3 d-inline-flex align-items-center justify-content-center gap-1 shadow-2xs w-100 class-attendance-scan" style="font-size: 0.82rem;" onclick="toggleInlineScanner()">
            <i class='bx bx-camera fs-5' id="toggleScannerIcon"></i>
            <span id="toggleScannerText" class="text-nowrap">Buka Scanner QR</span>
        </button>
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countHadir ?? 0 }}</strong> Hadir</span>
        </div>
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countTerlambat ?? 0 }}</strong> Terlambat</span>
        </div>
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countSakit ?? 0 }}</strong> Sakit</span>
        </div>
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countIzin ?? 0 }}</strong> Izin</span>
        </div>
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countAlfa ?? 0 }}</strong> Alfa</span>
        </div>
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countBelumAbsen ?? 0 }}</strong> Belum</span>
        </div>
    </div>

    <!-- MAIN CONTENT LAYOUT -->
    <div class="row g-3 align-items-start">
        <!-- Kolom Scanner Kiri -->
        <div class="col-12 col-lg-5 col-xl-4" id="scannerColumn" style="display: none;">
            <div class="scanner-kiosk-card mb-3 mb-lg-0">
                
                <!-- Switcher Button (Kamera / Alat Scanner) -->
                <div class="kiosk-switcher">
                    <button type="button" id="btnTabCamera" class="kiosk-switch-btn active" onclick="switchMode('camera')">
                        <i class='bx bx-camera fs-5'></i> Kamera
                    </button>
                    <button type="button" id="btnTabHardware" class="kiosk-switch-btn" onclick="switchMode('hardware')">
                        <i class='bx bx-barcode-reader fs-5'></i> Alat Scanner
                    </button>
                </div>

                <!-- Scanner Box Area -->
                <div id="scannerBox" class="scanner-viewport-container mb-3 camera-active">
                    
                    <!-- Mode Kamera -->
                    <div id="cameraView" class="w-100 h-100 position-relative">
                        <div id="reader"></div>
                        <div class="panel-scan-frame" aria-hidden="true">
                            <div class="panel-scan-box">
                                <span class="panel-corner panel-corner--tl"></span>
                                <span class="panel-corner panel-corner--tr"></span>
                                <span class="panel-corner panel-corner--bl"></span>
                                <span class="panel-corner panel-corner--br"></span>
                            </div>
                        </div>
                        <div id="cameraPlaceholder" class="py-4 position-absolute top-50 start-50 translate-middle w-100" style="background: #fafbfd; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 5;">
                            <i class='bx bx-qr-scan text-secondary' style="font-size: 3rem;"></i>
                            <p class="text-secondary small mt-2 mb-0 fw-semibold">Tempelkan kartu QR ke kamera</p>
                        </div>
                    </div>

                    <!-- Mode Alat Scanner -->
                    <div id="hardwareView" class="d-none w-100 py-3">
                        <i class='bx bx-scan text-primary' style="font-size: 3rem;"></i>
                        <p class="text-dark fw-semibold small mt-2 mb-3">Arahkan fokus tetap di sini, lalu scan kartu dengan alat</p>
                        <div class="px-3">
                            <input type="text" id="hardwareInput" class="form-control form-control-sm text-center fw-semibold font-monospace py-2" placeholder="Siap menerima scan..." autofocus autocomplete="off">
                        </div>
                    </div>

                    <!-- Overlay Hasil Scan Terpadu (SAMA PERSIS DENGAN KIOSK) -->
                    <div id="scanResultOverlay" class="scan-result-overlay d-none">
                        <div class="scan-result-content">
                            <svg class="scan-result-svg" viewBox="0 0 100 100" width="110" height="110">
                                <circle class="scan-result-circle" cx="50" cy="50" r="45" fill="none" stroke-width="6"/>
                                <!-- 1. Centang Putih -->
                                <path class="scan-result-icon scan-result-check" d="M30 52 L45 67 L72 35" fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                                <!-- 2. Silang X Putih -->
                                <g class="scan-result-icon scan-result-cross">
                                    <path class="scan-result-cross-1" d="M35 35 L65 65" fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round"/>
                                    <path class="scan-result-cross-2" d="M65 35 L35 65" fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round"/>
                                </g>
                                <!-- 3. Tanda Seru (!) Putih -->
                                <g class="scan-result-icon scan-result-exclamation">
                                    <path class="scan-result-exclamation-line" d="M50 28 L50 56" fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round"/>
                                    <circle class="scan-result-exclamation-dot" cx="50" cy="71" r="4.5" fill="#ffffff"/>
                                </g>
                            </svg>
                            <div class="scan-result-text">
                                <div class="scan-result-name" id="scanResultTitle">Nama Siswa</div>
                                <div class="scan-result-status" id="scanResultSubtitle">Status Presensi</div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Jam Operasional di Bawah Kotak -->
                <div class="text-secondary" style="font-size: 0.78rem;">
                    Jam masuk <span class="fw-semibold text-dark">{{ $formattedCheckIn ?? $jamMasuk ?? '07:00' }}</span> · Jam pulang <span class="fw-semibold text-dark">{{ $formattedCheckOut ?? $jamPulang ?? '14:00' }}</span>
                </div>

            </div>
        </div>

        <!-- Kolom Tabel Siswa Kanan -->
        <div class="col-12" id="tableColumn">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-enterprise table-zebra-custom">
                        <thead class="bg-light">
                            <tr class="text-dark small fw-bold text-uppercase" style="letter-spacing: 0.03em; color: #000000 !important;">
                                <th class="text-center py-3" style="width: 8%; white-space: nowrap;">NO</th>
                                <th class="py-3" style="width: 30%; white-space: nowrap;">NAMA SISWA</th>
                                <th class="text-center py-3" style="width: 15%; white-space: nowrap;">JAM MASUK</th>
                                <th class="text-center py-3" style="width: 20%; white-space: nowrap;">STATUS</th>
                                @if(is_admin())
                                <th class="text-center py-3" style="width: 27%; white-space: nowrap;">AKSI</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($studentsList ?? [] as $index => $student)
                            <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                                <td class="text-center text-secondary fw-semibold">{{ $index + 1 }}</td>
                                <td>
                                    <div class="presensi-student-cell">
                                        <div class="student-name">{{ $student->name ?? '-' }}</div>
                                    </div>
                                </td>
                                <td class="text-center text-secondary font-monospace">
                                    {{ $student->jam_masuk ?? '—' }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $st = $student->current_status ?? 'Belum';
                                        $stClass = match(strtolower($st)) {
                                            'hadir' => 'hadir',
                                            'terlambat' => 'terlambat',
                                            'sakit' => 'sakit',
                                            'izin' => 'izin',
                                            'alfa', 'alpha' => 'alfa',
                                            'libur' => 'libur',
                                            default => 'belum',
                                        };
                                    @endphp
                                    <span class="presensi-badge {{ $stClass }}">
                                        {{ $st }}
                                    </span>
                                </td>
                                @if(is_admin())
                                <td class="text-center">
                                    <form action="{{ panel_route('absensi.override') }}" method="POST" class="d-inline-flex align-items-center justify-content-center gap-1 m-0">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="date" value="{{ $tanggal ?? date('Y-m-d') }}">

                                        <!-- Dropdown Status Cepat -->
                                        <select name="status" class="select-quick-status">
                                            <option value="Hadir" {{ $st === 'Hadir' ? 'selected' : '' }}>Hadir</option>
                                            <option value="Terlambat" {{ $st === 'Terlambat' ? 'selected' : '' }}>Terlambat</option>
                                            <option value="Sakit" {{ $st === 'Sakit' ? 'selected' : '' }}>Sakit</option>
                                            <option value="Izin" {{ $st === 'Izin' ? 'selected' : '' }}>Izin</option>
                                            <option value="Alfa" {{ $st === 'Alfa' ? 'selected' : '' }}>Alfa</option>
                                        </select>

                                        <!-- Tombol Simpan Cepat (Checklist) -->
                                        <button type="submit" class="btn-save-quick" title="Simpan Status">
                                            <i class='bx bx-check fs-5'></i>
                                        </button>
                                    </form>

                                    <!-- MODAL DETAIL OVERRIDE UNTUK SISWA INI -->
                                    <div class="modal fade text-start" id="modalOverrideSiswa{{ $student->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content presensi-modal-content">
                                                <div class="presensi-modal-header d-flex justify-content-between align-items-center">
                                                    <h5 class="modal-title">Ubah Presensi Siswa</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="presensi-modal-student-strip">
                                                    <div class="student-name">{{ $student->name }}</div>
                                                    <div class="student-class">{{ $selectedClass->name ?? ($student->schoolClass->name ?? '-') }} &bull; NIS: {{ $student->nis }}</div>
                                                </div>
                                                <form action="{{ panel_route('absensi.override') }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                    <input type="hidden" name="date" value="{{ $tanggal ?? date('Y-m-d') }}">

                                                    <div class="modal-body p-4 text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Status Kehadiran <span class="text-danger">*</span></label>
                                                            <select name="status" class="form-select rounded-3" required>
                                                                <option value="Hadir" {{ $st === 'Hadir' ? 'selected' : '' }}>Hadir (Tepat Waktu)</option>
                                                                <option value="Terlambat" {{ $st === 'Terlambat' ? 'selected' : '' }}>Hadir (Terlambat)</option>
                                                                <option value="Sakit" {{ $st === 'Sakit' ? 'selected' : '' }}>Sakit (S)</option>
                                                                <option value="Izin" {{ $st === 'Izin' ? 'selected' : '' }}>Izin (I)</option>
                                                                <option value="Alfa" {{ $st === 'Alfa' ? 'selected' : '' }}>Alfa (A)</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Jam Masuk</label>
                                                            <input type="time" name="check_in" class="form-control rounded-3" value="{{ ($student->raw_check_in ?? false) ? substr($student->raw_check_in, 0, 5) : '' }}">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Catatan / Keterangan</label>
                                                            <textarea name="notes" rows="2" class="form-control rounded-3" placeholder="Contoh: Sakit demam ada surat dokter">{{ $student->notes ?? '' }}</textarea>
                                                        </div>

                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold text-secondary">Upload Bukti Surat (Sakit / Izin)</label>
                                                            <input type="file" name="proof_document" class="form-control rounded-3 form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light px-4 py-3 border-top">
                                                        <button type="button" class="btn btn-light border px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-presensi-primary px-4 fw-semibold rounded-3">Simpan Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ is_admin() ? 6 : 5 }}" class="text-center py-4 text-secondary">
                                    <i class='bx bx-info-circle fs-2 d-block mb-2'></i>
                                    BELUM ADA DATA SISWA DI KELAS INI.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

<audio id="beepSound" src="{{ asset('audio/beep.mp3') }}" preload="auto"></audio>

@push('scripts')
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script src="{{ asset('js/scanner.js') }}?v={{ file_exists(public_path('js/scanner.js')) ? filemtime(public_path('js/scanner.js')) : config('app.version', '1') }}" data-process-route="{{ panel_route('scanner.process') }}"></script>
<script src="{{ asset('js/camera-select.js') }}?v={{ file_exists(public_path('js/camera-select.js')) ? filemtime(public_path('js/camera-select.js')) : config('app.version', '1') }}"></script>
<script>
    let isScannerOpen = false;
    let html5QrKiosk = null;
    let isCamRunning = false;
    let isProcessing = false;
    let resetTimer = null;
    let activeMode = 'camera';

    // Suara Beep dari file audio/beep.mp3
    function playClassBeep(success = true) {
        const beep = document.getElementById('beepSound');
        if (beep) {
            beep.currentTime = 0;
            beep.play().catch(() => playBrowserBeep(success));
        } else {
            playBrowserBeep(success);
        }
    }

    // Beep standar browser via WebAudio — fallback bila file audio tidak tersedia
    function playBrowserBeep(success = true) {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.type = 'sine';
            oscillator.frequency.value = success ? 880 : 440;
            gain.gain.setValueAtTime(0.0001, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + (success ? 0.25 : 0.5));
            oscillator.start(ctx.currentTime);
            oscillator.stop(ctx.currentTime + (success ? 0.25 : 0.5));
            oscillator.onended = () => ctx.close();
        } catch (e) {
            // abaikan bila browser memblokir AudioContext
        }
    }

    function toggleInlineScanner() {
        isScannerOpen = !isScannerOpen;
        const scannerCol = document.getElementById('scannerColumn');
        const tableCol = document.getElementById('tableColumn');
        const btnToggle = document.getElementById('btnToggleScanner');
        const iconToggle = document.getElementById('toggleScannerIcon');
        const textToggle = document.getElementById('toggleScannerText');

        if (isScannerOpen) {
            sessionStorage.setItem('class_scanner_open', '1');

            if (scannerCol) scannerCol.style.display = 'block';
            if (tableCol) tableCol.className = 'col-12 col-lg-7 col-xl-8';

            if (btnToggle) {
                btnToggle.className = 'btn btn-danger fw-semibold btn-sm px-2 px-sm-3.5 py-1.5 rounded-3 d-inline-flex align-items-center gap-1 shadow-2xs class-attendance-scan';
            }
            if (iconToggle) {
                iconToggle.className = 'bx bx-camera-off fs-5';
            }
            if (textToggle) {
                textToggle.innerText = 'Tutup Scanner';
            }

            switchMode(activeMode);
        } else {
            sessionStorage.removeItem('class_scanner_open');

            if (scannerCol) scannerCol.style.display = 'none';
            if (tableCol) tableCol.className = 'col-12';

            if (btnToggle) {
                btnToggle.className = 'btn btn-primary fw-semibold btn-sm px-2 px-sm-3.5 py-1.5 rounded-3 d-inline-flex align-items-center gap-1 shadow-2xs class-attendance-scan';
            }
            if (iconToggle) {
                iconToggle.className = 'bx bx-camera fs-5';
            }
            if (textToggle) {
                textToggle.innerText = 'Buka Scanner QR';
            }

            stopCamera();
        }
    }

    function switchMode(mode) {
        activeMode = mode;
        if (isProcessing) return;

        const btnCam = document.getElementById('btnTabCamera');
        const btnHard = document.getElementById('btnTabHardware');
        const viewCam = document.getElementById('cameraView');
        const viewHard = document.getElementById('hardwareView');
        const boxArea = document.getElementById('scannerBox');

        if (mode === 'camera') {
            if (btnCam) btnCam.classList.add('active');
            if (btnHard) btnHard.classList.remove('active');
            if (viewCam) viewCam.classList.remove('d-none');
            if (viewHard) viewHard.classList.add('d-none');
            if (boxArea) boxArea.classList.add('camera-active');
            startCamera();
        } else {
            if (btnHard) btnHard.classList.add('active');
            if (btnCam) btnCam.classList.remove('active');
            if (viewHard) viewHard.classList.remove('d-none');
            if (viewCam) viewCam.classList.add('d-none');
            if (boxArea) boxArea.classList.remove('camera-active');
            stopCamera();

            const inputEl = document.getElementById('hardwareInput');
            if (inputEl) inputEl.focus();
        }
    }

    // Ekspos ke window agar handler onclick inline (tombol Buka/Tutup Scanner & tab
    // Kamera/Alat Scanner) selalu bisa memanggilnya.
    window.toggleInlineScanner = toggleInlineScanner;
    window.switchMode = switchMode;

    let lastClassScannedToken = '';
    let lastClassScannedTimestamp = 0;

    function processCode(token) {
        const cleanToken = (token || '').trim();
        if (!cleanToken || isProcessing) return;

        const now = Date.now();
        if (cleanToken === lastClassScannedToken && (now - lastClassScannedTimestamp < 2500)) {
            return;
        }
        lastClassScannedToken = cleanToken;
        lastClassScannedTimestamp = now;

        isProcessing = true;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        fetch("{{ panel_route('scanner.process') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json"
            },
            body: JSON.stringify({ qr_token: cleanToken })
        })
        .then(res => res.json().then(data => ({ status: res.status, body: data })))
        .then(({ status, body }) => {
            playClassBeep(status === 200 && body.success);
            if (typeof handleScanResult === 'function') {
                handleScanResult(status, body);
            }
        })
        .catch(err => {
            console.error('[class-scanner] Fetch error:', err);
            playClassBeep(false);
            if (typeof handleScanResult === 'function') {
                handleScanResult(500, {
                    success: false,
                    message: 'Terjadi kendala koneksi ke server, coba lagi'
                });
            }
        });
    }

    // Listener saat overlay hasil scan selesai (1.5 detik): reset flag & fokus input (kamera standby tanpa reload)
    window.addEventListener('scan-result-finished', function() {
        isProcessing = false;
        if (activeMode === 'hardware') {
            const inputEl = document.getElementById('hardwareInput');
            if (inputEl) inputEl.focus();
        }
    });

    // Catatan: reset overlay 1.5 detik (resetOverlayState) TIDAK dideklarasikan ulang di sini.
    // Satu sumber kebenarannya ada di public/js/scanner.js (diekspor ke window), sehingga
    // tidak ada nama global bentrok dengan script inline halaman ini.

    function startCamera() {
        if (isCamRunning) return;
        if (typeof Html5Qrcode === 'undefined') {
            console.warn('Html5Qrcode library not loaded');
            return;
        }
        if (typeof CameraSelect === 'undefined') {
            console.error('CameraSelect script not loaded');
            return;
        }

        const placeholder = document.getElementById('cameraPlaceholder');
        if (placeholder) placeholder.style.display = 'none';

        // Semua start/stop kamera lewat antrean serial agar tidak pernah
        // paralel (mencegah error "Cannot transition to a new state").
        CameraSelect.run(async () => {
            if (isCamRunning) return;

            // Pemilihan kamera SAMA PERSIS dengan mode gerbang
            // (public/js/scanner.js): HP -> kamera belakang, laptop -> depan.
            // qrbox proporsional 70% SAMA PERSIS dengan kiosk (GERBANG_SCAN_RATIO),
            // hanya tampilan jendela terang; logika decode/request tidak berubah.
            const result = await CameraSelect.startSmartCamera({
                containerId: 'reader',
                getInstance: () => html5QrKiosk,
                createInstance: () => { html5QrKiosk = new Html5Qrcode("reader"); },
                releaseInstance: async () => {
                    await CameraSelect.safeStop(html5QrKiosk, 'reader');
                    html5QrKiosk = null;
                    const el = document.getElementById('reader');
                    if (el) el.innerHTML = '';
                },
                config: { fps: 10, qrbox: (w, h) => { const s = Math.floor(Math.min(w, h) * 0.7); return { width: s, height: s }; } },
                onSuccess: (decodedText) => {
                    processCode(decodedText);
                },
                onError: () => {}
            });

            if (result && result.success) {
                isCamRunning = true;
                // REVISI 1 (kiblat: Scanner QR = tidak mirror): netralkan sisa
                // mirror panel. Hanya tampilan; pilih kamera/decode tidak berubah.
                try {
                    const v = document.querySelector('#scannerColumn #reader video');
                    if (v) { v.style.scale = ''; v.classList.remove('mirror-front'); }
                } catch (e) {}
            } else {
                const msg = CameraSelect.errorMessageFor(result && result.error);
                console.error(msg, result && result.error);
                if (placeholder) placeholder.style.display = 'flex';
                if (typeof showCameraError === 'function') showCameraError(msg);
            }
        });
    }

    function stopCamera() {
        CameraSelect.run(async () => {
            if (!(isCamRunning && html5QrKiosk)) return;
            try {
                await CameraSelect.safeStop(html5QrKiosk, 'reader');
            } catch (err) {
                console.error(err);
            }
            isCamRunning = false;
            const placeholder = document.getElementById('cameraPlaceholder');
            if (placeholder) placeholder.style.display = 'flex';
        });
    }

    // Matikan kamera (safeStop) saat halaman ditutup/di-refresh
    window.addEventListener('beforeunload', function () {
        if (typeof CameraSelect !== 'undefined' && html5QrKiosk) {
            CameraSelect.run(async () => { await CameraSelect.safeStop(html5QrKiosk, 'reader'); });
        }
    });

    // Listener Hardware Barcode Scanner & Auto Resume
    document.addEventListener('DOMContentLoaded', function() {
        const hardInput = document.getElementById('hardwareInput');
        if (hardInput) {
            hardInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    processCode(this.value);
                    this.value = '';
                }
            });
        }

        window.addEventListener('click', function(e) {
            if (isScannerOpen && activeMode === 'hardware' && !isProcessing) {
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON') {
                    if (hardInput) hardInput.focus();
                }
            }
        });

        // Buka kembali secara otomatis jika scanner aktif sebelum reload halaman
        if (sessionStorage.getItem('class_scanner_open') === '1') {
            toggleInlineScanner();
        }
    });
</script>
@endpush
