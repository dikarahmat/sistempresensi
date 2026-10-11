@php
    // Logo kanan (UNPAM)
    $logoKanan = 'images/logo-unpam.webp';

    // Logo kiri (SMP PGRI): isi manual kalau mau, contoh 'images/logo-smp.webp'.
    // Kalau dikosongkan, dicari otomatis di public/images (nama berisi pgri / smp / sekolah / logo).
    $logoKiri = null;
    if (!$logoKiri) {
        $imgs = array_values(array_filter(
            @scandir(public_path('images')) ?: [],
            fn ($f) => preg_match('/\.(webp|png|jpe?g|svg)$/i', $f)
                && stripos($f, 'unpam') === false
                && stripos($f, 'maintenance') === false
        ));
        foreach (['pgri', 'smp', 'sekolah', 'logo'] as $kata) {
            foreach ($imgs as $f) {
                if (!$logoKiri && stripos($f, $kata) !== false) { $logoKiri = 'images/' . $f; }
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700;800;900&display=swap" rel="stylesheet">
    <title>Sedang Pemeliharaan | SMP PGRI Parungpanjang</title>
    <style>
        :root {
            --blue-1: #1260a8;
            --blue-2: #0d4a88;
            --navy: #0b2a55;
            --gold: #d4b05f;
            --orange: #f08a24;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            font-family: "Montserrat", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            background: var(--blue-2);
            color: #fff;
            -webkit-font-smoothing: antialiased;
        }

        .page {
            position: relative;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background:
                radial-gradient(900px 500px at 10% -10%, rgba(255,255,255,.10), transparent 60%),
                linear-gradient(180deg, var(--blue-1) 0%, var(--blue-2) 100%);
        }

        /* ---------- Teks & Logo (Desktop Khusus) ---------- */
        /* ===== HEADER DESKTOP: POSISI TENGAH ===== */
.hero {
    position: relative;
    z-index: 2;
    flex: 1 1 auto;
    min-height: 150px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 16px clamp(20px, 4vw, 48px);
}

.row {
    position: relative;
    width: 100%;
    max-width: 1100px;
    margin-inline: auto;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    grid-template-areas:
        ". k ."
        "l h r";
    align-items: center;
    column-gap: 0;
}
/* Logo desktop umum, sebelah kanan */
.desktop-logo {
    --s: clamp(70px, 7vw, 95px);
    width: var(--s);
    height: var(--s);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

/* Ukuran dasar logo kanan */
.desktop-logo {
    --s: clamp(85px, 8vw, 115px);
    width: var(--s);
    height: var(--s);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

/* Logo kiri tetap */
.desktop-logo.l {
    grid-area: l;
    justify-self: end;
    margin-right: clamp(28px, 2.8vw, 45px);
    --s: clamp(58px, 5.8vw, 78px);
}

/* Logo kanan lebih besar */
.desktop-logo.r {
    grid-area: r;
    justify-self: start;
    margin-left: clamp(28px, 2.8vw, 45px);
}

.desktop-logo img {
    display: block;
    width: 100%;
    height: 100%;
    max-width: 100%;
    object-fit: contain;
    filter: drop-shadow(0 6px 14px rgba(4, 20, 48, .35));
}

/* Teks atas selalu tepat di tengah */
.kicker {
    grid-area: k;
    justify-self: center;
    width: 100%;
    margin: 0 0 4px;
    padding: 0;
    text-align: center;
    font-size: clamp(13px, 1.6vw, 22px);
    font-weight: 800;
    letter-spacing: .28em;
    text-transform: uppercase;
    color: #fff;
    text-shadow: 0 2px 10px rgba(4, 20, 48, .45);
}

/* Judul berada tepat di sumbu tengah halaman */
h1 {
    grid-area: h;
    justify-self: center;
    margin: 0;
    text-align: center;
    font-size: clamp(28px, 4.1vw, 66px);
    font-weight: 900;
    line-height: 1.02;
    letter-spacing: .01em;
    text-transform: uppercase;
    white-space: nowrap;
    filter: drop-shadow(0 4px 0 rgba(4, 20, 48, .38))
            drop-shadow(0 10px 22px rgba(4, 20, 48, .35));
}

/* Tagline dinaikkan sedikit dan tetap di tengah */
.tagline {
    position: relative;
    align-self: center;
    margin-top: 2px;
    transform: translateY(-5px);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    text-align: center;
    font-size: clamp(11px, 1.4vw, 20px);
    font-weight: 800;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: #fff;
    text-shadow: 0 2px 10px rgba(4, 20, 48, .45);
}

.tagline::before,
.tagline::after {
    content: "";
    display: block;
    flex: 0 0 clamp(20px, 3vw, 48px);
    height: 2.5px;
    border-radius: 3px;
    background: var(--gold);
}

/* ===== MOBILE ===== */
@media (max-width: 768px) {
    .hero {
        padding: 20px 16px 8px;
    }

    .row {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
    }

    .desktop-logo.l,
    .desktop-logo.r {
        display: none;
    }

    .mobile-logos {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        margin-bottom: 8px;
    }

    .mobile-logo {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mobile-logo.l {
        width: 48px;
        height: 48px;
    }

    .mobile-logo.r {
        width: 58px;
        height: 58px;
    }

    .mobile-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }

    .kicker {
        font-size: 20px;
        letter-spacing: normal;
        margin: 0;
    }

    h1 {
        width: 100%;
        margin: 0;
        font-size: clamp(26px, 8vw, 44px);
        line-height: 1.08;
        white-space: normal;
        text-align: center;
    }

    h1 .w1,
    h1 .w2 {
        display: inline;
    }

    .tagline {
        margin-top: 4px;
        transform: translateY(-2px);
        font-size: clamp(13px, 4vw, 20px);
        letter-spacing: normal;
        text-align: center;
    }

    .tagline::before,
    .tagline::after {
        display: none;
    }
}

@media (min-width: 769px) {
    .mobile-logos {
        display: none;
    }
}

@media (max-width: 768px) and (max-height: 720px) {
    .hero {
        padding-top: 10px;
    }

    .mobile-logo.l {
        width: 42px;
        height: 42px;
    }

    .mobile-logo.r {
        width: 50px;
        height: 50px;
    }

    .kicker {
        font-size: 16px;
    }

    h1 {
        font-size: clamp(22px, 7.5vw, 34px);
    }

    .tagline {
        margin-top: 4px;
        font-size: 16px;
    }

    .load {
        margin-top: 10px;
    }

    .fx .lo {
        display: none;
    }
}
        h1 .w1 {
            color: #e0b950;
            background: linear-gradient(180deg, #f8e19a 0%, #dcb14c 55%, #b98f33 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        h1 .w2 { color: #fff; }

        .tagline {
            margin-top: clamp(2px, 0.5vh, 6px);
            transform: translateY(-4px);
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: clamp(11px, 1.4vw, 20px);
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: #fff;
            text-shadow: 0 2px 10px rgba(4, 20, 48, .45);
        }
        .tagline::before, .tagline::after {
            content: "";
            width: clamp(20px, 3vw, 48px);
            height: 2.5px;
            border-radius: 3px;
            background: var(--gold);
        }

        /* ---------- Ilustrasi Bawah ---------- */
        .scene {
            position: relative;
            z-index: 1;
            flex: none;
            height: min(27vw, 56vh);
            height: min(27vw, 56dvh);
            overflow: hidden;
            line-height: 0;
        }
        .scene svg {
            position: absolute;
            left: 0; bottom: 0;
            width: 100%;
            height: 100%;
        }

        /* ---------- Animasi ---------- */
        .spin    { transform-box: fill-box; transform-origin: center; animation: spin 14s linear infinite; }
        .spin.rev  { animation-direction: reverse; animation-duration: 10s; }
        .bar-move  { animation: bar 1s linear infinite; }
        .blink     { animation: blink 2.4s ease-in-out infinite; }
        .blink.d2  { animation-delay: .8s; }
        .blink.d3  { animation-delay: 1.6s; }
        .hook      { transform-box: fill-box; transform-origin: 50% 0; animation: sway 4.5s ease-in-out infinite; }
        .tape      { animation: tape 3s ease-in-out infinite; transform-box: fill-box; transform-origin: center; }
        .hand      { transform-box: fill-box; transform-origin: 0 100%; animation: wave 2.2s ease-in-out infinite; }

        @keyframes spin  { to { transform: rotate(360deg); } }
        @keyframes bar   { to { transform: translateX(-24px); } }
        @keyframes blink { 0%, 100% { opacity: .25; } 50% { opacity: 1; } }
        @keyframes sway  { 0%, 100% { transform: rotate(-2.5deg); } 50% { transform: rotate(2.5deg); } }
        @keyframes tape  { 0%, 100% { transform: scaleX(1); } 50% { transform: scaleX(1.012); } }
        @keyframes wave  { 0%, 100% { transform: rotate(0deg); } 50% { transform: rotate(-9deg); } }

        @media (prefers-reduced-motion: reduce) {
            .spin, .bar-move, .blink, .hook, .tape, .hand, .fx *, .load i { animation: none !important; }
        }

        /* ---------- Efek IT (hanya tampil di mobile) ---------- */
        .fx, .load { display: none; }
        @keyframes floaty { 0%, 100% { transform: translateY(0); opacity: .35; } 50% { transform: translateY(-12px); opacity: .85; } }
        @keyframes scan   { from { transform: translateY(-140px); } to { transform: translateY(110vh); } }
        @keyframes shim   { from { transform: translateX(-100%); } to { transform: translateX(260%); } }
        @keyframes orb    { 0%, 100% { transform: scale(1); opacity: .55; } 50% { transform: scale(1.15); opacity: .8; } }

        /* ---------- Mobile ---------- */
        @media (max-width: 768px) {
            .hero { padding: 20px 16px 8px; }
            
            .row {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                row-gap: 12px;
                width: 100%;
            }

            .mobile-logos {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: px;
                margin-bottom: 8px;
            }

            /* Pengaturan khusus logo mobile */
            .mobile-logo {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .mobile-logo.l { --s: 52px; width: var(--s); height: var(--s); }
            .mobile-logo.r { --s: 82px; width: var(--s); height: var(--s); }
            .mobile-logo img {
                height: 100%;
                width: 100%;
                object-fit: contain;
                display: block;
                filter: drop-shadow(0 4px 10px rgba(4, 20, 48, .45));
            }

            .kicker {
                display: block;
                font-size: 20px;
                font-weight: 800;
                letter-spacing: normal;
                padding-left: 0;
                text-transform: uppercase;
                color: #fff;
                text-shadow: 0 2px 10px rgba(4, 20, 48, .45);
                margin: 0 auto;
                text-align: center;
            }

            h1 {
                margin-top: 2px;
                font-size: clamp(26px, 8vw, 44px);
                white-space: normal;
                text-align: center;
            }
            h1 .w1, h1 .w2 { display: inline; }

            .tagline {
                font-size: 20px;
                letter-spacing: normal;
                margin-top: 10px;
                text-align: center;
            }
            .tagline::before, .tagline::after { display: none; }

            .load {
                display: block;
                position: relative;
                width: 132px; height: 4px;
                margin-top: 14px;
                border-radius: 4px;
                background: rgba(255, 255, 255, .18);
                overflow: hidden;
            }
            .load i {
                position: absolute; inset: 0 auto 0 0;
                width: 40%;
                border-radius: 4px;
                background: linear-gradient(90deg, transparent, var(--gold), #fff3c4);
                animation: shim 1.6s ease-in-out infinite;
            }

            .scene { height: 44vw; }
            .scene svg {
                left: 50%;
                width: 138vw;
                height: auto;
                aspect-ratio: 1200 / 384;
                transform: translateX(-50%);
            }

            .fx { display: block; position: absolute; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
            .fx .grid {
                position: absolute; inset: 0;
                background-image:
                    linear-gradient(rgba(160, 205, 255, .09) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(160, 205, 255, .09) 1px, transparent 1px);
                background-size: 34px 34px;
                -webkit-mask-image: linear-gradient(180deg, transparent 0%, #000 22%, #000 72%, transparent 100%);
                mask-image: linear-gradient(180deg, transparent 0%, #000 22%, #000 72%, transparent 100%);
            }
            .fx .scan {
                position: absolute; left: 0; right: 0; top: 0; height: 140px;
                background: linear-gradient(180deg, transparent, rgba(130, 195, 255, .16), transparent);
                animation: scan 7s linear infinite;
            }
            .fx .orb { position: absolute; border-radius: 50%; filter: blur(34px); animation: orb 6s ease-in-out infinite; }
            .fx .o1 { width: 220px; height: 220px; left: 50%; top: 40%; margin-left: -110px; background: rgba(70, 150, 235, .45); }
            .fx .o2 { width: 160px; height: 160px; right: -50px; top: 8%; background: rgba(212, 176, 95, .22); animation-delay: 2s; }
            .fx .sym {
                position: absolute;
                font: 700 15px/1 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                color: #9ccaff;
                animation: floaty 5s ease-in-out infinite;
            }
            .fx .ico {
                position: absolute; width: 30px; height: 30px;
                fill: none; stroke: #9ccaff; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round;
                color: #9ccaff;
                animation: floaty 6s ease-in-out infinite;
            }
            .fx .s1 { top: 14%; left: 12%; }
            .fx .s2 { top: 20%; right: 10%; animation-delay: 1.2s; }
            .fx .s3 { top: 66%; left: 38%; animation-delay: 2.1s; }
            .fx .s4 { top: 62%; right: 10%; animation-delay: .6s; }
            .fx .i1 { top: 7%; left: 8%; animation-delay: .4s; }
            .fx .i2 { top: 6%; right: 9%; stroke: #e3bf6a; animation-delay: 1.6s; }
            .fx .i3 { top: 58%; left: 7%; animation-delay: 2.6s; }
            .fx .i4 { top: 66%; right: 24%; stroke: #e3bf6a; animation-delay: 1s; }
        }

        /* Sembunyikan elemen logo khusus mobile saat di desktop, dan sebaliknya */
        @media (min-width: 769px) {
            .mobile-logos { display: none; }
        }
        @media (max-width: 768px) {
            .row .desktop-logo.l, .row .desktop-logo.r { display: none; }
        }

        /* ---------- Layar pendek / Laptop kecil ---------- */
        @media (min-width: 769px) and (max-height: 720px) {
            .scene { height: min(24vw, 48vh); height: min(24vw, 48dvh); }
        }
        @media (max-width: 768px) and (max-height: 720px) {
            .hero { padding-top: 10px; }
            .mobile-logo.l { --s: 42px; width: 42px; height: 42px; }
            .mobile-logo.r { --s: 50px; width: 50px; height: 50px; }
            .kicker { font-size: 16px; }
            h1 { font-size: clamp(22px, 7.5vw, 34px); }
            .tagline { margin-top: 6px; font-size: 16px; }
            .load { margin-top: 10px; }
            .fx .lo { display: none; }
        }
    </style>
</head>
<body>
<main class="page">

    <div class="fx" aria-hidden="true">
        <i class="orb o1"></i><i class="orb o2"></i>
        <i class="grid"></i><i class="scan"></i>
        <span class="sym s1">&lt;/&gt;</span>
        <span class="sym s2">{ }</span>
        <span class="sym s3 lo">01</span>
        <span class="sym s4 lo">&gt;_</span>
        <svg class="ico i1" viewBox="0 0 24 24"><rect x="7" y="7" width="10" height="10" rx="1.5"/><path d="M10 3v4M14 3v4M10 17v4M14 17v4M3 10h4M3 14h4M17 10h4M17 14h4"/></svg>
        <svg class="ico i2" viewBox="0 0 24 24"><path d="M12 3l8 3v6c0 5-3.4 8.2-8 9-4.6-.8-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg>
        <svg class="ico i3 lo" viewBox="0 0 24 24"><path d="M2 9a15 15 0 0 1 20 0M5.5 12.5a10 10 0 0 1 13 0M9 16a5 5 0 0 1 6 0"/><circle cx="12" cy="19.5" r="1" fill="currentColor"/></svg>
        <svg class="ico i4 lo" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg>
    </div>

    <header class="hero">
        <!-- Logo khusus tampilan Mobile -->
        <div class="mobile-logos">
            <div class="mobile-logo l">@if($logoKiri)<img src="{{ asset($logoKiri) }}" alt="Logo SMP PGRI Parungpanjang" onerror="this.style.display='none'">@endif</div>
            <div class="mobile-logo r">@if($logoKanan)<img src="{{ asset($logoKanan) }}" alt="Logo Universitas Pamulang" onerror="this.style.display='none'">@endif</div>
        </div>

        <div class="row">
            <div class="desktop-logo l">@if($logoKiri)<img src="{{ asset($logoKiri) }}" alt="Logo SMP PGRI Parungpanjang" onerror="this.style.display='none'">@endif</div>
            <span class="kicker">Kami Sedang</span>
            <div class="desktop-logo r">@if($logoKanan)<img src="{{ asset($logoKanan) }}" alt="Logo Universitas Pamulang" onerror="this.style.display='none'">@endif</div>
            <h1><span class="w1">Pemeliharaan</span> <span class="w2">Sistem</span></h1>
        </div>
        <p class="tagline">Kami akan segera kembali!</p>
    </header>

    <div class="scene" aria-hidden="true">
        <svg viewBox="0 36 1200 384" preserveAspectRatio="xMidYMax meet" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="haz" width="44" height="44" patternUnits="userSpaceOnUse" patternTransform="rotate(-35)">
                    <rect width="22" height="44" fill="#c9a85c"/>
                    <rect x="22" width="22" height="44" fill="#0b2a55"/>
                </pattern>
                <pattern id="stripe" width="24" height="24" patternUnits="userSpaceOnUse" patternTransform="rotate(-45)">
                    <rect width="12" height="24" fill="#1a5fb0"/>
                    <rect x="12" width="12" height="24" fill="#3b82d6"/>
                </pattern>
                <pattern id="lat" width="22" height="22" patternUnits="userSpaceOnUse">
                    <path d="M0 0 L22 22 M22 0 L0 22" stroke="#a9c7ea" stroke-width="2.5" fill="none"/>
                </pattern>
                <clipPath id="barclip"><rect x="470" y="94" width="190" height="16" rx="3"/></clipPath>
                <linearGradient id="glass" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#fff" stop-opacity=".35"/>
                    <stop offset="1" stop-color="#fff" stop-opacity="0"/>
                </linearGradient>
            </defs>

            <!-- jalur sirkuit latar -->
            <g fill="none" stroke="#7fb0e6" stroke-width="2" opacity=".28" stroke-linecap="round">
                <path d="M0 150 H70 L95 125 H190"/>
                <path d="M0 190 H40 L70 220 H150"/>
                <path d="M1200 120 H1120 L1095 95 H1010"/>
                <path d="M1200 170 H1150 L1125 195 H1060"/>
                <path d="M1200 60 H1160 V30 H1100"/>
            </g>
            <g fill="#7fb0e6" opacity=".4">
                <circle cx="190" cy="125" r="4"/><circle cx="150" cy="220" r="4"/>
                <circle cx="1010" cy="95" r="4"/><circle cx="1060" cy="195" r="4"/><circle cx="1100" cy="30" r="4"/>
            </g>

            <!-- tanda + -->
            <g fill="#cfe3fa" font-weight="700" font-family="Arial, sans-serif" text-anchor="middle">
                <text class="blink"    x="215" y="105" font-size="30">+</text>
                <text class="blink d2" x="395" y="60"  font-size="26">+</text>
                <text class="blink d3" x="905" y="160" font-size="26">+</text>
                <text class="blink"    x="95"  y="320" font-size="24">+</text>
                <text class="blink d2" x="885" y="318" font-size="24">+</text>
                <text class="blink d3" x="855" y="395" font-size="24">+</text>
            </g>

            <!-- lantai -->
            <rect x="-400" y="330" width="2000" height="120" fill="#e3ecf7"/>
            <g fill="none" stroke="#5b8fcf" stroke-width="2.2" stroke-linecap="round" opacity=".85">
                <path d="M560 340 V372 L520 392 V420"/>
                <path d="M600 340 V420"/>
                <path d="M640 340 V372 L680 392 V420"/>
                <path d="M720 345 H780 L800 365 H870 L890 385 H960"/>
                <path d="M470 345 H410 L390 365 H330 L310 385 H240"/>
            </g>
            <g fill="#e3ecf7" stroke="#5b8fcf" stroke-width="2.2" opacity=".9">
                <circle cx="960" cy="385" r="5"/><circle cx="240" cy="385" r="5"/>
            </g>

            <!-- perangkat di lantai -->
            <rect x="-30" y="330" width="130" height="60" rx="6" fill="#1c4f93" transform="rotate(-4 35 360)"/>
            <rect x="-20" y="395" width="170" height="40" rx="6" fill="#16427c"/>
            <rect x="60" y="400" width="26" height="10" fill="#c9a85c"/>
            <rect x="1090" y="325" width="140" height="80" rx="8" fill="#1c4f93" transform="rotate(5 1160 365)"/>
            <rect x="1130" y="170" width="100" height="150" rx="6" fill="#16427c" opacity=".85"/>

            <!-- layar kode di belakang -->
            <rect x="225" y="160" width="160" height="125" rx="6" fill="#0b2a55"/>
            <rect x="225" y="160" width="160" height="12" rx="6" fill="#16427c"/>
            <g fill="#c9a85c" opacity=".9"><circle cx="234" cy="166" r="2"/><circle cx="242" cy="166" r="2"/><circle cx="250" cy="166" r="2"/></g>
            <g stroke-width="3" stroke-linecap="round" fill="none">
                <path d="M236 186 H300 M236 196 H280 M244 206 H320 M244 216 H290 M236 226 H310 M244 236 H270 M236 246 H300 M236 256 H284" stroke="#8fb4e3" stroke-dasharray="14 4 8 4 20 4"/>
                <path d="M318 186 H372 M300 206 H368 M312 226 H372 M290 246 H360" stroke="#c9a85c" stroke-dasharray="10 4 18 4"/>
            </g>

            <!-- derek -->
            <g>
                <rect x="1090" y="105" width="22" height="230" fill="#1c4f93"/>
                <rect x="1090" y="105" width="22" height="230" fill="url(#lat)"/>
                <rect x="1086" y="325" width="30" height="10" fill="#16427c"/>
                <rect x="1060" y="333" width="85" height="10" rx="3" fill="#a9c7ea"/>
                <rect x="945" y="96" width="250" height="10" fill="#a9c7ea"/>
                <path d="M945 96 H1100 L1100 76 Z" fill="none" stroke="#a9c7ea" stroke-width="3"/>
                <path d="M1100 76 L1100 96 M1100 76 L1060 96" stroke="#a9c7ea" stroke-width="3"/>
                <rect x="1084" y="86" width="34" height="22" rx="3" fill="#7fb0e6"/>
                <rect x="1170" y="82" width="26" height="28" fill="#5b8fcf"/>
                <g class="hook">
                    <path d="M975 106 V180 M1015 106 V180" stroke="#a9c7ea" stroke-width="2.5"/>
                    <rect x="940" y="180" width="110" height="9" fill="#a9c7ea"/>
                    <rect x="962" y="152" width="66" height="28" fill="#7fb0e6" opacity=".7"/>
                </g>
            </g>

            <!-- roda gigi -->
            <g fill="none" stroke="#c9a85c" stroke-linecap="butt">
                <g transform="translate(805 72)"><g class="spin">
                    <circle r="28" stroke-width="12" stroke-dasharray="9.2 6.4"/>
                    <circle r="21" stroke-width="10" stroke="#e3b95c" stroke-dasharray="none"/>
                    <circle r="9" fill="#12579b" stroke="#fff" stroke-width="3" stroke-opacity=".6"/>
                </g></g>
            </g>
            <g fill="none" stroke="#3b82d6">
                <g transform="translate(430 300)"><g class="spin rev">
                    <circle r="26" stroke-width="12" stroke-dasharray="10 6.8"/>
                    <circle r="19" stroke-width="10" stroke="#1c5fb0"/>
                    <circle r="8" fill="#e3b95c" stroke="none"/>
                </g></g>
                <g transform="translate(255 383)"><g class="spin">
                    <circle r="22" stroke-width="9" stroke-dasharray="8 5.5" stroke="#1c4f93"/>
                    <circle r="15" stroke-width="8" stroke="#2a6bc0"/>
                    <circle r="6" fill="#c9a85c" stroke="none"/>
                </g></g>
                <g transform="translate(950 392)"><g class="spin rev">
                    <circle r="26" stroke-width="10" stroke-dasharray="9 6" stroke="#1c4f93"/>
                    <circle r="18" stroke-width="9" stroke="#2a6bc0"/>
                    <circle r="7" fill="#c9a85c" stroke="none"/>
                </g></g>
            </g>

            <!-- ORANG DUDUK (kiri) -->
            <g>
                <!-- kursi -->
                <rect x="100" y="228" width="22" height="92" rx="11" fill="#c9a85c"/>
                <rect x="100" y="300" width="104" height="18" rx="9" fill="#b8944a"/>
                <rect x="147" y="318" width="9" height="24" fill="#0b2a55"/>
                <path d="M108 346 H196" stroke="#0b2a55" stroke-width="7" stroke-linecap="round"/>
                <circle cx="110" cy="356" r="7" fill="#0b2a55"/><circle cx="152" cy="358" r="7" fill="#0b2a55"/><circle cx="194" cy="356" r="7" fill="#0b2a55"/>
                <!-- kaki -->
                <path d="M172 306 H262 L270 336" stroke="#0b2a55" stroke-width="26" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <ellipse cx="282" cy="340" rx="22" ry="8" fill="#0b2a55"/>
                <!-- badan -->
                <rect x="126" y="240" width="62" height="74" rx="18" fill="#fff"/>
                <path d="M140 244 H176 L176 312 H140 Z" fill="#1c5fb0"/>
                <!-- kepala -->
                <rect x="156" y="224" width="12" height="18" fill="#f1c5a3"/>
                <circle cx="163" cy="208" r="21" fill="#f1c5a3"/>
                <path d="M141 206 C140 182 176 178 186 200 C176 194 160 196 150 208 Z" fill="#0b2a55"/>
                <!-- lengan + laptop -->
                <path d="M180 262 L226 292" stroke="#fff" stroke-width="13" stroke-linecap="round"/>
                <path d="M232 296 L266 262 L284 267 L254 302 Z" fill="#eaf1fa" stroke="#0b2a55" stroke-width="2"/>
                <rect x="230" y="296" width="58" height="8" rx="3" fill="#cfdff2" stroke="#0b2a55" stroke-width="2"/>
            </g>

            <!-- LAPTOP BESAR -->
            <g>
                <rect x="428" y="58" width="344" height="226" rx="14" fill="#0f3a73"/>
                <rect x="446" y="76" width="308" height="190" rx="4" fill="#cfe0f3"/>
                <!-- panel UI -->
                <rect x="458" y="88" width="76" height="82" rx="3" fill="#eef4fb" stroke="#9db9dc" stroke-width="2"/>
                <rect x="458" y="182" width="76" height="72" rx="3" fill="#eef4fb" stroke="#9db9dc" stroke-width="2"/>
                <rect x="548" y="88" width="196" height="30" rx="3" fill="#eef4fb" stroke="#9db9dc" stroke-width="2"/>
                <!-- progress bar -->
                <rect x="470" y="94" width="190" height="16" rx="3" fill="#1a5fb0"/>
                <g clip-path="url(#barclip)"><rect class="bar-move" x="470" y="94" width="240" height="16" fill="url(#stripe)"/></g>
                <rect x="668" y="94" width="68" height="16" rx="3" fill="#fff"/>
                <rect x="548" y="130" width="196" height="124" rx="3" fill="#a9c7ea"/>
                <path d="M548 254 L590 214 L632 240 L690 196 L744 254 Z" fill="#7fa6d4" opacity=".7"/>
                <path d="M452 82 H640 L560 262 H452 Z" fill="url(#glass)"/>
                <!-- pita bahaya -->
                <g class="tape">
                    <rect x="400" y="150" width="400" height="38" fill="url(#haz)" stroke="#0b2a55" stroke-width="3"/>
                    <rect x="400" y="150" width="400" height="5" fill="#fff" opacity=".18"/>
                </g>
                <!-- bawah laptop -->
                <path d="M392 284 H808 L828 336 Q830 346 818 346 H382 Q370 346 372 336 Z" fill="#1c4f93"/>
                <path d="M392 284 H808 L812 296 H388 Z" fill="#16427c"/>
                <g fill="#0b2a55" opacity=".85">
                    <rect x="410" y="302" width="380" height="5" rx="2"/>
                    <rect x="404" y="313" width="392" height="5" rx="2"/>
                    <rect x="398" y="324" width="404" height="5" rx="2"/>
                </g>
                <rect x="540" y="332" width="120" height="9" rx="4" fill="#0f3a73"/>
            </g>

            <!-- kerucut -->
            <g>
                <path d="M498 332 L528 232 Q540 218 552 232 L582 332 Z" fill="#0f3a73"/>
                <path d="M512 290 H568 L575 312 H505 Z" fill="#f08a24"/>
                <path d="M524 245 H556 L563 268 H517 Z" fill="#f08a24"/>
                <rect x="486" y="330" width="108" height="12" rx="3" fill="#0b2a55"/>
            </g>

            <!-- kerucut kecil kanan -->
            <g>
                <path d="M1052 330 L1068 290 Q1075 282 1082 290 L1098 330 Z" fill="#0f3a73"/>
                <path d="M1058 314 H1092 L1096 328 H1054 Z" fill="#f08a24"/>
                <rect x="1046" y="328" width="58" height="8" rx="2" fill="#0b2a55"/>
            </g>

            <!-- PEKERJA (kanan) -->
            <g>
                <rect x="886" y="282" width="12" height="62" rx="5" fill="#0b2a55"/>
                <rect x="904" y="282" width="12" height="62" rx="5" fill="#0b2a55"/>
                <ellipse cx="890" cy="346" rx="14" ry="5" fill="#0b2a55"/>
                <ellipse cx="914" cy="346" rx="14" ry="5" fill="#0b2a55"/>
                <rect x="878" y="222" width="46" height="68" rx="10" fill="#fff"/>
                <rect x="878" y="224" width="46" height="62" rx="10" fill="#14306a"/>
                <rect x="878" y="250" width="46" height="6" fill="#e8eef7"/>
                <rect x="878" y="262" width="46" height="6" fill="#e8eef7"/>
                <rect x="896" y="205" width="10" height="14" fill="#f1c5a3"/>
                <circle cx="901" cy="196" r="14" fill="#f1c5a3"/>
                <path d="M885 193 A16 16 0 0 1 917 193 Z" fill="#f2c14e"/>
                <rect x="882" y="191" width="38" height="5" rx="2" fill="#e0a92f"/>
                <!-- lengan memegang megafon -->
                <g class="hand">
                    <path d="M920 236 L948 214" stroke="#14306a" stroke-width="10" stroke-linecap="round"/>
                    <path d="M944 205 L972 194 L972 224 L944 216 Z" fill="#f2c14e"/>
                    <rect x="938" y="208" width="10" height="12" rx="2" fill="#e0a92f"/>
                </g>
                <!-- papan catatan -->
                <path d="M880 250 L866 266" stroke="#14306a" stroke-width="10" stroke-linecap="round"/>
                <rect x="846" y="256" width="30" height="40" rx="2" fill="#fff" stroke="#cdd9ea" stroke-width="2" transform="rotate(-8 861 276)"/>
            </g>

            <!-- mouse -->
            <ellipse cx="1005" cy="338" rx="0" ry="0" fill="none"/>
        </svg>
    </div>

</main>
</body>
</html>