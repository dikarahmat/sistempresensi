<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Presensi') | {{ \App\Models\Setting::getAppTitle() ?? 'SMP PGRI' }}</title>

    <!-- Global Favicon Dinamis -->
    <link rel="icon" type="image/webp" href="{{ asset(\App\Models\Setting::getLogo()) }}">

    <!-- Google Fonts: Poppins & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & Boxicons (Untuk Sidebar & Struktur Master) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS & JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Local Assets via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <style>
        :root {
            --primary-blue: #3b62f6;
            --sidebar-width: 255px;
            --text-dark: #1e293b;
            --bg-canvas: #f8faff;
            --sidebar-blue: #3b62f6;
            --sidebar-blue-active: #2f4ecc;
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100%;
            min-height: 100vh;
        }

        body {
            font-family: 'Poppins', 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
        }

        /* --- KUNCIAN SIDEBAR FIXED FULL STRETCH SOLID ROYAL BLUE (#3b62f6) (ROUNDED-R-3XL) --- */
        aside.w-64 {
            width: 16rem !important; /* 256px w-64 */
            min-width: 16rem !important;
            height: 100vh !important;
            min-height: 100vh !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            flex-shrink: 0 !important;
            border-top-right-radius: 1.5rem !important; /* rounded-r-3xl */
            border-bottom-right-radius: 1.5rem !important; /* rounded-r-3xl */
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            border: none !important;
            border-right: 1px solid rgba(255, 255, 255, 0.15) !important;
            box-shadow: 4px 0 24px rgba(59, 98, 246, 0.15);
            box-sizing: border-box !important;
            z-index: 50 !important;
        }

        /* --- KUNCIAN MAIN CONTENT (ml-64, scroll vertikal independen) --- */
        .main-content {
            margin-left: 16rem !important; /* ml-64 */
            flex: 1 1 0%;
            min-width: 0;
            height: 100vh !important;
            overflow-y: auto !important;
            overflow-x: hidden;
            padding: 1.5rem !important; /* p-6 */
            box-sizing: border-box !important;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.2rem 0.25rem 0.75rem 0.25rem;
            margin-bottom: 0.65rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            flex-shrink: 0;
        }

        .brand-logo {
            width: 36px;
            height: 36px;
            object-fit: contain;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            flex-shrink: 0;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.15));
        }

        .brand-box span {
            color: #ffffff !important;
            font-weight: 500; /* Font normal, tidak tebal/bold */
            font-size: 0.88rem;
            line-height: 1.25;
            letter-spacing: normal;
        }

        .menu-category {
            font-size: 0.64rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.75);
            letter-spacing: 0.08em;
            margin: 0 0 0.2rem 0.65rem;
            opacity: 0.95;
            text-transform: uppercase;
        }

        .menu-category:first-of-type {
            margin-top: 0;
        }

        .sidebar-menu-scroll {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 2px;
        }

        .sidebar-menu-scroll::-webkit-scrollbar {
            width: 3px;
        }

        .sidebar-menu-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 3px;
        }

        .menu-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            color: #ffffff;
            padding: 0.48rem 1rem;
            border-radius: 9999px !important; /* Rounded pill shape */
            text-decoration: none;
            font-weight: 500;
            font-size: 0.83rem;
            line-height: 1.3;
            margin-bottom: 0;
            transition: all 0.2s ease;
            position: relative;
        }

        .nav-link i {
            font-size: 1.18rem;
            color: #ffffff;
            opacity: 0.9;
            flex-shrink: 0;
            transition: all 0.15s ease;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            border-radius: 9999px !important;
        }

        .nav-link:hover i {
            color: #ffffff;
            opacity: 1;
        }

        /* Item Menu Aktif: Kapsul Melengkung Penuh (Rounded Pill Shape / rounded-full) Putih Bersih */
        .nav-link.active,
        .nav-link.active.rounded-full,
        .nav-link.active.rounded-pill {
            background-color: #ffffff !important;
            color: #3b62f6 !important;
            font-weight: 700 !important;
            border-radius: 9999px !important; /* Kapsul melengkung penuh di kedua ujung */
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
        }

        .nav-link.active i,
        .nav-link.active.rounded-full i,
        .nav-link.active.rounded-pill i {
            color: #3b62f6 !important;
            font-weight: 700;
            opacity: 1;
        }

        /* Footer Sidebar & Logout (Dipisah tegas di bagian paling bawah dengan jarak napas yang bersih) */
        .sidebar-footer {
            padding: 0.85rem 1rem 1rem 1rem !important;
            border: none !important;
            border-top: 1px solid rgba(255, 255, 255, 0.15) !important;
            margin-top: auto !important;
            flex-shrink: 0 !important;
        }

        .sidebar-logout-btn {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            width: 100%;
            padding: 0.52rem 1rem;
            border: none;
            border-radius: 9999px !important;
            background: transparent;
            color: #fee2e2;
            font-weight: 600;
            font-size: 0.83rem;
            text-align: left;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-logout-btn i {
            font-size: 1.18rem;
            color: #fee2e2;
            transition: all 0.22s ease;
        }

        /* Hover Merah Elegan (Tailwind bg-red-600 & text-white) */
        .sidebar-logout-btn:hover {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35);
            transform: translateY(-1px);
        }

        .sidebar-logout-btn:hover i {
            color: #ffffff !important;
        }

        .sidebar-logout-btn:active {
            transform: translateY(0);
        }

        /* Fallback bila memakai .sidebar-footer .nav-link */
        .sidebar-footer .nav-link {
            padding: 0.48rem 0.75rem;
            color: #fee2e2 !important;
            font-weight: 600;
            font-size: 0.83rem;
            margin-bottom: 0;
            border-radius: 12px;
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-footer .nav-link i {
            color: #fee2e2 !important;
            font-size: 1.18rem;
        }
        .sidebar-footer .nav-link:hover {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35);
            transform: translateY(-1px);
        }
        .sidebar-footer .nav-link:hover i {
            color: #ffffff !important;
        }

        /* Reset Tailwind yang bentrok sama Bootstrap */
        a { text-decoration: none; }
    </style>
</head>
<body>

<div class="flex h-screen w-screen overflow-hidden bg-gray-50 m-0 p-0">
    <!-- SIDEBAR (FULL STRETCH 100VH DARI POJOK KIRI ATAS 0PX SAMPAI DASAR) -->
    @include('partials.sidebar')

    <!-- MAIN CONTENT -->
    <main class="main-content ml-64 flex-1 h-screen overflow-y-auto p-6">
        
        <!-- Bagian ini khusus muncul kalau halamannya punya Title, jadi Dashboard tetap rapih -->
        @hasSection('page_title')
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="fw-bolder mb-0" style="color: #0f172a; letter-spacing: -0.02em; font-size: 1.25rem;">@yield('page_title')</h1>
                <p class="text-slate-500 fw-semibold mb-0 mt-0.5" style="font-size: 0.78rem;">@yield('page_subtitle')</p>
            </div>
            @yield('page_header_right')
        </div>
        @endif

        <!-- Ini akan diisi oleh konten dari file dashboard.blade.php lu -->
        @yield('content')
        
    </main>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
@stack('scripts')
</body>
</html>
