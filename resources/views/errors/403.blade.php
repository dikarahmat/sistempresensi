<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>{{ config('app.name', 'Website') }} - Error 403</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg1:#12030a; --bg2:#7a1426; --btn:#ffcf5a; --btn-ink:#3a0a14; }
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
        .stage::before, .stage::after { content:""; position:absolute; inset:0; pointer-events:none; animation: siren 1.2s ease-in-out infinite; }
        .stage::before { background: radial-gradient(ellipse at 0% 0%, rgba(255,40,60,.45), transparent 50%); }
        .stage::after { background: radial-gradient(ellipse at 100% 0%, rgba(50,110,255,.45), transparent 50%); animation-delay:-.6s; }
        .stamp { transform-box: fill-box; transform-origin: center; opacity:0; animation: stamp 5s ease-out infinite; }
        .laser { animation: laser 3.4s ease-in-out infinite alternate; }
        @keyframes siren { 0%,45% { opacity:1; } 50%,95% { opacity:.1; } 100% { opacity:1; } }
        @keyframes stamp { 0%,8% { opacity:0; transform:scale(2.6); } 14% { opacity:.95; transform:scale(1); } 85% { opacity:.95; transform:scale(1); } 95%,100% { opacity:0; transform:scale(1); } }
        @keyframes laser { from { transform:translateX(-260px); } to { transform:translateX(1060px); } }
        @media (prefers-reduced-motion: reduce) { .stage::before, .stage::after, .laser { animation:none !important; } .stamp { animation:none !important; opacity:.95; } }

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
    @if($_logout)
    <form id="logout-form" method="POST" action="{{ $_logout }}" style="display:none">@csrf</form>
    @endif

    <main class="stage" role="main">
        <h1 class="sr-only">Error 403 - Access Denied</h1>
        <p class="sr-only">Restricted zone! You don't have permission to enter.</p>

        <svg class="defs" aria-hidden="true" focusable="false">
            <defs>
                <filter id="glow" x="-30%" y="-30%" width="160%" height="160%">
                    <feGaussianBlur stdDeviation="9" result="b"/>
                    <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
                <filter id="blur6" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="6"/></filter>

                <pattern id="hazK" width="44" height="40" patternUnits="userSpaceOnUse">
                    <rect width="44" height="40" fill="#140407"/>
                    <polygon points="0,40 22,0 44,0 22,40" fill="#ffcf5a"/>
                </pattern>
                <radialGradient id="redGlow" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#ff2d3d" stop-opacity=".75"/><stop offset="1" stop-color="#ff2d3d" stop-opacity="0"/></radialGradient>
                <linearGradient id="goldG" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f1d27c"/><stop offset=".55" stop-color="#c9a54f"/><stop offset="1" stop-color="#8a6a22"/></linearGradient>
                <linearGradient id="beamB" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#3a7bff" stop-opacity=".95"/><stop offset="1" stop-color="#3a7bff" stop-opacity="0"/></linearGradient>
                <linearGradient id="beamR" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#ff3347" stop-opacity=".95"/><stop offset="1" stop-color="#ff3347" stop-opacity="0"/></linearGradient>
                <symbol id="hero" viewBox="0 0 800 520" overflow="visible">
                    <circle cx="400" cy="270" r="270" fill="url(#redGlow)" class="glowp"/>
                    <g transform="rotate(-15 400 300)">
                        <rect x="-220" y="268" width="1240" height="66" fill="#140407"/>
                        <rect x="-220" y="268" width="1240" height="9" fill="url(#hazK)"/><rect x="-220" y="325" width="1240" height="9" fill="url(#hazK)"/>
                        <text x="400" y="314" text-anchor="middle" font-size="26" font-weight="800" fill="#ffcf5a" textLength="1100" lengthAdjust="spacing">ACCESS DENIED &#9679; ACCESS DENIED &#9679; ACCESS DENIED &#9679; ACCESS DENIED &#9679;</text>
                    </g>
                    <g transform="rotate(15 400 300)">
                        <rect x="-220" y="268" width="1240" height="66" fill="#140407"/>
                        <rect x="-220" y="268" width="1240" height="9" fill="url(#hazK)"/><rect x="-220" y="325" width="1240" height="9" fill="url(#hazK)"/>
                        <text x="400" y="314" text-anchor="middle" font-size="26" font-weight="800" fill="#ffcf5a" textLength="1100" lengthAdjust="spacing">NO ENTRY &#9679; NO ENTRY &#9679; NO ENTRY &#9679; NO ENTRY &#9679; NO ENTRY &#9679; NO ENTRY &#9679;</text>
                    </g>
                    <g class="shake">
                        <path d="M302 262 V176 A98 98 0 0 1 498 176 V262" fill="none" stroke="#9aa3b2" stroke-width="48"/>
                        <path d="M302 262 V176 A98 98 0 0 1 498 176 V262" fill="none" stroke="#e8edf5" stroke-width="12" transform="translate(-9 0)" opacity=".8"/>
                        <rect x="236" y="240" width="328" height="262" rx="42" fill="url(#goldG)" stroke="#6b4f12" stroke-width="7"/>
                        <rect x="252" y="254" width="296" height="40" rx="20" fill="#fff" opacity=".18"/>
                        <circle cx="268" cy="276" r="8" fill="#6b4f12"/><circle cx="532" cy="276" r="8" fill="#6b4f12"/><circle cx="268" cy="466" r="8" fill="#6b4f12"/><circle cx="532" cy="466" r="8" fill="#6b4f12"/>
                        <circle cx="400" cy="352" r="40" fill="#2a060d"/>
                        <polygon points="381,368 419,368 436,448 364,448" fill="#2a060d"/>
                        <circle cx="400" cy="352" r="40" fill="none" stroke="#ff2d3d" stroke-width="3" class="glowp"/>
                    </g>
                    <g class="laser"><rect x="0" y="-80" width="3" height="720" fill="#ff2d3d" opacity=".85"/><rect x="-7" y="-80" width="17" height="720" fill="#ff2d3d" opacity=".16"/></g>
                    <g transform="translate(400 420) rotate(-10)"><g class="stamp"><rect x="-196" y="-50" width="392" height="100" rx="10" fill="#140407" fill-opacity=".6" stroke="#ff2d3d" stroke-width="9"/><text x="0" y="23" text-anchor="middle" font-size="64" font-weight="800" fill="#ff2d3d" letter-spacing="4">DENIED</text></g></g>
                    <g transform="translate(690 96)">
                        <circle r="64" fill="#e11d2e" stroke="#fff" stroke-width="9" class="glowp"/>
                        <rect x="-40" y="-10" width="80" height="20" rx="3" fill="#fff"/>
                    </g>
                    <g transform="translate(110 96)">
                        <circle r="64" fill="#e11d2e" stroke="#fff" stroke-width="9" class="glowp" style="animation-delay:.5s"/>
                        <path d="M-26 -26 L26 26 M26 -26 L-26 26" stroke="#fff" stroke-width="14" stroke-linecap="round"/>
                    </g>
                    <g class="scan"><rect x="-300" y="110" width="1400" height="3" fill="#ff2d3d" opacity=".85"/><rect x="-300" y="110" width="1400" height="14" fill="#ff2d3d" opacity=".18"/></g>
                </symbol>
            </defs>
        </svg>

        {{-- ========================= DESKTOP ========================= --}}
        <svg class="scene desk" viewBox="0 0 1376 768" preserveAspectRatio="xMidYMax meet" role="img" aria-label="Error 403 - Access Denied">
            <g transform="translate(70 40)"><g class="rot"><polygon points="0,0 1500,-170 1500,170" fill="url(#beamR)" opacity=".28"/></g><circle r="16" fill="#ff2d3d" class="warn"/></g>
            <g transform="translate(1306 40)"><g class="rot rev"><polygon points="0,0 1500,-170 1500,170" fill="url(#beamB)" opacity=".3"/></g><circle r="16" fill="#3a7bff" class="warn" style="animation-delay:.45s"/></g>
            <rect x="-2000" y="742" width="5400" height="60" fill="#12030a"/><rect x="-2000" y="742" width="5400" height="14" fill="url(#hazK)"/>
            <text x="688" y="92" text-anchor="middle" font-size="44" font-weight="700" fill="#ffb3bb">Stop!</text>
            <g style="--gdur:3.4s;--canim:pulseT 1.4s ease-in-out infinite">
                <text x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#ffcf5a" opacity=".55" filter="url(#blur6)">Error 403</text>
                <text class="gl1" x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#ff2d3d">Error 403</text>
                <text class="gl2" x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#ffcf5a">Error 403</text>
                <text class="codeT" x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#fff">Error <tspan fill="#ffcf5a">403</tspan></text>
            </g>
            <text x="688" y="234" text-anchor="middle" font-size="36" font-weight="600" fill="#fff">Access Denied</text>
            <text x="688" y="270" text-anchor="middle" font-size="22" font-weight="500" fill="#fff" opacity=".78">Restricted zone! You don't have permission to enter.</text>

            {{-- SLOT LOGO DESKTOP: kiri = public/images/logo-unpam.webp, kanan = public/images/logo.webp --}}
            <image class="logo" href="{{ asset('images/logo.webp') }}" x="1105" y="112" width="150" height="134" preserveAspectRatio="xMidYMid meet" onerror="this.style.display='none'"/>
            <image class="logo" href="{{ asset('images/logo-unpam.webp') }}" x="96" y="84" width="200" height="190" preserveAspectRatio="xMidYMid meet" onerror="this.style.display='none'"/>

            <use href="#hero" x="366" y="352" width="644" height="416"/>
            

            {{-- TOMBOL --}}
            @if($_isAuth)
            <a class="btn btn-primary" href="{{ $_home }}" role="button" tabindex="0"><rect class="btn-bg" x="480" y="292" width="200" height="40" rx="20"/><text x="580" y="312" text-anchor="middle" dominant-baseline="central" font-size="19">Go to Dashboard</text></a>
            @if($_logout)
            <a class="btn btn-ghost" href="#" onclick="document.getElementById('logout-form').submit();return false;" role="button" tabindex="0"><rect class="btn-bg" x="696" y="292" width="200" height="40" rx="20"/><text x="796" y="312" text-anchor="middle" dominant-baseline="central" font-size="19">Log Out</text></a>
            @endif
            @endif
        </svg>

        {{-- ========================= MOBILE ========================= --}}
        <svg class="scene mob" viewBox="0 0 768 1376" preserveAspectRatio="xMidYMax meet" role="img" aria-label="Error 403 - Access Denied">
            <g transform="translate(50 40)"><g class="rot"><polygon points="0,0 1500,-170 1500,170" fill="url(#beamR)" opacity=".28"/></g><circle r="16" fill="#ff2d3d" class="warn"/></g>
            <g transform="translate(718 40)"><g class="rot rev"><polygon points="0,0 1500,-170 1500,170" fill="url(#beamB)" opacity=".3"/></g><circle r="16" fill="#3a7bff" class="warn" style="animation-delay:.45s"/></g>
            <rect x="-2000" y="1150" width="5000" height="400" fill="#12030a"/><rect x="-2000" y="1150" width="5000" height="18" fill="url(#hazK)"/>
            <text x="384" y="156" text-anchor="middle" font-size="50" font-weight="700" fill="#ffb3bb">Stop!</text>
            <g style="--gdur:3.4s;--canim:pulseT 1.4s ease-in-out infinite">
                <text x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#ffcf5a" opacity=".55" filter="url(#blur6)">Error 403</text>
                <text class="gl1" x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#ff2d3d">Error 403</text>
                <text class="gl2" x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#ffcf5a">Error 403</text>
                <text class="codeT" x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#fff">Error <tspan fill="#ffcf5a">403</tspan></text>
            </g>
            <text x="384" y="304" text-anchor="middle" font-size="36" font-weight="600" fill="#fff">Access Denied</text>
            <text x="384" y="346" text-anchor="middle" font-size="24" font-weight="500" fill="#fff" opacity=".78">Restricted zone! You don't have permission to enter.</text>

            {{-- SLOT LOGO MOBILE: kiri = public/images/logo-unpam.webp, kanan = public/images/logo.webp --}}
            <image class="logo" href="{{ asset('images/logo.webp') }}" x="476" y="14" width="240" height="100" preserveAspectRatio="xMaxYMid meet" onerror="this.style.display='none'"/>
            <image class="logo" href="{{ asset('images/logo-unpam.webp') }}" x="52" y="4" width="240" height="120" preserveAspectRatio="xMinYMid meet" onerror="this.style.display='none'"/>

            <use href="#hero" x="0" y="640" width="768" height="499"/>
            

            {{-- TOMBOL --}}
            @if($_isAuth)
            <a class="btn btn-primary" href="{{ $_home }}" role="button" tabindex="0"><rect class="btn-bg" x="74" y="372" width="300" height="56" rx="28"/><text x="224" y="400" text-anchor="middle" dominant-baseline="central" font-size="26">Go to Dashboard</text></a>
            @if($_logout)
            <a class="btn btn-ghost" href="#" onclick="document.getElementById('logout-form').submit();return false;" role="button" tabindex="0"><rect class="btn-bg" x="394" y="372" width="300" height="56" rx="28"/><text x="544" y="400" text-anchor="middle" dominant-baseline="central" font-size="26">Log Out</text></a>
            @endif
            @endif
        </svg>
    </main>
</body>
</html>