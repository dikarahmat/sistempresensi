{{--
    Komponen BERSAMA: progres DI DALAM tombol unduhan.

    Dipakai semua tombol/link yang mengunduh file (panel admin & guru):
      - Cetak Kartu Presensi Massal   -> "Generate & Unduh"
      - Unduh Template Excel (siswa / guru / kelas / hari libur)
      - Unduh QR Code & Kartu di Detail Siswa
      - Popup Cetak Rekap Presensi (semua mode, kelas CSS yang sama)

    Cara pakai — cukup tambahkan atribut data-dl-progress, markup tombol
    tidak perlu diubah (fill + label dibuat otomatis oleh JS):
      <a href="{{ route('x.template') }}" data-download data-no-download data-dl-progress>Unduh Template Excel</a>
      <button type="submit" data-download data-dl-progress>Generate &amp; Unduh</button>

    Fallback: tanpa JS, atribut href / submit form tetap bekerja seperti biasa.

    DUA MODE progres:
      1. Mode PERSEN (default). "Persen asli" (target) dipisahkan dari "persen
         tampil" (display). Display adalah bilangan bulat yang naik 1 demi 1 lewat
         timer, tidak pernah mundur, dan tidak pernah diam di 0%.
      2. Mode GESER (indeterminate) untuk saat kita benar-benar tidak tahu
        berapa persen (mis. menunggu Retry-After). Bar gelap melintas.

    Warna isian selalu LEBIH GELAP dari warna tombol, lewat variabel
    --btn-progress-fill yang didefinisikan per kelas tombol di bawah:
      biru  #3b82f6 -> #1e3a8a | hijau #16a34a -> #14532d | merah #dc2626 -> #7f1d1d

    API global:
      downloadWithProgress(button, url, opts) -> Promise<boolean>
      dlProgressMulai(button)  -> mulai mode persen dari 0%
      dlProgressSet(target, margin) -> target baru + ceiling; display mengejar
      dlProgressFinal()        -> kejar 100%, tahan, lalu "Selesai"
      dlProgressGeser(aktif)   -> mode geser (indeterminate) nyala/mati
      dlProgressAbort()        -> batalkan (senyap, tanpa pesan)
      dlProgressReset()        -> kembalikan semua state ke kondisi awal
      dlProgressBusy()         -> true kalau sedang ada unduhan berjalan

    Peristiwa pada tombol:
      dl-busy-start  -> detail.controller (AbortController), detail.signal
      dl-busy-end    -> detail { url, sukses, dibatalkan }
--}}
<style>
    /* ----- VARIABEL WARNA PROGRES PER VARIAN TOMBOL ----- */
/* Setiap varian tombol mendefinisikan --btn-progress-base (warna dasar) dan
   --btn-progress-fill (isi bar gelap). Warna ini diasosiasikan melalui class
   tombol yang sudah ada (btn-download-blue, btn-download-green, btn-download-red,
   btn-primary, btn-success, btn-danger). */

    /* Warna dasar per varian + fallback ke biru */
    .btn-download-blue.btn-progress,   .btn-primary.btn-progress   { --btn-progress-base: #2563eb; } /* biru 600 */
    .btn-download-green.btn-progress,  .btn-success.btn-progress   { --btn-progress-base: #059669; } /* hijau 500 */
    .btn-download-red.btn-progress,    .btn-danger.btn-progress    { --btn-progress-base: #dc2626; } /* merah 600 */

    /* Isi bar per varian (versi gelap dari warna dasar) */
    .btn-download-blue.btn-progress,   .btn-primary.btn-progress   { --btn-progress-fill: #1e3a8a; } /* biru tua */
    .btn-download-green.btn-progress,  .btn-success.btn-progress   { --btn-progress-fill: #064e3b; } /* hijau tua */
    .btn-download-red.btn-progress,    .btn-danger.btn-progress    { --btn-progress-fill: #7f1d1d; } /* merah tua */

    /* ----- TOMBOL SEBAGAI WAHAN PROGRES ----- */
    .btn-progress {
        position: relative;
        overflow: hidden;
        white-space: nowrap;
        isolation: isolate;
        /* Warna dasar diambil dari variabel --btn-progress-base yang sesuai varian tombol */
        background-color: var(--btn-progress-base, #3b82f6);
        border-radius: inherit;
    }

    /* ----- LAPISAN ISI BAR PROGRES ----- */
    .btn-progress__fill {
        position: absolute;
        top: 0; left: 0; bottom: 0;
        width: 0%;
        border-radius: inherit;
        /* Warna isi diambil dari variabel --btn-progress-fill per varian tombol */
        background-color: var(--btn-progress-fill, #1f2937);
        opacity: 1;
        transition: width .2s linear;   /* pendek & linear: bar dan angka sinkron */
        z-index: 0;
        pointer-events: none;
    }

    /* ----- TEKS PERSENTASE (di atas fill) ----- */
    .btn-progress__label {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: bold;
        z-index: 1;
    }

    /* Saat jalan: hanya persen, tebal, rata tengah; ukuran tombol tetap. */
    .btn-progress.is-running { pointer-events: none; }
    .btn-progress.is-running .btn-progress__label { font-weight: 700; }

    /* Selesai: tombol hijau, teks "Selesai" (tanpa ikon). */
    .btn-progress.is-done {
        background-color: #16a34a !important;
        border-color: #16a34a !important;
        color: #fff !important;
    }
    .btn-progress.is-done .btn-progress__fill { display: none; }

    @media (prefers-reduced-motion: reduce) {
        .btn-progress__fill { transition: none; }
    }

    /* Pesan gagal: satu baris kecil, bukan blok teks panjang.
       Tidak dipakai saat unduhan DIBATALKAN. */
    .dl-progress-error {
        display: block;
        margin-top: 4px;
        font-size: .78rem;
        line-height: 1.3;
        color: #dc2626;
    }
    .dl-progress-error:empty { display: none; }

    /* Footer popup unduhan: dua tombol sama lebar, lebar tetap, baris penuh.
       Tombol Batal/Hentikan tidak pernah berubah abu gelap saat nonaktif. */
    .dl-actions {
        display: flex;
        gap: .5rem;
        width: 100%;
    }
    .dl-actions > .btn {
        flex: 1 1 168px;
        min-width: 168px;
        white-space: nowrap;
        text-align: center;
    }
    .dl-actions > .btn:disabled,
    .dl-actions > .btn[disabled] {
        opacity: 1;
        color: inherit;
    }
    .dl-actions > .btn-light:disabled,
    .dl-actions > .btn-light[disabled] {
        background-color: var(--bs-btn-bg, #f8f9fa);
        border-color: var(--bs-btn-border-color, #dee2e6);
        color: var(--bs-btn-color, #212529);
    }
</style>

<script>
(function () {
    'use strict';

    if (window.downloadWithProgress) return;   // sudah terpasang

    /* ---------- parameter ---------- */
    var TICK_MERAYAP_MS = 40;    // mode persen: naik +1 tiap 40 ms
    var TICK_KEJAR_MS   = 15;    // mengejar target: +1 (atau +2) tiap 15 ms
    var TICK_FINAL_MS   = 12;    // kejar 100%: +1 tiap 12 ms
    var HOLD_100_MS     = 300;   // tahan di 100% supaya terlihat
    var HOLD_SELESAI_MS = 1000;  // tahan teks "Selesai"
    var TEKS_SELESAI    = 'Selesai';
    var TEKS_GESER      = 'Memproses...';
    var MAKS_RETRY      = 3;
    var ESTIMASI_MS     = 150;   // tanpa Content-Length: interval estimasi
    var PLAFON_ESTIMASI = 90;    // maks estimasi selama menunggu
    var KURVA_ESTIMASI  = 0.06;  // target += (90 - target) * 0.06
    var MIN_LANGKAH     = 1;
    var MARGIN_BIASA    = 3;     // ceiling = target + 3 (supaya angka tidak membeku)

    var sedang = null;   // state unduhan yang sedang berjalan

    /* ---------- kerangka fill + label, dibuat otomatis ---------- */
    function scaffold(btn) {
        btn.classList.add('btn-progress');

        var fill = btn.querySelector(':scope > .btn-progress__fill');
        if (!fill) {
            fill = document.createElement('span');
            fill.className = 'btn-progress__fill';
            fill.setAttribute('aria-hidden', 'true');
            btn.insertBefore(fill, btn.firstChild);
        }

        var label = btn.querySelector(':scope > .btn-progress__label');
        if (!label) {
            label = document.createElement('span');
            label.className = 'btn-progress__label';
            // pindahkan SEMUA isi tombol yang ada ke dalam label (kecuali fill),
            // supaya teks tombol ikut ter-centering bersama bar progres.
            var anak = [];
            var i;
            for (i = 0; i < btn.childNodes.length; i++) {
                if (btn.childNodes[i] !== fill) anak.push(btn.childNodes[i]);
            }
            for (i = 0; i < anak.length; i++) {
                label.appendChild(anak[i]);
            }
            btn.appendChild(label);
        }
        return { fill: fill, label: label };
    }

    /* Paksa hitung ulang gaya supaya animasi mulai dari posisi semula. */
    function paksaReflow(el) {
        void el.offsetWidth;
    }

    function setPercent(parts, persen) {
        var p = Math.max(0, Math.min(100, Math.round(persen || 0)));
        parts.fill.style.width = p + '%';
        parts.label.textContent = p + '%';
        return p;
    }

    /* Pesan singkat di bawah tombol; hilang sendiri setelah 8 detik.
       HANYA untuk kegagalan server, tidak untuk pembatalan. */
    function pesanError(btn, teks) {
        if (!btn.parentNode) return;
        var el = btn.parentNode.querySelector('.dl-progress-error');
        if (!el) {
            // Jangan pernah membuat elemen pesan kalau memang tidak ada teks:
            // pembatalan tidak boleh meninggalkan jejak apa pun.
            if (!teks) return;
            el = document.createElement('span');
            el.className = 'dl-progress-error';
            btn.parentNode.appendChild(el);
        }
        window.clearTimeout(el._t);
        el.textContent = teks || '';
        if (teks) {
            el._t = window.setTimeout(function () { el.textContent = ''; }, 8000);
        }
    }

    function namaDariHeader(header, fallback) {
        if (!header) return fallback || 'unduh';
        var utf8 = header.match(/filename\*=UTF-8''([^;]+)/i);
        if (utf8) {
            try { return decodeURIComponent(utf8[1]); } catch (e) { return utf8[1]; }
        }
        var basic = header.match(/filename="?([^";]+)"?/i);
        return basic ? basic[1] : (fallback || 'unduh');
    }

    function simpanBlob(blob, filename) {
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename || 'unduh';
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.setTimeout(function () { URL.revokeObjectURL(url); }, 8000);
    }

    function csrf() {
        var el = document.querySelector('input[name="_token"]');
        return el ? el.value : '';
    }

    /* ---------- timer ---------- */
    function stop(s, nama) {
        if (s[nama]) { window.clearInterval(s[nama]); s[nama] = null; }
    }
    function stopSemuaTimer(s) {
        stop(s, 'timerMerayap');
        stop(s, 'timerKejar');
        stop(s, 'timerFinal');
        stop(s, 'timerEstimasi');
        if (s.timerTahan) { window.clearTimeout(s.timerTahan); s.timerTahan = null; }
    }

    /* Kembalikan tombol ke kondisi awal: persen, fill, teks, disabled. */
    function resetTampilan(s) {
        s.parts.fill.style.width   = '0%';
        s.parts.label.textContent = s.labelAwal;
        s.button.disabled         = false;
        s.button.classList.remove('is-running', 'is-done', 'is-indeterminate');
        s.button.dataset.dlBusy   = '';
    }

    /* Titik akhir tunggal (idempotent): reset + peristiwa + selesaikan promise. */
    function tutup(s, sukses) {
        if (s.sudahTutup) return;
        s.sudahTutup = true;

        stopSemuaTimer(s);
        if (sedang === s) sedang = null;
        resetTampilan(s);

        s.button.dispatchEvent(new CustomEvent('dl-busy-end', {
            detail: { url: s.url, sukses: !!sukses, dibatalkan: !!s.dibatalkan }
        }));

        if (typeof s.selesaikan === 'function') s.selesaikan(!!sukses);
    }

    /* ---------- mesin display ----------
       target  = persen asli (kelas selesai / N, atau byte / total)
       ceiling = batas atas display sementara (belum boleh melewati kelas berikutnya)
       display = yang benar-benar ditulis ke DOM (bilangan bulat, naik 1 demi 1) */

    function tampil(s) {
        if (s.geser) return;              // mode geser: CSS yang menangani lebar
        setPercent(s.parts, s.display);
    }

    function kejar(s) {
        if (s.geser || s.sudahTutup) return;
        s.fase = 'kejar';
        stop(s, 'timerMerayap');
        stop(s, 'timerFinal');
        if (!s.timerKejar) s.timerKejar = window.setInterval(function () { tickKejar(s); }, TICK_KEJAR_MS);
    }

    function merayap(s) {
        if (s.sudahTutup) return;
        s.fase = 'merayap';
        stop(s, 'timerKejar');
        stop(s, 'timerFinal');
        if (!s.timerMerayap) s.timerMerayap = window.setInterval(function () { tickMerayap(s); }, TICK_MERAYAP_MS);
    }

    function final(s) {
        if (s.geser || s.sudahTutup) return;
        s.fase = 'final';
        s.target = 100;
        s.ceiling = 100;
        stop(s, 'timerMerayap');
        stop(s, 'timerKejar');
        if (!s.timerFinal) s.timerFinal = window.setInterval(function () { tickFinal(s); }, TICK_FINAL_MS);
    }

    /* +1 tiap 40 ms selama display < ceiling. */
    function tickMerayap(s) {
        if (s.sudahTutup || s.dibatalkan) { stopSemuaTimer(s); return; }
        if (s.geser) return;

        // Display selalu bilangan bulat, jadi ceiling dibulatkan ke bawah.
        // (0.9 * (100/3) = 30.000000000000004; tanpa floor display bisa naik ke 31.)
        var batas = Math.min(100, Math.floor(s.ceiling));
        if (s.display < batas) {
            s.display = Math.min(100, s.display + 1);
            tampil(s);
            return;
        }
        // Sudah mencapai ceiling: kalau target sudah terkei, kejar cepat.
        if (s.target > s.display) kejar(s);
    }

    /* Mengejar target: +1 tiap 15 ms, atau +2 bila selisih > 10 (tanpa lompat). */
    function tickKejar(s) {
        if (s.sudahTutup || s.dibatalkan) { stopSemuaTimer(s); return; }
        if (s.geser) return;

        var langkah = (s.target - s.display) > 10 ? 2 : 1;
        s.display = Math.min(s.target, s.display + langkah);
        tampil(s);

        if (s.display >= s.target) merayap(s);
    }

    /* Kejar 100%: +1 tiap 12 ms, tahan ~300 ms, lalu "Selesai". */
    function tickFinal(s) {
        if (s.sudahTutup || s.dibatalkan) { stopSemuaTimer(s); return; }
        if (s.geser) return;

        if (s.display < 100) {
            s.display = Math.min(100, s.display + 1);
            tampil(s);
            return;
        }
        stop(s, 'timerFinal');
        s.timerTahan = window.setTimeout(function () { keSelesai(s); }, HOLD_100_MS);
    }

    /* Tanpa Content-Length: naik pelan menuju 90% dengan kurva melambat. */
    function tickEstimasi(s) {
        if (s.sudahTutup || s.dibatalkan || s.geser || s.fase === 'final') return;
        if (s.target >= PLAFON_ESTIMASI) return;
        var sisa = PLAFON_ESTIMASI - s.target;
        var baru = Math.min(PLAFON_ESTIMASI, s.target + Math.max(MIN_LANGKAH, sisa * KURVA_ESTIMASI));
        if (baru > s.target) window.dlProgressSet(baru, MARGIN_BIASA);
    }

    function keSelesai(s) {
        if (s.dibatalkan || s.sudahTutup) return;
        s.fase = 'selesai';

        stop(s, 'timerEstimasi');
        s.geser = false;
        s.button.classList.remove('is-indeterminate');

        s.button.classList.remove('is-running');
        s.button.classList.add('is-done');
        // Teks "Selesai" SAJA, tanpa ikon centang.
        s.parts.label.textContent = TEKS_SELESAI;

        s.timerTahan = window.setTimeout(function () { tutup(s, true); }, HOLD_SELESAI_MS);
    }

    /* ---------- API publik ---------- */

    /** Mulai mode persen dari 0%. Tepatnya: 0% lalu naik ke 1% dalam <=100 ms. */
    window.dlProgressMulai = function (button, margin) {
        if (!button) return null;
        var parts = scaffold(button);
        var labelAwal = (button.dataset.dlLabel || parts.label.textContent).trim();

        button.dataset.dlLabel = labelAwal;
        button.dataset.dlBusy  = '1';
        button.disabled        = true;      // hanya tombol utama yang nonaktif
        button.classList.remove('is-done', 'is-indeterminate');
        button.classList.add('is-running');
        pesanError(button, '');

        var ctrl = new AbortController();
        var s = {
            button: button,
            parts: parts,
            ctrl: ctrl,
            signal: ctrl.signal,
            labelAwal: labelAwal,
            display: 0,
            target: 0,
            ceiling: Math.min(100, margin === undefined ? MARGIN_BIASA : margin),
            fase: 'merayap',
            geser: false,
            dibatalkan: false,
            sudahTutup: false,
            timerMerayap: null,
            timerKejar: null,
            timerFinal: null,
            timerEstimasi: null,
            timerTahan: null,
            url: '',
            selesai: null
        };
        sedang = s;

        // Tampilkan 0% lalu reflow, supaya transisi & animasi mulai bersih.
        setPercent(parts, 0);
        paksaReflow(parts.fill);
        merayap(s);

        return s;
    };

    /** Target baru (persen asli) + ceiling; display mengejar cepat. */
    window.dlProgressSet = function (target, margin) {
        var s = sedang;
        if (!s) return 0;
        s.target  = Math.max(0, Math.min(100, target || 0));
        s.ceiling = Math.min(100, s.target + (margin === undefined ? MARGIN_BIASA : margin));
        if (s.target > s.display) kejar(s);
        return s.display;
    };

    /** Semua data sudah lengkap: kejar 100%, tahan, lalu "Selesai". */
    window.dlProgressFinal = function () {
        var s = sedang;
        if (!s) return false;
        final(s);
        return true;
    };

    /**
     * Mode GESER (indeterminate): dipakai saat kita benar-benar tidak tahu
     * berapa persen (mis. sedang menunggu Retry-After). Lebar bar diatur CSS,
     * jadi style inline dilepas supaya animasi geser tidak tertimpa.
     */
    window.dlProgressGeser = function (aktif) {
        var s = sedang;
        if (!s) return false;
        s.geser = !!aktif;

        if (s.geser) {
            s.button.classList.add('is-indeterminate');
            stop(s, 'timerMerayap');
            stop(s, 'timerKejar');
            stop(s, 'timerFinal');
            stop(s, 'timerEstimasi');
            s.parts.fill.style.width = '';      // biarkan CSS: width 45%
            s.parts.label.textContent = TEKS_GESER;
            paksaReflow(s.parts.fill);
        } else {
            s.button.classList.remove('is-indeterminate');
            paksaReflow(s.parts.fill);
            s.parts.fill.style.width = s.display + '%';
            s.parts.label.textContent = s.display + '%';
            merayap(s);
        }
        return true;
    };

    /** Batalkan unduhan yang sedang jalan. SENYAP: tanpa pesan, bukan kegagalan. */
    window.dlProgressAbort = function () {
        var s = sedang;
        if (!s) return false;
        s.dibatalkan = true;
        try { s.ctrl.abort(); } catch (e) { /* sudah selesai */ }
        tutup(s, false);
        return true;
    };

    /** Kembalikan semua state ke kondisi awal tanpa menghitungnya gagal. */
    window.dlProgressReset = function () {
        var s = sedang;
        if (!s) return false;
        tutup(s, false);
        return true;
    };

    window.dlProgressBusy = function () { return sedang !== null; };

    /* ---------- unduhan ---------- */
    /**
     * Unduh file dengan progres persen di dalam tombol.
     * @param {HTMLElement} button
     * @param {string} url
     * @param {Object}  opts { method, body, headers, filename, csrf }
     * @returns {Promise<boolean>} true bila file tersimpan
     */
    window.downloadWithProgress = function (button, url, opts) {
        opts = opts || {};
        if (!button || button.dataset.dlBusy === '1') return Promise.resolve(false);

        var s = window.dlProgressMulai(button, MARGIN_BIASA);
        if (!s) return Promise.resolve(false);

        // Terapkan mode progress berdasarkan data attribute tombol
        // data-dl-progress-mode="indeterminate" -> bar gelerak tanpa persentase
        // (tanpa attribute -> mode persentase 0% -> 100%)
        if (button.dataset.dlProgressMode === 'indeterminate') {
            window.dlProgressGeser(true);
        }

        s.url = url;
        s.signal = opts.signal || s.ctrl.signal;

        button.dispatchEvent(new CustomEvent('dl-busy-start', {
            detail: { controller: s.ctrl, signal: s.signal, url: url }
        }));

        return new Promise(function (resolve) {
            s.selesaikan = function (sukses) { resolve(sukses); };

            jalankan(s, opts, url).then(function () {
                // Sisa waktu (tahan 100% + "Selesai") ditangani oleh tickFinal().
            }, function (err) {
                if (err && err.name === 'AbortError') {
                    // Dibatalkan: SENYAP. Tidak ada pesan, bukan kegagalan.
                    s.dibatalkan = true;
                    tutup(s, false);
                } else {
                    pesanError(button, 'Gagal: ' + (err && err.message ? err.message : err));
                    tutup(s, false);
                }
            });
        });
    };

    async function jalankan(s, opts, url) {
        var percobaan = 0;

        while (percobaan <= MAKS_RETRY) {
            var headers = {};
            var key;
            for (key in (opts.headers || {})) {
                if (Object.prototype.hasOwnProperty.call(opts.headers, key)) {
                    headers[key] = opts.headers[key];
                }
            }
            if (opts.csrf) headers['X-CSRF-TOKEN'] = opts.csrf;
            headers['X-Requested-With'] = 'XMLHttpRequest';

            var res = await fetch(url, {
                method: opts.method || 'GET',
                body: opts.body,
                credentials: 'same-origin',
                headers: headers,
                signal: s.signal
            });

            // 429 = sementara: baca Retry-After, tunggu, lalu ulangi (maks 3x).
            if (res.status === 429 && percobaan < MAKS_RETRY) {
                percobaan++;
                var d      = parseInt(res.headers.get('Retry-After') || '', 10);
                var tunggu = (isNaN(d) || d <= 0) ? 5 : Math.min(d, 30);
                if (res.body && res.body.cancel) { try { res.body.cancel(); } catch (e) { /* noop */ } }
                // Selama menunggu kita tidak tahu persentasenya -> mode geser.
                window.dlProgressGeser(true);
                await new Promise(function (r) { window.setTimeout(r, tunggu * 1000); });
                window.dlProgressGeser(false);
                if (s.dibatalkan) throw abortError();
                continue;
            }

            var ct = (res.headers.get('Content-Type') || '').toLowerCase();
            if (!res.ok || ct.indexOf('application/json') !== -1) {
                var pesan = 'HTTP ' + res.status;
                try {
                    var j = await res.json();
                    pesan = j.message || pesan;
                } catch (e) { /* bukan JSON */ }
                throw new Error(pesan);
            }

            var total        = parseInt(res.headers.get('Content-Length') || '', 10);
            var punyaPanjang = !isNaN(total) && total > 0;
            var blob;

            if (res.body && typeof res.body.getReader === 'function') {
                var reader   = res.body.getReader();
                var chunks   = [];
                var diterima = 0;

                // PDF yang digenerate on-the-fly tidak punya Content-Length:
                // pakai estimasi bertahap 0 -> 90%, display tetap lewat timer.
                if (!punyaPanjang) {
                    s.timerEstimasi = window.setInterval(function () { tickEstimasi(s); }, ESTIMASI_MS);
                }

                for (;;) {
                    var chunk = await reader.read();
                    if (chunk.done) break;
                    chunks.push(chunk.value);
                    diterima += chunk.value.length;
                    if (punyaPanjang) {
                        // ceiling = target + 3 supaya angka tidak membeku antar chunk.
                        window.dlProgressSet((diterima / total) * 100, MARGIN_BIASA);
                    }
                }
                stop(s, 'timerEstimasi');

                blob = new Blob(chunks, {
                    type: res.headers.get('Content-Type') || 'application/octet-stream'
                });
            } else {
                blob = await res.blob();
            }

            // Respons sudah lengkap: unduh file, lalu kejar 100% -> "Selesai".
            simpanBlob(blob, namaDariHeader(res.headers.get('Content-Disposition'), opts.filename));

            // FORCE PROGRESS KE 100% DAN STOP SEMUA TIMER Secara eksplisit
            // setelah blob terunduh browser, agar progress bar tidak terus berjalan.
            if (s) {
                // Jika masih dalam mode merayap/kejar, force ke final
                if (s.fase !== 'final' && !s.sudahTutup && !s.dibatalkan) {
                    final(s);
                }
                // Force stop semua interval timer untuk mencegah progress terus berjalan
                window.clearInterval(s.timerMerayap);
                s.timerMerayap = null;
                window.clearInterval(s.timerKejar);
                s.timerKejar = null;
                window.clearInterval(s.timerFinal);
                s.timerFinal = null;
                window.clearInterval(s.timerEstimasi);
                s.timerEstimasi = null;
                if (s.timerTahan) { window.clearTimeout(s.timerTahan); s.timerTahan = null; }
                // Pastikan tampilan langsung 100% dan masukkan state selesai
                if (s.display < 100) {
                    s.display = 100;
                    tampil(s);
                }
                s.geser = false;
                s.button.classList.remove('is-indeterminate');
                s.button.classList.remove('is-running');
                s.button.classList.add('is-done');
                s.parts.label.textContent = TEKS_SELESAI;
                s.sudahTutup = true;
                stopSemuaTimer(s);
                if (sedang === s) sedang = null;
                resetTampilan(s);
                s.button.dispatchEvent(new CustomEvent('dl-busy-end', {
                    detail: { url: s.url, sukses: true, dibatalkan: false }
                }));
                if (typeof s.selesaikan === 'function') s.selesaikan(true);
            }
            return;
        }

    function abortError() {
        var e = new Error('dibatalkan');
        e.name = 'AbortError';
        return e;
    }

    /* ---------- pengikatan otomatis ---------- */
    function pasang(el) {
        if (el.dataset.dlBound === '1') return;
        el.dataset.dlBound = '1';

        el.addEventListener('click', function (e) {
            var form = el.closest ? el.closest('form') : null;

            // <a href> atau <button type="button"> -> GET ke href.
            if (el.tagName === 'A' || el.tagName !== 'BUTTON' || el.type !== 'submit') {
                var href = el.getAttribute('href');
                if (!href) return;
                e.preventDefault();
                window.downloadWithProgress(el, href, { method: 'GET' });
                return;
            }

            // <button type="submit"> di dalam form -> kirim isi form via fetch.
            if (!form) return;
            e.preventDefault();
            var body = new FormData(form);
            body.delete('output_format');   // dipilih di server lewat tombol
            window.downloadWithProgress(el, form.getAttribute('action') || window.location.href, {
                method: (form.getAttribute('method') || 'POST').toUpperCase(),
                body: body,
                csrf: csrf()
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var semua = document.querySelectorAll('[data-dl-progress]');
        Array.prototype.forEach.call(semua, pasang);
    });

    // Elemen yang muncul belakangan (baris tabel, modal) juga mengikat diri.
    document.addEventListener('click', function (e) {
        var t = e.target && e.target.closest ? e.target.closest('[data-dl-progress]') : null;
        if (t && t.dataset.dlBound !== '1') pasang(t);
    }, true);
})();
</script>
