<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'Sistem Presensi') - {{ \App\Models\Setting::getTitleAppName() }}</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="Sistem Presensi Sekolah - Platform manajemen kehadiran digital berbasis QR Code untuk sekolah. Monitoring presensi realtime, akurat, dan terintegrasi.">
    <meta name="keywords" content="presensi sekolah, sistem presensi, qr code, attendance, kehadiran siswa, SMP">
    <meta name="author" content="{{ \App\Models\Setting::getSchoolName() }}">
    <meta name="robots" content="index, follow">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Sistem Presensi') - {{ \App\Models\Setting::getTitleAppName() }}">
    <meta property="og:description" content="Sistem Presensi Sekolah - Platform manajemen kehadiran digital berbasis QR Code untuk sekolah.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ \App\Models\Setting::getLogoUrl() }}">
    <meta property="og:site_name" content="{{ \App\Models\Setting::getTitleAppName() }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('title', 'Sistem Presensi') - {{ \App\Models\Setting::getTitleAppName() }}">
    <meta name="twitter:description" content="Sistem Presensi Sekolah - Platform manajemen kehadiran digital berbasis QR Code.">

    <!-- Favicon -->
    <link rel="icon" type="image/webp" href="{{ \App\Models\Setting::getLogoUrl() }}">
    <link rel="apple-touch-icon" href="{{ \App\Models\Setting::getLogoUrl() }}">

    <!-- JSON-LD Organization Schema -->
    <script type="application/ld+json">
    @php
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => \App\Models\Setting::getSchoolName(),
            'description' => 'Sistem Presensi Sekolah - Platform manajemen kehadiran digital berbasis QR Code',
            'url' => url('/'),
            'logo' => \App\Models\Setting::getLogoUrl(),
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

        /* ===========================================================================
           NOTIFIKASI GLOBAL (SEMUA HALAMAN)
           ---------------------------------------------------------------------------
           ATURAN YANG DIPAKAI DI SELURUH PROJECT:
             1. Notifikasi (alert) yang bisa ditutup = satu BARIS flex:
                   [ ikon + teks .................] [ X ]
             2. Tombol X WAJIB center vertikal - satu baris maupun banyak baris.
             3. Isi notifikasi (body) merebut ruang sisa (flex:1) supaya teks
                membungkus rapi dan tidak pernah ketimpa / tertimpa tombol X.
             4. Padding vertikal simetris (12px atas = 12px bawah).

           KENAPA SEBELUMNYA TOMBOL X "TURUN KE BAWAH":
           Aturan lama menjadikan .alert-dismissible sebagai GRID 2 kolom.
           Tombol X bawaan Bootstrap itu position:absolute (top:0; right:0),
           jadi ia BUKAN grid item - penempatan grid (grid-column/grid-row)
           tidak berlaku padanya dan align-self:center ikut diabaikan karena
           top/right-nya bukan auto. Akibatnya tombol ikut "melayang" jauh dari
           pusat kotak notifikasi.

           SEKARANG positioning absolut Bootstrap DIBONGKAR TOTAL:
             position:static + top/right/bottom/left/transform/margin/padding
             semuanya direset, lalu tombol dijadikan flex item biasa dengan
             align-self:center. Hasilnya center vertikal dijamin, tidak
           bergantung pada konteks apa pun (grid/flex/position) di sekitarnya.

           CATATAN PENTING SOAL .btn-close::before
           Bootstrap menambah ::before berukuran 1.5em yang warnanya transparan
           (khusus memperbesar area klik) dan diposisikan absolute terhadap
           .btn-close. Karena tombol kini position:static, ::before itu akan
           terkunci ke kotak alert dan bisa MENYEBLOK klik di isi notifikasi.
           Karena itu ::before di-matikan (content:none) - area klik tombol X
           tetap 28x28px, jauh lebih besar dari aslinya (18px).

           Semua aturan di sini memakai !important dan ditulis DI SETELAH
           blok "push styles" milik layout, jadi aturan per halaman tidak
           bisa menimpanya.
           CATATAN PENTING: nama direktif Blade sengaja ditulis TANPA tanda
           "@" pada komentar ini, dan blok style tidak lagi disebut memakai
           tag aslinya. Blade memindai SELURUH teks file, termasuk isi blok
           style dan komentar CSS di dalamnya. Kalau direktif push-styles
           ditulis apa adanya di sini, Blade mengompilasinya menjadi PHP
           yang MENCETAK isi push halaman ke dalam blok style ini. Browser
           lalu menemukan tag penutup style milik halaman, menutup blok ini
           lebih awal, dan seluruh CSS di bawahnya tampil sebagai TEKS.
           ======================================================================== */
        .alert-dismissible {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 12px !important;
            /* padding vertikal simetris; tinggi kotak menyesuaikan isi */
            padding-top: 12px !important;
            padding-bottom: 12px !important;
            padding-left: 16px !important;
            padding-right: 12px !important;
            height: auto !important;
            min-height: 0 !important;
            /* fade-out 0.3s dipakai script notifikasi global */
            transition: opacity .3s linear !important;
        }

        /* Anak notifikasi tidak boleh memaksa kotak melebar (biarkan teks wrap) */
        .alert-dismissible > * {
            min-width: 0 !important;
        }

        /* Bodi notifikasi = anak tepat SEBELUM tombol X. Dipakai selector
           posisi (bukan nama class) supaya notifikasi ini tetap benar walau
           markup-nya belum diberi class .flash-notice-body. */
        .alert-dismissible > .flash-notice-body,
        .alert-dismissible > *:nth-last-child(2):not(.btn-close) {
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }

        /* Badge jumlah/baris tambahan di dalam notifikasi tidak ikut melebar */
        .alert-dismissible > ul,
        .alert-dismissible > .flash-notice-body > ul {
            margin-bottom: 0 !important;
        }

        /* TOMBOL X - center vertikal, tidak pernah keluar dari kotak */
        .alert-dismissible > .btn-close,
        .alert-dismissible .btn-close {
            position: static !important;
            top: auto !important;
            right: auto !important;
            bottom: auto !important;
            left: auto !important;
            transform: none !important;
            z-index: auto !important;
            float: none !important;
            margin: 0 !important;
            padding: 0 !important;
            align-self: center !important;
            flex: 0 0 auto !important;
            box-sizing: border-box !important;
            width: 28px !important;
            min-width: 28px !important;
            height: 28px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: 1 !important;
            border-radius: 6px !important;
            background-size: 12px 12px !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            opacity: .55 !important;
        }

        /* ::before bawaan Bootstrap dimatikan (lihat catatan di atas) */
        .alert-dismissible > .btn-close::before,
        .alert-dismissible .btn-close::before {
            content: none !important;
        }

        .alert-dismissible > .btn-close:hover,
        .alert-dismissible > .btn-close:focus-visible,
        .alert-dismissible .btn-close:hover,
        .alert-dismissible .btn-close:focus-visible {
            opacity: 1 !important;
            background-color: rgba(15, 23, 42, .10) !important;
        }

        /* Ikon di kiri tidak ikut melebar, teks di sampingnya yang tumbuh */
        .flash-notice-body {
            display: block;
        }
        .flash-notice-body > i,
        .flash-notice-body > .bx {
            flex: 0 0 auto;
        }

        /* Mobile: notifikasi kompak & proporsional (teks 13px), X tetap center.
           Daftar panjang (alasan import) dibatasi tinggi + scroll di dalam. */
        @media (max-width: 575.98px) {
            .alert-dismissible {
                gap: 10px !important;
                padding-top: 10px !important;
                padding-bottom: 10px !important;
                padding-left: 12px !important;
                padding-right: 10px !important;
                font-size: 13px !important;
                border-radius: 10px !important;
            }
        }

        /* Daftar alasan panjang (mis. import Excel): tinggi dibatasi, scroll
           di dalam alert, di semua ukuran layar. Warna alert tidak berubah. */
        .alert-dismissible ul,
        .alert-dismissible ol,
        .alert-dismissible .flash-import-errors {
            max-height: 160px !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            margin-bottom: 0 !important;
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
           MODEL SATU AREA SCROLL (pengganti pola "tabel punya scroll sendiri"):
           - .content-scroll-wrapper BUKAN lagi area scroll (overflow hidden).
           - <main> dikunci setinggi satu layar (100dvh, 100dvh-2*gap di >=768px)
             dan TIDAK ikut scroll (overflow hidden, kolom flex).
           - Header halaman (.app-header-bar) adalah anak flex yang tidak pernah
             ikut scroll (flex-shrink 0).
           - SATU-SATUNYA yang scroll: .flex-1 di dalam <main> (isi halaman:
             filter, ringkasan, tabel, pagination, form). Pagination kini selalu
             di akhir area scroll, di bawah baris terakhir.
           - Tabel yang lebar tetap boleh scroll HORIZONTAL di .table-responsive
             (lihat blok tabel), tanpa scroll vertikal bersarang.
           Penyebab "scroll ngawur" sebelumnya: scroll bersarang (wrapper +
           .flex-1 + .table-responsive masing-masing scroll vertikal), double
           scrollbar, dan tinggi calc(100dvh - N rem) per halaman yang tidak
           sinkron dengan header sehingga batas bawah kurang/lebih.
           -------------------------------------------------------------------------- */
        .content-scroll-wrapper {
            height: 100vh !important;
            height: 100dvh !important;
            min-height: 0 !important;
            overflow: hidden !important;
            box-sizing: border-box;
        }

        /* <main>: kanvas setinggi satu layar, kolom flex, tidak scroll. */
        .content-scroll-wrapper > main,
        main.w-full {
            height: 100vh !important;
            height: 100dvh !important;
            overflow: hidden !important;
            display: flex !important;
            flex-direction: column !important;
        }

        /* Header halaman: tidak pernah ikut scroll. */
        .content-scroll-wrapper > main > .app-header-bar {
            flex: 0 0 auto !important;
        }

        /* SATU area scroll vertikal: isi halaman. */
        .content-scroll-wrapper > main > .flex-1 {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            min-width: 0 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior-y: contain !important;
            scroll-behavior: smooth;
            scrollbar-width: thin !important;
            scrollbar-color: #94a3b8 #f1f5f9 !important;
        }

        .content-scroll-wrapper > main > .flex-1::-webkit-scrollbar {
            width: 10px !important;
            background-color: #f1f5f9;
            border-radius: 8px;
        }

        .content-scroll-wrapper > main > .flex-1::-webkit-scrollbar-thumb {
            background-color: #94a3b8;
            border-radius: 8px;
            border: 2px solid #f1f5f9;
        }

        .content-scroll-wrapper > main > .flex-1::-webkit-scrollbar-thumb:hover {
            background-color: #64748b;
        }

        @media (max-width: 1023.98px) {
            /* Mobile: scroll halus, kedua scrollbar disembunyikan (tetap bisa swipe). */
            .content-scroll-wrapper > main > .flex-1 {
                scrollbar-width: none !important;
                -ms-overflow-style: none !important;
            }

            .content-scroll-wrapper > main > .flex-1::-webkit-scrollbar {
                display: none !important;
                width: 0 !important;
                height: 0 !important;
            }
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
                height: calc(100vh - (var(--sidebar-gap, 10px) * 2)) !important;
                height: calc(100dvh - (var(--sidebar-gap, 10px) * 2)) !important;
                overflow: hidden !important;
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

        /* ==========================================================================
           3.C KANVAS SATU LAYAR + SCROLL DI DALAM KANVAS  (class .page-canvas-fixed)

           ACUAN (tidak diubah): halaman Presensi, Data Guru, Data Kelas. Di sana
           kanvas putih = <main> dengan tinggi PAS satu layar (tinggi layar
           dikurangi margin atas & bawah wrapper, masing-masing 10px), ujung
           bawahnya terlihat dengan rounded corner, dan isi panjang hanya
           menggulir DI DALAM kanvas - bukan seluruh halaman.

           Pada 5 halaman yang memakai class .page-canvas-fixed (Dashboard,
           Catatan Kehadiran, Rekap Presensi, Data Siswa, Pengaturan) kanvas
           sebelumnya ikut memanjang melebihi layar, sehingga halaman ikut
           ter-scroll dan ujung bawah + rounded corner-nya tidak pernah terlihat.

           Blok ini menyamakan GEOMETRI-nya saja. Margin, radius, warna, padding,
           tabel, route, dan fitur lain tidak disentuh - semuanya tetap memakai
           aturan kanvas di atas (bagian 3).

           Cara kerja:
             1. <main> dikunci setinggi layar: calc(100dvh - 2 x --sidebar-gap).
                Nilainya sama dengan min-height yang sudah dipakai layout, jadi
                tinggi kanvas tidak berubah, hanya terkunci; margin (10px) dan
                border radius (1rem) tetap dari aturan yang sama.
             2. <main> overflow:hidden -> tidak ada isi yang keluar atau menimpa
                kanvas. Area scroll dipindah ke .flex-1 (di dalam kanvas) supaya
                header halaman tetap diam dan tidak ikut bergulir.
             3. Halaman tanpa tabel (Dashboard, Pengaturan) memakai .flex-1 itu
                sendiri sebagai area scroll. Halaman tabel (Siswa, Kehadiran,
                Rekap) memakai .flex-1 sebagai kolom flex: kartu tabel mengisi
                sisa tinggi kanvas, jadi tabelnya yang menggulir di dalam
                .table-responsive dan header tabel tetap sticky.
             4. .row Bootstrap punya margin atas negatif dari gutter. Di dalam area
                scroll margin negatif di tepi atas tidak bisa digulir, jadi
                dinetralkan pada baris pertama saja (jarak antar baris tetap).

           Class .page-canvas-fixed dipasang lewat yield "canvas_class" pada
           markup <main> di bawah, jadi hanya halaman yang memilihnya yang
           terpengaruh; halaman lain tidak berubah sama sekali.
           (Sengaja ditulis tanpa "@" supaya Blade tidak mengompilasinya.)
        ========================================================================== */
        @media (min-width: 768px) {

            /* 1. Kanvas putih setinggi satu layar (margin & radius tetap dari
                  aturan layout bersama di atas). */
            .content-scroll-wrapper > main.page-canvas-fixed {
                height: calc(100dvh - (var(--sidebar-gap, 10px) * 2)) !important;
                overflow: hidden !important;
            }

            /* 2. Area scroll berada DI DALAM kanvas. */
            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1 {
                min-height: 0 !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                overscroll-behavior-y: contain;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
                scrollbar-color: #94a3b8 #f1f5f9;
            }

            /* Scrollbar isi kanvas: rampai dan memakai warna yang sama dengan
               scrollbar tabel (lihat blok .table-responsive di bawah). */
            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1::-webkit-scrollbar {
                width: 10px !important;
                height: 10px !important;
                display: block !important;
                background-color: #f1f5f9;
                border-radius: 8px;
            }

            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1::-webkit-scrollbar-track {
                background-color: #f1f5f9;
                border-radius: 8px;
            }

            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1::-webkit-scrollbar-thumb {
                background-color: #94a3b8;
                border-radius: 8px;
                border: 2px solid #f1f5f9;
            }

            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1::-webkit-scrollbar-thumb:hover {
                background-color: #64748b;
            }

            /* 3. Margin atas negatif baris pertama (gutter Bootstrap) dinetralkan
                  supaya tidak terpotong tepi atas area scroll. */
            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1 > .row:first-child {
                margin-top: 0 !important;
            }
        }

        @media (min-width: 1024px) {
            /* MODEL SATU AREA SCROLL: aturan flex-fill kartu tabel peninggalan
               pola lama dinetralkan. Kartu tabel tumbuh alami mengikuti isi;
               yang scroll vertikal hanya konten .flex-1 di dalam <main>. */
            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1:has(> #daftar-siswa),
            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1:has(> #daftar-kehadiran),
            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1:has(> #daftar-rekap) {
                display: block !important;
            }

            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1 > * {
                flex-shrink: 0;
            }

            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1 > #daftar-siswa,
            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1 > #daftar-kehadiran,
            .content-scroll-wrapper > main.page-canvas-fixed > .flex-1 > #daftar-rekap {
                height: auto !important;
                max-height: none !important;
                min-height: 0 !important;
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
        /* KONTAINER (kartu, isi modal, kotak statistik, notifikasi): 12px tetap. */
        .card,
        .modal-content,
        .stat-card-polished,
        .alert {
            border-radius: 12px !important;
        }

        /* KONTROL: search, input form, textarea, dropdown/select, dan tombol
           memakai SATU token radius (--clean-radius = 6px) di semua halaman,
           semua breakpoint, dan di dalam modal. Aturan khusus per halaman yang
           dulu memakai angka 6/7/8/10/12px juga mengacu ke token yang sama. */
        .btn,
        button,
        .form-control,
        .form-select,
        .input-group-text {
            border-radius: var(--clean-radius) !important;
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

        /* Fokus menyeluruh satu kotak: ring di wrapper + border input ikut
           biru, bayangan ganda bawaan dimatikan. Token ring sama dengan
           fokus input login (rgba(59,98,246,.12)). */
        .search-box-wrap:focus-within {
            border-radius: var(--clean-radius) !important;
            box-shadow: 0 0 0 3px rgba(59, 98, 246, 0.12) !important;
        }

        .search-box-wrap > .form-control:focus {
            border-color: #2563eb !important;
            box-shadow: none !important;
        }

        .search-box-wrap > .form-control {
            width: 100% !important;
            height: 38px !important;
            padding-right: 2.75rem !important;
            min-width: 0 !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: var(--clean-radius) !important;
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
            border-radius: var(--clean-radius) !important;
            background: transparent !important;
            box-shadow: none !important;
            color: #64748b !important;
            z-index: 10 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
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
            /* Transisi lokal (warna/background) kurz dari 0.18s menjadi 0s:
               hover terasa langsung, tanpa penundaan. */
            transition: none !important;
        }

        /* Label menu: selalu satu baris. Saat sidebar melebar, teks mengikuti
           lebar (tidak membungkus) dan tidak pernah membuat ikon melompat. */
        .app-sidebar-drawer .sidebar-nav-label {
            white-space: nowrap !important;
            /* Teks muncul/hilang bersama sidebar secara instan (0s). */
            transition: none !important;
        }

        /* --------------------------------------------------------------------------
           HOVER MENU TIDAK AKTIF - TANPA GESER

           Aturan khusus `:not(.active)` sehingga menu yang sedang aktif TIDAK
           bereaksi saat di-hover (highlight putihnya tidak berubah).
           Ikon & teks TIDAK digeser, tidak diskalakan, tidakBERGESER sama
           sekali - yang berubah hanya warna latar kotak menjadi abu-abu
           transparan tipis. Dibatasi di @media (hover: hover) supaya perangkat
           sentuh (HP/tablet) tidak pernah mendapat state hover yang "nempel".
           Warna dipakai dari palet yang sudah ada (putih transparan), tidak ada
           warna baru.
        -------------------------------------------------------------------------- */
        @media (hover: hover) {
            .app-sidebar-drawer .nav-link:not(.active):hover {
                background-color: rgba(255, 255, 255, 0.16) !important;
                color: #ffffff !important;
                box-shadow: none !important;
            }

            .app-sidebar-drawer .nav-link:not(.active):hover i {
                color: #ffffff !important;
            }
        }

        /* Fokus keyboard mendapat perlakuan yang sama dengan hover, tapi tetap
           memerlukan indikator fokus yang jelas (outline putih). */
        .app-sidebar-drawer .nav-link:not(.active):focus-visible {
            background-color: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff !important;
            outline: 2px solid #ffffff !important;
            outline-offset: -2px !important;
            box-shadow: none !important;
        }

        .app-sidebar-drawer .nav-link:not(.active):focus-visible i {
            color: #ffffff !important;
            outline: none !important;
        }

        /* Menu AKTIF tidak boleh berubah karena hover/fokus - dikunci ulang.
           Warna teks & ikon memakai BIRU TEMA yang sudah ada
           (--primary-blue = #3b62f6, sama dengan latar sidebar), bukan biru
           default Bootstrap, supaya menu aktif unmistakably milik tema ini.
           Rasio kontras #3b62f6 di atas putih = 4.9:1 (melewati ambang 4.5:1). */
        .app-sidebar-drawer .nav-link.active,
        .app-sidebar-drawer .nav-link.active i {
            color: var(--primary-blue, #3b62f6) !important;
        }

        .app-sidebar-drawer .nav-link.active {
            font-weight: 600 !important;
        }

        @media (hover: hover) {
            .app-sidebar-drawer .nav-link.active:hover {
                background-color: #ffffff !important;
                color: var(--primary-blue, #3b62f6) !important;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.15) !important;
            }
            .app-sidebar-drawer .nav-link.active:hover i {
                color: var(--primary-blue, #3b62f6) !important;
            }
        }

        /* --------------------------------------------------------------------------
           LOG OUT - NORMAL & HOVER/FOKUS (mode expanded)

           Lebar, tinggi, margin, sudut, dan padding TOMBOL Log Out sengaja
           TIDAK diberi width manual: ia memakai mekanismeLebar block-level
           yang PERSIS sama dengan item menu lain (margin 0 0.75rem,16px),
           plus box-sizing: border-box sebagai pengaman. Warna merah memakai
           merah tombol HAPUS yang sudah dipakai di aplikasi (#dc2626).
        -------------------------------------------------------------------------- */
        .app-sidebar-drawer .sidebar-logout-link {
            background: transparent !important;
            color: #ffffff !important;
            /* UKURAN DISAMAKAN PERSIS DENGAN ITEM MENU DI ATASNYA.
               Item menu (<a class="nav-link">) memakai margin kiri/kanan
               0.75rem dan otomatis selebar container (<li>). Tombol Log Out
              _results <button> yang punya perilaku lebar berbeda, jadi
               lebarnya ditulis EKSPLISIT di sini:
                 margin kiri + margin kanan (1.5rem) + lebar (100% - 1.5rem)
                 = 100% dari <li>  -> TEPAT sama dengan <a> menu di atas,
                 tanpa overflow dan tanpa tombol yang lebih kecil.
               Nilai lain (padding, radius, font, ikon, gap) sengaja
               menyalin aturan global .app-sidebar-drawer .nav-link. */
            width: calc(100% - 1.5rem) !important;
            margin-left: 0.75rem !important;
            margin-right: 0.75rem !important;
            margin-top: 0 !important;
            margin-bottom: 16px !important;
            padding: 0.6rem 0.75rem !important;
            border-radius: 12px !important;
            min-height: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 0.75rem !important;
            box-sizing: border-box !important;
            text-align: left !important;
            /* Transisi hover CEPAT (maks 0.1s) sesuai permintaan. */
            transition: background-color 0.1s ease, color 0.1s ease !important;
        }

        /* Ikon & teks Log Out rata kiri dengan ikon & teks menu lain. */
        .app-sidebar-drawer .sidebar-logout-link i {
            font-size: 1.25rem !important;
            line-height: 1 !important;
            flex: 0 0 auto !important;
        }

        .app-sidebar-drawer .sidebar-logout-link .sidebar-nav-label {
            text-align: left !important;
        }

        .app-sidebar-drawer .sidebar-logout-link i {
            color: #ffffff !important;
        }

        /* Hover / :active -> MERAH PENUH. Perilaku :active TIDAK memakai
           @media (hover:hover) supaya juga berlaku di perangkat sentuh
           (mobile), ketika tidak ada hover sama sekali. Bentuk & ukuran
           container tidak berubah karena yang berubah hanya warnanya. */
        @media (hover: hover) {
            .app-sidebar-drawer .sidebar-logout-link:hover {
                background-color: #dc2626 !important;
                color: #ffffff !important;
            }

            .app-sidebar-drawer .sidebar-logout-link:hover i {
                color: #ffffff !important;
            }
        }

        .app-sidebar-drawer .sidebar-logout-link:active {
            background-color: #dc2626 !important;
            color: #ffffff !important;
        }

        .app-sidebar-drawer .sidebar-logout-link:active i {
            color: #ffffff !important;
        }

        .app-sidebar-drawer .sidebar-logout-link:focus-visible {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            outline: none !important;
        }

        .app-sidebar-drawer .sidebar-logout-link:focus-visible i {
            color: #ffffff !important;
        }

        /* Pengguna yang memilih "kurangi animasi" di OS: seluruh transisi
           sidebar dimatikan (FASE 2 aturan 2 & 6). Animasi tidak hilang -
           hanya durasinya jadi 0, sehingga state akhir tetap sama persis. */
        @media (prefers-reduced-motion: reduce) {
            .app-sidebar-drawer,
            .app-sidebar-drawer .nav-link,
            .app-sidebar-drawer .sidebar-nav-label,
            .app-sidebar-drawer .sidebar-logout-link,
            .app-sidebar-drawer .nav-link[data-label]::after {
                transition: none !important;
                animation: none !important;
            }
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

            /* Transisi halus pada lebar sidebar. FASE 2: satu durasi (0.28s, berada di
               rentang 260-300ms) dan satu easing (cubic-bezier(0.4,0,0.2,1))
               dipakai untuk SEMUA yang bergerak, supaya buka/tutup tidak
               terasa seperti dua animasi berbeda. Hanya width yang beranimasi
               agar konten utama ikut melebar tanpa layout melompat. */
            html .app-sidebar-drawer {
                /* ANIMASI INSTAN: durasi 0s. Permintaan pemilik: buka/tutup
                   sidebar harus terasa langsung, tanpa penundaan sama
                   sekali. State akhir (lebarnya) tetap sama persis
                   seperti sebelumnya. */
                transition: none !important;
                overflow-x: hidden !important;
            }

            /* Garis pemisah di bawah logo & nama sekolah DITURUNKAN 10px (rentang
               8-12px) supaya tidak menempel pada logo. Nilai ini sama untuk
               mode expanded & collapsed supaya garis tidak "melompat" saat
               sidebar dibuka-tutup. Batas atas sidebar tetap sama. */
            html.sidebar-collapsed .sidebar-brand {
                padding: 1rem 0.75rem 1rem 0.75rem !important;
            }

            html.sidebar-collapsed .sidebar-brand > div {
                justify-content: center !important;
                align-items: center !important;
                min-height: 46px !important;
                /* Garis pemisah TETAP terlihat saat collapsed (hanya dikecilkan
                   & tetap terpusat), tidak dihilang seperti sebelumnya. */
                padding-bottom: 10px !important;
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

            /* Label teks disembunyikan saat collapsed, TANPA mengambil ruang layout:
               memakai pola "visually hidden" (absolute + clip) supaya ikon
               tetap persis di sumbu tengah sidebar dan TIDAK melompat.
              opacity-nya yang dianimasikan, sehingga teks terungkap
               bersamaan dengan melebar sidebar (delay 40ms, jauh di bawah
               batas 60ms). */
            html.sidebar-collapsed .app-sidebar-drawer .nav-link {
                /* Anchor untuk label yang absolute (position: fixed tooltip
                   ::after tetap tidak terpengaruh). */
                position: relative !important;
            }

            html.sidebar-collapsed .sidebar-nav-label {
                position: absolute !important;
                width: 1px !important;
                height: 1px !important;
                overflow: hidden !important;
                clip-path: inset(50%) !important;
                white-space: nowrap !important;
                opacity: 0 !important;
                /* Instan: tanpa transisi (sesuai permintaan buka/tutup cepat). */
                transition: none !important;
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
                transition: none !important;
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
                /* WARNA: PUTIH PENUH (#ffffff), sama persis dengan ikon menu lain
                   (Dashboard, Presensi, dst.) saat tidak aktif.
                   Nilai lama #fecaca (pink pucat) membuat ikon Log Out terlihat
                   pudar/muram dibanding menu lain - terutama karena di mode
                   collapsed label teksnya disembunyikan, jadi ikon satu-satunya
                   penanda tombol. */
                color: #ffffff !important;
            }

            html.sidebar-collapsed .app-sidebar-drawer .sidebar-logout-link i {
                font-size: 1.35rem !important;
                /* Sama seperti induknya: putih penuh, tanpa pudar. */
                color: #ffffff !important;
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
               FASE 2: garis pemisah di turunkan 10px (dari 0) supaya tidak
               menempel pada logo - nilai ini SAMA dengan mode collapsed,
               jadi garis tidak melompat saat sidebar dibuka/tutup. */
            .sidebar-brand > .sidebar-brand-row {
                align-items: center !important;
                justify-content: flex-start !important;
                min-height: 46px !important;
                width: 100% !important;
                padding-bottom: 10px !important;
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

        /* --------------------------------------------------------------------------
           JARAK BAWAH KONTEN SERAGAM (SUMBER BERSAMA)

           Nilai diambil dari halaman PRESENSI sebagai acuan: wrapper-nya
           memakai `pb-4` (= 1rem / 16px) sebagai jarak bawah. Dashboard,
           Kehadiran, Rekap, dan Pengaturan sebelumnya memakai `mb-4`
           (= 1.5rem / 24px) pada elemen terakhir, sehingga jaraknya 8px
           lebih besar dari Presensi dan antar halaman tidak seragam.

           Diperbaiki DI SATU kontainer layout bersama - bukan per halaman:
             - kontainer konten (`.flex-1` di dalam <main>) diberi
               padding-bottom 1rem, sama persis dengan `pb-4` di Presensi;
             - margin bawah elemen TERAKHIR dinolkan supaya tidak menambah
               jarak ganda (termasuk tombol SIMPAN di Pengaturan, yang
               sebelumnya memakai `mb-4`).

           Tidak menambah scrollbar baru: padding-bottom ini menggantikan
           margin yang dihapus, jadi total tinggi konten di halaman pendek
           justru tidak bertambah.
        -------------------------------------------------------------------------- */
        .content-scroll-wrapper > main > .flex-1 {
            padding-bottom: 1rem !important;
        }

        .content-scroll-wrapper > main > .flex-1 > *:last-child {
            margin-bottom: 0 !important;
        }

        /* ==========================================================================
           TABEL DATA DI MOBILE (< 1024px) - SAMA PERSIS DENGAN DESKTOP

           Sebelumnya tabel di bawah 1024px diubah menjadi KARTU BERTUMPUK:
           <thead> disembunyikan, tiap <tr> jadi satu kotak, tiap <td> jadi
           baris "label : nilai" yang labelnya dibaca dari atribut data-label,
           dan sel Aksi turun ke bawah kartu. SEMUA itu dihapus.

           Sekarang mobile memakai tabel yang persis sama dengan desktop:
           header kolom tetap terlihat, semua kolom tetap tampil (termasuk
           kolom Aksi), lebar kolom, zebra, dan header sticky tidak berubah.

           Kalau lebar layar tidak cukup, tabel digeser kiri-kanan (horizontal
           swipe) - overflow-x: auto + -webkit-overflow-scrolling: touch sudah
           dipasang pada .table-responsive di layout bersama (lihat blok
           "TABEL DATA: SCROLL VERTIKAL DI SISI KANAN" di bawah), dan setiap
           tabel punya min-width supaya kolom tidak gepeng. Pola persis sama
           dengan tabel Rekap yang sudah benar.

           DESKTOP >= 1024px sama sekali tidak tersentuh oleh blok ini.
        ========================================================================== */
        @media (max-width: 1023.98px) {

            /* 1. Kotak tabel TETAP jadi area scroll (vertikal + horizontal),
                  sama seperti desktop: tabel yang lebih lebar dari layar
                  digeser dengan sentuhan, bukan membuat halaman ikut
                  memanjang dan tidak lagi jadi dokumen panjang. */
            .table-responsive {
                overflow-x: auto !important;
                overflow-y: visible !important;
                max-height: none !important;
                height: auto !important;
                -webkit-overflow-scrolling: touch !important;
                overscroll-behavior-x: contain !important;
            }

            /* 2. Lantai lebar tabel: nilai minimal supaya kolom tidak gepeng.
                  Selector ditulis dengan :where() supaya kontribusinya 0,
                  sehingga halaman yang sudah punya min-width sendiri (Rekap
                  800px, Presensi Kelas 720px, Presensi 680px, Kehadiran 680px,
                  Data Siswa, Data Guru, Data Kelas) tetap memakai nilainya. */
            :where(.table-responsive) table {
                min-width: 620px;
            }
        }

        /* ==========================================================================
           TOOLBAR HALAMAN DATA (SISWA / GURU / KELAS) - DUA BREAKPOINT TERPISAH

             A. < 768px (HP)              -> tumpuk penuh satu per satu
                                             (susunan sama seperti tombol
                                             Pengaturan di mobile)
             B. 768 - 1023.98px (tablet)  -> SATU BARIS: baris 1 = pencarian +
                                             dropdown, baris 2 = tombol aksi
                                             sejajar rata kanan
             C. >= 1024px (desktop)       -> TIDAK DISENTUH (aturan per halaman
                                             + grid >= 1280 tetap berlaku)

           MENGAPA BLOK LAMA DIPISAH (blok lama: satu @media max-width: 1023.98px
           yang memaksa flex-direction: column di SEMUA rentang < 1024px):
             1. .action-search-form memakai `flex: 1 1 380px`; di dalam kontainer
                column, flex-basis dipakai sebagai TINGGI, bukan lebar ->
                terukur form tinggi 380px & toolbar 499px di 980px (celah kosong).
             2. flex-wrap: wrap + arah column membuat .filter-box-wrap mendarat di
                "kolom kanan": terukur x = 931.6 -> right = 1795.6 (keluar layar,
                dropdown "Semua Kelas" terpotong).
             3. `body .action-buttons-wrap .btn-solid-pill { flex: 1 1 100% }`
                membuat flex-basis menimpa `height: 38px`, sehingga tinggi tombol
                jadi tinggi teks (terukur 20.4px di 980px).

           Urutan elemen di DOM, teks/label, warna tombol, dan fungsi TIDAK berubah;
           hanya arah susunan, tinggi, dan ukuran tombol. Class yang dipakai
           (action-bar-section, action-search-form, search-box-wrap,
           search-input-wrap, filter-box-wrap, action-buttons-wrap,
           btn-solid-pill) sama di halaman Siswa, Guru, dan Kelas, jadi cukup
           diatur sekali di layout bersama.
        ========================================================================== */

        /* ---------- A. HP (< 768px): tumpuk penuh satu per satu ---------- */
        @media (max-width: 767.98px) {

            /* 1. Baris utama jadi satu kolom. */
            body .action-bar-section,
            body .action-search-form {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 0.6rem !important;
            }

            /* 2. Input cari + ikon search: full width, tetap satu baris,
                  tidak pernah turun atau terpotong (flex-wrap: nowrap
                  berasal dari aturan halaman dan tidak ditimpa). */
            body .action-search-form .search-box-wrap,
            body .action-search-form .search-input-wrap,
            body .action-bar-section .search-box-wrap,
            body .action-bar-section .search-input-wrap {
                flex: 1 1 100% !important;
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
            }

            /* 3. Dropdown filter: baris penuh sendiri di bawah pencarian. */
            body .action-search-form .filter-box-wrap,
            body .action-bar-section .filter-box-wrap {
                flex: 1 1 100% !important;
                width: 100% !important;
                max-width: none !important;
                min-width: 0 !important;
            }

            body .action-search-form .filter-box-wrap select.form-select,
            body .action-bar-section .filter-box-wrap select.form-select {
                width: 100% !important;
            }

            /* 4. Tombol aksi: satu per satu, full width, seperti tombol mobile
                  Presensi - tidak menyamping, tidak terpotong. */
            body .action-buttons-wrap {
                flex-direction: column !important;
                align-items: stretch !important;
                justify-content: flex-start !important;
                gap: 0.5rem !important;
                margin-left: 0 !important;
                margin-top: 0.25rem !important;
                width: 100% !important;
            }

            body .action-buttons-wrap .btn-solid-pill {
                flex: 1 1 100% !important;
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                justify-content: center !important;
            }

            /* 5. Ukuran tombol aksi = ukuran tombol Pengaturan (terukur di
                  halaman Pengaturan: min-height 40px, padding 0.5rem 1rem
                  (= 8px 16px, dari .pengaturan-modul-btn), font 13.6px,
                  radius 12px, weight 600).
                  Catatan: di sini flex-basis `1 1 100%` (butir 4) menimpa
                  `height`, jadi yang menentukan tinggi adalah min-height;
                  padding vertikal pun tidak berpengaruh karena tinggi kaku. */
            body .action-bar-section .action-buttons-wrap .btn-solid-pill {
                height: 40px !important;
                min-height: 40px !important;
                padding: 8px 16px !important;
                font-size: 13.6px !important;
                font-weight: 600 !important;
                border-radius: var(--clean-radius) !important;
            }
        }

        /* ---------- B. TABLET (768 - 1023.98px): SATU BARIS, TANPA CELAH ----------
           Arah baris + wrap berasal dari aturan per halaman (.action-bar-section
           & .action-search-form sudah display:flex / flex-wrap:wrap), di sini
           hanya DIPASTIKAN tetap baris (tidak ditimpa jadi column) dan tinggi
           mengikuti isi, sehingga tidak ada lagi ruang kosong 380px maupun
           dropdown yang terlempar ke luar layar. */
        @media (min-width: 768px) and (max-width: 1023.98px) {

            /* 1. Baris utama & form pencarian: arah BARIS, tinggi = isi.
                  flex-wrap: wrap tetap aktif, jadi bila ruang tidak cukup
                  tombol aksi turun ke baris berikutnya (bukan terpotong). */
            body .action-bar-section,
            body .action-bar-section .action-search-form,
            body .action-search-form {
                flex-direction: row !important;
                flex-wrap: wrap !important;
                align-items: center !important;
                gap: 0.6rem 1rem !important;
            }

            body .action-search-form {
                min-width: 0 !important;
                max-width: 100% !important;
                margin: 0 !important;
            }

            /* 2. Pencarian + dropdown: tetap sejajar dalam satu baris,
                  mengikuti nilai halaman (search 1 1 240px, filter 0 1 200px). */
            body .action-search-form .search-box-wrap,
            body .action-search-form .search-input-wrap,
            body .action-bar-section .search-box-wrap,
            body .action-bar-section .search-input-wrap {
                flex: 1 1 240px !important;
                width: auto !important;
                max-width: none !important;
                min-width: 170px !important;
            }

            body .action-search-form .filter-box-wrap,
            body .action-bar-section .filter-box-wrap {
                flex: 0 1 200px !important;
                width: auto !important;
                max-width: 200px !important;
                min-width: 150px !important;
            }

            body .action-search-form .filter-box-wrap select.form-select,
            body .action-bar-section .filter-box-wrap select.form-select {
                width: 100% !important;
            }

            /* 3. Tombol aksi: SEJAJAR satu baris rata kanan, tinggi & radius
                  mengikuti tombol Pengaturan (terukur: padding 8px 16px,
                  font 14px, weight 600, radius 12px, tinggi 39px).
                  min-width 150px dari halaman dipertahankan sehingga semua
                  tombol sama lebar seperti di desktop. */
            body .action-buttons-wrap,
            body .action-bar-section .action-buttons-wrap {
                flex: 0 0 auto !important;
                flex-direction: row !important;
                flex-wrap: wrap !important;
                align-items: center !important;
                justify-content: flex-end !important;
                gap: 0.5rem !important;
                width: auto !important;
                max-width: 100% !important;
                margin-left: auto !important;
                margin-top: 0 !important;
            }

            body .action-bar-section .action-buttons-wrap .btn-solid-pill {
                flex: 0 0 auto !important;
                width: auto !important;
                max-width: none !important;
                margin: 0 !important;
                justify-content: center !important;
                height: 39px !important;
                min-height: 39px !important;
                padding: 8px 16px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                border-radius: var(--clean-radius) !important;
            }
        }

        /* --------------------------------------------------------------------------
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
            /* MODEL SATU AREA SCROLL: tabel TUMBUH sesuai isi, TANPA scroll
               vertikal sendiri. Yang scroll vertikal hanya area konten halaman.
               Tabel lebar tetap boleh scroll HORIZONTAL di wrapper ini saja. */
            overflow-y: visible !important;
            overflow-x: auto !important;
            max-height: none !important;
            /* Izinkan tabel lebar bergeser ke samping tanpa merusak layout. */
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior-x: contain !important;
        }

        /* Header tabel menempel di atas area scroll HALAMAN.
           Sticky dipasang pada <thead> dan bekerja terhadap area scroll baru
           (konten .flex-1 di dalam <main>); warna background milik setiap
           halaman tetap utuh - tidak ada warna, font, atau gaya header
           yang berubah. Dipertahankan karena berfungsi benar. */
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

        /* --------------------------------------------------------------------------
           SCROLLBAR TABEL - VERTIKAL SAJA DI DESKTOP, SEMBUNYI DI MOBILE

           DESKTOP (>= 1024px):
             - scrollbar VERTIKAL (kanan) tetap terlihat, tebal 10px, warna abu-abu
               yang sama dengan warna teks tabel (bukan overlay yang menghilang);
             - scrollbar HORIZONTAL (bawah) DISEMBUNYIKAN (height: 0), supaya
               tabel lebar tidak menampilkan kotak scroll di bawah. Tabel tetap
               bisa digeser lewat wheel / trackpad / Shift+scroll.

           MOBILE (< 1024px):
             - KEDUA scrollbar (kanan & bawah) disembunyikan. Tabel tetap bisa
               digeser dengan sentuhan (overflow-x/y auto tetap aktif).

           Yang disembunyikan hanya VISUAL scrollbar-nya. overflow, min-width
           tabel, dan header sticky tidak berubah, jadi fungsi scroll, swipe
           horizontal, dan isi tabel tidak berubah sama sekali.

           Catatan Firefox: Firefox tidak punya cara menyembunyikan scrollbar
           per sumbu lewat CSS. Di sana sumbu horizontal tetap tipis (thin).
           Chrome / Edge / Safari (Android & iOS) ikut aturan di bawah.
        -------------------------------------------------------------------------- */
        .table-responsive {
            scrollbar-width: thin !important;
            -ms-overflow-style: scrollbar !important;
        }

        /* height: 0 = sembunyikan scrollbar horizontal (bawah). */
        .table-responsive::-webkit-scrollbar {
            width: 10px !important;
            height: 0 !important;
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

        /* Sudut pertemuan dua sumbu tidak perlu digambar. */
        .table-responsive::-webkit-scrollbar-corner {
            display: none !important;
        }

        /* MOBILE + TABLET (<= 1023.98px): sembunyikan KEDUA sumbu (kanan & bawah).
           Cara ini sama dengan .no-scrollbar & .content-scroll-wrapper di layout
           ini, jadi tidak ada pola baru. Cakupan media query dikembalikan ke
           max-width: 1023.98px seperti aslinya supaya halaman lain (mis. Catatan
           Kehadiran & Pengaturan) TIDAK berubah sama sekali.

           Pengecualian tablet hanya untuk tabel Data Siswa / Data Guru / Data
           Kelas: di 768-1023.98px scrollbar horizontal sengaja ditampilkan lagi
           (lihat blok setelah ini) karena menjadi satu-satunya petunjuk bahwa
           kolom terakhir (AKSI) masih bisa digeser.

           DESKTOP (>= 1024px) tidak terpengaruh: gaya scrollbar desktop di atas
           tidak pernah diubah. */
        @media (max-width: 1023.98px) {
            .table-responsive {
                scrollbar-width: none !important;
                -ms-overflow-style: none !important;
            }

            .table-responsive::-webkit-scrollbar {
                display: none !important;
                width: 0 !important;
                height: 0 !important;
            }
        }

        /* TABLET (768 - 1023.98px) SAJA: tampilkan scrollbar tipis kembali HANYA
           pada #daftar-siswa / #daftar-guru / #daftar-kelas. ID ini juga dipakai
           sebagai jangkar scroll paginasi (render_compact_pagination), jadi tidak
           ada elemen baru. Halaman lain tetap tanpa scrollbar seperti di atas. */
        @media (min-width: 768px) and (max-width: 1023.98px) {
            #daftar-siswa .table-responsive,
            #daftar-guru .table-responsive,
            #daftar-kelas .table-responsive {
                scrollbar-width: thin !important;
                -ms-overflow-style: scrollbar !important;
            }

            #daftar-siswa .table-responsive::-webkit-scrollbar,
            #daftar-guru .table-responsive::-webkit-scrollbar,
            #daftar-kelas .table-responsive::-webkit-scrollbar {
                display: block !important;
                width: 10px !important;
                height: 8px !important;
            }

            #daftar-siswa .table-responsive::-webkit-scrollbar-track,
            #daftar-guru .table-responsive::-webkit-scrollbar-track,
            #daftar-kelas .table-responsive::-webkit-scrollbar-track {
                display: block !important;
                background-color: #f1f5f9;
                border-radius: 8px;
            }

            #daftar-siswa .table-responsive::-webkit-scrollbar-thumb,
            #daftar-guru .table-responsive::-webkit-scrollbar-thumb,
            #daftar-kelas .table-responsive::-webkit-scrollbar-thumb {
                display: block !important;
                background-color: #94a3b8;
                border-radius: 8px;
                border: 2px solid #f1f5f9;
            }

            #daftar-siswa .table-responsive::-webkit-scrollbar-thumb:hover,
            #daftar-guru .table-responsive::-webkit-scrollbar-thumb:hover,
            #daftar-kelas .table-responsive::-webkit-scrollbar-thumb:hover {
                background-color: #64748b;
            }
        }

        /* ==========================================================================
           PAGINASI TEKS MURNI BERSAMA (Single Source of Truth)
           Susunan "‹ Sebelumnya  1 2 3 4 5  Berikutnya ›" + info "Menampilkan ...".
           Tanpa container/border/shadow/pill; halaman aktif biru tebal bergaris
           bawah tipis; "Sebelumnya/Berikutnya" redup di ujung.

           Blok ini adalah SUMBER BERSAMA untuk halaman yang paginasinya berada
           DI DALAM area scroll tabel: Catatan Kehadiran, Rekap, dan Data Siswa.
           Catatan Kehadiran & Rekap masih membawa salinan nilai yang sama di
           push "styles" masing-masing (keduanya identik, jadi tidak ada gaya
           yang berubah); Data Siswa memakai blok bersama ini tanpa salinan.
           (Sengaja ditulis tanpa "@" supaya Blade tidak mengompilasinya.)
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
            border-radius: var(--clean-radius) !important;
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
        /* ==========================================================================
           TEMA DIALOG BERSAMA (SEMUA DIALOG HAPUS + LOG OUT)

           SATU sumber gaya untuk:
             - dialog hapus satuan  (Siswa, Guru, Kelas, Hari Libur, Tahun Ajaran,
                                    Tempat Sampah, dan halaman lain)
             - dialog hapus massal ("Hapus Seluruh Data ...")
             - dialog konfirmasi Log Out (#logoutConfirmModal)

           Semua dialog memakai kelas yang sama persis, sehingga tidak ada
           copy-paste CSS per halaman. Warna diambil dari palet yang sudah
           dipakai aplikasi (merah #dc2626, merah tua #b91c1c, abu #f1f5f9)
           - tidak ada warna baru.

           Peringatan: selector di bawah sengaja tidak memakai karakter '<'
           atau '>' agar aman bila class ini ikut disalin ke dalam string
           JavaScript (tidak memicu parser HTML).
        ========================================================================== */
        :root {
            --app-dialog-red: #dc2626;
            --app-dialog-red-dark: #b91c1c;
            --app-dialog-red-soft: #fee2e2;
            /* Aksen biru untuk dialog NON-hapus (mis. "Aktifkan Tahun Ajaran?").
               Nilainya persis sama dengan palet biru yang sudah dipakai aplikasi
               (tombol btn-primary / kartu dashboard), jadi tidak ada warna baru. */
            --app-dialog-blue: #2563eb;
            --app-dialog-blue-dark: #1d4ed8;
            --app-dialog-blue-soft: #dbeafe;
            --app-dialog-gray-bg: #f8fafc;
            --app-dialog-gray-border: #e2e8f0;
            --app-dialog-gray-text: #64748b;
            --app-dialog-gray-btn: #f1f5f9;
            --app-dialog-gray-btn-hover: #e2e8f0;
            --app-dialog-text: #1e293b;
        }

        /* ---------- OVERLAY: gelap semi transparan + blur ringan ----------
           NILAI WARNA + BLUR DI SINI ADALAH ACUAN TEMA SELURUH APLIKASI.
           .modal-backdrop (semua modal Bootstrap, termasuk Log Out) dan
           #logoutConfirmModal sengaja disamakan persis dengan nilai di sini.
           Ubah overlay semua dialog HANYA di blok ini.

           Overlay TIDAK BERANIMASI (sengaja):
           - .swal2-container diberi .swal2-show oleh SweetAlert2, dan
             bawaan SweetAlert2 untuk kelas itu adalah
                @keyframes swal2-show { transform: scale(0.7 -> 1.05 -> 1) }
                animation: swal2-show 0.3s
             yaitu "muncul pelan" + pantulan skala. Ini dihapus supaya
             overlay muncul seketika, persis seperti yang diminta.
           - transition juga dihapus agar tidak ada penundaan apa pun. */
        .swal2-container,
        .swal2-backdrop {
            backdrop-filter: blur(4px) !important;
            -webkit-backdrop-filter: blur(4px) !important;
            animation: none !important;
            transition: none !important;
        }

        .swal2-backdrop {
            background: rgba(15, 23, 42, 0.55) !important;
        }

        /* ---------- KARTU DIALOG (KECIL & PADAT) ---------- */
        .swal2-popup.app-dialog {
            max-width: 440px !important;
            width: min(calc(100vw - 44px), 440px) !important;
            margin: 16px auto !important;
            padding: 24px !important;
            border-radius: 20px !important;
            border: 0 !important;
            background: #ffffff !important;
            color: var(--app-dialog-text) !important;
            box-shadow: 0 18px 36px -12px rgba(15, 23, 42, 0.22) !important;
            overflow: hidden !important;
            /* Isi boleh scroll di dalam kartu, tombol tetap terlihat. */
            display: flex !important;
            flex-direction: column !important;
            max-height: calc(100vh - 32px) !important;
            max-height: calc(100dvh - 32px) !important;
        }

        .swal2-popup.app-dialog .swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
        }

        /* ---------- IKON TANPA BLOK LATAR (satu gaya semua dialog) ----------
           Lingkaran merah-muda dihapus: tampil hanya ikonnya (merah untuk
           hapus/log out, biru untuk dialog primary), diperbesar 40px. */
        .app-dialog-icon {
            width: auto !important;
            height: auto !important;
            border-radius: 0 !important;
            background-color: transparent !important;
            color: var(--app-dialog-red) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin: 0 auto 10px !important;
            flex-shrink: 0 !important;
        }

        .app-dialog-icon i {
            font-size: 40px !important;
            line-height: 1 !important;
        }

        .app-dialog-title {
            font-size: 18px !important;
            font-weight: 700 !important;
            line-height: 1.3 !important;
            color: var(--app-dialog-text) !important;
            text-align: center !important;
            margin: 0 0 6px !important;
            text-transform: none !important;
        }

        .app-dialog-text {
            font-size: 14px !important;
            line-height: 1.45 !important;
            color: var(--app-dialog-text) !important;
            text-align: center !important;
            margin: 0 !important;
        }

        .app-dialog-text strong {
            color: var(--app-dialog-text) !important;
            font-weight: 700 !important;
        }

        .app-dialog-sub {
            font-size: 13px !important;
            line-height: 1.4 !important;
            color: #334155 !important;
            text-align: center !important;
            margin: 6px 0 0 !important;
        }

        /* Teks abu Bootstrap di dalam modal (paragraf Cetak Kartu,
           keterangan Import): naikkan ke #334155 (warna label form
           yang sudah dipakai situs), bukan abu pucat. */
        .modal-content .text-secondary {
            color: #334155 !important;
        }

        /* ---------- CHECKBOX KONFIRMASI (TANPA KARTU / BORDER / BACKGROUND) ----------
           Cukup baris biasa: checkbox 18px di kiri + teks 13px rata kiri.
           Label membungkus checkbox sehingga TEKS maupun KOTAKnya bisa diklik
           (klik di area teks akan mencentang checkbox). */
        .app-dialog-checks {
            display: flex !important;
            flex-direction: column !important;
            gap: 10px !important;
            margin: 14px 0 0 !important;
            padding: 0 !important;
            border: 0 !important;
            background: transparent !important;
            text-align: left !important;
        }

        .app-dialog-check {
            display: flex !important;
            align-items: flex-start !important;
            gap: 10px !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            cursor: pointer !important;
            font-size: 13px !important;
            line-height: 1.45 !important;
            color: var(--app-dialog-text) !important;
            text-align: left !important;
            transition: color 0.1s ease !important;
        }

        /* Border abu-abu gelap + merah saat dicentang, dan tetap jelas
           di atas background apa pun (tidak memakai opacity tipis). */
        .app-dialog-check input[type="checkbox"] {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            flex: 0 0 auto !important;
            margin: 1px 0 0 !important;
            padding: 0 !important;
            accent-color: var(--app-dialog-red) !important;
            cursor: pointer !important;
        }

        .app-dialog-check:hover,
        .app-dialog-check:hover span {
            color: var(--app-dialog-red) !important;
        }

        /* ---------- TOMBOL (BERDAMPINGAN, TINGGI 42px) ---------- */
        .swal2-popup.app-dialog .swal2-actions {
            width: 100% !important;
            margin: 16px 0 0 !important;
            padding: 0 !important;
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            gap: 10px !important;
            flex: 0 0 auto !important;
        }

        .swal2-popup.app-dialog .swal2-actions .btn {
            flex: 1 1 50% !important;
            width: 50% !important;
            height: 42px !important;
            min-height: 42px !important;
            padding: 0 10px !important;
            margin: 0 !important;
            border: 1px solid transparent !important;
            border-radius: var(--clean-radius) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
            text-transform: none !important;
            letter-spacing: 0 !important;
            white-space: nowrap !important;
            cursor: pointer !important;
            transition: background-color 0.1s ease, border-color 0.1s ease, color 0.1s ease !important;
        }

        /* Kiri: "Tidak" / "Batal" - abu-abu terang, teks gelap. */
        .swal2-popup.app-dialog .swal2-actions .btn-light {
            background-color: var(--app-dialog-gray-btn) !important;
            border-color: var(--app-dialog-gray-border) !important;
            color: var(--app-dialog-text) !important;
        }

        .swal2-popup.app-dialog .swal2-actions .btn-light:hover {
            background-color: var(--app-dialog-gray-btn-hover) !important;
            color: var(--app-dialog-text) !important;
        }

        /* Kanan: "Hapus" / "Hapus Semua" / "Ya, Log Out" - merah solid. */
        .swal2-popup.app-dialog .swal2-actions .btn-danger {
            background-color: var(--app-dialog-red) !important;
            border-color: var(--app-dialog-red) !important;
            color: #ffffff !important;
        }

        .swal2-popup.app-dialog .swal2-actions .btn-danger:hover {
            background-color: var(--app-dialog-red-dark) !important;
            border-color: var(--app-dialog-red-dark) !important;
            color: #ffffff !important;
        }

        /* Tombol merah yang BELUM aktif (checkbox belum dicentang). */
        .swal2-popup.app-dialog .swal2-actions .btn-danger:disabled,
        .swal2-popup.app-dialog .swal2-actions .btn-danger.app-dialog-btn-disabled {
            background-color: #fca5a5 !important;
            border-color: #fca5a5 !important;
            color: #ffffff !important;
            opacity: 0.85 !important;
            cursor: not-allowed !important;
        }

        /* ---------- VARIAN BIRU: DIALOG NON-HAPUS ----------
           Dialog yang BUKAN penghapusan (mis. "Aktifkan Tahun Ajaran?") memakai
           kartu, overlay, ikon, judul, deskripsi, dan tombol yang PERSIS SAMA
           dengan dialog hapus; yang dibedakan HANYA warna aksennya (biru, bukan
           merah) lewat class .app-dialog-primary pada popup.
           Aktif hanya bila confirmUniversalDelete() dipanggil dengan
           tone: 'primary'. Semua halaman hapus lain tidak memakai class ini,
           jadi tampilannya tidak berubah sama sekali. */
        .swal2-popup.app-dialog.app-dialog-primary .app-dialog-icon {
            background-color: transparent !important;
            color: var(--app-dialog-blue) !important;
        }

        .swal2-popup.app-dialog.app-dialog-primary .app-dialog-check input[type="checkbox"] {
            accent-color: var(--app-dialog-blue) !important;
        }

        .swal2-popup.app-dialog.app-dialog-primary .app-dialog-check:hover,
        .swal2-popup.app-dialog.app-dialog-primary .app-dialog-check:hover span {
            color: var(--app-dialog-blue) !important;
        }

        /* Tombol konfirmasi biru solid. Menimpa .btn-primary bawaan Bootstrap
           (yang warnanya sedikit lebih terang) supaya sama persis dengan
           kartu & tombol biru lain di aplikasi. */
        .swal2-popup.app-dialog.app-dialog-primary .swal2-actions .app-dialog-confirm {
            background-color: var(--app-dialog-blue) !important;
            border-color: var(--app-dialog-blue) !important;
            color: #ffffff !important;
        }

        .swal2-popup.app-dialog.app-dialog-primary .swal2-actions .app-dialog-confirm:hover {
            background-color: var(--app-dialog-blue-dark) !important;
            border-color: var(--app-dialog-blue-dark) !important;
            color: #ffffff !important;
        }

        /* ---------- ANIMASI KARTU: LANGSUNG MUNCUL, LANGSUNG HILANG ----------
           Semua dialog memakai aturan yang SAMA persis (hapus, aktifkan,
           pulihkan, Log Out, tambah, edit, import, cetak): overlay tanpa
           animasi, kartu tanpa scale / tanpa geser / tanpa transisi.

          (scale 0.95 -> 1 + translateY 6px) + keyframe appDialogIn 0.15s
           dihapus karena:
             1. memakai keyframe 0.15s, sehingga terasa "muncul pelan";
             2. SweetAlert2 menambahkan .swal2-show saat membuka dan
                .swal2-hide saat menutup, dan dia menunggu animasi yang
                sedang berjalan sebelum menutup dialog. Selama animasi
                kartu masih hidup, kartu ikut tertahan - tidak konsisten
                dengan overlay yang sekarang muncul seketika;
             3. scale membuat ukuran kartu BERBEDA dari kartu modal
                Bootstrap yang tidak di-scale sama sekali, sehingga
                keduanya terlihat tidak sama padahal harus sama persis.

           Batal / klik di luar / Esc -> kartu langsung hilang. */
        .swal2-popup.app-dialog {
            animation: none !important;
            transition: none !important;
            transform: none !important;
            opacity: 1 !important;
        }

        /* Class yang dipakai SweetAlert2 saat menutup adalah .swal2-hide pada popup. */
        .swal2-popup.app-dialog.swal2-hide {
            animation: none !important;
            transition: none !important;
        }

        /* ---------- MOBILE: LEBIH KECIL, RAPI, TETAP PROFESIONAL ----------
           Semua angka di bawah 1px lebih kecil dari desktop supaya dialog
           terasa ringan di layar HP, TAPI struktur & perilakunya tidak berubah:
           tombol tetap berdampingan 50/50 (tidak menumpuk), overlay tetap blur,
           dan isi tetap bisa scroll di dalam kartu saat layar pendek. */
        @media (max-width: 575.98px) {
            .swal2-popup.app-dialog {
                /* Lebar seragam final, margin kiri-kanan 22px. */
                width: min(calc(100vw - 44px), 400px) !important;
                max-width: 400px !important;
                margin: 22px auto !important;
                padding: 20px !important;
                border-radius: 18px !important;
                /* Layar pendek: max 85dvh, isi scroll di dalam kartu. */
                max-height: 85vh !important;
                max-height: 85dvh !important;
            }

            /* Lingkaran ikon 40px mengikuti ikon tanpa blok. */
            .app-dialog-icon {
                width: auto !important;
                height: auto !important;
                margin: 0 auto 8px !important;
            }

            .app-dialog-icon i {
                font-size: 36px !important;
            }

            /* Judul 16px (desktop 18px), jarak ke deskripsi 5px. */
            .app-dialog-title {
                font-size: 16px !important;
                line-height: 1.3 !important;
                margin: 0 0 5px !important;
            }

            /* Deskripsi 13px (desktop 14px). */
            .app-dialog-text {
                font-size: 13px !important;
                line-height: 1.4 !important;
            }

            /* Baris penjelas kecil 12px. */
            .app-dialog-sub {
                font-size: 12px !important;
                line-height: 1.4 !important;
                margin: 5px 0 0 !important;
            }

            /* Checkbox tetap POLOS (tanpa kartu/border/background), cuma lebih
               rapat: jarak antar baris 8px, jarak ke isi 11px. */
            .app-dialog-checks {
                gap: 8px !important;
                margin: 11px 0 0 !important;
            }

            .app-dialog-check {
                gap: 8px !important;
                font-size: 12.5px !important;
                line-height: 1.4 !important;
            }

            .app-dialog-check input[type="checkbox"] {
                width: 16px !important;
                height: 16px !important;
                min-width: 16px !important;
                margin: 0 !important;
            }

            /* Jarak isi -> tombol 12px (desktop 16px), jarak antar tombol 10px
               (SAMA dengan desktop, tidak ikut mengecil). */
            .swal2-popup.app-dialog .swal2-actions {
                margin: 12px 0 0 !important;
                gap: 10px !important;
            }

            /* Tombol tetap 50/50, tinggi 40px (desktop 42px), font 13px,
               radius tetap 10px. */
            .swal2-popup.app-dialog .swal2-actions .btn {
                flex: 1 1 50% !important;
                width: 50% !important;
                height: 40px !important;
                min-height: 40px !important;
                font-size: 13px !important;
                padding: 0 6px !important;
                border-radius: var(--clean-radius) !important;
            }
        }

        /* ==========================================================================
           TEMA MODAL FORM BERSAMA (Bootstrap .modal)

           Berlaku untuk SEMUA modal yang sudah ada di project: Tambah/
           Edit Siswa, Guru, Kelas, Tahun Ajaran, Hari Libur, Import Excel,
           Cetak Kartu, Ubah Status Kehadiran, dan modal Log Out.

           Cakupannya murni CSS di layout bersama - tidak ada satu pun file
           view yang perlu diubah, dan tidak ada file baru.

           Lebar menyesuaikan isi:
             - default (form pendek)  : 480px
             - .modal-lg  (form panjang): 560px + scroll di dalam kartu
           Mobile: margin kiri-kanan minimal 16px.
        ========================================================================== */

        /* ---------- OVERLAY: gelap semi transparan + blur ringan ----------
           NILAI & SUMBER DAYA DISAMAKAN PERSIS DENGAN OVERLAY DIALOG HAPUS
           (SweetAlert2), supaya SEMUA pop up/modal punya efek latar yang sama:
             warna : rgba(15, 23, 42, 0.55)   -> sama dgn .swal2-backdrop
             blur  : 4px                       -> sama dgn .swal2-container

           Kenapa opacity HARUS dipaksa 1 (ini akar masalah "blur tidak
           kelihatan" pada modal Bootstrap):
             Bawaan Bootstrap 5.3:
               .modal-backdrop.show { opacity: var(--bs-backdrop-opacity) }
               --bs-backdrop-opacity: 0.5
           SweetAlert2 TIDAK memakai opacity untuk overlaynya, jadi opacity-nya 1
           dan blur 4px-nya tampil PENUH.

           Jika sebuah elemen punya opacity < 1, hasil komposisinya adalah:
               opacity x (backdrop yang SUDAH di-blur)  +  (1 - opacity) x
               (backdrop ASLI yang MASIH TAJAM)
           Jadi dengan opacity 0.5, separuh gambar di belakang tetap tajam ->
           blur 4px nyaris tak terlihat dan tabel di belakang tetap "bersih".
           Akibatnya juga warnanya jadi 0.55 x 0.5 = 0.275 (jauh lebih terang
           dari 0.55 milik dialog Hapus).

           Solusi: alpha dipindah ke background-color, opacity dipaksa 1.
           Animasi fade-in sengaja TIDAK dipakai lagi (lihat catatan
           "MUNCUL PELAN" di bawah .modal-backdrop): transisi opacity
           pada overlay justru membuat Bootstrap menunggu sebelum dialog
           boleh tampil. Overlay sekarang langsung muncul penuh.
           Catatan: nilai lama "opacity: 0.15s" itu TIDAK VALID CSS (0.15s
           adalah <time>, bukan <number>) sehingga selalu diabaikan browser. */
        .modal-backdrop {
            background-color: rgba(15, 23, 42, 0.55) !important;
            backdrop-filter: blur(4px) !important;
            -webkit-backdrop-filter: blur(4px) !important;

            /* ---------- APAKAH MODAL BOOTSTRAP "MUNCUL PELAN"? ----------
               YA - dan ini akar masalah slow-mo, bukan cuma soal tampilan.
               Modal._showBackdrop() di Bootstrap 5.3 berjalan kira-kira:
                   this._backdrop.show(() => this._showElement(...))
               dan Backdrop.show() memanggil _emulateAnimation() ->
               executeAfterTransition(el, cb, {isAnimated:true}), yaitu
               MENUNGGU transisi .modal-backdrop SELESAI dulu.
               Bawaan .fade memberi transition opacity 0.15s linear, jadi ada
               JEDA MATI +/- 150ms sebelum dialog boleh tampil, lalu overlay
               masih fade 150ms, lalu kartu ikut fade 150ms.
               Total +/- 300ms - inilah "animasi pelan" yang dikeluhkan.

               Karena transisi dihapus total, transition-duration hasil hitung
               Bootstrap = 0ms -> callback dipanggil seketika -> overlay dan
               dialog langsung muncul, tanpa jeda sama sekali. */
            transition: none !important;
            animation: none !important;
        }

        /* Tanpa transisi, tidak ada lagi perlunya state opacity 0 -> 1.
           Overlay langsung tampil PENUH (blur 4px terlihat, darkness 0.55),
           persis sama seperti .swal2-backdrop milik dialog hapus. */
        .modal-backdrop.fade,
        .modal-backdrop.show {
            opacity: 1 !important;
        }

        /* ---------- LEBAR SERAGAM (KEPUTUSAN UKURAN FINAL v2) ----------
           360px sesi lalu terlalu sempit (teks patah, form tinggi-sempit).
           Final: desktop 440px; mobile min(calc(100vw - 44px), 400px)
           (viewport 502px -> 400px; 390px -> 346px). Sempat ditimbang 380
           (masih sempit) dan desktop 420 (kurang lega untuk dua kolom).
           .modal-lg ikut 440px — form dua kolom dilaporkan terpisah. */
        .modal-dialog {
            max-width: 440px !important;
            width: min(calc(100vw - 44px), 440px) !important;
            margin: 16px auto !important;
        }

        .modal-dialog.modal-lg {
            max-width: 440px !important;
        }

        /* ---------- KARTU ---------- */
        .modal-content {
            border: 0 !important;
            border-radius: 20px !important;
            background: #ffffff !important;
            box-shadow: 0 18px 36px -12px rgba(15, 23, 42, 0.22) !important;
            /* Form panjang: max 85dvh, isi scroll DI DALAM kartu, footer
               tetap terlihat. */
            max-height: 85vh !important;
            max-height: 85dvh !important;
            overflow: hidden !important;
            display: flex !important;
            flex-direction: column !important;
        }

        /* <form> anak langsung .modal-content (struktur modal project ini):
           flex item kolom yang boleh menyusut supaya body scroll di dalam
           dan footer tetap terlihat. */
        .modal-content > form {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            margin: 0 !important;
        }

        .modal-content > .modal-body,
        .modal-content > form > .modal-body {
            overflow-y: auto !important;
            min-height: 0 !important;
            flex: 1 1 auto !important;
        }

        /* ---------- HEADER: judul 18px tebal, tanpa ruang kosong berlebihan -- */
        .modal-content > .modal-header,
        .modal-content > form > .modal-header {
            padding: 20px 20px 0 !important;
            flex: 0 0 auto !important;
            border-bottom: 0 !important;
        }

        .modal-content > .modal-header .modal-title,
        .modal-content > form > .modal-header .modal-title {
            font-size: 18px !important;
            font-weight: 700 !important;
            line-height: 1.3 !important;
        }

        /* ---------- FIELD: label 13px gelap, input 40px (seragam mobile) -- */
        .modal-content .form-label,
        .modal-content label {
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #334155 !important;
            line-height: 1.3 !important;
            margin-bottom: 4px !important;
        }

        .modal-content .form-control,
        .modal-content .form-select {
            min-height: 40px !important;
            font-size: 13.5px !important;
            line-height: 1.45 !important;
        }

        /* ---------- BODY: padat 12px 20px, 16px sebelum footer, lh 1.45 -- */
        .modal-content > .modal-body,
        .modal-content > form > .modal-body {
            padding: 12px 20px 16px !important;
            line-height: 1.45 !important;
        }

        /* ---------- FOOTER: tombol berdampingan, sama lebar ---------- */
        .modal-content > .modal-footer,
        .modal-content > form > .modal-footer {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 0 20px 20px !important;
            margin: 0 !important;
            border-top: 0 !important;
            background: transparent !important;
            flex: 0 0 auto !important;
        }

        .modal-content > .modal-footer > *,
        .modal-content > form > .modal-footer > * {
            margin: 0 !important;
        }

        .modal-content > .modal-footer .btn,
        .modal-content > form > .modal-footer .btn,
        .modal-content > .modal-footer .btn-download-blue,
        .modal-content > .modal-footer .btn-download-green,
        .modal-content > form > .modal-footer .btn-download-blue,
        .modal-content > form > .modal-footer .btn-download-green {
            flex: 1 1 50% !important;
            width: 50% !important;
            min-width: 0 !important;
            height: 42px !important;
            min-height: 42px !important;
            border-radius: var(--clean-radius) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            padding: 0 10px !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            line-height: 1.25 !important;
            text-align: center !important;
            text-transform: none !important;
            letter-spacing: 0 !important;
            white-space: nowrap !important;
            transition: background-color 0.1s ease, border-color 0.1s ease, color 0.1s ease !important;
        }

        /* ---------- ANIMASI MODAL: LANGSUNG MUNCUL, TANPA SCALE ----------
           Semua modal di project ini (Tambah/Edit Siswa, Guru, Kelas, Tahun
           Ajaran, Hari Libur, Import Excel, Cetak Kartu, Ubah Status
           Kehadiran, dan Log Out) memakai blok ini, sehingga tidak ada satu
           pun yang lebih lambat atau lebih cepat dari dialog hapus.

          scale 0.95 -> 1 + transition 0.15s dihapus karena:
             1. menambah 150ms-plus sebelum kartu benar-benar tampil;
             2. membuat kartu BootstrapBERBEDA dari kartu dialog hapus
                (yang tidak di-scale);
             3. state "scale(0.95) + opacity:0" bisa tertinggal bila .show
                gagal ditambahkan, membuat dialog tampak hilang/tak berubah.

           Efek yang dipakai: TIDAK ADA (0s). Kalau nanti mau maksimal
           fade 0.1s, cukup ubah "transition: none" di bawah menjadi
           "transition: opacity 0.1s linear" TANPA mengubah nilai opacity. */
        .modal.fade,
        .modal.fade .modal-dialog,
        .modal.fade .modal-content,
        .modal.fade .modal-header,
        .modal.fade .modal-body,
        .modal.fade .modal-footer {
            transition: none !important;
            animation: none !important;
        }

        .modal.fade .modal-dialog,
        .modal.fade.show .modal-dialog {
            transform: none !important;
            opacity: 1 !important;
        }

        /* ---------- MOBILE (<= 768px): POP UP FORM KECIL, RAPI, TEPAT DI TENGAH ----------
           ACUAN UKURAN: blok .swal2-popup.app-dialog mobile di atas (dialog hapus).

           Yang diubah HANYA UKURAN. Isi form, nama field, validasi, dan tombol
           tidak disentuh. Semua aturan dikunci di media query max-width:
           767.98px, jadi DESKTOP >= 769px sama sekali tidak berubah.

           PENTING - STRUKTUR MODAL DI PROJECT INI:
               .modal > .modal-dialog(.modal-dialog-centered[.modal-lg])
                        > .modal-content > .modal-header + <form>
                                                      > .modal-body + .modal-footer
           Jadi <form> adalah anak LANGSUNG .modal-content, bukan .modal-body /
           .modal-footer. Aturan desktop di atas memakai selector ">"
           (mis. .modal-content > .modal-body) sehingga TIDAK ikut kena pada
           modal ini. Supaya aturan mobile di bawah tetap bekerja, selector
           memakai DESCENDANT (.modal-content .modal-body) sehingga keduanya
           aman tanpa perlu menyentuh file view. */
        @media (max-width: 767.98px) {

            /* 1. KARTU: maksimal 340px, margin kiri-kanan minimal 20px,
                  tepat di tengah layar (vertikal & horizontal). */
            .modal-dialog,
            .modal-dialog.modal-lg {
                max-width: 400px !important;
                width: min(calc(100vw - 44px), 400px) !important;
                margin: 22px auto !important;
            }

            /* min-height dikurangi 2 x margin 20px supaya pemusatan vertikal
               presisi dan kartu tidak pernah melebihi tinggi layar. */
            .modal-dialog-centered {
                display: flex !important;
                align-items: center !important;
                min-height: calc(100% - 44px) !important;
            }

            .modal-content {
                width: 100% !important;
                border-radius: 18px !important;
                /* Isi panjang (Tambah/Edit Siswa & Guru): maksimal 85vh, isi
                   scroll DI DALAM kartu, header & tombol tetap terlihat. */
                max-height: 85vh !important;
                max-height: 85dvh !important;
                overflow: hidden !important;
            }

            /* <form> jadi flex item yang boleh menyusut. Tanpa ini isi form
               yang panjang terpotong oleh overflow:hidden pada .modal-content
               dan tidak bisa di-scroll sama sekali. */
            .modal-content > form {
                display: flex !important;
                flex-direction: column !important;
                flex: 1 1 auto !important;
                min-height: 0 !important;
                margin: 0 !important;
            }

            /* 2. HEADER: judul 16px tebal, tombol X 28px dan redup. */
            .modal-content > .modal-header,
            .modal-content > form > .modal-header {
                flex: 0 0 auto !important;
                padding: 20px 20px 0 !important;
            }

            /* Judul 16px tebal. Sebagian modal memakai <h5 class="fw-bold">
               TANPA class .modal-title, jadi selectornya mencakup heading
               apa pun di dalam .modal-header agar semua ikut 16px. */
            .modal-content .modal-title,
            .modal-content .modal-header h1,
            .modal-content .modal-header h2,
            .modal-content .modal-header h3,
            .modal-content .modal-header h4,
            .modal-content .modal-header h5,
            .modal-content .modal-header h6 {
                font-size: 16px !important;
                font-weight: 700 !important;
                line-height: 1.3 !important;
                margin-bottom: 0 !important;
                /* Jarak aman dari tombol X di kanan untuk judul panjang. */
                padding-right: 24px !important;
            }

            .modal-content .btn-close {
                width: 28px !important;
                height: 28px !important;
                min-width: 28px !important;
                padding: 5px !important;
                margin: -3px -3px 0 auto !important;
                flex-shrink: 0 !important;
                opacity: 0.45 !important;
                background-size: 11px 11px !important;
                border-radius: 8px !important;
            }

            .modal-content .btn-close:hover,
            .modal-content .btn-close:focus-visible {
                opacity: 0.85 !important;
            }

            /* 3. BODY: padding 18px, scroll di dalam (scrollbar-nya
                  disembunyikan oleh blok "TIDAK ADA SCROLLBAR MOBILE" di
                  bawah). */
            .modal-content .modal-body {
                flex: 1 1 auto !important;
                min-height: 0 !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                -webkit-overflow-scrolling: touch !important;
                overscroll-behavior: contain !important;
                padding: 14px 20px 16px !important;
                font-size: 13.5px !important;
                line-height: 1.45 !important;
            }

            /* 4. FOOTER: tombol berdampingan 50/50, tinggi 40px, font 13px,
                  radius 10px, gap 10px, tidak all-caps & tidak membesar. */
            .modal-content .modal-footer {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                align-items: center !important;
                gap: 10px !important;
                flex: 0 0 auto !important;
                padding: 0 20px 20px !important;
                margin: 0 !important;
                border-top: 0 !important;
            }

            .modal-content .modal-footer > * {
                margin: 0 !important;
            }

            .modal-content .modal-footer .btn {
                flex: 1 1 50% !important;
                width: 50% !important;
                min-width: 0 !important;
                height: 40px !important;
                min-height: 40px !important;
                border-radius: var(--clean-radius) !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 6px !important;
                padding: 0 8px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                line-height: 1.25 !important;
                text-align: center !important;
                text-transform: none !important;
                letter-spacing: 0 !important;
                white-space: nowrap !important;
            }

            /* Tombol unduh/import di footer (tanpa class .btn) disamakan:
               50/50, tinggi 40px, satu baris. Warna & fungsi tidak berubah. */
            .modal-content .modal-footer .btn-download-blue,
            .modal-content .modal-footer .btn-download-green {
                flex: 1 1 50% !important;
                width: 50% !important;
                min-width: 0 !important;
                height: 40px !important;
                min-height: 40px !important;
                border-radius: var(--clean-radius) !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 6px !important;
                padding: 0 8px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                line-height: 1.25 !important;
                text-align: center !important;
                white-space: nowrap !important;
            }

            /* 5. FIELD: label 13px tebal gelap, input 40px & font 13px. */
            .modal-content .form-label,
            .modal-content label {
                font-size: 13px !important;
                font-weight: 600 !important;
                color: #334155 !important;
                line-height: 1.3 !important;
                margin-bottom: 4px !important;
            }

            .modal-content .form-control,
            .modal-content .form-select {
                height: 40px !important;
                min-height: 40px !important;
                font-size: 13px !important;
                line-height: 1.35 !important;
                padding: 6px 10px !important;
                border-radius: var(--clean-radius) !important;
            }

            /* Textarea Alamat: pendek (~2-3 baris), tidak memanjangkan form. */
            .modal-content textarea.form-control {
                height: auto !important;
                min-height: 64px !important;
                padding: 8px 10px !important;
            }

            .modal-content .input-group-text {
                font-size: 13px !important;
                padding: 6px 10px !important;
            }

            /* Jarak antar field 10-12px (tidak ada ruang kosong besar). Hanya sumbu
               VERTIKAL yang dikecilkan; sumbu horizontal dibiarkan mengikuti
               gutter milik halaman (g-2/g-3) supaya dua kolom tetap cukup
               lebar di kartu 340px. */
            .modal-content .row {
                --bs-gutter-y: 0.75rem !important;
            }

            .modal-content .mb-4 { margin-bottom: 10px !important; }
            .modal-content .mb-3 { margin-bottom: 10px !important; }
            .modal-content .mb-2 { margin-bottom: 8px !important; }
            .modal-content .mt-2 { margin-top: 8px !important; }
            .modal-content .form-text { font-size: 11.5px !important; margin-top: 3px !important; }
            .modal-content .invalid-feedback { font-size: 11.5px !important; margin-top: 3px !important; }

            /* 6. RADIO & CHECKBOX 16px, teks 13px. */
            .modal-content .form-check {
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                padding-left: 0 !important;
                margin-bottom: 8px !important;
            }

            .modal-content .form-check-input {
                position: static !important;
                flex: 0 0 auto !important;
                width: 16px !important;
                height: 16px !important;
                min-width: 16px !important;
                margin: 0 !important;
            }

            .modal-content .form-check-label {
                font-size: 13px !important;
                line-height: 1.35 !important;
                margin: 0 !important;
            }

            /* 7. KOTAK INFO (biru muda / hijau muda / merah muda):
                  padding 10px, font 12px. */
            .modal-content .alert {
                padding: 10px 12px !important;
                font-size: 12px !important;
                line-height: 1.4 !important;
                margin-bottom: 10px !important;
                border-radius: 10px !important;
            }

            .modal-content .alert i {
                font-size: 1rem !important;
                line-height: 1 !important;
            }

            /* Kotak info yang bukan .alert di modal Import Excel
               (kelas "p-3 bg-white border rounded-3 small text-secondary" pada
               modal Siswa/Kelas/Hari Libur) ikut diperkecil agar konsisten
               dengan kotak info di atas.

               Catatan perbaikan: selector lama memakai `.bg-light` sehingga
               TIDAK PERNAH match (keempat modal Import Excel memakai `bg-white`),
               jadi aturan ini sebelumnya mati total di HP. `bg-white` ditambahkan
               tanpa menghapus `bg-light` agar modal lama yang masih memakai
               kelas tersebut tetap terkena. */
            .modal-content .p-3.bg-light.rounded-3,
            .modal-content .p-3.bg-white.rounded-3 {
                padding: 10px 12px !important;
                font-size: 12px !important;
                line-height: 1.4 !important;
                margin-bottom: 10px !important;
                border-radius: 10px !important;
            }
        }

        /* ==========================================================================
           TIDAK ADA SCROLLBAR DI MOBILE (<= 768px) - SEMUA HALAMAN

           Satu sumber gaya untuk SELURUH aplikasi: html, body, kotak putih
           utama, tabel, pop up/modal, dropdown, sidebar, dan semua container
           lain yang bisa scroll (Dashboard, Presensi, Kehadiran, Rekap, Siswa,
           Guru, Kelas, Pengaturan, Tempat Sampah, Tahun Ajaran, Hari Libur, dll).

           Yang disembunyikan HANYA tampilan scrollbar. Fungsi scroll tetap
           berjalan penuh:
             - geser (swipe) sentuhan untuk vertikal & horizontal,
             - scroll wheel / trackpad tetap bekerja,
             - min-width tabel dan text-overflow tidak berubah.
           Tidak ada nilai overflow yang diubah, jadi tidak ada elemen yang
           tadinya bisa digeser menjadi tidak bisa.

           DESKTOP (>= 769px) tidak tersentuh: blok ini hanya ada di media query
           mobile. Aturan scrollbar tabel yang sudah ada (blok "TABEL DATA:
           SCROLL VERTIKAL...") juga tidak diubah.
           ========================================================================== */
        @media (max-width: 767.98px) {
            html,
            body,
            *,
            *::before,
            *::after {
                scrollbar-width: none !important;
                -ms-overflow-style: none !important;
            }

            html::-webkit-scrollbar,
            body::-webkit-scrollbar,
            *::-webkit-scrollbar {
                display: none !important;
                width: 0 !important;
                height: 0 !important;
            }
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
            border-radius: var(--clean-radius) !important;
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
            /* Margin kiri-kanan minimal 16px di layar kecil (mobile). */
            width: calc(100% - 32px) !important;
            /* Lebar seragam final 440px seperti modal lain. */
            max-width: 440px !important;
            margin: 16px auto !important;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        #logoutConfirmModal .modal-content {
            width: 100%;
            max-width: 440px !important;
            pointer-events: auto;
            /* Kartu dialog: tema sama persis dengan dialog hapus (Swal):
               380px / radius 20px / padding 24px / shadow lembut. Padding
               ditaruh pada KARTU (bukan pada .modal-body yang sudah dilepas). */
            padding: 24px !important;
            border: 0 !important;
            border-radius: 20px !important;
            background: #ffffff !important;
            overflow: hidden !important;
            box-shadow: 0 18px 36px -12px rgba(15, 23, 42, 0.22) !important;
        }

        #logoutConfirmModal .modal-body {
            text-align: center;
            /* Isi boleh scroll di dalam kartu; tombol tetap terlihat. */
            overflow-y: auto !important;
            max-height: calc(100vh - 180px) !important;
        }

        /* Dua tombol: BERDAMPINGAN 50%/50% (TIDAK menumpuk ke bawah), tinggi
           42px, radius 10px, jarak 10px - sama persis dengan dialog hapus.
           flex-wrap: nowrap WAJIB: bawaan Bootstrap .modal-footer memakai
           flex-wrap: wrap, sehingga dua tombol 50% + gap 10px (total 110%)
           membungkus ke baris bawah. border-top: 0 + background transparan
           menghapus garis pemisah tipis bawaan Bootstrap di atas tombol Batal. */
        #logoutConfirmModal .modal-footer {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 10px !important;
            margin: 16px 0 0 !important;
            padding: 0 !important;
            border-top: 0 !important;
            background: transparent !important;
        }

        #logoutConfirmModal .modal-footer > * {
            margin: 0 !important;
        }

        #logoutConfirmModal .modal-footer .btn {
            flex: 1 1 50% !important;
            width: 50% !important;
            min-width: 0 !important;
            height: 42px !important;
            min-height: 42px !important;
            border-radius: var(--clean-radius) !important;
            border: 1px solid transparent !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            padding: 0 10px !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            line-height: 1.25 !important;
            text-align: center !important;
            text-transform: none !important;
            letter-spacing: 0 !important;
            white-space: nowrap !important;
            transition: background-color 0.1s ease, border-color 0.1s ease, color 0.1s ease !important;
        }

        #logoutConfirmModal .modal-footer .btn i {
            font-size: 1rem !important;
            line-height: 1 !important;
        }

        /* ---------- OVERLAY LOG OUT: WARNA + BLUR PERSIS SEPERTI DIALOG HAPUS ----------
           AKAR MASALAH "LATAR GELAP HAMPIR HITAM":
           area gelap Log Out TERNYATA digambar DUA LAPIS, bukan satu:

             lapis 1 = .modal-backdrop (SAUDARA modal, dibuat Bootstrap),
                       sudah di-style persis seperti overlay dialog hapus:
                       rgba(15,23,42,0.55) + blur(4px)
             lapis 2 = #logoutConfirmModal itu sendiri, yang SEBELUMNYA
                       juga diberi background gelap + blur(4px) yang sama

           Dua lapis rgba(15,23,42,0.55) bertumpuk menjadi:
               0.55 + (1 - 0.55) x 0.55 = 0.55 + 0.2475 = 0.7975
               -> sekitar 80% hitam  =>  "hampir hitam" seperti dikeluhkan
           DAN karena blur ikut di-override dua kali, blur 4px di modal
           menimpa blur yang sudah ada di backdrop sehingga blur terlihat
           tidak bersih. Dialog hapus (Swal) hanya 1 lapis = 0.55, jauh
           lebih terang - itulah selisihnya.

           PERBAIKAN (di akar masalah, bukan dengan menurunkan warna):
           modal Log Out dibuat TRANSPARAN, jadi overlay_now HANYA berasal
           dari .modal-backdrop. Hasilnya:
             - darkness tepat 0.55, sama persis dialog hapus;
             - blur 4px hanya satu kali terpasang -> tajam & konsisten;
             - tetap berlaku di desktop & mobile karena berasal dari satu
               aturan global, bukan per halaman. */
        #logoutConfirmModal {
            background-color: transparent !important;
            background-image: none !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        /* Z-index tertinggi: di atas sidebar, loader, dan dialog Swal. */
        #logoutConfirmModal {
            z-index: 1000000000 !important;
        }

        /* Fade-out 180ms saat "Ya, Log Out" diklik (termasuk blur backdrop
           yang difade via inline style oleh script alur logout). Tanpa ini,
           aturan global ".modal.fade { transition: none }" membuat modal
           hilang seketika. */
        #logoutConfirmModal.logout-leaving {
            transition: opacity 180ms linear !important;
            opacity: 0 !important;
        }

        /* ---------- ANIMASI LOG OUT: INSTAN (SAMA DENGAN DIALOG HAPUS) ----------
           Pair aturan yang SEBELUMNYA ada di sini justru membuat dialog
           Log Out terasa lambat DAN ukurannya tidak sama dengan dialog hapus:

             #logoutConfirmModal .modal-dialog      { transition: transform .15s }
             #logoutConfirmModal.show .modal-dialog { transform: scale(1) }
             #logoutConfirmModal.fade .modal-dialog { transform: scale(.95) }

           MASALAH specificity: ".show" dan ".fade" di sini punya specificity
           yang SAMA (1 id + 2 class), dan ".fade" ditulis SESUDAH ".show",
           sehingga transform: scale(0.95) SELALU menang walau .show sudah
           ditambahkan -> kartu diam permanen di 95% (kartu Log Out lebih
           kecil dari kartu dialog hapus) PLUS tetap Transitional 0.15s.

           SEMUA pair itu dihapus di sini. Log Out sekarang TIDAK punya aturan
           animasi sendiri: Ia mewarisi blok global ".modal.fade ..." di atas,
           sehingga:
             - overlay: 0s, tanpa transisi (tidak ada jeda executeAfterTransition)
             - kartu  : 0s, tanpa scale, tanpa geser
             - tutup (Batal / klik luar / Esc): 0s, langsung hilang
           Efek yang dipakai TIDAK ADA. Kalau nanti mau fade maksimal 0.1s,
           ubah HANYA blok global ".modal.fade .modal-dialog" di atas. */

        /* Varian dengan class tambahan (belum dipakai halaman mana pun saat ini).
           Nilainya dibuat sama dengan .modal-backdrop di atas - termasuk
           transition: none, supaya tidak ada satu pun overlay yang lebih
           lambat dari dialog hapus. */
        .modal-backdrop.app-dialog-backdrop {
            background-color: rgba(15, 23, 42, 0.55) !important;
            backdrop-filter: blur(4px) !important;
            -webkit-backdrop-filter: blur(4px) !important;
            opacity: 1 !important;
            transition: none !important;
            animation: none !important;
        }

        /* ---------- LOG OUT: AKSEN MERAH (SAMA PERSIS DENGAN DIALOG HAPUS) ----------
           Dialog Log Out memakai tema dialog hapus: overlay (blur 4px), ukuran
           kartu, font, jarak isi, dan baris tombol 50/50 semuanya sama; yang
           berbeda HANYA isi dialog dan ikonnya (bx-log-out). Karena itu aksennya
           juga MERAH - tidak ada warna baru, memakai variabel yang sama dengan
           tombol "Hapus".

           - Lingkaran ikon: TIDAK diberi override warna di sini, jadi memakai
             .app-dialog-icon bersama (latar merah muda lembut #fee2e2 + ikon
             merah #dc2626) yang juga dipakai lingkaran ikon dialog hapus.
             Karena berasal dari aturan yang sama, keduanya dijamin identik.
           - Tombol "Ya, Log Out": merah solid #dc2626, hover merah lebih gelap
             #b91c1c, teks putih - persis tombol "Hapus".
           - Tombol "Batal": abu-abu terang + teks gelap, persis tombol kiri
             dialog hapus. */
        #logoutConfirmModal .modal-footer .btn-danger {
            background-color: var(--app-dialog-red) !important;
            border-color: var(--app-dialog-red) !important;
            color: #ffffff !important;
        }

        #logoutConfirmModal .modal-footer .btn-danger:hover {
            background-color: var(--app-dialog-red-dark) !important;
            border-color: var(--app-dialog-red-dark) !important;
            color: #ffffff !important;
        }

        #logoutConfirmModal .modal-footer .btn-light {
            background-color: var(--app-dialog-gray-btn) !important;
            border-color: var(--app-dialog-gray-border) !important;
            color: var(--app-dialog-text) !important;
        }

        #logoutConfirmModal .modal-footer .btn-light:hover {
            background-color: var(--app-dialog-gray-btn-hover) !important;
            color: var(--app-dialog-text) !important;
        }

        /* ---------- MOBILE: SERAGAM 420px DENGAN MODAL LAIN ----------
           Kartu min(calc(100vw - 64px), 360px) / margin 32px / padding 20px /
           radius 18px, tombol tetap berdampingan 50/50 dengan tinggi 40px +
           font 13px + padding 0 6px, dan jarak isi-ke-tombol 12px.
           Blok ini HARUS diletakkan SETELAH semua aturan #logoutConfirmModal di
           atas (sele crip specificity sama, jadi yang belakangan menang). */
        @media (max-width: 575.98px) {
            #logoutConfirmModal .modal-dialog {
                max-width: 400px !important;
                width: min(calc(100vw - 44px), 400px) !important;
                margin: 22px auto !important;
            }

            #logoutConfirmModal .modal-content {
                padding: 20px !important;
                border-radius: 18px !important;
                /* Layar pendek: tinggi maksimal dikurangi 2x margin 20px. */
                max-height: calc(100vh - 40px) !important;
                max-height: calc(100dvh - 40px) !important;
            }

            #logoutConfirmModal .modal-body {
                max-height: calc(100dvh - 180px) !important;
            }

            #logoutConfirmModal .modal-footer {
                margin: 12px 0 0 !important;
                gap: 10px !important;
            }

            #logoutConfirmModal .modal-footer .btn {
                height: 40px !important;
                min-height: 40px !important;
                font-size: 13px !important;
                padding: 0 6px !important;
            }
        }

        /* ==========================================================================
           CLEAN LOOK (ACUAN: PORTAL MY UNPAM) - BLOK TERPUSAT, PALING BAWAH
           --------------------------------------------------------------------------
           Blok ini sengaja diletakkan PALING BAWAH <style> layout, sedangkan style
           tiap halaman dirender lewat push "styles" SEBELAH ATAS blok ini. Semua
           aturan di bawah memakai !important + spesifisitas yang disesuaikan, jadi
           menang atas aturan abu-abu lama di setiap view TANPA mengubah satu pun
           file view, tanpa file baru, tanpa route, tanpa logika, tanpa data.

           YANG DIUBAH (murni tampilan):
           - abu-abu dekoratif (header tabel, zebra, kartu, pill, kotak keterangan,
             kotak logo, placeholder scanner)  ->  putih + garis tipis.
           - garis pemisah  ->  1px sangat tipis horizontal saja (tanpa garis
             vertikal, tanpa zebra, hover paling ringan).
           - tipografi  ->  judul halaman lebih besar/gelap, subtitle lebih kontras,
             header tabel 0.78rem semibold uppercase, isi tabel 0.875rem (desktop),
             label form lebih gelap.
           - tombol  ->  rata, bold, uppercase, radius 6px, bayangan minimal,
             tinggi seragam per jenis.
           - empty state  ->  ikon kecil + teks dalam satu baris di bawah header.

           YANG TIDAK DISENTUH:
           - teks/label/urutan kolom, route, logika, query, data, perilaku tombol.
           - warna identitas biru (kanvas, sidebar, tombol aksi utama) & font Poppins.
           - warna semantik: hijau Hadir/Detail, oranye Edit/Terlambat, merah
             Hapus/Alfa, biru aksi, pink libur & Minggu, huruf H/T/S/I/A/L,
             header kolom libur (.th-holiday), badge status.
           - modal (ukuran, backdrop, alur Log Out), spinner/loading, beep, kiosk.
           ========================================================================== */

        /* --- Token warna bersih (hanya dipakai blok ini) --- */
        :root {
            --clean-ink: #0f172a;        /* teks gelap: judul & header tabel */
            --clean-ink-soft: #334155;   /* teks sekunder & label */
            --clean-line: #e5e9f0;       /* garis tipis pemisah kartu/header */
            --clean-line-soft: #eef1f6;  /* garis tipis antar baris tabel */
            --clean-hover: #f1f5f9;      /* sorotan hover paling ringan */
            --clean-field: #cbd5e1;      /* garis tipis input */
            /* --- Token LENGKUNG (radius) bersama ---
               SATU nilai untuk search, input form, textarea, dropdown/select,
               dan tombol di seluruh halaman + modal (dipilih 6px karena itulah
               nilai pada halaman acuan Data Siswa/Guru/Kelas). Kontainer (card,
               modal, alert) dan badge/pill/tab TIDAK memakai token ini. */
            --clean-radius: 6px;
        }

        /* ==========================================================================
           1. JUDUL & SUBJUDUL HALAMAN - tebal, gelap, kontras tinggi
           REVISI: ukuran DIPERKECIL ~12% dari nilai sebelumnya
           (title 1.05 / 1.35 / 1.50rem -> 0.92 / 1.19 / 1.32rem;
            subtitle 0.75 / 0.85rem -> 0.66 / 0.75rem). Bobot TIDAK berubah.
           ========================================================================== */
        .header-main-title {
            font-size: 0.92rem !important;
            font-weight: 700 !important;
            color: var(--clean-ink) !important;
            letter-spacing: -0.01em !important;
        }
        @media (min-width: 640px) {
            .header-main-title { font-size: 1.19rem !important; }
        }
        @media (min-width: 1024px) {
            .header-main-title { font-size: 1.32rem !important; }
        }

        .header-main-subtitle {
            color: var(--clean-ink-soft) !important;
            font-size: 0.66rem !important;
            font-weight: 500 !important;
        }
        @media (min-width: 640px) {
            .header-main-subtitle { font-size: 0.75rem !important; }
        }

        /* ==========================================================================
           2. HEADER TABEL - latar putih, teks gelap, uppercase, satu garis tipis
           REVISI: bobot huruf 600 -> 400 (reguler, tidak tebal).
           Berlaku untuk semua tabel data admin: .table-zebra-custom,
           .table-enterprise, .table-matrix, .table-history, .table-years,
           .table-holidays, .matrix-table
           ========================================================================== */
        .table-zebra-custom thead th,
        .table-enterprise thead th,
        .table-matrix thead th,
        .table-history thead th,
        .table-years thead th,
        .table-holidays thead th,
        .matrix-table thead th {
            background-color: #ffffff !important;
            color: var(--clean-ink) !important;
            font-size: 0.78rem !important;
            font-weight: 400 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.04em;
            vertical-align: middle !important;
            padding-top: 0.8rem !important;
            padding-bottom: 0.8rem !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            border-bottom: 1px solid var(--clean-line) !important;
        }

        /* Latar <thead>: putih netral (menimpa bg-light, bg-slate-50, dan aturan
           layout untuk thead tanpa class yang latarnya abu #f8f9fa). */
        .table-responsive > table > thead:not([class]),
        .table-responsive > table > thead.bg-light,
        .table-responsive > table > thead.bg-slate-50,
        thead.bg-light,
        thead.bg-slate-50 {
            background-color: #ffffff !important;
        }

        /* Header sticky ber-ID (Data Siswa & Catatan Kehadiran): latarnya juga
           dibuat putih; spesifisitas aturan view memakai #id, jadi harus disamai. */
        #daftar-siswa .table-responsive > table > thead th,
        #daftar-kehadiran .table-responsive > table > thead th {
            background-color: #ffffff !important;
        }

        /* ==========================================================================
           3. ISI BARIS TABEL - garis horizontal tipis saja, tanpa garis vertikal
           Warna teks & berat huruf TIDAK ditulis di sini supaya warna semantik per
           sel (cell-present, status-hadir, presensi-hari-ini-status, huruf
           H/T/S/I/A/L) tetap bekerja seperti sediakala; semua view sudah
           mengatur teks gelap pada td masing-masing.
           ========================================================================== */
        .table-zebra-custom tbody td,
        .table-enterprise tbody td,
        .table-matrix tbody td,
        .table-history tbody td,
        .table-years tbody td,
        .table-holidays tbody td,
        .matrix-table tbody td {
            padding-top: 0.85rem !important;
            padding-bottom: 0.85rem !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            border-bottom: 1px solid var(--clean-line-soft) !important;
            vertical-align: middle !important;
        }

        /* Ukuran teks isi tabel (desktop saja; mobile memakai ukuran halaman
           masing-masing supaya tabel lebar tidak melebar berlebihan). */
        @media (min-width: 768px) {
            .table-zebra-custom tbody td,
            .table-enterprise tbody td,
            .table-matrix tbody td,
            .table-history tbody td,
            .table-years tbody td,
            .table-holidays tbody td,
            .matrix-table tbody td {
                font-size: 0.875rem !important;
            }
        }

        /* Tanggal & Jam di Riwayat Siswa sengaja lebih besar (tetap dipertahankan) */
        .table-history tbody td.col-tanggal,
        .table-history tbody td.col-jam {
            font-size: 1rem !important;
        }

        /* Sel terakhir .matrix-table tetap bergaris seperti baris lainnya */
        .matrix-table tr:last-child td {
            border-bottom: 1px solid var(--clean-line-soft) !important;
        }

        /* Garis baris .table-matrix dihapus: pemisahnya kini hanya dari sel */
        .table-matrix tbody tr {
            border-bottom: none !important;
        }

        /* ==========================================================================
           4. ZEBRA DIBUAT PUTIH SEMUA + HOVER SANGAT RINGAN
           (menimpa aturan zebra/hover abu #f8fafc, #f1f5f9, #e2e8f0 di tiap view)
           ========================================================================== */
        .table-zebra-custom tbody tr:nth-child(even) > td,
        .table-zebra-custom tbody tr:nth-child(odd) > td,
        .table-zebra-custom tbody tr.baris-abu > td,
        .table-zebra-custom tbody tr.baris-putih > td,
        .table-enterprise tbody tr:nth-child(even) > td,
        .table-enterprise tbody tr:nth-child(odd) > td,
        .table-enterprise tbody tr.baris-abu > td,
        .table-enterprise tbody tr.baris-putih > td,
        .table-matrix tbody tr:nth-child(even) > td,
        .table-matrix tbody tr:nth-child(odd) > td,
        .table-matrix tbody tr.baris-abu > td,
        .table-matrix tbody tr.baris-putih > td,
        .table-history tbody tr:nth-child(even) > td,
        .table-history tbody tr:nth-child(odd) > td,
        .table-years tbody tr:nth-child(even) > td,
        .table-years tbody tr:nth-child(odd) > td,
        .table-years tbody tr.baris-abu > td,
        .table-years tbody tr.baris-putih > td,
        .table-holidays tbody tr:nth-child(even) > td,
        .table-holidays tbody tr:nth-child(odd) > td,
        .table-holidays tbody tr.baris-abu > td,
        .table-holidays tbody tr.baris-putih > td {
            background-color: #ffffff !important;
        }

        .table-zebra-custom tbody tr:hover > td,
        .table-zebra-custom tbody tr.baris-abu:hover > td,
        .table-zebra-custom tbody tr.baris-putih:hover > td,
        .table-enterprise tbody tr:hover > td,
        .table-enterprise tbody tr.baris-abu:hover > td,
        .table-enterprise tbody tr.baris-putih:hover > td,
        .table-matrix tbody tr:hover > td,
        .table-matrix tbody tr.baris-abu:hover > td,
        .table-matrix tbody tr.baris-putih:hover > td,
        .table-history tbody tr:hover > td,
        .table-years tbody tr:hover > td,
        .table-years tbody tr.baris-abu:hover > td,
        .table-years tbody tr.baris-putih:hover > td,
        .table-holidays tbody tr:hover > td,
        .table-holidays tbody tr.baris-abu:hover > td,
        .table-holidays tbody tr.baris-putih:hover > td,
        .matrix-table tbody tr:hover > td {
            background-color: var(--clean-hover) !important;
        }

        /* ==========================================================================
           5. EMPTY STATE - SATU komponen bersama untuk SEMUA baris & panel kosong
           (siswa, guru, kelas, kehadiran, rekap, tempat sampah, tahun ajaran,
           hari libur, absensi kelas, dashboard).

           - Ikon peringatan SEGITIGA (bx-error: segitiga + tanda seru) berdiri di
             ATAS teks, teks di BAWAHnya, rata tengah dan rapat.
           - Warna ikon = token teks gelap tabel yang sudah ada (--clean-ink
             #0f172a), BUKAN kuning/oranye, dan TIDAK ada warna baru yang
             di-hard-code.
           - Tanpa garis/border tambahan pada baris kosong.
           - Isi teks tiap halaman TIDAK diubah, hanya tata letak & ikonnya.
           - font-size teks sengaja TIDAK ditulis di sini: ukurannya akan selalu
             ikut persis ukuran teks isi tabel (0.875rem di desktop, ukuran
             halaman masing-masing di mobile lewat aturan per tabel di atas).
           - .empty-state = class penanda bersama (dipakai di <td> maupun <div>).
             Selector td[colspan] lama tetap dipertahankan sebagai jaring
             pengaman untuk halaman yang belum ikut memakai class tersebut.
           ========================================================================== */
        .table-zebra-custom tbody td[colspan],
        .table-enterprise tbody td[colspan],
        .table-matrix tbody td[colspan],
        .table-history tbody td[colspan],
        .table-years tbody td[colspan],
        .table-holidays tbody td[colspan],
        .matrix-table tbody td[colspan],
        tbody td.empty-state {
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            text-align: center !important;
            vertical-align: middle !important;
            white-space: normal !important;
            background-color: #ffffff !important;
            border-top: none !important;
            border-right: none !important;
            border-bottom: none !important;
            border-left: none !important;
            color: var(--clean-ink-soft) !important;
        }

        .table-zebra-custom tbody td[colspan] > i,
        .table-enterprise tbody td[colspan] > i,
        .table-matrix tbody td[colspan] > i,
        .table-history tbody td[colspan] > i,
        .table-years tbody td[colspan] > i,
        .table-holidays tbody td[colspan] > i,
        .matrix-table tbody td[colspan] > i,
        .empty-state > i {
            display: block !important;
            font-size: 2rem !important;   /* 32px - ikon di atas, teks di bawah */
            line-height: 1 !important;
            color: var(--clean-ink) !important;   /* hitam: teks gelap tabel */
            margin: 0 0 0.4rem !important;        /* jarak ikon -> teks rapat */
            opacity: 1 !important;
            vertical-align: middle !important;
        }

        /* ==========================================================================
           6. TOMBOL - rata, bold, uppercase, radius kecil, bayangan minimal
           Warna tombol (hijau, oranye Edit, merah Hapus, biru aksi) TIDAK berubah;
           hanya bentuk, ukuran huruf, dan bayangannya.
           ========================================================================== */
        /* Tombol pill di bar aksi (Import / Cetak / Tambah) */
        .btn-solid-pill,
        button.btn-solid-pill,
        a.btn-solid-pill {
            font-size: 0.78rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.03em;
            border-radius: var(--clean-radius) !important;
            box-shadow: none !important;
        }

        /* Tombol aksi per baris (Edit / Hapus / Pulihkan) - satu ukuran seragam */
        .btn-row-action,
        .btn-restore,
        .btn-force-delete,
        .crud-center-wrapper .btn,
        .crud-center-wrapper .btn-row-action {
            height: 32px !important;
            min-height: 32px !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            line-height: 1;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 0.65rem !important;
            padding-right: 0.65rem !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.03em;
            border-radius: var(--clean-radius) !important;
            box-shadow: none !important;
            white-space: nowrap;
        }

        /* Tombol header (Excel / PDF / Import / Tambah) - tinggi & huruf seragam */
        .btn-green-excel,
        .btn-red-pdf,
        .btn-add-holiday,
        .btn-add-year {
            height: 38px !important;
            min-height: 38px !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
            font-size: 0.78rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.03em;
            border-radius: var(--clean-radius) !important;
            box-shadow: none !important;
            white-space: nowrap;
        }

        /* Tombol scanner & buka kelas (Presensi Hari Ini) - satu gaya */
        .btn-portal-action,
        .btn-buka-kelas {
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.03em;
            padding-top: 0.4rem !important;
            padding-bottom: 0.4rem !important;
            padding-left: 0.65rem !important;
            padding-right: 0.65rem !important;
            border-radius: var(--clean-radius) !important;
            box-shadow: none !important;
            line-height: 1.25;
        }
        .btn-buka-kelas {
            height: 34px !important;
            min-height: 34px !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
        }
        /* Tombol BUKA/TUTUP SCANNER (Presensi Kelas) - disamakan dgn tombol scanner di atas.
           Selector #id sengaja dipakai agar menang atas gaya view & inline style. */
        #btnToggleScanner.btn {
            height: 34px !important;
            min-height: 34px !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.03em;
            padding: 0.4rem 0.65rem !important;
            border-radius: var(--clean-radius) !important;
            box-shadow: none !important;
            line-height: 1.25;
        }

        /* Ikon pensil edit di tabel Presensi Kelas: kotak abu -> putih bergaris */
        .btn-edit-modal {
            background-color: #ffffff !important;
            border: 1px solid var(--clean-field) !important;
            color: var(--clean-ink-soft) !important;
        }
        .btn-edit-modal:hover {
            background-color: var(--clean-hover) !important;
            color: var(--clean-ink) !important;
        }

        /* ==========================================================================
           7. INPUT, DROPDOWN & LABEL - outline tipis, latar putih, placeholder jelas
           Radius & tinggi TIDAK diubah supaya input-group dan pasangan
           input + tombol tetap sama tinggi seperti sekarang.
           ========================================================================== */
        .form-control:not(:disabled):not(.is-invalid):not(.is-valid),
        .form-select:not(:disabled):not(.is-invalid):not(.is-valid) {
            background-color: #ffffff !important;
            border-color: var(--clean-field) !important;
        }

        .form-control:not(:disabled)::placeholder {
            color: #64748b !important;
            opacity: 1 !important;
        }

        /* Tombol ikon di dalam input-group (mis. tombol cari) ikut satu warna garis */
        .search-box-wrap > .btn,
        .search-box-wrap > .btn:hover,
        .search-box-wrap > .btn:focus-visible {
            border-color: var(--clean-field) !important;
        }

        .form-label,
        .filter-label {
            color: var(--clean-ink-soft) !important;
            font-weight: 600 !important;
        }

        /* ==========================================================================
           8. KARTU, PIL, KOTAK KETERANGAN - abu-abu dekoratif jadi putih + garis tipis
           ========================================================================== */
        /* Kartu pintasan & kartu jadwal operasional Dashboard (latar bg-light) */
        .shortcut-card-interactive,
        .operasional-card-interactive {
            background-color: #ffffff !important;
            border: 1px solid var(--clean-line) !important;
            box-shadow: none !important;
        }

        /* Baris Ketidakhadiran (Sakit / Izin / Alpha) di Dashboard */
        .absence-row-interactive {
            background-color: #ffffff !important;
            border: 1px solid var(--clean-line) !important;
            box-sizing: border-box;
        }

        /* Pill pengalih mode grafik & switcher scanner: putih bergaris tipis */
        .chart-mode-toggle,
        .kiosk-switcher {
            background-color: #ffffff !important;
        }

        /* Pill periode (Rekap & Riwayat): putih + garis tipis, hover ringan */
        .period-nav {
            background-color: #ffffff !important;
            border: 1px solid var(--clean-line) !important;
            box-sizing: border-box;
        }
        .period-link:hover {
            background-color: var(--clean-hover) !important;
        }
        .rekap-filter-submit:hover {
            background: var(--clean-hover) !important;
        }

        /* Kotak KETERANGAN (Rekap) - REVISI: kotak/border/latar DIHAPUS total
           (dulu: putih + garis solid di blok ini; sebelumnya: abu + putus-putus),
           jadi teks bersih di atas kanvas. Margin bawah (mb-3) & susunan
           .rekap-legend-item TIDAK diubah. Ukuran font diperkecil ke ~12.5px:
           item 0.82 -> 0.78rem, huruf H/T/S/I/A/L 0.90 -> 0.78rem,
           label 0.70rem dibiarkan. Warna huruf status TIDAK disentuh. */
        .rekap-legend {
            background-color: transparent !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            color: var(--clean-ink-soft) !important;
            font-size: 0.78rem !important;
        }
        .rekap-legend-label {
            color: var(--clean-ink) !important;
            font-size: 0.7rem !important;
        }
        .rekap-legend-item,
        .rekap-legend-item strong {
            font-size: 0.78rem !important;
        }

        /* Badge periode grafik Dashboard */
        .badge.bg-light {
            background-color: #ffffff !important;
            border: 1px solid var(--clean-line) !important;
            color: var(--clean-ink-soft) !important;
        }

        /* Kotak pratinjau logo (Pengaturan) */
        .logo-preview-box {
            background-color: #ffffff !important;
        }

        /* Badge status "terbatas" (Peran) */
        .status-limited {
            background: #ffffff !important;
            border-color: var(--clean-field) !important;
        }

        /* ==========================================================================
           9. AREA SCANNER (state idle, sebelum kamera aktif) - putih bersih
           ========================================================================== */
        .scanner-viewport-container {
            background: #ffffff !important;
        }
        #cameraPlaceholder {
            background: #ffffff !important;
        }

        /* ==========================================================================
           10. REVISI "BERSIH KEDUA" - garis berlebih dihapus, header tabel
           reguler, judul halaman lebih kecil (ukuran judul & bobot header
           sudah diubah langsung di bagian 1 & 2 di atas)
           ========================================================================== */

        /* 10.1 Garis tipis di bawah judul/subjudul SEMUA halaman
           (.app-header-bar = hairline pemisah sticky header, hanya ada di
           <1024px; di desktop >=1024px garis ini memang tidak pernah ada di
           kode). Hanya warna/shadow yang dimatikan - box-shadow tidak
           memengaruhi tinggi maupun posisi header. */
        .app-header-bar {
            box-shadow: none !important;
        }

        /* 10.2 Garis pemisah ANTARA toolbar filter dan baris header tabel
           (Data Siswa, Data Guru, Data Kelas, Catatan Kehadiran, Peran).
           Warna dibuat TRANSPARAN, bukan border-nya dihapus, supaya tinggi
           toolbar tetap 1px dan tidak ada layout yang bergeser. Hasilnya:
           tersisa SATU garis di bawah header tabel saja. Elemen <thead>/<tr>/<th>
           memakai class "border-b", BUKAN "border-bottom", jadi garis bawah
           header tabel TIDAK ikut mati. */
        .border-bottom.border-gray-100,
        .border-bottom.border-slate-100 {
            border-bottom-color: transparent !important;
        }

        /* 10.3 Garis pemisah header card Dashboard (class .clean-line-off hanya
           dipasang pada 3 elemen di dashboard.blade.php: "Pintasan Cepat",
           "Ketidakhadiran Hari Ini", dan garis di atas tombol
           "Buka Tabel Presensi Hari Ini"). Warna saja yang dimatikan,
           tinggi baris tetap 1px. */
        .clean-line-off {
            border-top-color: transparent !important;
            border-bottom-color: transparent !important;
        }

        /* 10.4 Garis tepi BAWAH kotak baris "Alpha" (baris terakhir panel
           Ketidakhadiran) dihapus; kotak Sakit & Izin tidak disentuh. */
        .absence-row-interactive:last-child {
            border-bottom-color: transparent !important;
        }

        /* 10.5 Kartu statistik atas Dashboard: garis tepi tipis 1px samar
           (#e5e9f0) menggantikan tepi yang terlalu tegas + tanpa bayangan,
           diseragamkan dengan kartu putih bersih lainnya di blok ini.
           Efek hover (angkat + bayangan) TIDAK diubah. */
        .stat-card-modern {
            border: 1px solid var(--clean-line) !important;
            box-shadow: none !important;
        }

        /* 10.6 Ringkasan status halaman Presensi Kelas (admin/absensi/class):
           kotak/pill pada 6 angka (Hadir, Terlambat, Sakit, Izin, Alfa, Belum)
           dijadikan TEKS BIASA - latar, border, radius, bayangan, dan padding
           kotak dihapus. Grid, urutan, kolom, lebar sel, ukuran & berat huruf
           TIDAK diubah, jadi angka tetap rapi sejajar. Tombol Buka Scanner QR
           (juga anak .status-summary-grid) TIDAK disentuh. */
        .status-summary-grid .status-badge-pill {
            background-color: transparent !important;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            padding: 0 !important;
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

    <!-- KONTEN UTAMA: Kotak Light Cream dengan Bezel Simetris
         Class tambahan per halaman lewat @yield('canvas_class'), misalnya
         .page-canvas-fixed = kanvas dikunci setinggi satu layar dan isinya
         menggulir di dalam kanvas (lihat blok CSS "3.C KANVAS SATU LAYAR").
         Halaman yang tidak menentukan section ini tidak berubah sama sekali. -->
    <div class="flex-1 min-w-0 h-screen overflow-y-auto overflow-x-hidden content-scroll-wrapper box-border">
        <main class="w-full bg-[#F8F3F0] rounded-none md:rounded-2xl shadow-none md:shadow-md p-4 sm:p-5 md:p-6 box-border flex flex-col relative @yield('canvas_class')">

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
<div class="modal fade app-dialog-modal" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 app-dialog-card">
            <form action="{{ route('logout') }}" method="POST" class="d-flex flex-column app-dialog-card-body">
                @csrf
                <div class="modal-body p-0 text-center">
                    <div class="app-dialog-icon">
                        <i class='bx bx-log-out'></i>
                    </div>
                    <h5 class="modal-title app-dialog-title mb-0" id="logoutConfirmModalLabel">Log Out</h5>
                    <p class="app-dialog-text mt-2 mb-0">Apakah Anda yakin untuk Log Out?</p>
                    <p class="app-dialog-sub">Anda harus login kembali untuk mengakses sistem.</p>
                </div>
                <div class="modal-footer app-dialog-footer">
                    <button type="button" class="btn btn-light app-dialog-btn" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger app-dialog-btn" id="logoutConfirmBtn">Ya, Log Out</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
/* ALUR LOG OUT DENGAN LOADING: klik "Ya, Log Out" -> modal + backdrop blur
   fade-out 180ms -> overlay loading bersama tampil -> form POST logout jalan.
   - Cegah klik ganda: tombol dinonaktifkan saat pertama diklik.
   - BATAL/X/Esc/klik-luar tetap bawaan Bootstrap (tak tersentuh).
   - Timeout 20 detik: bila masih di halaman ini, loading disembunyikan dan
     muncul notifikasi gagal (tidak macet menutup layar). */
(function () {
    'use strict';
    var modal = document.getElementById('logoutConfirmModal');
    if (!modal) return;
    var form = modal.querySelector('form');
    var confirmBtn = document.getElementById('logoutConfirmBtn');
    if (!form) return;

    function showLogoutFailed() {
        if (window.hideSmartLoader) { window.hideSmartLoader(); }
        var host = document.querySelector('.content-scroll-wrapper > main > .flex-1') || document.querySelector('main') || document.body;
        if (!host || document.getElementById('logoutFailedNotice')) return;
        var note = document.createElement('div');
        note.id = 'logoutFailedNotice';
        note.className = 'alert alert-danger alert-dismissible fade show';
        note.setAttribute('role', 'alert');
        note.innerHTML = '<span>Log out gagal atau waktu habis. Periksa koneksi lalu coba lagi.</span>'
            + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>';
        host.insertBefore(note, host.firstChild);
        if (confirmBtn) { confirmBtn.disabled = false; }
        if (form) { delete form.dataset.loggingOut; }
    }

    form.addEventListener('submit', function (e) {
        if (form.dataset.loggingOut === '1') return; /* submit programatik */
        e.preventDefault();
        if (confirmBtn && confirmBtn.disabled) return;
        if (confirmBtn) { confirmBtn.disabled = true; }
        modal.classList.add('logout-leaving');
        var backs = document.querySelectorAll('.modal-backdrop');
        for (var i = 0; i < backs.length; i++) {
            backs[i].style.transition = 'opacity 180ms linear';
            backs[i].style.opacity = '0';
        }
        window.setTimeout(function () {
            try {
                if (window.bootstrap && window.bootstrap.Modal) {
                    var inst = window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal);
                    inst.hide();
                } else {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    var b = document.querySelectorAll('.modal-backdrop');
                    for (var j = 0; j < b.length; j++) {
                        if (b[j].parentNode) { b[j].parentNode.removeChild(b[j]); }
                    }
                    document.body.classList.remove('modal-open');
                }
            } catch (err) { /* lanjut ke loading + submit */ }
            if (window.showSmartLoader) { window.showSmartLoader(); }
            window.setTimeout(function () {
                /* Masih di halaman ini setelah 20 detik = logout gagal/timeout. */
                showLogoutFailed();
            }, 20000);
            form.dataset.loggingOut = '1';
            form.submit();
        }, 180);
    });
})();
</script>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/instant-download.js') }}?v={{ file_exists(public_path('js/instant-download.js')) ? filemtime(public_path('js/instant-download.js')) : config('app.version', '1') }}"></script>
<script>
    /**
     * TEMA DIALOG BERSAMA - dialog konfirmasi hapus (satuan & massal).
     *
     * SATU fungsi ini dipakai oleh SEMUA halaman yang punya tombol hapus,
     * sehingga tema, ukuran, animasi, dan perilakunya benar-benar sama
     * persis di mana-mana. Gaya semuanya datang dari kelas .app-dialog-*
     * dan .swal2-popup.app-dialog di layout bersama.
     *
     * Opsi:
     *   title        - judul dialog
     *   html / text  - deskripsi (mendukung tag tebal: b / strong)
     *   sub          - baris penjelas kecil di bawah deskripsi
     *   icon         - kelas ikon Boxicons di dalam lingkaran
     *                  (default 'bx-error-circle')
     *   tone         - 'danger' (default, aksen MERAH - untuk semua dialog hapus)
     *                  atau 'primary' (aksen BIRU - untuk dialog NON-hapus,
     *                  mis. "Aktifkan Tahun Ajaran?"). Kartu, overlay, ikon,
     *                  judul, deskripsi, dan tombol TETAP sama persis; hanya
     *                  warna aksen yang dibedakan.
     *   confirmText  - teks tombol kanan (default "Hapus")
     *   cancelText   - teks tombol kiri  (default "Tidak")
     *   checks       - array of string/HTML label untuk checkbox konfirmasi
     *                  (opsional; bila diisi, tombol Hapus NONAKTIF sampai
     *                   semua checkbox dicentang)
     *   onConfirm    - callback saat tombol merah ditekan
     *
     * Klik di luar kartu atau tombol Esc menutup dialog (sama seperti
     * menekan tombol "Tidak") - perilaku bawaan SweetAlert2.
     */
    window.confirmUniversalDelete = function(options) {
        const opts = options || {};
        const title = opts.title || 'Hapus Data?';
        const confirmText = opts.confirmText || 'Hapus';
        const cancelText = opts.cancelText || 'Tidak';
        const message = opts.html || opts.text || 'Data ini akan dihapus.';
        const sub = opts.sub || '';
        const icon = opts.icon || 'bx-error-circle';
        const checks = Array.isArray(opts.checks) ? opts.checks : [];

        /* Aksen dialog. Default 'danger' (= merah) dipakai semua dialog hapus
           yang sudah ada, sehingga perilakunya TIDAK berubah sama sekali.
           Dialog non-hapus cukup mengesmukan tone: 'primary'. */
        const isPrimaryTone = opts.tone === 'primary';

        // Bangun isi dialog dari class yang sama untuk semua halaman.
        let html = '';

        html += '<div class="app-dialog-icon"><i class="bx ' + icon + '"></i></div>';
        html += '<h5 class="app-dialog-title">' + title + '</h5>';
        html += '<p class="app-dialog-text">' + message + '</p>';

        if (sub) {
            html += '<p class="app-dialog-sub">' + sub + '</p>';
        }

        if (checks.length) {
            html += '<div class="app-dialog-checks">';
            checks.forEach(function (label, i) {
                html += '<label class="app-dialog-check">'
                      + '<input type="checkbox" class="app-dialog-check-box" data-app-check="' + i + '">'
                      + '<span>' + label + '</span>'
                      + '</label>';
            });
            html += '</div>';
        }

        return Swal.fire({
            html: html,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            // Tepat di tengah layar, z-index tertinggi (loader global 999999999).
            position: 'center',
            backdrop: 'rgba(15,23,42,0.55)',
            allowOutsideClick: true,
            allowEscapeKey: true,
            draggable: false,
            customClass: {
                popup: 'app-dialog' + (isPrimaryTone ? ' app-dialog-primary' : ''),
                actions: 'app-dialog-actions',
                confirmButton: 'btn ' + (isPrimaryTone ? 'btn-primary' : 'btn-danger') + ' app-dialog-confirm',
                cancelButton: 'btn btn-light app-dialog-cancel'
            },
            didOpen: function (popup) {
                if (!checks.length) return;

                // Tombol konfirmasi NONAKTIF sampai semua checkbox dicentang.
                const btn = popup.querySelector('.app-dialog-confirm');
                const boxes = Array.prototype.slice.call(
                    popup.querySelectorAll('.app-dialog-check-box')
                );

                const sync = function () {
                    const allChecked = boxes.every(function (b) { return b.checked; });
                    btn.disabled = !allChecked;
                    btn.classList.toggle('app-dialog-btn-disabled', !allChecked);
                };

                boxes.forEach(function (box) {
                    box.addEventListener('change', sync);
                });

                sync();
            }
        }).then(function (result) {
            // Bila dialog ditutup lewat Esc / klik luar,	result.isDismissed
            // bernilai true dan onConfirm TIDAK dipanggil - sama persis
            // dengan menekan tombol "Tidak".
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

<!-- ==========================================================================
     NOTIFIKASI GLOBAL - HILANG OTOMATIS + TOMBOL X (SEMUA HALAMAN)
     --------------------------------------------------------------------------
     SATU script untuk SELURUH halaman, jadi tidak perlu menulis logika
     notifikasi per halaman. Cukup dengan Alert Bootstrap
     (alert + alert-dismissible + tombol .btn-close) di halaman mana pun.

     Yang dihapus otomatis:
       - sukses tambah / edit / pulihkan / hapus permanen / import (sukses)
       - gagal / peringatan (hapus, import 0 baris, validasi dari server, dll)

     Yang TIDAK dihapus otomatis:
       - kotak info statis (Zona Berbahaya, format Excel, catatan Pengaturan,
         dsb) - semuanya tidak punya .btn-close sehingga tidak tersentuh.
       - notifikasi error VALIDASI FORM yang ditandai data-flash-persist
         (tetap tampil sampai user memperbaikinya atau menekan tombol X).

     Alur: tampil 3 detik -> fade-out halus 0.3s -> elemen DIHAPUS dari layout
     (bukan disembunyikan), supaya konten di bawahnya langsung naik tanpa
     menyisakan celah kosong.

     Tombol X tetap berfungsi seperti biasa (Bootstrap Alert.close), sehingga
     notifikasi bisa ditutup lebih cepat secara manual.
     ========================================================================== -->
<script>
(function () {
    'use strict';

    var TUNGGU_MS = 4500;  /* sukses/info: hilang setelah 4.5 detik       */
    var TUNGGU_GAGAL_MS = 7000; /* gagal/peringatan: 7 detik (lebih lama dibaca) */
    var FADE_MS = 300;    /* durasi fade-out (0.3 detik)             */

    var PERSIST_ATTR = 'data-flash-persist';
    var ARMED_ATTR = 'data-flash-armed';   /* penanda sudah dijadwalkan */

    /**
     * Notifikasi yang boleh hilang otomatis.
     * Syarat: alert Bootstrap yang bisa ditutup (punya .btn-close) dan tidak
     * ditandai data-flash-persist.
     */
    function findAutoDismissAlerts() {
        var all = document.querySelectorAll('.alert.alert-dismissible');
        var out = [];
        for (var i = 0; i < all.length; i++) {
            var el = all[i];
            if (el.hasAttribute(PERSIST_ATTR)) continue;      /* validasi form */
            if (el.getAttribute('data-flash-auto') === 'off') continue;
            if (!el.querySelector('.btn-close')) continue;    /* bukan notifikasi */
            if (el.closest('.modal')) continue;              /* isi modal, bukan flash */
            if (el.hasAttribute(ARMED_ATTR)) continue;       /* sudah dijadwalkan */
            out.push(el);
        }
        return out;
    }

    /** Fade-out lalu HAPUS elemennya dari layout. */
    function hideAlert(el) {
        if (!el || el.getAttribute('data-flash-hiding') === '1') return;
        el.setAttribute('data-flash-hiding', '1');

        /* Melepas .show memicu transisi opacity Bootstrap -> fade-out. */
        el.classList.remove('show');
        /* Jangan bisa diklik lagi selama memudar. */
        el.style.pointerEvents = 'none';

        window.setTimeout(function () {
            /* Hapus dari DOM (bukan disembunyikan) -> konten bawah langsung naik. */
            if (el.parentNode) {
                el.parentNode.removeChild(el);
            }
        }, FADE_MS + 40);
    }

    function schedule(el, delay) {
        window.setTimeout(function () { hideAlert(el); }, delay);
    }

    function boot() {
        var alerts = findAutoDismissAlerts();
        for (var i = 0; i < alerts.length; i++) {
            alerts[i].setAttribute(ARMED_ATTR, '1');
            /* Gagal/peringatan diberi waktu baca lebih lama (7 dtk). */
            var isSerious = alerts[i].classList.contains('alert-danger') ||
                            alerts[i].classList.contains('alert-warning');
            schedule(alerts[i], isSerious ? TUNGGU_GAGAL_MS : TUNGGU_MS);
        }
    }

    /* Notifikasi tetap hilang tepat 3 detik walau user sedang memuat ulang. */
    boot();

    /* Halaman yang menyisipkan notifikasi lewat JavaScript (bukan render
       server) ikut ditangani: satu MutationObserver cukup untuk semuanya,
       tanpa menulis logika notifikasi per halaman. */
    if (typeof window.MutationObserver === 'function') {
        var observer = new MutationObserver(function () {
            boot();
        });
        observer.observe(document.body, { childList: true, subtree: true });
        /* Observer cukup Lama untuk memproses elemen yang baru ditambahkan. */
        window.setTimeout(function () { observer.disconnect(); }, 10000);
    }
})();
</script>
@yield('scripts')
@stack('scripts')
</body>
</html>
