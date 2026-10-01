<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Presensi Gerbang | {{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</title>
    <link rel="icon" type="image/webp" href="{{ asset(\App\Models\Setting::getLogo()) }}">

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

        /* ===== AREA UTAMA (Kamera di bawah papan tulisan) ===== */
        .kiosk-main {
            flex-grow: 1;
            min-height: 0;
            width: 100%;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            /* Padding top 48% supaya kamera naik sedikit tapi tetap di bawah papan tulisan */
            padding: 48vh 1rem 8vh;
        }

        .kiosk-card-box {
            /* Tanpa card, tanpa padding, tanpa shadow */
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* ===== TOMBOL POJOK: Murni ikon, tanpa background/border ===== */
        .kiosk-corner-btn {
            position: fixed;
            z-index: 1050;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            outline: none;
            box-shadow: none;
            color: #ffffff;
            font-size: 1.2rem;
            cursor: pointer;
            transition: opacity 0.2s ease;
            opacity: 0.5;
            text-decoration: none;
            -webkit-tap-highlight-color: transparent;
        }
        .kiosk-corner-btn:hover {
            opacity: 1;
        }
        .kiosk-corner-btn:focus-visible {
            outline: 2px solid rgba(255, 255, 255, 0.6);
            outline-offset: 2px;
            border-radius: 4px;
        }
        .kiosk-corner-btn i {
            filter: drop-shadow(0 1px 4px rgba(0, 0, 0, 0.45));
        }
        .kiosk-exit-corner {
            top: auto;
            bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
            left: calc(1rem + env(safe-area-inset-left, 0px));
        }
        .kiosk-fullscreen-corner {
            top: auto;
            bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
            right: calc(1rem + env(safe-area-inset-right, 0px));
        }

        /* ===== JAM DIGITAL: kanan atas, tanpa background, text-shadow ===== */
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
            font-size: 1.875rem;
            font-weight: 600;
            line-height: 1.2;
            letter-spacing: 0.02em;
            font-variant-numeric: tabular-nums;
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.45);
        }

        .kiosk-clock-date {
            font-family: 'Poppins', 'Roboto', system-ui, sans-serif;
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.3;
            margin-top: 2px;
            opacity: 0.9;
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.45);
        }

        /* ===== HEADER KHUSUS MOBILE (logo + nama sekolah) =====
           Default display:none: TIDAK tampil & TIDAK mempengaruhi layout desktop.
           Hanya diaktifkan di dalam @media (max-width: 767px) di bawah. */
        .kiosk-mobile-header {
            display: none;
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

        /* ===== SWITCHER PILL: kecil & transparan di bawah tengah ===== */
        .kiosk-switcher {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 4px;
            background: rgba(0, 0, 0, 0.3);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50rem;
            margin-top: 1rem;
            opacity: 0.6;
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
            font-weight: 600;
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
            background: rgba(37, 99, 235, 0.6);
            color: #ffffff;
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
            background: rgba(0, 0, 0, 0.7);
            border-radius: var(--gerbang-camera-radius);
        }

        .kiosk-placeholder-text {
            margin: 0.5rem 0 0;
            font-size: 0.76rem;
            font-weight: 600;
            line-height: 1.35;
            color: #ffffff;
        }

        /* ===== MODE ALAT SCANNER USB (minimalis, tanpa container) ===== */
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

        /* ===== MOBILE (<= 768px): Responsif ===== */
        @media (max-width: 768px) {
            :root { --gerbang-camera-size: 300px; }
            body::before {
                background-position: center 32%;
            }
        }

        @media (max-width: 480px) {
            :root { --gerbang-camera-size: 272px; }
            .overlay-title { font-size: 0.92rem; }
            .overlay-sub { font-size: 0.72rem; }
            .kiosk-corner { width: 24px; height: 24px; }
        }

        /* ==========================================================================
           MOBILE KHUSUS (max-width: 767px)
           Latar putih bersih, header (logo + nama + jam + tanggal) di atas,
           kamera + toggle lock center di sisa ruang. SEMUA aturan di bawah ini
           hanya berlaku di layar mobile — desktop (>= 768px) TIDAK terpengaruh.
           ========================================================================== */
        @media (max-width: 767px) {
            /* 1. Latar putih bersih: foto sekolah & overlay hitam dimatikan */
            html { background-color: #ffffff; }
            body {
                min-height: 100vh;
                min-height: 100dvh;
                background-color: #ffffff;
                background-image: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            }
            body::before { display: none; } /* foto sekolah (desktop tetap tampil) */
            body::after { display: none; }   /* overlay hitam tipis (desktop tetap tampil) */

            /* 2. Header: logo + nama sekolah + subjudul, rata tengah, tanpa kartu/border
                  (posisi atas diturunkan 1cm dari tepi atas layar) */
            .kiosk-mobile-header {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: max(calc(1.1rem + 1cm), env(safe-area-inset-top, 0px)) 1rem 0;
                flex: 0 0 auto;
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

            /* 3. Jam & tanggal: mengalir rata tengah di bawah header (bukan fixed
                  kanan-atas), warna gelap & tanpa text-shadow di atas latar putih.
                  Elemen jam/tanggal yang sudah ada (liveClock & liveDate) dipakai
                  ulang oleh kode jam yang sama — TANPA duplikasi id. */
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

            /* 4. Area kamera: ambil sisa ruang, center penuh (lock center, flex —
                  bukan absolut/px hardcode), posisi stabil saat overlay muncul */
            .kiosk-main {
                flex: 1 1 auto;
                align-items: center;
                justify-content: center;
                padding: 0.35rem 1rem calc(4rem + env(safe-area-inset-bottom, 0px));
            }

            /* 5. Kotak kamera: persegi min(86vw, 360px) — video tetap cover penuh.
                  Batas dvh menjaga: header yang turun 2cm + toggle + ikon bawah
                  selalu muat dalam tinggi layar (tanpa scroll) di HP pendek. */
            :root {
                --gerbang-camera-size: min(86vw, 360px, calc(100vh - 400px));
                --gerbang-camera-size: min(86vw, 360px, calc(100dvh - 400px));
            }

            /* Toggle menempel di atas kotak kamera (tanpa kartu putih), tetap jelas */
            .kiosk-switcher {
                margin-top: 0.5rem;
                opacity: 1;
            }

            /* 6. Ikon keluar & layar penuh: icon-only abu gelap, tap area 44px */
            .kiosk-corner-btn {
                width: 44px;
                height: 44px;
                color: #475569;
                opacity: 1;
            }
            .kiosk-corner-btn:hover { opacity: 1; }
            .kiosk-corner-btn:focus-visible {
                outline: 2px solid rgba(71, 85, 105, 0.6);
            }
            .kiosk-corner-btn i {
                filter: none;
                font-size: 1.35rem;
            }

            /* 7. Overlay hasil scan (5 kombinasi): TIDAK ADA aturan .scan-result-*
                  di view ini. SATU SUMBER di presensi-tokens.css (REVISI 2) —
                  nilai tetap px, sama untuk Gerbang & panel Scanner QR. */
        }

        /* ==========================================================================
           LAYAR PENDEK: (max-width: 767px) + (tinggi < 640px / landscape HP)
           Header lebih rapat, logo & jam dikecilkan, kamera dibatasi dvh supaya
           UTUH terlihat tanpa scroll — posisi tetap center.
           ========================================================================== */
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
            .kiosk-switcher { margin-top: 0.375rem; }
            :root {
                /* Batas dvh: header rapat + toggle + padding muat dalam tinggi layar */
                --gerbang-camera-size: min(86vw, 360px, calc(100vh - 250px));
                --gerbang-camera-size: min(86vw, 360px, calc(100dvh - 250px));
            }
            .kiosk-placeholder { padding: 0.5rem; }
            .kiosk-placeholder i { font-size: 1.7rem !important; }
            .kiosk-placeholder-text { font-size: 0.7rem; }
            /* Overlay hasil scan: SATU SUMBER di presensi-tokens.css (REVISI 2).
               Aturan @media layar pendek (max-height 639px) TIDAK diulang
               di sini — sudah ada di file token dengan nilai tetap px. */
        }
    </style>
</head>

<body>

    <!-- Tombol Keluar Mode Gerbang: ikon kecil di pojok kiri atas -->
    <a href="{{ panel_route('absensi.index') }}" class="kiosk-corner-btn kiosk-exit-corner" title="Keluar Mode Gerbang" aria-label="Keluar Mode Gerbang">
        <i class='bx bx-log-out'></i>
    </a>

    <!-- Tombol Layar Penuh: ikon kecil di pojok kanan atas -->
    <button type="button" class="kiosk-corner-btn kiosk-fullscreen-corner" onclick="toggleFullscreen()" title="Buka Layar Penuh" aria-label="Layar Penuh">
        <i class='bx bx-fullscreen'></i>
    </button>

    <!-- Header khusus MOBILE (<=767px): logo + nama sekolah, rata tengah.
         Default display:none sehingga tampilan desktop tidak berubah sama sekali. -->
    <div class="kiosk-mobile-header">
        <img class="kiosk-mobile-logo" src="{{ asset(\App\Models\Setting::getLogo()) }}" alt="Logo Sekolah">
        <div class="kiosk-mobile-name">{{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</div>
        <div class="kiosk-mobile-sub">Presensi Gerbang</div>
    </div>

    <!-- Jam Digital: tengah atas, pill glass tipis -->
    <div class="kiosk-clock" aria-label="Jam digital">
        <div class="kiosk-clock-time" id="liveClock">00:00:00</div>
        <div class="kiosk-clock-date" id="liveDate">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, j F Y') }}</div>
    </div>

    <!-- KAMERA DI TENGAH (langsung di atas foto, tanpa card) -->
    <main class="kiosk-main">
        <div class="kiosk-card-box">

            <!-- Switcher Pill: kecil & transparan di bawah tengah -->
            <div class="kiosk-switcher" role="tablist" aria-label="Metode input presensi">
                <button type="button" id="btnTabCamera" class="kiosk-switch-btn active" onclick="switchMode('camera')" role="tab" aria-selected="true">
                    <i class='bx bx-camera'></i>
                </button>
                <button type="button" id="btnTabHardware" class="kiosk-switch-btn" onclick="switchMode('hardware')" role="tab" aria-selected="false">
                    <i class='bx bx-barcode-reader'></i>
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

                <!-- Overlay Hasil Scan Terpadu (5 Kombinasi: Lingkaran Hijau/Oranye/Merah + Ikon Centang/X/Tanda Seru) -->
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
                
                // Update tanggal dalam bahasa Indonesia
                if (dateEl) {
                    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' };
                    dateEl.innerText = now.toLocaleDateString('id-ID', options);
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

    <script src="{{ asset('js/scanner.js') }}?v={{ file_exists(public_path('js/scanner.js')) ? filemtime(public_path('js/scanner.js')) : config('app.version', '1') }}" data-process-route="{{ panel_route('scanner.process') }}"></script>
</body>
</html>
