{{--
    Komponen BERSAMA: progres DI DALAM tombol unduhan.

    Dipakai semua tombol/link yang mengunduh file (panel admin & guru):
      - Cetak Kartu Presensi Massal   -> "Generate & Unduh"
      - Unduh Template Excel (siswa / guru / kelas / hari libur)
      - Unduh QR Code & Kartu di Detail Siswa
      - Popup Cetak Rekap Presensi (semua mode, kelas CSS yang sama)

     Cara pakai - cukup tambahkan atribut data-dl-progress, markup tombol
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
    /* ----- TOMBOL SEBAGAI WAHAN PROGRES -----
       Warna dasar (--btn-progress-base) dan warna isi bar gelap
       (--btn-progress-fill) DIAMBIL DARI TOMBOLNYA SENDIRI oleh JS lewat
       warnaDasar() + gelap() di bawah. Karena itu tidak ada lagi aturan warna
       per class di sini: tombol biru -> bar biru tua, hijau -> hijau tua,
       merah -> merah tua, dan tidak pernah ada warna putih saat loading. */
    .btn-progress {
        position: relative;
        overflow: hidden;
        white-space: nowrap;
        isolation: isolate;
    }

    /* Warna HANYA dipaksa saat proses benar-benar berjalan (atau selesai).
       Kalau dipaksa sejak awal, aturan !important di sini mengalahkan class
       warna tombol (btn-download-green & sejenis) sehingga tombol yang hijau
       ikut tampil biru sebelum diklik. */
    .btn-progress.is-running,
    .btn-progress.is-done {
        background-color: var(--btn-progress-base, #2563eb) !important;
        background-image: none !important;
        border-color: var(--btn-progress-base, #2563eb) !important;
        color: #fff !important;
        opacity: 1 !important;
    }

    /* ----- LAPISAN ISI BAR PROGRES ----- */
    .btn-progress__fill {
        position: absolute;
        top: 0; left: 0; bottom: 0;
        width: 0%;
        border-radius: inherit;
        background-color: var(--btn-progress-fill, #1e3a8a) !important;
        background-image: none !important;
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
        color: #fff !important;
        font-weight: 700;
        z-index: 1;
        text-shadow: none;
    }

    /* Nonaktif karena sedang jalan: tetap berwarna penuh, bukan putih/pucat. */
    .btn-progress:disabled, .btn-progress[disabled] { opacity: 1 !important; color: #fff !important; }

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

    /* Mode GESER (indeterminate): lebar tetap 45% lalu bergerak menyapu.
       Dipakai saat persentase benar-benar tidak diketahui (mis. menunggu
       Retry-After). JS melepas style inline supaya animasi ini yang menang. */
    .btn-progress.is-indeterminate .btn-progress__fill {
        width: 45%;
        transition: none;
        animation: btnProgressGeser 1.1s ease-in-out infinite;
    }
    @keyframes btnProgressGeser {
        0%   { transform: translateX(-100%); }
        100% { transform: translateX(320%); }
    }

    @media (prefers-reduced-motion: reduce) {
        .btn-progress__fill { transition: none; }
        .btn-progress.is-indeterminate .btn-progress__fill { animation: none; }
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
       Tanpa min-width tetap: tombol Generate dibungkus <form>, jadi hanya
       .dl-actions > .btn yang ikut aturan ini dan kolomnya bisa menyempit. */
    .dl-actions {
        display: flex;
        gap: .5rem;
        width: 100%;
    }
    .dl-actions > .btn {
        flex: 1 1 0;
        min-width: 0;
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

    /* Footer modal cetak: dua kolom sama lebar, satu baris, teks utuh.
       Berlaku juga pada tombol Generate yang dibungkus <form>. */
    .modal-footer {
        display: flex;
        flex-wrap: nowrap;
        gap: .5rem;
    }
    .modal-footer > * { flex: 1 1 0; min-width: 0; }
    .modal-footer form { flex: 1 1 0; min-width: 0; display: flex; }
    .modal-footer form > button,
    .modal-footer > button { width: 100%; }
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
    var KURVA_ESTIMASI  = 0.04;  // target += (90 - target) * 0.04
    var MIN_LANGKAH     = 1;
    var MARGIN_BIASA    = 3;     // ceiling = target + 3 (supaya angka tidak membeku)
    var MARGIN_ESTIMASI = 6;     // ceiling saat estimasi: selalu di depan display

    var sedang = null;   // state unduhan yang sedang berjalan

    /* ---------- kerangka fill + label, dibuat otomatis ---------- */

    /* Versi gelap dari sebuah warna rgb: isi bar selalu lebih gelap dari tombol. */
    function gelap(rgb, f) {
        var m = (rgb || '').match(/\d+/g) || [37, 99, 235];
        return 'rgb(' + [0, 1, 2].map(function (i) { return Math.round(m[i] * (1 - f)); }).join(',') + ')';
    }

    /* Warna dasar tombol.
       (MASALAH D) Kata kunci pada class / id / teks / href SELALU menang atas
       getComputedStyle: template / import / impor / unggah / excel -> hijau,
       danger / hapus -> merah, warning -> oranye. getComputedStyle hanya
       dipakai sebagai cadangan untuk tombol tanpa kata kunci, dan hasil putih
       / transparan tetap ditolak (bar tidak boleh pernah putih). */
    var HIJAU = 'rgb(5,150,105)';
    var MERAH = 'rgb(220,38,38)';
    var ORANJE = 'rgb(245,158,11)';
    var BIRU  = 'rgb(37,99,235)';

    function warnaDasar(btn) {
        var k = ((btn.className || '') + ' ' + (btn.id || '') + ' '
               + (btn.textContent || '') + ' '
               + (btn.getAttribute('href') || '') + ' '
               + (btn.getAttribute('data-bs-target') || '')).toLowerCase();

        if (k.indexOf('merah') > -1 || k.indexOf('red') > -1 || k.indexOf('danger') > -1 || k.indexOf('hapus') > -1) return MERAH;
        if (k.indexOf('orange') > -1 || k.indexOf('oranye') > -1 || k.indexOf('warning') > -1) return ORANJE;
        if (k.indexOf('template') > -1 || k.indexOf('import') > -1 || k.indexOf('impor') > -1
            || k.indexOf('unggah') > -1 || k.indexOf('upload') > -1 || k.indexOf('excel') > -1
            || k.indexOf('green') > -1 || k.indexOf('hijau') > -1 || k.indexOf('success') > -1) return HIJAU;

        // Cadangan: baca warna asli tombol. Class status progres dibuang
        // sementara supaya tidak ikut terukur.
        var simpan = [];
        ['btn-progress', 'is-running', 'is-done'].forEach(function (c) {
            if (btn.classList.contains(c)) { btn.classList.remove(c); simpan.push(c); }
        });
        var c = getComputedStyle(btn).backgroundColor || '';
        simpan.forEach(function (x) { btn.classList.add(x); });

        var m = c.match(/\d+/g) || [];
        var transparan = c === 'transparent' || (m.length === 4 && +m[3] === 0) || m.length === 0;
        var putih = m.length >= 3 && +m[0] > 225 && +m[1] > 225 && +m[2] > 225;
        if (!transparan && !putih) return c;

        // Tidak ada warna & tidak ada kata kunci: biru default.
        return BIRU;
    }

    /* (MASALAH A) Ubah tombol jadi wadah progres TANPA meninggalkan jejak.
       Sebelum apa pun diubah, snapshot lengkap diambil: atribut style utuh,
       className utuh, dan isi (innerHTML) utuh. Setelah proses selesai /
       dibatalkan / gagal, snapshot itu dipulihkan persis - itulah yang membuat
       ukuran tombol kembali sama seperti sebelum diklik. */
    function scaffold(btn) {
        if (!btn._dlAwal) {
            btn._dlAwal = {
                style: btn.getAttribute('style') || '',
                className: btn.className,
                html: btn.innerHTML,
                disabled: btn.disabled
            };
            btn.dataset.dlStyleAwal = btn._dlAwal.style;
            btn.dataset.dlClassAwal = btn._dlAwal.className;
        }

        // (MASALAH D) Warna dihitung ULANG tiap mulai; cache dihapus supaya
        // tombol yang salah di klik pertama tidak terkunci selamanya.
        delete btn.dataset.dlBase;
        var base = warnaDasar(btn);
        btn.dataset.dlBase = base;
        btn.style.setProperty('--btn-progress-base', base);
        btn.style.setProperty('--btn-progress-fill', gelap(base, 0.45));

        // KUNCI UKURAN sebelum isi tombol pindah ke label. Tanpa ini tombol ikut
        // menyusut karena teks aslinya lenyap saat loading (labelnya cuma "42%")
        // dan layout di sekitarnya ikut bergeser.
        var r = btn.getBoundingClientRect();
        if (r.width > 0) {
            btn.style.setProperty('width', r.width + 'px', 'important');
            btn.style.setProperty('min-width', r.width + 'px', 'important');
        }
        if (r.height > 0) {
            btn.style.setProperty('height', r.height + 'px', 'important');
        }

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

    /* Kembalikan tombol ke kondisi SEBELUM diklik: isi, class, dan style utuh.
       Dipakai oleh resetTampilan() sehingga berlaku untuk jalur selesai, abort,
       maupun error - semuanya lewat titik akhir yang sama (tutup()).
       Urutannya penting: isi & class dulu, baru style terakhir, karena
       width/height/min-width hasil kunci hanya boleh dilepas setelah
       tombol sudah kembali ke markup aslinya. */
    function pulihkanTombol(btn) {
        var awal = btn._dlAwal;
        if (!awal) return;

        // 1. Isi tombol kembali persis seperti semula (fill + label lenyap).
        if (btn.innerHTML !== awal.html) btn.innerHTML = awal.html;

        // 2. Class kembali persis (buang btn-progress/is-running/is-done/...),
        //    baru state disabled & penanda busy.
        btn.className = awal.className;
        btn.disabled  = !!awal.disabled;
        delete btn.dataset.dlBusy;

        // 3. Style: snapshot utuh, termasuk padding/height asli yang mungkin
        //    ada. Kalau memang tidak ada atribut style -> hapus, jangan dibuat
        //    string kosong yang bisa mengubah cascade.
        if (awal.style) btn.setAttribute('style', awal.style);
        else btn.removeAttribute('style');

        // 4. Bersihkan penanda yang kita tambahkan.
        delete btn.dataset.dlBase;
        delete btn.dataset.dlStyleAwal;
        delete btn.dataset.dlClassAwal;
        delete btn._dlAwal;
    }

    /* Kembalikan tombol ke kondisi awal: persen, fill, teks, disabled, UKURAN. */
    function resetTampilan(s) {
        // Selesaikan dulu tampilan persen supaya tidak ada kilatan "0%".
        if (s.parts.fill) s.parts.fill.style.width = '0%';
        pulihkanTombol(s.button);
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

        // Tutup modal induk jika unduhan berhasil (hanya saat sukses).
        // Mencari modal terdekat yang mengandung tombol, lalu menutupnya via Bootstrap API.
        if (sukses) {
            var modalEl = s.button.closest('.modal');
            if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                var modal = window.bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                } else {
                    // Fallback jika instance belum diinisialisasi
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    var backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                }
            }
        }

        // Mode import: baru buka halaman hasil SETELAH tampil "Selesai".
        // Kalau gagal / dibatalkan, callback ini tidak dijalankan sama sekali.
        if (sukses && typeof s.setelahSelesai === 'function') {
            var lanjut = s.setelahSelesai;
            s.setelahSelesai = null;
            try { lanjut(); } catch (e) { /* abaikan */ }
        }

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

    /* Mengejar target: +1 tiap 15 ms, atau +2 bila selisih > 10 (tanpa lompat).
       Display TIDAK BOLEH pernah mundur. Estimasi sempat mendorong display
       melewati target (ceiling = target + margin), lalu persen ASLI arrives
       dengan target yang lebih kecil - tanpa penjaga di bawah, angka akan
       mundur dan bar terlihat melompat ke belakang. */
    function tickKejar(s) {
        if (s.sudahTutup || s.dibatalkan) { stopSemuaTimer(s); return; }
        if (s.geser) return;

        if (s.display < s.target) {
            var langkah = (s.target - s.display) > 10 ? 2 : 1;
            s.display = Math.min(s.target, s.display + langkah);
            tampil(s);
        }

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

    /* Tanpa Content-Length (dan tanpa persen asli dari luar): naik pelan menuju
       90% dengan kurva melambat. Ceiling selalu MARGIN_ESTIMASI di depan target,
       sehingga display tidak pernah mandek di angka yang sama. */
    function tickEstimasi(s) {
        if (s.sudahTutup || s.dibatalkan || s.geser || s.fase === 'final') return;
        if (s.persenAsli === true) return;      // sudah dapat persen asli
        if (s.target >= PLAFON_ESTIMASI) return;
        var sisa = PLAFON_ESTIMASI - s.target;
        var baru = Math.min(PLAFON_ESTIMASI, s.target + Math.max(MIN_LANGKAH, sisa * KURVA_ESTIMASI));
        // Pakai setTarget langsung: window.dlProgressSet() akan mematikan timer
        // estimasi ini sendiri karena dianggap "persen asli".
        if (baru > s.target) setTarget(s, baru, MARGIN_ESTIMASI);
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
            persenAsli: false,
            timerMerayap: null,
            timerKejar: null,
            timerFinal: null,
            timerEstimasi: null,
            timerTahan: null,
            url: '',
            selesai: null,
            setelahSelesai: null
        };
        sedang = s;

        // Tampilkan 0% lalu reflow, supaya transisi & animasi mulai bersih.
        setPercent(parts, 0);
        paksaReflow(parts.fill);
        merayap(s);

        /* (MASALAH 1) Estimasi dinyalakan SEKARANG, bukan setelah respons
           datang. Sebelumnya estimate baru hidup setelah fetch selesai, sehingga
           display naik hanya sampai ceiling = 0 + 3 = 3% lalu macet, dan begitu
           respons tiba melompat ke 100%.
           Jalur ZIP Rekap yang mengirim persen ASLI menandai tombolnya dengan
           dataset.dlPersen = 'asli' sehingga estimasi tidak ikut berjalan. */
        if (button.dataset.dlPersen !== 'asli') {
            s.timerEstimasi = window.setInterval(function () { tickEstimasi(s); }, ESTIMASI_MS);
        }

        return s;
    };

    /** Set target + ceiling untuk state yang sedang berjalan (inti dari API publik). */
    function setTarget(s, target, margin) {
        s.target  = Math.max(0, Math.min(100, target || 0));
        s.ceiling = Math.min(100, s.target + (margin === undefined ? MARGIN_BIASA : margin));
        if (s.target > s.display) kejar(s);
        return s.display;
    }

    /** Target baru (persen asli) + ceiling; display mengejar cepat.
        Dipanggil dari luar = ada persen ASLI, jadi estimasi dimatikan. */
    window.dlProgressSet = function (target, margin) {
        var s = sedang;
        if (!s) return 0;
        // Persen asli masuk -> matikan timer estimasi (tidak lagi menebak).
        if (s.persenAsli !== true) {
            s.persenAsli = true;
            stop(s, 'timerEstimasi');
        }
        return setTarget(s, target, margin);
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
                    pesanError(button, ((window.__i18n && window.__i18n.dl_gagal) || 'Gagal') + ': ' + (err && err.message ? err.message : err));
                    tutup(s, false);
                }
            });
        });
    };

    /* (5e) Unggah import lewat XMLHttpRequest karena XHR punya upload.onprogress
       (persen ASLI saat file dikirim), sedangkan fetch tidak.
       Hanya dipakai kalau body-nya FormData yang benar-benar punya File.
       Mengembalikan Promise, atau null kalau tidak cocok (fallback ke fetch). */
    function kirimDgXhr(s, url, opts, headers) {
        if (typeof XMLHttpRequest === 'undefined') return null;
        if (!opts.body || typeof FormData === 'undefined' || !(opts.body instanceof FormData)) return null;

        var adaFile = false;
        try { opts.body.forEach(function (v) { if (v instanceof File) adaFile = true; }); }
        catch (e) { return null; }
        if (!adaFile) return null;

        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open(opts.method || 'GET', url, true);
            xhr.withCredentials = true;
            xhr.responseType = 'arraybuffer';
            Object.keys(headers).forEach(function (h) { xhr.setRequestHeader(h, headers[h]); });

            // Batal = batal: XHR ikut di-abort, tidak ada file tersimpan.
            if (s.signal) {
                if (s.signal.aborted) { reject(abortError()); return; }
                s.signal.addEventListener('abort', function () {
                    try { xhr.abort(); } catch (e) { /* noop */ }
                });
            }

            xhr.upload.onprogress = function (e) {
                if (!e.lengthComputable || !e.total) return;
                s.persenAsli = true;                       // tidak perlu menebak lagi
                stop(s, 'timerEstimasi');
                // Unggahan = maksimal 90%; 100% diserahkan ke final(s).
                setTarget(s, (e.loaded / e.total) * 90, MARGIN_BIASA);
            };

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    // Baca diam-diam: flash message & hasil import tetap tampil
                    // lewat reload/lokasi di bawah, sama seperti sebelumnya.
                    s.setelahSelesai = function () {
                        window.location.href = xhr.responseURL || (url + '#import-selesai');
                    };
                    resolve(true);
                } else {
                    reject(new Error('HTTP ' + xhr.status));
                }
            };
            xhr.onerror   = function () { reject(new Error((window.__i18n && window.__i18n.dl_gagal_upload) || 'Gagal mengunggah file')); };
            xhr.onabort   = function () { reject(abortError()); };
            xhr.ontimeout = function () { reject(new Error((window.__i18n && window.__i18n.dl_waktu_habis) || 'Waktu habis')); };

            xhr.send(opts.body);
        });
    }

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

            // MODE IMPORT: file diunggah, bukan diunduh.
            // Dijalankan SEBELUM fetch supaya tidak ada dua request.
            if (opts.mode === 'import') {
                // Unggahan lewat XMLHttpRequest memberi persen ASLI lewat
                // upload.onprogress - jauh lebih jujur daripada menebak.
                var viaXhr = kirimDgXhr(s, url, opts, headers);
                if (viaXhr) {
                    await viaXhr;   // resolve setelah respons selesai dibaca
                    if (s.dibatalkan) throw abortError();
                    // Tanpa onprogress, estimasi di dlProgressMulai tetap menyala
                    // sampai 90%, lalu final(s) mengejar 100% (+1 tiap 12 ms).
                    final(s);
                    return;
                }
                // Tidak ada File di body -> lanjut ke fetch biasa di bawah.
            }

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

            // MODE IMPORT via fetch (fallback, mis. tanpa File di body). Tidak ada blob
            // yang disimpan ke folder Unduhan; respons dipakai seperti biasa
            // supaya flash message & hasil import tetap tampil.
            if (opts.mode === 'import') {
                // Estimasi sudah menyala sejak dlProgressMulai.
                if (res.body && typeof res.body.getReader === 'function') {
                    var rdr = res.body.getReader();
                    var n   = 0;
                    for (;;) {
                        var piece = await rdr.read();
                        if (piece.done) break;
                        n += piece.value.length;
                        if (punyaPanjang) {
                            window.dlProgressSet((n / total) * 100, MARGIN_BIASA);
                        }
                    }
                } else {
                    await res.arrayBuffer();
                }
                stop(s, 'timerEstimasi');
                if (s.dibatalkan) throw abortError();

                // Respons import: diam-diam dibaca supaya tidak ada error HTML mentah.
                var ctImport = (res.headers.get('Content-Type') || '').toLowerCase();
                var jsonImport = null;
                try {
                    if (ctImport.indexOf('application/json') !== -1) {
                        jsonImport = await res.clone().json();
                    }
                } catch (e) { /* abaikan */ }

                if (jsonImport && jsonImport.message) {
                    pesanError(s.button, jsonImport.message);
                }

                // Kejar 100% -> "Selesai" -> baru buka halaman hasil import.
                // Callback-nya dijalankan oleh tutup() HANYA kalau sukses, jadi
                // pembatalan (tutup(s,false)) tidak pernah memuat ulang halaman.
                if (!s.dibatalkan && !s.sudahTutup) {
                    s.setelahSelesai = function () {
                        if (jsonImport) {
                            if (jsonImport.redirect) window.location.href = jsonImport.redirect;
                            else window.location.reload();
                        } else {
                            window.location.href = (res.url || window.location.href);
                        }
                    };
                    final(s);
                }
                return;
            }

            if (res.body && typeof res.body.getReader === 'function') {
                var reader   = res.body.getReader();
                var chunks   = [];
                var diterima = 0;

                // PDF yang digenerate on-the-fly tidak punya Content-Length:
                // estimasi sudah menyala sejak dlProgressMulai, jadi di sini
                // TIDAK perlu menyalakannya lagi (bakal dobel & bocor timer).
                // Kalau punya Content-Length, dlProgressSet() mematikan estimasi
                // dan mengambil alih dengan persen byte asli.

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
                // Tutup modal induk setelah unduhan selesai (sekitar 300ms supaya persen 100% terlihat)
                setTimeout(function() {
                    var modalEl = s.button.closest('.modal');
                    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                        var modal = window.bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        } else {
                            modalEl.classList.remove('show');
                            modalEl.style.display = 'none';
                            document.body.classList.remove('modal-open');
                            var backdrop = document.querySelector('.modal-backdrop');
                            if (backdrop) backdrop.remove();
                        }
                    }
                }, 300);
                if (typeof s.selesaikan === 'function') s.selesaikan(true);
            }
            return;
        }
    }

    function abortError() {
        var e = new Error('dibatalkan');
        e.name = 'AbortError';
        return e;
    }

    /* ---------- BATAL = BATAL ----------
       Satu listener delegasi untuk SELURUH modal: tombol Batal/Hentikan, tombol
       X, klik di luar modal, dan Esc semuanya melewati .modal. Efeknya sama:
       fetch di-ABORT, tidak ada file tersimpan, tidak ada pesan sukses/error,
       tidak ada "Selesai", tombol kembali ke bentuk awal & bisa diklik lagi. */
    function batalkanJikaBusy() {
        if (window.dlProgressBusy && window.dlProgressBusy()) {
            window.dlProgressAbort();
            return true;
        }
        return false;
    }
    document.addEventListener('click', function (e) {
        var pemicu = e.target && e.target.closest ? e.target.closest('[data-bs-dismiss="modal"]') : null;
        if (pemicu) batalkanJikaBusy();
    }, true);
    document.addEventListener('hide.bs.modal', function (e) {
        batalkanJikaBusy();
    });
    document.addEventListener('hidden.bs.modal', function (e) {
        batalkanJikaBusy();
    });

    /* ---------- pengikatan otomatis ---------- */
    function pasang(el) {
        if (el.dataset.dlBound === '1') return;
        el.dataset.dlBound = '1';
        el.setAttribute('data-dl-progress', '');

        // <button type="submit" data-import> -> FormData POST, mode import.
        if (el.hasAttribute('data-import')) {
            el.addEventListener('click', function (e) {
                var f = el.closest ? el.closest('form') : null;
                if (!f) return;
                e.preventDefault();
                var fd = new FormData(f);
                fd.delete('output_format');
                window.downloadWithProgress(el, f.getAttribute('action') || window.location.href, {
                    method: (f.getAttribute('method') || 'POST').toUpperCase(),
                    body: fd,
                    csrf: csrf(),
                    mode: 'import'
                });
            });
            return;
        }

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
        var semua = document.querySelectorAll('[data-dl-progress], [data-import]');
        Array.prototype.forEach.call(semua, pasang);
    });

    // Elemen yang muncul belakangan (baris tabel, modal) juga mengikat diri.
    document.addEventListener('click', function (e) {
        var t = e.target && e.target.closest ? e.target.closest('[data-dl-progress], [data-import]') : null;
        if (t && t.dataset.dlBound !== '1') pasang(t);
    }, true);
})();
</script>
