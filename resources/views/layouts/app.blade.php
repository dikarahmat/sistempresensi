<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'Sistem Presensi') | {{ \App\Models\Setting::getAppTitle() ?? 'SMP PGRI' }}</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="Sistem Presensi Sekolah - Platform manajemen kehadiran digital berbasis QR Code untuk sekolah. Monitoring presensi realtime, akurat, dan terintegrasi.">
    <meta name="keywords" content="presensi sekolah, sistem presensi, qr code, attendance, kehadiran siswa, SMP">
    <meta name="author" content="{{ \App\Models\Setting::getSchoolName() }}">
    <meta name="robots" content="index, follow">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Sistem Presensi') | {{ \App\Models\Setting::getAppTitle() }}">
    <meta property="og:description" content="Sistem Presensi Sekolah - Platform manajemen kehadiran digital berbasis QR Code untuk sekolah.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset(\App\Models\Setting::getLogo()) }}">
    <meta property="og:site_name" content="{{ \App\Models\Setting::getAppTitle() }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('title', 'Sistem Presensi') | {{ \App\Models\Setting::getAppTitle() }}">
    <meta name="twitter:description" content="Sistem Presensi Sekolah - Platform manajemen kehadiran digital berbasis QR Code.">

    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="{{ asset(\App\Models\Setting::getLogo()) }}">
    <link rel="apple-touch-icon" href="{{ asset(\App\Models\Setting::getLogo()) }}">

    <!-- JSON-LD Organization Schema -->
    <script type="application/ld+json">
    @php
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => \App\Models\Setting::getSchoolName(),
            'description' => 'Sistem Presensi Sekolah - Platform manajemen kehadiran digital berbasis QR Code',
            'url' => url('/'),
            'logo' => asset(\App\Models\Setting::getLogo()),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => \App\Models\Setting::getSchoolAddress(),
            ],
            'telephone' => \App\Models\Setting::getSchoolPhone(),
        ];
        echo json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    @endphp
    </script>

    <!-- Google Fonts: Poppins, Roboto & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & Boxicons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Local Assets via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Presensi Unified Design Tokens -->
    {{-- Query `?v=filemtime(...)` = cache busting. Tanpa ini browser/CDN bisa
         menyimpan CSS lama setelah deploy sehingga tampilan production tidak
         sama dengan lokal. --}}
    <link rel="stylesheet" href="{{ asset('css/presensi-tokens.css') }}?v={{ file_exists(public_path('css/presensi-tokens.css')) ? filemtime(public_path('css/presensi-tokens.css')) : config('app.version', '1') }}">

    <!-- ===========================================================================
         SIDEBAR COLLAPSE - TERAPAKAN SEBELUM RENDER PERTAMA (anti kedip)
         Script ini WAJIB berada di <head> DAN sebelum <style> utama agar class
         sudah menempel di <html> ketika browser mulai melukis. Kalau class baru
         ditambahkan setelah DOM siap, sidebar akan berkedip (flash) dari kondisi
         expanded ke collapsed setiap refresh / pindah halaman.
         State disimpan di localStorage supaya pilihan user bertahan.
         Hanya berlaku di desktop (>= 1024px); script ini sendiri hanya
         menempelkan class, sedangkan seluruh aturan visualnya dikunci di
         media query min-width: 1024px. ======================================== -->
    <script>
        (function () {
            try {
                if (window.localStorage.getItem('sidebarCollapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) { /* localStorage diblokir - abaikan, sidebar tetap expanded */ }
        })();
    </script>

    @stack('styles')

    <style>
        :root {
            --sidebar-width: 238px;
            /* Lebar sidebar saat collapsed (desktop). 76px comfortably berada
               di rentang 72-80px yang diminta: cukup untuk ikon + highlight. */
            --sidebar-width-collapsed: 76px;
            --sidebar-gap: 10px;
            --primary-blue: #3b62f6;
            --primary-blue-hover: #2563eb;
            --text-dark: #1e293b;
            --bg-canvas: #3b62f6;
            --bottom-nav-height: 56px;
        }

        /* --------------------------------------------------------------------------
           1. ROOT & RESET KANVAS
           -------------------------------------------------------------------------- */
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100%;
            min-height: 100vh;
            /* WARNA SAMA PERSIS dengan sidebar (#3b62f6). Sebelumnya warna
               kanvas memakai utility arbitrary-value Tailwind (hex 044ABA)
               yang HANYA aktif bila utility-nya ter-generate oleh build Vite.
               Build lokal tidak menghasilkannya (no-op), tapi build
               production menghasilkannya -> muncul garis/belah biru yang
               lebih gelap di tepi sidebar. Sekarang warna kanvas di-hardcode
               di sini supaya identik di semua environment. */
            background-color: #3b62f6 !important;
            color: var(--text-dark);
            overflow: hidden !important;
            overflow-x: hidden !important;
            font-family: 'Poppins', 'Roboto', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Wrapper kanvas aplikasi: satu-satunya sumber "celah" di belakang
           sidebar, jadi WAJIB warna identik dengan sidebar. */
        body > .app-canvas,
        .app-canvas {
            background-color: #3b62f6 !important;
        }

        /* ==========================================================================
           STANDARISASI TIPOGRAFI ENTERPRISE
           - Font Poppins/Robot untuk seluruh UI
           - Data dinamis (NIS, nama, status, kode) = UPPERCASE
           - Teks normal (judul, label, deskripsi) = normal/sentence case
           ========================================================================== */

        /* --- Tabel Matrix: Data dinamis & header tabel --- */
        .table-matrix thead th,
        .table-matrix tbody td {
            font-family: 'Poppins', 'Roboto', sans-serif;
            letter-spacing: 0.03em;
        }

        .table-matrix thead th {
            font-weight: 600;
            text-transform: uppercase;
        }

        .table-matrix tbody td {
            font-weight: 400;
            text-transform: uppercase;
        }

        /* --- Tabel Zebra Custom: Data dinamis & header tabel --- */
        .table-zebra-custom thead th,
        .table-zebra-custom tbody td {
            font-family: 'Poppins', 'Roboto', sans-serif;
            letter-spacing: 0.03em;
        }

        .table-zebra-custom thead th {
            font-weight: 600;
            text-transform: uppercase;
        }

        .table-zebra-custom tbody td {
            font-weight: 400;
            text-transform: uppercase;
        }

        /* --- Kartu Informasi (Card) --- */
        .card {
            font-family: 'Poppins', 'Roboto', sans-serif;
        }

        .card .card-title,
        .card .card-header,
        .card .card-body,
        .card .card-footer {
            font-family: 'Poppins', 'Roboto', sans-serif;
        }

        /* --- Badge & Status Pill --- */
        .badge,
        .status-badge-pill,
        .filter-pill {
            font-family: 'Poppins', 'Roboto', sans-serif;
            font-weight: 600;
            letter-spacing: 0.03em;
        }

        /* --- Stat Card Modern --- */
        .stat-card-modern,
        .stat-card-polished {
            font-family: 'Poppins', 'Roboto', sans-serif;
        }

        .stat-card-modern .stat-value,
        .stat-card-modern .stat-label,
        .stat-card-polished .stat-value,
        .stat-card-polished .stat-label {
            font-family: 'Poppins', 'Roboto', sans-serif;
            letter-spacing: 0.03em;
        }

        /* --- Form Label & Helper Text (sentence case - TIDAK di-uppercase) --- */
        .form-label,
        .filter-label,
        .form-check-label,
        .form-text {
            font-family: 'Poppins', 'Roboto', sans-serif;
            font-weight: 500;
            letter-spacing: 0.03em;
        }

        /* --- Header Halaman (UPPERCASE) --- */
        .header-main-title {
            font-family: 'Poppins', 'Roboto', sans-serif;
            text-transform: uppercase;
        }

        /* --- Nav Sidebar (sentence case - TIDAK di-uppercase) --- */
        .nav-link {
            font-family: 'Poppins', 'Roboto', sans-serif;
        }

        /* --- Tombol & Aksi --- */
        .btn,
        .btn-solid-pill,
        .btn-row-action,
        .btn-modern-smooth,
        .btn-action-header,
        .btn-portal-action,
        .btn-buka-kelas,
        .btn-green-excel,
        .btn-red-pdf,
        .filter-btn,
        .recap-mobile-submit {
            font-family: 'Poppins', 'Roboto', sans-serif;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        /* --- Tabel Custom Card --- */
        .table-custom-card {
            font-family: 'Poppins', 'Roboto', sans-serif;
        }

        .table-custom-card .card-header,
        .table-custom-card .card-title {
            font-family: 'Poppins', 'Roboto', sans-serif;
            letter-spacing: 0.03em;
        }

        /* --- Alert --- */
        .alert {
            font-family: 'Poppins', 'Roboto', sans-serif;
        }

        /* --- Pagination --- */
        .pagination-compact {
            font-family: 'Poppins', 'Roboto', sans-serif;
        }

        .pagination-compact .page-link {
            font-family: 'Poppins', 'Roboto', sans-serif;
            letter-spacing: 0.03em;
        }

        .pagination-arrow-container {
            width: 100% !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            padding-top: 1.25rem !important;
            padding-bottom: calc(1.5rem + env(safe-area-inset-bottom, 0px)) !important;
            margin: 0 auto !important;
        }

        .pagination-arrow-bar {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.85rem !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 9999px !important;
            padding: 5px 8px !important;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06) !important;
        }

        .pagination-arrow-btn {
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            min-height: 44px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 9999px !important;
            background-color: #f8fafc !important;
            color: #1e293b !important;
            border: 1px solid #cbd5e1 !important;
            font-size: 1.45rem !important;
            line-height: 1 !important;
            text-decoration: none !important;
            transition: all 0.15s ease-in-out !important;
            cursor: pointer !important;
            user-select: none !important;
        }

        .pagination-arrow-btn:hover:not(.is-disabled) {
            background-color: #2563eb !important;
            color: #ffffff !important;
            border-color: #2563eb !important;
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.28) !important;
            transform: translateY(-1px) !important;
        }

        .pagination-arrow-btn.is-disabled {
            background-color: #f1f5f9 !important;
            color: #94a3b8 !important;
            border-color: #e2e8f0 !important;
            cursor: not-allowed !important;
            opacity: 0.6 !important;
            pointer-events: none !important;
        }

        .pagination-arrow-info {
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            color: #475569 !important;
            padding: 0 0.85rem !important;
            white-space: nowrap !important;
            user-select: none !important;
            font-family: 'Poppins', 'Roboto', sans-serif !important;
        }

        /* --- Modal --- */
        .modal-content {
            font-family: 'Poppins', 'Roboto', sans-serif;
        }

        .modal-title {
            font-family: 'Poppins', 'Roboto', sans-serif;
            letter-spacing: 0.03em;
        }

        /* --- Shortcut Card & Operasional Card --- */
        .shortcut-card-interactive,
        .operasional-card-interactive {
            font-family: 'Poppins', 'Roboto', sans-serif;
        }

        .shortcut-card-interactive .card-title,
        .shortcut-card-interactive .card-text,
        .operasional-card-interactive .card-title,
        .operasional-card-interactive .card-text {
            font-family: 'Poppins', 'Roboto', sans-serif;
            letter-spacing: 0.03em;
        }

        /* --- Table Status Cells (tetap uppercase) --- */
        .cell-present,
        .cell-late,
        .cell-sick,
        .cell-permission,
        .cell-alpha,
        .cell-holiday,
        .cell-future {
            font-family: 'Poppins', 'Roboto', sans-serif;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        /* --- Table Zebra Custom: Override untuk sel berisi teks normal --- */
        .table-zebra-custom tbody td.text-secondary,
        .table-zebra-custom tbody td.text-muted {
            font-weight: 400;
        }

        /* --- Helper Class: Teks Data Dinamis (uppercase) --- */
        .data-uppercase {
            text-transform: uppercase !important;
        }

        /* --- Helper Class: Teks Normal (non-uppercase) --- */
        .data-normal {
            text-transform: none !important;
        }

        @media (max-width: 767.98px) {
            html, body {
                background-color: #ffffff !important;
            }
        }

        [x-cloak] { display: none !important; }
        a { text-decoration: none; }

        /* Pencegahan konflik class .collapse antara Tailwind CSS CDN (visibility: collapse) dan Bootstrap 5 Collapse */
        .collapse.show,
        .collapsing,
        .accordion-collapse.collapse.show,
        .accordion-collapse.collapsing {
            visibility: visible !important;
        }

        /* --------------------------------------------------------------------------
           2. HIERARKI Z-INDEX: SIDEBAR > OVERLAY > BOTTOM NAVBAR > KONTEN
           -------------------------------------------------------------------------- */

        /* A. SIDEBAR MOBILE DRAWER (Z-INDEX 9999999 - PALING TINGGI DI ATAS SEMUA ELEMEN) */
        aside,
        .app-sidebar-drawer {
            width: var(--sidebar-width) !important;
            min-width: var(--sidebar-width) !important;
            max-width: var(--sidebar-width) !important;
            height: 100vh !important;
            min-height: 100vh !important;
            flex-shrink: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            /* Sudut melengkung HANYA untuk drawer mobile, diaktifkan di media
               query max-width: 1023.98px di bawah. Di desktop sidebar wajib
               PERSGI (lihat blok Desktop), karena sebelumnya radius ini
               berada di rule global sehingga ikut merusak tampilan desktop. */
            border-radius: 0 !important;
            border: none !important;
            box-sizing: border-box !important;
            background-color: #3b62f6 !important;
            overflow: hidden;
        }

        /* Mobile & Tablet (< 1024px) Drawer Style */
        @media (max-width: 1023.98px) {
            aside,
            .app-sidebar-drawer {
                position: fixed !important;
                top: env(safe-area-inset-top, 0px) !important;
                left: 0 !important;
                bottom: 0 !important;
                /* FIX BENAR: dvh mengikuti tinggi layar AKTUAL (termasuk saat address bar
                   browser masih terlihat), beda dengan vh/% yang mengasumsikan address bar
                   sudah hilang -> itu sebabnya Logout kepotong di luar layar yang terlihat.
                   Baris 100vh ditulis dulu sebagai fallback, lalu 100dvh override-nya
                   di browser yang sudah support (pola sama seperti .content-scroll-wrapper). */
                height: calc(100vh - env(safe-area-inset-top, 0px)) !important;
                height: calc(100dvh - env(safe-area-inset-top, 0px)) !important;
                max-height: calc(100vh - env(safe-area-inset-top, 0px)) !important;
                max-height: calc(100dvh - env(safe-area-inset-top, 0px)) !important;
                overflow: hidden !important;
                width: var(--sidebar-width) !important;
                min-width: 0 !important;
                max-width: 78vw !important;
                /* Sudut melengkung khusus drawer mobile (tidak diubah dari sebelumnya). */
                border-top-right-radius: 1.25rem !important;
                border-bottom-right-radius: 1.25rem !important;
                z-index: 1045 !important;
                box-shadow: 20px 0 50px -10px rgba(15, 23, 42, 0.45), 8px 0 25px -5px rgba(15, 23, 42, 0.25) !important;
                transform: translate3d(-100%, 0, 0) !important;
                -webkit-transform: translate3d(-100%, 0, 0) !important;
                visibility: hidden !important;
                pointer-events: none !important;
                will-change: transform, visibility !important;
                backface-visibility: hidden !important;
                -webkit-backface-visibility: hidden !important;
                transition: transform 0.32s cubic-bezier(0.32, 0.72, 0, 1), visibility 0.32s ease !important;
            }

            aside.mobile-sidebar-active,
            .app-sidebar-drawer.mobile-sidebar-active {
                visibility: visible !important;
                pointer-events: auto !important;
                transform: translate3d(0, 0, 0) !important;
                -webkit-transform: translate3d(0, 0, 0) !important;
            }

            /* Aside di dalam drawer: kolom flex penuh tinggi supaya footer Logout
               menempel di dasar dan area menu saja yang boleh menggulir.
               min-width:0 KRUSIAL: tanpa ini, .app-sidebar-panel (min-width:16rem)
               menang karena specificity lebih tinggi -> aside overflow ke kanan
               dan pill menu aktif terpotong oleh overflow:hidden drawer. */
            .app-sidebar-drawer aside,
            aside aside {
                width: 100% !important;
                min-width: 0 !important;
                height: 100% !important;
                max-height: 100% !important;
                min-height: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                visibility: visible !important;
                pointer-events: auto !important;
                transform: none !important;
                position: relative !important;
                box-shadow: none !important;
                border-top-right-radius: 1.25rem !important;
                border-bottom-right-radius: 1.25rem !important;
            }
        }

        /* Desktop (>= 1024px) Static Sidebar */
        @media (min-width: 1024px) {
            aside,
            .app-sidebar-drawer {
                position: relative !important;
                display: flex !important;
                visibility: visible !important;
                pointer-events: auto !important;
                transform: none !important;
                transition: none !important;
                z-index: 40 !important;
                margin-right: 0 !important;
                /* SIDEBAR DESKTOP HARUS PERSGI: menempel penuh dari atas sampai
                   bawah tanpa sudut melengkung & tanpa garis tepi.
                   Penting: production (Railway) memakai build Vite yang berbeda
                   dari lokal, sehingga utility pembulatan sisi kanan ikut
                   ter-generate di sana tapi tidak di lokal. Rule ini
                   menetralkan keduanya. */
                border-radius: 0 !important;
                border-top-right-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
                border: none !important;
                outline: none !important;
                box-shadow: none !important;
            }
        }

        /* B. BACKDROP OVERLAY (Z-INDEX 9999990 - MENUTUPI SELURUH LAYAR & BOTTOM NAVBAR) */
        .mobile-sidebar-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            height: 100dvh !important;
            background-color: rgba(0, 0, 0, 0.45) !important;
            -webkit-backdrop-filter: blur(4px) !important;
            backdrop-filter: blur(4px) !important;
            z-index: 1040 !important;
            touch-action: none !important;
            cursor: pointer !important;
        }

        /* C. FULL-WIDTH EDGE-TO-EDGE BOTTOM NAVBAR (Z-INDEX 99990 - DI BAWAH OVERLAY & SIDEBAR) */
        .mobile-bottom-nav {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            height: calc(var(--bottom-nav-height) + env(safe-area-inset-bottom, 0px)) !important;
            min-height: var(--bottom-nav-height) !important;
            margin: 0 !important;
            padding: 0 env(safe-area-inset-right, 0px) env(safe-area-inset-bottom, 0px) env(safe-area-inset-left, 0px) !important;
            background-color: #ffffff !important;
            border-radius: 0 !important; /* Lurus mentok penuh kiri-kanan */
            border: none !important;
            border-top: 1px solid #e2e8f0 !important;
            box-shadow: 0 -2px 10px rgba(15, 23, 42, 0.06) !important;
            z-index: 1030 !important;
            transform: translateZ(0) !important;
            -webkit-transform: translateZ(0) !important;
            will-change: transform !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
            display: block !important;
        }

        /* Pastikan disembunyikan sepenuhnya di Desktop tanpa flickering */
        @media (min-width: 1024px) {
            .mobile-bottom-nav {
                display: none !important;
            }
        }

        .mobile-nav-grid {
            display: grid !important;
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            height: var(--bottom-nav-height) !important;
            width: 100% !important;
            max-width: 600px !important;
            margin: 0 auto !important;
            align-items: center !important;
            position: relative !important;
            padding: 0 4px !important;
            box-sizing: border-box !important;
        }

        .mobile-nav-item {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            height: 100% !important;
            position: relative !important;
            text-decoration: none !important;
            color: #64748b !important;
            transition: color 0.15s ease, transform 0.12s ease !important;
            -webkit-tap-highlight-color: transparent !important;
            overflow: hidden !important;
            border-radius: 8px !important;
            padding: 4px 0 !important;
        }

        .mobile-nav-item:active {
            transform: scale(0.93);
        }

        @media (hover: hover) {
            .mobile-nav-item:not(.active):hover {
                color: #2563eb !important;
            }
            .mobile-nav-item:not(.active):hover .mobile-nav-icon {
                transform: translateY(-1px);
                color: #2563eb !important;
            }
        }

        .mobile-nav-item.active {
            color: #2563eb !important;
        }

        /* Indikator Bar Atas Menu Aktif */
        .mobile-nav-indicator {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 28px;
            height: 3px;
            background: #2563eb;
            border-radius: 0 0 3px 3px;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
            z-index: 3;
        }

        /* Efek Spotlight Halus & Elegan */
        .mobile-nav-spotlight {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            background: linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0%, rgba(37, 99, 235, 0.02) 60%, transparent 100%);
            clip-path: polygon(25% 0%, 75% 0%, 100% 100%, 0% 100%);
        }

        .mobile-nav-icon {
            font-size: 1.4rem;
            line-height: 1;
            margin-bottom: 2px;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), color 0.15s ease;
            position: relative;
            z-index: 2;
        }

        .mobile-nav-label {
            font-size: 10.5px;
            font-weight: 500;
            letter-spacing: -0.01em;
            line-height: 1;
            position: relative;
            z-index: 2;
            transition: font-weight 0.15s ease, color 0.15s ease;
        }

        .mobile-nav-item.active .mobile-nav-label {
            font-weight: 700 !important;
            color: #2563eb !important;
        }

        .mobile-nav-item.active .mobile-nav-icon {
            color: #2563eb !important;
            transform: translateY(0);
        }

        /* --------------------------------------------------------------------------
           3. KONTEN UTAMA & WRAPPER SCROLL
           -------------------------------------------------------------------------- */
        .content-scroll-wrapper {
            height: 100vh !important;
            height: 100dvh !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-y: contain;
            box-sizing: border-box;
            scroll-behavior: smooth;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }

        .content-scroll-wrapper::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        @media (max-width: 767.98px) {
            .content-scroll-wrapper {
                padding: 0 !important;
                background-color: #ffffff !important;
            }

            main,
            .content-scroll-wrapper > main,
            main.w-full {
                min-height: 100vh !important;
                min-height: 100dvh !important;
                width: 100% !important;
                background-color: #ffffff !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                margin: 0 !important;
                /* padding-top sengaja 0: header sticky yang memegang area notch/status bar,
                   supaya tidak ada celah putih yang bisa "bocor" di atas header.
                   padding-bottom: tinggi bottom nav (56px) + ruang napas 2.5rem (40px) + safe area inset
                   supaya tombol paling bawah (Detail Siswa, Pengaturan, dll.) tidak terpotong saat di-scroll mentok. */
                padding: 0 calc(16px + env(safe-area-inset-right, 0px)) calc(var(--bottom-nav-height) + 2.5rem + env(safe-area-inset-bottom, 0px)) calc(16px + env(safe-area-inset-left, 0px)) !important;
            }

            body {
                font-size: 0.875rem !important;
            }

            /* Subjudul halaman TETAP tampil di mobile (sebelumnya di-hidden sehingga
               halaman tampak kosong). Hanya dikecilkan agar tidak memicu wrap. */
            .header-main-subtitle {
                display: block !important;
                font-size: 0.7rem !important;
                line-height: 1.25 !important;
                margin-top: 1px !important;
                white-space: nowrap;
            }

            .app-header-left {
                align-items: center !important;
                gap: 0.25rem !important;
            }

            .mobile-top-hamburger {
                align-self: center !important;
                margin-block: 0 !important;
            }

            .card-body,
            .card > .p-4,
            .card > [class~="p-3.5"] {
                padding: 0.75rem !important;
            }

            .btn {
                padding: 0.4rem 0.65rem !important;
                font-size: 0.85rem !important;
            }

            .btn-solid-pill,
            .btn-row-action {
                min-height: 34px !important;
                height: 34px !important;
                padding: 0 0.7rem !important;
                font-size: 0.8rem !important;
            }

            .mobile-nav-icon {
                font-size: 1.1rem !important;
            }

            .mobile-nav-label {
                font-size: 10px !important;
            }

            .mobile-top-hamburger i {
                font-size: 1.25rem !important;
            }

            /* Catatan: jarak vertikal antar menu sidebar TIDAK diatur di sini,
               melainkan pada satu aturan global `.app-sidebar-drawer .nav-link`
               di bagian bawah stylesheet agar mobile & desktop identik (16px). */
            .app-sidebar-drawer .nav-link {
                font-size: 0.8rem !important;
            }

            .app-sidebar-drawer .nav-link i {
                font-size: 1.1rem !important;
            }

            .btn i,
            button i {
                font-size: 0.9em !important;
            }
        }

        /* --------------------------------------------------------------------------
           3.B LAPISAN MOBILE: SAFE-AREA 16px, CLEAN LOOK & PERFORMA RENDER
           Aktif HANYA di < 768px — tampilan desktop tetap 100% identik
           -------------------------------------------------------------------------- */
        @media (max-width: 767.98px) {
            /* Cegah auto-zoom iOS saat fokus input (font < 16px memicu zoom Safari) */
            .form-control,
            .form-select {
                font-size: 16px !important;
                min-height: 40px !important;
            }

            /* Touch target minimal 40px (Apple HIG / Material) + respons tap instan */
            .btn {
                min-height: 40px !important;
                -webkit-tap-highlight-color: transparent !important;
                touch-action: manipulation !important;
            }

            /* Pengecualian tombol aksi dalam tabel: kompak namun tetap mudah di-tap */
            .btn-row-action,
            .crud-center-wrapper .btn {
                min-height: 34px !important;
            }

            .pagination-compact .page-link {
                min-height: 36px !important;
                min-width: 36px !important;
            }

            /* Safety belt: tombol Hapus selalu tampil paling akhir di baris aksi */
            .crud-center-wrapper .action-delete,
            .crud-center-wrapper .btn-danger {
                order: 99;
            }

            /* Clean look: redam bayangan berat & efek lift (hemat GPU, anti-jank) */
            .card,
            .stat-card-modern,
            .table-custom-card,
            .modal-content {
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06) !important;
            }
            .stat-card-modern:hover,
            .shortcut-card-interactive:hover,
            .operasional-card-interactive:hover,
            .btn-modern-smooth:hover {
                transform: none !important;
            }

            /* Ikon kartu statistik ikut diskalakan agar rapi di layar kecil */
            .stat-card-modern > i {
                font-size: 1.45rem !important;
            }

            /* Scroll horizontal tabel: momentum halus tanpa memantul ke halaman */
            .table-responsive {
                overscroll-behavior-x: contain !important;
                -webkit-overflow-scrolling: touch !important;
                border-radius: 0 !important;
            }

            /* Normalisasi container/card di mobile:
               - kartu tidak lagi "melebar" melebihi layar
               - garis batas table/card konsisten (tidak ada tepi yang menggantung) */
            .card,
            .table-custom-card,
            .stat-card-modern,
            .stat-card-polished,
            .filter-card,
            .modal-content {
                max-width: 100% !important;
                border-radius: 12px !important;
            }

            .card > .card-body,
            .card > .p-3,
            .card > .p-4,
            .card > [class~="p-3.5"] {
                min-width: 0 !important;
            }

            /* Cegah isi panjang (nama siswa, tabel, kode) memaksa card melebar */
            .card,
            .card-body,
            .table-responsive > .table {
                overflow-wrap: break-word !important;
                word-break: break-word !important;
            }

            /* Cegah font-inflation saat rotasi layar */
            html {
                -webkit-text-size-adjust: 100% !important;
                text-size-adjust: 100% !important;
            }
        }

        @media (min-width: 768px) {
            .content-scroll-wrapper {
                padding: var(--sidebar-gap, 10px) !important;
                background-color: #3b62f6 !important;
            }

            main,
            .content-scroll-wrapper > main,
            main.w-full {
                min-height: calc(100vh - (var(--sidebar-gap, 10px) * 2)) !important;
                min-height: calc(100dvh - (var(--sidebar-gap, 10px) * 2)) !important;
                width: 100% !important;
                background-color: #ffffff !important;
                border-radius: 1rem !important;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
                margin: 0 !important;
            }

            @media (max-width: 1023.98px) {
                main,
                .content-scroll-wrapper > main,
                main.w-full {
                    padding-bottom: calc(var(--bottom-nav-height) + 2.5rem + env(safe-area-inset-bottom, 0px)) !important;
                }
            }
        }

        /* --------------------------------------------------------------------------
           4. TOMBOL GARIS TIGA & UNIVERSAL HEADER (SEJAJAR VERTIKAL PRESISI)
           -------------------------------------------------------------------------- */
        .mobile-top-hamburger {
            display: none !important;
        }

        @media (max-width: 1023.98px) {
            .mobile-top-hamburger {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                background: transparent !important;
                background-color: transparent !important;
                border: none !important;
                outline: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #0f172a !important;
                cursor: pointer !important;
                touch-action: manipulation !important;
                -webkit-tap-highlight-color: transparent !important;
                transition: color 0.15s ease, transform 0.12s cubic-bezier(0.32, 0.72, 0, 1) !important;
                flex: 0 0 40px !important;
                width: 40px !important;
                min-width: 40px !important;
                max-width: 40px !important;
                height: 40px !important;
                min-height: 40px !important;
                max-height: 40px !important;
                line-height: 1 !important;
            }

            .mobile-top-hamburger:hover {
                color: #2563eb !important;
                background: transparent !important;
            }

            .mobile-top-hamburger:active {
                transform: scale(0.92) !important;
                color: #1d4ed8 !important;
                background: transparent !important;
            }

            .mobile-top-hamburger i {
                font-size: 1.5rem !important;
                line-height: 1 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
        }

        /* -------------------------------------------------------------------------- 
           4.B TOMBOL GARIS TIGA DI DESKTOP (>= 1024px) - REUSE TOMBOL YANG SAMA

           Tombol ini adalah elemen yang PERSIS SAMA dengan tombol mobile
           (class .mobile-top-hamburger + ikon <i class='bx bx-menu'>), bukan
           tombol tambahan. only perilaku yang berbeda:
             - < 1024px : buka/tutup DRAWER (persis seperti sebelumnya)
             - >= 1024px: toggle COLLAPSE sidebar (state di localStorage)

           Semua ukuran, ketebalan garis, warna, dan jarak ke teks sengaja
           menyalin nilai dari blok mobile di atas supaya visualnya identik.
           PURE ICON: tanpa background, border, shadow, maupun kotak melingkar
           pada keadaan normal, hover, maupun focus.
           -------------------------------------------------------------------------- */
        @media (min-width: 1024px) {
            .mobile-top-hamburger {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                /* PURE ICON - semua lapisan visual dihapus */
                background: transparent !important;
                background-color: transparent !important;
                border: none !important;
                outline: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #0f172a !important;
                cursor: pointer !important;
                touch-action: manipulation !important;
                -webkit-tap-highlight-color: transparent !important;
                transition: color 0.15s ease, transform 0.12s cubic-bezier(0.32, 0.72, 0, 1) !important;
                /* 40x40 & flex:0 0 40px mengunci lebar tombol, sehingga posisi
                   hamburger TIDAK bergeser meski judul halaman tiap halaman
                   berbeda panjang. */
                flex: 0 0 40px !important;
                width: 40px !important;
                min-width: 40px !important;
                max-width: 40px !important;
                height: 40px !important;
                min-height: 40px !important;
                max-height: 40px !important;
                line-height: 1 !important;
                align-self: center !important;
            }

            /* Hover hanya mengubah warna ikon - tidak ada background/border. */
            .mobile-top-hamburger:hover {
                color: #2563eb !important;
                background: transparent !important;
            }

            .mobile-top-hamburger:active {
                transform: scale(0.92) !important;
                color: #1d4ed8 !important;
            }

            /* Ukuran ikon identik dengan versi mobile. */
            .mobile-top-hamburger i {
                font-size: 1.5rem !important;
                line-height: 1 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            /* Fokus keyboard: outline TIPIS pada IKON SAJA, container bersih. */
            .mobile-top-hamburger:focus {
                outline: none !important;
                background: transparent !important;
            }

            .mobile-top-hamburger:focus-visible {
                outline: none !important;
                background: transparent !important;
            }

            .mobile-top-hamburger:focus-visible i {
                outline: 2px solid #2563eb !important;
                outline-offset: 2px !important;
                border-radius: 3px !important;
            }
        }

        /* Header UTAMA: melekat permanen di puncak scroll container.
           - position:sticky + top:0  -> tidak pernah ikut ter-scroll atau menyusut
           - flex-shrink:0             -> terkunci pada tinggi normal walau konten panjang
           - padding-top aman-area     -> notch/status bar tidak menutupi isi header
           - background solid          -> konten di bawah tidak "tembus" saat digulir */
        .app-header-bar {
            width: 100% !important;
            margin-bottom: 0.875rem !important;
            flex-shrink: 0 !important;
        }

        @media (max-width: 1023.98px) {
            .app-header-bar {
                position: sticky !important;
                top: 0 !important;
                z-index: 50 !important;
                flex-shrink: 0 !important;
                margin-top: 0 !important;
                margin-bottom: 0.625rem !important;
                /* 1.25rem + safe-area: ukuran pas untuk ikon hamburger & judul halaman,
                   tidak mepet ke tepi atas layar HP (notch/status bar tetap aman). */
                padding-top: calc(1.25rem + env(safe-area-inset-top, 0px)) !important;
                padding-bottom: 0.5rem !important;
                background-color: #ffffff !important;
                box-shadow: 0 1px 0 rgba(15, 23, 42, 0.06) !important;
            }

            /* Kreserve tinggi header melekat agar konten pertama tidak tertutup */
            .content-scroll-wrapper > main > .flex-1:first-of-type {
                min-width: 0;
            }
        }

        @media (min-width: 640px) {
            .app-header-bar {
                margin-bottom: 1rem !important;
            }
        }

        .app-header-left {
            display: flex !important;
            align-items: center !important;
            min-width: 0 !important;
        }

        @media (max-width: 767.98px) {
            .app-header-left {
                width: 100% !important;
            }
        }

        @media (min-width: 768px) {
            .app-header-left {
                width: auto !important;
                max-width: 65% !important;
            }
            .app-header-right {
                margin-left: auto !important;
                flex-shrink: 0 !important;
            }
        }

        .header-text-block {
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-start !important;
            min-width: 0 !important;
            flex: 1 !important;
            margin: 0 !important;
            padding: 0 !important;
            padding-top: 2px !important;
        }

        .header-main-title {
            color: #0f172a !important;
            letter-spacing: -0.02em !important;
            font-size: 0.85rem !important;
            font-weight: 700 !important;
            line-height: 1.25 !important;
            margin: 0 !important;
            padding: 0 !important;
            text-transform: uppercase;
        }

        @media (min-width: 640px) {
            .header-main-title {
                font-size: 1.18rem !important;
            }
        }

        @media (min-width: 1024px) {
            .header-main-title {
                font-size: 1.25rem !important; /* Proporsional text-xl */
            }
        }

        .header-main-subtitle {
            color: #64748b !important;
            font-size: 0.65rem !important;
            font-weight: 600 !important;
            line-height: 1.3 !important;
            margin: 0 !important;
            margin-top: 2px !important;
            padding: 0 !important;
            letter-spacing: 0.02em;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 100% !important;
            display: block !important;
        }

        @media (min-width: 640px) {
            .header-main-subtitle {
                font-size: 0.78rem !important;
            }
        }

        /* --------------------------------------------------------------------------
           5. STANDAR KOMPONEN & UI DESAIN SISTEM
           -------------------------------------------------------------------------- */
        .rounded-pill,
        .rounded-full,
        [class*="rounded-pill"],
        [class*="rounded-full"],
        .filter-pill,
        .status-badge-pill,
        .badge {
            border-radius: 10px !important;
        }

        /* Catatan: .table-responsive sengaja TIDAK diberi border-radius di sini.
           Wadah tabel sudah dibulatkan oleh card induk (.rounded-4 + .overflow-hidden).
           Kalau ikut dibulatkan, sudut header tabel tampak "terpotong"/meluber. */
        .card,
        .modal-content,
        .btn,
        button,
        .form-control,
        .form-select,
        .input-group-text,
        .stat-card-polished,
        .alert {
            border-radius: 12px !important;
        }

        /* .no-scrollbar dipakai SENGaja pada elemen yang memang tidak boleh
           menampilkan scrollbar (chip filter horizontal di Presensi Hari Ini).
           Setiap tabel data memakai .table-responsive, dan untuk TABEL scrollbar
           justru dibuat TERLIHAT lewat blok "TABEL DATA: SCROLL..." di bawah.
          (rule ini HANYA untuk elemen .no-scrollbar, bukan tabel). -->
        .no-scrollbar {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        .search-box-wrap {
            position: relative !important;
            display: block !important;
            height: 38px !important;
        }

        .search-box-wrap > .form-control {
            width: 100% !important;
            height: 38px !important;
            padding-right: 2.75rem !important;
            min-width: 0 !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            letter-spacing: 0.02em;
            font-family: 'Poppins', 'Roboto', sans-serif;
            font-size: 0.82rem !important;
            font-weight: 400 !important;
        }

        .search-box-wrap > .btn {
            position: absolute !important;
            top: 0 !important;
            right: 0 !important;
            width: 38px !important;
            height: 38px !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 6px !important;
            background: transparent !important;
            box-shadow: none !important;
            color: #64748b !important;
            z-index: 10 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }

        .search-box-wrap > .btn:hover,
        .search-box-wrap > .btn:focus-visible,
        .search-box-wrap > .btn:active {
            background: transparent !important;
            color: #2563eb !important;
            box-shadow: none !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        @media (min-width: 1280px) {
            .action-bar-section {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) auto !important;
                align-items: center !important;
                gap: 0.75rem !important;
            }

            .action-bar-section > form {
                display: flex !important;
                flex: initial !important;
                flex-direction: row !important;
                align-items: center !important;
                width: 100% !important;
                min-width: 0 !important;
                gap: 0.5rem !important;
            }

            .action-bar-section > form .search-box-wrap {
                flex: 1 1 0% !important;
                width: auto !important;
                min-width: 220px !important;
                max-width: none !important;
            }

            .action-bar-section > form .filter-box-wrap {
                flex: 0 1 36% !important;
                width: auto !important;
                min-width: 160px !important;
                max-width: none !important;
            }

            .action-bar-section > form > .w-md-auto {
                flex: 0 0 auto !important;
                width: auto !important;
            }

            .action-bar-section > .d-flex {
                flex: 0 0 auto !important;
                width: auto !important;
                margin-left: 0 !important;
                justify-content: flex-end !important;
            }
        }

        .nav-link {
            display: flex;
            align-items: center;
            color: #ffffff;
            border-radius: 8px !important;
            text-decoration: none;
            font-weight: 500 !important;
            font-size: 0.83rem;
            line-height: 1.3;
            transition: background-color 0.2s ease, color 0.2s ease;
            position: relative;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-radius: 8px !important;
        }

        .nav-link.active {
            background-color: #ffffff !important;
            color: #3b62f6 !important;
            font-weight: 700 !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08) !important;
        }

        .nav-link.active i {
            color: #3b62f6 !important;
            font-weight: 700;
        }

        /* --------------------------------------------------------------------------
           JARAK VERTIKAL ANTAR ITEM MENU SIDEBAR = 16px (SATU aturan untuk SEMUA
           breakpoint: drawer mobile < 1024px & sidebar statis desktop >= 1024px).
           Sebelumnya hanya mobile yang punya margin-bottom (12px) sedangkan desktop 0
           sehingga ritme menunya beda. Aturan ini sengaja diletakkan SETELAH blok
           @media (max-width: 767.98px) supaya menang atas margin inline sidebar
           (`style="margin: 0 0.75rem"` pada partial sidebar.blade.php).
           -------------------------------------------------------------------------- */
        .app-sidebar-drawer .nav-link {
            margin: 0 0.75rem 16px 0.75rem !important;
            /* Pengaman box model: padding TIDAK menambah lebar di luar margin,
               sehingga tidak ada elemen yang bisa melewati batas sidebar. */
            box-sizing: border-box !important;
        }

        /* ==========================================================================
           MODE COLLAPSED - DESKTOP SAJA (>= 1024px)

           Seluruh blok ini dikunci di media query min-width: 1024px dengan begitu
           tampilan MOBILE (< 1024px) tidak tersentuh sama sekali - drawer, margin
           0.75rem, dan lebar 238px tetap persis seperti sebelumnya.

           State berasal dari class `sidebar-collapsed` pada elemen <html>, yang
           sudah dipasang oleh script anti-kedip di <head> sebelum render pertama.
           ========================================================================== */
        @media (min-width: 1024px) {

            /* --- 1. LEBAR SIDEBAR ------------------------------------------------ */
            html.sidebar-collapsed .app-sidebar-drawer {
                width: var(--sidebar-width-collapsed) !important;
                min-width: var(--sidebar-width-collapsed) !important;
                max-width: var(--sidebar-width-collapsed) !important;
            }

            /* Transisi halus pada lebar sidebar (200-300ms). Hanya width yang
               beranimasi supaya konten utama ikut melebar/mempempit tanpa
               layout melompat. */
            html .app-sidebar-drawer {
                transition: width 0.25s ease, min-width 0.25s ease, max-width 0.25s ease !important;
                overflow-x: hidden !important;
            }

            /* --- 2. ATAS SIDEBAR: HANYA LOGO, DIPUSATKAN ----------------------- */
            /* Padding SAMA PERSIS dengan mode expanded (lihat blok "BLOK ATAS
               SIDEBAR" di bawah). Expanded & collapsed harus memakai nilai
               yang sama supaya posisi vertikal logo TIDAK lompat ketika
               sidebar di-collapse / di-expand. */
            html.sidebar-collapsed .sidebar-brand {
                padding: 1rem 0.75rem 1rem 0.75rem !important;
            }

            html.sidebar-collapsed .sidebar-brand > div {
                justify-content: center !important;
                align-items: center !important;
                min-height: 46px !important;
                border-bottom: none !important;
                padding-bottom: 0 !important;
                width: 100% !important;
            }

            /* Nama sekolah disembunyikan - bagian atas hanya berisi logo. */
            html.sidebar-collapsed .sidebar-brand-text {
                display: none !important;
            }

            /* Logo versi kecil & dipusatkan horizontal. */
            html.sidebar-collapsed .sidebar-brand-logo {
                width: 40px !important;
                height: 40px !important;
                margin: 0 auto !important;
                flex-shrink: 0 !important;
            }

            /* --- 3. ITEM MENU: HANYA IKON, DIPUSATKAN ------------------------- */
            html.sidebar-collapsed .app-sidebar-drawer .nav-link {
                /* Margin diperkecil & dipusatkan: semua item berukuran sama
                   sehingga semua ikon berada di sumbu tengah sidebar. */
                margin: 0 auto 16px auto !important;
                width: 48px !important;
                min-width: 48px !important;
                max-width: 48px !important;
                height: 44px !important;
                padding: 0 !important;
                justify-content: center !important;
                gap: 0 !important;
                box-sizing: border-box !important;
                overflow: hidden !important;
            }

            /* Label teks disembunyikan; ikon tetap dengan ukuran & warna sama. */
            html.sidebar-collapsed .sidebar-nav-label {
                display: none !important;
            }

            /* Garis pembatas ikut memendek & tetap terpusat. */
            html.sidebar-collapsed .app-sidebar-drawer .nav-item:has(> .border-bottom) {
                padding-left: 12px !important;
                padding-right: 12px !important;
            }

            /* --- 4. HIGHLIGHT MENU AKTIF ---------------------------------------- */
            /* Kotak rounded di sekitar ikon, ukuran PERSIS sama untuk semua item
               karena .nav-link collapsed dikunci ke 48x44px di atas. */
            html.sidebar-collapsed .app-sidebar-drawer .nav-link.active {
                border-radius: 12px !important;
                box-shadow: none !important;
            }

            html.sidebar-collapsed .app-sidebar-drawer .nav-link i {
                /* Ikon sedikit diperbesar agar tetap terbaca di sidebar sempit,
                   namun tetap satu visual dengan versi expanded. */
                font-size: 1.35rem !important;
                margin: 0 auto !important;
            }

            /* --- 5. TOOLTIP NAMA MENU (collapsed) ------------------------------- */
            /* Tooltip memakai position: FIXED, bukan absolute. Alasannya: <aside>
               memakai overflow:hidden, sehingga tooltip yang diposisikan relatif
               terhadap ikon akan TERPOTONG tepi sidebar. Dengan position:fixed
               plus koordinat yang dihitung JS dari boundingClientRect, tooltip
               selalu tampil utuh di luar sidebar. */
            html.sidebar-collapsed .app-sidebar-drawer .nav-link[data-label]::after {
                content: attr(data-label);
                position: fixed;
                /* Diisi oleh JS lewat custom property (lihat initTooltipPositioning).
                   Nilai awal disembunyikan di luar layar agar tidak berkedip
                   sebelum JS sempat menghitung koordinat. */
                left: var(--tt-left, -9999px);
                top: var(--tt-top, -9999px);
                transform: translateY(-50%);
                /* Di atas sidebar (z-index 40 desktop / 1045 drawer mobile),
                   tapi DI BAWAH modal Bootstrap (1055) supaya tooltip tidak
                   pernah muncul di atas jendela konfirmasi Log Out. */
                z-index: 1046;
                padding: 0.4rem 0.7rem;
                border-radius: 8px;
                background: #0f172a;
                color: #ffffff;
                font-size: 0.72rem;
                font-weight: 600;
                line-height: 1.2;
                letter-spacing: 0.03em;
                text-transform: uppercase;
                white-space: nowrap;
                pointer-events: none;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.15s ease, visibility 0.15s ease;
                box-shadow: 0 6px 18px -6px rgba(15, 23, 42, 0.55);
            }

            html.sidebar-collapsed .app-sidebar-drawer .nav-link[data-label]:hover::after,
            html.sidebar-collapsed .app-sidebar-drawer .nav-link[data-label]:focus-visible::after {
                opacity: 1;
                visibility: visible;
            }

            /* Fokus keyboard: outline tipis pada ikon saja. */
            html.sidebar-collapsed .app-sidebar-drawer .nav-link:focus-visible {
                outline: 2px solid #ffffff !important;
                outline-offset: -2px !important;
            }
            html.sidebar-collapsed .app-sidebar-drawer .nav-link:focus-visible i {
                outline: none !important;
            }

            /* --- 6. TOMBOL LOG OUT SAAT COLLAPSED ------------------------------ */
            /* Tombol ikon saja: ukuran & lebar SAMA dengan highlight menu aktif
               (48x44px), tetap merah, dan tetap memicu modal konfirmasi yang
               sudah ada karena atribut data-bs-* tidak diubah. */
            html.sidebar-collapsed .app-sidebar-drawer .sidebar-logout-link {
                width: 48px !important;
                min-width: 48px !important;
                max-width: 48px !important;
                height: 44px !important;
                margin: 0 auto 16px auto !important;
                padding: 0 !important;
                justify-content: center !important;
                gap: 0 !important;
                border-radius: 12px !important;
                box-sizing: border-box !important;
                color: #fecaca !important;
            }

            html.sidebar-collapsed .app-sidebar-drawer .sidebar-logout-link i {
                font-size: 1.35rem !important;
                color: #fecaca !important;
            }

            html.sidebar-collapsed .app-sidebar-drawer .sidebar-logout-link:hover,
            html.sidebar-collapsed .app-sidebar-drawer .sidebar-logout-link:focus-visible {
                background-color: #dc2626 !important;
                color: #ffffff !important;
                outline: none !important;
            }

            html.sidebar-collapsed .app-sidebar-drawer .sidebar-logout-link:hover i,
            html.sidebar-collapsed .app-sidebar-drawer .sidebar-logout-link:focus-visible i {
                color: #ffffff !important;
            }

            /* Toast native browser (title="Log Out") sengaja tetap ada sebagai
               cadangan saat collapsed. */
        }

        /* --------------------------------------------------------------------------

        .sidebar-brand {
            padding: 1.25rem 1rem 0.75rem 1rem !important;
            flex-shrink: 0 !important;
        }

        /* Panel sidebar (elemen <aside> dari partial) */
        .app-sidebar-panel {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            height: 100% !important;
            max-height: 100% !important;
            min-height: 0 !important;
            background-color: #3b62f6 !important;
        }

        @media (max-width: 1023.98px) {
            /* Ramping sedikit di layar kecil agar konten tetap punya ruang napas */
            .sidebar-brand {
                padding: 1rem 1rem 0.75rem 1rem !important;
            }
        }

        /* --------------------------------------------------------------------------
           BLOK ATAS SIDEBAR (LOGO + NAMA SEKOLAH) - HANYA DESKTOP (>= 1024px)

           SEMUA aturan di bawah ini sengaja dikunci di min-width: 1024px. Nilai
           mobile (1rem 1rem 0.75rem 1rem di blok media query di atas) maupun
           utility class pada markup TIDAK DISENTUH, sehingga tampilan mobile
           benar-benar tidak berubah.

           Driver masalah "logo mepet ke pojok kiri-atas":
             - padding kiri blok brand (1rem = 16px) TIDAK sama dengan margin kiri
               item menu (0.75rem = 12px)  -> logo tidak sejajar dengan kartu menu;
             - garis pembatas brand ikut lebih pendek dari garis pembatas di atas
               tombol Log Out;
             - padding atas (1.25rem) dan bawah (0.75rem) tidak seimbang, dan
               utility pb-3 pada baris logo bikin jarak ke menu pertama berlebihan.

           Perbaikan:
             - padding kiri = margin kiri item menu (0.75rem)  -> logo sejajar
               dengan sisi kiri kartu menu, dan garis pembatas brand lebarnya
               sama persis dengan garis pembatas di atas tombol Log Out;
             - padding atas = bawah (1rem) -> tidak menempel tepi atas dan tidak
               ada ruang kosong berlebih sebelum menu pertama;
             - min-height yang sama (46px) dipakai expanded & collapsed -> tinggi
               blok header sidebar konsisten, logo tidak lompat vertikal.
           -------------------------------------------------------------------------- */
        @media (min-width: 1024px) {
            .sidebar-brand {
                padding: 1rem 0.75rem 1rem 0.75rem !important;
            }

            /* Baris logo + nama sekolah: tinggi pas, logo & teks sejajar vertikal.
               pb-3 (1rem) dihapus di sini supaya jarak ke garis pembatas dan ke
               menu pertama tidak dobel. */
            .sidebar-brand > .sidebar-brand-row {
                align-items: center !important;
                justify-content: flex-start !important;
                min-height: 46px !important;
                width: 100% !important;
                padding-bottom: 0 !important;
            }

            /* Nama sekolah boleh turun ke baris berikutnya, tidak terpotong dan
               tidak keluar dari batas sidebar. */
            .sidebar-brand-text {
                min-width: 0 !important;
                white-space: normal !important;
                overflow-wrap: break-word !important;
                overflow: hidden !important;
            }
        }

        /* ==========================================================================
           TABEL DATA: SCROLL VERTIKAL DI SISI KANAN + HEADER STICKY
           SATU-SATUNYA sumber gaya ini (letak di layout bersama), dipakai SEMUA
           halaman yang tabelnya dibungkus .table-responsive:
           Presensi Hari Ini, Presensi Kelas, Kehadiran, Rekap, Siswa, Guru,
           Kelas, Tahun Ajaran, Hari Libur, Arsip, Peran, dsb.
           Cara ini dipakai agar tidak ada copy-paste CSS per halaman.

           Catatan: .table-responsive memakai max-height (bukan tinggi tetap),
           sehingga tabel yang isinya sedikit (mis. 3 baris di modal) tetap
           tampil utuh tanpa memunculkan scrollbar yang tidak perlu.
           ========================================================================== */
        .table-responsive {
            /* Area scroll tabel: tinggi maksimal + scrollbar di sisi kanan */
            overflow-y: auto !important;
            overflow-x: auto !important;
            max-height: 65vh !important;
            /* Izinkan tabel lebar bergeser ke samping tanpa merusak layout. */
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior: contain !important;
        }

        /* Header tabel menempel di atas area scroll.
           Sticky dipasang pada <thead> (bukan <th>) supaya warna background
           milik setiap halaman (bg-light / bg-slate-50) tetap utuh - tidak ada
           warna, font, atau gaya header yang berubah. */
        .table-responsive > table > thead {
            position: sticky !important;
            top: 0 !important;
            z-index: 2 !important;
        }

        /* <thead> tanpa class background (3 tabel) diberi warna netral yang
           sama dengan .bg-light Bootstrap, agar isi tabel tidak terlihat
           menembus header saat digulir. */
        .table-responsive > table > thead:not([class]) {
            background-color: #f8f9fa !important;
        }

        /* Scrollbar yang SELALU terlihat (bukan overlay yang menghilang).
           Warna memakai warna teks abu-abu yang sudah dipakai di tabel. */
        .table-responsive {
            scrollbar-width: auto !important;
            -ms-overflow-style: scrollbar !important;
        }

        .table-responsive::-webkit-scrollbar {
            width: 10px !important;
            height: 10px !important;
            display: block !important;
            background-color: #f1f5f9;
            border-radius: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background-color: #f1f5f9;
            border-radius: 8px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background-color: #94a3b8;
            border-radius: 8px;
            border: 2px solid #f1f5f9;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background-color: #64748b;
        }

        /* ==========================================================================
           PAGINASI TEKS MURNI BERSAMA (Single Source of Truth)
           Susunan "‹ Sebelumnya  1 2 3 4 5  Berikutnya ›" + info "Menampilkan ...".
           Tanpa container/border/shadow/pill; halaman aktif biru tebal bergaris
           bawah tipis; "Sebelumnya/Berikutnya" redup di ujung.

           Blok ini adalah SUMBER BERSAMA untuk halaman yang paginasinya berada
           DI DALAM area scroll tabel: Catatan Kehadiran, Rekap, dan Data Siswa.
           Catatan Kehadiran & Rekap masih membawa salinan nilai yang sama di
           @push('styles') masing-masing (keduanya identik, jadi tidak ada gaya
           yang berubah); Data Siswa memakai blok bersama ini tanpa salinan.
           ========================================================================== */
        .kehadiran-pagination {
            /* Berada DI DALAM area scroll: padding atas/bawah lega supaya tidak
               menempel baris terakhir dan tidak terpotong tepi bawah layar. */
            padding: 1.25rem 0.5rem 1.5rem;
            margin: 0;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            background-color: #ffffff;
            width: 100%;
            box-sizing: border-box;
        }

        .kehadiran-pagination-list {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.15rem 1.1rem;
            list-style: none;
            margin: 0 0 0.35rem;
            padding: 0;
        }

        .kehadiran-pagination-step,
        .kehadiran-pagination-page {
            display: inline-block;
            padding: 0.45rem 0.3rem;
            font-size: 0.95rem;
            font-weight: 400;
            line-height: 1.2;
            text-decoration: none;
            background: none !important;
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            color: #64748b;
            cursor: pointer;
        }

        /* Halaman aktif: biru tema, tebal, garis bawah tipis. */
        .kehadiran-pagination-page.is-active {
            color: #2563eb !important;
            font-weight: 700;
            border-bottom: 2px solid #2563eb !important;
            cursor: default;
        }

        /* Hover: hanya warna teks, tanpa bentuk muncul. */
        .kehadiran-pagination-step:hover,
        .kehadiran-pagination-page:hover {
            color: #2563eb !important;
            text-decoration: underline;
        }

        /* Fokus keyboard: outline tipis pada TEKS saja. */
        .kehadiran-pagination-step:focus-visible,
        .kehadiran-pagination-page:focus-visible {
            outline: 2px solid #2563eb !important;
            outline-offset: 1px;
            border-radius: 0 !important;
        }

        /* Redup + tidak bisa diklik di halaman pertama / terakhir. */
        .kehadiran-pagination-step.is-disabled {
            color: #cbd5e1;
            cursor: not-allowed;
            pointer-events: none;
        }

        .kehadiran-pagination-step.is-disabled:hover {
            color: #cbd5e1;
            text-decoration: none;
        }

        .kehadiran-pagination-ellipsis {
            display: inline-block;
            padding: 0.45rem 0.1rem;
            font-size: 0.95rem;
            color: #94a3b8;
            line-height: 1.2;
        }

        .kehadiran-pagination-info {
            margin: 0;
            font-size: 0.85rem;
            color: #64748b;
            letter-spacing: 0.02em;
        }

        /* --------------------------------------------------------------------------
           SIDEBAR DRAWER: SCROLL AREA + LOGOUT (PERBAIKAN BENTUK & POSISI)
           min-height:0 WAJIB ada. Tanpa itu, nilai default min-height:auto pada flex item
           membuat area menu memanjang melebihi tinggi drawer, sehingga footer Logout
           terdorong keluar & ter-clip (tidak terlihat / tidak bisa diketuk).
           -------------------------------------------------------------------------- */

        /* Wrapper utama sidebar: flexbox vertikal dengan tinggi dinamis aman untuk mobile */
        .main-sidebar,
        .sidebar-container {
            display: flex !important;
            flex-direction: column !important;
            height: 100vh !important;
            height: 100dvh !important;
            max-height: 100vh !important;
            max-height: 100dvh !important;
            overflow: hidden !important;
        }

        /* Area menu scrollable: flex 1 1 auto dengan padding bawah untuk ruang napas */
        .sidebar-menu-wrapper,
        .menu-container,
        .sidebar-menu-scroll {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            max-height: 100%;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-y: contain;
            padding-bottom: 20px !important;
        }

        .sidebar-menu-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-menu-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 4px;
        }

        /* Tombol Logout di dalam menu list: styling seragam dengan nav-link lainnya.
           Tidak menggunakan position absolute/fixed agar ikut ter-scroll natural di HP Android. */
        .sidebar-logout-btn {
            display: flex !important;
            align-items: center !important;
            background: transparent !important;
            border: none !important;
            text-align: left !important;
            cursor: pointer !important;
            font-family: 'Poppins', 'Roboto', sans-serif !important;
            font-weight: 500 !important;
            font-size: 0.83rem !important;
            line-height: 1.3 !important;
            color: #fee2e2 !important;
            -webkit-tap-highlight-color: transparent !important;
            touch-action: manipulation !important;
            transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, transform 0.12s ease !important;
        }

        .sidebar-logout-btn i {
            font-size: 1.18rem !important;
            color: #fee2e2 !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
            transition: color 0.2s ease !important;
        }

        .sidebar-logout-btn:hover,
        .sidebar-logout-btn:focus-visible {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35) !important;
            outline: none !important;
        }

        .sidebar-logout-btn:hover i,
        .sidebar-logout-btn:focus-visible i {
            color: #ffffff !important;
        }

        .sidebar-logout-btn:active {
            background-color: #b91c1c !important;
            transform: scale(0.98) !important;
        }

        /* Tombol Log Out: kapsul melengkung penuh, target sentuh nyaman, hover/active
           murni CSS (tanpa onmouseover) supaya tidak "nyangkut" di perangkat sentuh */
        .sidebar-logout-btn {
            display: flex !important;
            align-items: center !important;
            gap: 0.7rem !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0.6rem 1rem !important;
            border: none !important;
            border-radius: 8px !important;
            background-color: transparent !important;
            color: #fee2e2 !important;
            font-weight: 500 !important;
            font-size: 0.83rem !important;
            line-height: 1.3 !important;
            text-align: left !important;
            text-decoration: none !important;
            cursor: pointer !important;
            -webkit-tap-highlight-color: transparent !important;
            touch-action: manipulation !important;
            transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, transform 0.12s ease !important;
        }

        .sidebar-logout-btn i {
            font-size: 1.18rem !important;
            color: #fee2e2 !important;
            line-height: 1 !important;
            flex-shrink: 0 !important;
            transition: color 0.2s ease !important;
        }

        .sidebar-logout-btn:hover,
        .sidebar-logout-btn:focus-visible {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35) !important;
            outline: none !important;
        }

        .sidebar-logout-btn:hover i,
        .sidebar-logout-btn:focus-visible i {
            color: #ffffff !important;
        }

        .sidebar-logout-btn:active {
            background-color: #b91c1c !important;
            transform: scale(0.98) !important;
        }

        /* --------------------------------------------------------------------------
           STANDARISASI UNIVERSAL MODAL KONFIRMASI HAPUS (PREMIUM SOFT UI)
           -------------------------------------------------------------------------- */
        .swal2-popup.swal2-modal-soft {
            border-radius: 1.25rem !important;
            padding: 2.25rem 2rem 1.75rem !important;
            max-width: 440px !important;
            width: 90% !important;
            background: #F8F3F0 !important;
            border: 0 !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
        }
        .swal2-popup.swal2-modal-soft .swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
        }
        .swal2-popup.swal2-modal-soft .swal2-actions {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            display: flex !important;
            justify-content: center !important;
            gap: 0.75rem !important;
        }
        .swal2-popup.swal2-modal-soft .swal2-actions .btn {
            flex: 1 1 0 !important;
            min-height: 44px !important;
            padding: 0.6rem 1.5rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.85rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            border-radius: 0.75rem !important;
            margin: 0 !important;
            line-height: 1.25 !important;
            transition: all 0.2s ease !important;
        }
        .swal2-popup.swal2-modal-soft .swal2-actions .btn-danger {
            background-color: #dc2626 !important;
            border: 1px solid #dc2626 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.25) !important;
        }
        .swal2-popup.swal2-modal-soft .swal2-actions .btn-danger:hover {
            background-color: #b91c1c !important;
            border-color: #b91c1c !important;
            transform: translateY(-1px) !important;
        }
        .swal2-popup.swal2-modal-soft .swal2-actions .btn-light {
            background-color: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
            color: #475569 !important;
        }
        .swal2-popup.swal2-modal-soft .swal2-actions .btn-light:hover {
            background-color: #e2e8f0 !important;
            color: #1e293b !important;
        }
        .bg-danger-subtle {
            background-color: #fee2e2 !important;
            color: #dc2626 !important;
        }
        @media (max-width: 575.98px) {
            .swal2-popup.swal2-modal-soft {
                padding: 1.75rem 1.25rem 1.25rem !important;
                width: 92% !important;
            }
            .swal2-popup.swal2-modal-soft .swal2-actions {
                gap: 0.5rem !important;
            }
            .swal2-popup.swal2-modal-soft .swal2-actions .btn {
                min-height: 40px !important;
                font-size: 0.85rem !important;
                padding: 0.5rem 0.75rem !important;
            }
        }

        /* --------------------------------------------------------------------------
           STANDARISASI TOMBOL AKSI TABEL (DATA SISWA, GURU, KELAS, DLL)
           -------------------------------------------------------------------------- */
        .crud-center-wrapper {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.35rem !important;
        }
        .crud-center-wrapper .btn {
            padding: 0.28rem 0.65rem !important;
            font-size: 0.78rem !important;
            font-weight: 600 !important;
            border-radius: 6px !important;
            line-height: 1.25 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.25rem !important;
            border: none !important;
            box-shadow: none !important;
            cursor: pointer !important;
            text-decoration: none !important;
            transition: all 0.15s ease-in-out !important;
        }
        .crud-center-wrapper .btn i {
            font-size: 0.95rem !important;
            line-height: 1 !important;
        }
        .crud-center-wrapper .btn-warning {
            background-color: #f59e0b !important;
            color: #ffffff !important;
        }
        .crud-center-wrapper .btn-warning:hover {
            background-color: #d97706 !important;
            color: #ffffff !important;
        }
        .crud-center-wrapper .btn-success {
            background-color: #059669 !important;
            color: #ffffff !important;
        }
        .crud-center-wrapper .btn-success:hover {
            background-color: #047857 !important;
            color: #ffffff !important;
        }
        .crud-center-wrapper .btn-danger {
            background-color: #ef4444 !important;
            color: #ffffff !important;
        }
        .crud-center-wrapper .btn-danger:hover {
            background-color: #dc2626 !important;
            color: #ffffff !important;
        }

        /* =========================================================================
           MODAL KONFIRMASI LOG OUT - PEMUSATAN DI TENGAH VIEWPORT
           -------------------------------------------------------------------------
           AKAR MASALAH (bukan gejalanya):
           Markup lama menaruh <form> LANGSUNG sebagai anak dari
           .modal-dialog-centered. Bootstrap mendefinisikan:
               .modal-dialog          { position:relative; width:auto;
                                        margin:var(--bs-modal-margin);
                                        pointer-events:none }
               .modal-dialog          { max-width:var(--bs-modal-width)/*500px*/;
                                        margin-left:auto; margin-right:auto }
               .modal-dialog-centered { display:flex; align-items:center;
                                        min-height:calc(100% - margin*2) }
           Perhatikan: .modal-dialog-centered hanya mengatur align-items (sumbu
           LINTANG/vertikal). justify-content TIDAK pernah di-set, sehingga sumbu
           horizontal (main axis) memakai nilai default flex-start.
           Akibatnya <form> - yang menjadi flex item dengan width:auto - ikut
           shrink-wrap sesuai lebar kontennya dan menempel di sisi KIRI dialog
           500px, bukan memenuhi lebar dialog. Kotak modal karena itu tampak
           bergeser ke kiri dari tengah layar (vertikal tetap pas karena
           align-items:center bekerja normal).

           PERBAIKAN (di akar masalah, tanpa margin/negative offset):
           1) .modal-content dipindahkan menjadi anak langsung .modal-dialog
              (struktur resmi Bootstrap), sedangkan <form> diletakkan DI DALAM
              .modal-content. Akibatnya yang jadi flex item adalah
              .modal-content yang punya width:100%, sehingga tidak lagi
              shrink-wrap dan ikut ter-align dengan benar.
           2) Aturan di bawah memaksa area gelap menutupi viewport penuh dan
              .modal-dialog benar-benar terpusat di kedua sumbu, dengan lebar
              wajar max 400px + margin samping pada layar kecil.
           Markup modal diletakkan sebagai anak langsung <body> (tepat sebelum
           </body>), yaitu DI LUAS <aside> sidebar. Ini penting: <aside> memakai
           transform: translate3d(...) + will-change: transform, yang akan
           membuat position:fixed dihitung relatif terhadap aside, bukan viewport.
           ------------------------------------------------------------------------- */
        #logoutConfirmModal {
            /* Bootstrap memberi position:fixed; top/left:0; width/height:100%.
               Dipertegas menjadi inset:0 agar area gelap selalu 1:1 viewport. */
            inset: 0;
        }

        /* Saat tampil, area gelap menjadi flex container sehingga .modal-dialog
           (flex item) terpusat horizontal DAN vertikal otomatis. */
        #logoutConfirmModal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        #logoutConfirmModal .modal-dialog {
            /* min-height di-reset: pemusatan vertikal ditangani oleh
               align-items:center pada overlay, bukan oleh min-height. */
            min-height: 0;
            width: calc(100% - 2rem);
            max-width: 400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        #logoutConfirmModal .modal-content {
            width: 100%;
            max-width: 400px;
            pointer-events: auto;
        }

        /* Isi modal: judul + tombol X tetap di atas (kiri-kanan),
           teks pertanyaan dan tombol aksi dibuat rata tengah & seimbang. */
        #logoutConfirmModal .modal-body {
            text-align: center;
        }

        #logoutConfirmModal .modal-footer {
            justify-content: center;
            gap: 0.5rem;
        }

        /* Netralkan margin default Bootstrap pada anak footer supaya jarak
           antara kedua tombol benar-benar sama, lalu samakan lebar tombol. */
        #logoutConfirmModal .modal-footer > * {
            margin: 0 !important;
            flex: 0 0 auto;
        }

        #logoutConfirmModal .modal-footer .btn {
            min-width: 8.5rem;
        }

    </style>
</head>
<!-- Catatan: utility arbitrary-value Tailwind untuk warna kanvas (hex 044ABA)
     SENGAJA DIHAPUS dari class body & wrapper. Utility seperti ini hanya aktif
     bila ter-generate build Vite, dan hasilnya berbeda antara build lokal vs
     production (Railway) sehingga memunculkan garis/belah biru gelap di tepi
     sidebar. Warna kanvas kini di-hardcode #3b62f6 (sama persis dengan
     sidebar) lewat CSS di dalam <head>, jadi identik di semua environment. -->
<body class="app-canvas overflow-hidden m-0 p-0" x-data="{ sidebarOpen: false }">

{{-- Global Smart Loader Component --}}
@include('components.loading-overlay')

<!-- ========================================================================= -->
<!-- 1. ROOT APPLICATION CANVAS                                                -->
<!-- ========================================================================= -->
<div class="app-canvas flex h-screen w-screen overflow-hidden m-0 p-0">

    <!-- SIDEBAR DRAWER (Z-INDEX 9999999 - PALING DEPAN KETIKA DIBUKA) -->
    <aside :class="sidebarOpen ? 'mobile-sidebar-active' : ''"
           x-cloak
           class="app-sidebar-drawer fixed lg:static inset-y-0 left-0">
        @include('partials.sidebar')
    </aside>

    <!-- KONTEN UTAMA: Kotak Light Cream dengan Bezel Simetris -->
    <div class="flex-1 min-w-0 h-screen overflow-y-auto overflow-x-hidden content-scroll-wrapper box-border">
        <main class="w-full bg-[#F8F3F0] rounded-none md:rounded-2xl shadow-none md:shadow-md p-4 sm:p-5 md:p-6 box-border flex flex-col relative">

            <!-- HEADER UTAMA DENGAN TOMBOL GARIS TIGA DI POJOK KIRI ATAS UNTUK MOBILE/TABLET/IPAD -->
            <div class="app-header-bar d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2.5 sm:gap-3 mb-3 sm:mb-4 w-100">
                <div class="app-header-left d-flex align-items-start gap-2 sm:gap-2.5 min-w-0">
                    <!-- TOMBOL GARIS TIGA (SATUR ELEMEN UNTUK MOBILE & DESKTOP)
                     Elemen yang SAMA melayani dua breakpoint, sehingga tidak
                     mungkin ada dua hamburger tampil bersamaan:
                       - < 1024px : sidebarOpen = !sidebarOpen (drawer, TIDAK BERUBAH)
                       - >= 1024px: toggle collapse sidebar (localStorage)
                     Ikon <i class='bx bx-menu'> dipakai ulang, tidak ada ikon baru,
                     dan tidak ada hamburger di area sidebar.

                     Pencabangan breakpoint dilakukan DI DALAM ekspresi Alpine
                     supaya cabang mobile tetap menjalankan statement asli
                     `sidebarOpen = !sidebarOpen` apa adanya. -->
                    <button @click="window.matchMedia('(min-width: 1024px)').matches ? (window.__presensiToggleSidebar && window.__presensiToggleSidebar()) : (sidebarOpen = !sidebarOpen)"
                            type="button"
                            class="mobile-top-hamburger flex-shrink-0" 
                            title="Buka/Tutup sidebar"
                            aria-label="Buka/Tutup sidebar">
                        <i class='bx bx-menu'></i>
                    </button>
                    <!-- Wadah Blok Teks Header Solid (Judul & Subjudul Sejajar Vertikal Presisi) -->
                    <div class="header-text-block min-w-0 flex-1 d-flex flex-column justify-content-start">
                        <h1 class="header-main-title truncate m-0 p-0">
                            @hasSection('page_title')
                                @yield('page_title')
                            @else
                                @yield('title', 'Sistem Presensi')
                            @endif
                        </h1>
                        @hasSection('page_subtitle')
                        <p class="header-main-subtitle truncate m-0 p-0">@yield('page_subtitle')</p>
                        @endif
                    </div>
                </div>
                @hasSection('page_header_right')
                <div class="app-header-right d-flex align-items-center gap-2 flex-wrap ms-md-auto">
                    @yield('page_header_right')
                </div>
                @endif
            </div>

            <!-- Konten Utama Halaman -->
            <div class="flex-1">
                @yield('content')
            </div>
            
        </main>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. BOTTOM NAVIGATION BAR (Z-INDEX 99990)                                  -->
<!-- Terletak di atas konten, namun di bawah overlay & sidebar drawer          -->
<!-- ========================================================================= -->
@php
    $navUserRole = Auth::user()->role ?? 'admin';
    $isNavAdmin = ($navUserRole === 'admin');

    // 1. Dashboard
    $navDashboardUrl = panel_route('dashboard');
    $isNavDashboardActive = request()->routeIs('*dashboard*');

    // 2. Kehadiran
    $navKehadiranUrl = panel_route('kehadiran');
    $isNavKehadiranActive = request()->routeIs('*kehadiran*');

    // 3. Presensi (Ikon QR Code: bx-qr-scan)
    $navAbsensiUrl = panel_route('absensi.index');
    $isNavAbsensiActive = (request()->routeIs('*absensi*') || request()->routeIs('*presensi*') || request()->routeIs('*scanner*') || request()->routeIs('*kiosk*')) && !$isNavKehadiranActive;

    // 4. Rekap
    $navRekapUrl = panel_route('rekap');
    $isNavRekapActive = request()->routeIs('*rekap*');

    // 5. Menu tambahan: Pengaturan (admin) / Kelas (guru)
    $navPengaturanUrl = $isNavAdmin ? route('admin.settings.index') : panel_route('classes.index');
    $isNavPengaturanActive = $isNavAdmin
        ? (request()->routeIs('*settings*') || request()->routeIs('*pengaturan*'))
        : (request()->routeIs('*classes*') || request()->routeIs('*kelas*'));
@endphp

<nav class="mobile-bottom-nav lg:hidden" aria-label="Menu Navigasi Bawah">
    <div class="mobile-nav-grid">
        
        <!-- 1. Dashboard (bx bxs-dashboard) -->
        <a href="{{ $navDashboardUrl }}" 
           class="mobile-nav-item {{ $isNavDashboardActive ? 'active' : '' }}"
           title="Dashboard">
            @if($isNavDashboardActive)
                <span class="mobile-nav-indicator"></span>
                <span class="mobile-nav-spotlight"></span>
            @endif
            <i class='bx bxs-dashboard mobile-nav-icon'></i>
            <span class="mobile-nav-label">Dashboard</span>
        </a>

        <!-- 2. Kehadiran (bx bx-calendar-check) -->
        <a href="{{ $navKehadiranUrl }}" 
           class="mobile-nav-item {{ $isNavKehadiranActive ? 'active' : '' }}"
           title="Kehadiran">
            @if($isNavKehadiranActive)
                <span class="mobile-nav-indicator"></span>
                <span class="mobile-nav-spotlight"></span>
            @endif
            <i class='bx bx-calendar-check mobile-nav-icon'></i>
            <span class="mobile-nav-label">Kehadiran</span>
        </a>

        <!-- 3. Presensi (QR Code: bx bx-qr-scan) -->
        <a href="{{ $navAbsensiUrl }}" 
           class="mobile-nav-item {{ $isNavAbsensiActive ? 'active' : '' }}"
           title="Presensi">
            @if($isNavAbsensiActive)
                <span class="mobile-nav-indicator"></span>
                <span class="mobile-nav-spotlight"></span>
            @endif
            <i class='bx bx-qr-scan mobile-nav-icon'></i>
            <span class="mobile-nav-label">Presensi</span>
        </a>

        <!-- 4. Rekap (bx bx-folder-open) -->
        <a href="{{ $navRekapUrl }}" 
           class="mobile-nav-item {{ $isNavRekapActive ? 'active' : '' }}"
           title="Rekap">
            @if($isNavRekapActive)
                <span class="mobile-nav-indicator"></span>
                <span class="mobile-nav-spotlight"></span>
            @endif
            <i class='bx bx-folder-open mobile-nav-icon'></i>
            <span class="mobile-nav-label">Rekap</span>
        </a>

        <!-- 5. Pengaturan (admin) / Kelas (guru) -->
        @php
            $nav5Title = $isNavAdmin ? 'Pengaturan' : 'Kelas';
            $nav5Icon = $isNavAdmin ? 'bx bx-cog' : 'bx bx-buildings';
        @endphp
        <a href="{{ $navPengaturanUrl }}" 
           class="mobile-nav-item {{ $isNavPengaturanActive ? 'active' : '' }}"
           title="{{ $nav5Title }}">
            @if($isNavPengaturanActive)
                <span class="mobile-nav-indicator"></span>
                <span class="mobile-nav-spotlight"></span>
            @endif
            <i class='{{ $nav5Icon }} mobile-nav-icon'></i>
            <span class="mobile-nav-label">{{ $nav5Title }}</span>
        </a>

    </div>
</nav>

<!-- ========================================================================= -->
<!-- 3. BACKDROP OVERLAY GELAP (Z-INDEX 9999990)                               -->
<!-- Menutupi penuh seluruh layar dari ujung atas ke bawah & Bottom Navbar     -->
<!-- ========================================================================= -->
<div x-show="sidebarOpen" 
     x-transition:enter="transition-opacity duration-200 ease-out"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity duration-150 ease-in"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="mobile-sidebar-overlay lg:hidden" 
     style="display: none;">
</div>

<!-- ========================================================================= -->
<!-- 4. MODAL KONFIRMASI LOG OUT (Bersama untuk semua halaman sidebar)        -->
<!-- Tombol "Log Out" di sidebar TIDAK lagi langsung mengeluarkan user.        -->
<!-- Kliknya hanya membuka modal ini; logout baru dijalankan setelah user      -->
<!-- menekan tombol "Ya, Log Out" (POST + @csrf, jadi tetap aman).            -->
<!--                                                                       -->
<!-- POSISI MARKUP (penting, jangan diubah):                                 -->
<!-- Modal ini adalah anak LANGSUNG <body>, tepat sebelum </body>, dan        -->
<!-- berada DI LUAS <aside> sidebar. <aside> memakai transform:              -->
<!-- translate3d(...) + will-change: transform, yang membuat position:fixed   -->
<!-- dihitung relatif terhadap aside, bukan terhadap viewport - dan itu akan  -->
<!-- membuat modal tampak bergeser. Karena markup-nya di luar aside, overlay   -->
<!-- gelap & modal terpusat aman di semua breakpoint dan di semua halaman      -->
<!-- yang memakai layout ini (admin maupun guru).                             -->
<!--                                                                       -->
<!-- STRUKTUR (penting, jangan diubah):                                      -->
<!-- .modal-content HARUS jadi anak langsung .modal-dialog (struktur resmi   -->
<!-- Bootstrap), sedangkan <form> berada DI DALAM .modal-content.            -->
<!-- Bila <form> diletakkan langsung di .modal-dialog, form menjadi flex     -->
<!-- item dengan justify-content default (flex-start) sehingga kotak modal    -->
<!-- menempel ke kiri dan tidak terpusat. Lihat blok CSS "#logoutConfirmModal" -->
<!-- di dalam <head> untuk penjelasan lengkap.                              -->
<!--                                                                       -->
<!-- Penutupan modal: tombol "Batal", tombol "X", klik area overlay gelap,    -->
<!-- dan tombol Esc semuanya sudah difasilitasi Bootstrap 5 secara bawaan.  -->
<!-- ========================================================================= -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('logout') }}" method="POST" class="d-flex flex-column">
                @csrf
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-dark fs-5 mb-0" id="logoutConfirmModalLabel">Log Out</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body py-3 px-4">
                    <p class="mb-0 text-secondary">Apakah Anda yakin untuk Log Out?</p>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-3 fw-semibold">Ya, Log Out</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/instant-download.js') }}?v={{ file_exists(public_path('js/instant-download.js')) ? filemtime(public_path('js/instant-download.js')) : config('app.version', '1') }}"></script>
<script>
    /**
     * Universal Soft UI Delete Confirmation Modal
     * Standardized across Desktop and Mobile (Premium Soft UI Circle)
     */
    window.confirmUniversalDelete = function(options) {
        const opts = options || {};
        const title = opts.title || 'Hapus Data?';
        const confirmText = opts.confirmText || 'Hapus';
        const cancelText = opts.cancelText || 'Tidak';
        const message = opts.html || opts.text || 'Tindakan ini bersifat permanen. Apakah Anda yakin ingin menghapus data ini?';

        const fullHtml = `
            <div style="text-align: center;">
                <div style="color: #dc2626; margin-bottom: 1rem;">
                    <i class='bx bx-error' style="font-size: 2.5rem; line-height: 1; display: block;"></i>
                </div>
                <h5 style="font-weight: 700; color: #1e293b; font-size: 1.25rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.03em;">${title}</h5>
                <div style="color: #64748b; line-height: 1.625; margin-bottom: 1.5rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.02em; text-align: justify; text-align-last: center; padding: 0 0.5rem;">${message}</div>
            </div>
        `;

        return Swal.fire({
            html: fullHtml,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            customClass: {
                popup: 'swal2-modal-soft shadow-lg border-0',
                actions: 'd-flex justify-content-center gap-3 w-100 m-0 p-0',
                confirmButton: 'btn btn-danger fw-medium px-4 py-2',
                cancelButton: 'btn btn-light text-secondary fw-medium px-4 py-2 border'
            }
        }).then(function(result) {
            if (result.isConfirmed && typeof opts.onConfirm === 'function') {
                opts.onConfirm();
            }
            return result;
        });
    };
</script>

<!-- ==========================================================================
     SIDEBAR COLLAPSIBLE - DESKTOP (>= 1024px)
     Script ini HANYA mengatur state collapse & posisi tooltip. Seluruh
     tampilan (lebar, ikon, warna, tooltip) dikerjakan oleh CSS.
     Périlaku mobile TIDAK diubah: di bawah 1024px tombol ini tidak melakukan
     apa-apa dan Alpine @click yang Handles drawer berjalan seperti semula.
     ========================================================================== -->
<script>
(function () {
    'use strict';

    var STORAGE_KEY = 'sidebarCollapsed';
    var DESKTOP_QUERY = window.matchMedia('(min-width: 1024px)');

    function isDesktop() {
        return DESKTOP_QUERY.matches;
    }

    /**
     * Toggle sidebar desktop + simpan state.
     * Dijadikan window.__presensiToggleSidebar supaya bisa dipanggil dari
     * atribut @click Alpine pada tombol hamburger.
     */
    window.__presensiToggleSidebar = function (event) {
        // Di bawah breakpoint desktop: serahkan sepenuhnya ke Alpine drawer.
        if (!isDesktop()) {
            return false;
        }

        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        var root = document.documentElement;
        var collapsed = root.classList.toggle('sidebar-collapsed');

        try {
            window.localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        } catch (e) { /* localStorage diblokir: state tetap berlaku sesi ini */ }

        return true;
    };

    /**
     * Tooltip memakai ::after dengan position:fixed. Alasannya <aside> memakai
     * overflow:hidden, sehingga tooltip yang diposisikan relatif terhadap ikon
     * akan TERPOTONG tepi sidebar. Koordinat left/top tidak bisa dihitung CSS,
     * jadi di sini diisi lewat custom property --tt-left / --tt-top yang
     * kemudian dipakai rules ::after. Diperbarui tiap hover, focus, scroll,
     * dan resize supaya posisinya selalu tepat.
     */
    function initTooltipPositioning() {
        var SIDEBAR_GAP = 12;   // jarak tooltip dari tepi kanan sidebar
        var EDGE_PAD = 8;      // jarak minimum dari tepi jendela

        function place(link) {
            var rect = link.getBoundingClientRect();
            if (!rect.width) {
                return;
            }

            // Perkiraan lebar tooltip (elemen pseudo tidak bisa diukur dari JS).
            // Dipakai hanya untuk menjaga tooltip tidak keluar jendela.
            var estimatedWidth = (link.getAttribute('data-label') || '').length * 7.2 + 22;

            var left = rect.right + SIDEBAR_GAP;
            var maxLeft = window.innerWidth - estimatedWidth - EDGE_PAD;
            if (left > maxLeft) {
                left = Math.max(EDGE_PAD, maxLeft);
            }

            link.style.setProperty('--tt-left', left + 'px');
            link.style.setProperty('--tt-top', (rect.top + rect.height / 2) + 'px');
        }

        function placeAll() {
            // Hanya perlu diposisikan saat sidebar benar-benar collapsed.
            if (!document.documentElement.classList.contains('sidebar-collapsed')) {
                return;
            }
            var links = document.querySelectorAll('.app-sidebar-drawer .nav-link[data-label]');
            Array.prototype.forEach.call(links, place);
        }

        var links = document.querySelectorAll('.app-sidebar-drawer .nav-link[data-label]');
        Array.prototype.forEach.call(links, function (link) {
            link.addEventListener('mouseenter', function () { place(link); });
            link.addEventListener('focus', function () { place(link); });
        });

        window.addEventListener('scroll', placeAll, true);
        window.addEventListener('resize', placeAll);
    }

    /**
     * Saat pindah dari desktop -> mobile, class sidebar-collapsed DILETAKAN
     * dari <html> supaya tidak ada state sisa yang mengganggu drawer mobile.
     * Nilai localStorage sengaja TIDAK dihapus, sehingga saat user kembali ke
     * desktop, sidebar kembali ke modecollapsed yang mereka pilih.
     */
    function syncOnResize() {
        if (!isDesktop()) {
            document.documentElement.classList.remove('sidebar-collapsed');
        } else {
            try {
                if (window.localStorage.getItem(STORAGE_KEY) === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) { /* abaikan */ }
        }
    }

    // Jalankan setelah DOM siap.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            syncOnResize();
            initTooltipPositioning();
        });
    } else {
        syncOnResize();
        initTooltipPositioning();
    }

    window.addEventListener('resize', syncOnResize);
})();
</script>

<script>
/* Auto-filter bersama: pencarian otomatis (debounce 400ms), tombol "x" kecil
   di dalam input, fokus kursor dipertahankan setelah muat ulang, dan submit
   GET filter tidak menampilkan overlay loading penuh (form diberi
   data-no-loader agar pola loading yang ada tidak menutup input). */
(function () {
    'use strict';

    document.querySelectorAll('form[method="GET"] input[name="search"], form[method="get"] input[name="search"]').forEach(function (input) {
        var form = input.form;
        if (!form) return;
        if (!input.id) {
            input.id = 'auto-filter-search-' + Math.random().toString(36).slice(2, 7);
        }

        // 1) Pencarian otomatis saat mengetik (debounce 400ms). Enter tetap
        //    bisa: event submit pada form ikut membersihkan timer debounce.
        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { input.form.submit(); }, 400);
        });
        form.addEventListener('submit', function () { clearTimeout(timer); });

        // 2) Tombol "x" kecil di dalam input (bukan tombol reset terpisah):
        //    hanya muncul saat ada teks, mengosongkan & memuat ulang sekali klik.
        var group = input.closest('.input-group') || input.parentElement;
        if (group && !group.querySelector('.search-clear-x')) {
            if (!group.style.position) group.style.position = 'relative';
            var clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'search-clear-x';
            clearBtn.setAttribute('aria-label', 'Kosongkan pencarian');
            clearBtn.innerHTML = '&times;';
            clearBtn.style.cssText = 'position:absolute; top:50%; transform:translateY(-50%); right:40px; z-index:5; width:18px; height:18px; padding:0; line-height:1; border:none; background:transparent; color:#94a3b8; font-size:16px; display:none; cursor:pointer;';
            var searchBtn = group.querySelector('button:not(.search-clear-x)');
            if (searchBtn && searchBtn.offsetWidth) {
                clearBtn.style.right = (searchBtn.offsetWidth + 10) + 'px';
            }
            group.appendChild(clearBtn);
            input.style.paddingRight = '28px';

            var syncClear = function () {
                clearBtn.style.display = input.value.length > 0 ? 'block' : 'none';
            };
            input.addEventListener('input', syncClear);
            syncClear();

            clearBtn.addEventListener('mousedown', function (e) { e.preventDefault(); });
            clearBtn.addEventListener('click', function () {
                clearTimeout(timer);
                input.value = '';
                syncClear();
                input.focus();
                try {
                    window.sessionStorage.setItem('__filterFocus', JSON.stringify({ id: input.id, path: window.location.pathname }));
                } catch (e) { /* abaikan */ }
                form.submit();
            });
        }

        // 3) Jangan tampilkan overlay loading penuh saat filter otomatis mengetik.
        form.setAttribute('data-no-loader', '');
    });

    // Semua form GET yang berisi select/date: beri data-no-loader agar overlay
    // loading penuh tidak menutup halaman saat ganti dropdown/tanggal.
    document.querySelectorAll('form[method="GET"], form[method="get"]').forEach(function (form) {
        if (form.querySelector('input[name="search"], select, input[type="date"]')) {
            form.setAttribute('data-no-loader', '');
        }
    });

    // 4) Fokus kursor & posisi akhir teks dipertahankan setelah muat ulang penuh.
    try {
        var saved = JSON.parse(window.sessionStorage.getItem('__filterFocus') || 'null');
        if (saved && saved.path === window.location.pathname && saved.id) {
            window.sessionStorage.removeItem('__filterFocus');
            var el = document.getElementById(saved.id);
            if (el) {
                el.focus();
                if (el.tagName === 'INPUT' && el.setSelectionRange && typeof el.value === 'string') {
                    try { el.setSelectionRange(el.value.length, el.value.length); } catch (e) { /* abaikan */ }
                }
            }
        }
    } catch (e) { /* abaikan */ }

    window.addEventListener('beforeunload', function () {
        var el = document.activeElement;
        if (el && el.id && (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA')) {
            try {
                window.sessionStorage.setItem('__filterFocus', JSON.stringify({ id: el.id, path: window.location.pathname }));
            } catch (e) { /* abaikan */ }
        }
    });
})();
</script>
@yield('scripts')
@stack('scripts')
</body>
</html>
