@extends('layouts.app')

@section('title', {!! __('Presensi Hari Ini') !!})
@section('page_title', {!! __('Presensi Hari Ini') !!})
@section('page_subtitle')
    @php
        \Carbon\Carbon::setLocale(app()->getLocale());
        $tanggalStr = \Carbon\Carbon::parse($tanggal ?? now())->translatedFormat('l, d F Y');
    @endphp
    {{-- Subjudul HANYA tanggal hari ini, sama persis dengan versi mobile.
         Sumber tanggal tetap $tanggal ?? now() (tidak di-hardcode), format
         "l, d F Y" -> contoh: "Minggu, 11 Oktober 2026". Ukuran & warna font
         tetap dari .header-main-subtitle di layout bersama, jadi tidak perlu
         CSS tambahan dan tampilan HP tidak berubah. --}}
    <span>{{ $tanggalStr }}</span>
@endsection

@section('page_header_right')
<div class="header-action-btns d-none d-md-flex">
    <!-- Tombol Mode Gerbang (Direct ke Kiosk / Scanner Gerbang)
         Warna hijau diambil dari class yang sama dengan tombol versi mobile
         (.btn-portal-gate), sehingga desktop & mobile satu sumber warna. -->
    <a href="{{ panel_route('absensi.kiosk') }}" 
       class="btn btn-action-header btn-portal-gate fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-2xs" 
       title="{{ __('Mode Gerbang') }}">
        <i class='bx bx-scan'></i>
        <span>{{ __('Mode Gerbang') }}</span>
    </a>

    <!-- Tombol Toggle Scanner (Desktop)
         Warna biru juga diambil dari class versi mobile (.btn-portal-scanner). -->
    <button type="button" id="btnToggleScannerDesktop" 
            class="btn btn-action-header btn-portal-scanner fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-2xs" 
            onclick="toggleInlineScanner()"
            title="{{ __('Buka Scanner QR') }}">
        <i class='bx bx-camera' id="toggleScannerIconDesktop"></i>
        <span id="toggleScannerTextDesktop">{{ __('Buka Scanner QR') }}</span>
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
        border-radius: var(--clean-radius);
        line-height: 1.25;
        white-space: nowrap;
    }
    .btn-action-header i {
        font-size: 1.15rem;
        /* Samakan dengan Presensi Kelas: netralkan line-height bawaan ikon
           supaya ikon & teks selalu sejajar tengah. */
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        line-height: 1 !important;
        margin: 0 !important;
        padding: 0 !important;
        flex: 0 0 auto !important;
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
        border-radius: var(--clean-radius) !important;
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
        font-size: 0.8rem;
        line-height: 1;
        flex-shrink: 0;
        /* Samakan dengan Presensi Kelas: ikon selalu sejajar tengah. */
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Touch target portal buttons (mobile): tetap ringkas namun mudah di-tap */
    @media (max-width: 767.98px) {
        .btn-portal-action {
            min-height: 40px;
            padding: 0.5rem 0.65rem;
        }

        /* Ikon tombol HP = ICON TOMBOL DESKTOP (class & library sama:
           "bx-camera" untuk Scanner QR dan "bx-scan" untuk Mode Gerbang).
           Yang disamakan hanya UKURAN & JARAK, dibuat proporsional terhadap
           tinggi tombol: versi desktop memakai ikon 1.15rem dengan font tombol
           .82rem (rasio ~1.4); tombol HP berfont .8rem -> ikon ~1.1rem.
           Jarak ikon-teks ikut disamakan dengan gap-1 (.25rem) milik tombol
           desktop. Ukuran tombol sendiri tetap (min-height 40px, w-100). */
        .btn-portal-action i {
            font-size: 1.1rem !important;
        }
        .btn-portal-action {
            gap: 0.25rem !important;
        }
    }

    /* Mode Gerbang Button (Green) */
    .btn-portal-gate {
        background-color: #16a34a;
        color: #ffffff !important;
        border-color: #16a34a;
        box-shadow: 0 1px 2px rgba(22, 163, 74, 0.2);
    }
    .btn-portal-gate:hover {
        background-color: #15803d;
        border-color: #15803d;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(22, 163, 74, 0.25);
    }
    .btn-portal-gate:active {
        transform: translateY(0);
        box-shadow: 0 1px 2px rgba(22, 163, 74, 0.2);
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

    /* Wadah Bar Statistik Responsif: Horizontal Scroll di Mobile, Bar di Desktop */
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
    @media (max-width: 767.98px) {
        /* Hapus total bar statistik pada tampilan mobile */
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
    }

    /* ==========================================================================
       BAR STATISTIK (sudah / belum / total) - SATU bar panjang berbingkai.
       - Satu bingkai luar: background putih, border 1px #e2e8f0 (warna yang
         sama dengan chip sebelumnya), radius 8px (sama dengan chip sebelumnya).
       - Tiga bagian berjajar tanpa gap, dipisah SEKAT vertikal 1px; tidak ada
         border/radius sendiri per bagian.
       - Padding, font, ketebalan, line-height, dan tinggi diambil dari chip
         lama, jadi tinggi bar tetap sama dengan tinggi chip sebelumnya.
       - Lebar ikut isi (inline-flex), bukan full width.
       - Wadahnya tetap yang sama (d-none d-md-block, overflow visible), jadi
         jarak vertikal ke judul & ke tabel tidak berubah dan HP tidak tersentuh.
       ========================================================================== */
    @media (min-width: 641px) {
        .stats-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 28px;
            padding: 0;
            background: none;
            border: 0;
            box-shadow: none;
        }
        .stats-bar__item {
            border: 0;
            background: none;
            padding: 0;
            box-shadow: none;
        }
        .stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            line-height: 1;
        }
        .stat-chip strong {
            font-size: 20px;
            font-weight: 700;
            line-height: 1;
        }
        .stat-ico {
            width: 22px;
            height: 22px;
            flex: none;
            background: none;
            border: 0;
            border-radius: 0;
            padding: 0;
            display: block;
        }
        .stat-ico--ok {
            color: #16a34a;
        }
        .stat-ico--no {
            color: #dc2626;
        }
        .stat-ico--all {
            color: #2563eb;
        }
    }

    /* ==========================================================================
       BAR STATISTIK: tepi kirinya sejajar dengan ikon HAMBURGER (garis tiga).
       Tombol hamburger (.mobile-top-hamburger) berada di dalam main yang sama
       dengan konten halaman, dan tombol itu TIDAK memakai padding internal
       (padding: 0 dikunci layout, baik < 1024px maupun >= 1024px). Karena itu:
         x ikon garis tiga = x tombol hamburger = x tepi kiri container konten
       sehingga bar tidak lagi memakai offset ke judul. Nilai padding tombol
       diambil lewat variabel di bawah supaya kompensasinya mengikuti struktur:
       bila suatu saat tombol itu diberi padding, cukup ubah satu nilai ini.
       ========================================================================== */
    @media (min-width: 641px) {
        .stats-bar {
            --presensi-hamburger-pad: 0px;   /* = padding .mobile-top-hamburger */
            margin-left: calc(-1 * var(--presensi-hamburger-pad));
        }
    }

    /* Tabel Enterprise Standar & Roomy */
    .table-custom-card {
        background: #ffffff;
        border: 0 !important;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    /* Sembunyikan Scrollbar Horizontal Secara Total (Clean Look)
       CATATAN: .table-responsive TIDAK ikut disembunyikan di sini, karena pada
       halaman ini tabelnya harus punya scrollbar vertikal yang terlihat di sisi
       kanan. Aturan table-wide (max-height, scrollbar, header sticky) datang
       dari layout bersama. Yang tetap disembunyikan hanya elemen non-tabel. */
    .table-custom-card,
    .badges-scroll-container,
    div[class*="table-responsive"]:not(.table-responsive) {
        scrollbar-width: none !important; /* Firefox */
        -ms-overflow-style: none !important; /* IE 10+ & Edge */
    }
    .table-custom-card::-webkit-scrollbar,
    .badges-scroll-container::-webkit-scrollbar,
    div[class*="table-responsive"]:not(.table-responsive)::webkit-scrollbar {
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
            padding: 0.2rem !important;
            border-radius: 0 !important;
        }
    }

    /* Tombol Buka Kelas (Pure Icon - tanpa background box) */
    .btn-buka-kelas {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        background: transparent !important;
        border: none !important;
        border-radius: 0 !important;
        padding: 0.2rem;
        color: #3b62f6;
        font-size: 0.80rem;
        font-weight: 600;
        text-decoration: none !important;
        box-shadow: none !important;
        transition: color 0.15s ease, opacity 0.15s ease;
        line-height: 1.25;
        white-space: nowrap;
        cursor: pointer;
    }
    .btn-buka-kelas:hover {
        color: #254fd9;
        opacity: 0.85;
        transform: none;
        box-shadow: none !important;
    }
    .btn-buka-kelas:active {
        transform: none;
    }
    .btn-buka-kelas i {
        font-size: 22px !important;
        font-weight: 400 !important;
        width: 24px !important;
        height: 24px !important;
        min-width: 24px !important;
        min-height: 24px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
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

    /* Tab Kamera / Alat Scanner (hanya halaman Presensi Hari Ini).
       Dua tombol MENYATU jadi satu segmented control: menempel tanpa celah
       (gap 0), pembungkus selebar dua tombol saja dan tetap rata tengah
       terhadap panel scanner. Pembungkus tidak punya padding/background
       tambahan, hanya border 1px abu terang + radius 12px + overflow hidden
       supaya sudut membulat hanya di ujung luar gabungan.
       Aturan memakai !important karena layout bersama memberi
       .kiosk-switcher background putih !important. */
    .presensi-hari-ini-scanner-col .kiosk-switcher {
        display: flex;
        justify-content: center;
        align-items: stretch;
        gap: 0;
        width: fit-content;
        max-width: 100%;
        margin-inline: auto;
        background: transparent !important;
        background-color: transparent !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: none !important;
        padding: 0 !important;
        border-radius: 12px !important;
        overflow: hidden;
        margin-bottom: 1.15rem;
    }

    /* Kedua tombol sama persis ukurannya (tinggi, padding, font, ikon) supaya
       tidak ada yang bergeser saat tab berpindah. Tombol KIRI membulat di
       kiri saja, tombol KANAN membulat di kanan saja. */
    .presensi-hari-ini-scanner-col .kiosk-switch-btn {
        background: #ffffff;
        border: 0;
        border-radius: 0;
        padding: 0.42rem 1.1rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        transition: background-color 0.15s ease, color 0.15s ease;
        cursor: pointer;
    }

    /* Garis pemisah tipis hanya di antara dua tombol. */
    .presensi-hari-ini-scanner-col .kiosk-switch-btn:first-child {
        border-right: 1px solid #e2e8f0;
        border-radius: 11px 0 0 11px;
    }

    .presensi-hari-ini-scanner-col .kiosk-switch-btn:last-child {
        border-radius: 0 11px 11px 0;
    }

    .presensi-hari-ini-scanner-col .kiosk-switch-btn:hover {
        background: #f1f5f9;
    }

    /* Tombol AKTIF: biru penuh sampai ke tepi pembungkus. */
    .presensi-hari-ini-scanner-col .kiosk-switch-btn.active {
        background: #2563eb;
        color: #ffffff;
        border-right-color: transparent;
    }

    .presensi-hari-ini-scanner-col .kiosk-switch-btn.active:hover {
        background: #1d4ed8;
        color: #ffffff;
    }

    /* Fokus keyboard: outline tipis biru di dalam tombol (z-index naik
       supaya tidak terpotong overflow:hidden pembungkus). */
    .presensi-hari-ini-scanner-col .kiosk-switch-btn:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: -2px;
        z-index: 1;
    }

    /* Mode Alat Scanner: isi kotak hitam (latar #000 dari tokens.css) rata
       tengah - ikon, teks petunjuk, lalu kotak status input.
       :not(.d-none) dipakai supaya class "d-none" milik JS (untuk
       menyembunyikan/menampilkan panel) tetap menang seperti semula. */
    .hardware-view-panel:not(.d-none) {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 0.9rem;
    }
    .hardware-view-panel .hardware-scan-icon {
        font-size: 4.25rem !important;
        line-height: 1;
    }
    .hardware-view-panel .hardware-hint {
        color: rgba(255, 255, 255, 0.72) !important;
        max-width: 90%;
    }
    .hardware-view-panel .hardware-input-wrap {
        width: 100%;
        max-width: 85%;
    }

    /* Teks jam di bawah panel scanner: rata tengah, label normal & angka
       tebal. Nilai jam diambil dari pengaturan (bukan hardcode). */
    .scanner-time-text {
        text-align: center;
    }

    /* Layar kecil: tab tetap menyatu & di tengah, tidak overflow; teks jam
       boleh turun baris tapi tetap rata tengah. */
    @media (max-width: 575.98px) {
        .presensi-hari-ini-scanner-col .kiosk-switcher {
            flex-wrap: nowrap;
            gap: 0;
        }
        .presensi-hari-ini-scanner-col .kiosk-switch-btn {
            flex: 0 1 auto;
            justify-content: center;
            padding: 0.42rem 0.65rem;
            font-size: 0.78rem;
        }
        .scanner-time-text {
            text-align: center;
        }
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
       RAPIRAN TABEL PRESENSI HARI INI
       Semua aturan di bawah HANYA berlaku di halaman ini (blok styles milik
       view ini), jadi halaman lain tidak ikut berubah.

       Warna status memakai set yang SAMA dengan Presensi Kelas dan diambil dari
       warna teks yang sudah dipakai di public/css/presensi-tokens.css
       (baris .presensi-badge.*) + #1d4ed8 yang sudah ada di sistem. Dipilih
       versi gelap supaya kontrasnya tetap terbaca.
       ========================================================================== */

    /* --- STATUS: TEKS MURNI, TANPA CONTAINER ------------------------------- */
    .presensi-hari-ini-status {
        font-weight: 600 !important;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .presensi-hari-ini-status.hadir     { color: #065f46 !important; } /* hijau */
    .presensi-hari-ini-status.terlambat { color: #92400e !important; } /* oranye */
    .presensi-hari-ini-status.sakit     { color: #1d4ed8 !important; } /* biru  */
    .presensi-hari-ini-status.izin      { color: #5b21b6 !important; } /* ungu   */
    .presensi-hari-ini-status.alfa      { color: #991b1b !important; } /* merah  */
    .presensi-hari-ini-status.libur,
    .presensi-hari-ini-status.belum     { color: #475569 !important; } /* abu    */

    /* --- UPPERCASE UNTUK SEMUA DATA TABEL (header kolom tidak diubah) ----- */
    .table-enterprise tbody td,
    .table-enterprise tbody td .fw-bold {
        text-transform: uppercase;
    }

    /* Nama siswa di tabel ini tidak bold, satu baris (selaras dengan
       kolom Nama Siswa di Presensi Kelas). */
    .table-enterprise tbody td .presensi-hari-ini-nama {
        font-weight: 400 !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* --- TABEL MENGIKUTI ISI, SCROLL DI AREA KONTEN HALAMAN ---------------- */
    /* MODEL SATU AREA SCROLL: kolom & kartu tumbuh alami mengikuti isi tabel.
       Tidak ada tinggi calc(100dvh - ...) dan tidak ada scroll vertikal di
       dalam tabel; yang scroll vertikal hanya area konten halaman (layout
       bersama). */
    .presensi-table-col {
        display: block;
        height: auto;
        min-height: 0;
    }

    .presensi-table-col > .card {
        display: block;
    }

    .presensi-table-col .table-responsive {
        max-height: none !important;
        overflow-y: visible !important;
    }
</style>
@endpush

@section('content')
{{-- Padding atas konten DISAMAKAN dengan halaman Data Siswa/Guru/Kelas (tanpa
     pt tambahan); jarak antar blok tetap memakai space-y.
     pb-4 mobile-only: ruang napas tambah 16px di bawah konten supaya elemen
     terakhir (tabel/baris) tidak nyentuh bottom nav di halaman Presensi. --}}
<div class="space-y-2 sm:space-y-3 pb-4 lg:pb-0">

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
        {{-- DULU: <div class="card border border-light-subtle shadow-sm rounded-3 p-3 mb-3 bg-white">
             REVISI CLEAN LOOK: kontainer/card pembungkus DIHAPUS (border, latar,
             shadow, radius, padding kontener). Yang tersisa hanya dua tombol
             langsung di kanvas putih, full width di mobile, jarak antar tombol
             8px (gap-2) dari div pembungkus di bawah. Tombol & teks TIDAK diubah. --}}
        <div class="mb-1 presensi-mobile-actions">
            <div class="d-flex flex-column gap-2">
                <!-- 1. Buka Scanner QR -->
                <div class="w-100">
                    <button type="button" id="btnToggleScannerMobile" 
                            class="btn-portal-action btn-portal-scanner shadow-2xs w-100" 
                            onclick="toggleInlineScanner()"
                            title="{{ __('Buka Scanner QR') }}">
                        <i class='bx bx-camera' id="toggleScannerIconMobile"></i>
                        <span class="text-nowrap" style="font-size: 0.8rem;" id="toggleScannerTextMobile">{{ __('Scanner QR') }}</span>
                    </button>
                </div>

                <!-- 2. Mode Gerbang -->
                <div class="w-100">
                          <a href="{{ panel_route('absensi.kiosk') }}"
                              class="btn-portal-action btn-portal-gate shadow-2xs w-100"
                       title="{{ __('Mode Gerbang') }}">
                        <i class='bx bx-scan'></i>
                        <span class="text-nowrap" style="font-size: 0.8rem;">{{ __('Mode Gerbang') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. KONTEN KHUSUS DESKTOP (>= 768px): BADGE RINGKASAN STATUS HARIAN        -->
    <!-- ========================================================================= -->
    <div class="badges-scroll-container mb-3 no-scrollbar d-none d-md-block" style="scrollbar-width: none; -ms-overflow-style: none;">
        <div class="stats-bar">
<div class="stats-bar__item"><span class="stat-chip"><svg class="stat-ico stat-ico--ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5 9.5 18 20 6.5"/></svg><strong>{{ $totalSudahAbsen ?? $sumSudah }}</strong> {!! __('sudah presensi') !!}</span></div>
<div class="stats-bar__item"><span class="stat-chip"><svg class="stat-ico stat-ico--no" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5.5 5.5 18.5 18.5M18.5 5.5 5.5 18.5"/></svg><strong>{{ $totalBelumAbsen ?? $sumBelum }}</strong> {!! __('belum presensi') !!}</span></div>
<div class="stats-bar__item"><span class="stat-chip"><svg class="stat-ico stat-ico--all" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="7" r="3.6"/><path d="M5.5 21v-2.2a6.5 6.5 0 0 1 13 0V21z"/><circle cx="4.6" cy="9.2" r="2.4" opacity=".75"/><path d="M0 19.5v-1.3a4.6 4.6 0 0 1 4.6-4.6c.7 0 1.3.1 1.9.4A8 8 0 0 0 4.4 19.5z" opacity=".75"/><circle cx="19.4" cy="9.2" r="2.4" opacity=".75"/><path d="M24 19.5v-1.3a4.6 4.6 0 0 0-4.6-4.6c-.7 0-1.3.1-1.9.4a8 8 0 0 1 2.1 5.5z" opacity=".75"/></svg><strong>{{ $totalSiswaSeluruhnya ?? $sumTotal }}</strong> {!! __('total siswa') !!}</span></div>
        </div>
    </div>

    <!-- MAIN CONTENT LAYOUT -->
    <div class="row g-2 sm:g-3 align-items-start">
        <!-- Kolom Scanner Kiri.
             Class .presensi-hari-ini-scanner-col dipakai HANYA untuk style
             tab Kamera/Alat Scanner di halaman ini (pembungkus visualnya
             dihilangkan); id & class lain tidak diubah. -->
        <div class="col-12 col-lg-5 col-xl-4 presensi-hari-ini-scanner-col" id="scannerColumn" style="display: none;">
            <div class="scanner-kiosk-card mb-3 mb-lg-0">
                
                <!-- Switcher Button (Kamera / Alat Scanner) -->
                <div class="kiosk-switcher">
                    <button type="button" id="btnTabCamera" class="kiosk-switch-btn active" onclick="switchMode('camera')">
                        <i class='bx bx-camera fs-5'></i> {!! __('Kamera') !!}
                    </button>
                    <button type="button" id="btnTabHardware" class="kiosk-switch-btn" onclick="switchMode('hardware')">
                        <i class='bx bx-barcode-reader fs-5'></i> {!! __('Alat Scanner') !!}
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
                        <div id="cameraError" class="alert alert-danger d-none mt-2" role="alert" style="font-size: 0.85rem;"></div>
                        <div id="cameraPlaceholder" class="py-4 position-absolute top-50 start-50 translate-middle w-100" style="background: #fafbfd; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 5;">
                            <i class='bx bx-qr-scan text-secondary' style="font-size: 3rem;"></i>
                            <p class="text-secondary small mt-2 mb-0 fw-semibold">{!! __('Tempelkan kartu QR ke kamera') !!}</p>
                        </div>
                    </div>

                    <!-- Mode Alat Scanner: isi kotak hitam rata tengah.
                         Class .d-none tetap dipakai JS untuk
                         menyembunyikan/menampilkan panel ini. -->
                    <div id="hardwareView" class="d-none w-100 py-3 hardware-view-panel">
                        <i class='bx bx-scan text-primary hardware-scan-icon'></i><p class="hardware-hint fw-semibold small mb-0 px-2">{!! __('Scan kartu dengan alat, atau ketik NIS/NIP lalu tekan Enter') !!}</p>
                            <div class="hardware-input-wrap">
                                <input type="text" id="hardwareInput" class="form-control form-control-sm text-center fw-semibold font-monospace py-2" placeholder="{!! __('Ketik NIS/NIP untuk Siswa/Guru') !!}" autofocus autocomplete="off">
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
                                <div class="scan-result-name" id="scanResultTitle">{!! __('Nama Siswa') !!}</div>
                                <div class="scan-result-status" id="scanResultSubtitle">{!! __('Status Presensi') !!}</div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Jam Operasional di Bawah Kotak.
                     Nilai diambil dari Pengaturan Jadwal (Setting::getCheckInTime /
                     getCheckOutTime, sama seperti Dashboard) - TANPA nilai
                     hardcode, format HH:mm WIB tanpa detik. -->
                <div class="text-secondary scanner-time-text" style="font-size: 0.78rem;">
                    {!! __('Jam masuk') !!} <span class="fw-semibold text-dark">{{ substr((string) ($formattedCheckIn ?? $jam_masuk ?? $checkInTime ?? ''), 0, 5) }} WIB</span>
                </div>

            </div>
        </div>

        @if(request()->filled('status'))
        <!-- Kolom Tabel Siswa Terfilter -->
        <div class="col-12 presensi-table-col">
            <div class="alert alert-primary d-flex align-items-center justify-content-between py-2 px-3 mb-3 rounded-3 shadow-xs">
                <div class="d-flex align-items-center gap-2">
                    <i class='bx bx-filter-alt fs-5'></i>
                    <span class="small fw-semibold">{!! __('Menampilkan siswa dengan status:') !!} <strong class="badge bg-primary fs-7">{{ ucfirst(request('status')) }}</strong> {!! __('pada tanggal') !!} {{ \Carbon\Carbon::parse($tanggal ?? now())->translatedFormat('d F Y') }} ({{ count($processedStudents ?? []) }} {!! __('Siswa') !!})</span>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <!-- Scrollbar tabel sengaja TIDAK disembunyikan (butuh scroll vertikal di kanan) -->
                <div class="table-responsive">
                    <table class="table table-hover table-enterprise table-zebra-custom align-middle mb-0 text-nowrap">
                        <thead class="bg-light text-nowrap">
                            <tr class="text-nowrap">
                                <th style="width: 5%;" class="text-nowrap">{!! __('NO') !!}</th>
                                <th style="width: 35%;" class="text-nowrap">{!! __('Nama Siswa') !!}</th>
                                <th class="text-center text-nowrap" style="width: 15%;">{!! __('NISN') !!}</th>
                                <th class="text-center text-nowrap" style="width: 15%;">{!! __('Kelas') !!}</th>
                                <th class="text-center text-nowrap" style="width: 15%;">{!! __('Status') !!}</th>
                                <th class="text-center text-nowrap" style="width: 15%;">{!! __('Keterangan') !!}</th>
                            </tr>
                        </thead>
                        <tbody class="text-nowrap">
                            @forelse($processedStudents as $idx => $st)
                            <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }} text-nowrap">
                                <td class="text-center text-secondary small text-nowrap">{{ $idx + 1 }}</td>
                                <td class="text-nowrap">
                                    <div class="fw-bold text-dark text-nowrap presensi-hari-ini-nama">{{ $st->name }}</div>
                                </td>
                                <td class="text-center text-secondary font-monospace small text-nowrap">
                                    {{ $st->nisn ?: '-' }}
                                </td>
                                <td class="text-center text-nowrap">
                                    <span class="badge bg-light text-dark border text-nowrap">{{ $st->schoolClass->name ?? '-' }}</span>
                                </td>
                                <td class="text-center text-nowrap">
                                    @php
                                        // Status tetap TEKS MURNI (tanpa badge/container).
                                        // Kelas status menentukan warnanya lewat CSS halaman ini.
                                        $statusColor = match(strtolower($st->current_status ?? '')) {
                                            'hadir' => 'hadir',
                                            'terlambat' => 'terlambat',
                                            'sakit' => 'sakit',
                                            'izin' => 'izin',
                                            'alfa', 'alpha' => 'alfa',
                                            'libur' => 'libur',
                                            default => 'belum',
                                        };
                                    @endphp
                                    <span class="presensi-hari-ini-status {{ $statusColor }} text-nowrap">{{ $st->current_status }}</span>
                                </td>
                                <td class="text-center text-secondary small text-nowrap">
                                    {{ $st->attendance_notes ?? '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr class="text-nowrap">
                                <td colspan="6" class="text-center py-3 text-secondary text-nowrap empty-state">
                                    <i class='bx bx-error' aria-hidden='true'></i>
                                    BELUM ADA DATA SISWA BERSTATUS {{ strtoupper(request('status')) }} PADA TANGGAL INI.
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
        <div class="col-12 presensi-table-col" id="tableColumn">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <!-- Scrollbar tabel sengaja TIDAK disembunyikan (butuh scroll vertikal di kanan) -->
                <div class="table-responsive">
                    <table class="table table-hover table-enterprise table-zebra-custom align-middle mb-0 text-nowrap">
                        <thead class="bg-light text-nowrap">
                            <tr class="text-nowrap">
                                <th class="text-center align-middle col-th-kelas text-nowrap" style="width: 16%;">{!! __('Kelas') !!}</th>
                                <th class="text-center align-middle col-th-stat text-nowrap" style="width: 9%;">{!! __('Total') !!}</th>
                                <th class="text-center align-middle col-th-stat text-nowrap" style="width: 9%;">{!! __('Hadir') !!}</th>
                                <th class="text-center align-middle col-th-stat text-nowrap" style="width: 9%;">{!! __('Sakit') !!}</th>
                                <th class="text-center align-middle col-th-stat text-nowrap" style="width: 9%;">{!! __('Izin') !!}</th>
                                <th class="text-center align-middle col-th-stat text-nowrap" style="width: 9%;">{!! __('Alfa') !!}</th>
                                <th class="text-center align-middle col-th-kehadiran text-nowrap" style="width: 22%;">{!! __('Kehadiran') !!}</th>
                                <th class="text-center align-middle col-th-aksi text-nowrap" style="width: 17%;">{!! __('Aksi') !!}</th>
                            </tr>
                        </thead>
                        <tbody class="text-nowrap">
                            @forelse($listKelas as $cls)
                            @php
                                $clsId = is_array($cls) ? ($cls['id'] ?? null) : ($cls->id ?? null);
                                $clsName = is_array($cls) ? ($cls['nama_kelas'] ?? $cls['name'] ?? '-') : ($cls->name ?? $cls->nama_kelas ?? '-');
                                $totSiswa = is_array($cls) ? ($cls['total_siswa'] ?? 0) : ($cls->total_students ?? $cls->total_siswa ?? $cls->students_count ?? 0);
                                $hadirTepat = is_array($cls) ? ($cls['hadir'] ?? 0) : ($cls->hadir_count ?? $cls->hadir ?? 0);
                                $terlambat = is_array($cls) ? ($cls['terlambat'] ?? 0) : ($cls->terlambat_count ?? $cls->terlambat ?? 0);
                                $sakit = is_array($cls) ? ($cls['sakit'] ?? 0) : ($cls->sakit_count ?? $cls->sakit ?? 0);
                                $izin = is_array($cls) ? ($cls['izin'] ?? 0) : ($cls->izin_count ?? $cls->izin ?? 0);
                                $alpha = is_array($cls) ? ($cls['alpha'] ?? 0) : ($cls->alfa_count ?? $cls->alpha ?? $cls->alfa ?? 0);
                                $sudah = is_array($cls) ? ($cls['sudah_absen'] ?? 0) : ($cls->sudah_absen ?? ($hadirTepat + $terlambat + $sakit + $izin + $alpha));
                                /* HADIR di daftar kelas = siswa yang HADIR, termasuk yang
                                     TERLAMBAT (aturan yang sama dipakai Rekap,
                                     Dashboard, dan ringkasan per kelas). */
                                $hadir = $hadirTepat + $terlambat;
                                /* Persentase diambil apa adanya dari DailyAttendanceSummary
                                     (rumus yang sama dengan Rekap & Dashboard):
                                     round((hadir + terlambat) / total siswa x 100). */
                                $persentase = is_array($cls)
                                    ? ($cls['persentase'] ?? ($totSiswa > 0 ? round(($hadir / $totSiswa) * 100) : 0))
                                    : ($cls->percentage ?? ($totSiswa > 0 ? round(($hadir / $totSiswa) * 100) : 0));
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
                                        <span class="text-slate-900 text-nowrap" style="color: #0f172a !important; font-size: 0.875rem; font-weight: 400;">{!! $alpha !!}</span>
                                    </div>
                                </td>
                                <td class="text-center align-middle col-td-kehadiran text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center w-100 text-nowrap">
                                        <span class="text-slate-900 text-nowrap" style="font-size: 0.875rem; color: #0f172a !important; font-weight: 400;">
                                            {!! $persentase ?? 0 !!}%
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center align-middle col-td-aksi text-nowrap">
                                    <div class="d-flex align-items-center justify-content-center w-100 text-nowrap">
                                        <a href="{{ panel_route('absensi.show', ['schoolClass' => $clsId, 'tanggal' => $tanggal ?? date('Y-m-d')]) }}" class="btn-buka-kelas text-nowrap" title="{!! __('Buka Kelas') !!}" aria-label="{!! __('Buka Kelas') !!}">
                                            <i class='bx bx-door-open' style="font-size: 22px !important; font-weight: 400 !important; width: 24px !important; height: 24px !important; min-width: 24px !important; min-height: 24px !important; display: inline-flex; align-items: center; justify-content: center; color: #3b62f6 !important;"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="text-nowrap">
                                <td colspan="8" class="text-center py-3 text-secondary text-nowrap empty-state">
                                    <i class='bx bx-error' aria-hidden='true'></i>
                                    {!! __('Belum ada data kelas tersedia.') !!}
                                </td>
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
{{-- Elemen <audio> beep scanner. Dulu diletakkan DI LUAR section sehingga
     tercetak SEBELUM <!DOCTYPE html> dari layout - itu membuat browser
     mengabaikan blok <head> (favicon, title, meta) dan ikon tab jadi globe
     polos. Sekarang ikut di dalam stack scripts => dokumen tetap dimulai
     dengan <!DOCTYPE html> dan <head> terbaca normal. --}}
<audio id="beepSound" src="{{ asset('audio/beep.mp3') }}" preload="auto"></audio>
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

    // Beep = file audio/beep.mp3 via fungsi bersama di scanner.js (window.playBeep).
    // SATU titik pusat umpan balik per halaman ini: dipanggil TEPAT SATU KALI
    // per scan (respons diterima ATAU gagal jaringan). SEMUA hasil berbunyi beep
    // dengan nada yang sama persis — termasuk kartu tidak dikenal, sudah absen,
    // kode ngawur, error server, dan gagal jaringan. Hasil gagal ikut getar
    // (getar lama tidak hilang), kode kosong dipanggil tanpa argumen (beep saja).
    function playDailyBeep(success = true) {
        if (typeof window.playBeep === 'function') {
            window.playBeep();
        } else {
            const beep = document.getElementById('beepSound');
            if (beep) {
                try {
                    beep.currentTime = 0;
                    const p = beep.play();
                    if (p && typeof p.catch === 'function') { p.catch(() => {}); }
                } catch (e) {}
            }
        }
        if (success === false && typeof window.vibrateOnScanFail === 'function') {
            window.vibrateOnScanFail();
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

            // Buka kunci audio (gestur user) supaya beep scan diizinkan browser.
            if (typeof window.unlockScannerAudio === 'function') {
                window.unlockScannerAudio();
            }

            if (scannerCol) scannerCol.style.display = 'block';
            if (tableCol) tableCol.className = 'col-12 col-lg-7 col-xl-8';

            if (btnDesktop) {
                btnDesktop.className = 'btn btn-action-header btn-danger fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-2xs';
                if (iconDesktop) iconDesktop.className = 'bx bx-camera-off';
                if (textDesktop) textDesktop.textContent = (window.__i18n && window.__i18n.tutup_scanner) || 'Tutup Scanner';
            }
            if (btnMobile) {
                btnMobile.className = 'btn-portal-action btn-portal-scanner scanner-active shadow-2xs';
                if (iconMobile) iconMobile.className = 'bx bx-camera-off';
                if (textMobile) textMobile.textContent = (window.__i18n && window.__i18n.tutup) || 'Tutup';
            }

            switchMode(activeMode);
        } else {
            sessionStorage.removeItem('daily_scanner_open');

            if (scannerCol) scannerCol.style.display = 'none';
            if (tableCol) tableCol.className = 'col-12';

            if (btnDesktop) {
                btnDesktop.className = 'btn btn-action-header btn-primary fw-semibold d-inline-flex align-items-center justify-content-center gap-1 shadow-2xs';
                if (iconDesktop) iconDesktop.className = 'bx bx-camera';
                if (textDesktop) textDesktop.textContent = (window.__i18n && window.__i18n.buka_scanner_qr) || 'Buka Scanner QR';
            }
            if (btnMobile) {
                btnMobile.className = 'btn-portal-action btn-portal-scanner shadow-2xs';
                if (iconMobile) iconMobile.className = 'bx bx-camera';
                if (textMobile) textMobile.textContent = (window.__i18n && window.__i18n.scanner_qr) || 'Scanner QR';
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
    // Kamera/Alat Scanner) selalu bisa memanggilnya dari atribut onclick.
    window.toggleInlineScanner = toggleInlineScanner;
    window.switchMode = switchMode;

    let lastDailyScannedToken = '';
    let lastDailyScannedTimestamp = 0;

    function processCode(token) {
        const cleanToken = (token || '').trim();
        if (!cleanToken) {
            // KODE KOSONG: tetap satu beep (tanpa notifikasi & tanpa getar).
            playDailyBeep();
            return;
        }
        if (isProcessing) return;

        const now = Date.now();
        if (cleanToken === lastDailyScannedToken && (now - lastDailyScannedTimestamp < 2500)) {
            return;
        }
        lastDailyScannedToken = cleanToken;
        lastDailyScannedTimestamp = now;

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
            playDailyBeep(status === 200 && body && body.success);
            if (typeof handleScanResult === 'function') {
                handleScanResult(status, body);
            }
        })
        .catch(err => {
            console.error('[daily-scanner] Fetch error:', err);
            playDailyBeep(false);
            if (typeof handleScanResult === 'function') {
                handleScanResult(500, {
                    success: false,
                    message: (window.__i18n && window.__i18n.connection_error) || 'Terjadi kendala koneksi ke server, coba lagi'
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
            console.error('Html5Qrcode library not loaded. Make sure the script is loaded.');
            showCameraError((window.__i18n && window.__i18n.library_scanner_not_loaded) || 'Library scanner tidak dimuat. Muat ulang halaman.');
            return;
        }

        // Check if browser supports camera
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            console.error('Camera not supported in this browser');
            showCameraError((window.__i18n && window.__i18n.browser_camera_unsupported) || 'Browser tidak mendukung akses kamera.');
            return;
        }
        if (typeof CameraSelect === 'undefined') {
            console.error('CameraSelect script not loaded');
            showCameraError((window.__i18n && window.__i18n.camera_script_not_loaded) || 'Script kamera tidak dimuat. Muat ulang halaman.');
            return;
        }

        const placeholder = document.getElementById('cameraPlaceholder');
        if (placeholder) placeholder.style.display = 'none';

        const errorDiv = document.getElementById('cameraError');
        if (errorDiv) errorDiv.classList.add('d-none');

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
            }
        });
    }

    function showCameraError(message) {
        console.error(message);
        const errorDiv = document.getElementById('cameraError');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('d-none');
        }
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

<script>
(function () {
  try {
    var p = new URLSearchParams(window.location.search);
    if (p.get('scan') !== '1' && p.get('tab') !== 'scanner') return;
    var tries = 0;
    var timer = setInterval(function () {
      tries++;
      var btn = document.getElementById('btnToggleScannerDesktop');
      if (!btn || btn.offsetParent === null) {
        btn = document.getElementById('btnToggleScannerMobile');
      }
      if (btn && btn.offsetParent !== null) {
        clearInterval(timer);
        btn.click();
        history.replaceState({}, '', window.location.pathname);
      } else if (tries > 40) {
        clearInterval(timer);
      }
    }, 150);
  } catch (e) { console.error(e); }
})();
</script>
@endpush
