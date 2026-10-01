<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Presensi Gerbang | {{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</title>
    <link rel="icon" type="image/webp" href="{{ asset(\App\Models\Setting::getLogo()) }}">

    <!-- Google Fonts: Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & Boxicons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <!-- HTML5 QR Code Scanner (lokal, 2.3.8) -->
    <script src="{{ asset('js/html5-qrcode.min.js') }}"></script>

    <!-- Presensi Unified Design Tokens -->
    <!-- Presensi Unified Design Tokens (satu sumber gaya; ?v= agar browser
         selalu mengambil versi terbaru — sama seperti kiosk & panel) -->
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
            /* Warna cadangan bila foto gagal dimuat */
            background-color: var(--kiosk-bg);
        }

        body {
            font-family: 'Roboto', sans-serif;
            /* Transparan supaya lapisan foto (::before) di bawahnya terlihat */
            background-color: transparent;
            color: var(--kiosk-text);
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ===== LATAR FOTO SEKOLAH (fixed, tidak bergeser saat halaman digulir) ===== */
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

        /* Kabut putih: menjaga keterbacaan teks di atas foto */
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            background:
                radial-gradient(120% 85% at 50% 38%, rgba(255, 255, 255, 0.32) 0%, rgba(255, 255, 255, 0.78) 100%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.52) 0%, rgba(255, 255, 255, 0.28) 35%, rgba(255, 255, 255, 0.70) 100%);
        }

        .mono { font-family: 'JetBrains Mono', monospace; }

        /* ===== HEADER: Kaca Transparan (logo kiri, jam kanan) ===== */
        .kiosk-header {
            background: var(--kiosk-veil);
            -webkit-backdrop-filter: blur(10px) saturate(140%);
            backdrop-filter: blur(10px) saturate(140%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.55);
            padding: 0.9rem 1.25rem;
            width: 100%;
        }

        .kiosk-header-inner {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .kiosk-brand-text {
            min-width: 0;
            overflow: hidden;
        }

        .kiosk-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }

        .kiosk-brand-logo,
        .kiosk-brand-fallback {
            width: 48px;
            height: 48px;
            min-width: 48px;
            flex-shrink: 0;
        }

        .kiosk-brand-logo {
            object-fit: contain;
            background: transparent;
            border: none;
            box-shadow: none;
            border-radius: 0;
        }

        .kiosk-brand-fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            color: var(--kiosk-primary);
            font-size: 1.5rem;
        }

        .kiosk-school-name {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1.2;
            color: #0f172a;
        }

        .kiosk-subtitle {
            font-size: 0.8rem;
            font-weight: 400;
            color: var(--kiosk-muted);
            margin-top: 1px;
        }

        /* ===== JAM DIGITAL (Mentok Kanan) ===== */
        .kiosk-clock-block { 
            text-align: right; 
        }

        .kiosk-clock {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -0.02em;
            color: #0f172a;
        }

        .kiosk-date {
            font-size: 0.75rem;
            font-weight: 400;
            color: var(--kiosk-muted);
            margin-top: 1px;
        }

        /* ===== JAM OPERASIONAL (dari Pengaturan) ===== */
        .kiosk-hours {
            margin: 0.85rem 0 0;
            font-size: 0.76rem;
            font-weight: 400;
            color: var(--kiosk-muted);
            text-align: center;
            line-height: 1.4;
        }
        .kiosk-hours strong {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            color: var(--kiosk-text);
        }

        /* ===== AREA UTAMA (Kotak Card Putih Kamera Di Tengah) ===== */
        .kiosk-main {
            flex-grow: 1;
            min-height: 0;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem calc(4.5rem + env(safe-area-inset-bottom, 0px));
        }

        .kiosk-card-box {
            /* Panel kaca: foto sekolah tetap terlihat, kamera tetap fokus di tengah */
            background: rgba(255, 255, 255, 0.55) !important;
            -webkit-backdrop-filter: blur(14px) saturate(140%);
            backdrop-filter: blur(14px) saturate(140%);
            border: 1px solid rgba(255, 255, 255, 0.6) !important;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18) !important;
            border-radius: 1.5rem !important;
            padding: 1.25rem !important;
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* ===== BAR BAWAH: Kaca Transparan (kiri & kanan) ===== */
        .kiosk-bottom-bar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1040;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.72);
            -webkit-backdrop-filter: blur(10px) saturate(140%);
            backdrop-filter: blur(10px) saturate(140%);
            border-top: 1px solid rgba(255, 255, 255, 0.55);
            padding: 0.6rem 1.25rem calc(0.6rem + env(safe-area-inset-bottom, 0px));
            width: 100%;
        }

        .btn-kiosk-action {
            padding: 0.45rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid var(--kiosk-border);
            background: rgba(255, 255, 255, 0.82);
            color: var(--kiosk-text);
        }
        .btn-kiosk-action:hover {
            background: #ffffff;
            color: var(--kiosk-text);
            border-color: rgba(255, 255, 255, 0.9);
        }
    </style>

    {{-- ============ CSS KAMERA ============ --}}
    <style>
        /* ==========================================================================
           KAMERA GERBANG (sama persis untuk /admin/scanner & /admin/absensi/kiosk)
           Ubah ukuran kamera hanya di --gerbang-camera-size di bawah.
           ========================================================================== */
        :root {
            --gerbang-camera-size: 320px;   /* sisi kotak kamera (persegi 1:1) */
            --gerbang-camera-radius: 16px;
            --gerbang-scan-ratio: 70%;     /* harus sama dengan GERBANG_SCAN_RATIO di JS */
            --gerbang-card-max: 380px;
        }

        /* ===== KARTU KAMERA: PANEL KACA =====
           Panel bening di atas foto sekolah; ukuran & radius diatur di blok utama. */
        .kiosk-card-box {
            text-align: center;
            position: relative;
        }

        /* ===== SWITCHER PILL: Kamera | Alat Scanner (kaca) ===== */
        .kiosk-switcher {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 4px;
            background: rgba(255, 255, 255, 0.65);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 50rem;
            margin-bottom: 1rem;
        }

        .kiosk-switch-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.5rem 1.05rem;
            min-height: 40px;
            border-radius: 50rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .kiosk-switch-btn:hover { color: #1e293b; }

        .kiosk-switch-btn.active {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }

        /* ===== VIEWPORT SCANNER =====
           Ukuran dikunci di sini (bukan hanya saat .camera-active) supaya mode
           "Alat Scanner" juga tetap persegi dan tidak melebar. Tanpa garis/latar pembatas. */
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

        /* iOS/Safari: sembunyikan kontrol media bawaan agar tidak "ngeframe" */
        #reader video::-webkit-media-controls,
        #reader video::-webkit-media-controls-enclosure,
        #reader video::-webkit-media-controls-panel {
            display: none !important;
        }

        /* Canvas internal html5-qrcode tidak boleh tampil */
        #reader canvas { display: none !important; }

        /* Area luar qrbox digelapkan library; shader putih bawaan disembunyikan
           supaya tidak ada kotak putih menggantung di luar video. */
        #qr-shaded-region { pointer-events: none !important; }
        #qr-shaded-region > div { display: none !important; }
        /* ===== BRACKET 4 SUDUT (sejajar & simetris dengan qrbox proporsional) ===== */
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
            width: 28px;
            height: 28px;
            border: 5px solid #ffffff;
        }
        .kiosk-corner--tl { top: 0; left: 0; border-right: 0; border-bottom: 0; border-top-left-radius: 10px; }
        .kiosk-corner--tr { top: 0; right: 0; border-left: 0; border-bottom: 0; border-top-right-radius: 10px; }
        .kiosk-corner--bl { bottom: 0; left: 0; border-right: 0; border-top: 0; border-bottom-left-radius: 10px; }
        .kiosk-corner--br { bottom: 0; right: 0; border-left: 0; border-top: 0; border-bottom-right-radius: 10px; }

        /* ===== PLACEHOLDER KAMERA (pesan izin + tombol Coba Lagi) ===== */
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
            background: rgba(255, 255, 255, 0.94);
        }

        .kiosk-placeholder-text {
            margin: 0.5rem 0 0;
            font-size: 0.76rem;
            font-weight: 600;
            line-height: 1.35;
            color: #64748b;
        }

        /* ===== MODE ALAT SCANNER USB (dalam kotak yang sama) ===== */
        #hardwareView {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.9rem;
        }

        /* ===== MOBILE (<= 768px): HEADER TERSUSUN VERTIKAL =====
           Baris 1 : logo + nama sekolah  (kiri-kanan, di tengah)
           Baris 2 : jam digital + tanggal (di tengah)
           Baris 3 : scanner / kamera (tetap di tengah layar) */
        @media (max-width: 768px) {
            /* Kamerakan dikecilkan sedikit supaya muat di HP dan tetap center */
            :root { --gerbang-camera-size: 300px; }

            /* Foto tetap dipakai di HP, fokus ke bagian tengah atas (gedung + papan nama) */
            body::before {
                background-position: center 32%;
            }
            body::after {
                background:
                    radial-gradient(130% 80% at 50% 30%, rgba(255, 255, 255, 0.34) 0%, rgba(255, 255, 255, 0.80) 100%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.55) 0%, rgba(255, 255, 255, 0.30) 32%, rgba(255, 255, 255, 0.72) 100%);
            }
            .kiosk-card-box {
                background: rgba(255, 255, 255, 0.58) !important;
                padding: 1rem !important;
            }

            /* Header dikunci tinggi & rata tengah:
               -> isi header duduk sedikit lebih bawah
               -> tinggi header tidak pernah berubah
               -> posisi kamera di tengah TIDAK PERNAH bergeser */
            .kiosk-header {
                height: 196px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0.75rem 1rem;
                overflow: hidden;
                flex: 0 0 auto;
            }
            .kiosk-header-inner {
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                text-align: center;
            }
            /* Baris 1: logo (tunggal) */
            .kiosk-brand-logo,
            .kiosk-brand-fallback { width: 54px; height: 54px; min-width: 54px; }
            .kiosk-brand-fallback { font-size: 1.7rem; }
            .kiosk-brand {
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                max-width: 100%;
                text-align: center;
            }
            /* Baris 2: nama sekolah + "Absensi Gerbang" */
            .kiosk-brand-text { text-align: center; }
            .kiosk-school-name {
                font-size: 1.1rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .kiosk-subtitle { font-size: 0.72rem; margin-top: 2px; }
            /* Baris 3: jam + tanggal */
            .kiosk-clock-block { text-align: center; }
            .kiosk-clock { font-size: 1.7rem; }
            .kiosk-date { font-size: 0.68rem; margin-top: 2px; }

            /* Di HP: tombol "Layar Penuh" disembunyikan (HP sudah layar penuh) */
            .kiosk-fullscreen-btn { display: none !important; }

            /* Di HP: "Keluar Mode Gerbang" jadi ikon saja, pojok kiri bawah, warna merah */
            .kiosk-exit-btn {
                padding: 0.5rem 0.7rem;
                gap: 0;
                color: #dc2626;
                border-color: #fecaca;
                background: #ffffff;
            }
            .kiosk-exit-btn .kiosk-action-text { display: none; }
            .kiosk-exit-btn i {
                font-size: 1.55rem;
                line-height: 1;
                color: #dc2626;
            }
            .kiosk-exit-btn:hover,
            .kiosk-exit-btn:focus {
                background: #fef2f2;
                color: #b91c1c;
                border-color: #fca5a5;
            }
        }

        @media (max-width: 480px) {
            :root { --gerbang-camera-size: 272px; }
            .kiosk-header { height: 182px; padding: 0.5rem 0.9rem; }
            .kiosk-header-inner { gap: 0.4rem; }
            .kiosk-brand { gap: 0.4rem; }
            .kiosk-brand-logo,
            .kiosk-brand-fallback { width: 46px; height: 46px; min-width: 46px; }
            .kiosk-brand-fallback { font-size: 1.4rem; }
            .kiosk-school-name { font-size: 1rem; }
            .kiosk-subtitle { font-size: 0.68rem; }
            .kiosk-clock { font-size: 1.55rem; }
            .kiosk-date { font-size: 0.64rem; }
            .kiosk-switcher { margin-bottom: 0.75rem; }
            .kiosk-switch-btn { padding: 0.42rem 0.75rem; font-size: 0.78rem; min-height: 38px; }
            .overlay-title { font-size: 0.92rem; }
            .overlay-sub { font-size: 0.72rem; }
            .kiosk-corner { width: 24px; height: 24px; }
        }
    </style>
</head>

<body>

    <!-- 1. HEADER: Mentok Kiri (Logo) & Mentok Kanan (Jam) -->
    <header class="kiosk-header">
        <div class="kiosk-header-inner">
            <div class="kiosk-brand">
                <img src="{{ asset(\App\Models\Setting::getLogo()) }}" alt="Logo Sekolah" class="kiosk-brand-logo" onerror="this.outerHTML='<i class=\'bx bxs-school kiosk-brand-fallback\'></i>'">
                <div class="kiosk-brand-text">
                    <p class="kiosk-school-name">{{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</p>
                    <div class="kiosk-subtitle">Absensi Gerbang</div>
                </div>
            </div>

            <div class="kiosk-clock-block">
                <div id="liveClock" class="kiosk-clock mono">00:00:00</div>
                <div id="liveDate" class="kiosk-date">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</div>
            </div>
        </div>
    </header>

    <!-- 2. KOTAK CARD KAMERA DI TENGAH -->
    <main class="kiosk-main">
        <div class="kiosk-card-box">

            <!-- Switcher Pill: Kamera | Alat Scanner -->
            <div class="kiosk-switcher" role="tablist" aria-label="Metode input presensi">
                <button type="button" id="btnTabCamera" class="kiosk-switch-btn active" onclick="switchMode('camera')" role="tab" aria-selected="true">
                    <i class='bx bx-camera'></i> Kamera
                </button>
                <button type="button" id="btnTabHardware" class="kiosk-switch-btn" onclick="switchMode('hardware')" role="tab" aria-selected="false">
                    <i class='bx bx-barcode-reader'></i> Alat Scanner
                </button>
            </div>

            <!-- Scanner Box: persegi 1:1 (ukuran diatur --gerbang-camera-size) -->
            <div id="scannerBox" class="scanner-viewport-container camera-active">

                <!-- Mode Kamera -->
                <div id="cameraView" class="w-100 h-100">
                    <div id="reader"></div>
                    <div id="cameraPlaceholder" class="kiosk-placeholder">
                        <i class='bx bx-qr-scan' style="font-size: 2.4rem; color: #94a3b8;"></i>
                        <p class="kiosk-placeholder-text">Tempelkan kartu QR ke kamera</p>
                    </div>
                    <!-- Bracket 4 sudut, sejajar & simetris dengan qrbox proporsional -->
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
                    <i class='bx bx-scan' style="font-size: 2.2rem; color: #2563eb;"></i>
                    <p class="kiosk-placeholder-text">Fokuskan alat di sini, lalu scan kartu siswa</p>
                    <div class="w-100 mt-2 px-1">
                        <input type="text" id="hardwareInput" class="form-control form-control-sm text-center fw-semibold font-monospace py-2" placeholder="Siap menerima scan..." autocomplete="off">
                    </div>
                </div>

                <!-- Overlay Hasil Scan Terpadu (5 Kombinasi Sesuai Acuan Mode Gerbang) -->
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

            <!-- Jam operasional, nilainya dari Pengaturan (bukan hardcode) -->
            <p class="kiosk-hours">
                Jam masuk <strong>{{ $checkInTime ?? '07:00' }}</strong>
                &middot; Batas terlambat <strong>{{ $lateLimitTime ?? '07:15' }}</strong>
                &middot; Jam pulang <strong>{{ $checkOutTime ?? '14:00' }}</strong>
            </p>
        </div>
    </main>

    <!-- 3. BAR BAWAH: Mentok Kiri & Mentok Kanan -->
    <div class="kiosk-bottom-bar">
        <a href="{{ panel_route('absensi.index') }}" class="btn-kiosk-action kiosk-exit-btn" title="Keluar Mode Gerbang" aria-label="Keluar Mode Gerbang">
            <i class='bx bx-log-out'></i>
            <span class="kiosk-action-text">Keluar Mode Gerbang</span>
        </a>
        <button type="button" class="btn-kiosk-action kiosk-fullscreen-btn" onclick="toggleFullscreen()" title="Buka Layar Penuh" aria-label="Layar Penuh">
            <i class='bx bx-fullscreen'></i>
            <span class="kiosk-action-text">Layar Penuh</span>
        </button>
    </div>

    <audio id="beepSound" src="{{ asset('audio/beep.mp3') }}" preload="auto"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="{{ asset('js/scanner.js') }}?v={{ file_exists(public_path('js/scanner.js')) ? filemtime(public_path('js/scanner.js')) : config('app.version', '1') }}" data-process-route="{{ panel_route('scanner.process') }}"></script>

</body>
</html>
