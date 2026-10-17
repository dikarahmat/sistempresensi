@php
    $appName = \App\Models\Setting::getTitleAppName();
    // Nama sekolah: ambil dari Pengaturan (getSchoolName() / key school_name / nama_sekolah)
    $schoolName = null;
    try {
        $S = \App\Models\Setting::class;
        if (method_exists($S, 'getSchoolName')) {
            $schoolName = $S::getSchoolName();
        } elseif (method_exists($S, 'get')) {
            $schoolName = $S::get('school_name') ?: $S::get('nama_sekolah');
        }
    } catch (\Throwable $e) {
        $schoolName = null;
    }
    $schoolName = $schoolName ?: 'SMP PGRI Parung Panjang';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $appName }}</title>

    <link rel="icon" type="image/webp" href="{{ \App\Models\Setting::getLogoUrl() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <style>
        :root {
            --blue: #3b62f6;
            --blue-hover: #254fd9;
            --blue-dark: #1d3fd0;
            --green: #16a34a;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --split: 50%;     /* batas kiri/kanan */
            --overlap: 40px;  /* panel biru menjorok ke bawah gelombang */
        }

        * { box-sizing: border-box; }
        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            background: #fff;
            overflow-x: hidden;
        }

        /* ===================== FULL SCREEN ===================== */
        .login-screen {
            position: relative;
            display: flex;
            width: 100%;
            min-height: 100vh;
            min-height: 100svh;
            overflow: hidden;
            background: #fff;
        }

        /* ===================== PANEL KIRI ===================== */
        .brand-panel {
            position: relative;
            z-index: 1;
            flex: 0 0 calc(var(--split) + var(--overlap));
            width: calc(var(--split) + var(--overlap));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            text-align: center;
            color: #fff;
            padding: clamp(1.5rem, 5vh, 3.5rem) calc(2rem + var(--overlap)) clamp(1.25rem, 4vh, 2.5rem) 2rem;
            overflow: hidden;
            background:
                radial-gradient(circle at 18% 12%, rgba(130, 160, 255, .65) 0, transparent 42%),
                radial-gradient(circle at 70% 95%, rgba(20, 50, 200, .6) 0, transparent 50%),
                linear-gradient(160deg, #4f74ff 0%, #3b62f6 48%, #1f44db 100%);
        }

        .dots {
            position: absolute;
            top: 0; left: 0;
            width: clamp(120px, 14vw, 230px);
            aspect-ratio: 1;
            background-image: radial-gradient(rgba(255, 255, 255, .45) 1.5px, transparent 1.8px);
            background-size: 10px 10px;
            -webkit-mask-image: radial-gradient(circle at 0 0, #000 30%, transparent 72%);
            mask-image: radial-gradient(circle at 0 0, #000 30%, transparent 72%);
        }

        .swoosh { position: absolute; pointer-events: none; z-index: 1; }
        .swoosh svg { width: 100%; height: 100%; overflow: visible; }
        .swoosh path { fill: none; stroke: url(#gLine); stroke-width: 3; stroke-linecap: round; }
        .swoosh-tl { top: -10px; left: -10px; width: clamp(150px, 16vw, 260px); aspect-ratio: 170 / 220; }
        .dots-r, .swoosh-tr, .swoosh-br { display: none; }
        .swoosh-bl { bottom: -10px; left: -10px; width: clamp(130px, 14vw, 230px); aspect-ratio: 150 / 170; }

        .brand-top, .brand-mid, .brand-bottom { position: relative; z-index: 2; width: 100%; }

        .brand-logo {
            width: clamp(52px, 8.5vh, 84px);
            height: clamp(52px, 8.5vh, 84px);
            object-fit: contain;
            filter: drop-shadow(0 10px 18px rgba(0, 0, 0, .3));
        }
        .brand-title {
            margin: clamp(.6rem, 2vh, 1.3rem) 0 .35rem;
            font-size: clamp(1.15rem, 1.9vw, 1.9rem);
            font-weight: 800;
            letter-spacing: -.01em;
            text-transform: uppercase;
            text-shadow: 0 2px 10px rgba(0, 0, 0, .25);
        }
        .brand-school {
            display: inline-block;
            margin-top: 0;
            font-size: clamp(1.15rem, 1.9vw, 1.9rem);
            font-weight: 800;
            letter-spacing: -.01em;
            text-transform: uppercase;
            color: #e6eeff;
            text-shadow: 0 2px 8px rgba(0, 0, 0, .22);
            position: relative;
        }

        .brand-mid {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: clamp(.5rem, 2vh, 1.5rem) 0;
        }
        .illus {
            display: block;
            width: min(100%, 36vw, 66vh);
            height: auto;
            max-width: 100%;
            object-fit: contain;
            filter: drop-shadow(0 18px 28px rgba(10, 30, 120, .3));
        }

        .brand-icons {
            display: flex;
            justify-content: center;
            gap: clamp(1.1rem, 2vw, 1.8rem);
            margin-bottom: clamp(.5rem, 1.4vh, 1rem);
        }
        .feat-icon {
            display: flex; align-items: center; justify-content: center;
            font-size: clamp(1.3rem, 2.8vh, 1.8rem);
            line-height: 1;
            color: #fff;
            filter: drop-shadow(0 3px 6px rgba(10, 30, 120, .3));
        }
        .brand-foot {
            font-size: clamp(.7rem, .95vw, .95rem);
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        /* ===================== GELOMBANG PEMISAH ===================== */
        /* svg x=0 berada di (split - 100px); panel biru berakhir di svg x=140,
           lebih jauh dari lengkung terjauh (x~125), jadi tidak ada celah. */
        .wave-divider {
            position: absolute;
            top: 0; bottom: 0;
            left: calc(var(--split) - 100px);
            width: 240px;
            z-index: 5;
            pointer-events: none;
        }
        .wave-divider svg { width: 100%; height: 100%; display: block; overflow: visible; }
        .wave-mobile { display: none; }

        /* ===================== PANEL KANAN ===================== */
        .form-panel {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 3rem 2.5rem calc(3rem - var(--overlap));
            background:
                radial-gradient(circle at 100% 100%, rgba(59, 98, 246, .08) 0, transparent 45%),
                radial-gradient(circle at 30% 100%, rgba(139, 224, 42, .09) 0, transparent 40%),
                #fff;
        }
        .form-wrap { position: relative; z-index: 10; width: 100%; max-width: 460px; }

        .form-heading {
            font-size: clamp(1.9rem, 2.6vw, 2.7rem);
            font-weight: 800;
            letter-spacing: -.03em;
            margin-bottom: .4rem;
        }
        .form-subtext {
            font-size: clamp(.85rem, 1vw, 1rem);
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 2rem;
        }

        .form-group-item { margin-bottom: 1.3rem; }
        .input-label-custom {
            display: block;
            font-size: .88rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: .45rem;
        }
        .input-box-wrapper {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: .2rem .6rem;
            overflow: hidden;
        }
        .input-box-wrapper:focus-within {
            border-color: var(--blue);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(59, 98, 246, .14);
        }
        .input-box-wrapper.is-invalid-field { border-color: #ef4444; background: #fffafb; }

        .input-icon-box {
            padding: .55rem .6rem;
            color: #94a3b8;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }
        .input-box-wrapper:focus-within .input-icon-box { color: var(--blue); }
        .input-box-wrapper.is-invalid-field .input-icon-box { color: #ef4444; }

        .form-input-field {
            border: none !important;
            background: transparent !important;
            width: 100%;
            min-width: 0;
            padding: .7rem .4rem;
            font-size: .95rem;
            font-weight: 600;
            color: #0f172a !important;
            -webkit-text-fill-color: #0f172a;
            caret-color: #0f172a;
            outline: none !important;
            box-shadow: none !important;
        }
        .form-input-field::placeholder {
            color: #94a3b8 !important;
            -webkit-text-fill-color: #94a3b8 !important;
            font-weight: 400;
            opacity: 1;
        }
        .form-input-field:-webkit-autofill,
        .form-input-field:-webkit-autofill:hover,
        .form-input-field:-webkit-autofill:focus {
            -webkit-text-fill-color: #0f172a !important;
            -webkit-box-shadow: 0 0 0 1000px #f8fafc inset !important;
            box-shadow: 0 0 0 1000px #f8fafc inset !important;
            transition: background-color 9999s ease-out 0s;
        }
        .input-box-wrapper:focus-within .form-input-field:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
            box-shadow: 0 0 0 1000px #fff inset !important;
        }

        .btn-toggle-eye {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding: .5rem .6rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .btn-toggle-eye:hover { color: var(--text-main); }

        .field-error-feedback {
            color: #dc2626;
            font-size: .8rem;
            font-weight: 600;
            margin-top: .4rem;
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .row-extra {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: 1.6rem;
        }
        .remember-container { display: flex; align-items: center; gap: .55rem; }
        .remember-checkbox {
            width: 1.1rem; height: 1.1rem;
            margin: 0;
            border-radius: 4px;
            cursor: pointer;
            accent-color: var(--green);
        }
        .remember-label {
            font-size: .88rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
            margin: 0;
        }
        .forgot-link {
            font-size: .88rem;
            font-weight: 700;
            color: var(--blue-dark);
            text-decoration: none;
        }
        .forgot-link:hover { text-decoration: underline; }

        .btn-submit-action {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            border: none;
            border-radius: 14px;
            padding: .95rem 1.25rem;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            background-color: var(--blue);
            box-shadow: 0 8px 20px rgba(59, 98, 246, .38);
        }
        .btn-submit-action:hover { background-color: var(--blue-hover); }
        .btn-submit-action:disabled { opacity: .85; cursor: not-allowed; }

        /* loader tombol (satu-satunya yang bergerak, hanya saat proses login) */
        .app-loader {
            display: inline-block;
            width: 22px;
            padding: 3px;
            aspect-ratio: 1;
            border-radius: 50%;
            background: #fff;
            --_m: conic-gradient(#0000 10%, #000), linear-gradient(#000 0 0) content-box;
            -webkit-mask: var(--_m);
            mask: var(--_m);
            -webkit-mask-composite: source-out;
            mask-composite: subtract;
            animation: spin 1s infinite linear;
        }
        @keyframes spin { to { transform: rotate(1turn); } }

        .alert-login { border-radius: 12px; font-size: .85rem; font-weight: 600; padding: .65rem .9rem; margin-bottom: 1rem; }

        /* ===================== MOBILE / TABLET ===================== */
        @media (max-width: 991px) {
            .login-screen { flex-direction: column; }
            /* panel biru ringkas: logo, judul, nama sekolah, icon + tagline (tanpa ilustrasi) */
            .brand-panel {
                flex: none;
                width: 100%;
                height: auto;
                justify-content: center;
                padding: calc(2.3rem + env(safe-area-inset-top, 0px)) 1.25rem 4.2rem;
            }
            .brand-mid { display: none; }
            .brand-logo { width: 56px; height: 56px; }
            .brand-title { font-size: clamp(1.05rem, 5vw, 1.25rem); margin: .55rem 0 .15rem; }
            .brand-school { font-size: clamp(1.05rem, 5vw, 1.25rem); }
            .brand-bottom { margin-top: 1.5rem; }
            .dots-r {
                display: block;
                left: auto; right: 0;
                -webkit-mask-image: radial-gradient(circle at 100% 0, #000 30%, transparent 72%);
                mask-image: radial-gradient(circle at 100% 0, #000 30%, transparent 72%);
            }
            .swoosh-tr, .swoosh-br { display: block; transform: scaleX(-1); }
            .swoosh-tr { top: -10px; left: auto; right: -10px; width: clamp(150px, 16vw, 260px); aspect-ratio: 170 / 220; }
            .swoosh-br { bottom: -10px; left: auto; right: -10px; width: clamp(130px, 14vw, 230px); aspect-ratio: 150 / 170; }
            .brand-icons { gap: 1.2rem; margin-bottom: .5rem; }
            .feat-icon { font-size: 1.4rem; }
            .brand-foot { font-size: .7rem; }

            .wave-divider { display: none; }
            .wave-mobile {
                display: block;
                position: relative;
                z-index: 5;
                height: 34px;
                margin-top: -34px;
                pointer-events: none;
            }
            .wave-mobile svg { width: 100%; height: 100%; display: block; }

            .form-panel {
                flex: 1;
                align-items: flex-start;
                padding: 2.7rem 2.4rem calc(1.25rem + env(safe-area-inset-bottom, 0px));
            }
            .form-wrap { max-width: 400px; }
            .form-heading { font-size: 1.4rem; margin-bottom: .2rem; }
            .form-subtext { font-size: .78rem; margin-bottom: .9rem; }
            .form-group-item { margin-bottom: .75rem; }
            .input-label-custom { font-size: .78rem; margin-bottom: .25rem; }
            .input-box-wrapper { border-radius: 11px; padding: 0 .4rem; }
            .input-icon-box { padding: .35rem .4rem; font-size: 1.05rem; }
            .btn-toggle-eye { padding: .35rem .4rem; font-size: 1.05rem; }
            .form-input-field { padding: .42rem .3rem; font-size: .82rem; }
            .remember-label { font-size: .76rem; }
            .row-extra { margin-bottom: .9rem; }
            .btn-submit-action { min-height: 40px; padding: .55rem 1.25rem; font-size: .88rem; border-radius: 11px; }
        }
    </style>
</head>
<body>

<div class="login-screen">

    {{-- ================= PANEL KIRI ================= --}}
    <section class="brand-panel">
        <div class="dots" aria-hidden="true"></div>

        <div class="swoosh swoosh-tl" aria-hidden="true">
            <svg viewBox="0 0 170 220">
                <defs>
                    <linearGradient id="gLine" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#d9f99d"/>
                        <stop offset=".5" stop-color="#a3e635"/>
                        <stop offset="1" stop-color="#6cc417"/>
                    </linearGradient>
                </defs>
                <path d="M0 150 C 60 140, 100 90, 110 40 S 140 5, 170 0"/>
                <path d="M0 175 C 70 165, 118 105, 128 55 S 150 18, 170 12"/>
            </svg>
        </div>
        <div class="swoosh swoosh-bl" aria-hidden="true">
            <svg viewBox="0 0 150 170">
                <path d="M0 40 C 55 50, 95 85, 105 125 S 135 160, 150 170"/>
                <path d="M0 62 C 60 72, 110 100, 120 140 S 140 165, 150 170"/>
            </svg>
        </div>

        {{-- Hiasan sisi kanan (hanya mobile): cermin dari hiasan kiri --}}
        <div class="dots dots-r" aria-hidden="true"></div>
        <div class="swoosh swoosh-tr" aria-hidden="true">
            <svg viewBox="0 0 170 220">
                <path d="M0 150 C 60 140, 100 90, 110 40 S 140 5, 170 0"/>
                <path d="M0 175 C 70 165, 118 105, 128 55 S 150 18, 170 12"/>
            </svg>
        </div>
        <div class="swoosh swoosh-br" aria-hidden="true">
            <svg viewBox="0 0 150 170">
                <path d="M0 40 C 55 50, 95 85, 105 125 S 135 160, 150 170"/>
                <path d="M0 62 C 60 72, 110 100, 120 140 S 140 165, 150 170"/>
            </svg>
        </div>

        <div class="brand-top">
            <img class="brand-logo" src="{{ \App\Models\Setting::getLogoUrl() }}" alt="Logo">
            <h1 class="brand-title">{{ $appName }}</h1>
            <div class="brand-school">{{ $schoolName }}</div>
        </div>

        <div class="brand-mid">
            {{-- Ilustrasi siswa: gambar dari public/images/login-illustration.webp --}}
            <img class="illus"
                 src="{{ asset('images/login-illustration.webp') }}"
                 alt="Siswa belajar bersama"
                 decoding="async"
                 fetchpriority="high">
        </div>

        <div class="brand-bottom">
            <div class="brand-icons">
                <div class="feat-icon"><i class='bx bx-calendar'></i></div>
                <div class="feat-icon"><i class='bx bx-qr-scan'></i></div>
                <div class="feat-icon"><i class='bx bx-chat'></i></div>
                <div class="feat-icon"><i class='bx bx-file'></i></div>
            </div>
            <div class="brand-foot">Presensi menjadi lebih mudah</div>
        </div>
    </section>

    {{-- ================= GELOMBANG PEMISAH (DESKTOP) ================= --}}
    {{-- Semua lapisan memakai kurva S yang sama, hanya digeser ke kanan,
         sehingga lebar tiap pita konstan dari atas sampai bawah. --}}
    <div class="wave-divider" aria-hidden="true">
        <svg viewBox="0 0 240 1000" preserveAspectRatio="none">
            <defs>
                <linearGradient id="gBand" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#c7d4ff"/>
                    <stop offset=".5" stop-color="#8aa3ff"/>
                    <stop offset="1" stop-color="#b3c4ff"/>
                </linearGradient>
                <linearGradient id="gGreenBand" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#bef264"/>
                    <stop offset=".4" stop-color="#8be02a"/>
                    <stop offset=".75" stop-color="#5fa80f"/>
                    <stop offset="1" stop-color="#a3e635"/>
                </linearGradient>
            </defs>
            <path fill="url(#gBand)"
                  d="M70 0 C 70 140, 45 220, 52 340 C 58 460, 95 520, 100 650 C 104 780, 88 900, 78 1000 L 260 1000 L 260 0 Z"/>
            <path fill="url(#gGreenBand)"
                  d="M78 0 C 78 140, 53 220, 60 340 C 66 460, 103 520, 108 650 C 112 780, 96 900, 86 1000 L 260 1000 L 260 0 Z"/>
            <path fill="#f0fdf4"
                  d="M85 0 C 85 140, 60 220, 67 340 C 73 460, 110 520, 115 650 C 119 780, 103 900, 93 1000 L 260 1000 L 260 0 Z"/>
            <path fill="#fff"
                  d="M88 0 C 88 140, 63 220, 70 340 C 76 460, 113 520, 118 650 C 122 780, 106 900, 96 1000 L 260 1000 L 260 0 Z"/>
        </svg>
    </div>

    {{-- ================= GELOMBANG PEMISAH (MOBILE) ================= --}}
    <div class="wave-mobile" aria-hidden="true">
        <svg viewBox="0 0 400 46" preserveAspectRatio="none">
            <path d="M0 22 C 100 0, 200 44, 400 12 L 400 46 L 0 46 Z" fill="#9db3ff"/>
            <path d="M0 28 C 100 6, 200 50, 400 20 L 400 46 L 0 46 Z" fill="#8be02a"/>
            <path d="M0 36 C 100 14, 200 54, 400 28 L 400 46 L 0 46 Z" fill="#fff"/>
        </svg>
    </div>

    {{-- ================= PANEL KANAN ================= --}}
    <section class="form-panel">
        <div class="form-wrap">
            <h2 class="form-heading">Masuk Akun</h2>
            <p class="form-subtext">Silakan masuk untuk melanjutkan ke sistem presensi.</p>

            @if (session('status'))
                <div class="alert alert-success alert-login">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                @csrf

                <div class="form-group-item">
                    <label class="input-label-custom" for="login">Username</label>
                    <div class="input-box-wrapper @error('login') is-invalid-field @enderror @error('email') is-invalid-field @enderror">
                        <span class="input-icon-box"><i class='bx bx-envelope'></i></span>
                        <input type="text" id="login" name="login" class="form-input-field"
                               value="{{ old('login', old('email')) }}"
                               placeholder="Masukkan Username"
                               autocomplete="username" required autofocus>
                    </div>
                    @error('login')
                        <div class="field-error-feedback"><i class='bx bx-error-circle'></i>{{ $message }}</div>
                    @enderror
                    @error('email')
                        <div class="field-error-feedback"><i class='bx bx-error-circle'></i>{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group-item">
                    <label class="input-label-custom" for="password">Kata Sandi</label>
                    <div class="input-box-wrapper @error('password') is-invalid-field @enderror">
                        <span class="input-icon-box"><i class='bx bx-lock-alt'></i></span>
                        <input type="password" id="password" name="password" class="form-input-field"
                               placeholder="Masukkan kata sandi"
                               autocomplete="current-password" required>
                        <button type="button" class="btn-toggle-eye" id="togglePassword" aria-label="Tampilkan kata sandi">
                            <i class='bx bx-hide' id="eyeIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="field-error-feedback"><i class='bx bx-error-circle'></i>{{ $message }}</div>
                    @enderror
                </div>

                <div class="row-extra">
                    <div class="remember-container">
                        <input type="checkbox" class="remember-checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="remember-label" for="remember">Ingat Saya</label>
                    </div>
                </div>

                <button type="submit" class="btn-submit-action" id="btnLogin">
                    <span id="btnText">Masuk Sekarang</span>
                    <span class="app-loader d-none" id="btnLoader"></span>
                </button>
            </form>
        </div>
    </section>
</div>

<script>
    const pwd = document.getElementById('password');
    const eye = document.getElementById('eyeIcon');
    document.getElementById('togglePassword').addEventListener('click', () => {
        const show = pwd.type === 'password';
        pwd.type = show ? 'text' : 'password';
        eye.className = show ? 'bx bx-show' : 'bx bx-hide';
    });

    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('btnLogin');
        btn.disabled = true;
        document.getElementById('btnText').textContent = 'Memproses...';
        document.getElementById('btnLoader').classList.remove('d-none');
    });
</script>

</body>
</html>