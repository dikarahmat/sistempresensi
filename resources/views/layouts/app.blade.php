<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'Sistem Presensi') | {{ \App\Models\Setting::getAppTitle() ?? 'SMP PGRI' }}</title>

    <!-- Global Favicon Dinamis -->
    <link rel="icon" type="image/webp" href="{{ asset(\App\Models\Setting::getLogo()) }}">

    <!-- Google Fonts: Poppins & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & Boxicons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Local Assets via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        :root {
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
            background-color: #3b62f6 !important;
            color: var(--text-dark);
            overflow-y: auto !important;
            overflow-x: hidden !important;
            font-family: 'Poppins', 'Plus Jakarta Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
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
            width: 16rem !important;
            min-width: 16rem !important;
            max-width: 16rem !important;
            height: 100vh !important;
            min-height: 100vh !important;
            flex-shrink: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border-top-right-radius: 1.25rem !important;
            border-bottom-right-radius: 1.25rem !important;
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
                height: 100vh !important;
                height: calc(100dvh - env(safe-area-inset-top, 0px)) !important;
                width: 14rem !important;
                min-width: 0 !important;
                max-width: 78vw !important;
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

            .app-sidebar-drawer aside,
            aside aside {
                width: 100% !important;
                height: 100% !important;
                display: flex !important;
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
                padding: calc(1.5rem + env(safe-area-inset-top, 0px)) calc(16px + env(safe-area-inset-right, 0px)) calc(var(--bottom-nav-height) + 1rem + env(safe-area-inset-bottom, 0px)) calc(16px + env(safe-area-inset-left, 0px)) !important;
            }

            body {
                font-size: 0.875rem !important;
            }

            .header-main-subtitle {
                display: none !important;
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
            }

            /* Cegah font-inflation saat rotasi layar */
            html {
                -webkit-text-size-adjust: 100% !important;
                text-size-adjust: 100% !important;
            }
        }

        @media (min-width: 768px) {
            .content-scroll-wrapper {
                padding: 0.35rem !important;
                background-color: #3b62f6 !important;
            }

            main,
            .content-scroll-wrapper > main,
            main.w-full {
                min-height: calc(100vh - 0.7rem) !important;
                min-height: calc(100dvh - 0.7rem) !important;
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
                    padding-bottom: calc(var(--bottom-nav-height) + 2rem + env(safe-area-inset-bottom, 0px)) !important;
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
                flex-shrink: 0 !important;
                width: 32px !important;
                height: 32px !important;
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

        .app-header-bar {
            width: 100% !important;
            margin-bottom: 0.875rem !important;
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
            justify-content: center !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .header-main-title {
            color: #0f172a !important;
            letter-spacing: -0.02em !important;
            font-size: 1.05rem !important;
            font-weight: 750 !important;
            line-height: 1.25 !important;
            margin: 0 !important;
            padding: 0 !important;
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
            font-size: 0.72rem !important;
            font-weight: 500 !important;
            line-height: 1.3 !important;
            margin: 0 !important;
            margin-top: 2px !important;
            padding: 0 !important;
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

        .card,
        .modal-content,
        .btn,
        button,
        .form-control,
        .form-select,
        .input-group-text,
        .stat-card-polished,
        .alert,
        .table-responsive {
            border-radius: 12px !important;
        }

        .table-responsive,
        .no-scrollbar {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        .table-responsive::-webkit-scrollbar,
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
            z-index: 2;
        }

        .search-box-wrap > .btn:hover,
        .search-box-wrap > .btn:focus-visible {
            background: transparent !important;
            color: #2563eb !important;
            box-shadow: none !important;
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
            border-radius: 12px !important;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.83rem;
            line-height: 1.3;
            transition: background-color 0.2s ease, color 0.2s ease;
            position: relative;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-radius: 12px !important;
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

        .sidebar-logout-btn {
            border-radius: 12px !important;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .sidebar-menu-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-menu-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 4px;
        }

        /* --------------------------------------------------------------------------
           STANDARISASI UNIVERSAL MODAL KONFIRMASI HAPUS (PREMIUM SOFT UI)
           -------------------------------------------------------------------------- */
        .swal2-popup.swal2-modal-soft {
            border-radius: 1.25rem !important;
            padding: 2.25rem 2rem 1.75rem !important;
            max-width: 440px !important;
            width: 90% !important;
            background: #ffffff !important;
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
            min-height: 42px !important;
            padding: 0.55rem 1.5rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.9rem !important;
            font-weight: 500 !important;
            border-radius: 0.5rem !important;
            margin: 0 !important;
            line-height: 1.25 !important;
            transition: all 0.2s ease !important;
        }
        .swal2-popup.swal2-modal-soft .swal2-actions .btn-danger {
            background-color: #dc2626 !important;
            border: 1px solid #dc2626 !important;
            color: #ffffff !important;
        }
        .swal2-popup.swal2-modal-soft .swal2-actions .btn-danger:hover {
            background-color: #b91c1c !important;
            border-color: #b91c1c !important;
        }
        .swal2-popup.swal2-modal-soft .swal2-actions .btn-light {
            background-color: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
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
    </style>
</head>
<body class="overflow-hidden bg-[#3b62f6] m-0 p-0" x-data="{ sidebarOpen: false }">

{{-- Global Smart Loader Component --}}
@include('components.loading-overlay')

<!-- ========================================================================= -->
<!-- 1. ROOT APPLICATION CANVAS                                                -->
<!-- ========================================================================= -->
<div class="flex h-screen w-screen overflow-hidden bg-[#3b62f6] m-0 p-0">

    <!-- SIDEBAR DRAWER (Z-INDEX 9999999 - PALING DEPAN KETIKA DIBUKA) -->
    <aside :class="sidebarOpen ? 'mobile-sidebar-active' : ''"
           x-cloak
           class="app-sidebar-drawer fixed lg:static inset-y-0 left-0">
        @include('partials.sidebar')
    </aside>

    <!-- KONTEN UTAMA: Kotak Putih dengan Bezel Simetris -->
    <div class="flex-1 min-w-0 h-screen overflow-y-auto overflow-x-hidden content-scroll-wrapper box-border">
        <main class="w-full bg-white rounded-none md:rounded-2xl shadow-none md:shadow-md p-4 sm:p-5 md:p-6 box-border flex flex-col relative">

            <!-- HEADER UTAMA DENGAN TOMBOL GARIS TIGA DI POJOK KIRI ATAS UNTUK MOBILE/TABLET/IPAD -->
            <div class="app-header-bar d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2.5 sm:gap-3 mb-3 sm:mb-4 w-100">
                <div class="app-header-left d-flex align-items-center gap-2 sm:gap-2.5 min-w-0">
                    <!-- Tombol Garis Tiga Pure Icon (Khusus HP, Tablet & iPad < 1024px) -->
                    <button @click="sidebarOpen = !sidebarOpen" 
                            type="button"
                            class="mobile-top-hamburger lg:hidden flex-shrink-0" 
                            title="Buka Menu Sidebar"
                            aria-label="Buka Menu Sidebar">
                        <i class='bx bx-menu'></i>
                    </button>
                    <!-- Wadah Blok Teks Header Solid (Judul & Subjudul Sejajar Vertikal Presisi) -->
                    <div class="header-text-block min-w-0 flex-1 d-flex flex-column justify-content-center">
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

    // 1. Dashboard
    $navDashboardUrl = match($navUserRole) {
        'admin' => route('admin.dashboard'),
        'guru' => route('guru.dashboard'),
        'kesiswaan' => route('kesiswaan.dashboard'),
        default => url('/'),
    };
    $isNavDashboardActive = request()->routeIs('*dashboard*');

    // 2. Kehadiran
    $navKehadiranUrl = match($navUserRole) {
        'admin' => route('admin.kehadiran'),
        'guru' => route('guru.kehadiran'),
        'kesiswaan' => route('kesiswaan.kehadiran'),
        default => '#',
    };
    $isNavKehadiranActive = request()->routeIs('*kehadiran*');

    // 3. Presensi (Ikon QR Code: bx-qr-scan)
    $navAbsensiUrl = match($navUserRole) {
        'admin' => route('admin.absensi.index'),
        'guru' => route('guru.absensi.index'),
        'kesiswaan' => route('kesiswaan.absensi.index'),
        default => '#',
    };
    $isNavAbsensiActive = (request()->routeIs('*absensi*') || request()->routeIs('*presensi*') || request()->routeIs('*scanner*') || request()->routeIs('*kiosk*')) && !$isNavKehadiranActive;

    // 4. Rekap
    $navRekapUrl = match($navUserRole) {
        'admin' => route('admin.rekap'),
        'guru' => route('guru.rekap'),
        'kesiswaan' => route('kesiswaan.rekap.index'),
        default => '#',
    };
    $isNavRekapActive = request()->routeIs('*rekap*');

    // 5. Pengaturan / Menu Tambahan
    $navPengaturanUrl = match($navUserRole) {
        'admin' => route('admin.settings.index'),
        'guru' => route('guru.students'),
        'kesiswaan' => route('kesiswaan.classes.index'),
        default => '#',
    };
    $isNavPengaturanActive = request()->routeIs('*settings*') || request()->routeIs('*pengaturan*') || ($navUserRole === 'guru' && request()->routeIs('guru.students*')) || ($navUserRole === 'kesiswaan' && request()->routeIs('kesiswaan.classes*'));
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

        <!-- 5. Pengaturan / Menu Tambahan -->
        @php
            $nav5Title = ($navUserRole === 'guru') ? 'Siswa' : (($navUserRole === 'kesiswaan') ? 'Kelas' : 'Pengaturan');
            $nav5Icon = ($navUserRole === 'guru') ? 'bx bx-user' : (($navUserRole === 'kesiswaan') ? 'bx bx-buildings' : 'bx bx-cog');
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

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /**
     * Universal Soft UI Delete Confirmation Modal
     * Standardized across Desktop and Mobile (Premium Soft UI Circle)
     */
    window.confirmUniversalDelete = function(options) {
        const opts = options || {};
        const title = opts.title || 'Hapus Data?';
        const confirmText = opts.confirmText || 'Ya, Hapus';
        const cancelText = opts.cancelText || 'Batal';
        const iconClass = opts.icon === 'warning' ? 'bx bx-error' : 'bx bx-trash';
        const message = opts.html || opts.text || 'Tindakan ini bersifat permanen. Apakah Anda yakin ingin menghapus data ini?';

        const fullHtml = `
            <div class="text-center">
                <div class="d-inline-block bg-danger-subtle text-danger rounded-circle p-3 mb-3">
                    <i class="${iconClass} fs-1 d-block" style="line-height: 1;"></i>
                </div>
                <h5 class="fw-bold text-dark fs-5 mb-2">${title}</h5>
                <div class="text-secondary lh-base mb-4" style="font-size: 0.9rem;">${message}</div>
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
@yield('scripts')
@stack('scripts')
</body>
</html>