<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>{{ config('app.name', 'Website') }} - Under Maintenance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DotGothic16&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#10509a; --navy:#0b2a5b; --gold:#c9a54f; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            background: var(--bg);
            overflow: hidden;
            font-family: 'Poppins', 'Segoe UI', Roboto, Arial, sans-serif;
        }
        .sr-only { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }
        .defs { position:absolute; width:0; height:0; overflow:hidden; }

        .stage { position:fixed; inset:0; display:flex; align-items:flex-end; justify-content:center; background:var(--bg); }
        .scene { display:none; width:100%; height:100%; overflow:visible; animation: appear .8s ease-out both; }
        .scene text { font-family:'Poppins','Segoe UI',Roboto,Arial,sans-serif; }

        /* Desktop = landscape, Mobile = portrait */
        .scene.desk { display:block; }
        @media (orientation: portrait), (max-width: 700px) {
            .scene.desk { display:none; }
            .scene.mob { display:block; }
        }

        /* Animations */
        .spin     { transform-box: fill-box; transform-origin: center; animation: spin 16s linear infinite; }
        .spin.rev { animation-direction: reverse; animation-duration: 11s; }
        .spin.fast{ animation-duration: 8s; }
        .sway     { transform-box: fill-box; transform-origin: 50% 0; animation: sway 4.5s ease-in-out infinite alternate; }
        .twinkle  { animation: twinkle 2.8s ease-in-out infinite; }
        .t2 { animation-delay:.7s } .t3 { animation-delay:1.4s } .t4 { animation-delay:2.1s }
        .blink    { animation: blink 1s steps(2, start) infinite; }
        .pulse    { transform-box: fill-box; transform-origin: left center; animation: pulse 1.8s ease-out infinite; opacity:0; }
        .p2 { animation-delay:.35s } .p3 { animation-delay:.7s }
        .bob      { animation: bob 3.6s ease-in-out infinite alternate; }
        .glint    { animation: glint 5s ease-in-out infinite; }

        @keyframes spin    { to { transform: rotate(360deg); } }
        @keyframes sway    { from { transform: rotate(-2.5deg); } to { transform: rotate(2.5deg); } }
        @keyframes twinkle { 0%,100% { opacity:.35; } 50% { opacity:1; } }
        @keyframes blink   { to { opacity:0; } }
        @keyframes pulse   { 0% { transform: scale(.5); opacity:.9; } 100% { transform: scale(1.3); opacity:0; } }
        @keyframes bob     { from { transform: translateY(0); } to { transform: translateY(-5px); } }
        @keyframes glint   { 0%,60%,100% { opacity:.12; } 75% { opacity:.3; } }
        @keyframes appear  { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform:none; } }

        @media (prefers-reduced-motion: reduce) {
            .scene, .spin, .sway, .twinkle, .blink, .pulse, .bob, .glint { animation: none !important; }
            .pulse { opacity:.5; }
        }
    </style>
</head>
<body>
    <main class="stage" role="main">
        <h1 class="sr-only">We are Under Maintenance!</h1>
        <p class="sr-only">We'll Be Back Soon!</p>

        {{-- ========== SHARED DEFINITIONS ========== --}}
        <svg class="defs" aria-hidden="true" focusable="false">
            <defs>
                <pattern id="haz" width="44" height="40" patternUnits="userSpaceOnUse">
                    <rect width="44" height="40" fill="#0b2a5b"/>
                    <polygon points="0,40 22,0 44,0 22,40" fill="#c9a54f"/>
                </pattern>
                <pattern id="ledtxt" x="0" y="34" width="730" height="36" patternUnits="userSpaceOnUse">
                    <text x="6" y="27" font-family="DotGothic16, 'Courier New', monospace" font-size="24" fill="#fff" textLength="640" lengthAdjust="spacingAndGlyphs">UNIVERSITAS PAMULANG <tspan fill="#e3c26a">&#215;</tspan> SMP PGRI PARUNG PANJANG</text>
                    <text x="690" y="27" font-family="DotGothic16, 'Courier New', monospace" font-size="24" fill="#e3c26a">&#215;</text>
                    <animateTransform attributeName="patternTransform" type="translate" from="0 0" to="-730 0" dur="16s" repeatCount="indefinite"/>
                </pattern>
                <pattern id="ledgrid" width="4" height="4" patternUnits="userSpaceOnUse">
                    <path fill-rule="evenodd" d="M0 0H4V4H0Z M2 .5A1.5 1.5 0 1 0 2 3.5A1.5 1.5 0 1 0 2 .5Z" fill="#061a3a" opacity=".8"/>
                </pattern>
                <linearGradient id="ledfade" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0" stop-color="#061a3a"/>
                    <stop offset=".1" stop-color="#061a3a" stop-opacity="0"/>
                    <stop offset=".9" stop-color="#061a3a" stop-opacity="0"/>
                    <stop offset="1" stop-color="#061a3a"/>
                </linearGradient>
                <pattern id="keys" width="22" height="11" patternUnits="userSpaceOnUse">
                    <rect x="1.5" y="1.5" width="19" height="8" rx="1.5" fill="#2b5c9e"/>
                </pattern>
                <pattern id="lat" width="34" height="34" patternUnits="userSpaceOnUse">
                    <path d="M0 0 L34 34 M34 0 L0 34 M0 0 H34 M0 34 H34" stroke="#9bbbe0" stroke-width="2" fill="none"/>
                </pattern>
                <linearGradient id="shine" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#fff" stop-opacity=".35"/>
                    <stop offset="1" stop-color="#fff" stop-opacity="0"/>
                </linearGradient>

                <symbol id="gear" viewBox="0 0 100 100">
                    <g fill="#3b78c0">
                        <rect x="41" y="1" width="18" height="24" rx="4"/>
                        <rect x="41" y="1" width="18" height="24" rx="4" transform="rotate(45 50 50)"/>
                        <rect x="41" y="1" width="18" height="24" rx="4" transform="rotate(90 50 50)"/>
                        <rect x="41" y="1" width="18" height="24" rx="4" transform="rotate(135 50 50)"/>
                        <rect x="41" y="1" width="18" height="24" rx="4" transform="rotate(180 50 50)"/>
                        <rect x="41" y="1" width="18" height="24" rx="4" transform="rotate(225 50 50)"/>
                        <rect x="41" y="1" width="18" height="24" rx="4" transform="rotate(270 50 50)"/>
                        <rect x="41" y="1" width="18" height="24" rx="4" transform="rotate(315 50 50)"/>
                        <circle cx="50" cy="50" r="36"/>
                    </g>
                    <circle cx="50" cy="50" r="17" fill="none" stroke="#c9a54f" stroke-width="9"/>
                    <circle cx="50" cy="50" r="10" fill="#10509a" opacity=".55"/>
                </symbol>

                <symbol id="plus" viewBox="0 0 20 20">
                    <path d="M10 2V18M2 10H18" stroke="#fff" stroke-width="3.5" stroke-linecap="round"/>
                </symbol>

                <symbol id="cone" viewBox="0 0 70 120">
                    <polygon points="28,4 42,4 62,100 8,100" fill="#2d62a8"/>
                    <polygon points="23,28 47,28 50.75,46 19.25,46" fill="#e8832a"/>
                    <polygon points="16,62 54,62 58.1,82 11.9,82" fill="#e8832a"/>
                    <rect x="0" y="98" width="70" height="16" rx="4" fill="#123e7a"/>
                </symbol>

                <symbol id="laptop" viewBox="0 0 520 340">
                    <rect x="40" y="0" width="440" height="252" rx="12" fill="#123e7a"/>
                    <rect x="48" y="8" width="424" height="236" rx="8" fill="#1a4f94"/>
                    <rect x="58" y="22" width="104" height="204" rx="3" fill="#dde8f5"/>
                    <rect x="68" y="34" width="84" height="26" fill="#f4f8fd"/>
                    <rect x="68" y="72" width="84" height="26" fill="#f4f8fd"/>
                    <rect x="176" y="22" width="286" height="204" rx="3" fill="#8fb3de"/>
                    <rect x="186" y="34" width="268" height="36" rx="5" fill="#061a3a" stroke="#4f86c6" stroke-width="2"/>
                    <rect x="190" y="36" width="260" height="32" fill="url(#ledtxt)"/>
                    <rect x="190" y="36" width="260" height="32" fill="url(#ledgrid)"/>
                    <rect x="190" y="36" width="260" height="32" fill="url(#ledfade)"/>
                    <polygon points="190,150 250,150 270,226 190,226" fill="#6c97cb" opacity=".6"/>
                    <polygon points="300,170 360,140 420,200 440,226 300,226" fill="#5b88bf" opacity=".55"/>
                    <polygon points="300,0 372,0 250,252 178,252" fill="url(#shine)" class="glint"/>
                    <rect x="0" y="252" width="520" height="12" rx="3" fill="#0d3166"/>
                    <polygon points="20,264 500,264 520,312 0,312" fill="#123e7a"/>
                    <polygon points="44,270 476,270 488,298 32,298" fill="url(#keys)"/>
                    <rect x="195" y="302" width="130" height="8" rx="4" fill="#0d3166"/>
                    <rect x="60" y="316" width="400" height="14" rx="7" fill="#0d3166"/>
                </symbol>

                <symbol id="tape" viewBox="0 0 520 50">
                    <rect x="0" y="6" width="520" height="40" fill="#000" opacity=".22"/>
                    <rect x="0" y="0" width="520" height="40" fill="url(#haz)"/>
                    <rect x="0" y="0" width="520" height="3" fill="#fff" opacity=".18"/>
                </symbol>

                <symbol id="person" viewBox="0 0 300 340">
                    <!-- chair -->
                    <rect x="6" y="64" width="44" height="150" rx="16" fill="#c9a24a" transform="rotate(-6 28 140)"/>
                    <rect x="20" y="204" width="124" height="28" rx="13" fill="#d8b45c"/>
                    <rect x="74" y="230" width="10" height="62" fill="#123e7a"/>
                    <rect x="26" y="288" width="108" height="9" rx="4" fill="#123e7a"/>
                    <circle cx="30" cy="312" r="10" fill="#0d3166"/><circle cx="80" cy="314" r="10" fill="#0d3166"/><circle cx="130" cy="312" r="10" fill="#0d3166"/>
                    <rect x="38" y="150" width="14" height="60" rx="6" fill="#b8913c" transform="rotate(-8 45 180)"/>
                    <!-- legs -->
                    <polygon points="88,176 216,192 222,226 90,228" fill="#14376e"/>
                    <polygon points="198,192 232,198 262,288 232,298" fill="#14376e"/>
                    <ellipse cx="258" cy="302" rx="36" ry="11" fill="#0d2a55"/>
                    <!-- torso -->
                    <polygon points="64,98 122,98 136,188 66,204" fill="#fff"/>
                    <polygon points="60,100 102,100 112,192 64,200" fill="#2d6ab5"/>
                    <rect x="88" y="76" width="22" height="26" fill="#e7b392"/>
                    <!-- head -->
                    <circle cx="100" cy="60" r="27" fill="#f0bf9c"/>
                    <path d="M72 60 C70 22 120 18 128 48 C112 36 92 40 72 60Z" fill="#14264f"/>
                    <!-- arm + laptop -->
                    <polygon points="96,108 124,112 128,160 196,172 194,192 108,184 90,150" fill="#fff"/>
                    <circle cx="198" cy="182" r="9" fill="#f0bf9c"/>
                    <polygon points="206,128 272,124 266,186 200,190" fill="#e9eef5"/>
                    <circle cx="236" cy="158" r="7" fill="#c3cddb"/>
                    <polygon points="150,194 272,190 284,202 146,206" fill="#cfd8e4"/>
                </symbol>

                <symbol id="worker" viewBox="0 0 110 200">
                    <rect x="40" y="132" width="14" height="58" fill="#14376e"/>
                    <rect x="58" y="132" width="14" height="58" fill="#14376e"/>
                    <ellipse cx="46" cy="193" rx="12" ry="5" fill="#0d2a55"/><ellipse cx="66" cy="193" rx="12" ry="5" fill="#0d2a55"/>
                    <rect x="34" y="70" width="44" height="70" rx="7" fill="#14376e"/>
                    <rect x="34" y="98" width="44" height="6" fill="#e8eef7"/>
                    <rect x="34" y="112" width="44" height="6" fill="#e8eef7"/>
                    <line x1="40" y1="80" x2="24" y2="48" stroke="#e8eef7" stroke-width="11" stroke-linecap="round"/>
                    <polygon points="2,28 26,38 22,54 0,46" fill="#c9a54f"/>
                    <rect x="22" y="38" width="8" height="12" fill="#14376e" transform="rotate(20 26 44)"/>
                    <line x1="74" y1="80" x2="88" y2="116" stroke="#e8eef7" stroke-width="11" stroke-linecap="round"/>
                    <polygon points="80,112 106,106 110,132 84,138" fill="#fff"/>
                    <rect x="50" y="62" width="12" height="12" fill="#e7b392"/>
                    <circle cx="56" cy="52" r="14" fill="#f0bf9c"/>
                    <path d="M39 50 A17 17 0 0 1 73 50Z" fill="#d9ae45"/>
                    <rect x="35" y="48" width="42" height="6" rx="3" fill="#c9a54f"/>
                </symbol>

                <symbol id="crane" viewBox="0 0 260 340">
                    <polygon points="167,0 150,28 184,28" fill="#8fb0d8"/>
                    <line x1="167" y1="2" x2="4" y2="30" stroke="#8fb0d8" stroke-width="2.5"/>
                    <line x1="167" y1="2" x2="252" y2="30" stroke="#8fb0d8" stroke-width="2.5"/>
                    <rect x="0" y="28" width="256" height="22" fill="url(#lat)" stroke="#8fb0d8" stroke-width="3"/>
                    <rect x="224" y="46" width="30" height="28" fill="#a9c3e2"/>
                    <rect x="150" y="50" width="34" height="272" fill="url(#lat)" stroke="#8fb0d8" stroke-width="3"/>
                    <rect x="120" y="56" width="30" height="24" rx="3" fill="#c3d6ee"/>
                    <rect x="112" y="320" width="110" height="14" fill="#8fb0d8"/>
                </symbol>
                <symbol id="load" viewBox="0 0 140 130">
                    <line x1="70" y1="0" x2="12" y2="86" stroke="#8fb0d8" stroke-width="2.5"/>
                    <line x1="70" y1="0" x2="128" y2="86" stroke="#8fb0d8" stroke-width="2.5"/>
                    <rect x="2" y="86" width="136" height="9" fill="#6ea0d6"/>
                    <rect x="26" y="95" width="88" height="7" fill="#8fb0d8"/>
                </symbol>

                <symbol id="code" viewBox="0 0 200 160">
                    <rect width="200" height="160" rx="7" fill="#1b4b8e"/>
                    <rect x="0" y="0" width="200" height="14" rx="7" fill="#e9eef5"/>
                    <circle cx="9" cy="7" r="2.5" fill="#e8483a"/><circle cx="17" cy="7" r="2.5" fill="#e8b93a"/><circle cx="25" cy="7" r="2.5" fill="#4fbf6a"/>
                    <rect x="6" y="20" width="90" height="134" fill="#143a73"/>
                    <rect x="102" y="20" width="92" height="134" fill="#143a73"/>
                    <g fill="#8fb3de">
                        <rect x="12" y="28" width="40" height="3"/><rect x="18" y="38" width="62" height="3" fill="#e8c35a"/><rect x="18" y="48" width="48" height="3"/>
                        <rect x="12" y="58" width="70" height="3" fill="#6fd0c4"/><rect x="18" y="68" width="34" height="3"/><rect x="18" y="78" width="58" height="3" fill="#e8c35a"/>
                        <rect x="12" y="88" width="44" height="3"/><rect x="18" y="98" width="66" height="3" fill="#6fd0c4"/><rect x="18" y="108" width="40" height="3"/>
                        <rect x="12" y="118" width="60" height="3" fill="#e8c35a"/><rect x="18" y="128" width="50" height="3"/><rect x="12" y="138" width="30" height="3"/>
                        <rect x="108" y="28" width="56" height="3" fill="#e8c35a"/><rect x="114" y="38" width="70" height="3"/><rect x="114" y="48" width="44" height="3" fill="#6fd0c4"/>
                        <rect x="108" y="58" width="64" height="3"/><rect x="114" y="68" width="76" height="3" fill="#e8c35a"/><rect x="114" y="78" width="38" height="3"/>
                        <rect x="108" y="88" width="72" height="3" fill="#6fd0c4"/><rect x="114" y="98" width="52" height="3"/><rect x="108" y="108" width="68" height="3" fill="#e8c35a"/>
                        <rect x="114" y="118" width="46" height="3"/><rect x="108" y="128" width="74" height="3" fill="#6fd0c4"/><rect x="108" y="138" width="36" height="3"/>
                    </g>
                </symbol>

                <symbol id="circ" viewBox="0 0 400 180">
                    <g fill="none" stroke="#5a8fd0" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M0 30H120L150 60H260L290 30H400"/>
                        <path d="M60 180V110L90 80H200"/>
                        <path d="M200 180V122H330L360 92H400"/>
                        <path d="M0 120H40L60 100"/>
                        <path d="M260 60V14H330"/>
                    </g>
                    <g fill="#dce8f6" stroke="#5a8fd0" stroke-width="2.5">
                        <circle cx="120" cy="30" r="5"/><circle cx="90" cy="80" r="5"/><circle cx="330" cy="122" r="5"/><circle cx="330" cy="14" r="5"/><circle cx="60" cy="100" r="5"/>
                    </g>
                </symbol>

                <symbol id="bgc" viewBox="0 0 300 200">
                    <g fill="none" stroke="#7fb0e8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M0 40H110L140 70H300"/><path d="M0 100H60L90 130H220"/><path d="M0 160H150L175 135H300"/><path d="M120 0V40"/>
                    </g>
                    <g fill="none" stroke="#7fb0e8" stroke-width="2"><circle cx="110" cy="40" r="5"/><circle cx="90" cy="130" r="5"/><circle cx="175" cy="135" r="5"/></g>
                </symbol>
            </defs>
        </svg>

        {{-- ========================= DESKTOP ========================= --}}
        <svg class="scene desk" viewBox="0 0 1376 768" preserveAspectRatio="xMidYMax meet" role="img" aria-label="We are Under Maintenance! We'll Be Back Soon!">
            <path d="M-300 0H300C330 90 240 150 160 190C90 230 30 280 -300 270Z" fill="#2a6fb8" opacity=".35"/>
            <use href="#bgc" x="-40" y="320" width="340" height="226" opacity=".22"/>
            <use href="#bgc" x="1120" y="110" width="300" height="200" opacity=".22" transform="translate(2540 0) scale(-1 1)"/>
            <path d="M390 700C380 560 440 380 620 330C820 280 1000 330 1090 480C1130 560 1110 650 1090 700Z" fill="#2a68ad" opacity=".6"/>

            <text x="688" y="120" text-anchor="middle" font-size="50" font-weight="700" fill="#0b2a5b">We are</text>
            <text x="688" y="208" text-anchor="middle" font-size="68" font-weight="800" fill="#fff" textLength="752" lengthAdjust="spacingAndGlyphs">Under <tspan fill="#c9a54f">Maintenance!</tspan></text>
            <text x="688" y="276" text-anchor="middle" font-size="38" font-weight="500" fill="#fff">We'll Be Back Soon!</text>

            {{-- SLOT LOGO DESKTOP: kiri = public/images/logo.webp, kanan = public/images/logo-unpam.webp --}}
            <image class="logo" href="{{ asset('images/logo.webp') }}" x="121" y="112" width="150" height="134" preserveAspectRatio="xMidYMid meet" onerror="this.style.display='none'"/>
            <image class="logo" href="{{ asset('images/logo-unpam.webp') }}" x="1080" y="84" width="200" height="190" preserveAspectRatio="xMidYMid meet" onerror="this.style.display='none'"/>

            <rect x="-2000" y="586" width="5400" height="400" fill="#dce8f6"/>
            <use href="#circ" x="0" y="600" width="430" height="168" opacity=".9"/>
            <use href="#circ" x="500" y="680" width="520" height="88" opacity=".9"/>
            <use href="#circ" x="950" y="610" width="430" height="158" opacity=".9"/>

            <rect x="-20" y="592" width="132" height="52" rx="6" fill="#123e7a"/><rect x="-14" y="598" width="120" height="40" rx="3" fill="#1a4f94"/>
            <rect x="-10" y="688" width="172" height="64" rx="5" fill="#123e7a"/><rect x="52" y="702" width="50" height="22" rx="2" fill="#c9a54f"/>
            <rect x="1288" y="346" width="120" height="212" rx="6" fill="#123e7a"/><rect x="1294" y="352" width="110" height="196" rx="3" fill="#1a4f94"/>
            <rect x="1256" y="664" width="150" height="112" rx="8" fill="#123e7a"/><rect x="1266" y="674" width="130" height="92" rx="4" fill="#1a4f94"/>
            <g transform="rotate(-18 1182 712)"><ellipse cx="1182" cy="712" rx="38" ry="26" fill="#1d4f93"/><path d="M1182 686V712" stroke="#123e7a" stroke-width="3"/></g>

            <use href="#plus" x="425" y="322" width="22" height="22" class="twinkle"/>
            <use href="#plus" x="232" y="332" width="24" height="24" class="twinkle t2"/>
            <use href="#plus" x="108" y="528" width="24" height="24" class="twinkle t3"/>
            <use href="#plus" x="100" y="645" width="24" height="24" class="twinkle t4"/>
            <use href="#plus" x="963" y="531" width="24" height="24" class="twinkle t2"/>
            <use href="#plus" x="1082" y="365" width="24" height="24" class="twinkle t3"/>
            <use href="#plus" x="968" y="733" width="24" height="24" class="twinkle"/>

            <g class="spin"><use href="#gear" x="913" y="303" width="84" height="84"/></g>
            <g class="spin rev"><use href="#gear" x="385" y="548" width="90" height="90"/></g>

            <use href="#crane" x="1015" y="272" width="255" height="335"/>
            <g class="sway"><use href="#load" x="1021" y="316" width="140" height="130"/></g>

            <use href="#code" x="280" y="362" width="180" height="144"/>
            <rect class="blink" x="338" y="440" width="9" height="3" fill="#fff"/>
            <g class="bob"><use href="#person" x="140" y="375" width="270" height="306"/></g>

            <use href="#laptop" x="437" y="343" width="562" height="367"/>
            <use href="#tape" x="437" y="476" width="562" height="54"/>
            <use href="#cone" x="520" y="538" width="98" height="140"/>

            <use href="#worker" x="1000" y="482" width="104" height="192"/>
            <g fill="none" stroke="#c9a54f" stroke-width="3" stroke-linecap="round">
                <path class="pulse" d="M996 500q-10 8 0 16"/><path class="pulse p2" d="M988 494q-16 14 0 28"/><path class="pulse p3" d="M980 488q-22 20 0 40"/>
            </g>
            <use href="#cone" x="1225" y="568" width="62" height="108"/>

            <g class="spin fast"><use href="#gear" x="388" y="690" width="1" height="1" opacity="0"/></g>
            <g class="spin"><use href="#gear" x="224" y="700" width="84" height="84"/></g>
            <g class="spin rev"><use href="#gear" x="1046" y="698" width="84" height="84"/></g>
        </svg>

        {{-- ========================= MOBILE ========================= --}}
        <svg class="scene mob" viewBox="0 0 768 1376" preserveAspectRatio="xMidYMax meet" role="img" aria-label="We are Under Maintenance! We'll Be Back Soon!">
            <path d="M-300 0H270C290 90 230 150 150 190C80 230 30 250 -300 245Z" fill="#2a6fb8" opacity=".35"/>
            <use href="#bgc" x="470" y="110" width="300" height="200" opacity=".22"/>
            <use href="#bgc" x="-60" y="410" width="260" height="173" opacity=".2"/>
            <path d="M70 700C60 560 150 440 330 430C520 424 700 480 740 620L740 720L70 740Z" fill="#2a68ad" opacity=".6"/>

            <text x="384" y="156" text-anchor="middle" font-size="50" font-weight="700" fill="#0b2a5b">We are</text>
            <text x="384" y="232" text-anchor="middle" font-size="62" font-weight="800" fill="#fff" textLength="664" lengthAdjust="spacingAndGlyphs">Under <tspan fill="#c9a54f">Maintenance!</tspan></text>
            <text x="384" y="296" text-anchor="middle" font-size="38" font-weight="500" fill="#fff">We'll Be Back Soon!</text>

            {{-- SLOT LOGO MOBILE: kiri = public/images/logo.webp, kanan = public/images/logo-unpam.webp --}}
            <image class="logo" href="{{ asset('images/logo.webp') }}" x="52" y="14" width="240" height="100" preserveAspectRatio="xMinYMid meet" onerror="this.style.display='none'"/>
            <image class="logo" href="{{ asset('images/logo-unpam.webp') }}" x="476" y="4" width="240" height="120" preserveAspectRatio="xMaxYMid meet" onerror="this.style.display='none'"/>

            <rect x="-2000" y="1196" width="5000" height="400" fill="#dce8f6"/>
            <use href="#circ" x="200" y="1210" width="400" height="168" opacity=".9"/>
            <use href="#circ" x="480" y="1230" width="320" height="140" opacity=".9"/>
            <use href="#circ" x="-20" y="1240" width="260" height="136" opacity=".9"/>

            <rect x="-30" y="1214" width="110" height="46" rx="6" fill="#123e7a"/>
            <rect x="-20" y="1304" width="140" height="56" rx="5" fill="#123e7a"/><rect x="40" y="1316" width="46" height="20" rx="2" fill="#c9a54f"/>
            <rect x="722" y="1276" width="90" height="70" rx="8" fill="#123e7a"/>
            <g transform="rotate(20 686 1322)"><ellipse cx="686" cy="1322" rx="32" ry="24" fill="#1d4f93"/><path d="M686 1300V1322" stroke="#123e7a" stroke-width="3"/></g>

            <use href="#plus" x="140" y="396" width="26" height="26" class="twinkle"/>
            <use href="#plus" x="24" y="640" width="26" height="26" class="twinkle t2"/>
            <use href="#plus" x="664" y="652" width="26" height="26" class="twinkle t3"/>
            <use href="#plus" x="352" y="884" width="26" height="26" class="twinkle t4"/>
            <use href="#plus" x="138" y="896" width="26" height="26" class="twinkle t2"/>
            <use href="#plus" x="568" y="982" width="24" height="24" class="twinkle"/>
            <use href="#plus" x="452" y="1142" width="24" height="24" class="twinkle t3"/>
            <use href="#plus" x="12" y="1144" width="24" height="24" class="twinkle t4"/>

            <g class="spin"><use href="#gear" x="610" y="418" width="86" height="86"/></g>
            <g class="spin rev"><use href="#gear" x="64" y="682" width="86" height="86"/></g>
            <g class="spin rev"><use href="#gear" x="428" y="872" width="72" height="72"/></g>

            <use href="#laptop" x="112" y="458" width="590" height="386"/>
            <use href="#tape" x="112" y="578" width="590" height="56"/>
            <use href="#cone" x="188" y="658" width="112" height="152"/>

            <use href="#code" x="190" y="928" width="200" height="160"/>
            <rect class="blink" x="254" y="1018" width="10" height="3" fill="#fff"/>
            <use href="#crane" x="480" y="895" width="275" height="360"/>
            <g class="sway"><use href="#load" x="487" y="941" width="148" height="138"/></g>

            <g class="bob"><use href="#person" x="40" y="965" width="285" height="323"/></g>
            <g class="spin"><use href="#gear" x="318" y="1148" width="92" height="92"/></g>

            <use href="#worker" x="495" y="1088" width="110" height="202"/>
            <g fill="none" stroke="#c9a54f" stroke-width="3" stroke-linecap="round">
                <path class="pulse" d="M490 1108q-10 8 0 16"/><path class="pulse p2" d="M481 1102q-16 14 0 28"/><path class="pulse p3" d="M472 1096q-22 20 0 40"/>
            </g>
            <use href="#cone" x="392" y="1186" width="88" height="102"/>
            <use href="#cone" x="700" y="1188" width="68" height="78"/>

            <g class="spin rev"><use href="#gear" x="148" y="1306" width="88" height="88"/></g>
            <g class="spin"><use href="#gear" x="548" y="1318" width="84" height="84"/></g>
        </svg>
    </main>
</body>
</html>
