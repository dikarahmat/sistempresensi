/* ==========================================================================
   PEMILIHAN KAMERA PINTAR UNTUK PANEL "SCANNER QR" DI HALAMAN ABSENSI
   ==========================================================================
   Logika disalin 1:1 dari acuan public/js/scanner.js (MODE GERBANG / kiosk):
     - Android / iOS / iPad -> kamera BELAKANG (facingMode: environment)
     - Laptop / PC          -> kamera DEPAN    (facingMode: user)
   Urutan start: facingMode per perangkat -> label kamera -> kamera apa saja.
   Format start(): { facingMode: '...' } ATAU string deviceId (BUKAN
   { video: {...} } / { deviceId: { exact: ... } }) -> mencegah error
   "Cannot transition to a new state, already under transition".

   PENTING: public/js/scanner.js dan halaman mode gerbang TIDAK memuat file
   ini, sehingga perilaku mode gerbang tidak berubah sama sekali.
   ========================================================================== */

window.CameraSelect = (function () {
    'use strict';

    /* ================= ANTREAN SERIAL (MUTEX) =================
       Semua start/stop berjalan berurutan, tidak pernah paralel. */
    let queue = Promise.resolve();

    function run(task) {
        queue = queue.then(task, task).catch(e => {
            console.error('[camera-select] Queue error:', e);
        });
        return queue;
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

    /* ================= NORMALISASI ERROR ================= */
    function normalizeCameraError(err, tahap) {
        const name = err?.name || (typeof err === 'string' ? 'LibraryError' : err?.constructor?.name || 'UnknownError');
        const message = err?.message || String(err);
        console.error('[camera-select]', tahap, name, message, err);
        return { name, message };
    }

    /* Pesan error per nama error — daftar persis seperti mode gerbang */
    function errorMessageFor(normalizedErr) {
        if (!normalizedErr || !normalizedErr.name) {
            return "Kamera gagal dibuka. Coba lagi atau gunakan Alat Scanner.";
        }
        switch (normalizedErr.name) {
            case 'NotAllowedError':
            case 'SecurityError':
                return "Izin kamera ditolak. Izinkan akses kamera di pengaturan browser.";
            case 'NotReadableError':
            case 'TrackStartError':
                return "Kamera sedang dipakai aplikasi atau tab lain. Tutup lalu coba lagi.";
            case 'NotFoundError':
            case 'OverconstrainedError':
                return "Kamera tidak ditemukan.";
            case 'LibraryError':
                return `Kamera gagal dibuka (kode: ${normalizedErr.name}). ${normalizedErr.message}`;
            default:
                return `Kamera gagal dibuka (kode: ${normalizedErr.name}).`;
        }
    }

    /* ================= UTIL ELEMEN ================= */
    function getReaderEl(containerId) {
        const id = containerId || 'reader';
        return document.getElementById(id) || document.getElementById('reader');
    }

    function getVideoEl(containerId) {
        const root = getReaderEl(containerId);
        return root ? root.querySelector('video') : null;
    }

    /* ================= HARDENING VIDEO UNTUK iOS / SAFARI ================= */
    function fixVideoFrame(containerId) {
        const root = getReaderEl(containerId);
        if (!root) return;
        const video = root.querySelector('video');
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
            ['loadedmetadata', 'resize', 'playing'].forEach(function (ev) {
                video.addEventListener(ev, function () { fixVideoFrame(containerId); });
            });
        }
    }

    /* ================= MIRROR (hanya kamera depan di desktop) =================
       Halaman absensi memakai `transform: translate(-50%, -50%) !important`
       untuk positioning video. Inline `transform` akan kalah dengan !important
       (dan bila dipaksa, menggeser posisi elemen), jadi mirror memakai property
       CSS `scale` yang terpisah dari transform: posisi TIDAK berubah, hanya
       cermin visual. Decode QR tidak terpengaruh (html5-qrcode menggambar
       frame video apa adanya, tanpa efek CSS). */
    function mirrorIfFront(containerId, isFront) {
        const video = getVideoEl(containerId);
        if (!video) return;
        try {
            video.style.scale = isFront ? 'scaleX(-1)' : '';
        } catch (e) { /* browser lama tanpa property `scale`: tanpa mirror */ }
    }

    /* ================= SAFE STOP ================= */
    async function safeStop(instance, containerId) {
        if (!instance) return;

        try {
            const state = instance.getState();
            if (state === 2 || state === 3) {
                await instance.stop();
            }
        } catch (e) {
            console.error('[camera-select] safeStop error:', e);
        }

        try {
            await instance.clear();
        } catch (e) {
            console.error('[camera-select] clear error:', e);
        }

        const root = getReaderEl(containerId);
        if (root) {
            root.querySelectorAll('video').forEach(v => {
                if (v.srcObject) {
                    v.srcObject.getTracks().forEach(t => t.stop());
                    v.srcObject = null;
                }
                v.style.scale = '';
            });
        }
    }

    /* ================= START KAMERA 3 TAHAP =================
       opts: {
         containerId     : ID container (default 'reader'),
         getInstance     : () => instance Html5Qrcode saat ini (boleh null),
         createInstance  : () => buat instance baru & daftarkan ke halaman,
         releaseInstance : async () => safeStop + buang instance + kosongkan
                           container (dipanggil di awal & tiap gagal start),
         config          : config scan halaman (fps/qrbox) — TIDAK diubah,
         onSuccess       : callback hasil scan halaman — TIDAK diubah,
         onError         : callback error halaman — TIDAK diubah
       } -> { success: true } ATAU { success: false, error: {name,message} }. */
    async function startSmartCamera(opts) {
        const containerId = (opts && opts.containerId) || 'reader';
        const config = opts.config;
        let lastError = null;

        // Instance bersih sebelum percobaan pertama
        await opts.releaseInstance();
        opts.createInstance();

        const ios = isIOSDevice();
        const mobile = ios || isAndroidDevice() || isMobileDevice();

        async function tryStart(cameraIdOrConfig) {
            try {
                await opts.getInstance().start(cameraIdOrConfig, config, opts.onSuccess, opts.onError);
                fixVideoFrame(containerId);
                setTimeout(function () { fixVideoFrame(containerId); }, 350);
                return true;
            } catch (e) {
                lastError = normalizeCameraError(e, 'tryStart');
                // Wajib reset instance sebelum tahap berikutnya
                await opts.releaseInstance();
                opts.createInstance();
                return false;
            }
        }

        // Tahap 1: sesuai perangkat (laptop = depan, Android/iOS = belakang)
        if (await tryStart({ facingMode: mobile ? 'environment' : 'user' })) {
            mirrorIfFront(containerId, !mobile);
            return { success: true };
        }

        // Tahap 2: pilih berdasar label kamera; Tahap 3: kamera apa saja
        try {
            const devices = await Html5Qrcode.getCameras();
            if (devices && devices.length > 0) {
                const back = devices.find(d => isBackCameraLabel(d.label));
                const front = devices.find(d => isFrontCameraLabel(d.label));
                const order = (mobile ? [back, front] : [front, back]).filter(Boolean);

                for (const cam of order) {
                    if (await tryStart(cam.id)) {
                        mirrorIfFront(containerId, !mobile && cam === front);
                        return { success: true };
                    }
                }

                // Tahap 3: kamera apa saja yang tersedia (jaring pengaman:
                // HP tanpa kamera belakang -> otomatis kamera depan)
                if (await tryStart(devices[0].id)) {
                    mirrorIfFront(containerId, !mobile);
                    return { success: true };
                }
            }
        } catch (e) {
            lastError = normalizeCameraError(e, 'getCameras');
        }

        return {
            success: false,
            error: lastError || { name: 'NotFoundError', message: 'No camera available' }
        };
    }

    return {
        run: run,
        isIOSDevice: isIOSDevice,
        isAndroidDevice: isAndroidDevice,
        isMobileDevice: isMobileDevice,
        isBackCameraLabel: isBackCameraLabel,
        isFrontCameraLabel: isFrontCameraLabel,
        normalizeCameraError: normalizeCameraError,
        errorMessageFor: errorMessageFor,
        fixVideoFrame: fixVideoFrame,
        mirrorIfFront: mirrorIfFront,
        safeStop: safeStop,
        startSmartCamera: startSmartCamera
    };
})();
