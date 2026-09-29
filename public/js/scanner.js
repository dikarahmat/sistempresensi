/* ==========================================================================
   JS KAMERA GERBANG (sama persis untuk /admin/scanner & /admin/absensi/kiosk)
   Pilihan kamera otomatis:
     - Android / iOS / iPad -> kamera BELAKANG (facingMode: environment)
     - Laptop / PC          -> kamera DEPAN    (facingMode: user)

   Mutex + antrean serial untuk mencegah race condition.
   FIX: Html5Qrcode.start() menerima { facingMode } atau deviceId (string),
        BUKAN { video: {...} }. Instance dibuat ulang bila start gagal.
   ========================================================================== */

// Rasio sisi qrbox terhadap sisi viewfinder (harus sama dgn --gerbang-scan-ratio)
const GERBANG_SCAN_RATIO = 0.7;

// Instance scanner disimpan di window agar tidak ter-proxy oleh Livewire/Alpine
if (!window.__kioskScanner) {
    window.__kioskScanner = {
        instance: null,
        state: 'idle', // 'idle' | 'starting' | 'running' | 'stopping'
        queue: Promise.resolve(),
        isProcessingScan: false,
        resetTimer: null,
        lastCameraError: null,
        initialized: false
    };
}

const scanner = window.__kioskScanner;

/* ================= ANTREAN SERIAL (MUTEX) ================= */
function runCamera(task) {
    scanner.queue = scanner.queue.then(task, task).catch(e => {
        console.error('[kiosk-camera] Queue error:', e);
    });
    return scanner.queue;
}

/* ================= NORMALISASI ERROR ================= */
function normalizeCameraError(err, tahap) {
    const name = err?.name || (typeof err === 'string' ? 'LibraryError' : err?.constructor?.name || 'UnknownError');
    const message = err?.message || String(err);
    console.error('[kiosk-camera]', tahap, name, message, err);
    return { name, message };
}

/* ================= SUARA BEEP (audio/beep.mp3) ================= */
function playBeep() {
    const beep = document.getElementById('beepSound');
    if (beep) {
        beep.currentTime = 0;
        beep.play().catch(() => {});
    }
}

function playBeepSound() { playBeep(); }

/* ================= DETEKSI PERANGKAT ================= */
function isIOSDevice() {
    const ua = navigator.userAgent || '';
    return /iPad|iPhone|iPod/.test(ua) ||
           (/Macintosh/i.test(ua) && (navigator.maxTouchPoints || 0) > 1);
}

function isAndroidDevice() {
    return /Android/i.test(navigator.userAgent || '');
}

function isMobileDevice() {
    if (navigator.userAgentData && navigator.userAgentData.mobile === true) {
        return true;
    }
    const ua = navigator.userAgent || '';
    if (isIOSDevice() || isAndroidDevice() ||
        /IEMobile|Opera Mini|Windows Phone|Mobi/i.test(ua)) {
        return true;
    }
    if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches &&
        navigator.maxTouchPoints > 1) {
        return true;
    }
    return false;
}

function isBackCameraLabel(label) {
    const l = (label || '').toLowerCase();
    return l.includes('back') || l.includes('belakang') ||
           l.includes('rear') || l.includes('environment');
}

function isFrontCameraLabel(label) {
    const l = (label || '').toLowerCase();
    return l.includes('front') || l.includes('depan') ||
           l.includes('user') || l.includes('facing');
}

/* ================= HARDENING VIDEO UNTUK iOS / SAFARI ================= */
function fixVideoFrame() {
    const reader = document.getElementById('reader');
    if (!reader) return;
    const video = reader.querySelector('video');
    if (!video) return;

    video.setAttribute('playsinline', '');
    video.setAttribute('webkit-playsinline', '');
    video.muted = true;

    video.style.width = '100%';
    video.style.height = '100%';
    video.style.minWidth = '100%';
    video.style.minHeight = '100%';
    video.style.objectFit = 'cover';

    if (!video.__frameFixed) {
        video.__frameFixed = true;
        ['loadedmetadata', 'resize', 'playing'].forEach(function(ev) {
            video.addEventListener(ev, fixVideoFrame);
        });
    }
}

/* ================= PROSES SCAN (endpoint gerbang) ================= */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return (meta && meta.getAttribute('content')) || '';
}

function processCode(token) {
    const cleanToken = (token || '').trim();
    if (!cleanToken || scanner.isProcessingScan) return;
    scanner.isProcessingScan = true;

    const csrfToken = getCsrfToken();
    const scannerScript = document.querySelector('script[src*="scanner.js"]');
    const route = scannerScript ? scannerScript.getAttribute('data-process-route') : null;

    if (!route) {
        console.error('Scanner process route not defined');
        scanner.isProcessingScan = false;
        return;
    }

    fetch(route, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ qr_token: cleanToken })
    })
    .then(res => res.json().then(data => ({ status: res.status, body: data })))
    .then(({ status, body }) => {
        if (status === 200 && body.success) {
            playBeep();
            showOverlaySuccess(body);
        } else {
            playBeep();
            showOverlayError(body.message || 'QR Code tidak valid!', body.student);
        }
    })
    .catch(() => {
        playBeep();
        showOverlayError('Terjadi kendala koneksi ke server.');
    });
}

function processScanCode(qrToken) { processCode(qrToken); }

function refocusHardwareInput() {
    const view = document.getElementById('hardwareView');
    const input = document.getElementById('hardwareInput');
    if (view && input && !view.classList.contains('d-none')) {
        input.focus();
    }
}

/* ================= OVERLAY HASIL SCAN (ANIMASI) ================= */
function showOverlaySuccess(data) {
    const overlay = document.getElementById('overlaySuccess');
    const textEl = document.getElementById('successText');
    const metaEl = document.getElementById('successMeta');
    if (!overlay || !textEl || !metaEl) return;

    const nama = (data.student && data.student.name) || '-';
    const waktu = data.time_short ? data.time_short + ' WIB' : '';

    let status = 'Hadir';
    if (data.type === 'check_out') {
        status = 'Presensi Pulang';
    } else if (data.is_late) {
        status = 'Terlambat +' + data.late_minutes + ' mnt';
    } else if (data.display_remark) {
        status = data.display_remark;
    }

    textEl.innerText = nama;
    metaEl.innerText = status + (waktu ? ' · ' + waktu : '');

    overlay.classList.remove('d-none', 'error');
    void overlay.offsetWidth;
    overlay.classList.add('d-none');
    void overlay.offsetWidth;
    overlay.classList.remove('d-none');

    resetOverlayState(2200);
}

function showOverlayError(message, student) {
    const overlay = document.getElementById('overlayError');
    const titleEl = document.getElementById('errorTitle');
    const textEl = document.getElementById('errorText');
    if (!overlay || !titleEl || !textEl) return;

    const adaSiswa = student && student.name;
    titleEl.innerText = adaSiswa ? 'Presensi Gagal' : 'Kartu Tidak Dikenali';
    textEl.innerText = message || 'Scan gagal, coba lagi';

    overlay.classList.remove('d-none', 'error');
    void overlay.offsetWidth;
    overlay.classList.add('d-none');
    void overlay.offsetWidth;
    overlay.classList.remove('d-none');
    overlay.classList.add('error');

    resetOverlayState(2200);
}

function resetOverlayState(delay = 2200) {
    if (scanner.resetTimer) clearTimeout(scanner.resetTimer);
    scanner.resetTimer = setTimeout(() => {
        const ok = document.getElementById('overlaySuccess');
        const err = document.getElementById('overlayError');
        if (ok) ok.classList.add('d-none');
        if (err) {
            err.classList.add('d-none');
            err.classList.remove('error');
        }
        scanner.isProcessingScan = false;
        refocusHardwareInput();
    }, delay);
}

/* ================= PLACEHOLDER KAMERA ================= */
function showCameraPlaceholder(html) {
    const placeholder = document.getElementById('cameraPlaceholder');
    if (!placeholder) return;
    placeholder.innerHTML = html;
    placeholder.style.display = 'flex';
}

function resetCameraPlaceholder() {
    showCameraPlaceholder(
        `<i class='bx bx-qr-scan' style="font-size: 2.4rem; color: #94a3b8;"></i>
         <p class="kiosk-placeholder-text">Tempelkan kartu QR ke kamera</p>`
    );
}

function showCameraError(msg) {
    showCameraPlaceholder(
        `<i class='bx bx-camera-off' style="font-size: 2.2rem; color: #dc2626;"></i>
         <p class="kiosk-placeholder-text" style="color:#dc2626;">${msg}</p>
         <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="retryCamera()">
             <i class='bx bx-refresh'></i> Coba Lagi
         </button>`
    );
}

/* ================= SAFE STOP ================= */
async function safeStop() {
    if (!scanner.instance) {
        scanner.state = 'idle';
        return;
    }

    try {
        const state = scanner.instance.getState();
        if (state === 2 || state === 3) {
            await scanner.instance.stop();
        }
    } catch (e) {
        console.error('[kiosk-camera] safeStop error:', e);
    }

    try {
        await scanner.instance.clear();
    } catch (e) {
        console.error('[kiosk-camera] clear error:', e);
    }

    document.querySelectorAll('#reader video').forEach(v => {
        if (v.srcObject) {
            v.srcObject.getTracks().forEach(t => t.stop());
            v.srcObject = null;
        }
    });

    scanner.state = 'idle';
}

/* ================= START KAMERA ================= */
async function startCameraKiosk() {
    console.log('[kiosk-camera] startCameraKiosk called, state:', scanner.state);

    if (scanner.state === 'starting' || scanner.state === 'running') {
        console.log('[kiosk-camera] Already starting/running, skipping');
        return;
    }

    const readerEl = document.getElementById('reader');
    if (!readerEl) {
        console.error('[kiosk-camera] Container #reader tidak ditemukan di DOM');
        showCameraError("Kamera gagal dibuka (kode: ContainerNotFound).");
        return;
    }

    const readerStyle = window.getComputedStyle(readerEl);
    if (readerStyle.display === 'none' || readerStyle.visibility === 'hidden') {
        console.error('[kiosk-camera] Container #reader masih hidden');
        showCameraError("Kamera gagal dibuka (kode: ContainerHidden).");
        return;
    }

    scanner.state = 'starting';
    scanner.lastCameraError = null;

    const placeholder = document.getElementById('cameraPlaceholder');
    if (placeholder) placeholder.style.display = 'none';

    if (!scanner.instance) {
        scanner.instance = new Html5Qrcode("reader");
    }

    const config = {
        fps: 10,
        aspectRatio: 1.0,
        qrbox: (viewfinderWidth, viewfinderHeight) => {
            const s = Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * GERBANG_SCAN_RATIO);
            return { width: s, height: s };
        }
    };
    const onSuccess = (decodedText) => processCode(decodedText);
    const onError = () => {};

    // Buat ulang instance bersih (instance yang gagal start bisa nyangkut di state "transition")
    async function resetInstance() {
        try { if (scanner.instance) await scanner.instance.clear(); } catch (e) {}
        scanner.instance = null;
        readerEl.innerHTML = '';
        scanner.instance = new Html5Qrcode("reader");
    }

    function mirrorIfFront(isFront) {
        const video = document.querySelector('#reader video');
        if (video) video.style.transform = isFront ? 'scaleX(-1)' : '';
    }

    // cameraIdOrConfig: { facingMode: '...' } ATAU string deviceId. JANGAN pakai { video: ... }
    async function tryStart(cameraIdOrConfig) {
        try {
            await scanner.instance.start(cameraIdOrConfig, config, onSuccess, onError);
            scanner.state = 'running';
            fixVideoFrame();
            setTimeout(fixVideoFrame, 350);
            return true;
        } catch (e) {
            scanner.lastCameraError = normalizeCameraError(e, 'tryStart');
            await resetInstance(); // wajib: bersihkan state nyangkut sebelum percobaan berikutnya
            return false;
        }
    }

    const ios = isIOSDevice();
    const mobile = ios || isAndroidDevice() || isMobileDevice();

    // Tahap 1: sesuai perangkat (laptop = depan, Android/iOS = belakang)
    if (await tryStart({ facingMode: mobile ? 'environment' : 'user' })) {
        mirrorIfFront(!mobile);
        return;
    }

    // Tahap 2: pilih berdasarkan label kamera
    try {
        const devices = await Html5Qrcode.getCameras();
        if (devices && devices.length > 0) {
            const back = devices.find(d => isBackCameraLabel(d.label));
            const front = devices.find(d => isFrontCameraLabel(d.label));
            const order = (mobile ? [back, front] : [front, back]).filter(Boolean);

            for (const cam of order) {
                if (await tryStart(cam.id)) {
                    mirrorIfFront(!mobile && cam === front);
                    return;
                }
            }

            // Tahap 3: kamera apa saja
            if (await tryStart(devices[0].id)) {
                mirrorIfFront(!mobile);
                return;
            }
        }
    } catch (e) {
        normalizeCameraError(e, 'getCameras');
    }

    scanner.state = 'idle';

    let errorMsg = "Kamera gagal dibuka. Coba lagi atau gunakan Alat Scanner.";
    const lastError = scanner.lastCameraError;

    if (lastError) {
        switch (lastError.name) {
            case 'NotAllowedError':
            case 'SecurityError':
                errorMsg = "Izin kamera ditolak. Izinkan akses kamera di pengaturan browser.";
                break;
            case 'NotReadableError':
            case 'TrackStartError':
                errorMsg = "Kamera sedang dipakai aplikasi atau tab lain. Tutup lalu coba lagi.";
                break;
            case 'NotFoundError':
            case 'OverconstrainedError':
                errorMsg = "Kamera tidak ditemukan.";
                break;
            case 'LibraryError':
                errorMsg = `Kamera gagal dibuka (kode: ${lastError.name}). ${lastError.message}`;
                break;
            default:
                errorMsg = `Kamera gagal dibuka (kode: ${lastError.name}).`;
        }
    }

    showCameraError(errorMsg);
}

/* ================= STOP KAMERA ================= */
async function stopCameraKiosk() {
    await safeStop();
    resetCameraPlaceholder();
}

/* ================= RETRY KAMERA ================= */
async function retryCamera() {
    await runCamera(async () => {
        await safeStop();
        await new Promise(resolve => setTimeout(resolve, 300));
        await startCameraKiosk();
    });
}

/* ================= SWITCHER MODE ================= */
function switchMode(mode) {
    if (scanner.isProcessingScan) return;

    const btnCam = document.getElementById('btnTabCamera');
    const btnHard = document.getElementById('btnTabHardware');
    const viewCam = document.getElementById('cameraView');
    const viewHard = document.getElementById('hardwareView');
    const boxArea = document.getElementById('scannerBox');
    if (!btnCam || !btnHard || !viewCam || !viewHard || !boxArea) return;

    if (mode === 'camera') {
        btnCam.classList.add('active');
        btnHard.classList.remove('active');
        btnCam.setAttribute('aria-selected', 'true');
        btnHard.setAttribute('aria-selected', 'false');
        viewCam.classList.remove('d-none');
        viewHard.classList.add('d-none');
        boxArea.classList.add('camera-active');

        if (scanner.state !== 'running' && scanner.state !== 'starting') {
            runCamera(async () => {
                await safeStop();
                await startCameraKiosk();
            });
        }
    } else {
        btnHard.classList.add('active');
        btnCam.classList.remove('active');
        btnHard.setAttribute('aria-selected', 'true');
        btnCam.setAttribute('aria-selected', 'false');
        viewHard.classList.remove('d-none');
        viewCam.classList.add('d-none');
        boxArea.classList.remove('camera-active');

        runCamera(async () => {
            await safeStop();
        });

        refocusHardwareInput();
    }
}

/* ================= CLOCK & FULLSCREEN ================= */
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        clockEl.innerText = `${hours}:${minutes}:${seconds}`;
    }
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => console.error(err));
    } else if (document.exitFullscreen) {
        document.exitFullscreen();
    }
}

/* ================= INITIALIZATION ================= */
document.addEventListener('DOMContentLoaded', function() {
    if (scanner.initialized) {
        console.log('[kiosk-camera] Already initialized, skipping');
        return;
    }
    scanner.initialized = true;

    console.log('[kiosk-camera] Initializing...');

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

    window.addEventListener('click', function(e) {
        const view = document.getElementById('hardwareView');
        if (!view || view.classList.contains('d-none')) return;
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON') {
            refocusHardwareInput();
        }
    });

    window.addEventListener('beforeunload', function() {
        runCamera(async () => {
            await safeStop();
        });
    });

    console.log('[kiosk-camera] Starting camera...');
    runCamera(async () => {
        await startCameraKiosk();
    });
});

// Initialize clock
setInterval(updateClock, 1000);
updateClock();