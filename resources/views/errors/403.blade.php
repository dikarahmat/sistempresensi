<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | {{ \App\Models\Setting::getSchoolName() }}</title>

    <!-- Global Favicon Dinamis -->
    <link rel="icon" type="image/webp" href="{{ asset(\App\Models\Setting::getLogo()) }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & Boxicons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-blue: #2563eb;
            --text-main: #0f172a;
            --text-sub: #475569;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: 
                radial-gradient(circle at center, rgba(15, 23, 42, 0.45) 0%, rgba(15, 23, 42, 0.85) 100%), 
                url("{{ asset('images/bg-school.jpg') }}") no-repeat center center fixed;
            background-size: cover;
        }

        .error-card {
            max-width: 500px;
            width: 100%;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0.6) 100%) !important;
            backdrop-filter: blur(24px) saturate(190%);
            -webkit-backdrop-filter: blur(24px) saturate(190%);
            border-radius: 2rem;
            border: 1.5px solid rgba(255, 255, 255, 0.85);
            box-shadow: 0 35px 70px -15px rgba(0, 0, 0, 0.45);
            padding: 2.75rem 2rem;
            text-align: center;
        }

        .error-icon-box {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: rgba(239, 68, 68, 0.12);
            border: 1.5px solid rgba(239, 68, 68, 0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc2626;
            font-size: 2.75rem;
        }

        .error-code {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1;
            color: var(--text-main);
            letter-spacing: -0.05em;
            margin-bottom: 0.5rem;
        }

        .error-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.75rem;
        }

        .error-description {
            font-size: 0.88rem;
            color: var(--text-sub);
            line-height: 1.5;
            margin-bottom: 2rem;
        }

        .btn-action {
            border-radius: 9999px;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.25s ease;
            text-decoration: none;
        }

        .btn-dashboard {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border: none;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.4);
        }

        .btn-dashboard:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.55);
        }

        .btn-logout {
            background: rgba(241, 245, 249, 0.9);
            color: #475569;
            border: 1px solid rgba(203, 213, 225, 0.8);
        }

        .btn-logout:hover {
            background: #ffffff;
            color: #0f172a;
        }
    </style>
</head>
<body>

<div class="error-card">
    <div class="error-icon-box">
        <i class='bx bx-shield-x'></i>
    </div>

    <div class="error-code">403</div>
    <div class="error-title">Akses Ditolak (Unauthorized)</div>
    <p class="error-description">
        {{ $exception->getMessage() ?: 'Maaf, Anda tidak memiliki izin atau hak akses untuk membuka halaman ini.' }}
    </p>

    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
        @auth
            @php
                $destRoute = match(auth()->user()->role) {
                    'admin' => route('admin.dashboard'),
                    'guru' => route('guru.dashboard'),
                    'kesiswaan' => route('kesiswaan.dashboard'),
                    default => route('login'),
                };
            @endphp
            <a href="{{ $destRoute }}" class="btn-action btn-dashboard">
                <i class='bx bx-home-alt'></i> Ke Dashboard Saya
            </a>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn-action btn-logout w-100">
                    <i class='bx bx-log-out'></i> Keluar Akun
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="btn-action btn-dashboard">
                <i class='bx bx-log-in'></i> Halaman Login
            </a>
        @endauth
    </div>
</div>

</body>
</html>
