/* ==========================================================================
   JS KAMERA GERBANG (sama persis untuk /admin/scanner & /admin/absensi/kiosk)
   Pilihan kamera otomatis:
     - Android / iOS / iPad -> kamera BELAKANG (facingMode: environment)
     - Laptop / PC          -> kamera DEPAN    (facingMode: user)

   Mutex + antrean serial untuk mencegah race condition.
   FIX: Html5Qrcode.start() menerima { facingMode } atau deviceId (string),
        BUKAN { video: {...} }. Instance dibuat ulang bila start gagal.
   ========================================================================== */

/* KONTRAK SKOP: seluruh isi file ini berada di dalam IIFE — TIDAK ada deklarasi
   let/const/var/function yang bocor ke global. Yang diekspor ke window hanya
   fungsi bersama (handleScanResult, renderScanResult, resetOverlayState, playBeep,
   dll) sehingga tidak pernah bentrok dengan script inline halaman
   Presensi Hari Ini (admin/attendances/daily) atau Detail Kelas (admin/absensi/class). */

(function () {
    'use strict';

// Rasio sisi qrbox terhadap sisi viewfinder (harus sama dgn --gerbang-scan-ratio)
const GERBANG_SCAN_RATIO = 0.7;

// Durasi total tampilan animasi hasil scan: 1.5 detik (masuk cepat, tahan, keluar halus)
const SCAN_OVERLAY_DURATION_MS = 1500;

// Debounce proteksi untuk token QR yang sama persis (2.5 detik)
const SAME_CODE_DEBOUNCE_MS = 2500;
let lastScannedToken = '';
let lastScannedTimestamp = 0;

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

/* ================= SUARA BEEP (audio/beep.mp3 milik sekolah) =================
   SATU-SATUNYA beep yang dipakai semua scanner. Hanya file beep.mp3, tanpa
   suara lain dalam bentuk apa pun (tanpa Web Audio/oscillator/cadangan). */
function playBeep() {
    const beep = document.getElementById('beepSound');
    if (!beep) return;
    try {
        // Pastikan elemen tidak sedang terkunci senyap oleh proses unlock,
        // supaya beep scan selalu terdengar (nada/durasi/volume tidak berubah).
        if (beep.muted) beep.muted = false;
        beep.currentTime = 0;
        const p = beep.play();
        if (p && typeof p.catch === 'function') {
            p.catch(() => { /* autoplay ditolak: diam, tanpa suara pengganti */ });
        }
    } catch (e) { /* diam */ }
}

/* Buka kunci audio saat ada gestur user (klik/sentuh/tombol), supaya
   beep berikutnya diizinkan browser (Chrome Android & iOS Safari). */
let scannerAudioUnlocked = false;
function unlockScannerAudio() {
    if (scannerAudioUnlocked) return;
    const beep = document.getElementById('beepSound');
    if (!beep) return;
    // Jangan ganggu beep scan yang sedang berbunyi; unlock dicoba lagi
    // pada gestur user berikutnya (listener permanen di bawah).
    if (!beep.paused && !beep.muted) return;
    try {
        beep.muted = true;
        const p = beep.play();
        const done = () => {
            // Hanya kunci ulang bila pemutaran senyap unlock masih berjalan.
            // Bila di antaranya ada beep scan yang sudah berbunyi (muted sudah
            // dilepas playBeep), JANGAN pause/reset agar bunyi scan tidak mati.
            if (beep.muted) {
                try { beep.pause(); beep.currentTime = 0; } catch (e) {}
                beep.muted = false;
            }
            scannerAudioUnlocked = true;
        };
        if (p && typeof p.then === 'function') { p.then(done).catch(() => {}); }
        else { done(); }
    } catch (e) { /* diam */ }
}

/* Kunci audio dicoba pada SETIAP interaksi user sampai benar-benar berhasil
   (percobaan pertama bisa ditolak browser), lalu listener melepas dirinya
   sendiri. Berlaku di semua halaman scan (gerbang, presensi harian, detail
   kelas) dan di desktop maupun mobile. */
(function attachScannerAudioUnlock() {
    const tryUnlock = function () {
        unlockScannerAudio();
        if (scannerAudioUnlocked) {
            window.removeEventListener('pointerdown', tryUnlock);
            window.removeEventListener('keydown', tryUnlock);
        }
    };
    window.addEventListener('pointerdown', tryUnlock);
    window.addEventListener('keydown', tryUnlock);
})();

/* Umpan balik scan GAGAL: getar singkat di mobile bila didukung, tanpa bunyi. */
function vibrateOnScanFail() {
    try {
        if (navigator && typeof navigator.vibrate === 'function') {
            navigator.vibrate(120);
        }
    } catch (e) { /* diam */ }
}

function playBeepSound() { playBeep(); }

/* ============== SATU TITIK PUSAT UMpan BALIK PER SCAN ==============
   Dipanggil TEPAT SATU KALI untuk tiap kali user memindai:
     - hasil diterima dari server: Hadir, Terlambat, Presensi Pulang,
       sudah absen, kartu tidak dikenal, kode ngawur, error 4xx/5xx;
     - gagal jaringan / timeout / respons bukan JSON (blok catch);
     - kode kosong (status null -> tanpa notifikasi & tanpa getar).
   SEMUA hasil berbunyi beep dengan nada yang sama persis (audio/beep.mp3):
   tidak ada nada baru, tidak ada perubahan frekuensi/durasi/volume, dan
   tidak ada bunyi ganda (satu pemanggilan per scan).
   Hasil non-sukses tetap mendapat getar seperti sebelumnya.
   Dipanggil SEBELUM render hasil, sehingga bunyi keluar bersamaan notifikasi. */
function feedbackScan(status, body) {
    playBeep();
    if (status === null || status === undefined) return; // kode kosong: beep saja
    if (status === 200 && body && body.success) return;  // sukses: tanpa getar
    vibrateOnScanFail();
}

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

function isUnrecognizedQrMessage(msg) {
    const m = (msg || '').toLowerCase();
    return m.includes('tidak dikenali') || m.includes('tidak ditemukan') || m.includes('tidak valid');
}

function processCode(token) {
    const cleanToken = (token || '').trim();
    if (!cleanToken) {
        // KODE KOSONG: tetap SATU beep supaya scan tidak hilang tanpa bunyi.
        // Tanpa notifikasi & tanpa getar (tampilan hasil tidak berubah).
        feedbackScan(null, null);
        return;
    }
    if (scanner.isProcessingScan) return;

    const now = Date.now();
    if (cleanToken === lastScannedToken && (now - lastScannedTimestamp < SAME_CODE_DEBOUNCE_MS)) {
        return; // Abaikan jika QR token sama persis dalam masa debounce
    }

    scanner.isProcessingScan = true;
    lastScannedToken = cleanToken;
    lastScannedTimestamp = now;

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
        // SATU beep per scan untuk SEMUA hasil (Hadir, Terlambat, sudah absen,
        // kartu tidak dikenal, kode ngawur, error server) — bersamaan notifikasi.
        feedbackScan(status, body);
        handleScanResult(status, body);
    })
    .catch((err) => {
        console.error('[kiosk-scanner] Fetch error:', err);
        const failBody = {
            success: false,
            message: 'Terjadi kendala koneksi ke server, coba lagi'
        };
        // SATU beep per scan (gagal jaringan/timeout/respons tak terbaca) + getar.
        feedbackScan(500, failBody);
        // HASIL 5: Error server/jaringan -> Lingkaran MERAH + TANDA SERU (!) putih
        handleScanResult(500, failBody);
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

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* ==========================================================================
   PENENTUAN HASIL SCAN (5 KOMBINASI WARNA LINGKARAN & IKON)
   Single Source of Truth: presensi-tokens.css & scanner.js
   ========================================================================== */
function handleScanResult(status, body) {
    body = body || {};

    // 1 & 2: SCAN BERHASIL (status 200 && body.success)
    if (status === 200 && body.success) {
        const student = body.student || {};
        const nama = student.name || 'Siswa';
        const kelas = student.class || student.kelas || '';
        const waktu = body.time_short ? body.time_short + ' WIB' : '';

        const titleHtml = `<span class="d-block text-uppercase fw-bold text-truncate" style="letter-spacing: -0.01em;">${escapeHtml(nama)}</span>` +
                          (kelas ? `<span class="d-block fw-bold text-white opacity-90 mt-1" style="font-size: 0.88em;">${escapeHtml(kelas)}</span>` : '');

        if (body.is_late) {
            // HASIL 2: Berhasil tapi TERLAMBAT -> Lingkaran HIJAU + X putih
            const subtitleText = 'Terlambat' + (waktu ? ' · ' + waktu : '');
            renderScanResult('green', 'cross', titleHtml, subtitleText, false);
        } else if (body.type === 'check_out') {
            // Presensi Pulang -> Lingkaran HIJAU + CENTANG putih
            const subtitleText = 'Presensi Pulang' + (waktu ? ' · ' + waktu : '');
            renderScanResult('green', 'check', titleHtml, subtitleText, false);
        } else {
            // HASIL 1: Berhasil, tepat waktu (HADIR) -> Lingkaran HIJAU + CENTANG putih
            const subtitleText = 'Hadir' + (waktu ? ' · ' + waktu : '');
            renderScanResult('green', 'check', titleHtml, subtitleText, false);
        }
        return;
    }

    // 3: SISWA SUDAH PRESENSI HARI INI (ada data student)
    if (body.student && body.student.name) {
        const student = body.student;
        const nama = student.name;
        const kelas = student.class || student.kelas || '';

        const titleHtml = `<span class="d-block text-uppercase fw-bold text-truncate">${escapeHtml(nama)}</span>` +
                          (kelas ? `<span class="d-block fw-bold text-white opacity-90 mt-1" style="font-size: 0.88em;">${escapeHtml(kelas)}</span>` : '');

        // Pesan teks: "Siswa atas nama X sudah melakukan presensi hari ini"
        const subtitleText = body.message || `Siswa atas nama ${nama} sudah melakukan presensi hari ini`;

        // HASIL 3: Sudah presensi hari ini -> Lingkaran MERAH + X putih
        renderScanResult('red', 'cross', titleHtml, subtitleText, true);
        return;
    }

    // 4: QR/KARTU TIDAK DIKENALI ATAU SISWA TIDAK DITEMUKAN (status 404 / pesan kartu tidak dikenali)
    if (status === 404 || isUnrecognizedQrMessage(body.message)) {
        // HASIL 4: QR tidak dikenali -> Lingkaran ORANYE + TANDA SERU (!) putih
        // Pesan teks: "Kartu tidak dikenali atau data siswa tidak ditemukan"
        const subtitleText = 'Kartu tidak dikenali atau data siswa tidak ditemukan';
        renderScanResult('orange', 'exclamation', '', subtitleText, true);
        return;
    }

    // 5: ERROR SERVER / JARINGAN
    // HASIL 5: Error server/jaringan -> Lingkaran MERAH + TANDA SERU (!) putih
    const titleHtml = `<span class="d-block text-uppercase fw-bold">Gagal</span>`;
    const subtitleText = body.message || 'Terjadi kendala koneksi ke server, coba lagi';
    renderScanResult('red', 'exclamation', titleHtml, subtitleText, true);
}

/**
 * Satu fungsi terpusat untuk menampilkan hasil scan dengan kombinasi warna lingkaran dan ikon
 * @param {'green'|'orange'|'red'} circleColor - Warna lingkaran luar
 * @param {'check'|'cross'|'exclamation'} iconType - Ikon putih di dalam lingkaran
 * @param {string} titleHtml - HTML judul (Nama Siswa Kapital Bold & Kelas)
 * @param {string} subtitleText - Teks status / pesan di bawahnya
 * @param {boolean} shake - Animasi getar halus jika error / warning
 */
function renderScanResult(circleColor, iconType, titleHtml, subtitleText, shake = false) {
    const overlay = document.getElementById('scanResultOverlay');
    if (!overlay) return;

    // Set kelas styling lingkaran dan ikon sesuai kombinasi
    overlay.className = 'scan-result-overlay';
    overlay.classList.add('circle-' + circleColor);
    overlay.classList.add('icon-' + iconType);
    if (shake) {
        overlay.classList.add('shake');
    }

    const titleEl = document.getElementById('scanResultTitle');
    const subtitleEl = document.getElementById('scanResultSubtitle');

    if (titleEl) {
        titleEl.innerHTML = titleHtml || '';
        titleEl.style.display = titleHtml ? '' : 'none';
    }
    if (subtitleEl) {
        subtitleEl.innerText = subtitleText || '';
    }

    triggerOverlayDisplay(overlay);
}

function triggerOverlayDisplay(overlay) {
    if (!overlay) return;

    // 1) TAMPILKAN overlay dulu. Jika masih display:none, browser TIDAK
    //    menjalankan animasi sama sekali (gejala: animasi tidak muncul di
    //    panel Scanner QR). d-none & fade-out dibersihkan di sini.
    overlay.classList.remove('d-none', 'fade-out');
    overlay.style.display = '';
    overlay.style.opacity = '';

    // 2) Paksa reflow supaya state "tampil" ter-commit ke layout sebelum
    //    node animasi diganti (langkah 3).
    void overlay.offsetWidth;

    // 3) RESTART SEMUA animasi hasil scan (gambar lingkaran, ikon centang/
    //    X/tanda seru, teks) dengan menukar node .scan-result-content
    //    -> node baru. Elemen baru = animasi CSS mulai dari frame 0.
    //    Berlaku PERSIS SAMA untuk Mode Gerbang & panel Scanner QR
    //    karena markup & CSS-nya identik (sumber tunggal).
    const content = overlay.querySelector('.scan-result-content');
    if (content && content.parentNode) {
        const clone = content.cloneNode(true);
        content.parentNode.replaceChild(clone, content);
    }

    if (scanner.resetTimer) clearTimeout(scanner.resetTimer);

    // Animasi keluar halus (fade-out) 150ms sebelum durasi total 1.5 detik selesai
    const fadeOutDelay = Math.max(0, SCAN_OVERLAY_DURATION_MS - 150);
    setTimeout(() => {
        if (!overlay.classList.contains('d-none')) {
            overlay.classList.add('fade-out');
        }
    }, fadeOutDelay);

    // Reset overlay & izinkan scan berikutnya setelah durasi selesai (kamera tetap standby)
    scanner.resetTimer = setTimeout(() => {
        overlay.classList.add('d-none');
        overlay.classList.remove('fade-out');
        scanner.isProcessingScan = false;
        try {
            window.dispatchEvent(new CustomEvent('scan-result-finished'));
        } catch (e) {}
        refocusHardwareInput();
    }, SCAN_OVERLAY_DURATION_MS);
}

// Backward-compatibility delegators
function showOverlaySuccess(data) { handleScanResult(200, data); }
function showOverlayWarning(message) { handleScanResult(404, { success: false, message: message }); }
function showOverlayError(message, student) { handleScanResult(400, { success: false, message: message, student: student }); }
function resetOverlayState(delay = SCAN_OVERLAY_DURATION_MS) {
    if (scanner.resetTimer) clearTimeout(scanner.resetTimer);
    scanner.resetTimer = setTimeout(() => {
        const overlay = document.getElementById('scanResultOverlay');
        if (overlay) {
            overlay.classList.add('d-none');
            overlay.classList.remove('fade-out');
        }
        scanner.isProcessingScan = false;
        try {
            window.dispatchEvent(new CustomEvent('scan-result-finished'));
        } catch (e) {}
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
        // REVISI 1 (kiblat: Scanner QR = tidak mirror): SATU aturan arah untuk
        // kiosk + panel — video TIDAK di-mirror pada kamera depan maupun
        // belakang. Hanya tampilan; pemilihan kamera/decode/request tidak berubah.
        const v = document.querySelector('#reader video');
        if (!v) return;
        try { v.style.scale = ''; } catch (e) {}
        v.classList.remove('mirror-front');
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
    // Hanya auto-start kamera gerbang jika berada di halaman kiosk/scanner mandiri (memiliki .kiosk-main)
    if (!document.querySelector('.kiosk-main')) {
        return;
    }

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

    window.addEventListener('pagehide', function() {
        runCamera(async () => {
            await safeStop();
        });
    });

    // Buka kunci audio sekali saat interaksi pertama (penting untuk halaman
    // kiosk yang auto-start kamera tanpa tombol buka).
    const unlockOnce = function() { unlockScannerAudio(); };
    window.addEventListener('pointerdown', unlockOnce, { once: true });
    window.addEventListener('keydown', unlockOnce, { once: true });

    console.log('[kiosk-camera] Starting camera...');
    runCamera(async () => {
        await startCameraKiosk();
    });
});

// Initialize clock (hanya jika ada elemen liveClock di halaman)
if (document.getElementById('liveClock')) {
    setInterval(updateClock, 1000);
    updateClock();
}

// ================= EXPORTS KE WINDOW =================
// 1. Fungsi hasil scan dipakai bersama oleh Mode Gerbang dan Scanner Inline
window.handleScanResult = handleScanResult;
window.renderScanResult = renderScanResult;
window.triggerOverlayDisplay = triggerOverlayDisplay;
window.isUnrecognizedQrMessage = isUnrecognizedQrMessage;
window.escapeHtml = escapeHtml;
window.showOverlaySuccess = showOverlaySuccess;
window.showOverlayWarning = showOverlayWarning;
window.showOverlayError = showOverlayError;
window.resetOverlayState = resetOverlayState;
window.playBeep = playBeep;
window.playBeepSound = playBeepSound;
window.unlockScannerAudio = unlockScannerAudio;
window.vibrateOnScanFail = vibrateOnScanFail;

// 2. Fungsi tombol interaktif khusus halaman Mode Gerbang & Presensi Gerbang (.kiosk-main)
if (document.querySelector('.kiosk-main') || document.body.classList.contains('kiosk-body')) {
    window.switchMode = switchMode;
    window.retryCamera = retryCamera;
    window.toggleFullscreen = toggleFullscreen;
    window.processCode = processCode;
    window.processScanCode = processScanCode;
}

})();
