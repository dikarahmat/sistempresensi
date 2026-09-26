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
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        :root {
            --bg-kiosk: #0f172a;
            --card-kiosk: #1e293b;
            --border-kiosk: #334155;
            --accent-green: #10b981;
            --accent-blue: #3b82f6;
            --accent-amber: #f59e0b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-kiosk);
            color: #f8fafc;
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .kiosk-header {
            background-color: #0b1329;
            border-bottom: 1px solid var(--border-kiosk);
            padding: 0.85rem 1.5rem;
        }

        .clock-large {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .btn-kiosk-action {
            padding: 0.45rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid var(--border-kiosk);
            background: #1e293b;
            color: #e2e8f0;
        }
        .btn-kiosk-action:hover {
            background: #334155;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .kiosk-card {
            background-color: var(--card-kiosk);
            border: 1px solid var(--border-kiosk);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        }

        /* ============================================================
           OPTIMASI MOBILE (< 768px): CLEAN LOOK & TOUCH TARGET AMAN
           ============================================================ */
        @media (max-width: 767.98px) {
            .kiosk-header {
                padding: 0.65rem 1rem;
            }
            .clock-large {
                font-size: 1.5rem;
            }
            main.container-fluid {
                padding: 0.75rem 1rem !important; /* Grid native 16px */
            }
            .row.g-4 {
                --bs-gutter-x: 1rem;
                --bs-gutter-y: 1rem;
            }
            .kiosk-card {
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
            }
            .kiosk-card.p-4 {
                padding: 0.875rem !important;
            }
            .btn-kiosk-action {
                padding: 0.5rem 0.7rem;
                min-height: 40px;
                min-width: 40px;
                font-size: 0.8rem;
            }
            .tab-mode-btn {
                padding: 0.55rem 0.85rem;
                min-height: 40px;
                font-size: 0.8rem;
            }
        }

        .input-scanner-box {
            background-color: #0b1329 !important;
            border: 2px solid #3b82f6 !important;
            color: #38bdf8 !important;
            font-size: 1.25rem !important;
            font-weight: 600;
            letter-spacing: 0.05em;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            transition: all 0.2s ease;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.2);
        }
        .input-scanner-box:focus {
            outline: none;
            border-color: #60a5fa !important;
            box-shadow: 0 0 25px rgba(59, 130, 246, 0.4);
        }
        .input-scanner-box::placeholder {
            color: #64748b !important;
            font-weight: 500;
        }

        .beacon-pulse {
            width: 10px;
            height: 10px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 1.8s infinite;
        }

        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .result-display-box {
            min-height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .tab-mode-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.15s ease;
        }
        .tab-mode-btn.active {
            background: #3b82f6;
            color: #ffffff;
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.3);
        }

        .feed-table th {
            background-color: #0b1329 !important;
            color: #94a3b8 !important;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-kiosk);
            padding: 0.75rem 1rem;
        }
        .feed-table td {
            background-color: var(--card-kiosk) !important;
            color: #f1f5f9 !important;
            border-bottom: 1px solid #293548;
            padding: 0.65rem 1rem;
            font-size: 0.84rem;
            vertical-align: middle;
        }
    </style>
</head>
<body>

    <!-- 1. TOP BAR KIOSK (Brand, Operational Schedule, Big Real-Time Clock) -->
    <header class="kiosk-header">
        <div class="container-fluid px-2 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <!-- Left: Identitas Pos Gerbang -->
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset(\App\Models\Setting::getLogo()) }}" onerror="this.outerHTML='<div class=\'rounded-3 bg-primary text-white fw-bold d-flex align-items-center justify-content-center\' style=\'width:42px;height:42px;\'>SMP</div>'" class="rounded-3" style="width: 42px; height: 42px; object-fit: contain;">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold mb-0 text-white" style="letter-spacing: -0.01em;">POS GERBANG • MODE KIOSK</h5>
                        <span class="badge bg-emerald-950 text-emerald-400 border border-emerald-800 rounded-pill px-2.5 py-1 small d-inline-flex align-items-center gap-1.5" style="font-size: 0.72rem;">
                            <span class="beacon-pulse"></span> SIAP SCAN
                        </span>
                    </div>
                    <div class="text-secondary small mt-0.5">{{ $schoolName ?? \App\Models\Setting::getSchoolName() }} • Pos Satpam</div>
                </div>
            </div>

            <!-- Center: Jadwal Operasional Sekolah -->
            <div class="d-none d-lg-flex align-items-center gap-2.5 px-3 py-1.5 rounded-3 border" style="background-color: #0b1329; border-color: var(--border-kiosk) !important;">
                <div class="d-flex align-items-center gap-2">
                    <i class='bx bx-alarm text-primary fs-5'></i>
                    <div class="small">
                        <span class="text-secondary d-block" style="font-size: 0.68rem; text-transform: uppercase;">Jam Masuk</span>
                        <strong class="text-white mono">{{ $checkInTime }} - {{ $lateLimitTime }}</strong>
                    </div>
                </div>
                <div class="text-secondary opacity-30">|</div>
                <div class="d-flex align-items-center gap-2">
                    <i class='bx bx-door-open text-warning fs-5'></i>
                    <div class="small">
                        <span class="text-secondary d-block" style="font-size: 0.68rem; text-transform: uppercase;">Jam Pulang</span>
                        <strong class="text-white mono">{{ $checkOutTime }} WIB</strong>
                    </div>
                </div>
            </div>

            <!-- Right: Jam Digital Besar & Tombol Aksi -->
            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <div id="liveClock" class="clock-large mono">00:00:00</div>
                    <div id="liveDate" class="text-secondary small mt-0.5">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn-kiosk-action" onclick="toggleFullscreen()" title="Buka Layar Penuh">
                        <i class='bx bx-fullscreen fs-5'></i>
                        <span class="d-none d-sm-inline">Layar Penuh</span>
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="btn-kiosk-action border-danger text-danger" title="Keluar dari Mode Kiosk">
                        <i class='bx bx-log-out fs-5'></i>
                        <span class="d-none d-sm-inline">Keluar</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- 2. KONTEN UTAMA KIOSK (DUA KOLOM: SCANNER & HASIL / FEED) -->
    <main class="container-fluid flex-grow-1 p-3 p-lg-4">
        <div class="row g-4 h-100">
            <!-- Kolom Kiri (Input Scanner & Mode Switcher) -->
            <div class="col-12 col-xl-6 d-flex flex-column gap-3">
                <div class="kiosk-card p-4 flex-grow-1 d-flex flex-column justify-content-between">
                    <div>
                        <!-- Header Switcher Mode -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pb-3 mb-3 border-bottom border-secondary border-opacity-25">
                            <span class="text-uppercase small fw-bold text-secondary" style="letter-spacing: 0.05em;">Metode Input Presensi</span>
                            <div class="p-1 rounded-3 d-inline-flex gap-1" style="background: #0b1329;">
                                <button type="button" id="btnModeHardware" class="tab-mode-btn active" onclick="switchMode('hardware')">
                                    <i class='bx bx-barcode-reader me-1'></i> Alat Scanner (USB)
                                </button>
                                <button type="button" id="btnModeCamera" class="tab-mode-btn" onclick="switchMode('camera')">
                                    <i class='bx bx-camera me-1'></i> Kamera (QR)
                                </button>
                            </div>
                        </div>

                        <!-- 1. MODE A: HARDWARE SCANNER BOX -->
                        <div id="hardwareModeContainer" class="py-2">
                            <div class="alert alert-info border-0 rounded-3 py-2 px-3 small mb-3 text-info-emphasis d-flex align-items-center gap-2" style="background-color: #0c2340;">
                                <i class='bx bx-info-circle fs-5 flex-shrink-0 text-info'></i>
                                <span>Arahkan barcode scanner / QR reader USB ke kartu siswa. Kolom input otomatis fokus tanpa perlu diklik.</span>
                            </div>

                            <div class="mb-3 position-relative">
                                <label class="form-label small fw-semibold text-secondary mb-1.5">Kotak Penerima Scan Barcode/QR</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-slate-900 border-primary text-primary"><i class='bx bx-barcode fs-3'></i></span>
                                    <input type="text" id="hardwareScanInput" class="form-control input-scanner-box mono" placeholder="Siap menerima scan..." autofocus autocomplete="off">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center px-1">
                                <span class="small text-secondary">
                                    Status Scanner: <span id="scannerStatusBadge" class="text-success fw-bold">● Standby Siap</span>
                                </span>
                                <button type="button" class="btn btn-sm btn-link text-decoration-none text-primary p-0" onclick="refocusInput()">
                                    <i class='bx bx-target-lock me-1'></i> Fokuskan Ulang
                                </button>
                            </div>
                        </div>

                        <!-- 2. MODE B: KAMERA QR CODE SCANNER -->
                        <div id="cameraModeContainer" class="d-none py-2">
                            <div class="row g-2 mb-2">
                                <div class="col-12 col-md-8">
                                    <select id="cameraSourceSelect" class="form-select bg-slate-900 text-light border-secondary small rounded-3">
                                        <option value="">Mendeteksi perangkat kamera...</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <button type="button" id="btnToggleCamera" class="btn btn-primary btn-sm w-100 fw-semibold rounded-3 py-2" onclick="toggleCameraScanning()">
                                        <i class='bx bx-play-circle me-1'></i> Mulai Kamera
                                    </button>
                                </div>
                            </div>

                            <!-- Area Render Kamera -->
                            <div class="rounded-3 overflow-hidden border border-secondary border-opacity-50 position-relative bg-black" style="min-height: 260px; max-height: 320px;">
                                <div id="qr-reader" style="width: 100%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Indikator Tips Satpam -->
                    <div class="pt-3 mt-3 border-top border-secondary border-opacity-25 small text-secondary d-flex align-items-center justify-content-end">
                        <div class="mono text-muted" style="font-size: 0.72rem;">OnHadir Enterprise v2.0</div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan (Hasil Presensi Real-Time & Riwayat Scan) -->
            <div class="col-12 col-xl-6 d-flex flex-column gap-3">
                <!-- 1. KOTAK KARTU HASIL PEMINDAIAN TERAKHIR -->
                <div class="kiosk-card p-4">
                    <div class="d-flex justify-content-between align-items-center pb-2.5 mb-3 border-bottom border-secondary border-opacity-25">
                        <span class="text-uppercase small fw-bold text-secondary" style="letter-spacing: 0.05em;">Hasil Presensi Terkini</span>
                        <span id="scanTimestamp" class="small mono text-muted">Belum ada scan</span>
                    </div>

                    <div id="resultDisplayBox" class="result-display-box text-center p-3">
                        <!-- Default Idle State -->
                        <div id="idleState" class="py-4">
                            <div class="rounded-circle d-inline-flex p-3 mb-2" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                <i class='bx bx-scan fs-1'></i>
                            </div>
                            <h5 class="fw-bold text-secondary mb-1">Menunggu Kartu Presensi</h5>
                            <p class="text-muted small mb-0">Silakan scan kartu pelajar siswa di depan alat pembaca.</p>
                        </div>

                        <!-- Active Scanned Card (Dinamis via JS) -->
                        <div id="scannedStudentCard" class="d-none w-100">
                            <div class="d-flex flex-column flex-sm-row align-items-center gap-3 p-3 rounded-3 border border-slate-700 text-start" style="background-color: #0b1329;">
                                <div id="resPhoto" class="rounded-circle border border-primary d-flex align-items-center justify-content-center text-white fw-bold overflow-hidden flex-shrink-0" style="width: 76px; height: 76px; font-size: 1.6rem; background: #1e3a8a;">
                                    SW
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-1">
                                        <h4 class="fw-bold text-white mb-0" id="resName">Nama Siswa</h4>
                                        <span id="resStatusBadge" class="badge bg-success fs-6 px-3 py-1.5 rounded-pill fw-bold">HADIR</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 text-secondary small">
                                        <span>NIS: <strong class="text-light mono" id="resNis">12345</strong></span>
                                        <span>•</span>
                                        <span>Kelas: <strong class="text-light" id="resClass">7-A</strong></span>
                                        <span>•</span>
                                        <span>Pukul: <strong class="text-light mono" id="resTime">07:05 WIB</strong></span>
                                    </div>
                                    <div class="mt-2" id="resMessageWrap">
                                        <span class="badge bg-slate-800 text-slate-300 border border-slate-700 py-1 px-2 small" id="resDetailText">Presensi Masuk Berhasil</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. TABEL RIWAYAT 10 SCAN TERAKHIR -->
                <div class="kiosk-card p-3 flex-grow-1 overflow-hidden d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <span class="text-uppercase small fw-bold text-secondary" style="letter-spacing: 0.05em;">Log Presensi Masuk & Pulang Hari Ini</span>
                        <span class="small text-muted mono">Live Feed</span>
                    </div>

                    <div class="table-responsive flex-grow-1" style="max-height: 240px; overflow-y: auto;">
                        <table class="table feed-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Waktu</th>
                                    <th style="width: 45%;">Nama Siswa</th>
                                    <th style="width: 20%;">Kelas</th>
                                    <th style="width: 20%;" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="recentFeedBody">
                                @forelse($recentScans as $att)
                                <tr>
                                    <td class="mono small text-secondary">
                                        {{ substr($att->check_out ?? $att->check_in ?? '-', 0, 5) }} WIB
                                    </td>
                                    <td class="fw-semibold text-white">
                                        {{ $att->student->name ?? '-' }}
                                    </td>
                                    <td class="text-secondary small">
                                        {{ $att->student->schoolClass->name ?? '-' }}
                                    </td>
                                    <td class="text-center">
                                        @if($att->check_out)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded small">Pulang</span>
                                        @elseif($att->time_remark === 'Terlambat')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5 rounded small">Telat</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded small">Hadir</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr id="emptyFeedRow">
                                    <td colspan="4" class="text-center py-4 text-muted small">
                                        Belum ada data scan presensi hari ini.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

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

        // 3. SYNTHESIZED SOUND EFFECTS (Web Audio API - No External Assets Needed)
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

        function playBeep(success = true) {
            try {
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);

                if (success) {
                    // Two-tone high chime (beep-beep!)
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
                    osc.frequency.setValueAtTime(1174.66, audioCtx.currentTime + 0.08); // D6
                    gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.25);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.25);
                } else {
                    // Low warning buzz
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(220, audioCtx.currentTime);
                    gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.35);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.35);
                }
            } catch (e) {
                console.warn("Audio not initialized:", e);
            }
        }

        // 4. HARDWARE SCANNER HANDLER (Autofocus & Enter Trigger)
        const scanInput = document.getElementById('hardwareScanInput');
        let isProcessingScan = false;

        function refocusInput() {
            if (scanInput) {
                scanInput.focus();
                document.getElementById('scannerStatusBadge').innerText = "● Standby Siap";
                document.getElementById('scannerStatusBadge').className = "text-success fw-bold";
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
            document.getElementById('scannerStatusBadge').innerText = "⏳ Memproses...";
            document.getElementById('scannerStatusBadge').className = "text-warning fw-bold";

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
            if (data.student.photo) {
                photoEl.innerHTML = `<img src="${data.student.photo}" class="w-100 h-100 object-cover" alt="Foto">`;
            } else {
                photoEl.innerText = data.student.name.substring(0, 2).toUpperCase();
            }

            // Status Badge
            const badge = document.getElementById('resStatusBadge');
            const detailText = document.getElementById('resDetailText');

            if (data.type === 'check_out') {
                badge.className = 'badge bg-primary fs-6 px-3 py-1.5 rounded-pill fw-bold';
                badge.innerText = 'PRESENSI PULANG';
                detailText.innerText = 'Siswa telah menyelesaikan kegiatan belajar mengajar hari ini.';
            } else if (data.is_late) {
                badge.className = 'badge bg-warning text-dark fs-6 px-3 py-1.5 rounded-pill fw-bold';
                badge.innerText = `TERLAMBAT (+${data.late_minutes}m)`;
                detailText.innerText = `Tercatat terlambat melewati batas pukul {{ $lateLimitTime }} WIB.`;
            } else {
                badge.className = 'badge bg-success fs-6 px-3 py-1.5 rounded-pill fw-bold';
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
            photoEl.innerHTML = `<i class='bx bx-error fs-1 text-danger'></i>`;
            photoEl.style.background = '#450a0a';

            const badge = document.getElementById('resStatusBadge');
            badge.className = 'badge bg-danger fs-6 px-3 py-1.5 rounded-pill fw-bold';
            badge.innerText = 'PERHATIAN';

            document.getElementById('resDetailText').innerText = message;
        }

        // Add dynamically to real-time feed
        function addToFeed(data) {
            const tbody = document.getElementById('recentFeedBody');
            const emptyRow = document.getElementById('emptyFeedRow');
            if (emptyRow) emptyRow.remove();

            const statusBadge = data.type === 'check_out' 
                ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded small">Pulang</span>`
                : (data.is_late 
                    ? `<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5 rounded small">Telat</span>`
                    : `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded small">Hadir</span>`);

            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td class="mono small text-secondary">${data.time_short} WIB</td>
                <td class="fw-semibold text-white">${data.student.name}</td>
                <td class="text-secondary small">${data.student.class}</td>
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
                contHard.classList.remove('d-none');
                contCam.classList.add('d-none');
                stopCameraScanning();
                refocusInput();
            } else {
                btnCam.classList.add('active');
                btnHard.classList.remove('active');
                contCam.classList.remove('d-none');
                contHard.classList.add('d-none');
                initCameraDevices();
            }
        }

        function initCameraDevices() {
            Html5Qrcode.getCameras().then(devices => {
                const select = document.getElementById('cameraSourceSelect');
                select.innerHTML = '';
                if (devices && devices.length) {
                    devices.forEach((cam, index) => {
                        const opt = document.createElement('option');
                        opt.value = cam.id;
                        opt.text = cam.label || `Kamera ${index + 1}`;
                        select.appendChild(opt);
                    });
                } else {
                    select.innerHTML = '<option value="">Tidak ada kamera terdeteksi</option>';
                }
            }).catch(err => {
                console.error("Kamera error:", err);
            });
        }

        function toggleCameraScanning() {
            if (isCameraRunning) {
                stopCameraScanning();
            } else {
                startCameraScanning();
            }
        }

        function startCameraScanning() {
            const cameraId = document.getElementById('cameraSourceSelect').value;
            if (!cameraId) {
                Swal.fire({ icon: 'warning', title: 'Pilih Kamera', text: 'Perangkat kamera belum terdeteksi.' });
                return;
            }

            html5QrCode = new Html5Qrcode("qr-reader");
            html5QrCode.start(
                cameraId,
                { fps: 10, qrbox: { width: 220, height: 220 } },
                (decodedText) => {
                    processScanCode(decodedText);
                },
                (errorMessage) => {
                    // scanning loop frame error (silent)
                }
            ).then(() => {
                isCameraRunning = true;
                const btn = document.getElementById('btnToggleCamera');
                btn.className = "btn btn-danger btn-sm w-100 fw-semibold rounded-3 py-2";
                btn.innerHTML = "<i class='bx bx-stop-circle me-1'></i> Hentikan Kamera";
            }).catch(err => {
                console.error("Gagal start kamera:", err);
            });
        }

        function stopCameraScanning() {
            if (html5QrCode && isCameraRunning) {
                html5QrCode.stop().then(() => {
                    isCameraRunning = false;
                    const btn = document.getElementById('btnToggleCamera');
                    if (btn) {
                        btn.className = "btn btn-primary btn-sm w-100 fw-semibold rounded-3 py-2";
                        btn.innerHTML = "<i class='bx bx-play-circle me-1'></i> Mulai Kamera";
                    }
                }).catch(err => console.error(err));
            }
        }
    </script>
</body>
</html>
