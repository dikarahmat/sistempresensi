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
            --split: 50%;      /* batas kiri/kanan: garis lurus tepat di tengah */
            --bar: 6px;        /* tebal garis hijau pemisah */
            --fade: 56px;      /* lebar gradasi hijau memudar ke arah kanan */
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
            flex: 0 0 var(--split);
            width: var(--split);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            text-align: center;
            color: #fff;
            padding: clamp(1.5rem, 5vh, 3.5rem) 2rem clamp(1.25rem, 4vh, 2.5rem) 2rem;
            overflow: hidden;
            background:
                radial-gradient(circle at 18% 12%, rgba(130, 160, 255, .65) 0, transparent 42%),
                radial-gradient(circle at 70% 95%, rgba(20, 50, 200, .6) 0, transparent 50%),
                linear-gradient(160deg, #4f74ff 0%, #3b62f6 48%, #1f44db 100%);
        }

        /* ---------- Lapisan dekorasi desktop (di belakang konten) ---------- */
        .deco {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .deco-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, .07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .07) 1px, transparent 1px);
            background-size: 52px 52px;
            -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 58%, #000 25%, transparent 75%);
            mask-image: radial-gradient(ellipse 70% 60% at 50% 58%, #000 25%, transparent 75%);
        }
        .deco-orb {
            position: absolute;
            border-radius: 50%;
        }
        .deco-orb.o1 {
            top: -14%; right: -16%;
            width: 52%; aspect-ratio: 1;
            background: radial-gradient(circle at 35% 35%, rgba(255, 255, 255, .22), rgba(255, 255, 255, .02) 65%, transparent 70%);
        }
        .deco-orb.o2 {
            bottom: -16%; right: -12%;
            width: 46%; aspect-ratio: 1;
            background: radial-gradient(circle at 40% 40%, rgba(10, 30, 140, .38), transparent 68%);
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
            margin: clamp(.6rem, 2vh, 1.3rem) 0 .1rem;
            font-size: clamp(1.15rem, 1.9vw, 1.9rem);
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -.01em;
            text-transform: uppercase;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, .25);
        }
        .brand-school {
            display: inline-block;
            margin-top: 0;
            font-size: clamp(1rem, 1.4vw, 1.4rem);
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: .02em;
            text-transform: uppercase;
            color: #ffffff;
            text-shadow: 0 2px 8px rgba(0, 0, 0, .22);
            position: relative;
        }

        .brand-mid {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            min-height: 0;
            margin-bottom: -8px;
        }
        .illus {
            display: block;
            width: min(100%, 36vw, 66vh);
            height: auto;
            max-width: 100%;
            object-fit: contain;
            filter: drop-shadow(0 18px 28px rgba(10, 30, 120, .3));
            transform: translateY(-25px);
        }

        .brand-bottom {
            margin-top: auto;
            padding-bottom: clamp(1rem, 2vh, 1.5rem);
        }
        .brand-icons {
            display: flex;
            justify-content: center;
            gap: clamp(1.1rem, 2vw, 1.8rem);
            margin-bottom: clamp(.75rem, 1.5vh, 1.25rem);
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

        /* ===================== PEMISAH LURUS HIJAU (DESKTOP) ===================== */
        .wave-divider {
            position: absolute;
            top: 0; bottom: 0;
            left: var(--split);
            width: var(--bar);
            z-index: 5;
            pointer-events: none;
            background: #4ade80;
        }
        .wave-divider::before { display: none; }
        .wave-mobile { display: none; }

        /* ===================== PANEL KANAN ===================== */
        .form-panel {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 3rem 2.5rem calc(3rem + var(--bar));
            background:
                radial-gradient(circle at 100% 100%, rgba(59, 98, 246, .08) 0, transparent 45%),
                radial-gradient(circle at 30% 100%, rgba(139, 224, 42, .09) 0, transparent 40%),
                #fff;
        }
        .form-wrap { position: relative; z-index: 10; width: 100%; max-width: 460px; }

        /* Blok khusus mobile: disembunyikan di desktop */
        .card-brand { display: none; }

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

        /* loader tombol */
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

        /* ==========================================================
           MOBILE / TABLET (< 992px)  -- SATU BLOK, SEMUA DI DALAMNYA
           Kartu putih solid di atas background biru, tanpa scroll.
        ========================================================== */
        @media (max-width: 991px) {
            html, body {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
                overflow: hidden;
                overscroll-behavior: none;
                background: linear-gradient(160deg, #4f74ff 0%, #3b62f6 48%, #1f44db 100%);
            }

            .login-screen {
                flex-direction: column;
                position: relative;
                width: 100%;
                min-height: 100dvh;
                margin: 0;
                padding: 0;
                overflow: hidden;
                background: linear-gradient(160deg, #4f74ff 0%, #3b62f6 48%, #1f44db 100%);
            }

            /* Panel biru lama, pemisah, dan semua hiasan: hilang di mobile */
            .brand-panel,
            .wave-divider,
            .wave-mobile { display: none !important; }

            .form-panel {
                position: relative;
                z-index: 2;
                flex: 1;
                height: 100%;
                min-height: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.75rem 2.25rem calc(1.75rem + env(safe-area-inset-bottom, 0px));
                background: transparent;
                overflow: hidden;
            }

            /* ---------- KARTU PUTIH SOLID ---------- */
            .form-wrap {
                width: 100%;
                max-width: none;
                margin: 0;
                background: #ffffff;
                border-radius: 10px;
                padding: 1.5rem 1.25rem 1.5rem;
                border: 1px solid rgba(255, 255, 255, .6);
                box-shadow: 0 24px 60px rgba(10, 30, 120, .35);
            }

            /* ---------- BLOK LOGO + JUDUL ---------- */
            .card-brand {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                width: fit-content;
                margin: 0 auto 1.25rem;
            }
            .card-brand-logo {
                width: 56px;
                height: 56px;
                object-fit: contain;
                margin-bottom: .6rem;
                border-radius: 8px;
                filter: drop-shadow(0 1px 2px rgba(5, 15, 90, .5));
            }
            .card-brand-title {
                font-size: clamp(1.05rem, 4.8vw, 1.25rem);
                line-height: 1.15;
                font-weight: 800;
                letter-spacing: -.01em;
                text-transform: uppercase;
                color: #0f172a;
                margin-bottom: .25rem;
                white-space: nowrap;
            }
            .card-brand-school {
                font-size: clamp(.85rem, 3.9vw, .98rem);
                line-height: 1.15;
                font-weight: 800;
                letter-spacing: -.01em;
                text-transform: uppercase;
                color: #0f172a;
                margin-top: .15rem;
                white-space: nowrap;
            }

            .form-heading, .form-subtext { display: none; }

            /* ---------- FIELD ---------- */
            .form-group-item { margin-bottom: .8rem; }
            .input-label-custom {
                font-size: .8rem;
                font-weight: 700;
                color: #0f172a;
                margin-bottom: .3rem;
            }
            .input-box-wrapper {
                border-radius: 10px;
                padding: 0 .6rem;
                min-height: 54px;
                background: #f8fafc;
                border: 1.5px solid #e2e8f0;
                box-shadow: none;
            }
            .input-box-wrapper:focus-within {
                border-color: #3b5bf5;
                box-shadow: 0 0 0 3px rgba(59, 91, 245, .15);
                background: #ffffff;
            }
            .input-icon-box { padding: .5rem .6rem; font-size: 1.2rem; color: #94a3b8; }
            .btn-toggle-eye { padding: .5rem .6rem; font-size: 1.2rem; color: #94a3b8; }
            .input-box-wrapper:focus-within .input-icon-box,
            .input-box-wrapper:focus-within .btn-toggle-eye { color: #3b5bf5; }

            .form-input-field {
                padding: .85rem .4rem;
                font-size: 16px; /* anti-zoom iOS */
                font-weight: 500;
            }

            /* ---------- INGAT SAYA ---------- */
            .row-extra { margin-top: .5rem; margin-bottom: .8rem; gap: .4rem; }
            .remember-label { font-size: .8rem; color: #334155; }
            .remember-checkbox {
                appearance: none;
                -webkit-appearance: none;
                width: 22px;
                height: 22px;
                border-radius: 5px;
                border: 1.5px solid #d1d5db;
                background: #ffffff;
                cursor: pointer;
            }
            .remember-checkbox:checked {
                background-color: #3b5bf5;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 6L9 17l-5-5'/%3E%3C/svg%3E");
                background-size: 16px 16px;
                background-position: center;
                background-repeat: no-repeat;
            }
            .remember-checkbox:focus-visible {
                outline: none;
                box-shadow: 0 0 0 3px rgba(59, 91, 245, .3);
            }

            /* ---------- TOMBOL ---------- */
            .btn-submit-action {
                min-height: 54px;
                padding: .85rem 1.25rem;
                border-radius: 10px;
                background: #3b5bf5;
                color: #ffffff;
                box-shadow: 0 10px 24px rgba(59, 91, 245, .35);
            }
            .btn-submit-action:hover { background: #2f4ce0; }
            .btn-submit-action:active { transform: translateY(1px) scale(.985); }

            .alert-login {
                background: #fef2f2;
                border: 1px solid #fecaca;
                color: #991b1b;
                border-radius: 10px;
            }
        }

        /* Tablet (576-991px) */
        @media (min-width: 576px) and (max-width: 991px) {
            .form-panel { padding-left: 1.25rem; padding-right: 1.25rem; }
            .form-wrap { width: 86%; max-width: 540px; }
        }

        /* Layar sempit sekali */
        @media (max-width: 340px) {
            .form-panel { padding-left: 1.25rem; padding-right: 1.25rem; }
        }

        /* Layar pendek: padatkan agar kartu tetap utuh tanpa scroll */
        @media (max-width: 991px) and (max-height: 700px) {
            .form-wrap { padding: 1.5rem 1.1rem 1.4rem; }
            .card-brand { margin-bottom: 1rem; }
            .card-brand-logo { width: 56px; height: 56px; margin-bottom: .6rem; }
            .card-brand-title { margin-bottom: .2rem; }
            .form-group-item { margin-bottom: .65rem; }
            .input-box-wrapper { min-height: 46px; }
            .btn-submit-action { min-height: 46px; padding: .7rem 1rem; }
            .row-extra { margin-top: .5rem; margin-bottom: .9rem; }
            .alert-login { padding: .55rem .8rem; margin-bottom: .75rem; }
        }
        @media (max-width: 991px) and (max-height: 600px) {
            .form-wrap { padding: 1.25rem 1rem 1.2rem; }
            .card-brand-logo { width: 48px; height: 48px; margin-bottom: .4rem; }
            .form-group-item { margin-bottom: .5rem; }
            .input-box-wrapper { min-height: 44px; }
            .btn-submit-action { min-height: 44px; padding: .65rem 1rem; }
        }
    </style>
</head>
<body>

<div class="login-screen">

    {{-- ================= PANEL KIRI ================= --}}
    <section class="brand-panel">

        <div class="deco" aria-hidden="true">
            <div class="deco-grid"></div>
            <div class="deco-orb o1"></div>
            <div class="deco-orb o2"></div>
        </div>

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
            <img class="illus"
                 src="{{ asset('images/login-illustration.webp') }}"
                 alt="{{ __('Siswa belajar bersama') }}"
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
            <div class="brand-foot">{{ __('Presensi menjadi lebih mudah') }}</div>
        </div>
    </section>

    {{-- ================= PEMISAH HIJAU LURUS (DESKTOP) ================= --}}
    <div class="wave-divider" aria-hidden="true"></div>

    {{-- ================= PEMISAH (MOBILE, disembunyikan) ================= --}}
    <div class="wave-mobile" aria-hidden="true"></div>

    {{-- ================= PANEL KANAN ================= --}}
    <section class="form-panel">
        <div class="form-wrap">
            {{-- Blok card-brand: hanya tampil di mobile (< 992px) --}}
            <div class="card-brand" aria-hidden="true">
                <img class="card-brand-logo" src="{{ \App\Models\Setting::getLogoUrl() }}" alt="Logo">
                <div class="card-brand-title">{{ $appName }}</div>
                <div class="card-brand-school">{{ $schoolName }}</div>
            </div>

            <h2 class="form-heading">{{ __('Masuk Akun') }}</h2>
            <p class="form-subtext">{{ __('Silakan masuk untuk melanjutkan ke sistem presensi.') }}</p>

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
                               placeholder="{{ __('Masukkan Username') }}"
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
                    <label class="input-label-custom" for="password">{{ __('Kata Sandi') }}</label>
                    <div class="input-box-wrapper @error('password') is-invalid-field @enderror">
                        <span class="input-icon-box"><i class='bx bx-lock-alt'></i></span>
                        <input type="password" id="password" name="password" class="form-input-field"
                               placeholder="{{ __('Masukkan kata sandi') }}"
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
                        <label class="remember-label" for="remember">{{ __('Ingat Saya') }}</label>
                    </div>
                </div>

                <button type="submit" class="btn-submit-action" id="btnLogin">
                    <span id="btnText">{{ __('Masuk Sekarang') }}</span>
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
        document.getElementById('btnText').textContent = {!! json_encode(__('Memproses...')) !!};
        document.getElementById('btnLoader').classList.remove('d-none');
    });
</script>

</body>
</html>