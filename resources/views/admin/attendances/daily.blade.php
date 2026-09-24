@extends('layouts.app')

@section('title', 'Presensi Hari Ini')
@section('page_title', 'Presensi Hari Ini')
@section('page_subtitle', \Carbon\Carbon::parse($tanggal ?? now())->translatedFormat('l, d F Y'))

@section('page_header_right')
<div class="header-action-btns d-none d-md-flex">
    <!-- Tombol Mode Gerbang (Direct ke Kiosk / Scanner Gerbang) -->
    <a href="{{ Route::has('admin.scanner.kiosk') ? route('admin.scanner.kiosk') : (Route::has('admin.scanner') ? route('admin.scanner') : url('/admin/scanner/kiosk')) }}" 
       class="btn btn-action-header btn-outline-primary fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-2xs" 
       title="Mode Gerbang">
        <i class='bx bx-scan'></i>
        <span>Mode Gerbang</span>
    </a>

    <!-- Tombol Toggle Scanner (Desktop) -->
    <button type="button" id="btnToggleScannerDesktop" 
            class="btn btn-action-header btn-primary fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-2xs" 
            onclick="toggleInlineScanner()"
            title="Buka Scanner QR">
        <i class='bx bx-camera' id="toggleScannerIconDesktop"></i>
        <span id="toggleScannerTextDesktop">Buka Scanner QR</span>
    </button>
</div>
@endsection

@push('styles')
<style>
    .page-subtitle-date {
        font-size: 0.9rem;
        font-weight: 500;
        color: #475569;
    }

    /* Header Action Buttons di Desktop (Kanan Atas Header) */
    .header-action-btns {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .btn-action-header {
        font-size: 0.82rem;
        padding: 0.38rem 0.85rem;
        border-radius: 8px;
        line-height: 1.25;
        white-space: nowrap;
    }
    .btn-action-header i {
        font-size: 1.15rem;
    }
    @media (max-width: 767.98px) {
        .header-action-btns {
            display: none !important;
        }
    }

    /* Action Portal Grid Khusus Mobile (Muncul di Bawah Judul) */
    .action-portal-grid {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 0.65rem;
        width: 100%;
        margin-top: 0.65rem;
        margin-bottom: 1.15rem;
    }
    @media (min-width: 768px) {
        .action-portal-grid {
            display: none !important;
        }
    }
    .btn-portal-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        width: 100%;
        padding: 0.42rem 0.65rem;
        border-radius: 8px;
        font-size: 0.80rem;
        font-weight: 600;
        text-decoration: none !important;
        transition: all 0.15s ease-in-out;
        cursor: pointer;
        line-height: 1.25;
        border: 1px solid transparent;
        text-align: center;
        white-space: nowrap;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    .btn-portal-action i {
        font-size: 1.2rem;
        line-height: 1;
        flex-shrink: 0;
    }
    @media (min-width: 640px) {
        .btn-portal-action i {
            font-size: 1.3rem;
        }
    }

    /* Mode Gerbang Button (Slate Neutral Crisp) */
    .btn-portal-gate {
        background-color: #ffffff;
        color: #1e293b !important;
        border-color: #cbd5e1;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .btn-portal-gate:hover {
        background-color: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a !important;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.06);
    }
    .btn-portal-gate:active {
        transform: translateY(0);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .btn-portal-gate i {
        color: #2563eb;
    }

    /* Buka Scanner Button (Royal Blue Primary) */
    .btn-portal-scanner {
        background-color: #3b62f6;
        color: #ffffff !important;
        border-color: #3b62f6;
        box-shadow: 0 1px 3px rgba(59, 98, 246, 0.25);
    }
    .btn-portal-scanner:hover {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(59, 98, 246, 0.35);
    }
    .btn-portal-scanner:active {
        transform: translateY(0);
        box-shadow: 0 1px 3px rgba(59, 98, 246, 0.25);
    }
    .btn-portal-scanner.scanner-active {
        background-color: #ef4444 !important;
        border-color: #ef4444 !important;
        color: #ffffff !important;
        box-shadow: 0 1px 3px rgba(239, 68, 68, 0.25);
    }
    .btn-portal-scanner.scanner-active:hover {
        background-color: #dc2626 !important;
        border-color: #dc2626 !important;
        box-shadow: 0 4px 8px rgba(239, 68, 68, 0.35);
    }

    /* Wadah Badge Responsif: Horizontal Scroll di Mobile, Flex Wrap di Desktop */
    .badges-scroll-container {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none !important; /* Firefox */
        -ms-overflow-style: none !important; /* IE/Edge */
        padding-bottom: 2px;
    }
    .badges-scroll-container::-webkit-scrollbar {
        display: none !important; /* Chrome/Safari */
        width: 0 !important;
        height: 0 !important;
    }
    .badges-scroll-wrapper {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: nowrap;
        width: max-content;
        min-width: 100%;
    }
    @media (max-width: 767.98px) {
        /* Hapus total badge statistik pada tampilan mobile */
        .badges-scroll-container {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }
    }
    @media (min-width: 768px) {
        .badges-scroll-container {
            display: block !important;
            overflow-x: visible;
            margin-bottom: 0.85rem;
        }
        .badges-scroll-wrapper {
            flex-wrap: wrap;
            width: auto;
        }
    }
    
    /* Badge Ringkasan Minimalis: Ikon di Kanan, Background Putih */
    .status-badge-pill {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.45rem;
        padding: 0.32rem 0.65rem;
        border-radius: 8px !important;
        font-size: 0.78rem;
        font-weight: 600;
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        color: #1e293b;
        white-space: nowrap;
        flex-shrink: 0;
        line-height: 1.2;
    }
    .status-badge-pill i {
        font-size: 0.95rem;
    }
    @media (max-width: 639.98px) {
        .status-badge-pill {
            padding: 0.20rem 0.45rem !important;
            font-size: 0.72rem !important;
            border-radius: 6px !important;
            gap: 0.3rem !important;
            line-height: 1.15 !important;
        }
        .status-badge-pill i {
            font-size: 0.85rem !important;
        }
    }
    .icon-hadir { color: #15803d; }
    .icon-belum { color: #64748b; }
    .icon-total { color: #2563eb; }

    /* Tabel Enterprise Standar & Roomy */
    .table-custom-card {
        background: #ffffff;
        border: 0 !important;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    /* Sembunyikan Scrollbar Horizontal Secara Total (Clean Look) */
    .table-responsive,
    .table-custom-card,
    .badges-scroll-container,
    div[class*="table-responsive"] {
        scrollbar-width: none !important; /* Firefox */
        -ms-overflow-style: none !important; /* IE 10+ & Edge */
    }
    .table-responsive::-webkit-scrollbar,
    .table-custom-card::-webkit-scrollbar,
    .badges-scroll-container::-webkit-scrollbar,
    div[class*="table-responsive"]::-webkit-scrollbar {
        display: none !important; /* Chrome, Safari, WebKit, Edge */
        width: 0 !important;
        height: 0 !important;
    }
    .table-enterprise {
        width: 100%;
        table-layout: fixed;
        min-width: 680px;
    }
    .table-enterprise thead th {
        background-color: #f8fafc;
        color: #0f172a !important;
        font-weight: 700 !important;
        font-size: 0.80rem;
        padding: 0.65rem 0.5rem !important;
        border-bottom: 1.5px solid #edf2f7;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
        vertical-align: middle !important;
        text-align: center !important;
    }
    .table-enterprise thead th.text-center,
    .table-enterprise tbody td.text-center {
        text-align: center !important;
        vertical-align: middle !important;
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
        padding: 0.55rem 0.5rem !important;
        font-size: 0.875rem;
        color: #0f172a !important;
        font-weight: 400 !important;
        vertical-align: middle !important;
        text-align: center !important;
        border-bottom: 1px solid #f1f5f9;
        line-height: 1.35;
    }

    @media (max-width: 639.98px) {
        .table-enterprise {
            min-width: 600px !important;
        }

        /* Kolom Kelas Proporsional */
        .table-enterprise thead th.col-th-kelas,
        .table-enterprise tbody td.col-td-kelas {
            width: 14% !important;
            padding-left: 0.45rem !important;
            padding-right: 0.35rem !important;
            text-align: center !important;
        }

        /* Kolom Angka (Total, Hadir, Sakit, Izin, Alpha) - Lebar Seimbang & Jarak Seragam */
        .table-enterprise thead th.col-th-stat,
        .table-enterprise tbody td.col-td-stat {
            width: 9.5% !important;
            padding-left: 0.3rem !important;
            padding-right: 0.3rem !important;
            text-align: center !important;
        }

        /* Kolom Kehadiran & Aksi di Mobile */
        .table-enterprise thead th.col-th-kehadiran,
        .table-enterprise tbody td.col-td-kehadiran {
            width: 21.5% !important;
            padding: 0.55rem 0.35rem !important;
            text-align: center !important;
        }
        .table-enterprise thead th.col-th-aksi,
        .table-enterprise tbody td.col-td-aksi {
            width: 17% !important;
            padding: 0.55rem 0.35rem !important;
            text-align: center !important;
        }
        .btn-buka-kelas {
            padding: 0.30rem 0.65rem !important;
            font-size: 0.76rem !important;
            border-radius: 7px !important;
        }
    }

    /* Tombol Buka Kelas (Tema Royal Blue Standar & Proporsional) */
    .btn-buka-kelas {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        background-color: #3b62f6;
        color: #ffffff !important;
        border: 1px solid #3b62f6;
        border-radius: 8px;
        padding: 0.32rem 0.75rem;
        font-size: 0.80rem;
        font-weight: 600;
        text-decoration: none !important;
        box-shadow: 0 1px 2px rgba(59, 98, 246, 0.2);
        transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        line-height: 1.25;
        white-space: nowrap;
    }
    .btn-buka-kelas:hover {
        background-color: #254fd9;
        border-color: #254fd9;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(59, 98, 246, 0.3);
    }
    .btn-buka-kelas:active {
        transform: translateY(0);
        box-shadow: 0 1px 2px rgba(59, 98, 246, 0.2);
    }

    /* Card Scanner Mode Gerbang Style di Halaman Presensi Harian */
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

    /* Viewport / Area Scanner */
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

    .scanner-viewport-container.camera-active {
        border: 2px solid #2563eb;
        background: #000000;
        padding: 0;
    }

    #reader {
        width: 100% !important;
        height: 100% !important;
        min-height: 220px;
        border: none !important;
    }

    #reader video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover;
        border-radius: 14px;
    }

    /* Overlay Notifikasi Langsung di Dalam Scanner Box */
    .overlay-status {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 50;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.2rem;
        text-align: center;
        backdrop-filter: blur(6px);
        animation: fadeInScale 0.15s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    /* Sukses: Gradient Emerald */
    .overlay-success {
        background: linear-gradient(135deg, rgba(13, 148, 136, 0.96) 0%, rgba(16, 185, 129, 0.96) 100%);
        color: #ffffff;
        box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.2);
        border-radius: 14px;
    }

    /* Gagal/Error: Danger Red */
    .overlay-error {
        background: #dc2626 !important;
        color: #ffffff !important;
        box-shadow: 0 0 25px rgba(220, 38, 38, 0.5);
        border-radius: 14px;
    }

    @keyframes fadeInScale {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
</style>
@endpush

@section('content')
<div class="pt-0.5 sm:pt-1 pb-4 space-y-2 sm:space-y-3">

    <!-- AMBIL DATA DARI CONTROLLER & HITUNG TOTAL OTOMATIS JIKA KOSONG -->
    @php
        $listKelas = $classSummaries ?? $classesSummary ?? $classes ?? $rekapKelas ?? $schoolClasses ?? [];
        
        $sumSudah = 0;
        $sumBelum = 0;
        $sumTotal = 0;

        foreach($listKelas as $c) {
            $tSiswa = is_array($c) ? ($c['total_siswa'] ?? 0) : ($c->total_students ?? $c->total_siswa ?? $c->students_count ?? 0);
            $sAbsen = is_array($c) ? ($c['sudah_absen'] ?? 0) : ($c->sudah_absen ?? $c->hadir_count ?? 0);
            $bAbsen = is_array($c) ? ($c['belum'] ?? 0) : ($c->belum_absen ?? max(0, $tSiswa - $sAbsen));

            $sumTotal += $tSiswa;
            $sumSudah += $sAbsen;
            $sumBelum += $bAbsen;
        }
    @endphp

    <!-- ========================================================================= -->
    <!-- 1. KONTEN KHUSUS MOBILE (< 768px): KARTU TOMBOL AKSI PRESENSI             -->
    <!-- ========================================================================= -->
    <div class="d-block d-md-none">
        <div class="card border border-light-subtle shadow-sm rounded-3 p-3 mb-3 bg-white">
            <div class="row g-2">
                <!-- 1. Mode Gerbang -->
                <div class="col-6">
                    <a href="{{ Route::has('admin.scanner.kiosk') ? route('admin.scanner.kiosk') : (Route::has('admin.scanner') ? route('admin.scanner') : url('/admin/scanner/kiosk')) }}" 
                       class="btn-portal-action btn-portal-gate shadow-2xs w-100" 
                       title="Mode Gerbang">
                        <i class='bx bx-scan'></i>
                        <span class="text-nowrap" style="font-size: 0.8rem;">Mode Gerbang</span>
                    </a>
                </div>

                <!-- 2. Buka Scanner QR -->
                <div class="col-6">
                    <button type="button" id="btnToggleScannerMobile" 
                            class="btn-portal-action btn-portal-scanner shadow-2xs w-100" 
                            onclick="toggleInlineScanner()"
                            title="Buka Scanner QR">
                        <i class='bx bx-camera' id="toggleScannerIconMobile"></i>
                        <span class="text-nowrap" style="font-size: 0.8rem;" id="toggleScannerTextMobile">Scanner QR</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. KONTEN KHUSUS DESKTOP (>= 768px): BADGE RINGKASAN STATUS HARIAN        -->
    <!-- ========================================================================= -->
    <div class="badges-scroll-container mb-3 no-scrollbar d-none d-md-block" style="scrollbar-width: none; -ms-overflow-style: none;">
        <div class="badges-scroll-wrapper">
            <div class="status-badge-pill shadow-2xs">
                <span><strong>{{ $totalSudahAbsen ?? $sumSudah }}</strong> sudah presensi</span>
                <i class='bx bx-check-circle icon-hadir'></i>
            </div>
            <div class="status-badge-pill shadow-2xs">
                <span><strong>{{ $totalBelumAbsen ?? $sumBelum }}</strong> belum presensi</span>
                <i class='bx bx-minus-circle icon-belum'></i>
            </div>
            <div class="status-badge-pill shadow-2xs">
                <span><strong>{{ $totalSiswaSeluruhnya ?? $sumTotal }}</strong> total siswa</span>
                <i class='bx bx-user icon-total'></i>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT LAYOUT -->
    <div class="row g-2 sm:g-3 align-items-start">
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
                        <div id="reader" style="width: 100%; min-height: 220px; border-radius: 12px; overflow: hidden;"></div>
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

                    <!-- Overlay Sukses (Emerald Gradient) -->
                    <div id="overlaySuccess" class="overlay-status overlay-success d-none">
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 mb-2 d-inline-flex">
                            <i class='bx bx-check fs-2 text-white'></i>
                        </div>
                        <h6 class="fw-bold text-white mb-0 px-2" id="successText" style="font-size: 1.05rem; letter-spacing: -0.01em;">Nama Siswa — Hadir jam 07:00</h6>
                    </div>

                    <!-- Overlay Gagal (Danger Red) -->
                    <div id="overlayError" class="overlay-status overlay-error d-none">
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 mb-2 d-inline-flex">
                            <i class='bx bx-error-circle fs-2 text-white'></i>
                        </div>
                        <h6 class="fw-bold text-white mb-1" style="font-size: 1.05rem;">Peringatan Presensi</h6>
                        <p class="text-white small mb-0 fw-medium px-2" id="errorText" style="font-size: 0.82rem;">Siswa sudah presensi hari ini.</p>
                    </div>

                </div>

                <!-- Jam Operasional di Bawah Kotak -->
                <div class="text-secondary" style="font-size: 0.78rem;">
                    Jam masuk <span class="fw-semibold text-dark">{{ $formattedCheckIn ?? $jam_masuk ?? $checkInTime ?? '07:00' }}</span> · Jam pulang <span class="fw-semibold text-dark">{{ $formattedCheckOut ?? $jam_pulang ?? $checkOutTime ?? '14:00' }}</span>
                </div>

            </div>
        </div>

        @if(request()->filled('status'))
        <!-- Kolom Tabel Siswa Terfilter -->
        <div class="col-12">
            <div class="alert alert-primary d-flex align-items-center justify-content-between py-2 px-3 mb-3 rounded-3 shadow-xs">
                <div class="d-flex align-items-center gap-2">
                    <i class='bx bx-filter-alt fs-5'></i>
                    <span class="small fw-semibold">Menampilkan siswa dengan status: <strong class="badge bg-primary fs-7">{{ ucfirst(request('status')) }}</strong> pada tanggal {{ \Carbon\Carbon::parse($tanggal ?? now())->translatedFormat('d F Y') }} ({{ count($processedStudents ?? []) }} Siswa)</span>
                </div>
                <a href="{{ route('admin.presensi.index', ['tanggal' => $tanggal ?? date('Y-m-d')]) }}" class="btn btn-sm btn-light border text-dark fw-semibold py-1 px-2.5 rounded-2" style="font-size: 0.78rem;">
                    <i class='bx bx-x'></i> Reset Filter
                </a>
            </div>
            
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive no-scrollbar" style="scrollbar-width: none; -ms-overflow-style: none;">
                    <table class="table table-hover table-enterprise table-zebra-custom align-middle mb-0 text-nowrap">
                        <thead class="bg-light text-nowrap">
                            <tr class="text-nowrap">
                                <th style="width: 5%;" class="text-nowrap">NO</th>
                                <th style="width: 35%;" class="text-nowrap">Nama Siswa</th>
                                <th class="text-center text-nowrap" style="width: 15%;">NIS / NISN</th>
                                <th class="text-center text-nowrap" style="width: 15%;">Kelas</th>
                                <th class="text-center text-nowrap" style="width: 15%;">Status</th>
                                <th class="text-center text-nowrap" style="width: 15%;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="text-nowrap">
                            @forelse($processedStudents as $idx => $st)
                            <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }} text-nowrap">
                                <td class="text-center text-secondary small text-nowrap">{{ $idx + 1 }}</td>
                                <td class="text-nowrap">
                                    <div class="fw-bold text-dark text-nowrap">{{ $st->name }}</div>
                                </td>
                                <td class="text-center text-secondary font-monospace small text-nowrap">
                                    {{ $st->nis ?? '-' }}
                                </td>
                                <td class="text-center text-nowrap">
                                    <span class="badge bg-light text-dark border text-nowrap">{{ $st->schoolClass->name ?? '-' }}</span>
                                </td>
                                <td class="text-center text-nowrap">
                                    @php
                                        $statusColor = match(strtolower($st->current_status ?? '')) {
                                            'hadir' => 'text-success',
                                            'terlambat' => 'text-warning',
                                            'sakit' => 'text-primary',
                                            'izin' => 'text-info',
                                            'alfa', 'alpha' => 'text-danger',
                                            default => 'text-secondary',
                                        };
                                    @endphp
                                    <span class="{{ $statusColor }} fw-semibold text-nowrap">{{ $st->current_status }}</span>
                                </td>
                                <td class="text-center text-secondary small text-nowrap">
                                    {{ $st->notes ?? '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr class="text-nowrap">
                                <td colspan="6" class="text-center py-3 text-secondary text-nowrap">
                                    Tidak ada data siswa berstatus {{ ucfirst(request('status')) }} pada tanggal ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <!-- Kolom Tabel Rekap Kelas Kanan (Kaya Data & Compact) -->
        <div class="col-12" id="tableColumn">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive no-scrollbar" style="scrollbar-width: none; -ms-overflow-style: none;">
                    <table class="table table-hover table-enterprise table-zebra-custom align-middle mb-0 text-nowrap">
                        <thead class="bg-light text-nowrap">
                            <tr class="text-nowrap">
                                <th class="text-center align-middle col-th-kelas text-nowrap" style="width: 16%;">Kelas</th>
                                <th class="text-center align-middle col-th-stat text-nowrap" style="width: 9%;">Total</th>
                                <th class="text-center align-middle col-th-stat text-nowrap" style="width: 9%;">Hadir</th>
                                <th class="text-center align-middle col-th-stat text-nowrap" style="width: 9%;">Sakit</th>
                                <th class="text-center align-middle col-th-stat text-nowrap" style="width: 9%;">Izin</th>
                                <th class="text-center align-middle col-th-stat text-nowrap" style="width: 9%;">Alpha</th>
                                <th class="text-center align-middle col-th-kehadiran text-nowrap" style="width: 22%;">Kehadiran</th>
                                <th class="text-center align-middle col-th-aksi text-nowrap" style="width: 17%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-nowrap">
                            @forelse($listKelas as $cls)
                            @php
                                $clsId = is_array($cls) ? ($cls['id'] ?? null) : ($cls->id ?? null);
                                $clsName = is_array($cls) ? ($cls['nama_kelas'] ?? $cls['name'] ?? '-') : ($cls->name ?? $cls->nama_kelas ?? '-');
                                $totSiswa = is_array($cls) ? ($cls['total_siswa'] ?? 0) : ($cls->total_students ?? $cls->total_siswa ?? $cls->students_count ?? 0);
                                $hadir = is_array($cls) ? ($cls['hadir'] ?? 0) : ($cls->hadir_count ?? $cls->hadir ?? 0);
                                $sakit = is_array($cls) ? ($cls['sakit'] ?? 0) : ($cls->sakit_count ?? $cls->sakit ?? 0);
                                $izin = is_array($cls) ? ($cls['izin'] ?? 0) : ($cls->izin_count ?? $cls->izin ?? 0);
                                $alpha = is_array($cls) ? ($cls['alpha'] ?? 0) : ($cls->alfa_count ?? $cls->alpha ?? $cls->alfa ?? 0);
                                $sudah = is_array($cls) ? ($cls['sudah_absen'] ?? 0) : ($cls->sudah_absen ?? ($hadir + $sakit + $izin + $alpha));
                                $persentase = is_array($cls) 
                                    ? ($cls['persentase'] ?? ($totSiswa > 0 ? round(($hadir / $totSiswa) * 100) : 0))
                                    : ($cls->percentage ?? ($totSiswa > 0 ? round(($hadir / $totSiswa) * 100) : 0));
                                if ($totSiswa > 0 && $hadir > 0) {
                                    $persentase = round(($hadir / $totSiswa) * 100);
                                }
                            @endphp
                            <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }} text-nowrap">
                                <td class="text-center align-middle col-td-kelas text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center w-100 text-nowrap">
                                        <span class="text-slate-900 text-nowrap" style="color: #0f172a !important; font-size: 0.875rem; font-weight: 400;">{{ $clsName }}</span>
                                    </div>
                                </td>
                                <td class="text-center align-middle col-td-stat text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center w-100 text-nowrap">
                                        <span class="text-slate-900 text-nowrap" style="color: #0f172a !important; font-size: 0.875rem; font-weight: 400;">{{ $totSiswa }}</span>
                                    </div>
                                </td>
                                <td class="text-center align-middle col-td-stat text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center w-100 text-nowrap">
                                        <span class="text-slate-900 text-nowrap" style="color: #0f172a !important; font-size: 0.875rem; font-weight: 400;">{{ $hadir }}</span>
                                    </div>
                                </td>
                                <td class="text-center align-middle col-td-stat text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center w-100 text-nowrap">
                                        <span class="text-slate-900 text-nowrap" style="color: #0f172a !important; font-size: 0.875rem; font-weight: 400;">{{ $sakit }}</span>
                                    </div>
                                </td>
                                <td class="text-center align-middle col-td-stat text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center w-100 text-nowrap">
                                        <span class="text-slate-900 text-nowrap" style="color: #0f172a !important; font-size: 0.875rem; font-weight: 400;">{{ $izin }}</span>
                                    </div>
                                </td>
                                <td class="text-center align-middle col-td-stat text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center w-100 text-nowrap">
                                        <span class="text-slate-900 text-nowrap" style="color: #0f172a !important; font-size: 0.875rem; font-weight: 400;">{{ $alpha }}</span>
                                    </div>
                                </td>
                                <td class="text-center align-middle col-td-kehadiran text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center w-100 text-nowrap">
                                        <span class="text-slate-900 text-nowrap" style="font-size: 0.875rem; color: #0f172a !important; font-weight: 400;">
                                            {{ $persentase ?? 0 }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center align-middle col-td-aksi text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center w-100 text-nowrap">
                                        <a href="{{ route('admin.absensi.show', ['schoolClass' => $clsId, 'tanggal' => $tanggal ?? date('Y-m-d')]) }}" class="btn-buka-kelas text-nowrap">
                                            <i class='bx bx-door-open fs-6'></i>
                                            <span>Buka Kelas</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="text-nowrap">
                                <td colspan="8" class="text-center py-3 text-secondary text-nowrap">Belum ada data kelas tersedia.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let isScannerOpen = false;
    let html5QrKiosk = null;
    let isCamRunning = false;
    let isProcessing = false;
    let resetTimer = null;
    let activeMode = 'camera';

    // Web Audio API Generator Suara Beep Browser
    let audioCtx = null;

    function playBrowserBeep(success = true) {
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();

            if (success) {
                // Suara Beep Sukses (Tit Tinggi Renyah)
                osc.type = 'sine';
                osc.frequency.setValueAtTime(2500, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.08);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.08);
            } else {
                // Suara Beep Error (Buzzer Rendah)
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(150, audioCtx.currentTime);
                osc.frequency.setValueAtTime(300, audioCtx.currentTime + 0.1);
                gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.3);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.3);
            }
        } catch (e) {
            console.warn('Audio error:', e);
        }
    }

    function toggleInlineScanner() {
        const scannerCol = document.getElementById('scannerColumn');
        const tableCol = document.getElementById('tableColumn');
        const btnDesktop = document.getElementById('btnToggleScannerDesktop');
        const iconDesktop = document.getElementById('toggleScannerIconDesktop');
        const textDesktop = document.getElementById('toggleScannerTextDesktop');
        const btnMobile = document.getElementById('btnToggleScannerMobile');
        const iconMobile = document.getElementById('toggleScannerIconMobile');
        const textMobile = document.getElementById('toggleScannerTextMobile');

        isScannerOpen = !isScannerOpen;

        if (isScannerOpen) {
            sessionStorage.setItem('daily_scanner_open', '1');

            if (scannerCol) scannerCol.style.display = 'block';
            if (tableCol) tableCol.className = 'col-12 col-lg-7 col-xl-8';

            if (btnDesktop) {
                btnDesktop.className = 'btn btn-action-header btn-danger fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-2xs';
                if (iconDesktop) iconDesktop.className = 'bx bx-camera-off';
                if (textDesktop) textDesktop.textContent = 'Tutup Scanner';
            }
            if (btnMobile) {
                btnMobile.className = 'btn-portal-action btn-portal-scanner scanner-active shadow-2xs';
                if (iconMobile) iconMobile.className = 'bx bx-camera-off';
                if (textMobile) textMobile.textContent = 'Tutup';
            }

            switchMode(activeMode);
        } else {
            sessionStorage.removeItem('daily_scanner_open');

            if (scannerCol) scannerCol.style.display = 'none';
            if (tableCol) tableCol.className = 'col-12';

            if (btnDesktop) {
                btnDesktop.className = 'btn btn-action-header btn-primary fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-2xs';
                if (iconDesktop) iconDesktop.className = 'bx bx-camera';
                if (textDesktop) textDesktop.textContent = 'Buka Scanner QR';
            }
            if (btnMobile) {
                btnMobile.className = 'btn-portal-action btn-portal-scanner shadow-2xs';
                if (iconMobile) iconMobile.className = 'bx bx-camera';
                if (textMobile) textMobile.textContent = 'Scanner QR';
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

    function processCode(token) {
        const cleanToken = (token || '').trim();
        if (!cleanToken || isProcessing) return;
        isProcessing = true;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        fetch("{{ route('admin.scanner.process') }}", {
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
            if (status === 200 && body.success) {
                playBrowserBeep(true);
                showOverlaySuccess(body);

                // Reload otomatis setelah notifikasi tampil agar tabel statistik terupdate
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            } else {
                playBrowserBeep(false);
                showOverlayError(body.message || 'QR Code tidak valid.');
            }
        })
        .catch(err => {
            console.error(err);
            playBrowserBeep(false);
            showOverlayError('Terjadi kendala koneksi ke server.');
        });
    }

    function showOverlaySuccess(data) {
        const overlaySucc = document.getElementById('overlaySuccess');
        const successText = document.getElementById('successText');
        if (!overlaySucc || !successText) return;

        const studentName = data.student ? data.student.name : 'Siswa';
        const statusLabel = data.type === 'check_out' ? 'Pulang' : (data.remark || 'Hadir');
        const now = new Date();
        const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

        successText.innerText = `${studentName} — ${statusLabel} jam ${timeStr}`;
        overlaySucc.classList.remove('d-none');
    }

    function showOverlayError(errorMsg) {
        const overlayErr = document.getElementById('overlayError');
        const errorText = document.getElementById('errorText');
        if (!overlayErr || !errorText) return;

        errorText.innerText = errorMsg;
        overlayErr.classList.remove('d-none');

        resetOverlayState(1500);
    }

    function resetOverlayState(delay = 1000) {
        if (resetTimer) clearTimeout(resetTimer);
        resetTimer = setTimeout(() => {
            const overlaySucc = document.getElementById('overlaySuccess');
            const overlayErr = document.getElementById('overlayError');
            if (overlaySucc) overlaySucc.classList.add('d-none');
            if (overlayErr) overlayErr.classList.add('d-none');

            isProcessing = false;

            if (activeMode === 'hardware') {
                const inputEl = document.getElementById('hardwareInput');
                if (inputEl) inputEl.focus();
            }
        }, delay);
    }

    function startCamera() {
        if (isCamRunning) return;
        if (typeof Html5Qrcode === 'undefined') {
            console.warn('Html5Qrcode library not loaded');
            return;
        }

        const placeholder = document.getElementById('cameraPlaceholder');
        if (placeholder) placeholder.style.display = 'none';

        html5QrKiosk = new Html5Qrcode("reader");

        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                html5QrKiosk.start(
                    devices[0].id,
                    { fps: 10, qrbox: { width: 180, height: 180 } },
                    (decodedText) => {
                        processCode(decodedText);
                    },
                    () => {}
                ).then(() => { isCamRunning = true; });
            }
        }).catch(err => {
            console.error(err);
            if (placeholder) placeholder.style.display = 'flex';
        });
    }

    function stopCamera() {
        if (isCamRunning && html5QrKiosk) {
            html5QrKiosk.stop().then(() => {
                isCamRunning = false;
                html5QrKiosk.clear();
                const placeholder = document.getElementById('cameraPlaceholder');
                if (placeholder) placeholder.style.display = 'flex';
            }).catch(err => {
                console.error(err);
                isCamRunning = false;
            });
        }
    }

    // Live Real-Time Filtering Tabel Kelas Harian
    function filterDailyClasses(query) {
        const q = (query || '').toLowerCase().trim();
        const rows = document.querySelectorAll('#tableColumn tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    }

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
        if (sessionStorage.getItem('daily_scanner_open') === '1') {
            toggleInlineScanner();
        }
    });
</script>
@endpush