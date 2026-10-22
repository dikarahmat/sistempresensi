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
            --blue: #2563eb;
            --blue-dark: #1d4ed8;
            --navy: #1e3a8a;
            --sky: #dbeafe;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --page-bg: #faf8ff;
        }

        * { box-sizing: border-box; }
        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;
            color: var(--text-main);
            background: #fff;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* ===================== LAYOUT ===================== */
        .login-screen {
            display: flex;
            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;
        }

        /* ===================== PANEL KIRI (BRAND) ===================== */
        .brand-panel {
            position: relative;
            flex: 0 0 50%;
            width: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem;
            overflow: hidden;
            color: #fff;
            text-align: center;
            background: linear-gradient(180deg, #1e3a8a 0%, #1e3a8a 30%, #2563eb 100%);
            user-select: none;
        }

        /* grafis tipis: titik halus memudar ke tengah + cahaya lembut */
        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
        }
        .brand-panel::before {
            background-image: radial-gradient(rgba(255, 255, 255, .5) 1px, transparent 1.4px);
            background-size: 26px 26px;
            opacity: .22;
            -webkit-mask-image: radial-gradient(ellipse 62% 55% at 50% 50%, transparent 35%, #000 100%);
            mask-image: radial-gradient(ellipse 62% 55% at 50% 50%, transparent 35%, #000 100%);
        }
        .brand-panel::after {
            background:
                radial-gradient(ellipse 60% 45% at 100% 0%, rgba(147, 197, 253, .20) 0, transparent 70%),
                radial-gradient(ellipse 55% 40% at 0% 100%, rgba(255, 255, 255, .07) 0, transparent 70%);
        }

        .brand-inner {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 600px;
        }

        /* Logo: murni, tanpa kontainer */
        .brand-logo {
            display: block;
            width: 92px;
            height: 92px;
            margin-bottom: 1.5rem;
            object-fit: contain;
        }

        /* Teks header (DESKTOP & MOBILE satu gaya): semua uppercase, putih,
           bold, satu baris, rata tengah. Urutan: judul aplikasi dulu,
           lalu nama sekolah (sedikit lebih besar), lalu ikon fitur.
           Keterangan "Presensi digital ..." disembunyikan di semua layar.
           Mobile hanya menyesuaikan ukuran mengikuti lebar layar (lihat
           media query di bawah). */
        .brand-title {
            order: 1;
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            line-height: 1.3;
            letter-spacing: .06em;
            padding-left: .06em; /* kompensasi letter-spacing agar tetap center */
            text-transform: uppercase;
            white-space: nowrap;
            color: #fff;
        }
        .brand-school {
            order: 2;
            margin: .65rem 0 0;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.3;
            letter-spacing: .06em;
            padding-left: .06em;
            text-transform: uppercase;
            white-space: nowrap;
            color: #fff;
        }
        .brand-subtitle {
            display: none;
            margin: .9rem 0 0;
            font-size: 15px;
            font-weight: 400;
            line-height: 1.6;
            letter-spacing: .01em;
            color: rgba(219, 234, 254, .85);
        }
        .brand-chips {
            display: flex;
            flex-wrap: nowrap;
            justify-content: center;
            align-items: flex-start;
            gap: 1.25rem 2.5rem;
            width: 100%;
            order: 4;
            margin-top: 2.25rem;
        }
        .chip {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .45rem;
            min-width: 56px;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: .02em;
            color: rgba(255, 255, 255, .92);
        }
        .chip i { font-size: 28px; color: #bfdbfe; }

        /* ===================== PANEL KANAN (FORM) ===================== */
        .form-panel {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            overflow: hidden;
            background:
                radial-gradient(circle at 100% 0%, rgba(165, 180, 252, .22) 0, transparent 42%),
                radial-gradient(circle at 0% 100%, rgba(219, 234, 254, .55) 0, transparent 40%),
                #fff;
        }
        .form-wrap {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
        }

        .form-card {
            position: relative;
            background: #fff;
            border-radius: 24px;
            padding: 2.25rem;
            border: 1px solid #f1f5f9;
            box-shadow: 0 25px 50px -12px rgba(30, 58, 138, .16);
        }

        .form-heading {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -.02em;
            line-height: 1.2;
            color: var(--text-main);
        }
        .form-subtext {
            margin: .5rem 0 1.75rem;
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-muted);
        }

        .form-group-item { margin-bottom: 1.5rem; }
        .input-label-custom {
            position: absolute;
            top: -8px;
            left: 12px;
            z-index: 1;
            margin: 0;
            padding: 0 6px;
            background: #fff;
            font-size: 12px;
            font-weight: 500;
            line-height: 16px;
            letter-spacing: .01em;
            color: var(--text-muted);
            transition: color .2s;
        }
        .input-box-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: .5rem;
            height: 52px;
            padding: 0 1rem;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            transition: border-color .2s, box-shadow .2s;
        }
        .input-box-wrapper:focus-within {
            border-color: var(--blue);
            box-shadow: none;
        }
        .input-box-wrapper:focus-within .input-label-custom { color: var(--blue); }
        .input-box-wrapper.is-invalid-field { border-color: #ef4444; }
        .input-box-wrapper.is-invalid-field .input-label-custom { color: #ef4444; }

        .form-input-field {
            flex: 1;
            width: 100%;
            min-width: 0;
            height: 100%;
            padding: 0;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            background: transparent !important;
            font-family: inherit;
            font-size: 15px;
            font-weight: 500;
            color: var(--text-main) !important;
            -webkit-text-fill-color: var(--text-main);
            caret-color: var(--text-main);
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
            -webkit-text-fill-color: var(--text-main) !important;
            -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
            box-shadow: 0 0 0 1000px #fff inset !important;
            transition: background-color 9999s ease-out 0s;
        }

        .btn-toggle-eye {
            display: flex;
            align-items: center;
            flex-shrink: 0;
            padding: .25rem;
            border: none;
            background: transparent;
            font-size: 20px;
            color: #94a3b8;
            cursor: pointer;
            transition: color .2s;
        }
        .btn-toggle-eye:hover { color: var(--blue); }

        .field-error-feedback {
            display: flex;
            align-items: center;
            gap: .3rem;
            margin-top: .4rem;
            font-size: .8rem;
            font-weight: 600;
            color: #dc2626;
        }

        .row-extra { margin: .25rem 0 1.5rem; }
        .remember-container { display: flex; align-items: center; gap: .5rem; }
        .remember-checkbox {
            width: 16px;
            height: 16px;
            margin: 0;
            border-radius: 4px;
            cursor: pointer;
            accent-color: var(--blue);
        }
        .remember-label {
            margin: 0;
            font-size: 14px;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
        }

        .btn-submit-action {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 14px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: .02em;
            color: #fff;
            cursor: pointer;
            background: linear-gradient(90deg, #2563eb, #1d4ed8);
            box-shadow: 0 10px 22px rgba(37, 99, 235, .28);
            transition: box-shadow .2s, transform .15s, background .15s;
        }
        .btn-submit-action:hover {
            background: linear-gradient(90deg, #1d4ed8, #1e40af);
            box-shadow: 0 12px 28px rgba(37, 99, 235, .4);
        }
        .btn-submit-action:active { transform: scale(.99); }
        .btn-submit-action:disabled { opacity: .85; cursor: not-allowed; }

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

        .alert-login {
            margin-bottom: 1rem;
            padding: .65rem .9rem;
            border-radius: 12px;
            font-size: .85rem;
            font-weight: 600;
        }

        /* ==========================================================
           MOBILE / TABLET (< 992px): header gradasi + kartu menumpuk
        ========================================================== */
        @media (max-width: 991px) {
            body { background: var(--page-bg); }

            .login-screen { flex-direction: column; background: var(--page-bg); }

            /* ---------- HEADER BIRU ---------- */
            .brand-panel {
                flex: none;
                width: 100%;
                align-items: center;
                /* atas 2.75rem = jarak bawah (2.75rem) + tumpang-tindih kartu (4.9rem) */
                padding: calc(2.75rem + env(safe-area-inset-top, 0px)) 1rem 7.65rem;
                border-radius: 0 0 32px 32px;
            }
            .brand-inner { max-width: 520px; }

            .brand-logo {
                width: 68px;
                height: 68px;
                margin-bottom: 1.35rem;
            }
            /* Tiga baris teks header: SEMUA UPPERCASE, satu baris, rata tengah.
               Baris 1 & 2 ukurannya SAMA (dibedakan lewat ketebalan huruf),
               baris 3 lebih kecil karena kalimatnya dua kali lebih panjang.
               Ukuran mengikuti lebar layar (clamp) supaya tiap baris selalu
               muat satu baris dari HP kecil sampai besar. */
            .brand-school,
            .brand-title {
                font-size: clamp(14px, 4.5vw, 18px);
                line-height: 1.3;
                letter-spacing: .06em;
                padding-left: .06em;
                text-transform: uppercase;
                white-space: nowrap;
                color: #ffffff;
            }
            /* Urutan MOBILE: judul aplikasi dulu, lalu nama sekolah, lalu
               keterangan. Hanya urutan tampil (flex order); markup & urutan
               desktop tidak berubah. */
            /* Skala ukuran menurun, semua bold (700) kecuali keterangan:
                 1. SISTEM PRESENSI SEKOLAH  maks 17px
                 2. SMP PGRI PARUNG PANJANG  maks 19px (terbesar)
                 3. keterangan               disembunyikan di mobile */
            .brand-title {
                order: 1;
                margin-top: 0;
                font-size: clamp(13px, 4.2vw, 17px);
                font-weight: 700;
            }
            .brand-school {
                order: 2;
                margin-top: .5rem; /* gap judul -> sekolah */
                font-size: clamp(14px, 4.8vw, 19px);
                font-weight: 700;
            }
            .brand-subtitle {
                margin-top: .5rem; /* gap sekolah -> keterangan: SAMA dengan gap di atasnya */
                font-size: 12px;
                font-weight: 400;
                line-height: 1.5;
                letter-spacing: .03em;
                padding-left: .03em;
                text-transform: uppercase;
                /* Di HP lebar muat satu baris; di HP sempit turun jadi dua
                   baris yang seimbang (bukan terpotong). */
                white-space: normal;
                text-wrap: balance;
                max-width: 100%;
                color: #ffffff;
                order: 3;
            }
            /* Keterangan DISEMBUNYIKAN di mobile (hanya tampil di desktop). */
            .brand-subtitle { display: none; }
            /* Ikon fitur (Presensi / Kehadiran / Rekap) TAMPIL di mobile,
               di bawah nama sekolah, versi ringkas. */
            .brand-chips {
                display: flex;
                order: 4;
                gap: 0 1.75rem;
                margin-top: 1.25rem;
            }
            .chip { min-width: 52px; gap: .35rem; font-size: 11px; }
            .chip i { font-size: 22px; }

            /* ---------- AREA FORM ---------- */
            .form-panel {
                position: relative;
                z-index: 20;
                flex: 1;
                align-items: flex-start;
                padding: 0 1rem calc(1rem + env(safe-area-inset-bottom, 0px));
                margin-top: -4.9rem;
                overflow: visible;
                background: transparent;
            }
            .form-wrap { max-width: 520px; }

            /* ---------- KARTU ---------- */
            .form-card {
                padding: 1.5rem;
                border: 0;
                border-radius: 28px 28px 24px 24px;
                box-shadow: 0 20px 25px -5px rgba(0, 74, 198, .10), 0 8px 10px -6px rgba(0, 74, 198, .10);
            }

            .form-heading { font-size: 24px; letter-spacing: -.02em; }
            .form-subtext { font-size: 14px; margin-bottom: 1.5rem; }

            .form-group-item { margin-bottom: 1.35rem; }
            .form-input-field { font-size: 16px; } /* anti-zoom iOS */

            .row-extra { margin: .25rem 0 1.25rem; }

        }

        /* ==========================================================
           DESKTOP (>= 992px): tanpa kontainer kartu, form langsung
           di atas background putih (clean look)
        ========================================================== */
        @media (min-width: 992px) {
            .form-wrap { max-width: 380px; }
            .form-card {
                padding: 0;
                background: transparent;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }
        }

        @media (max-width: 380px) {
            .brand-school,
            .brand-title { letter-spacing: .04em; padding-left: .04em; }
            .brand-subtitle { letter-spacing: .02em; padding-left: .02em; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>

<div class="login-screen">

    {{-- ================= PANEL KIRI / HEADER MOBILE ================= --}}
    <section class="brand-panel">
        <div class="brand-inner">
            <img class="brand-logo" src="{{ \App\Models\Setting::getLogoUrl() }}" alt="Logo {{ $schoolName }}">

            <h2 class="brand-school">{{ $schoolName }}</h2>
            <h1 class="brand-title">{{ $appName }}</h1>
            <p class="brand-subtitle"><span class="sub-line">{{ __('Presensi digital yang') }}</span> <span class="sub-line">{{ __('mudah, cepat, dan akurat.') }}</span></p>

            <div class="brand-chips">
                <span class="chip"><i class='bx bx-qr-scan'></i>{{ __('Presensi') }}</span>
                <span class="chip"><i class='bx bx-calendar-check'></i>{{ __('Kehadiran') }}</span>
                <span class="chip"><i class='bx bx-folder-open'></i>{{ __('Rekap') }}</span>
            </div>
        </div>
    </section>

    {{-- ================= PANEL KANAN / KARTU LOGIN ================= --}}
    <section class="form-panel">
        <div class="form-wrap">

            <div class="form-card">
                <h2 class="form-heading">{{ __('Masuk Akun') }}</h2>
                <p class="form-subtext">{{ __('Masuk untuk memulai presensi hari ini.') }}</p>

                @if (session('status'))
                    <div class="alert alert-success alert-login">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                    @csrf

                    <div class="form-group-item">
                        <div class="input-box-wrapper @error('login') is-invalid-field @enderror">
                            <label class="input-label-custom" for="login">Username</label>
                            <input type="text" id="login" name="login" class="form-input-field"
                                   value="{{ old('login') }}"
                                   autocomplete="username" required autofocus>
                        </div>
                        @error('login')
                            <div class="field-error-feedback"><i class='bx bx-error-circle'></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group-item">
                        <div class="input-box-wrapper @error('password') is-invalid-field @enderror">
                            <label class="input-label-custom" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-input-field"
                                   autocomplete="current-password" required>
                            <button type="button" class="btn-toggle-eye" id="togglePassword" aria-label="{{ __('Tampilkan kata sandi') }}">
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
                            <label class="remember-label" for="remember">{{ __('Ingat saya') }}</label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-action" id="btnLogin">
                        <span id="btnText">{{ __('Masuk') }}</span>
                        <span class="app-loader d-none" id="btnLoader"></span>
                    </button>
                </form>
            </div>

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
        document.getElementById('btnText').textContent = {!! json_encode(__('Memproses...')) !!};
        document.getElementById('btnLoader').classList.remove('d-none');
    });
</script>

</body>
</html>