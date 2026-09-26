<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Masuk ke Dashboard | {{ \App\Models\Setting::getSchoolName() }}</title>

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
            --royal-blue: #3b62f6;
            --royal-blue-hover: #254fd9;
            --royal-blue-light: #eff4ff;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            background-color: #0f172a;
            background-image:
                linear-gradient(rgba(15, 23, 42, 0.58), rgba(15, 23, 42, 0.58)),
                url("{{ asset('images/bg.webp') }}");
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: var(--text-main);
        }

        /* Container Kartu Split Layout */
        .login-split-card {
            width: 100%;
            max-width: 1020px;
            background: transparent;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(59, 98, 246, 0.18), 0 0 0 1px rgba(226, 232, 240, 0.8);
            display: flex;
            overflow: hidden;
            min-height: 560px;
        }

        /* ============================================================ */
        /* SISI KIRI: PANEL BRANDING                                    */
        /* ============================================================ */
        .branding-panel {
            width: 45%;
            min-width: 380px;
            position: relative;
            border-radius: 20px 0 0 20px;
            padding: 3rem 2.25rem 2.25rem 2.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            color: #ffffff;
            overflow: hidden;
            background-color: transparent;
        }

        .branding-bg-image {
            display: none;
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            z-index: 0;
            opacity: 0.65;
            filter: contrast(1.05) brightness(0.95);
        }

        .branding-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(155deg, 
                rgba(59, 98, 246, 0.42) 0%,
                rgba(37, 99, 235, 0.38) 50%,
                rgba(29, 78, 216, 0.48) 100%);
            z-index: 1;
        }

        .branding-center-wrapper {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: auto 0;
            width: 100%;
            max-width: 340px;
        }

        .logo-hero-wrapper {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            background: transparent;
            border: none;
            box-shadow: none;
        }

        .school-logo-hero {
            width: 92px;
            height: 92px;
            object-fit: contain;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.3));
        }

        .school-tagline {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #dbeafe;
            margin-bottom: 0.4rem;
            display: inline-block;
            text-shadow: 0 1px 4px rgba(0,0,0,0.3);
        }

        .school-title-hero {
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.25;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.35);
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }

        .school-desc-hero {
            font-size: 0.84rem;
            color: #f1f5f9;
            line-height: 1.5;
            margin: 0 auto;
            font-weight: 500;
            text-shadow: 0 1px 6px rgba(0,0,0,0.3);
        }

        .badge-grid-hero {
            position: relative;
            z-index: 2;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.45rem;
            margin-top: 1.5rem;
            width: 100%;
        }

        .badge-soft-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.65rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: rgba(255, 255, 255, 0.98);
            font-size: 0.72rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .badge-soft-pill i {
            font-size: 0.85rem;
            color: #bfdbfe;
        }

        .branding-copyright {
            position: relative;
            z-index: 2;
            margin-top: 1.25rem;
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 600;
            text-shadow: 0 1px 4px rgba(0,0,0,0.3);
        }

        /* ============================================================ */
        /* SISI KANAN: FORM LOGIN UNIVERSAL                             */
        /* ============================================================ */
        .form-panel {
            width: 55%;
            flex: 1;
            padding: 3.25rem 2.75rem 2.75rem 2.75rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
            border-radius: 0 20px 20px 0;
        }

        .form-header-badge {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--royal-blue);
            margin-bottom: 0.25rem;
            display: block;
        }

        .form-heading {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.025em;
            margin-bottom: 0.35rem;
        }

        .form-subtext {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
            font-weight: 500;
            line-height: 1.45;
        }

        /* Field Input */
        .form-group-item {
            margin-bottom: 1.2rem;
        }

        .input-label-custom {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 0.4rem;
        }

        .input-box-wrapper {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.15rem 0.5rem;
            transition: all 0.2s ease;
        }

        .input-box-wrapper:focus-within {
            border-color: var(--royal-blue);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 98, 246, 0.12);
        }

        .input-box-wrapper.is-invalid-field {
            border-color: #ef4444;
            background-color: #fffafb;
        }

        .input-box-wrapper .input-icon-box {
            padding: 0.5rem 0.55rem;
            color: #94a3b8;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
        }

        .input-box-wrapper:focus-within .input-icon-box {
            color: var(--royal-blue);
        }

        .input-box-wrapper.is-invalid-field .input-icon-box {
            color: #ef4444;
        }

        .input-box-wrapper .form-input-field {
            border: none;
            background: transparent;
            width: 100%;
            padding: 0.6rem 0.35rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-main);
            outline: none;
        }

        .input-box-wrapper .form-input-field::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .btn-toggle-eye {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding: 0.5rem 0.55rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 1.2rem;
        }

        .btn-toggle-eye:hover {
            color: var(--text-main);
        }

        .field-error-feedback {
            color: #dc2626;
            font-size: 0.78rem;
            font-weight: 600;
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        /* Checkbox Ingat Saya */
        .remember-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.4rem;
            margin-left: 0.1rem;
        }

        .remember-checkbox {
            width: 1.05rem;
            height: 1.05rem;
            border-radius: 4px;
            border: 1.5px solid #cbd5e1;
            cursor: pointer;
            accent-color: var(--royal-blue);
            margin: 0;
        }

        .remember-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
            margin: 0;
        }

        /* Tombol Aksi Utama */
        .btn-submit-action {
            background-color: var(--royal-blue);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1.25rem;
            font-weight: 700;
            font-size: 0.92rem;
            box-shadow: 0 4px 14px rgba(59, 98, 246, 0.28);
            transition: all 0.2s ease;
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
        }

        .btn-submit-action:hover {
            background-color: var(--royal-blue-hover);
            box-shadow: 0 6px 16px rgba(59, 98, 246, 0.38);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* ============================================================ */
        /* OPTIMASI RESPONSIF MOBILE                                    */
        /* ============================================================ */
        @media (max-width: 991px) {
            body {
                min-height: 100vh;
                min-height: 100svh;
                padding:
                    calc(1rem + env(safe-area-inset-top, 0px))
                    calc(1rem + env(safe-area-inset-right, 0px))
                    calc(1rem + env(safe-area-inset-bottom, 0px))
                    calc(1rem + env(safe-area-inset-left, 0px));
                align-items: flex-start;
                background-color: #eff4ff;
                background-image: linear-gradient(135deg, #eef5ff 0%, #f8fbff 50%, #edf4ff 100%);
                background-attachment: scroll;
            }

            .login-split-card {
                flex-direction: column;
                width: 100%;
                max-width: 448px;
                min-height: 0;
                border-radius: 16px;
                margin: auto 0;
                background: transparent;
                box-shadow: none;
                overflow: visible;
            }

            .branding-panel {
                width: 100%;
                min-width: 100%;
                min-height: 0;
                aspect-ratio: 16 / 9;
                border-radius: 16px 16px 0 0;
                padding: 1rem 1.25rem;
                background: linear-gradient(155deg, #3b62f6 0%, #2563eb 55%, #1d4ed8 100%);
                justify-content: center;
            }

            .branding-bg-image {
                display: block;
            }

            .branding-overlay {
                background: linear-gradient(155deg,
                    rgba(59, 98, 246, 0.65) 0%,
                    rgba(37, 99, 235, 0.60) 50%,
                    rgba(29, 78, 216, 0.70) 100%);
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
            }

            .school-logo-hero {
                width: 56px;
                height: 56px;
            }

            .logo-hero-wrapper {
                margin-bottom: 0.6rem;
            }

            .school-tagline {
                font-size: 0.62rem;
                margin-bottom: 0.25rem;
            }

            .school-title-hero {
                font-size: 1.15rem;
                margin-bottom: 0.35rem;
            }

            .school-desc-hero {
                font-size: 0.74rem;
                max-width: 270px;
                line-height: 1.4;
            }

            .form-panel {
                width: 100%;
                border-radius: 16px;
                padding: 1.35rem 1.1rem 1.25rem;
                background: rgba(255, 255, 255, 0.96);
                box-shadow: 0 16px 36px rgba(15, 23, 42, 0.22);
            }

            .form-heading {
                font-size: 1.3rem;
            }

            .form-subtext {
                font-size: 0.8rem;
                margin-bottom: 1rem;
            }

            .form-group-item {
                margin-bottom: 0.85rem;
            }

            .input-box-wrapper .form-input-field {
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }

            .remember-container {
                margin-bottom: 1rem;
            }

            .btn-submit-action {
                min-height: 44px;
                padding: 0.7rem 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Container Split Layout Dua Kolom -->
<div class="login-split-card">

    <!-- ============================================== -->
    <!-- SISI KIRI: BRANDING PANEL                      -->
    <!-- ============================================== -->
    <div class="branding-panel">
        
        <!-- Foto Gedung Sekolah di Latar Belakang -->
        <img src="{{ asset('images/bg.webp') }}"
             alt="Gedung Sekolah" 
             class="branding-bg-image"
             onerror="this.style.display='none'">

        <!-- Overlay Gradasi Biru Terang Transparan -->
        <div class="branding-overlay"></div>

        <!-- Bagian Tengah: Pure Logo Terpusat & Identitas Sekolah -->
        <div class="branding-center-wrapper">
            <div class="logo-hero-wrapper">
                <img src="{{ asset(\App\Models\Setting::getLogo()) }}" 
                     alt="Logo Sekolah" 
                     class="school-logo-hero"
                     onerror="this.outerHTML='<div class=\'fs-3 fw-bold text-white\'>SMP</div>'">
            </div>

            <span class="school-tagline">SISTEM PRESENSI TERINTEGRASI</span>
            <h1 class="school-title-hero">
                {{ \App\Models\Setting::getSchoolName() }}
            </h1>
            <p class="school-desc-hero">
                Portal manajemen kehadiran digital berbasis QR Code & pemantauan presensi terpadu secara realtime.
            </p>
        </div>

        <!-- Bagian Bawah Kiri: Badges Fitur & Copyright (Tampil di Layar Besar) -->
        <div class="w-100 d-none d-lg-block" style="position: relative; z-index: 2;">
            <div class="badge-grid-hero">
                <span class="badge-soft-pill">
                    <i class='bx bx-check-shield'></i> Terpusat
                </span>
                <span class="badge-soft-pill">
                    <i class='bx bx-pulse'></i> Realtime
                </span>
                <span class="badge-soft-pill">
                    <i class='bx bx-qr-scan'></i> QR Scanner
                </span>
            </div>
            <div class="branding-copyright">
                &copy; {{ date('Y') }} {{ \App\Models\Setting::getSchoolName() }}
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- SISI KANAN: FORM LOGIN UNIVERSAL               -->
    <!-- ============================================== -->
    <div class="form-panel">
        <div>
            <span class="form-header-badge">Portal Presensi Terpadu</span>
            <h2 class="form-heading">Selamat Datang</h2>
            <p class="form-subtext">Masukkan kredensial akun Anda untuk masuk ke sistem presensi.</p>
        </div>

        <!-- Form Login Universal -->
        <form action="{{ route('login') }}" 
              method="POST" 
              id="formUniversalLogin" 
              autocomplete="on">
            @csrf

            <!-- Input Email / Username / NIP -->
            <div class="form-group-item">
                <label class="input-label-custom" for="loginInput">Email / Username</label>
                <div class="input-box-wrapper {{ $errors->has('login') ? 'is-invalid-field' : '' }}">
                    <span class="input-icon-box">
                        <i class='bx bx-user'></i>
                    </span>
                    <input type="text" 
                           name="login" 
                           id="loginInput" 
                           class="form-input-field" 
                           value="{{ old('login') }}" 
                           required 
                           autofocus
                           placeholder="Masukkan Email, Username, atau NIP"
                           autocomplete="username">
                </div>
                @if($errors->has('login'))
                    <div class="field-error-feedback">
                        <i class='bx bx-error-circle'></i>
                        <span>{{ $errors->first('login') }}</span>
                    </div>
                @endif
            </div>

            <!-- Input Kata Sandi -->
            <div class="form-group-item">
                <label class="input-label-custom" for="passwordInput">Kata Sandi</label>
                <div class="input-box-wrapper {{ $errors->has('password') ? 'is-invalid-field' : '' }}">
                    <span class="input-icon-box">
                        <i class='bx bx-lock-alt'></i>
                    </span>
                    <input type="password" 
                           name="password" 
                           id="passwordInput" 
                           class="form-input-field" 
                           required 
                           placeholder="Masukkan kata sandi"
                           autocomplete="current-password">
                    <button type="button" 
                            class="btn-toggle-eye" 
                            onclick="togglePasswordVisibility('passwordInput', 'eyeIconUniversal')" 
                            title="Lihat Kata Sandi"
                            aria-label="Lihat Kata Sandi">
                        <i class='bx bx-hide' id="eyeIconUniversal"></i>
                    </button>
                </div>
                @if($errors->has('password'))
                    <div class="field-error-feedback">
                        <i class='bx bx-error-circle'></i>
                        <span>{{ $errors->first('password') }}</span>
                    </div>
                @endif
            </div>

            <!-- Checkbox Ingat Saya -->
            <div class="remember-container">
                <input type="checkbox" name="remember" class="remember-checkbox" id="rememberMe" {{ old('remember') ? 'checked' : '' }}>
                <label class="remember-label" for="rememberMe">Ingat Saya</label>
            </div>

            <!-- Tombol Aksi "Masuk Sekarang" -->
            <button type="submit" id="btnSubmitLogin" class="btn-submit-action">
                <span id="btnTextLogin">Masuk Sekarang</span>
                <span class="spinner-border spinner-border-sm d-none" id="spinnerLogin" role="status"></span>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input || !icon) return;

        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');
        icon.classList.toggle('bx-hide', !isPassword);
        icon.classList.toggle('bx-show', isPassword);
    }

    const form = document.getElementById('formUniversalLogin');
    if (form) {
        form.addEventListener('submit', function () {
            const btn = document.getElementById('btnSubmitLogin');
            const txt = document.getElementById('btnTextLogin');
            const spn = document.getElementById('spinnerLogin');
            if (btn) btn.disabled = true;
            if (txt) txt.textContent = 'Memverifikasi...';
            if (spn) spn.classList.remove('d-none');
        });
    }
</script>

</body>
</html>