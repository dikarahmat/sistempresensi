<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mode Gerbang (Kiosk Presensi) | {{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</title>
    <link rel="icon" type="image/webp" href="{{ asset(\App\Models\Setting::getLogo()) }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & Boxicons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- HTML5 QR Code Scanner -->
    <script src="{{ asset('js/html5-qrcode.min.js') }}"></script>

    <style>
        :root {
            /* ================= TEMA CLEAN (TERANG) MODE GERBANG ================= */
            --kiosk-bg: #f8fafc;
            --kiosk-card: #ffffff;
            --kiosk-border: #e2e8f0;
            --kiosk-text: #0f172a;
            --kiosk-text-soft: #1e293b;
            --kiosk-muted: #64748b;
            --kiosk-primary: #2563eb;
            --kiosk-primary-soft: #eff4ff;
            --kiosk-success: #047857;
            --kiosk-success-soft: #d1fae5;
            --kiosk-warning: #b45309;
            --kiosk-warning-soft: #fef3c7;
            --kiosk-danger: #dc2626;
            --kiosk-danger-soft: #fee2e2;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--kiosk-bg);
            color: var(--kiosk-text);
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* ===== HEADER: logo di kiri, nama sekolah di kanan (sejajar, terpusat) ===== */
        .kiosk-header {
            background-color: var(--kiosk-card);
            border-bottom: 1px solid var(--kiosk-border);
            padding: 0.75rem 0 1.1rem;
        }

        .kiosk-header-inner {
            max-width: 880px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .kiosk-tools {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.5rem;
        }

        .kiosk-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 0.5rem;
            min-width: 0;
        }

        .kiosk-brand-logo,
        .kiosk-brand-fallback {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 14px;
            flex-shrink: 0;
        }

        .kiosk-brand-logo {
            object-fit: contain;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.16);
        }

        .kiosk-brand-fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--kiosk-primary-soft);
            color: var(--kiosk-primary);
            font-size: 1.4rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.10);
        }

        .kiosk-brand-text {
            min-width: 0;
            text-align: left;
        }

        /* Nama sekolah: boleh wrap 2 baris, tidak menggeser logo / tidak overflow 360px */
        .kiosk-school-name {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.015em;
            color: #0f172a;
            overflow-wrap: anywhere;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .kiosk-subtitle {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--kiosk-muted);
            margin-top: 2px;
        }

        /* ===== JAM DIGITAL BESAR + TANGGAL (di bawah header, terpusat) ===== */
        .kiosk-clock-block {
            text-align: center;
            margin-top: 0.9rem;
        }

        .kiosk-clock {
            font-size: 2.4rem;
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: -0.02em;
            color: #0f172a;
        }

        .kiosk-date {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--kiosk-muted);
            margin-top: 2px;
        }

        /* ===== TOMBOL OUTLINE (Keluar Mode Gerbang / Layar Penuh) ===== */
        .btn-kiosk-action {
            padding: 0.5rem 0.9rem;
            font-size: 0.82rem;
            font-weight: 600;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid var(--kiosk-border);
            background: var(--kiosk-card);
            color: var(--kiosk-text);
        }
        .btn-kiosk-action:hover {
            background: #f1f5f9;
            color: var(--kiosk-text);
            border-color: #cbd5e1;
        }

        /* ===== KARTU UTAMA (putih, radius 20px, shadow lembut) ===== */
        .kiosk-main {
            flex-grow: 1;
            width: 100%;
            max-width: 880px;
            margin: 0 auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .kiosk-card {
            background-color: var(--kiosk-card);
            border: 1px solid var(--kiosk-border);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            padding: 1.15rem;
        }

        /* ===== TOGGLE PILL: Kamera | Alat Scanner ===== */
        .kiosk-switch-wrap {
            text-align: center;
        }

        .kiosk-switch {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 4px;
            background: #f1f5f9;
            border: 1px solid var(--kiosk-border);
            border-radius: 50rem;
        }

        .kiosk-switch-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border: none;
            background: transparent;
            color: var(--kiosk-muted);
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.5rem 1.05rem;
            min-height: 40px;
            border-radius: 50rem;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .kiosk-switch-btn:hover {
            color: var(--kiosk-text-soft);
        }

        .kiosk-switch-btn.active {
            background: var(--kiosk-primary);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }

        /* ===== AREA KAMERA (latar hitam, radius 16px, border biru 2px) ===== */
        .kiosk-camera-box {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            background: #000000;
            border: 2px solid var(--kiosk-primary);
            border-radius: 16px;
            overflow: hidden;
        }

        /* #qr-reader mengisi 100% kotak kamera, sehingga #qr-shaded-region (inset 0)
           dan .kiosk-scan-frame memakai kotak yang sama persis */
        #qr-reader {
            width: 100% !important;
            height: 100% !important;
            border: none !important;
            background: #000000;
        }

        #qr-reader video {
            display: block;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 14px;
        }

        /* Perbaikan bug bawaan: canvas internal html5-qrcode tidak boleh tampil */
        #qr-reader canvas { display: none !important; }

        /* #qr-shaded-region & bracket: sumber posisi sama (absolute + inset 0 di #qr-reader) */
        #qr-shaded-region {
            position: absolute !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
            pointer-events: none !important;
        }

        .kiosk-scan-frame {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        .kiosk-scan-box {
            position: relative;
            width: 220px;
            height: 220px;
            max-width: 76%;
            max-height: 76%;
        }

        /* 4 sudut scan putih tebal 5px, sejajar & simetris terhadap qrbox 220x220 */
        .kiosk-corner {
            position: absolute;
            width: 30px;
            height: 30px;
            border: 5px solid #ffffff;
        }
        .kiosk-corner--tl { top: 0; left: 0; border-right: 0; border-bottom: 0; border-top-left-radius: 10px; }
        .kiosk-corner--tr { top: 0; right: 0; border-left: 0; border-bottom: 0; border-top-right-radius: 10px; }
        .kiosk-corner--bl { bottom: 0; left: 0; border-right: 0; border-top: 0; border-bottom-left-radius: 10px; }
        .kiosk-corner--br { bottom: 0; right: 0; border-left: 0; border-top: 0; border-bottom-right-radius: 10px; }

        /* Teks kecil di bawah video: kontras tinggi */
        .kiosk-camera-hint {
            margin: 0.7rem 0 0;
            text-align: center;
            font-size: 0.78rem;
            font-weight: 500;
            color: #475569;
        }

        /* ===== JAM OPERASIONAL (di bawah kamera, terpusat) ===== */
        .kiosk-hours {
            margin: 0.95rem 0 0;
            text-align: center;
            font-size: 0.82rem;
            color: var(--kiosk-muted);
        }

        .kiosk-hours strong {
            color: var(--kiosk-text);
            font-weight: 700;
        }

        /* ===== INPUT ALAT SCANNER USB ===== */
        .input-scanner-box {
            background-color: #ffffff !important;
            border: 1px solid var(--kiosk-border) !important;
            color: #0f172a !important;
            font-size: 1.1rem !important;
            font-weight: 600;
            letter-spacing: 0.04em;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        .input-scanner-box:focus {
            outline: none;
            border-color: var(--kiosk-primary) !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
        }
        .input-scanner-box::placeholder {
            color: #94a3b8 !important;
            font-weight: 500;
        }

        .kiosk-input-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--kiosk-muted);
            margin-bottom: 0.35rem;
        }

        .kiosk-input-icon {
            background: var(--kiosk-primary-soft) !important;
            border: 1px solid var(--kiosk-border) !important;
            color: var(--kiosk-primary) !important;
        }

        /* ===== DROPDOWN KAMERA: kontras teks agar terbaca ===== */
        .kiosk-select {
            background-color: #ffffff;
            color: #0f172a;
            border: 1px solid var(--kiosk-border);
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 500;
            padding-top: 0.55rem;
            padding-bottom: 0.55rem;
        }
        .kiosk-select:focus {
            border-color: var(--kiosk-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            color: #0f172a;
        }
        .kiosk-select option {
            color: #0f172a;
            background-color: #ffffff;
        }

        /* ===== TOOLBAR KAMERA (dropdown + tombol mulai) ===== */
        .kiosk-camera-toolbar {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .kiosk-start-btn {
            border-radius: 12px;
            font-size: 0.85rem;
            padding: 0.6rem 0.9rem;
            min-height: 42px;
        }

        .kiosk-link-btn {
            color: var(--kiosk-primary) !important;
            font-size: 0.8rem;
            font-weight: 600;
        }

        @media (min-width: 576px) {
            .kiosk-camera-toolbar {
                flex-direction: row;
                align-items: center;
            }
            .kiosk-camera-toolbar .kiosk-select {
                flex: 1 1 auto;
            }
            .kiosk-camera-toolbar .kiosk-start-btn {
                flex: 0 0 auto;
            }
        }

        .result-display-box {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        /* ===== KARTU HASIL SCAN (tema terang) ===== */
        .kiosk-section-title {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--kiosk-muted);
        }

        .kiosk-result-panel {
            background: #f8fafc;
            border: 1px solid var(--kiosk-border);
            border-radius: 16px;
        }

        .kiosk-avatar {
            width: 68px;
            height: 68px;
            min-width: 68px;
            border-radius: 50%;
            background: var(--kiosk-primary);
            color: #ffffff;
            font-weight: 700;
            font-size: 1.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .kiosk-result-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
        }

        .kiosk-result-meta {
            font-size: 0.8rem;
            color: var(--kiosk-muted);
        }
        .kiosk-result-meta strong {
            color: var(--kiosk-text-soft);
            font-weight: 700;
        }

        .kiosk-status-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.7rem;
            border-radius: 50rem;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .kiosk-status-pill--success { background: var(--kiosk-success-soft); color: var(--kiosk-success); }
        .kiosk-status-pill--warning { background: var(--kiosk-warning-soft); color: var(--kiosk-warning); }
        .kiosk-status-pill--primary { background: #dbeafe; color: #1d4ed8; }
        .kiosk-status-pill--danger  { background: var(--kiosk-danger-soft); color: var(--kiosk-danger); }

        .kiosk-detail-chip {
            display: inline-block;
            background: #ffffff;
            border: 1px solid var(--kiosk-border);
            border-radius: 8px;
            padding: 0.25rem 0.55rem;
            font-size: 0.76rem;
            color: var(--kiosk-text-soft);
        }

        /* ===== TABEL LOG PRESENSI (tema terang) ===== */
        .feed-table th {
            background-color: #f8fafc !important;
            color: var(--kiosk-muted) !important;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid var(--kiosk-border);
            padding: 0.7rem 0.85rem;
            white-space: nowrap;
        }
        .feed-table td {
            background-color: #ffffff !important;
            color: var(--kiosk-text-soft) !important;
            border-bottom: 1px solid #eef2f7;
            padding: 0.6rem 0.85rem;
            font-size: 0.82rem;
            vertical-align: middle;
        }
        .feed-table tbody tr:last-child td {
            border-bottom: none;
        }

        .feed-time { color: var(--kiosk-muted); font-size: 0.78rem; }
        .feed-name { color: #0f172a; font-weight: 600; }
        .feed-class { color: var(--kiosk-muted); font-size: 0.78rem; }

        .feed-badge {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        .feed-badge--success { background: var(--kiosk-success-soft); color: var(--kiosk-success); }
        .feed-badge--warning { background: var(--kiosk-warning-soft); color: var(--kiosk-warning); }
        .feed-badge--primary  { background: #dbeafe; color: #1d4ed8; }

        .kiosk-feed-scroll {
            max-height: 260px;
            overflow-y: auto;
        }

        /* ===== RESPONSIF: mobile-first, desktop tetap tema clean ===== */
        @media (max-width: 768px) {
            .kiosk-header { padding: 0.6rem 0 0.9rem; }
            .kiosk-clock { font-size: 1.9rem; }
            .kiosk-date { font-size: 0.75rem; }
            .kiosk-school-name { font-size: 0.98rem; }
            .kiosk-main { padding: 0.85rem 0.75rem 1.5rem; }
            .kiosk-card { padding: 0.9rem; border-radius: 18px; }
            .kiosk-switch-btn { padding: 0.45rem 0.85rem; font-size: 0.8rem; }
            .btn-kiosk-action { padding: 0.45rem 0.7rem; font-size: 0.78rem; }
            .kiosk-corner { width: 26px; height: 26px; }
        }
    </style>
</head>
<body>

    <!-- 1. HEADER KIOSK (logo + nama sekolah sejajar, jam digital, tombol aksi) -->
    <header class="kiosk-header">
        <div class="kiosk-header-inner">
            <!-- Tombol aksi: Layar Penuh & Keluar Mode Gerbang (outline) -->
            <div class="kiosk-tools">
                <button type="button" class="btn-kiosk-action" onclick="toggleFullscreen()" title="Buka Layar Penuh">
                    <i class='bx bx-fullscreen'></i>
                    <span class="d-none d-sm-inline">Layar Penuh</span>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn-kiosk-action" title="Keluar Mode Gerbang">
                    <i class='bx bx-log-out'></i>
                    <span class="d-none d-sm-inline">Keluar Mode Gerbang</span>
                </a>
            </div>

            <!-- Baris horizontal: [logo] [nama sekolah + subjudul] -->
            <div class="kiosk-brand">
                <img src="{{ asset(\App\Models\Setting::getLogo()) }}" alt="Logo Sekolah" class="kiosk-brand-logo" onerror="this.outerHTML='<i class=\'bx bxs-school kiosk-brand-fallback\'></i>'">
                <div class="kiosk-brand-text">
                    <p class="kiosk-school-name">{{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</p>
                    <div class="kiosk-subtitle">Absensi Gerbang</div>
                </div>
            </div>

            <!-- Jam digital besar + tanggal, terpusat di bawah header -->
            <div class="kiosk-clock-block">
                <div id="liveClock" class="kiosk-clock mono">00:00:00</div>
                <div id="liveDate" class="kiosk-date">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</div>
            </div>
        </div>
    </header>

    <!-- 2. KONTEN UTAMA KIOSK: kartu scan utama, hasil scan, dan log presensi -->
    <main class="kiosk-main">

        <!-- ===== KARTU UTAMA: metode input + area scan ===== -->
        <section class="kiosk-card">
            <div class="kiosk-switch-wrap">
                <div class="kiosk-switch" role="tablist" aria-label="Metode input presensi">
                    <button type="button" id="btnModeCamera" class="kiosk-switch-btn active" onclick="switchMode('camera')" role="tab" aria-selected="true">
                        <i class='bx bx-camera'></i> Kamera
                    </button>
                    <button type="button" id="btnModeHardware" class="kiosk-switch-btn" onclick="switchMode('hardware')" role="tab" aria-selected="false">
                        <i class='bx bx-barcode-reader'></i> Alat Scanner
                    </button>
                </div>
            </div>

            <!-- MODE A: KAMERA (QR) -->
            <div id="cameraModeContainer">
                <div class="kiosk-camera-toolbar">
                    <select id="cameraSourceSelect" class="form-select kiosk-select" aria-label="Pilih kamera">
                        <option value="">Mendeteksi perangkat kamera...</option>
                    </select>
                    <button type="button" id="btnToggleCamera" class="btn btn-primary fw-semibold kiosk-start-btn" onclick="toggleCameraScanning()">
                        <i class='bx bx-play-circle me-1'></i> Mulai Kamera
                    </button>
                </div>

                <div class="kiosk-camera-box">
                    <div id="qr-reader"></div>
                    <!-- Bracket 4 sudut: sumber posisi sama dengan #qr-shaded-region (absolute + inset 0) -->
                    <div class="kiosk-scan-frame" aria-hidden="true">
                        <div class="kiosk-scan-box">
                            <span class="kiosk-corner kiosk-corner--tl"></span>
                            <span class="kiosk-corner kiosk-corner--tr"></span>
                            <span class="kiosk-corner kiosk-corner--bl"></span>
                            <span class="kiosk-corner kiosk-corner--br"></span>
                        </div>
                    </div>
                </div>

                <p class="kiosk-camera-hint">Arahkan kartu QR siswa ke dalam kotak untuk memindai.</p>
            </div>

            <!-- MODE B: ALAT SCANNER USB -->
            <div id="hardwareModeContainer" class="d-none">
                <p class="kiosk-camera-hint mb-3">Arahkan alat scanner USB ke kartu siswa. Kolom input fokus otomatis.</p>

                <label class="kiosk-input-label" for="hardwareScanInput">Kotak Penerima Scan Barcode/QR</label>
                <div class="input-group">
                    <span class="input-group-text kiosk-input-icon"><i class='bx bx-barcode'></i></span>
                    <input type="text" id="hardwareScanInput" class="form-control input-scanner-box mono" placeholder="Siap menerima scan..." autofocus autocomplete="off">
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                    <span class="kiosk-camera-hint text-start m-0">Status Scanner: <span id="scannerStatusBadge" class="text-success fw-bold">Standby Siap</span></span>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 kiosk-link-btn" onclick="refocusInput()">
                        <i class='bx bx-target-lock me-1'></i> Fokuskan Ulang
                    </button>
                </div>
            </div>

            <!-- Jam operasional, nilainya dari pengaturan (bukan hardcode) -->
            <p class="kiosk-hours">
                Jam masuk <strong>{{ $checkInTime }}</strong> &middot; Jam pulang <strong>{{ $checkOutTime }}</strong>
            </p>
        </section>

        <!-- ===== KARTU HASIL SCAN TERKINI ===== -->
        <section class="kiosk-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-2 mb-3 border-bottom">
                <span class="kiosk-section-title">Hasil Presensi Terkini</span>
                <span id="scanTimestamp" class="small mono kiosk-camera-hint">Belum ada scan</span>
            </div>

            <div id="resultDisplayBox" class="result-display-box text-center">
                <!-- Default Idle State -->
                <div id="idleState" class="py-3">
                    <div class="kiosk-brand-fallback mx-auto mb-2"><i class='bx bx-scan'></i></div>
                    <h5 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.02rem;">Menunggu Kartu Presensi</h5>
                    <p class="kiosk-camera-hint mb-0">Silakan scan kartu pelajar siswa di depan alat pembaca.</p>
                </div>

                <!-- Active Scanned Card (dinamis via JS) -->
                <div id="scannedStudentCard" class="d-none w-100">
                    <div class="kiosk-result-panel d-flex flex-column flex-sm-row align-items-center gap-3 p-3 text-start">
                        <div id="resPhoto" class="kiosk-avatar">SW</div>
                        <div class="flex-grow-1 w-100">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <h4 class="kiosk-result-name mb-0" id="resName">Nama Siswa</h4>
                                <span id="resStatusBadge" class="kiosk-status-pill kiosk-status-pill--success">HADIR</span>
                            </div>
                            <div class="kiosk-result-meta d-flex align-items-center flex-wrap gap-2">
                                <span>NIS: <strong class="mono" id="resNis">12345</strong></span>
                                <span>Kelas: <strong id="resClass">7-A</strong></span>
                                <span>Pukul: <strong class="mono" id="resTime">07:05 WIB</strong></span>
                            </div>
                            <div class="mt-2">
                                <span class="kiosk-detail-chip" id="resDetailText">Presensi Masuk Berhasil</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== LOG PRESENSI MASUK & PULANG HARI INI ===== -->
        <section class="kiosk-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <span class="kiosk-section-title">Log Presensi Hari Ini</span>
                <span class="small mono kiosk-camera-hint">Live Feed</span>
            </div>

            <div class="table-responsive kiosk-feed-scroll">
                <table class="table feed-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 18%;">Waktu</th>
                            <th style="width: 44%;">Nama Siswa</th>
                            <th style="width: 20%;">Kelas</th>
                            <th style="width: 18%;" class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentFeedBody">
                        @forelse($recentScans as $att)
                        <tr>
                            <td class="mono feed-time">
                                {{ substr($att->check_out ?? $att->check_in ?? '-', 0, 5) }} WIB
                            </td>
                            <td class="feed-name">
                                {{ $att->student->name ?? '-' }}
                            </td>
                            <td class="feed-class">
                                {{ $att->student->schoolClass->name ?? '-' }}
                            </td>
                            <td class="text-center">
                                @if($att->check_out)
                                    <span class="feed-badge feed-badge--primary">Pulang</span>
                                @elseif($att->time_remark === 'Terlambat')
                                    <span class="feed-badge feed-badge--warning">Telat</span>
                                @else
                                    <span class="feed-badge feed-badge--success">Hadir</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr id="emptyFeedRow">
                            <td colspan="4" class="text-center py-4 kiosk-camera-hint">
                                Belum ada data scan presensi hari ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <audio id="beepSound" src="{{ asset('audio/beep.mp3') }}" preload="auto"></audio>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // 1. DIGITAL CLOCK REAL-TIME
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('liveClock').innerText = `${hours}:${minutes}:${seconds}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // 2. FULLSCREEN TOGGLE
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.error("Gagal fullscreen:", err);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        // 3. SOUND EFFECTS (audio/beep.mp3)
        function playBeep(success = true) {
            const beep = document.getElementById('beepSound');
            if (beep) {
                beep.currentTime = 0;
                beep.play().catch(() => {});
            }
        }

        // 4. HARDWARE SCANNER HANDLER (Autofocus & Enter Trigger)
        const scanInput = document.getElementById('hardwareScanInput');
        let isProcessingScan = false;

        function refocusInput() {
            if (scanInput) {
                scanInput.focus();
                const badge = document.getElementById('scannerStatusBadge');
                badge.innerText = "● Standby Siap";
                badge.className = "fw-bold";
                badge.style.color = "#047857";
            }
        }

        // Keep input focused automatically
        window.addEventListener('click', function(e) {
            if (document.getElementById('hardwareModeContainer').classList.contains('d-none')) return;
            // Refocus after 500ms if not clicking another input
            if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON' && e.target.tagName !== 'SELECT') {
                refocusInput();
            }
        });

        scanInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const code = this.value.trim();
                if (code && !isProcessingScan) {
                    processScanCode(code);
                    this.value = '';
                }
            }
        });

        // 5. PROCESS SCAN CODE (AJAX to /admin/scanner/process)
        function processScanCode(qrToken) {
            if (isProcessingScan) return;
            isProcessingScan = true;
            const statusBadge = document.getElementById('scannerStatusBadge');
            statusBadge.innerText = "⏳ Memproses...";
            statusBadge.className = "fw-bold";
            statusBadge.style.color = "#b45309";

            fetch(`{{ route('admin.scanner.process') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ qr_token: qrToken })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (status === 200 && body.success) {
                    playBeep(true);
                    renderResultSuccess(body);
                    addToFeed(body);
                } else {
                    playBeep(false);
                    renderResultError(body.message || 'QR Code tidak valid!', body.student);
                }
            })
            .catch(err => {
                playBeep(false);
                renderResultError('Terjadi kendala koneksi ke server.');
            })
            .finally(() => {
                setTimeout(() => {
                    isProcessingScan = false;
                    refocusInput();
                }, 1000);
            });
        }

        // Render Success Card
        function renderResultSuccess(data) {
            document.getElementById('idleState').classList.add('d-none');
            const card = document.getElementById('scannedStudentCard');
            card.classList.remove('d-none');

            document.getElementById('scanTimestamp').innerText = data.time || 'Baru saja';
            document.getElementById('resName').innerText = data.student.name;
            document.getElementById('resNis').innerText = data.student.nis;
            document.getElementById('resClass').innerText = data.student.class;
            document.getElementById('resTime').innerText = data.time_short + ' WIB';

            // Photo / Avatar
            const photoEl = document.getElementById('resPhoto');
            photoEl.style.background = '';
            if (data.student.photo) {
                photoEl.innerHTML = `<img src="${data.student.photo}" class="w-100 h-100 object-fit-cover" alt="Foto">`;
            } else {
                photoEl.innerText = data.student.name.substring(0, 2).toUpperCase();
            }

            // Status Badge
            const badge = document.getElementById('resStatusBadge');
            const detailText = document.getElementById('resDetailText');

            if (data.type === 'check_out') {
                badge.className = 'kiosk-status-pill kiosk-status-pill--primary';
                badge.innerText = 'PRESENSI PULANG';
                detailText.innerText = 'Siswa telah menyelesaikan kegiatan belajar mengajar hari ini.';
            } else if (data.is_late) {
                badge.className = 'kiosk-status-pill kiosk-status-pill--warning';
                badge.innerText = `TERLAMBAT (+${data.late_minutes}m)`;
                detailText.innerText = `Tercatat terlambat melewati batas pukul {{ $lateLimitTime }} WIB.`;
            } else {
                badge.className = 'kiosk-status-pill kiosk-status-pill--success';
                badge.innerText = 'HADIR TEPAT WAKTU';
                detailText.innerText = 'Presensi masuk berhasil dicatat tepat waktu.';
            }
        }

        // Render Error / Warning Card
        function renderResultError(message, student = null) {
            document.getElementById('idleState').classList.add('d-none');
            const card = document.getElementById('scannedStudentCard');
            card.classList.remove('d-none');

            document.getElementById('scanTimestamp').innerText = 'Peringatan';
            document.getElementById('resName').innerText = student ? student.name : 'Siswa Tidak Dikenali';
            document.getElementById('resNis').innerText = student ? student.nis : '-';
            document.getElementById('resClass').innerText = student ? student.class : '-';
            document.getElementById('resTime').innerText = '-';

            const photoEl = document.getElementById('resPhoto');
            photoEl.innerHTML = `<i class='bx bx-error-circle' style="font-size: 1.75rem; color: #dc2626;"></i>`;
            photoEl.style.background = '#fee2e2';

            const badge = document.getElementById('resStatusBadge');
            badge.className = 'kiosk-status-pill kiosk-status-pill--danger';
            badge.innerText = 'PERHATIAN';

            document.getElementById('resDetailText').innerText = message;
        }

        // Add dynamically to real-time feed
        function addToFeed(data) {
            const tbody = document.getElementById('recentFeedBody');
            const emptyRow = document.getElementById('emptyFeedRow');
            if (emptyRow) emptyRow.remove();

            const statusBadge = data.type === 'check_out' 
                ? `<span class="feed-badge feed-badge--primary">Pulang</span>`
                : (data.is_late 
                    ? `<span class="feed-badge feed-badge--warning">Telat</span>`
                    : `<span class="feed-badge feed-badge--success">Hadir</span>`);

            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td class="mono feed-time">${data.time_short} WIB</td>
                <td class="feed-name">${data.student.name}</td>
                <td class="feed-class">${data.student.class}</td>
                <td class="text-center">${statusBadge}</td>
            `;

            tbody.insertBefore(newRow, tbody.firstChild);

            // Limit feed rows to 12
            if (tbody.children.length > 12) {
                tbody.removeChild(tbody.lastChild);
            }
        }

        // 6. CAMERA SCANNER MODE (HTML5 QR CODE)
        let html5QrCode = null;
        let isCameraRunning = false;

        function switchMode(mode) {
            const btnHard = document.getElementById('btnModeHardware');
            const btnCam = document.getElementById('btnModeCamera');
            const contHard = document.getElementById('hardwareModeContainer');
            const contCam = document.getElementById('cameraModeContainer');

            if (mode === 'hardware') {
                btnHard.classList.add('active');
                btnCam.classList.remove('active');
                btnHard.setAttribute('aria-selected', 'true');
                btnCam.setAttribute('aria-selected', 'false');
                contHard.classList.remove('d-none');
                contCam.classList.add('d-none');
                stopCameraScanning();
                refocusInput();
                return Promise.resolve();
            }

            btnCam.classList.add('active');
            btnHard.classList.remove('active');
            btnCam.setAttribute('aria-selected', 'true');
            btnHard.setAttribute('aria-selected', 'false');
            contCam.classList.remove('d-none');
            contHard.classList.add('d-none');
            return initCameraDevices();
        }

        function initCameraDevices() {
            return Html5Qrcode.getCameras().then(devices => {
                const select = document.getElementById('cameraSourceSelect');
                const previousValue = select.value;
                select.innerHTML = '';
                if (devices && devices.length) {
                    devices.forEach((cam, index) => {
                        const opt = document.createElement('option');
                        opt.value = cam.id;
                        opt.text = cam.label || `Kamera ${index + 1}`;
                        select.appendChild(opt);
                    });
                    // Pertahankan kamera yang sebelumnya dipilih bila masih tersedia
                    if (previousValue && devices.some(cam => cam.id === previousValue)) {
                        select.value = previousValue;
                    }
                } else {
                    select.innerHTML = '<option value="">Tidak ada kamera terdeteksi</option>';
                }
            }).catch(err => {
                console.error("Kamera error:", err);
            });
        }

        // Kamera yang harus dipakai: pilihan dropdown > kamera belakang > device pertama
        function preferredCameraId(devices) {
            const select = document.getElementById('cameraSourceSelect');
            const selectedId = select ? select.value : '';
            if (selectedId && devices.some(cam => cam.id === selectedId)) {
                return selectedId;
            }
            const backCamera = devices.find(cam => {
                const label = (cam.label || '').toLowerCase();
                return label.includes('back') || label.includes('belakang') ||
                       label.includes('rear') || label.includes('environment');
            });
            return (backCamera || devices[0]).id;
        }

        function toggleCameraScanning() {
            if (isCameraRunning) {
                stopCameraScanning();
            } else {
                startCameraScanning();
            }
        }

        function startCameraScanning() {
            if (typeof Html5Qrcode === 'undefined') {
                console.error("Html5Qrcode library not loaded");
                return;
            }

            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("qr-reader");
            }

            // Step 1: Request camera permission first (prompts user if needed)
            navigator.mediaDevices.getUserMedia({ video: true }).then(stream => {
                // Stop the test stream immediately
                stream.getTracks().forEach(track => track.stop());

                // Step 2: Enumerate available cameras (works now that permission is granted)
                return Html5Qrcode.getCameras();
            }).then(devices => {
                if (!devices || devices.length === 0) {
                    throw new Error('No camera found');
                }

                // Step 3: Start camera memakai device yang dipilih di dropdown
                return html5QrCode.start(
                    preferredCameraId(devices),
                    { fps: 10, qrbox: { width: 220, height: 220 } },
                    (decodedText) => {
                        processScanCode(decodedText);
                    },
                    (errorMessage) => {
                        // scanning loop frame error (silent)
                    }
                );
            }).then(() => {
                isCameraRunning = true;
                const btn = document.getElementById('btnToggleCamera');
                btn.className = "btn btn-danger fw-semibold kiosk-start-btn";
                btn.innerHTML = "<i class='bx bx-stop-circle me-1'></i> Hentikan Kamera";
            }).catch(err => {
                console.error("Gagal start kamera:", err);
                alert("Kamera tidak dapat diakses. Pastikan izin kamera diberikan di browser.");
            });
        }

        function stopCameraScanning() {
            if (!html5QrCode || !isCameraRunning) {
                return Promise.resolve();
            }
            return html5QrCode.stop().then(() => {
                isCameraRunning = false;
                const btn = document.getElementById('btnToggleCamera');
                if (btn) {
                    btn.className = "btn btn-primary fw-semibold kiosk-start-btn";
                    btn.innerHTML = "<i class='bx bx-play-circle me-1'></i> Mulai Kamera";
                }
            }).catch(err => {
                console.error(err);
                isCameraRunning = false;
            });
        }

        // Ganti kamera terpilih -> jalankan ulang kamera dengan device tersebut
        document.getElementById('cameraSourceSelect').addEventListener('change', function() {
            if (!isCameraRunning) return;
            stopCameraScanning().then(() => startCameraScanning());
        });

        // Auto-start kamera saat halaman dimuat (Mode Gerbang)
        document.addEventListener('DOMContentLoaded', function() {
            switchMode('camera')
                .then(() => startCameraScanning())
                .catch(() => startCameraScanning());
        });
    </script>
</body>
</html>
