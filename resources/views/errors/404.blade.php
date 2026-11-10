<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>{{ config('app.name', 'Website') }} - Error 404</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg1:#030b1f; --bg2:#10509a; --btn:#c9a54f; --btn-ink:#0b2a5b; }
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
        .ping { transform-box: fill-box; transform-origin: center; opacity:0; animation: ping 2.4s ease-out infinite; }
        @keyframes ping { 0% { transform:scale(.4); opacity:.9; } 100% { transform:scale(1.8); opacity:0; } }
        @media (prefers-reduced-motion: reduce) { .ping { animation:none !important; opacity:.4; } }

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
        <h1 class="sr-only">Error 404 - Page Not Found</h1>
        <p class="sr-only">This page was sucked into a digital black hole.</p>

        <svg class="defs" aria-hidden="true" focusable="false">
            <defs>
                <filter id="glow" x="-30%" y="-30%" width="160%" height="160%">
                    <feGaussianBlur stdDeviation="9" result="b"/>
                    <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
                <filter id="blur6" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="6"/></filter>

                <linearGradient id="planetG" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#5a9be6"/><stop offset="1" stop-color="#0d3166"/></linearGradient>
                <linearGradient id="beamG" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff6c4" stop-opacity=".7"/><stop offset="1" stop-color="#fff6c4" stop-opacity="0"/></linearGradient>
                <radialGradient id="nebula" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#2a6fb8" stop-opacity=".55"/><stop offset="1" stop-color="#2a6fb8" stop-opacity="0"/></radialGradient>
                <radialGradient id="bhGlow" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#000" stop-opacity=".95"/><stop offset=".45" stop-color="#3b1d6e" stop-opacity=".55"/><stop offset="1" stop-color="#3b1d6e" stop-opacity="0"/></radialGradient>
                <symbol id="hero" viewBox="0 0 800 520" overflow="visible">
                    <circle cx="620" cy="250" r="230" fill="url(#nebula)"/>
                    <g transform="rotate(-20 620 250)"><path d="M370 250 A250 52 0 0 1 870 250" fill="none" stroke="#c9a54f" stroke-width="10" opacity=".9"/></g>
                    <circle cx="620" cy="250" r="125" fill="url(#planetG)"/>
                    <circle cx="570" cy="205" r="18" fill="#0d3166" opacity=".45"/><circle cx="665" cy="300" r="26" fill="#0d3166" opacity=".4"/><circle cx="650" cy="195" r="10" fill="#0d3166" opacity=".4"/>
                    <path d="M575 128 L600 182 L578 214 L618 258 L592 312 L630 372" fill="none" stroke="#061a3a" stroke-width="7" stroke-linejoin="round"/>
                    <path d="M575 128 L600 182 L578 214 L618 258 L592 312 L630 372" fill="none" stroke="#e3c26a" stroke-width="2.5" stroke-linejoin="round" class="tw"/>
                    <g transform="rotate(-20 620 250)"><path d="M370 250 A250 52 0 0 0 870 250" fill="none" stroke="#c9a54f" stroke-width="10"/></g>
                    <circle cx="110" cy="96" r="40" fill="#8fb3de"/><circle cx="96" cy="86" r="9" fill="#6c97cb"/><circle cx="126" cy="108" r="12" fill="#6c97cb"/>
                    <text x="190" y="330" font-size="84" font-weight="800" fill="#c9a54f" opacity=".85" class="tw2">?</text>
                    <text x="470" y="96" font-size="64" font-weight="800" fill="#e3c26a" opacity=".8" class="tw3">?</text>
                    <text x="700" y="500" font-size="72" font-weight="800" fill="#c9a54f" opacity=".7" class="tw">?</text>
                    <text x="60" y="470" font-size="54" font-weight="800" fill="#e3c26a" opacity=".6" class="tw4">?</text>
                    <g transform="translate(-72 420)"><circle r="130" fill="url(#bhGlow)"/><g class="rot" style="animation-duration:10s"><ellipse rx="90" ry="24" fill="none" stroke="#c9a54f" stroke-width="8" opacity=".9" transform="rotate(-20)"/><ellipse rx="64" ry="16" fill="none" stroke="#e3c26a" stroke-width="4" opacity=".7" transform="rotate(-20)"/><path d="M0 0 C25 -45 80 -55 120 -22 M0 0 C-25 45 -80 55 -120 22" fill="none" stroke="#8fb3de" stroke-width="3" opacity=".55"/></g><circle r="30" fill="#000" stroke="#e3c26a" stroke-width="2"/></g>
                    <g transform="translate(330 270)"><g class="float"><g class="drift">
                        <circle class="ping" cx="0" cy="-98" r="70" fill="none" stroke="#ff4d4d" stroke-width="4"/><circle class="ping" cx="0" cy="-98" r="70" fill="none" stroke="#ff4d4d" stroke-width="3" style="animation-delay:1.2s"/>
                        <path d="M-50 22 C-130 50 -140 130 -230 120 S -330 70 -400 150" stroke="#c9a54f" stroke-width="5" fill="none" stroke-dasharray="14 9"/>
                        <circle cx="-402" cy="150" r="7" fill="#ffd166" class="tw"/>
                        <rect x="-68" y="-52" width="46" height="98" rx="10" fill="#9fb4cc"/>
                        <path d="M-24 56 L-58 136" stroke="#e6edf6" stroke-width="32" stroke-linecap="round"/>
                        <rect x="-82" y="128" width="48" height="24" rx="10" fill="#c9a54f" transform="rotate(20 -58 140)"/>
                        <path d="M24 56 L64 126" stroke="#e6edf6" stroke-width="32" stroke-linecap="round"/>
                        <rect x="44" y="118" width="48" height="24" rx="10" fill="#c9a54f" transform="rotate(-24 68 130)"/>
                        <rect x="-46" y="-58" width="92" height="124" rx="30" fill="#f2f6fb"/>
                        <rect x="-24" y="-22" width="48" height="28" rx="6" fill="#c9a54f"/>
                        <circle cx="-10" cy="-8" r="4" fill="#e8483a"/><circle cx="6" cy="-8" r="4" fill="#4fbf6a"/>
                        <path d="M-38 -32 L-94 -4" stroke="#e6edf6" stroke-width="28" stroke-linecap="round"/><circle cx="-100" cy="-1" r="15" fill="#c9a54f"/>
                        <path d="M38 -32 L98 -64" stroke="#e6edf6" stroke-width="28" stroke-linecap="round"/><circle cx="104" cy="-67" r="15" fill="#c9a54f"/>
                        <rect x="104" y="-86" width="48" height="22" rx="6" fill="#44566e"/>
                        <polygon points="152,-80 380,-190 380,40" fill="url(#beamG)" class="tw3"/>
                        <circle cx="0" cy="-98" r="54" fill="#f2f6fb"/>
                        <ellipse cx="8" cy="-96" rx="38" ry="31" fill="#0b2a5b"/>
                        <path d="M-14 -118 Q4 -126 22 -116" stroke="#8fb3de" stroke-width="5" fill="none" stroke-linecap="round" opacity=".8"/>
                        <rect x="-8" y="-156" width="6" height="22" rx="3" fill="#c9a54f"/><circle cx="-5" cy="-160" r="6" fill="#e8483a" class="tw"/>
                    </g></g></g>
                    <g transform="translate(470 404)"><rect width="250" height="84" rx="8" fill="#061a3a" stroke="#c9a54f" stroke-width="3" opacity=".95"/><text x="16" y="30" font-family="'Courier New',monospace" font-size="17" fill="#ff5b5b" class="warn">SIGNAL: LOST</text><text x="16" y="56" font-family="'Courier New',monospace" font-size="15" fill="#e3c26a">OXYGEN 12%</text><rect x="16" y="64" width="218" height="8" rx="4" fill="#0d3166"/><rect x="16" y="64" width="26" height="8" rx="4" fill="#ff5b5b" class="warn"/></g>
                </symbol>
            </defs>
        </svg>

        {{-- ========================= DESKTOP ========================= --}}
        <svg class="scene desk" viewBox="0 0 1376 768" preserveAspectRatio="xMidYMax meet" role="img" aria-label="Error 404 - Page Not Found">
            <circle class="tw4" cx="466" cy="310" r="1.0" fill="#fff"/>
            <circle class="tw3" cx="-131" cy="68" r="0.8" fill="#fff"/>
            <circle class="tw3" cx="-259" cy="227" r="1.7" fill="#fff"/>
            <circle class="tw" cx="207" cy="108" r="1.3" fill="#fff"/>
            <circle class="tw3" cx="566" cy="278" r="1.1" fill="#fff"/>
            <circle class="tw3" cx="1025" cy="88" r="2.3" fill="#fff"/>
            <circle class="tw4" cx="1572" cy="254" r="1.1" fill="#fff"/>
            <circle class="tw3" cx="1743" cy="307" r="0.8" fill="#fff"/>
            <circle class="tw4" cx="776" cy="520" r="1.2" fill="#fff"/>
            <circle class="tw3" cx="1265" cy="462" r="1.1" fill="#fff"/>
            <circle class="tw3" cx="-323" cy="83" r="0.9" fill="#fff"/>
            <circle class="tw2" cx="1430" cy="351" r="1.1" fill="#e3c26a"/>
            <circle class="tw3" cx="330" cy="650" r="1.9" fill="#fff"/>
            <circle class="tw2" cx="1285" cy="602" r="1.4" fill="#fff"/>
            <circle class="tw3" cx="-87" cy="63" r="2.1" fill="#fff"/>
            <circle class="tw2" cx="472" cy="125" r="1.4" fill="#fff"/>
            <circle class="tw" cx="-395" cy="43" r="1.4" fill="#fff"/>
            <circle class="tw" cx="670" cy="691" r="2.5" fill="#fff"/>
            <circle class="tw4" cx="817" cy="156" r="2.2" fill="#fff"/>
            <circle class="tw4" cx="-182" cy="300" r="1.9" fill="#fff"/>
            <circle class="tw2" cx="524" cy="390" r="1.9" fill="#fff"/>
            <circle class="tw2" cx="-462" cy="372" r="0.9" fill="#fff"/>
            <circle class="tw4" cx="985" cy="297" r="1.8" fill="#fff"/>
            <circle class="tw" cx="1236" cy="212" r="1.0" fill="#e3c26a"/>
            <circle class="tw2" cx="190" cy="609" r="2.0" fill="#fff"/>
            <circle class="tw3" cx="1737" cy="502" r="1.8" fill="#fff"/>
            <circle class="tw4" cx="0" cy="541" r="1.3" fill="#fff"/>
            <circle class="tw4" cx="320" cy="489" r="1.2" fill="#fff"/>
            <circle class="tw2" cx="-349" cy="224" r="1.6" fill="#fff"/>
            <circle class="tw" cx="1252" cy="220" r="1.7" fill="#e3c26a"/>
            <circle class="tw2" cx="537" cy="248" r="1.7" fill="#fff"/>
            <circle class="tw3" cx="571" cy="145" r="1.4" fill="#fff"/>
            <circle class="tw" cx="1833" cy="412" r="2.5" fill="#fff"/>
            <circle class="tw2" cx="-120" cy="440" r="1.2" fill="#fff"/>
            <circle class="tw4" cx="1430" cy="657" r="1.4" fill="#fff"/>
            <circle class="tw4" cx="598" cy="346" r="2.5" fill="#e3c26a"/>
            <circle class="tw" cx="647" cy="642" r="2.0" fill="#fff"/>
            <circle class="tw" cx="22" cy="275" r="2.0" fill="#fff"/>
            <circle class="tw4" cx="1402" cy="582" r="1.6" fill="#fff"/>
            <circle class="tw2" cx="395" cy="3" r="1.2" fill="#e3c26a"/>
            <circle class="tw4" cx="554" cy="118" r="1.5" fill="#fff"/>
            <circle class="tw2" cx="1755" cy="54" r="2.4" fill="#e3c26a"/>
            <circle class="tw4" cx="854" cy="575" r="2.2" fill="#fff"/>
            <circle class="tw" cx="1300" cy="27" r="0.9" fill="#fff"/>
            <circle class="tw" cx="552" cy="622" r="2.2" fill="#fff"/>
            <circle class="tw3" cx="1642" cy="10" r="1.3" fill="#fff"/>
            <circle class="tw2" cx="-152" cy="556" r="1.6" fill="#fff"/>
            <circle class="tw4" cx="774" cy="397" r="1.2" fill="#fff"/>
            <circle class="tw3" cx="1138" cy="97" r="0.9" fill="#fff"/>
            <circle class="tw4" cx="1277" cy="425" r="2.3" fill="#e3c26a"/>
            <circle class="tw4" cx="302" cy="652" r="1.3" fill="#fff"/>
            <circle class="tw2" cx="1786" cy="171" r="1.5" fill="#e3c26a"/>
            <circle class="tw3" cx="111" cy="334" r="1.7" fill="#fff"/>
            <circle class="tw3" cx="-481" cy="172" r="0.8" fill="#e3c26a"/>
            <circle class="tw4" cx="-47" cy="498" r="2.2" fill="#fff"/>
            <circle class="tw3" cx="1632" cy="251" r="1.5" fill="#fff"/>
            <circle class="tw" cx="238" cy="641" r="0.8" fill="#fff"/>
            <circle class="tw4" cx="783" cy="557" r="2.4" fill="#fff"/>
            <circle class="tw4" cx="1827" cy="312" r="2.5" fill="#fff"/>
            <circle class="tw3" cx="1310" cy="401" r="1.1" fill="#fff"/>
            <circle class="tw2" cx="892" cy="136" r="1.6" fill="#fff"/>
            <circle class="tw3" cx="227" cy="292" r="2.4" fill="#fff"/>
            <circle class="tw" cx="935" cy="691" r="1.9" fill="#e3c26a"/>
            <circle class="tw3" cx="235" cy="337" r="2.0" fill="#e3c26a"/>
            <circle class="tw" cx="-406" cy="618" r="0.8" fill="#fff"/>
            <circle class="tw4" cx="-97" cy="162" r="1.1" fill="#fff"/>
            <circle class="tw4" cx="-186" cy="119" r="1.1" fill="#fff"/>
            <circle class="tw3" cx="410" cy="632" r="2.2" fill="#fff"/>
            <circle class="tw4" cx="1163" cy="613" r="1.2" fill="#fff"/>
            <circle class="tw3" cx="768" cy="376" r="1.2" fill="#fff"/>
            <circle class="tw4" cx="1669" cy="462" r="2.4" fill="#fff"/>
            <circle class="tw" cx="782" cy="290" r="1.6" fill="#fff"/>
            <circle class="tw3" cx="1712" cy="469" r="2.0" fill="#fff"/>
            <circle class="tw" cx="1091" cy="638" r="0.9" fill="#fff"/>
            <circle class="tw" cx="922" cy="618" r="2.2" fill="#fff"/>
            <circle class="tw4" cx="1480" cy="227" r="2.0" fill="#fff"/>
            <circle class="tw" cx="-229" cy="152" r="2.5" fill="#fff"/>
            <circle class="tw3" cx="-308" cy="167" r="1.5" fill="#e3c26a"/>
            <circle class="tw2" cx="505" cy="161" r="2.2" fill="#e3c26a"/>
            <circle class="tw" cx="796" cy="83" r="1.0" fill="#fff"/>
            <circle class="tw2" cx="683" cy="624" r="2.4" fill="#fff"/>
            <circle class="tw" cx="1199" cy="133" r="2.4" fill="#fff"/>
            <circle class="tw4" cx="-490" cy="565" r="1.1" fill="#e3c26a"/>
            <circle class="tw4" cx="721" cy="495" r="1.7" fill="#e3c26a"/>
            <circle class="tw3" cx="548" cy="527" r="1.5" fill="#fff"/>
            <circle class="tw4" cx="845" cy="174" r="1.5" fill="#fff"/>
            <circle class="tw2" cx="641" cy="692" r="0.8" fill="#e3c26a"/>
            <circle class="tw2" cx="-26" cy="623" r="2.3" fill="#fff"/>
            <circle class="tw2" cx="-465" cy="488" r="2.4" fill="#fff"/>
            <circle class="tw4" cx="1352" cy="699" r="2.5" fill="#fff"/>
            <circle class="tw" cx="206" cy="543" r="1.9" fill="#fff"/>
            <circle class="tw4" cx="961" cy="312" r="2.5" fill="#fff"/>
            <circle class="tw3" cx="-348" cy="647" r="1.2" fill="#fff"/>
            <circle class="tw" cx="1455" cy="640" r="2.3" fill="#fff"/>
            <circle class="tw" cx="794" cy="106" r="2.0" fill="#fff"/>
            <circle class="tw3" cx="1658" cy="487" r="2.6" fill="#fff"/>
            <circle class="tw2" cx="225" cy="44" r="1.7" fill="#e3c26a"/>
            <circle class="tw2" cx="-208" cy="344" r="2.2" fill="#fff"/>
            <circle class="tw" cx="-22" cy="519" r="2.6" fill="#e3c26a"/>
            <circle class="tw" cx="426" cy="638" r="1.3" fill="#fff"/>
            <circle class="tw2" cx="-360" cy="10" r="1.7" fill="#fff"/>
            <circle class="tw3" cx="520" cy="353" r="2.3" fill="#fff"/>
            <circle class="tw4" cx="1149" cy="34" r="1.1" fill="#fff"/>
            <circle class="tw3" cx="1591" cy="521" r="1.3" fill="#fff"/>
            <circle class="tw" cx="1291" cy="329" r="1.1" fill="#fff"/>
            <circle class="tw4" cx="1290" cy="282" r="1.4" fill="#fff"/>
            <circle class="tw2" cx="1177" cy="328" r="2.2" fill="#fff"/>
            <circle class="tw3" cx="1593" cy="88" r="0.9" fill="#fff"/>
            <circle class="tw" cx="42" cy="627" r="2.2" fill="#fff"/>
            <circle class="tw2" cx="179" cy="635" r="1.0" fill="#fff"/>
            <circle class="tw4" cx="538" cy="684" r="1.2" fill="#fff"/>
            <circle class="tw2" cx="894" cy="8" r="1.6" fill="#e3c26a"/>
            <circle class="tw" cx="500" cy="531" r="2.3" fill="#fff"/>
            <circle class="tw2" cx="794" cy="303" r="2.4" fill="#fff"/>
            <circle class="tw2" cx="-190" cy="677" r="2.3" fill="#fff"/>
            <circle class="tw3" cx="-389" cy="185" r="2.0" fill="#e3c26a"/>
            <circle class="tw3" cx="828" cy="646" r="1.4" fill="#fff"/>
            <circle class="tw" cx="165" cy="175" r="2.2" fill="#fff"/>
            <circle class="tw" cx="157" cy="700" r="2.5" fill="#fff"/>
            <circle class="tw4" cx="572" cy="684" r="2.0" fill="#e3c26a"/>
            <line class="meteor" x1="1250" y1="60" x2="1320" y2="25" stroke="#fff" stroke-width="3" stroke-linecap="round" style="animation-delay:0.0s"/>
            <line class="meteor" x1="900" y1="30" x2="970" y2="-5" stroke="#fff" stroke-width="3" stroke-linecap="round" style="animation-delay:1.3s"/>
            <line class="meteor" x1="1380" y1="200" x2="1450" y2="165" stroke="#fff" stroke-width="3" stroke-linecap="round" style="animation-delay:2.6s"/>
            <line class="meteor" x1="560" y1="10" x2="630" y2="-25" stroke="#fff" stroke-width="3" stroke-linecap="round" style="animation-delay:3.9s"/>
            <text x="688" y="92" text-anchor="middle" font-size="44" font-weight="700" fill="#8fb3de">Lost!</text>
            <g style="--gdur:4.2s;--canim:none">
                <text x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#c9a54f" opacity=".55" filter="url(#blur6)">Error 404</text>
                <text class="gl1" x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#00e5ff">Error 404</text>
                <text class="gl2" x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#ff2bd6">Error 404</text>
                <text class="codeT" x="688" y="182" text-anchor="middle" font-size="92" font-weight="800" fill="#fff">Error <tspan fill="#c9a54f">404</tspan></text>
            </g>
            <text x="688" y="234" text-anchor="middle" font-size="36" font-weight="600" fill="#fff">Page Not Found</text>
            <text x="688" y="270" text-anchor="middle" font-size="22" font-weight="500" fill="#fff" opacity=".78">This page was sucked into a digital black hole.</text>

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
        <svg class="scene mob" viewBox="0 0 768 1376" preserveAspectRatio="xMidYMax meet" role="img" aria-label="Error 404 - Page Not Found">
            <circle class="tw" cx="975" cy="523" r="2.1" fill="#fff"/>
            <circle class="tw2" cx="210" cy="1329" r="0.9" fill="#e3c26a"/>
            <circle class="tw" cx="660" cy="504" r="1.5" fill="#fff"/>
            <circle class="tw2" cx="-274" cy="443" r="1.5" fill="#fff"/>
            <circle class="tw" cx="497" cy="326" r="2.2" fill="#e3c26a"/>
            <circle class="tw" cx="964" cy="911" r="1.0" fill="#fff"/>
            <circle class="tw2" cx="-290" cy="428" r="2.2" fill="#fff"/>
            <circle class="tw2" cx="292" cy="642" r="2.5" fill="#fff"/>
            <circle class="tw3" cx="103" cy="784" r="1.3" fill="#fff"/>
            <circle class="tw3" cx="-2" cy="540" r="0.9" fill="#fff"/>
            <circle class="tw3" cx="900" cy="6" r="1.9" fill="#e3c26a"/>
            <circle class="tw3" cx="427" cy="626" r="1.7" fill="#fff"/>
            <circle class="tw" cx="667" cy="360" r="0.9" fill="#fff"/>
            <circle class="tw4" cx="432" cy="827" r="0.8" fill="#fff"/>
            <circle class="tw2" cx="884" cy="18" r="1.6" fill="#fff"/>
            <circle class="tw4" cx="102" cy="243" r="2.2" fill="#fff"/>
            <circle class="tw4" cx="426" cy="1074" r="1.3" fill="#e3c26a"/>
            <circle class="tw" cx="452" cy="605" r="0.9" fill="#fff"/>
            <circle class="tw2" cx="750" cy="1250" r="1.5" fill="#fff"/>
            <circle class="tw3" cx="816" cy="188" r="1.4" fill="#fff"/>
            <circle class="tw3" cx="-140" cy="1283" r="1.1" fill="#fff"/>
            <circle class="tw4" cx="30" cy="99" r="0.9" fill="#e3c26a"/>
            <circle class="tw4" cx="916" cy="704" r="2.3" fill="#fff"/>
            <circle class="tw" cx="-2" cy="114" r="2.6" fill="#fff"/>
            <circle class="tw2" cx="384" cy="424" r="1.0" fill="#fff"/>
            <circle class="tw3" cx="547" cy="218" r="1.1" fill="#e3c26a"/>
            <circle class="tw2" cx="561" cy="603" r="1.1" fill="#fff"/>
            <circle class="tw3" cx="628" cy="999" r="2.0" fill="#fff"/>
            <circle class="tw2" cx="296" cy="962" r="1.5" fill="#e3c26a"/>
            <circle class="tw4" cx="789" cy="367" r="1.9" fill="#fff"/>
            <circle class="tw3" cx="68" cy="182" r="1.7" fill="#e3c26a"/>
            <circle class="tw3" cx="428" cy="1203" r="2.0" fill="#fff"/>
            <circle class="tw3" cx="1068" cy="575" r="2.3" fill="#fff"/>
            <circle class="tw" cx="301" cy="696" r="2.0" fill="#fff"/>
            <circle class="tw4" cx="213" cy="667" r="2.0" fill="#fff"/>
            <circle class="tw3" cx="724" cy="1325" r="2.0" fill="#fff"/>
            <circle class="tw4" cx="1019" cy="707" r="2.1" fill="#fff"/>
            <circle class="tw3" cx="53" cy="921" r="2.6" fill="#fff"/>
            <circle class="tw3" cx="784" cy="340" r="1.2" fill="#fff"/>
            <circle class="tw4" cx="677" cy="579" r="2.0" fill="#e3c26a"/>
            <circle class="tw4" cx="889" cy="1057" r="2.0" fill="#fff"/>
            <circle class="tw" cx="832" cy="1304" r="1.3" fill="#fff"/>
            <circle class="tw2" cx="901" cy="903" r="1.9" fill="#fff"/>
            <circle class="tw4" cx="69" cy="1292" r="2.1" fill="#fff"/>
            <circle class="tw" cx="39" cy="110" r="2.4" fill="#fff"/>
            <circle class="tw" cx="71" cy="988" r="1.1" fill="#fff"/>
            <circle class="tw" cx="419" cy="778" r="2.0" fill="#fff"/>
            <circle class="tw4" cx="187" cy="765" r="0.8" fill="#fff"/>
            <circle class="tw3" cx="539" cy="235" r="2.0" fill="#fff"/>
            <circle class="tw3" cx="826" cy="1256" r="1.3" fill="#fff"/>
            <circle class="tw3" cx="395" cy="1189" r="1.3" fill="#fff"/>
            <circle class="tw3" cx="558" cy="838" r="2.3" fill="#fff"/>
            <circle class="tw4" cx="21" cy="1220" r="1.5" fill="#fff"/>
            <circle class="tw" cx="-28" cy="1246" r="1.0" fill="#fff"/>
            <circle class="tw2" cx="367" cy="1158" r="2.4" fill="#fff"/>
            <circle class="tw2" cx="855" cy="769" r="1.6" fill="#fff"/>
            <circle class="tw2" cx="682" cy="777" r="1.5" fill="#fff"/>
            <circle class="tw4" cx="232" cy="616" r="1.7" fill="#e3c26a"/>
            <circle class="tw2" cx="329" cy="1007" r="2.5" fill="#fff"/>
            <circle class="tw4" cx="-51" cy="1351" r="1.9" fill="#fff"/>
            <circle class="tw" cx="-219" cy="279" r="2.3" fill="#fff"/>
            <circle class="tw4" cx="847" cy="560" r="1.2" fill="#fff"/>
            <circle class="tw2" cx="203" cy="1003" r="1.3" fill="#fff"/>
            <circle class="tw4" cx="285" cy="600" r="1.7" fill="#fff"/>
            <circle class="tw2" cx="936" cy="242" r="0.8" fill="#fff"/>
            <circle class="tw" cx="792" cy="688" r="1.9" fill="#fff"/>
            <circle class="tw" cx="439" cy="1209" r="1.0" fill="#fff"/>
            <circle class="tw2" cx="633" cy="220" r="2.4" fill="#e3c26a"/>
            <circle class="tw4" cx="575" cy="1218" r="1.8" fill="#fff"/>
            <circle class="tw2" cx="489" cy="974" r="1.5" fill="#fff"/>
            <circle class="tw" cx="652" cy="135" r="1.3" fill="#fff"/>
            <circle class="tw4" cx="586" cy="1192" r="1.3" fill="#fff"/>
            <circle class="tw4" cx="-8" cy="338" r="1.7" fill="#fff"/>
            <circle class="tw" cx="801" cy="314" r="1.6" fill="#e3c26a"/>
            <circle class="tw" cx="171" cy="547" r="2.3" fill="#fff"/>
            <circle class="tw" cx="-248" cy="682" r="2.1" fill="#fff"/>
            <circle class="tw" cx="696" cy="100" r="1.0" fill="#fff"/>
            <circle class="tw2" cx="1022" cy="227" r="2.1" fill="#fff"/>
            <circle class="tw2" cx="596" cy="306" r="1.1" fill="#fff"/>
            <circle class="tw" cx="30" cy="137" r="1.9" fill="#fff"/>
            <circle class="tw3" cx="1052" cy="776" r="2.1" fill="#e3c26a"/>
            <circle class="tw2" cx="878" cy="242" r="2.1" fill="#e3c26a"/>
            <circle class="tw4" cx="-113" cy="644" r="2.0" fill="#fff"/>
            <circle class="tw2" cx="1043" cy="766" r="2.4" fill="#fff"/>
            <circle class="tw3" cx="9" cy="1164" r="1.9" fill="#fff"/>
            <circle class="tw3" cx="836" cy="1126" r="1.9" fill="#e3c26a"/>
            <circle class="tw3" cx="182" cy="520" r="2.3" fill="#fff"/>
            <circle class="tw4" cx="-27" cy="480" r="2.4" fill="#fff"/>
            <circle class="tw3" cx="67" cy="268" r="2.1" fill="#e3c26a"/>
            <circle class="tw" cx="1067" cy="73" r="1.0" fill="#fff"/>
            <circle class="tw4" cx="628" cy="488" r="1.5" fill="#fff"/>
            <circle class="tw" cx="362" cy="220" r="2.2" fill="#fff"/>
            <circle class="tw4" cx="487" cy="109" r="2.3" fill="#fff"/>
            <circle class="tw4" cx="156" cy="237" r="1.0" fill="#fff"/>
            <circle class="tw2" cx="-14" cy="1279" r="1.5" fill="#fff"/>
            <circle class="tw4" cx="956" cy="714" r="2.2" fill="#e3c26a"/>
            <circle class="tw4" cx="433" cy="1326" r="1.3" fill="#fff"/>
            <circle class="tw2" cx="441" cy="1059" r="0.9" fill="#fff"/>
            <circle class="tw" cx="510" cy="129" r="1.0" fill="#fff"/>
            <circle class="tw4" cx="100" cy="80" r="1.7" fill="#fff"/>
            <circle class="tw3" cx="413" cy="0" r="1.6" fill="#fff"/>
            <circle class="tw4" cx="968" cy="877" r="1.4" fill="#e3c26a"/>
            <circle class="tw3" cx="16" cy="1338" r="1.1" fill="#fff"/>
            <circle class="tw" cx="661" cy="311" r="1.8" fill="#fff"/>
            <circle class="tw" cx="30" cy="587" r="2.0" fill="#fff"/>
            <circle class="tw3" cx="-235" cy="705" r="2.5" fill="#fff"/>
            <circle class="tw4" cx="-180" cy="44" r="2.0" fill="#fff"/>
            <circle class="tw3" cx="19" cy="241" r="2.2" fill="#fff"/>
            <circle class="tw2" cx="1081" cy="491" r="2.1" fill="#e3c26a"/>
            <circle class="tw" cx="-249" cy="1177" r="1.2" fill="#fff"/>
            <circle class="tw3" cx="445" cy="638" r="1.1" fill="#fff"/>
            <circle class="tw2" cx="658" cy="334" r="2.2" fill="#fff"/>
            <circle class="tw3" cx="-211" cy="1322" r="1.9" fill="#e3c26a"/>
            <circle class="tw4" cx="-176" cy="927" r="2.2" fill="#e3c26a"/>
            <circle class="tw3" cx="-232" cy="1123" r="2.3" fill="#fff"/>
            <circle class="tw4" cx="-155" cy="485" r="2.4" fill="#e3c26a"/>
            <circle class="tw" cx="665" cy="110" r="2.0" fill="#fff"/>
            <circle class="tw4" cx="-177" cy="98" r="1.3" fill="#fff"/>
            <circle class="tw2" cx="1011" cy="105" r="2.4" fill="#e3c26a"/>
            <circle class="tw2" cx="741" cy="1311" r="1.5" fill="#e3c26a"/>
            <line class="meteor" x1="700" y1="120" x2="770" y2="85" stroke="#fff" stroke-width="3" stroke-linecap="round" style="animation-delay:0.0s"/>
            <line class="meteor" x1="500" y1="40" x2="570" y2="5" stroke="#fff" stroke-width="3" stroke-linecap="round" style="animation-delay:1.3s"/>
            <line class="meteor" x1="760" y1="500" x2="830" y2="465" stroke="#fff" stroke-width="3" stroke-linecap="round" style="animation-delay:2.6s"/>
            <line class="meteor" x1="300" y1="10" x2="370" y2="-25" stroke="#fff" stroke-width="3" stroke-linecap="round" style="animation-delay:3.9s"/>
            <text x="384" y="156" text-anchor="middle" font-size="50" font-weight="700" fill="#8fb3de">Lost!</text>
            <g style="--gdur:4.2s;--canim:none">
                <text x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#c9a54f" opacity=".55" filter="url(#blur6)">Error 404</text>
                <text class="gl1" x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#00e5ff">Error 404</text>
                <text class="gl2" x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#ff2bd6">Error 404</text>
                <text class="codeT" x="384" y="250" text-anchor="middle" font-size="92" font-weight="800" fill="#fff">Error <tspan fill="#c9a54f">404</tspan></text>
            </g>
            <text x="384" y="304" text-anchor="middle" font-size="36" font-weight="600" fill="#fff">Page Not Found</text>
            <text x="384" y="346" text-anchor="middle" font-size="24" font-weight="500" fill="#fff" opacity=".78">This page was sucked into a digital black hole.</text>

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