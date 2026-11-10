<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>{{ config('app.name', 'Website') }} - Error 500</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg1:#0b0614; --bg2:#4a1608; --btn:#ff9a3c; --btn-ink:#1a0b0b; }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; }
        body {
            background: radial-gradient(ellipse at 50% 35%, var(--bg2) 0%, var(--bg1) 78%);
            overflow: hidden;
            font-family: 'Poppins', 'Segoe UI', Roboto, Arial, sans-serif;
        }
        .sr-only { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0 0 0 0); white-space:nowrap; }
        .defs { position:absolute; width:0; height:0; overflow:hidden; }
        symbol { overflow: visible; }
        .stage { position:fixed; inset:0; display:flex; align-items:flex-end; justify-content:center; }
        .scene { display:none; width:100%; height:100%; overflow:visible; animation: appear .8s ease-out both; }
        .scene text { font-family:'Poppins','Segoe UI',Roboto,Arial,sans-serif; }
        .scene.desk { display:block; }
        @media (orientation: portrait), (max-width: 700px) {
            .scene.desk { display:none; }
            .scene.mob { display:block; }
        }

        .tw, .tw2, .tw3, .tw4 { animation: twinkle 2.8s ease-in-out infinite; }
        .tw2 { animation-delay:.7s } .tw3 { animation-delay:1.4s } .tw4 { animation-delay:2.1s }
        .float  { animation: float 5s ease-in-out infinite alternate; }
        .drift  { transform-box: fill-box; transform-origin: center; animation: drift 7s ease-in-out infinite alternate; }
        .meteor { opacity:0; animation: meteor 5s linear infinite; }
        .shake  { transform-box: fill-box; transform-origin: center; animation: shake 3.2s infinite; }
        .glowp  { transform-box: fill-box; transform-origin: center; animation: glowp 1.6s ease-in-out infinite alternate; }
        .scan   { animation: scan 3.5s ease-in-out infinite alternate; }
        .rot    { transform-origin: 0 0; animation: rot 3.6s linear infinite; }
        .rot.rev{ animation-direction: reverse; }
        .flame  { transform-box: fill-box; transform-origin: 50% 100%; animation: flame .45s ease-in-out infinite alternate; }
        .f2 { animation-delay:.12s } .f3 { animation-delay:.25s } .f4 { animation-delay:.07s }
        .smoke  { transform-box: fill-box; transform-origin: center; opacity:0; animation: smoke 5s ease-out infinite; }
        .spark  { opacity:0; animation: spark 1.5s ease-out infinite; }
        .bolt   { opacity:0; animation: bolt 3.4s infinite; }
        .flash  { opacity:0; animation: flash 6s infinite; }
        .warn   { animation: warn .9s steps(2,start) infinite; }
        .gl1    { opacity:0; animation: glitchA var(--gdur,4s) infinite; }
        .gl2    { opacity:0; animation: glitchB var(--gdur,4s) infinite; }
        .codeT  { transform-box: fill-box; transform-origin: center; animation: var(--canim, none); }

        @keyframes twinkle { 0%,100% { opacity:.3; } 50% { opacity:1; } }
        @keyframes float   { from { transform: translateY(0); } to { transform: translateY(-14px); } }
        @keyframes drift   { from { transform: rotate(-7deg); } to { transform: rotate(7deg); } }
        @keyframes meteor  { 0% { transform: translate(0,0); opacity:0; } 6% { opacity:1; } 30% { transform: translate(-440px,220px); opacity:0; } 100% { transform: translate(-440px,220px); opacity:0; } }
        @keyframes shake   { 0%,68%,100% { transform: translate(0,0) rotate(0); } 70% { transform: translate(-5px,0) rotate(-1.6deg); } 72% { transform: translate(5px,0) rotate(1.6deg); } 74% { transform: translate(-4px,0) rotate(-1deg); } 76% { transform: translate(4px,0) rotate(1deg); } 78% { transform: translate(0,0) rotate(0); } }
        @keyframes glowp   { from { opacity:.5; transform: scale(.94); } to { opacity:1; transform: scale(1.08); } }
        @keyframes scan    { from { transform: translateY(0); } to { transform: translateY(250px); } }
        @keyframes rot     { to { transform: rotate(360deg); } }
        @keyframes flame   { from { transform: scale(1,.88) skewX(-4deg); } to { transform: scale(.9,1.14) skewX(4deg); } }
        @keyframes smoke   { 0% { transform: translate(0,0) scale(.5); opacity:0; } 15% { opacity:.55; } 100% { transform: translate(var(--sx,20px),-230px) scale(2.2); opacity:0; } }
        @keyframes spark   { 0% { transform: translate(0,0); opacity:1; } 100% { transform: translate(var(--dx,40px),var(--dy,-90px)); opacity:0; } }
        @keyframes bolt    { 0%,58%,100% { opacity:0; } 60% { opacity:1; } 62% { opacity:.1; } 64% { opacity:1; } 70% { opacity:0; } }
        @keyframes flash   { 0%,86%,100% { opacity:0; } 87% { opacity:.32; } 88% { opacity:0; } 90% { opacity:.5; } 93% { opacity:0; } }
        @keyframes warn    { to { opacity:.25; } }
        @keyframes glitchA { 0%,84%,100% { transform: translate(0,0); opacity:0; } 86% { transform: translate(-7px,2px); opacity:.75; } 88% { transform: translate(6px,-2px); opacity:.75; } 90% { transform: translate(-3px,1px); opacity:.5; } 92% { opacity:0; } }
        @keyframes glitchB { 0%,85%,100% { transform: translate(0,0); opacity:0; } 87% { transform: translate(7px,-2px); opacity:.75; } 89% { transform: translate(-6px,2px); opacity:.75; } 91% { transform: translate(3px,0); opacity:.5; } 93% { opacity:0; } }
        @keyframes pulseT  { 0%,100% { opacity:1; } 50% { opacity:.78; } }
        @keyframes jitter  { 0%,90%,100% { transform: translate(0,0); } 92% { transform: translate(-4px,1px); } 94% { transform: translate(4px,-1px); } 96% { transform: translate(-2px,0); } }
        @keyframes appear  { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform:none; } }

        @media (prefers-reduced-motion: reduce) {
            .scene, .tw, .tw2, .tw3, .tw4, .float, .drift, .meteor, .shake, .glowp, .scan, .rot, .flame, .smoke, .spark, .bolt, .flash, .warn, .gl1, .gl2, .codeT { animation: none !important; }
        }

        /* Drama tambahan */
        .stage { animation: quake 6s infinite; }
        .stage::after { content:""; position:absolute; inset:0; pointer-events:none; background: radial-gradient(ellipse at 50% 55%, transparent 45%, rgba(255,40,10,.38) 100%); animation: alarm 1.1s ease-in-out infinite alternate; }
        .boom { transform-box: fill-box; transform-origin: center; opacity:0; animation: boom 6s ease-out infinite; }
        @keyframes quake { 0%,85%,100% { transform:none; } 86% { transform:translate(-7px,4px); } 88% { transform:translate(8px,-4px); } 90% { transform:translate(-5px,3px); } 92% { transform:translate(3px,-2px); } 94% { transform:none; } }
        @keyframes alarm { from { opacity:.25; } to { opacity:1; } }
        @keyframes boom { 0%,86% { transform:scale(.2); opacity:0; } 88% { opacity:.95; } 100% { transform:scale(3.6); opacity:0; } }
        @media (prefers-reduced-motion: reduce) { .stage, .stage::after, .boom { animation:none !important; } .stage::after { opacity:.5; } }

        /* Tombol di dalam scene */
        .btn { cursor: pointer; outline: none; }
        .btn text { font-weight: 700; pointer-events: none; }
        .btn .btn-bg { transition: filter .2s ease; }
        .btn:hover .btn-bg, .btn:focus-visible .btn-bg { filter: brightness(1.15); }
        .btn-primary .btn-bg { fill: var(--btn); }
        .btn-primary text { fill: var(--btn-ink); }
        .btn-ghost .btn-bg { fill: rgba(255,255,255,.12); stroke: #fff; stroke-width: 2; }
        .btn-ghost text { fill: #fff; }
    </style>
</head>
<body>
    @php
        // Tujuan tombol: aman dipakai di halaman error (session/route bisa saja belum siap).
        $_home = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/');
        $_isAuth = false;
        try {
            $_u = auth()->user();
            if ($_u) {
                $_isAuth = true;
                $_rn = match ($_u->role ?? '') {
                    'admin' => 'admin.dashboard',
                    'guru'  => 'guru.dashboard',
                    'piket' => 'piket.dashboard',
                    default => null,
                };
                if ($_rn && \Illuminate\Support\Facades\Route::has($_rn)) { $_home = route($_rn); }
            }
        } catch (\Throwable $e) { $_isAuth = false; }
        $_logout = \Illuminate\Support\Facades\Route::has('logout') ? route('logout') : null;
    @endphp
    <main class="stage" role="main">
        <h1 class="sr-only">Error 500 - Internal Server Error</h1>
        <p class="sr-only">Server blew up! Our team is putting out the fire.</p>

        <svg class="defs" aria-hidden="true" focusable="false">
            <defs>
                <filter id="glow" x="-30%" y="-30%" width="160%" height="160%">
                    <feGaussianBlur stdDeviation="9" result="b"/>
                    <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
                <filter id="blur6" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="6"/></filter>

                <linearGradient id="flameG" x1="0" y1="1" x2="0" y2="0"><stop offset="0" stop-color="#ff3d00"/><stop offset=".6" stop-color="#ff8a00"/><stop offset="1" stop-color="#ffcf5a"/></linearGradient>
                <radialGradient id="fireGlow" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#ff6a1a" stop-opacity=".7"/><stop offset="1" stop-color="#ff6a1a" stop-opacity="0"/></radialGradient>
                <radialGradient id="smokeG" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#8b8296" stop-opacity=".9"/><stop offset="1" stop-color="#8b8296" stop-opacity="0"/></radialGradient>
                <symbol id="hero" viewBox="0 0 800 520" overflow="visible">
                    <circle cx="400" cy="250" r="290" fill="url(#fireGlow)" class="glowp"/>
                    <ellipse cx="400" cy="506" rx="290" ry="12" fill="#000" opacity=".4"/>
                    <polygon class="bolt" points="120,20 60,230 130,230 80,440 230,170 160,170 215,20" fill="#ffe27a" filter="url(#glow)"/>
                    <g class="shake">
                        <rect x="250" y="140" width="300" height="340" rx="16" fill="#1b1730" stroke="#3a3358" stroke-width="6"/>
                        <rect x="270" y="166" width="260" height="64" rx="8" fill="#251f40"/><circle cx="290" cy="198" r="6" fill="#4fbf6a" class=""/><circle cx="310" cy="198" r="6" fill="#ffcf5a" class="tw2"/><g stroke="#3a3358" stroke-width="4"><path d="M350 182H510M350 198H510M350 214H510"/></g>
                        <rect x="270" y="244" width="260" height="64" rx="8" fill="#251f40"/><circle cx="290" cy="276" r="6" fill="#ff2d2d" class="warn"/><circle cx="310" cy="276" r="6" fill="#ffcf5a" class="tw2"/><g stroke="#3a3358" stroke-width="4"><path d="M350 260H510M350 276H510M350 292H510"/></g>
                        <g transform="translate(34 322) rotate(5 130 32)"><rect x="270" y="0" width="260" height="64" rx="8" fill="#2c2347" stroke="#ff7a2f" stroke-width="3"/><rect x="286" y="14" width="130" height="36" rx="4" fill="#3a0f10"/><path d="M296 20 L330 44 L352 22 L384 46" stroke="#ff4d2d" stroke-width="3" fill="none"/><circle cx="440" cy="32" r="7" fill="#ff2d2d" class="warn"/></g>
                        <rect x="270" y="400" width="260" height="64" rx="8" fill="#251f40"/><circle cx="290" cy="432" r="6" fill="#ff2d2d" class="warn"/><circle cx="310" cy="432" r="6" fill="#ffcf5a" class="tw2"/><g stroke="#3a3358" stroke-width="4"><path d="M350 416H510M350 432H510M350 448H510"/></g>
                        <path d="M262 220 L300 270 L280 320 L322 370 L300 430" fill="none" stroke="#ff7a2f" stroke-width="3" opacity=".9"/>
                    </g>
                    <g transform="translate(300 140) scale(0.7)"><path class="flame " d="M0 0 C-34 -36 -14 -84 0 -138 C14 -96 46 -64 34 -22 C28 -6 12 2 0 2Z" fill="url(#flameG)"/><path class="flame " d="M0 0 C-14 -20 -6 -50 0 -78 C8 -54 22 -36 16 -14 C12 -4 5 0 0 0Z" fill="#ffe27a"/></g>
                    <g transform="translate(350 140) scale(0.9)"><path class="flame f2" d="M0 0 C-34 -36 -14 -84 0 -138 C14 -96 46 -64 34 -22 C28 -6 12 2 0 2Z" fill="url(#flameG)"/><path class="flame f2" d="M0 0 C-14 -20 -6 -50 0 -78 C8 -54 22 -36 16 -14 C12 -4 5 0 0 0Z" fill="#ffe27a"/></g>
                    <g transform="translate(405 140) scale(1.05)"><path class="flame f3" d="M0 0 C-34 -36 -14 -84 0 -138 C14 -96 46 -64 34 -22 C28 -6 12 2 0 2Z" fill="url(#flameG)"/><path class="flame f3" d="M0 0 C-14 -20 -6 -50 0 -78 C8 -54 22 -36 16 -14 C12 -4 5 0 0 0Z" fill="#ffe27a"/></g>
                    <g transform="translate(460 140) scale(0.85)"><path class="flame f4" d="M0 0 C-34 -36 -14 -84 0 -138 C14 -96 46 -64 34 -22 C28 -6 12 2 0 2Z" fill="url(#flameG)"/><path class="flame f4" d="M0 0 C-14 -20 -6 -50 0 -78 C8 -54 22 -36 16 -14 C12 -4 5 0 0 0Z" fill="#ffe27a"/></g>
                    <g transform="translate(510 140) scale(0.68)"><path class="flame f2" d="M0 0 C-34 -36 -14 -84 0 -138 C14 -96 46 -64 34 -22 C28 -6 12 2 0 2Z" fill="url(#flameG)"/><path class="flame f2" d="M0 0 C-14 -20 -6 -50 0 -78 C8 -54 22 -36 16 -14 C12 -4 5 0 0 0Z" fill="#ffe27a"/></g>
                    <circle class="smoke" cx="380" cy="90" r="46" fill="url(#smokeG)" style="--sx:-30px"/>
                    <circle class="smoke" cx="430" cy="80" r="56" fill="url(#smokeG)" style="--sx:40px;animation-delay:1.6s"/>
                    <circle class="smoke" cx="340" cy="100" r="40" fill="url(#smokeG)" style="--sx:-60px;animation-delay:3.1s"/>
                    <circle class="spark" cx="470" cy="270" r="4" fill="#ffd166" style="--dx:90px;--dy:-80px"/>
                    <circle class="spark" cx="480" cy="290" r="3" fill="#ff9a3c" style="--dx:120px;--dy:-30px;animation-delay:.5s"/>
                    <circle class="spark" cx="470" cy="260" r="3" fill="#ffd166" style="--dx:60px;--dy:-130px;animation-delay:.9s"/>
                    <circle class="spark" cx="300" cy="320" r="3" fill="#ff9a3c" style="--dx:-90px;--dy:-60px;animation-delay:.3s"/>
                    <g transform="translate(690 300)" class="warn">
                        <polygon points="0,-84 84,60 -84,60" fill="#ffcf5a" stroke="#140b1f" stroke-width="8" stroke-linejoin="round"/>
                        <rect x="-8" y="-38" width="16" height="56" rx="6" fill="#140b1f"/><circle cx="0" cy="38" r="9" fill="#140b1f"/>
                    </g>
                    <g transform="translate(140 502) scale(0.5)"><path class="flame f3" d="M0 0 C-34 -36 -14 -84 0 -138 C14 -96 46 -64 34 -22 C28 -6 12 2 0 2Z" fill="url(#flameG)"/><path class="flame f3" d="M0 0 C-14 -20 -6 -50 0 -78 C8 -54 22 -36 16 -14 C12 -4 5 0 0 0Z" fill="#ffe27a"/></g><g transform="translate(215 502) scale(0.65)"><path class="flame f2" d="M0 0 C-34 -36 -14 -84 0 -138 C14 -96 46 -64 34 -22 C28 -6 12 2 0 2Z" fill="url(#flameG)"/><path class="flame f2" d="M0 0 C-14 -20 -6 -50 0 -78 C8 -54 22 -36 16 -14 C12 -4 5 0 0 0Z" fill="#ffe27a"/></g><g transform="translate(600 502) scale(0.7)"><path class="flame f4" d="M0 0 C-34 -36 -14 -84 0 -138 C14 -96 46 -64 34 -22 C28 -6 12 2 0 2Z" fill="url(#flameG)"/><path class="flame f4" d="M0 0 C-14 -20 -6 -50 0 -78 C8 -54 22 -36 16 -14 C12 -4 5 0 0 0Z" fill="#ffe27a"/></g><g transform="translate(675 502) scale(0.55)"><path class="flame f3" d="M0 0 C-34 -36 -14 -84 0 -138 C14 -96 46 -64 34 -22 C28 -6 12 2 0 2Z" fill="url(#flameG)"/><path class="flame f3" d="M0 0 C-14 -20 -6 -50 0 -78 C8 -54 22 -36 16 -14 C12 -4 5 0 0 0Z" fill="#ffe27a"/></g><g transform="translate(745 502) scale(0.45)"><path class="flame f2" d="M0 0 C-34 -36 -14 -84 0 -138 C14 -96 46 -64 34 -22 C28 -6 12 2 0 2Z" fill="url(#flameG)"/><path class="flame f2" d="M0 0 C-14 -20 -6 -50 0 -78 C8 -54 22 -36 16 -14 C12 -4 5 0 0 0Z" fill="#ffe27a"/></g><g transform="translate(30 502) scale(0.4)"><path class="flame f4" d="M0 0 C-34 -36 -14 -84 0 -138 C14 -96 46 -64 34 -22 C28 -6 12 2 0 2Z" fill="url(#flameG)"/><path class="flame f4" d="M0 0 C-14 -20 -6 -50 0 -78 C8 -54 22 -36 16 -14 C12 -4 5 0 0 0Z" fill="#ffe27a"/></g><g transform="translate(14 150) rotate(-6)"><rect width="214" height="108" rx="8" fill="#0a0612" stroke="#ff4d2d" stroke-width="3"/><text x="14" y="30" font-family="'Courier New',monospace" font-size="16" fill="#ff5b3a" class="warn">FATAL: kernel panic</text><text x="14" y="56" font-family="'Courier New',monospace" font-size="15" fill="#ffb347">500 Internal Server Error</text><text x="14" y="80" font-family="'Courier New',monospace" font-size="15" fill="#8b8296">fire spreading...</text><rect class="warn" x="14" y="90" width="10" height="3" fill="#fff"/></g><circle class="boom" cx="400" cy="300" r="70" fill="none" stroke="#ffb347" stroke-width="12"/><circle class="boom" cx="400" cy="300" r="70" fill="none" stroke="#ff4d2d" stroke-width="6" style="animation-delay:.1s"/><polygon class="spark" points="0,0 14,3 9,15 -3,11" fill="#3a3358" style="--dx:-150px;--dy:-170px;animation-duration:2.4s;animation-delay:0s" transform="translate(330 300)"/><polygon class="spark" points="0,0 14,3 9,15 -3,11" fill="#3a3358" style="--dx:160px;--dy:-190px;animation-duration:2.6s;animation-delay:0.4s" transform="translate(470 290)"/><polygon class="spark" points="0,0 14,3 9,15 -3,11" fill="#251f40" style="--dx:-60px;--dy:-230px;animation-duration:2.2s;animation-delay:0.9s" transform="translate(400 330)"/><polygon class="spark" points="0,0 14,3 9,15 -3,11" fill="#ff7a2f" style="--dx:200px;--dy:-90px;animation-duration:2.8s;animation-delay:0.2s" transform="translate(520 330)"/><polygon class="spark" points="0,0 14,3 9,15 -3,11" fill="#ff7a2f" style="--dx:-190px;--dy:-60px;animation-duration:2.5s;animation-delay:1.2s" transform="translate(280 360)"/><polygon class="spark" points="0,0 14,3 9,15 -3,11" fill="#5a4f80" style="--dx:40px;--dy:-260px;animation-duration:3s;animation-delay:0.6s" transform="translate(430 270)"/><path d="M250 440 C180 470 150 430 110 480" fill="none" stroke="#3a3358" stroke-width="8"/>
                    <circle class="spark" cx="110" cy="480" r="4" fill="#ffe27a" style="--dx:-30px;--dy:-50px"/>
                </symbol>
            </defs>
        </svg>

        {{-- ========================= DESKTOP ========================= --}}
        <svg class="scene desk" viewBox="0 0 1376 768" preserveAspectRatio="xMidYMax meet" role="img" aria-label="Error 500 - Internal Server Error">
            <circle class="spark" cx="563" cy="760" r="2" fill="#ffd166" style="--dx:-10px;--dy:-337px;animation-duration:2.6s;animation-delay:0.98s"/>
            <circle class="spark" cx="1093" cy="760" r="2" fill="#ffb347" style="--dx:56px;--dy:-289px;animation-duration:3.2s;animation-delay:0.76s"/>
            <circle class="spark" cx="43" cy="760" r="2" fill="#ffd166" style="--dx:-49px;--dy:-383px;animation-duration:2.6s;animation-delay:0.83s"/>
            <circle class="spark" cx="357" cy="760" r="3" fill="#ffb347" style="--dx:-53px;--dy:-558px;animation-duration:4.2s;animation-delay:0.87s"/>
            <circle class="spark" cx="-5" cy="760" r="3" fill="#ffb347" style="--dx:49px;--dy:-545px;animation-duration:3.4s;animation-delay:0.2s"/>
            <circle class="spark" cx="1069" cy="760" r="2" fill="#ffb347" style="--dx:11px;--dy:-417px;animation-duration:3.4s;animation-delay:1.22s"/>
            <circle class="spark" cx="1208" cy="760" r="4" fill="#ffb347" style="--dx:-13px;--dy:-356px;animation-duration:3.4s;animation-delay:0.15s"/>
            <circle class="spark" cx="1167" cy="760" r="3" fill="#ff7a2f" style="--dx:3px;--dy:-365px;animation-duration:3.2s;animation-delay:1.02s"/>
            <circle class="spark" cx="828" cy="760" r="2" fill="#ffd166" style="--dx:-22px;--dy:-445px;animation-duration:3.8s;animation-delay:0.37s"/>
            <circle class="spark" cx="67" cy="760" r="3" fill="#ffd166" style="--dx:-22px;--dy:-554px;animation-duration:3.2s;animation-delay:0.79s"/>
            <circle class="spark" cx="1147" cy="760" r="2" fill="#ff7a2f" style="--dx:-45px;--dy:-297px;animation-duration:2.7s;animation-delay:0.77s"/>
            <circle class="spark" cx="901" cy="760" r="2" fill="#ffd166" style="--dx:-55px;--dy:-475px;animation-duration:3.4s;animation-delay:1.44s"/>
            <circle class="spark" cx="542" cy="760" r="3" fill="#ffd166" style="--dx:28px;--dy:-434px;animation-duration:3.8s;animation-delay:0.53s"/>
            <circle class="spark" cx="40" cy="760" r="4" fill="#ffd166" style="--dx:60px;--dy:-307px;animation-duration:2.5s;animation-delay:0.4s"/>
            <circle class="spark" cx="1397" cy="760" r="4" fill="#ff7a2f" style="--dx:22px;--dy:-418px;animation-duration:2.9s;animation-delay:0.87s"/>
            <circle class="spark" cx="690" cy="760" r="3" fill="#ffb347" style="--dx:-58px;--dy:-437px;animation-duration:3.5s;animation-delay:1.41s"/>
            <circle class="spark" cx="911" cy="760" r="2" fill="#ffd166" style="--dx:-33px;--dy:-290px;animation-duration:2.8s;animation-delay:1.15s"/>
            <circle class="spark" cx="700" cy="760" r="3" fill="#ffd166" style="--dx:-50px;--dy:-514px;animation-duration:2.9s;animation-delay:0.25s"/>
            <circle class="spark" cx="180" cy="760" r="4" fill="#ff7a2f" style="--dx:50px;--dy:-480px;animation-duration:4.2s;animation-delay:0.83s"/>
            <circle class="spark" cx="1298" cy="760" r="2" fill="#ffb347" style="--dx:-31px;--dy:-454px;animation-duration:2.8s;animation-delay:0.23s"/>
            <circle class="spark" cx="377" cy="760" r="2" fill="#ff7a2f" style="--dx:2px;--dy:-266px;animation-duration:2.9s;animation-delay:1.25s"/>
            <circle class="spark" cx="198" cy="760" r="4" fill="#ff7a2f" style="--dx:8px;--dy:-474px;animation-duration:4.1s;animation-delay:0.55s"/>
            <circle class="spark" cx="1314" cy="760" r="4" fill="#ffb347" style="--dx:19px;--dy:-523px;animation-duration:3.2s;animation-delay:0.98s"/>
            <circle class="spark" cx="1497" cy="760" r="3" fill="#ffb347" style="--dx:-10px;--dy:-546px;animation-duration:3.3s;animation-delay:0.6s"/>
            <circle class="spark" cx="720" cy="760" r="2" fill="#ff7a2f" style="--dx:-36px;--dy:-291px;animation-duration:2.7s;animation-delay:0.1s"/>
            <circle class="spark" cx="596" cy="760" r="2" fill="#ffd166" style="--dx:-47px;--dy:-286px;animation-duration:2.6s;animation-delay:0.0s"/>
            <rect x="-2000" y="744" width="5400" height="60" fill="#1a0a06"/><ellipse cx="688" cy="748" rx="900" ry="16" fill="#ff6a1a" opacity=".3" class="glowp"/>
            <text x="688" y="92" text-anchor="middle" font-size="44" font-weight="700" fill="#ffb380">Oh no!</text>
            <g style="--gdur:2.4s;--canim:jitter 2.4s infinite">
                <text x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#ff7a2f" opacity=".55" filter="url(#blur6)">Error 500</text>
                <text class="gl1" x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#00e5ff">Error 500</text>
                <text class="gl2" x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#ff2d6f">Error 500</text>
                <text class="codeT" x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#fff">Error <tspan fill="#ff7a2f">500</tspan></text>
            </g>
            <text x="688" y="234" text-anchor="middle" font-size="36" font-weight="600" fill="#fff">Internal Server Error</text>
            <text x="688" y="270" text-anchor="middle" font-size="22" font-weight="500" fill="#fff" opacity=".78">Server blew up! Our team is putting out the fire.</text>

            {{-- SLOT LOGO DESKTOP: kiri = public/images/logo-unpam.webp, kanan = public/images/logo.webp --}}
            <image class="logo" href="{{ asset('images/logo.webp') }}" x="1105" y="112" width="150" height="134" preserveAspectRatio="xMidYMid meet" onerror="this.style.display='none'"/>
            <image class="logo" href="{{ asset('images/logo-unpam.webp') }}" x="96" y="84" width="200" height="190" preserveAspectRatio="xMidYMid meet" onerror="this.style.display='none'"/>

            <use href="#hero" x="366" y="352" width="644" height="416"/>
            <rect class="flash" x="-2000" y="-1000" width="5400" height="2800" fill="#fff"/>

            {{-- TOMBOL --}}
            @if($_isAuth)
            <a class="btn btn-primary" href="{{ $_home }}" role="button" tabindex="0"><rect class="btn-bg" x="548" y="292" width="280" height="40" rx="20"/><text x="688" y="312" text-anchor="middle" dominant-baseline="central" font-size="19">Back to Dashboard</text></a>
            @endif
        </svg>

        {{-- ========================= MOBILE ========================= --}}
        <svg class="scene mob" viewBox="0 0 768 1376" preserveAspectRatio="xMidYMax meet" role="img" aria-label="Error 500 - Internal Server Error">
            <circle class="spark" cx="414" cy="1380" r="4" fill="#ffb347" style="--dx:-26px;--dy:-451px;animation-duration:3.0s;animation-delay:0.21s"/>
            <circle class="spark" cx="414" cy="1380" r="4" fill="#ffd166" style="--dx:-18px;--dy:-301px;animation-duration:2.5s;animation-delay:0.83s"/>
            <circle class="spark" cx="328" cy="1380" r="4" fill="#ff7a2f" style="--dx:30px;--dy:-346px;animation-duration:2.7s;animation-delay:1.42s"/>
            <circle class="spark" cx="183" cy="1380" r="4" fill="#ffb347" style="--dx:-46px;--dy:-286px;animation-duration:3.8s;animation-delay:0.2s"/>
            <circle class="spark" cx="332" cy="1380" r="4" fill="#ffb347" style="--dx:57px;--dy:-312px;animation-duration:3.7s;animation-delay:0.44s"/>
            <circle class="spark" cx="371" cy="1380" r="2" fill="#ff7a2f" style="--dx:38px;--dy:-304px;animation-duration:2.9s;animation-delay:0.4s"/>
            <circle class="spark" cx="768" cy="1380" r="2" fill="#ff7a2f" style="--dx:-35px;--dy:-282px;animation-duration:2.5s;animation-delay:1.06s"/>
            <circle class="spark" cx="327" cy="1380" r="3" fill="#ffd166" style="--dx:-43px;--dy:-511px;animation-duration:4.0s;animation-delay:0.04s"/>
            <circle class="spark" cx="54" cy="1380" r="2" fill="#ffb347" style="--dx:-45px;--dy:-261px;animation-duration:3.0s;animation-delay:1.15s"/>
            <circle class="spark" cx="25" cy="1380" r="4" fill="#ffb347" style="--dx:9px;--dy:-328px;animation-duration:3.4s;animation-delay:1.38s"/>
            <circle class="spark" cx="490" cy="1380" r="2" fill="#ffd166" style="--dx:-7px;--dy:-361px;animation-duration:3.8s;animation-delay:0.1s"/>
            <circle class="spark" cx="657" cy="1380" r="4" fill="#ffd166" style="--dx:-42px;--dy:-556px;animation-duration:4.1s;animation-delay:0.26s"/>
            <circle class="spark" cx="-5" cy="1380" r="2" fill="#ffd166" style="--dx:11px;--dy:-398px;animation-duration:2.9s;animation-delay:1.02s"/>
            <circle class="spark" cx="529" cy="1380" r="3" fill="#ffb347" style="--dx:-45px;--dy:-277px;animation-duration:2.7s;animation-delay:1.3s"/>
            <circle class="spark" cx="457" cy="1380" r="4" fill="#ffd166" style="--dx:34px;--dy:-282px;animation-duration:3.5s;animation-delay:0.55s"/>
            <circle class="spark" cx="733" cy="1380" r="4" fill="#ff7a2f" style="--dx:51px;--dy:-304px;animation-duration:3.2s;animation-delay:0.52s"/>
            <circle class="spark" cx="351" cy="1380" r="3" fill="#ffd166" style="--dx:13px;--dy:-361px;animation-duration:3.0s;animation-delay:1.12s"/>
            <circle class="spark" cx="383" cy="1380" r="3" fill="#ff7a2f" style="--dx:-33px;--dy:-317px;animation-duration:3.9s;animation-delay:0.33s"/>
            <circle class="spark" cx="254" cy="1380" r="2" fill="#ffb347" style="--dx:-47px;--dy:-405px;animation-duration:3.1s;animation-delay:0.78s"/>
            <circle class="spark" cx="513" cy="1380" r="2" fill="#ffd166" style="--dx:3px;--dy:-479px;animation-duration:3.7s;animation-delay:0.52s"/>
            <circle class="spark" cx="375" cy="1380" r="3" fill="#ffd166" style="--dx:-48px;--dy:-378px;animation-duration:3.1s;animation-delay:1.45s"/>
            <circle class="spark" cx="820" cy="1380" r="3" fill="#ffb347" style="--dx:-13px;--dy:-546px;animation-duration:3.6s;animation-delay:0.21s"/>
            <circle class="spark" cx="205" cy="1380" r="2" fill="#ff7a2f" style="--dx:-54px;--dy:-275px;animation-duration:4.1s;animation-delay:1.41s"/>
            <circle class="spark" cx="63" cy="1380" r="3" fill="#ffb347" style="--dx:-59px;--dy:-335px;animation-duration:3.8s;animation-delay:0.07s"/>
            <rect x="-2000" y="1146" width="5000" height="400" fill="#1a0a06"/><ellipse cx="384" cy="1150" rx="700" ry="20" fill="#ff6a1a" opacity=".3" class="glowp"/>
            <text x="384" y="156" text-anchor="middle" font-size="50" font-weight="700" fill="#ffb380">Oh no!</text>
            <g style="--gdur:2.4s;--canim:jitter 2.4s infinite">
                <text x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#ff7a2f" opacity=".55" filter="url(#blur6)">Error 500</text>
                <text class="gl1" x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#00e5ff">Error 500</text>
                <text class="gl2" x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#ff2d6f">Error 500</text>
                <text class="codeT" x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#fff">Error <tspan fill="#ff7a2f">500</tspan></text>
            </g>
            <text x="384" y="304" text-anchor="middle" font-size="36" font-weight="600" fill="#fff">Internal Server Error</text>
            <text x="384" y="346" text-anchor="middle" font-size="24" font-weight="500" fill="#fff" opacity=".78">Server blew up! Our team is putting out the fire.</text>

            {{-- SLOT LOGO MOBILE: kiri = public/images/logo-unpam.webp, kanan = public/images/logo.webp --}}
            <image class="logo" href="{{ asset('images/logo.webp') }}" x="476" y="14" width="240" height="100" preserveAspectRatio="xMaxYMid meet" onerror="this.style.display='none'"/>
            <image class="logo" href="{{ asset('images/logo-unpam.webp') }}" x="52" y="4" width="240" height="120" preserveAspectRatio="xMinYMid meet" onerror="this.style.display='none'"/>

            <use href="#hero" x="0" y="640" width="768" height="499"/>
            <rect class="flash" x="-2000" y="-1000" width="5400" height="3600" fill="#fff"/>

            {{-- TOMBOL --}}
            @if($_isAuth)
            <a class="btn btn-primary" href="{{ $_home }}" role="button" tabindex="0"><rect class="btn-bg" x="194" y="372" width="380" height="56" rx="28"/><text x="384" y="400" text-anchor="middle" dominant-baseline="central" font-size="26">Back to Dashboard</text></a>
            @endif
        </svg>
    </main>
</body>
</html>