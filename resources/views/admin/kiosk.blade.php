<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Presensi Gerbang - {{ \App\Models\Setting::getTitleAppName() }}</title>
    <link rel="icon" type="image/webp" href="{{ \App\Models\Setting::getLogoUrl() }}">

    <!-- Google Fonts: Poppins & Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & Boxicons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <!-- HTML5 QR Code Scanner (lokal, 2.3.8) -->
    <script src="{{ asset('js/html5-qrcode.min.js') }}"></script>

    <!-- Presensi Unified Design Tokens -->
    <link rel="stylesheet" href="{{ asset('css/presensi-tokens.css') }}?v={{ file_exists(public_path('css/presensi-tokens.css')) ? filemtime(public_path('css/presensi-tokens.css')) : config('app.version', '1') }}">

    <style>
        :root {
            /* Latar halaman = foto sekolah (public/images/bg.webp) + kabut putih tipis,
               supaya halaman tidak polos tetapi teks/kamera tetap mudah dibaca
               (desktop maupun mobile) */
            --kiosk-bg: #dbe4f0;
            --kiosk-bg-image: url('{{ asset('images/bg.webp') }}');
            --kiosk-card: rgba(255, 255, 255, 0.60);
            --kiosk-veil: rgba(255, 255, 255, 0.72);
            --kiosk-border: rgba(255, 255, 255, 0.65);
            --kiosk-text: #0f172a;
            --kiosk-muted: #5a6a80;
            --kiosk-primary: #2563eb;
        }

        html {
            background-color: var(--kiosk-bg);
            overflow: hidden;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            background-color: transparent;
            background-image: var(--kiosk-bg-image);
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Latar foto sekolah: fixed di belakang seluruh halaman */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -2;
            background-image: var(--kiosk-bg-image);
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }

        /* Overlay hitam tipis di atas foto background (z-index di bawah kamera) */
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            background: rgba(0, 0, 0, 0.12);
        }

        .mono { font-family: 'JetBrains Mono', monospace; }

        /* ===== HEADER & FOOTER: DIHAPUS (tampilan full-screen) ===== */

        .kiosk-main {
            flex-grow: 1;
            min-height: 0;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; /* Kamera persis di tengah vertikal */
            padding: 0 1rem;
        }

        .kiosk-card-box {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            width: 100%;
            max-width: 540px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* ===== TOMBOL POJOK (DENGAN TEKS DI SAMPING) ===== */
        .kiosk-corner-btn {
            position: fixed;
            z-index: 1050;
            height: 44px;
            padding: 0 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(15, 23, 42, 0.55);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 50rem;
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            -webkit-tap-highlight-color: transparent;
        }
        .kiosk-corner-btn:hover {
            background: rgba(15, 23, 42, 0.8);
            border-color: rgba(255, 255, 255, 0.45);
            color: #ffffff;
            transform: translateY(-2px);
        }
        .kiosk-corner-btn:active {
            transform: translateY(0);
        }
        .kiosk-corner-btn i {
            font-size: 1.3rem;
            display: block;
            -webkit-text-stroke: 0px;
        }
        .kiosk-exit-corner {
            top: auto;
            bottom: 24px;
            left: 24px;
        }
        .kiosk-fullscreen-corner {
            top: auto;
            bottom: 24px;
            right: 24px;
        }

        /* ===== BESAR FULLSCREEN ===== */
        :root:fullscreen {
            --gerbang-camera-size: min(78vh, 580px);
        }

        /* ===== JAM DIGITAL ===== */
        .kiosk-clock {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 1050;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            color: #ffffff;
            opacity: 0.85;
            transition: opacity 0.2s ease;
        }
        .kiosk-clock:hover {
            opacity: 1;
        }

        .kiosk-clock-time {
            font-family: 'Poppins', 'Roboto', system-ui, sans-serif;
            font-size: 2.5rem;
            font-weight: 600;
            line-height: 1.2;
            letter-spacing: 0.02em;
            font-variant-numeric: tabular-nums;
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.45);
        }

        .kiosk-clock-date {
            font-family: 'Poppins', 'Roboto', system-ui, sans-serif;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.3;
            margin-top: 2px;
            opacity: 0.9;
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.45);
        }

        .kiosk-mobile-header {
            display: none;
        }
    </style>

    {{-- ============ CSS KAMERA ============ --}}
    <style>
        /* ==========================================================================
           KAMERA GERBANG - UKURAN 520px SESUAI PERMINTAAN
           ========================================================================== */
        :root {
            --gerbang-camera-size: 520px;   /* Ukuran 520px di tengah */
            --gerbang-camera-radius: 16px;
            --gerbang-scan-ratio: 72%;
            --gerbang-card-max: 540px;
        }

        .kiosk-card-box {
            text-align: center;
            position: relative;
        }

        /* ===== SWITCHER PILL ===== */
        .kiosk-switcher {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 4px;
            background: rgba(0, 0, 0, 0.35);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50rem;
            margin-bottom: 1rem;
            opacity: 0.8;
            transition: opacity 0.2s ease;
        }
        .kiosk-switcher:hover {
            opacity: 1;
        }

        .kiosk-switch-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            color: #ffffff;
            font-weight: 500;
            font-size: 0.85rem;
            width: 40px;
            height: 40px;
            border-radius: 50rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .kiosk-switch-btn:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .kiosk-switch-btn.active {
            background: rgba(37, 99, 235, 0.7);
            color: #ffffff;
        }

        /* ===== VIEWPORT SCANNER ===== */
        .scanner-viewport-container {
            position: relative;
            width: min(100%, var(--gerbang-camera-size));
            aspect-ratio: 1 / 1;
            margin: 0 auto;
            border: none;
            border-radius: var(--gerbang-camera-radius);
            background: rgba(15, 23, 42, 0.88);
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.35);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .scanner-viewport-container.camera-active {
            border: none;
            background: #000000;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.45);
        }

        #reader {
            position: relative;
            width: 100% !important;
            height: 100% !important;
            border: none !important;
            background: #000000;
            aspect-ratio: 1 / 1 !important;
            min-height: 0 !important;
            max-height: none !important;
            overflow: hidden !important;
        }
        #reader video {
            display: block;
            width: 100% !important;
            height: 100% !important;
            min-width: 100% !important;
            min-height: 100% !important;
            object-fit: cover !important;
            border-radius: 14px;
        }
        #reader video::-webkit-media-controls,
        #reader video::-webkit-media-controls-enclosure,
        #reader video::-webkit-media-controls-panel {
            display: none !important;
        }

        #reader canvas { display: none !important; }
        #qr-shaded-region { pointer-events: none !important; }
        #qr-shaded-region > div { display: none !important; }

        /* ===== BESAR FULLSCREEN ===== */
        :root:fullscreen {
            --gerbang-camera-size: min(78vh, 580px);
        }
        
        /* ===== BRACKET 4 SUDUT ===== */
        .kiosk-scan-frame {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            z-index: 6;
        }

        .kiosk-scan-box {
            position: relative;
            width: var(--gerbang-scan-ratio);
            height: var(--gerbang-scan-ratio);
        }

        .kiosk-corner {
            position: absolute;
            width: 40px;
            height: 40px;
            border: 4px solid #ffffff;
            border-radius: 0;
        }
        .kiosk-corner--tl { top: 0; left: 0; border-right: 0; border-bottom: 0; }
        .kiosk-corner--tr { top: 0; right: 0; border-left: 0; border-bottom: 0; }
        .kiosk-corner--bl { bottom: 0; left: 0; border-right: 0; border-top: 0; }
        .kiosk-corner--br { bottom: 0; right: 0; border-left: 0; border-top: 0; }

        /* ===== PLACEHOLDER KAMERA ===== */
        .kiosk-placeholder {
            position: absolute;
            inset: 0;
            z-index: 7;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.75rem;
            text-align: center;
            background: rgba(0, 0, 0, 0.7);
            border-radius: var(--gerbang-camera-radius);
        }

        .kiosk-placeholder-text {
            margin: 0.5rem 0 0;
            font-size: 0.85rem;
            font-weight: 600;
            line-height: 1.35;
            color: #ffffff;
        }

        /* ===== MODE ALAT SCANNER USB ===== */
        #hardwareView {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.9rem;
            background: rgba(0, 0, 0, 0.5);
            border-radius: var(--gerbang-camera-radius);
        }

        /* ===== MOBILE (<= 768px) ===== */
        @media (max-width: 768px) {
            :root { --gerbang-camera-size: 340px; }
            body::before { background-position: center 32%; }
            .kiosk-corner-btn span { display: none; }
            .kiosk-corner-btn { width: 44px; padding: 0; }
        }

        @media (max-width: 480px) {
            :root { --gerbang-camera-size: 290px; }
            .overlay-title { font-size: 0.92rem; }
            .overlay-sub { font-size: 0.72rem; }
            .kiosk-corner { width: 28px; height: 28px; }
        }

        /* ===== MOBILE KHUSUS (max-width: 767px) ===== */
        @media (max-width: 767px) {
            html { background-color: #ffffff; }
            body {
                min-height: 100vh;
                min-height: 100dvh;
                background-color: #ffffff;
                background-image: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            }
            body::before { display: none; }
            body::after { display: none; }

            .kiosk-main {
                flex: 1 1 auto;
                align-items: center;
                justify-content: center;
                padding-top: 0;
            }
            .kiosk-mobile-logo {
                display: block;
                width: clamp(56px, 17vw, 72px);
                height: auto;
                object-fit: contain;
            }
            .kiosk-mobile-name {
                font-family: 'Poppins', 'Roboto', system-ui, sans-serif;
                font-size: 1.25rem;
                font-weight: 700;
                line-height: 1.25;
                color: #0f172a;
                text-wrap: balance;
                margin-top: 0.25rem;
            }
            .kiosk-mobile-sub {
                font-family: 'Poppins', 'Roboto', system-ui, sans-serif;
                font-size: 0.8rem;
                font-weight: 500;
                color: #64748b;
                margin-top: 0.1rem;
            }

            .kiosk-clock {
                position: static;
                align-items: center;
                color: #0f172a;
                opacity: 1;
                padding: 0.35rem 1rem 0;
                flex: 0 0 auto;
            }
            .kiosk-clock:hover { opacity: 1; }
            .kiosk-clock-time {
                font-size: 2rem;
                color: #0f172a;
                text-shadow: none;
            }
            .kiosk-clock-date {
                font-size: 0.8rem;
                color: #64748b;
                opacity: 1;
                text-shadow: none;
            }

            :root {
                --gerbang-camera-size: min(86vw, 360px);
            }
            .kiosk-switcher {
                margin-bottom: 0.5rem;
                opacity: 1;
            }
        }

        /* ===== LAYAR PENDEK ===== */
        @media (max-width: 767px) and (max-height: 639px) {
            .kiosk-mobile-header {
                padding-top: max(0.25rem, env(safe-area-inset-top, 0px));
            }
            .kiosk-mobile-logo { width: 48px; }
            .kiosk-mobile-name {
                font-size: 0.95rem;
                margin-top: 0.1rem;
            }
            .kiosk-mobile-sub { font-size: 0.65rem; }
            .kiosk-clock { padding-top: 0.15rem; }
            .kiosk-clock-time { font-size: 1.5rem; }
            .kiosk-clock-date { font-size: 0.65rem; }
            .kiosk-main {
                padding-top: 0.2rem;
                padding-bottom: calc(3rem + env(safe-area-inset-bottom, 0px));
            }
            .kiosk-switcher { margin-bottom: 0.375rem; }
            :root {
                --gerbang-camera-size: min(86vw, 360px, calc(100vh - 250px));
                --gerbang-camera-size: min(86vw, 360px, calc(100dvh - 250px));
            }
            .kiosk-placeholder { padding: 0.5rem; }
            .kiosk-placeholder i { font-size: 1.7rem !important; }
            .kiosk-placeholder-text { font-size: 0.7rem; }
        }
    </style>
</head>

<body>

    <!-- Tombol Keluar Mode Gerbang: Kiri Bawah dengan Teks -->
    <a href="{{ panel_route('absensi.index') }}" class="kiosk-corner-btn kiosk-exit-corner" title="Keluar Mode Gerbang" aria-label="Keluar Mode Gerbang">
        <i class='bx bx-log-out'></i>
        <span>Keluar</span>
    </a>

    <!-- Tombol Layar Penuh: Kanan Bawah dengan Teks -->
    <button type="button" class="kiosk-corner-btn kiosk-fullscreen-corner" onclick="toggleFullscreen()" title="Buka Layar Penuh" aria-label="Layar Penuh">
        <i class='bx bx-fullscreen'></i>
        <span>Layar Penuh</span>
    </button>

    <!-- Header khusus MOBILE (<=767px) -->
    <div class="kiosk-mobile-header">
        <img class="kiosk-mobile-logo" src="{{ \App\Models\Setting::getLogoUrl() }}" alt="Logo Sekolah">
        <div class="kiosk-mobile-name">{{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</div>
        <div class="kiosk-mobile-sub">Presensi Gerbang</div>
    </div>

    <!-- Jam Digital -->
    <div class="kiosk-clock" aria-label="Jam digital">
        <div class="kiosk-clock-time" id="liveClock">00:00:00</div>
        <div class="kiosk-clock-date" id="liveDate">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, j F Y') }}</div>
    </div>

    <!-- KAMERA DI TENGAH -->
    <main class="kiosk-main">
        <div class="kiosk-card-box">

            <!-- Switcher Pill: kecil & transparan di atas kamera -->
            <div class="kiosk-switcher" role="tablist" aria-label="Metode input presensi">
                <button type="button" id="btnTabCamera" class="kiosk-switch-btn active" onclick="switchMode('camera')" role="tab" aria-selected="true">
                    <i class='bx bx-camera'></i>
                </button>
                <button type="button" id="btnTabHardware" class="kiosk-switch-btn" onclick="switchMode('hardware')" role="tab" aria-selected="false">
                    <i class='bx bx-barcode-reader'></i>
                </button>
            </div>

            <!-- Scanner Box -->
            <div id="scannerBox" class="scanner-viewport-container camera-active">

                <!-- Mode Kamera -->
                <div id="cameraView" class="w-100 h-100">
                    <div id="reader"></div>
                    <div id="cameraPlaceholder" class="kiosk-placeholder">
                        <i class='bx bx-qr-scan' style="font-size: 2.8rem; color: #94a3b8;"></i>
                        <p class="kiosk-placeholder-text">Tempelkan kartu QR ke kamera</p>
                    </div>
                    <!-- Bracket 4 sudut -->
                    <div class="kiosk-scan-frame" aria-hidden="true">
                        <div class="kiosk-scan-box">
                            <span class="kiosk-corner kiosk-corner--tl"></span>
                            <span class="kiosk-corner kiosk-corner--tr"></span>
                            <span class="kiosk-corner kiosk-corner--bl"></span>
                            <span class="kiosk-corner kiosk-corner--br"></span>
                        </div>
                    </div>
                </div>

                <!-- Mode Alat Scanner USB -->
                <div id="hardwareView" class="d-none">
                    <i class='bx bx-scan' style="font-size: 2.6rem; color: #2563eb;"></i>
                    <p class="kiosk-placeholder-text">{{ __('Scan kartu dengan alat, atau ketik NIS/NIP lalu tekan Enter') }}</p>
                    <div class="w-100 mt-2 px-1">
                        <input type="text" id="hardwareInput" class="form-control form-control-sm text-center fw-semibold font-monospace py-2" placeholder="{{ __('Ketik NIS/NIP untuk Siswa/Guru') }}" autocomplete="off">
                    </div>
                </div>

                <!-- Overlay Hasil Scan Terpadu -->
                <div id="scanResultOverlay" class="scan-result-overlay d-none">
                    <div class="scan-result-content">
                        <svg class="scan-result-svg" viewBox="0 0 100 100" width="110" height="110">
                            <circle class="scan-result-circle" cx="50" cy="50" r="45" fill="none" stroke-width="6"/>
                            <!-- 1. Centang Putih -->
                            <path class="scan-result-icon scan-result-check" d="M30 52 L45 67 L72 35" fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                            <!-- 2. Silang X Putih -->
                            <g class="scan-result-icon scan-result-cross">
                                <path class="scan-result-cross-1" d="M35 35 L65 65" fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round"/>
                                <path class="scan-result-cross-2" d="M65 35 L35 65" fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round"/>
                            </g>
                            <!-- 3. Tanda Seru (!) Putih -->
                            <g class="scan-result-icon scan-result-exclamation">
                                <path class="scan-result-exclamation-line" d="M50 28 L50 56" fill="none" stroke="#ffffff" stroke-width="8" stroke-linecap="round"/>
                                <circle class="scan-result-exclamation-dot" cx="50" cy="71" r="4.5" fill="#ffffff"/>
                            </g>
                        </svg>
                        <div class="scan-result-text">
                            <div class="scan-result-name" id="scanResultTitle">Nama Siswa</div>
                            <div class="scan-result-status" id="scanResultSubtitle">Status Presensi</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <audio id="beepSound" src="{{ asset('audio/beep.mp3') }}" preload="auto"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Jam digital realtime - update setiap detik
        (function() {
            const clockEl = document.getElementById('liveClock');
            const dateEl = document.getElementById('liveDate');
            
            function updateClock() {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                if (clockEl) {
                    clockEl.innerText = `${hours}:${minutes}:${seconds}`;
                }
                
                // Update tanggal mengikuti bahasa aktif (id / en)
                if (dateEl) {
                    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' };
                    dateEl.innerText = now.toLocaleDateString({!! json_encode(app()->getLocale() === 'en' ? 'en-US' : 'id-ID') !!}, options);
                }
            }
            
            // Update immediately
            updateClock();
            
            // Update every second
            const intervalId = setInterval(updateClock, 1000);
            
            // Cleanup when page is unloaded
            window.addEventListener('beforeunload', function() {
                clearInterval(intervalId);
            });
        })();
    </script>

    <script>window.__i18n = Object.assign(window.__i18n || {}, {!! json_encode([
        'connection_error' => __('Terjadi kendala koneksi ke server, coba lagi'),
        'placeholder_camera' => __('Tempelkan kartu QR ke kamera'),
        'try_again' => __('Coba Lagi'),
        'camera_denied' => __('Izin kamera ditolak. Izinkan akses kamera di pengaturan browser.'),
        'camera_busy' => __('Kamera sedang dipakai aplikasi atau tab lain. Tutup lalu coba lagi.'),
        'camera_not_found' => __('Kamera tidak ditemukan.'),
        'camera_open_failed' => __('Kamera gagal dibuka. Coba lagi atau gunakan Alat Scanner.'),
        'late' => __('Terlambat'),
        'check_out' => __('Presensi Pulang'),
        'present' => __('Hadir'),
        'already_checked_in' => __('Siswa atas nome :name sudah melakukan presensi hari ini'),
        'card_not_found' => __('Kartu tidak dikenali atau data siswa tidak ditemukan'),
        'failed' => __('Gagal'),
    ], JSON_UNESCAPED_UNICODE) !!});</script>
    <script src="{{ asset('js/scanner.js') }}?v={{ file_exists(public_path('js/scanner.js')) ? filemtime(public_path('js/scanner.js')) : config('app.version', '1') }}" data-process-route="{{ panel_route('scanner.process') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var scannerBox = document.getElementById('scannerBox');
            if (!scannerBox) return;

            var cameraTab = document.getElementById('btnTabCamera');
            var hardwareTab = document.getElementById('btnTabHardware');
            if (cameraTab && hardwareTab && hardwareTab.classList.contains('active')) {
                window.switchMode && window.switchMode('camera');
            }

            if (!window.__kioskScanner.started) {
                window.__kioskScanner.start && window.__kioskScanner.start();
            } else if (window.__kioskScanner.state === 'idle' && window.__kioskScanner.retryCamera) {
                window.__kioskScanner.retryCamera();
            }
        });
    </script>
</body>
</html>