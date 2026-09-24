<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Presensi Gerbang | {{ \App\Models\Setting::getSchoolName() }}</title>

    <!-- Global Favicon Dinamis -->
    <link rel="icon" type="image/webp" href="{{ asset(\App\Models\Setting::getLogo()) }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Boxicons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #ffffff;
            color: #0f172a;
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-x: hidden;
        }

        /* Top Bar Minimalis (Desktop Default) */
        .kiosk-topbar {
            padding: 1.25rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kiosk-brand-group {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .kiosk-title-block {
            text-align: left;
        }

        .kiosk-clock-group {
            text-align: right;
        }

        /* Center Content Card Style */
        .kiosk-center-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .kiosk-card-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            width: 100%;
            max-width: 480px;
            padding: 24px;
            text-align: center;
            position: relative;
        }

        /* Switcher Pill di Atas Card */
        .kiosk-switcher {
            display: inline-flex;
            background: #f8fafc;
            padding: 4px;
            border-radius: 50rem;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }

        .kiosk-switch-btn {
            background: transparent;
            border: none;
            padding: 0.45rem 1.2rem;
            border-radius: 50rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.15s ease;
        }

        .kiosk-switch-btn.active {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }

        /* Viewport / Area Scanner (Posisi Tetap Stabil) */
        .scanner-viewport-container {
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            background: #fafbfd;
            padding: 24px;
            min-height: 240px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .scanner-viewport-container.camera-active {
            border: 2px solid #2563eb;
            background: #000000;
            padding: 0;
        }

        #reader {
            width: 100% !important;
            height: 100% !important;
            border: none !important;
        }

        #reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover;
            border-radius: 14px;
        }

        /* Overlay Notifikasi */
        .overlay-status {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 50;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.2rem;
            text-align: center;
            backdrop-filter: blur(6px);
            animation: fadeInScale 0.15s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        /* Sukses: Gradient Emerald */
        .overlay-success {
            background: linear-gradient(135deg, rgba(13, 148, 136, 0.96) 0%, rgba(16, 185, 129, 0.96) 100%);
            color: #ffffff;
            box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.2);
            border-radius: 14px;
        }

        /* Gagal/Error: Danger Banget */
        .overlay-error {
            background: #dc2626 !important;
            color: #ffffff !important;
            box-shadow: 0 0 25px rgba(220, 38, 38, 0.5);
            border-radius: 14px;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Bottom Footer Nav */
        .kiosk-footer {
            padding: 1.25rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-kiosk-nav {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #475569;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-kiosk-nav:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }

        /* Khusus Tampilan Mobile (Logo & Jam Proporsional di Atas, Kotak Kamera Menggunakan -6.5rem) */
        @media (max-width: 639.98px) {
            .kiosk-topbar {
                padding: 1.5rem 1rem 0.4rem 1rem !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                gap: 0.5rem !important;
            }

            .kiosk-brand-group {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                gap: 0.4rem !important;
                margin-top: 0 !important;
            }

            /* Logo Diperbesar & Murni Tanpa Background / Shadow */
            .kiosk-logo {
                width: 72px !important;
                height: 72px !important;
                margin: 0 auto !important;
                background: transparent !important;
                background-color: transparent !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                filter: none !important;
                object-fit: contain !important;
            }

            .kiosk-logo-fallback {
                width: 72px !important;
                height: 72px !important;
                margin: 0 auto !important;
                box-shadow: none !important;
                border-radius: 14px !important;
            }

            .kiosk-title-block {
                text-align: center !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
            }

            /* Teks Utama Diperbesar & Tegas */
            .kiosk-school-name {
                font-size: 1.35rem !important;
                font-weight: 800 !important;
                text-align: center !important;
                margin: 0 !important;
                line-height: 1.2 !important;
                letter-spacing: -0.02em !important;
                color: #0f172a !important;
            }

            .kiosk-subtext {
                font-size: 0.85rem !important;
                font-weight: 500 !important;
                color: #64748b !important;
                text-align: center !important;
                margin-top: 2px !important;
            }

            /* Jam Digital & Tanggal */
            .kiosk-clock-group {
                text-align: center !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                margin-top: 0.2rem !important;
                gap: 1px !important;
            }

            #realtimeClock {
                font-size: 1.55rem !important;
                font-weight: 700 !important;
                letter-spacing: 0.02em !important;
                text-align: center !important;
                line-height: 1.1 !important;
                color: #1e293b !important;
            }

            .kiosk-date-text {
                font-size: 0.78rem !important;
                color: #64748b !important;
                text-align: center !important;
                margin-top: 2px !important;
            }

            /* Wrapper Center */
            .kiosk-center-wrapper {
                flex-grow: 1 !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 0 1rem 0.5rem 1rem !important;
                width: 100% !important;
            }

            /* KOTAK CARD KAMERA DENGAN MARGIN-TOP -6.5rem */
            .kiosk-card-box {
                max-width: 440px !important;
                width: 100% !important;
                padding: 1.15rem 1rem !important;
                margin-top: -6.5rem !important;
                margin-bottom: 0 !important;
                margin-left: auto !important;
                margin-right: auto !important;
                border-radius: 16px !important;
            }

            .kiosk-switcher {
                margin: 0 auto 0.9rem auto !important;
            }

            .kiosk-switch-btn {
                padding: 0.35rem 0.9rem !important;
                font-size: 0.82rem !important;
            }

            .scanner-viewport-container {
                min-height: 220px !important;
                margin: 0 auto 0.75rem auto !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
            }

            /* Footer Nav Khusus Mobile: Posisi Mutlak Menempel di Pojok Kiri Bawah Layar */
            .kiosk-footer {
                position: fixed !important;
                bottom: 0.75rem !important;
                left: 0.75rem !important;
                z-index: 1050 !important;
                padding: 0 !important;
                margin: 0 !important;
                width: auto !important;
                background: transparent !important;
                border: none !important;
                display: flex !important;
                align-items: center !important;
            }

            .btn-kiosk-fullscreen {
                display: none !important;
            }

            /* Murni Ikon Pintu Keluar Saja di Sudut Kiri Bawah Layar */
            .btn-kiosk-exit {
                width: auto !important;
                height: auto !important;
                min-width: unset !important;
                padding: 6px !important;
                margin: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                background: transparent !important;
                background-color: transparent !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                text-decoration: none !important;
                transition: opacity 0.15s ease, transform 0.15s ease !important;
            }

            .btn-kiosk-exit i {
                font-size: 1.85rem !important;
                line-height: 1 !important;
                color: #dc2626 !important;
                display: block !important;
            }

            .btn-kiosk-exit:hover i {
                color: #b91c1c !important;
            }

            .btn-kiosk-exit:active {
                opacity: 0.7 !important;
                transform: scale(0.92) !important;
            }

            .btn-kiosk-exit-text {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- 1. TOP BAR DENGAN LOGO SEKOLAH -->
    <header class="kiosk-topbar">
        <div class="kiosk-brand-group d-flex align-items-center gap-3">
            <img src="{{ asset(\App\Models\Setting::getLogo()) }}" alt="Logo" class="kiosk-logo rounded-3 shadow-2xs" onerror="this.outerHTML='<div class=\'rounded-3 bg-primary text-white fw-bold d-flex align-items-center justify-content-center shadow-2xs kiosk-logo-fallback\' style=\'width:44px;height:44px;\'>SMP</div>'" style="width: 44px; height: 44px; object-fit: contain;">
            <div class="kiosk-title-block">
                <h4 class="kiosk-school-name fw-bold mb-0" style="letter-spacing: -0.02em; color: #0f172a; font-size: 1.2rem;">{{ \App\Models\Setting::getSchoolName() }}</h4>
                <span class="kiosk-subtext text-secondary small">Presensi Gerbang @if(isset($schoolClass)) · Kelas {{ $schoolClass->name }} @endif</span>
            </div>
        </div>
        <div class="kiosk-clock-group text-end">
            <div id="realtimeClock" class="fw-bold font-monospace" style="font-size: 1.8rem; line-height: 1.1; color: #0f172a;">00:00:00</div>
            <div class="kiosk-date-text text-secondary small mt-0.5" style="font-size: 0.8rem;">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</div>
        </div>
    </header>

    <!-- 2. CENTER CONTENT -->
    <main class="kiosk-center-wrapper">
        <div class="kiosk-card-box">
            
            <!-- Switcher Button (Kamera / Alat Scanner) -->
            <div class="kiosk-switcher">
                <button type="button" id="btnTabCamera" class="kiosk-switch-btn active" onclick="switchMode('camera')">
                    <i class='bx bx-camera fs-5'></i> Kamera
                </button>
                <button type="button" id="btnTabHardware" class="kiosk-switch-btn" onclick="switchMode('hardware')">
                    <i class='bx bx-barcode-reader fs-5'></i> Alat Scanner
                </button>
            </div>

            <!-- Scanner Box Area -->
            <div id="scannerBox" class="scanner-viewport-container mb-3 camera-active">
                
                <!-- Mode Kamera -->
                <div id="cameraView" class="w-100 h-100">
                    <div id="reader" style="width: 100%; min-height: 220px; border-radius: 12px; overflow: hidden;"></div>
                    <div id="cameraPlaceholder" class="py-4 position-absolute top-50 start-50 translate-middle w-100" style="background: #fafbfd; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 5;">
                        <i class='bx bx-qr-scan text-secondary' style="font-size: 3rem;"></i>
                        <p class="text-secondary small mt-2 mb-0 fw-semibold">Tempelkan kartu QR ke kamera</p>
                    </div>
                </div>

                <!-- Mode Alat Scanner -->
                <div id="hardwareView" class="d-none w-100 py-3">
                    <i class='bx bx-scan text-primary' style="font-size: 3rem;"></i>
                    <p class="text-dark fw-semibold small mt-2 mb-3">Arahkan fokus tetap di sini, lalu scan kartu dengan alat</p>
                    <div class="px-4">
                        <input type="text" id="hardwareInput" class="form-control form-control-sm text-center fw-semibold font-monospace py-2" placeholder="Siap menerima scan..." autofocus autocomplete="off">
                    </div>
                </div>

                <!-- Overlay Sukses -->
                <div id="overlaySuccess" class="overlay-status overlay-success d-none">
                    <div class="rounded-circle bg-white bg-opacity-25 p-2 mb-2 d-inline-flex">
                        <i class='bx bx-check fs-2 text-white'></i>
                    </div>
                    <h6 class="fw-bold text-white mb-0 px-2" id="successText" style="font-size: 1.05rem; letter-spacing: -0.01em;">Nama Siswa — Hadir jam 07:00</h6>
                </div>

                <!-- Overlay Gagal -->
                <div id="overlayError" class="overlay-status overlay-error d-none">
                    <div class="rounded-circle bg-white bg-opacity-25 p-2 mb-2 d-inline-flex">
                        <i class='bx bx-error-circle fs-2 text-white'></i>
                    </div>
                    <h6 class="fw-bold text-white mb-1" style="font-size: 1.05rem;">Peringatan Presensi</h6>
                    <p class="text-white small mb-0 fw-medium px-2" id="errorText" style="font-size: 0.82rem;">Siswa sudah presensi hari ini.</p>
                </div>

            </div>

            <!-- Jam Operasional di Bawah Kotak -->
            <div class="text-secondary" style="font-size: 0.78rem;">
                Jam masuk <span class="fw-semibold text-dark">{{ $checkInTime ?? '07:00' }}</span> · Jam pulang <span class="fw-semibold text-dark">{{ $checkOutTime ?? '14:00' }}</span>
            </div>

        </div>
    </main>

    <!-- 3. BOTTOM FOOTER NAV -->
    <footer class="kiosk-footer">
        <a href="{{ route('guru.absensi.index') }}" class="btn-kiosk-nav btn-kiosk-exit" title="Keluar Mode Gerbang" aria-label="Keluar Mode Gerbang">
            <i class='bx bx-log-out fs-5'></i> <span class="btn-kiosk-exit-text">Keluar Mode Gerbang</span>
        </a>
        <button type="button" class="btn-kiosk-nav btn-kiosk-fullscreen" onclick="toggleFullscreen()">
            <i class='bx bx-fullscreen fs-5'></i> Layar Penuh
        </button>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        // Realtime Clock
        function updateClock() {
            const now = new Date();
            document.getElementById('realtimeClock').innerText = now.toTimeString().split(' ')[0];
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Fullscreen Toggle
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => console.error(err));
            } else if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }

        // Web Audio API Generator Suara Beep Browser
        let audioCtx = null;

        function playBrowserBeep(success = true) {
            try {
                if (!audioCtx) {
                    audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }

                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();

                if (success) {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(2500, audioCtx.currentTime);
                    gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.08);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.08);
                } else {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(150, audioCtx.currentTime);
                    osc.frequency.setValueAtTime(300, audioCtx.currentTime + 0.1);
                    gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.3);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.3);
                }
            } catch (e) {
                console.warn('Audio error:', e);
            }
        }

        let isProcessing = false;
        let resetTimer = null;

        function processCode(token) {
            const cleanToken = (token || '').trim();
            if (!cleanToken || isProcessing) return;
            isProcessing = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch("{{ route('guru.scanner.process') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({ qr_token: cleanToken })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (status === 200 && body.success) {
                    playBrowserBeep(true);
                    showOverlaySuccess(body);
                } else {
                    playBrowserBeep(false);
                    showOverlayError(body.message || 'QR Code tidak valid.');
                }
            })
            .catch(err => {
                console.error(err);
                playBrowserBeep(false);
                showOverlayError('Terjadi kendala koneksi ke server.');
            });
        }

        // Tampilkan Overlay Sukses Modern Emerald
        function showOverlaySuccess(data) {
            const overlaySucc = document.getElementById('overlaySuccess');
            const successText = document.getElementById('successText');

            const studentName = data.student.name;
            const statusLabel = data.type === 'check_out' ? 'Pulang' : (data.remark || 'Hadir');
            const now = new Date();
            const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

            successText.innerText = `${studentName} — ${statusLabel} jam ${timeStr}`;
            overlaySucc.classList.remove('d-none');

            resetOverlayState(1000);
        }

        // Tampilkan Overlay Gagal
        function showOverlayError(errorMsg) {
            const overlayErr = document.getElementById('overlayError');
            const errorText = document.getElementById('errorText');

            errorText.innerText = errorMsg;
            overlayErr.classList.remove('d-none');

            resetOverlayState(1000);
        }

        // Reset Overlay kembali normal setelah 1 detik
        function resetOverlayState(delay = 1000) {
            if (resetTimer) clearTimeout(resetTimer);
            resetTimer = setTimeout(() => {
                document.getElementById('overlaySuccess').classList.add('d-none');
                document.getElementById('overlayError').classList.add('d-none');
                
                isProcessing = false;

                const isHardwareActive = document.getElementById('btnTabHardware').classList.contains('active');
                if (isHardwareActive) {
                    const inputEl = document.getElementById('hardwareInput');
                    if (inputEl) inputEl.focus();
                }
            }, delay);
        }

        // Switcher Mode Kamera / Hardware
        let html5QrKiosk = null;
        let isCamRunning = false;

        function switchMode(mode) {
            if (isProcessing) return;
            const btnCam = document.getElementById('btnTabCamera');
            const btnHard = document.getElementById('btnTabHardware');
            const viewCam = document.getElementById('cameraView');
            const viewHard = document.getElementById('hardwareView');
            const boxArea = document.getElementById('scannerBox');

            if (mode === 'camera') {
                btnCam.classList.add('active');
                btnHard.classList.remove('active');
                viewCam.classList.remove('d-none');
                viewHard.classList.add('d-none');
                boxArea.classList.add('camera-active');
                startCameraKiosk();
            } else {
                btnHard.classList.add('active');
                btnCam.classList.remove('active');
                viewHard.classList.remove('d-none');
                viewCam.classList.add('d-none');
                boxArea.classList.remove('camera-active');
                stopCameraKiosk();

                const inputEl = document.getElementById('hardwareInput');
                if (inputEl) inputEl.focus();
            }
        }

        // Hardware Input Listener
        const hardInput = document.getElementById('hardwareInput');
        if (hardInput) {
            hardInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    processCode(this.value);
                    this.value = '';
                }
            });
        }

        // Keep focus on hardware input if active
        window.addEventListener('click', function(e) {
            if (!document.getElementById('hardwareView').classList.contains('d-none') && !isProcessing) {
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON') {
                    hardInput.focus();
                }
            }
        });

        // Start Camera
        function startCameraKiosk() {
            if (isCamRunning || typeof Html5Qrcode === 'undefined') return;
            
            const placeholder = document.getElementById('cameraPlaceholder');
            if (placeholder) placeholder.style.display = 'none';
            
            html5QrKiosk = new Html5Qrcode("reader");

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    html5QrKiosk.start(
                        devices[0].id,
                        { fps: 10, qrbox: { width: 180, height: 180 } },
                        (decodedText) => {
                            processCode(decodedText);
                        },
                        () => {}
                    ).then(() => { isCamRunning = true; });
                }
            }).catch(err => console.error(err));
        }

        function stopCameraKiosk() {
            if (isCamRunning && html5QrKiosk) {
                html5QrKiosk.stop().then(() => {
                    isCamRunning = false;
                    html5QrKiosk.clear();
                    const placeholder = document.getElementById('cameraPlaceholder');
                    if (placeholder) placeholder.style.display = 'flex';
                }).catch(err => console.error(err));
            }
        }

        // Default start camera langsung on load
        document.addEventListener("DOMContentLoaded", function() {
            switchMode('camera');
        });
    </script>
</body>
</html>
