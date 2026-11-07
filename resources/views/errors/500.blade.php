<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - {{ __('Kesalahan Server') }} | {{ \App\Models\Setting::getAppTitle() }}</title>
    <link rel="icon" type="image/webp" href="{{ asset(\App\Models\Setting::getLogo()) }}">
    <!-- Google Fonts & Bootstrap 5 -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1d4ed8;
            --dark-navy: #0f172a;
            --text-muted: #64748b;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 24px 24px;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .error-container {
            max-width: 1050px;
            width: 100%;
            background: #ffffff;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.8);
            padding: 3.5rem 4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3rem;
        }
        @media (max-width: 992px) {
            .error-container {
                flex-direction: column;
                text-align: center;
                padding: 2.5rem 1.5rem;
            }
        }
        .error-content {
            flex: 1;
            max-width: 480px;
        }
        .error-illustration {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .error-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            background: #eff6ff;
            color: var(--primary-blue);
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.25rem;
        }
        .error-title {
            font-size: 3.25rem;
            font-weight: 800;
            color: var(--dark-navy);
            line-height: 1.15;
            letter-spacing: -0.03em;
            margin-bottom: 1rem;
        }
        .error-description {
            font-size: 1rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn-action {
            border-radius: 9999px;
            padding: 0.85rem 1.75rem;
            font-weight: 700;
            font-size: 0.95rem;
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
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
        }
        .btn-dashboard:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 14px 24px -5px rgba(37, 99, 235, 0.55);
        }
        /* Ilustrasi Kustom Gaya Flat Vector */
        .illustration-box {
            position: relative;
            width: 100%;
            max-width: 420px;
            background: linear-gradient(180deg, #f0f4ff 0%, #e0e9ff 100%);
            border-radius: 1.5rem;
            padding: 2.5rem;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        .server-rack-icon {
            font-size: 8rem;
            color: #3b82f6;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <!-- Bagian Kiri: Teks & Aksi -->
        <div class="error-content">
            <div class="error-badge">
                <i class='bx bx-error-alt'></i> Error 500
            </div>
            <h1 class="error-title">Oops! Terjadi Kesalahan.</h1>
            <p class="error-description">
                {{ __('Maaf, sistem mengalami gangguan internal pada server. Tim kami sedang memperbaikinya. Silakan kembali ke halaman utama.') }}
            </p>
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-start">
                @auth
                    @php
                        $destRoute = match(auth()->user()->role ?? 'guest') {
                            'admin' => route('admin.dashboard'),
                            'guru' => route('guru.dashboard'),
                            'piket' => route('piket.dashboard'),
                            default => route('login'),
                        };
                    @endphp
                    <a href="{{ $destRoute }}" class="btn-action btn-dashboard">
                        <i class='bx bx-home-alt'></i> {{ __('Ke Dashboard Saya') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-action btn-dashboard">
                        <i class='bx bx-log-in'></i> {{ __('Halaman Login') }}
                    </a>
                @endauth
            </div>
        </div>

        <!-- Bagian Kanan: Ilustrasi Vector Minimalis -->
        <div class="error-illustration">
            <div class="illustration-box">
                <!-- Ikon Server / Konstruksi Modern -->
                <i class='bx bx-server server-rack-icon'></i>
            </div>
        </div>
    </div>
</body>
</html>