@extends('layouts.app')

@section('title', is_admin() ? __('Dashboard Admin') : (is_piket() ? __('Dashboard Guru Piket') : __('Dashboard Guru')))
@section('page_title', is_admin() ? __('Dashboard Administrator') : (is_piket() ? __('DASHBOARD GURU PIKET') : __('DASHBOARD GURU')))


{{-- Kanvas dikunci setinggi satu layar (sama seperti halaman acuan Presensi /
     Data Guru / Data Kelas) dan isi dashboard menggulir di dalam kanvas.
     Aturan class ini ada di layout bersama, layouts/app.blade.php. --}}
@section('canvas_class', 'page-canvas-fixed')

@section('page_header_right')
<!-- Tanggal di Sisi Kanan Modern
     Blok ini bisa diklik: ikon kalender + blok teks dibungkus <form> GET
     yang memuat <input type="date"> (pola yang sudah dipakai di halaman lain).
     Memilih tanggal hanya submitting GET ke halaman dashboard yang sama dengan
     ?tanggal=YYYY-MM-DD, lalu controller menghitung ulang seluruh statistik,
     grafik, dan kehadiran. Tampilan (ikon, teks, ukuran, warna) TIDAK diubah. -->
<form method="GET" action="{{ panel_route('dashboard') }}" class="dashboard-date-form d-none d-lg-flex align-items-center gap-2.5 text-end">            {{-- Mode grafik & filter status ikut dipertahankan saat tanggal diganti --}}
            <input type="hidden" name="mode" value="{{ $chartMode ?? 'harian' }}">
            @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

    <div class="dashboard-date-text">
        <span class="text-secondary fw-semibold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.05em;">{{ __($dateLabel ?? 'Tanggal Hari Ini') }}</span>
        <strong class="text-dark fw-bold d-block" style="font-size: 0.88rem;">
            {{-- Tanggal mengikuti bahasa aktif. Controller membungkus tanggal dengan
                 Carbon locale 'id', jadi locale diterapkan langsung ke instance
                 di sini (tidak mengubah controller):
                 "Minggu, 18 Oktober 2026" (id) -> "Sunday, 18 October 2026" (en).
                 Format 'l, d F Y' TIDAK diubah supaya mode Bahasa Indonesia
                 tetap persis sama seperti sebelumnya. --}}
            {{ app()->getLocale() === 'en'
                ? \Illuminate\Support\Carbon::parse($selectedDate ?? $dateString)->locale('en')->translatedFormat('l, d F Y')
                : ($todayFormatted ?? \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y')) }}
        </strong>
    </div>

    {{-- Input date disembunyikan (bukan diklik langsung); ikon kalender yang jadi pemicu --}}
    <input type="date" name="tanggal" value="{{ $dateString }}"
           class="dashboard-date-input" tabindex="-1" aria-hidden="true">

    <button type="button" class="dashboard-date-trigger" title="{{ __('Pilih tanggal') }}" aria-label="{{ __('Pilih tanggal') }}">
        <i class='bx bx-calendar text-primary fs-3'></i>
    </button>
</form>

{{-- Kembali ke Hari Ini: hanya tampil bila tanggal yang dipilih bukan hari ini --}}            @if(!($isToday ?? true))            <a href="{{ panel_route('dashboard', array_filter(['mode' => $chartMode ?? 'harian', 'status' => request('status')])) }}"
                   class="btn btn-light border text-dark fw-semibold rounded-2 px-2.5 py-1 d-none d-lg-inline-flex align-items-center gap-1 dashboard-reset-today"
                   style="font-size: 0.78rem;" title="{{ __('Kembali ke data hari ini') }}">
                    <i class='bx bx-undo'></i> {{ __('Hari Ini') }}
                </a>
            @endif
        @endsection

@push('styles')
<style>
    /* ==================================================================
       DASHBOARD - CLEAN LOOK (versi sebelum redesign terakhir)
       Kanvas & wrapper TIDAK diubah (putih, lengkung, sama seperti halaman
       lain). Semua bagian tampil di atas putih: tanpa kartu abu, tanpa
       border, tanpa garis, tanpa shadow pada kontainer. Pemisah antar
       bagian hanya jarak kosong 24px (HP) + judul bagian yang tebal.

       Pengecualian satu-satunya yang boleh berborder: grup tab
       Harian/Mingguan/Bulanan (.dash-seg).
       ================================================================== */

    /* Jarak antar bagian di HP. Padding kiri-kanan TIDAK disentuh sehingga
       tetap sama seperti halaman lain (dari <main> layout + --m-inset). */
    body .content-scroll-wrapper > main:has(#attendanceLineChart) > .flex-1 {
        gap: 32px !important;
    }

    /* Penjaga: isi dashboard tidak boleh tertekan/terpotong (terutama blok
       Statistik paling atas) saat area scroll menjadi kolom flex di HP. */
    .dashboard-section {
        flex: 0 0 auto;
    }

    /* Jarak seragam antar bagian = 32px.
       - HP: jarak datang dari gap area scroll di atas; untuk baris yang berisi
         Grafik + Kehadiran (satu blok), jarak internalnya 32px juga.
       - Desktop: jarak dari margin-bottom tiap section. */
    @media (max-width: 640px) {
        body .dashboard-section.row:not(.dashboard-stat-row) {
            row-gap: 2rem !important;
        }
    }
    @media (min-width: 641px) {
        body .dashboard-section {
            margin-bottom: 2rem !important;
        }
    }

    .text-purple {
        color: #9333ea !important;
    }

    /* ==================================================================
       TOKEN SATU SUMBER untuk semua bagian dashboard
       ================================================================== */
    body .dashboard-section {
        --dash-card-r: 18px;
        --dash-card-bg: #ffffff;            /* semua bagian: PUTIH        */
        --dash-stroke: transparent;         /* kontainer: tanpa garis     */
        --dash-stroke-hover: #cbd5e1;
        --dash-card-shadow: none;           /* tanpa shadow               */
        --dash-divider: transparent;        /* statistik: tanpa pemisah   */
        --dash-inner-bg: #ffffff;
        --dash-inner-stroke: #e2e8f0;       /* garis tipis elemen kecil   */
        --dash-line: #e5e9f0;
        --dash-accent: #2563eb;
        --dash-title-weight: 700;           /* judul section selalu bold */
    }

    /* Kotak Grafik / Kehadiran / Monitoring / Jadwal: putih, tanpa
       garis, tanpa shadow, dan tanpa padding luar. ISI tidak diubah. */
    body .dashboard-section .dashboard-box,
    body .dashboard-section .dashboard-monitor-box {
        padding: 0 !important;
        border: 0;
        box-shadow: none;
        outline: 0;
    }

    /* Judul kotak sejajar kiri-kanan: sama font, tebal, margin-top,
       padding-top di desktop dan mobile. */
    .dashboard-section .dashboard-box-title {
        font-size: var(--dash-title-size) !important;
        font-weight: var(--dash-title-weight) !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        padding-top: 0 !important;
    }

    /* Kartu pembungkus Statistik & Pintasan Cepat (tanpa garis/shadow) */
    .dashboard-section .dash-card {
        background-color: var(--dash-card-bg);
        padding: 0;
        min-width: 0;
    }

    /* ------------------------------------------------------------------
       BAGIAN 1: STATISTIK - memakai grid yang SAMA PERSIS dengan grid
       Pintasan Cepat (jumlah kolom, lebar kolom, dan gap identik) dan
       konten tiap sel RATA TENGAH di kolomnya, sehingga titik tengah
       angka statistik lurus vertikal dengan titik tengah kolom ikon di
       bawahnya. Tanpa ikon, tanpa kotak, dan tanpa garis pemisah.
       ------------------------------------------------------------------ */
    .dashboard-section .dash-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        column-gap: 0.5rem;
    }

    .dashboard-section .dash-stat-cell {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        min-width: 0;
        /* keterangan kecil dihapus, jadi padding bawah 0 supaya tidak ada
           ruang kosong sisa sebelum judul "Pintasan Cepat". */
        padding: 0.875rem 0 0;
        color: #0f172a;
        text-decoration: none;
        -webkit-tap-highlight-color: transparent;
        /* Kartu statistik TIDAK PERNAH jadi kotak: tanpa background, tanpa
           border, tanpa bayangan, tanpa outline - di keadaan normal, hover,
           focus, maupun active. Efeknya hanya "naik sedikit" (lihat di
           bawah), persis seperti kartu Pintasan Cepat. */
        background: transparent !important;
        background-color: transparent !important;
        background-image: none !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        outline: none !important;
    }

    /* Kartu statistik yang berupa tautan: pointer + efek hover halus
       (angka & label naik sedikit). TIDAK ada kotak/bayang. */
    .dashboard-section a.dash-stat-cell,
    .dashboard-section a.dash-stat-cell:hover,
    .dashboard-section a.dash-stat-cell:visited {
        cursor: pointer;
        color: inherit;
        text-decoration: none;
        background: transparent !important;
        background-color: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
    .dashboard-section a.dash-stat-cell .dash-stat-value,
    .dashboard-section a.dash-stat-cell .dash-stat-label {
        transition: transform 0.15s ease;
    }
    @media (hover: hover) and (pointer: fine) {
        .dashboard-section a.dash-stat-cell:hover .dash-stat-value,
        .dashboard-section a.dash-stat-cell:hover .dash-stat-label {
            transform: translateY(-2px);
        }
    }

    .dashboard-section .dash-stat-label {
        display: block;
        font-size: 0.64rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #0f172a;           /* label: hitam pekat, bukan abu */
        line-height: 1.25;
        margin-bottom: 0;        /* label kini elemen terakhir */
    }

    .dashboard-section .dash-stat-value,
    .dashboard-section .dash-att-value {
        display: block;
        font-size: 1.75rem;         /* 28px di HP, 32px di desktop */
        font-weight: 800;           /* angka besar & tebal */
        letter-spacing: -0.02em;
        color: #0f172a;             /* angka: hitam pekat, sama seperti label */
        line-height: 1.1;
        margin-bottom: 0.25rem;     /* jarak rapat angka -> label (4px) */
    }

    /* Palet warna angka dashboard - dipakai SAMA PERSIS di bagian "Data Sekolah"
       dan "Kehadiran Hari Ini" (satu biru, satu kuning, satu hijau, satu
       merah). Class KHUSUS elemen dashboard ini; definisi class bersama
       (text-danger, text-primary, text-purple, dll) tidak disentuh. */
    /* Sumber warna status seragam dengan Rekap Siswa (cell-present/late/sick/permission/alpha).
       Digunakan oleh Data Sekolah dan Kehadiran Hari Ini. */
    .dashboard-section .dash-stat-value.is-sidebar-blue,
    .dashboard-section .dash-att-value.is-sidebar-blue {
        color: #2563eb !important;   /* biru = Sakit (sama dengan cell-sick di Rekap) */
    }
    .dashboard-section .dash-stat-value.is-yellow,
    .dashboard-section .dash-att-value.is-yellow {
        color: #7e22ce !important;   /* ungu = Izin (sama dengan cell-permission di Rekap) */
    }
    .dashboard-section .dash-stat-value.is-green,
    .dashboard-section .dash-att-value.is-green {
        color: #16a34a !important;   /* hijau = Hadir (sama dengan cell-present di Rekap) */
    }
    .dashboard-section .dash-stat-value.is-orange,
    .dashboard-section .dash-att-value.is-orange {
        color: #d97706 !important;   /* oranye = Terlambat (sama dengan cell-late di Rekap) */
    }
    .dashboard-section .dash-stat-value.is-red,
    .dashboard-section .dash-att-value.is-red {
        color: #ef4444 !important;   /* merah = Alfa */
    }

    /* Label Kehadiran Hari Ini: warna sinkron dengan angka status di atas. */
    .dashboard-section .absence-label-hadir   { color: #16a34a !important; }   /* hijau */
    .dashboard-section .absence-label-terlambat { color: #d97706 !important; } /* oranye */
    .dashboard-section .absence-label-sakit   { color: #2563eb !important; }   /* biru */
    .dashboard-section .absence-label-izin    { color: #7e22ce !important; }   /* ungu */
    .dashboard-section .absence-label-alfa    { color: #ef4444 !important; }   /* merah */


    /* Jam sealang dengan angka statistik: label & nilai sama ukuran & bobot. */
    .dashboard-section .dashboard-clock .clock-label {
        font-size: 0.9rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.05em !important;
        text-transform: uppercase !important;
    }
    .dashboard-section .dashboard-clock .clock-value {
        font-size: 1.75rem !important;
        font-weight: 800 !important;
        line-height: 1.1 !important;
        letter-spacing: -0.02em !important;
        margin-top: 0.25rem !important;
    }

    @media (min-width: 768px) {
        /* 3 kolom sama lebar, isi rata tengah, tampil seimbang di tengah.
           Padding kiri/kanan sel 0 supaya sejajar dengan isi konten. */
        .dashboard-section .dash-stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            column-gap: 1rem;
        }
        .dashboard-section .dash-stat-cell {
            padding: 1.35rem 0 0;
        }
        .dashboard-section .dash-stat-label {
            font-size: 0.7rem;
        }
        .dashboard-section .dash-stat-value,
        .dashboard-section .dash-att-value {
            font-size: 2rem;            /* 32px */
        }
    }

    /* Tanpa background sama sekali (bersih): statistik & item Pintasan Cepat
       tidak lagi memunculkan kotak biru muda saat hover / ditekan.
       Ganti efeknya: ikon Pintasan Cepat naik sedikit & membesar. */
    @media (hover: hover) and (pointer: fine) {
        .dashboard-section .dash-shortcut-item:hover .dash-sc-icon {
            transform: translateY(-2px) scale(1.05);
        }
    }
    .dashboard-section .dash-shortcut-item:focus-visible {
        outline: 2px solid var(--dash-accent);
        outline-offset: 2px;
    }

    /* Fokus keyboard pada kartu Data Sekolah: halus, TANPA kotak tebal.
       Angka naik sedikit & label diberi garis bawah tipis. */
    .dashboard-section a.dash-stat-cell:focus-visible {
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    .dashboard-section a.dash-stat-cell:focus-visible .dash-stat-value {
        transform: translateY(-2px);
    }
    .dashboard-section a.dash-stat-cell:focus-visible .dash-stat-label {
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    /* ------------------------------------------------------------------
       BAGIAN 2: PINTASAN CEPAT - grid ikon 4 kolom (2 baris x 4 kolom).
       Tanpa kotak/border per item: hanya wadah ikon rounded-square dengan
       latar lembut + satu label pendek di bawahnya.
       ------------------------------------------------------------------ */
    /* Judul "Pintasan Cepat" harus rata kiri, sejajar dengan judul
       "Monitoring & Analitik Presensi" dan "Grafik Garis Kehadiran".
       Ikon petir digeser ke ujung kanan (absolut) supaya tidak mendorong
       teks judul ke kanan. Isi shortcut (ikon + label) tidak diubah. */
    .dashboard-section .dash-shortcut-head {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.875rem;
        padding-right: 2.25rem;
    }

    .dashboard-section .dash-shortcut-head > i {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
    }

    /* Catatan: jarak antar bagian kini seragam 32px dan diatur global di blok
       atas (gap area scroll untuk HP, margin-bottom section untuk desktop),
       jadi tidak perlu aturan khusus untuk blok statistik lagi. */

    .dashboard-section .dash-card-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
    }

    .dashboard-section .dash-shortcut-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        row-gap: 0.875rem;
        column-gap: 0.25rem;
    }

    .dashboard-section .dash-shortcut-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.45rem;
        min-width: 0;
        padding: 0.3rem 0.125rem;
        border-radius: 14px;
        text-decoration: none;
        color: inherit;
        -webkit-tap-highlight-color: transparent;
        transition: background-color 0.15s ease;
    }

    /* Wadah ikon: warna lembut disuntikkan per item lewat --sc-soft */
    .dashboard-section .dash-sc-icon {
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: var(--sc-soft, #dbeafe);
        border-radius: 14px;
        transition: transform 0.18s ease;
    }
    .dashboard-section .dash-sc-icon i {
        font-size: 1.45rem;
        line-height: 1;
    }

    .dashboard-section .dash-sc-label {
        font-size: 0.66rem;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.25;
        text-align: center;
        max-width: 100%;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        overflow-wrap: break-word;
    }

    @media (min-width: 768px) {
        .dashboard-section .dash-shortcut-head {
            margin-bottom: 1rem;
        }
        .dashboard-section .dash-shortcut-grid {
            row-gap: 1.25rem;
            column-gap: 0.5rem;
        }
        .dashboard-section .dash-sc-icon {
            width: 52px;
            height: 52px;
            flex: 0 0 52px;
        }
        .dashboard-section .dash-sc-icon i {
            font-size: 1.65rem;
        }
        .dashboard-section .dash-sc-label {
            font-size: 0.72rem;
        }
    }

    /* ------------------------------------------------------------------
       BARIS KEHADIRAN & KOTAK JAM - tanpa kartu: tanpa background,
       border, outline, dan shadow. Yang membedakan hanya jarak kosong.
       ------------------------------------------------------------------ */
    .dashboard-section .dashboard-inner-card,
    .dashboard-section .absence-row-interactive,
    .dashboard-section .dashboard-clock {
        background-color: transparent;
        border: 0;
        outline: 0;
        box-shadow: none;
    }

    /* Angka + label (Hadir/Sakit/Izin/Alfa) TIDAK bisa diklik, jadi
       tidak ada lagi pointer maupun efek hover di baris tersebut. */
    .absence-row-interactive {
        transition: background-color 0.15s ease;
    }

    .dashboard-section .absence-row-inner {
        gap: 0.75rem !important;
    }

    /* Lima item Hadir/Terlambat/Sakit/Izin/Alfa: SEJAJAR 5 KOLOM di HP maupun desktop.
       Isi tiap item vertikal, rapat, dan rata tengah. */
    .dashboard-section .dash-absence-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.75rem;
    }
    @media (min-width: 992px) {
        .dashboard-section .dash-absence-grid {
            gap: 1rem;
        }
    }

    /* Angka Kehadiran Hari Ini: font tebal SAMA persis dengan angka Data Sekolah. */
    .dashboard-section .dash-att-figure {
        margin-bottom: 0.25rem;
    }

    /* ==================================================================
       BAGIAN BAWAH - "Kehadiran Hari Ini" (kiri) dan "Jadwal
       Operasional Sekolah" (kanan) jadi DUA KOLOM SEJAJAR di desktop
       (md ke atas) dan tetap BERTUMPUK di HP.

       Tata letaknya sendiri memakai utility Tailwind yang sudah ada di
       halaman ini ("grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8
       items-stretch" pada .dash-bottom-grid dan "flex flex-col h-full
       min-w-0" pada .dash-col), sehingga:
         - HP (<768px) : 1 kolom, jarak antar bagian 32px (gap-y-8).
         - >=768px      : 2 kolom sama lebar, jarak 48px (gap-x-12).
       Yang diatur DI BAWAH ini hanya detail yang tidak terjangkau Tailwind:
       judul sejajar, tombol, badge, dan jam.

       PENTING: semua selector di blok ini memakai
       "body .dashboard-section .<class>". Class .dashboard-section WAJIB
       ada pada INDUK elemen (.dashboard-section > .dash-bottom-grid
       > .dash-col > .card). Bila .dashboard-section diletakkan pada
       elemen yang sama, selector tidak akan pernah cocok.

       Tidak ada border / shadow / background abu-abu pada kontainer:
       gaya clean look (putih polos) tetap, seperti bagian lain.
       ================================================================== */

    /* Judul + subjudul tiap kolom. min-height dipakai agar judul kedua
       kolom (yang tidak punya subjudul) tetap SEJAJAR pada satu garis
       atas dengan judul kolom kiri, sehingga isi kedua kolom mulai pada
       garis yang sama. */
    body .dashboard-section .dash-col-head {
        padding-bottom: 0 !important;
        margin-bottom: 1.25rem !important;
        min-height: 2.6rem;
        align-items: flex-start !important;
    }

    /* Kartu di dalam kolom mengisi tinggi kolom, sehingga tombolnya
       benar-benar bisa terdorong ke dasar (mt-auto pada markup). */
    body .dashboard-section .dash-bottom-grid > .dash-col > .card {
        flex: 1 1 auto;
    }

    /* Dua tombol biru: 40px, radius 10px, font 12px bold kapital, dan
       selebar kolomnya saja (BUKAN selebar layar). Identik di HP dan
       desktop sehingga keduanya selalu sejajar. */
    body .dashboard-section .dash-col-btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        height: 40px !important;
        min-height: 40px !important;
        margin: 0 !important;
        padding: 0 1rem !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        line-height: 1;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        color: #ffffff !important;
        border-radius: 10px !important;
        box-sizing: border-box;
        white-space: nowrap;
        text-decoration: none;
    }

    /* Jam besar + "WIB" dalam SATU BARIS (WIB sedikit lebih kecil)
       memakai font tebal yang SAMA PERSIS dengan angka Data Sekolah (bukan monospace). */
    body .dashboard-section .dashboard-clock .clock-value {
        margin-top: 0.2rem !important;
        line-height: 1.15;
        white-space: nowrap;
        font-weight: 800 !important;
        letter-spacing: -0.02em;
    }
    body .dashboard-section .dashboard-clock .clock-wib {
        font-size: 0.62em;
        font-weight: 700;
        letter-spacing: 0.02em;
        margin-left: 0.35rem;
        vertical-align: baseline;
    }

    /* Di 2 kolom (>=768px) tiap kolom hanya setengah lebar konten, jadi
       angka jam sama ukurannya dengan angka statistik (tidak lebih besar). */
    @media (min-width: 768px) {
        body .dashboard-section .dashboard-clock .clock-value {
            font-size: 2rem !important;
        }
    }
       3 statistik "Data Sekolah" SEJAJAR satu baris (3 kolom sama lebar)
       dan 5 item "Kehadiran Hari Ini" sejajar satu baris (5 kolom),
       dengan angka & label yang mengecil supaya muat di layar 320-500px
       tanpa scroll horizontal. Desktop (md ke atas) TIDAK tersentuh: nilai
       aslinya tetap dipakai lewat blok min-width 768px / 992px di atas.
       Blok ini diletakkan SESUDAH aturan dasar karena specificity-nya sama.
       ================================================================== */
    @media (max-width: 767.98px) {
        /* Data Sekolah: 3 kolom sejajar rapat; align-items start supaya semua
           angka berada pada satu garis walau label 2 baris. */
        .dashboard-section .dash-stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            column-gap: 0.25rem;
            align-items: start;
        }
        .dashboard-section .dash-stat-cell {
            padding: 0.875rem 0.25rem 0;   /* padding kolom kecil */
        }
        .dashboard-section .dash-stat-value,
        .dashboard-section .dash-att-value {
            font-size: 1.375rem;            /* 22px */
            line-height: 1.1;
        }
        .dashboard-section .dash-stat-label {
            font-size: 0.5625rem;          /* 9px: muat "ROMBONGAN" di 320px */
            letter-spacing: 0.02em;
            line-height: 1.2;
            min-height: 1.5em;              /* 2 baris seragam => rata */
            text-wrap: balance;
            overflow-wrap: break-word;
        }

        /* Kehadiran Hari Ini: 5 kolom sama lebar, gap rapat 4px, label satu baris tanpa pecah. */
        .dashboard-section .dash-absence-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.25rem;
            align-items: start;
        }
        /* Kurangi padding horizontal tiap item agar label lebih longgar di kolom sempit. */
        .dashboard-section .dash-absence-grid > .dashboard-inner-card {
            padding-left: 0.125rem !important;
            padding-right: 0.125rem !important;
        }
        /* Angka: 20px di mobile. */
        .dashboard-section .dash-absence-grid .dash-att-value {
            font-size: 1.25rem !important;   /* 20px */
            line-height: 1.1;
        }
        /* Label: 11px di mobile, nowrap untuk mencegah "Terlambat" pecah baris.
           Target class spesifik absence-label-* agar inline style tidak bisa override. */
        .dashboard-section .dash-absence-grid [class^="absence-label-"],
        .dashboard-section .dash-absence-grid [class*=" absence-label-"] {
            font-size: 0.6875rem !important;   /* 11px */
            font-weight: 700 !important;
            letter-spacing: 0 !important;
            line-height: 1.2 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            min-height: 1.2em !important;
            display: block !important;
        }
        /* Fallback: target fw-bold langsung di dalam absence-grid. */
        .dashboard-section .dash-absence-grid .fw-bold {
            font-size: 0.6875rem !important;   /* 11px */
            white-space: nowrap !important;
        }

        /* Jadwal Operasional: judul + tanggal pendek SEBARIS di mobile,
           keduanya bold, ukuran sama, dipisah tanda strip, tanpa wrap. */
        .dashboard-section .schedule-title {
            display: inline !important;
            font-size: 0.875rem !important;   /* 14px */
            font-weight: 700 !important;
            white-space: nowrap !important;
        }
        .dashboard-section .schedule-date {
            display: inline !important;
            font-size: 0.875rem !important;   /* 14px, sama dengan judul */
            font-weight: 700 !important;       /* bold, samakan dengan judul */
            color: #0f172a !important;         /* gelap, sama dengan judul */
            line-height: 1.2 !important;
            margin-top: 0 !important;          /* satu baris dengan judul */
            white-space: nowrap !important;
        }
        /* Jarak rapi antara baris judul/tanggal dan blok jam di bawahnya. */
        .dashboard-section .dash-clock-grid {
            margin-top: 0.5rem !important;   /* 8px */
        }

        /* Jam HP: sama ukuran dengan statistik HP. */
        .dashboard-section .dashboard-clock .clock-label {
            font-size: 0.5625rem !important;
        }
        .dashboard-section .dashboard-clock .clock-value {
            font-size: 1.375rem !important;
        }
    }

    /* Layar sangat sempit (~360px): label kehadiran diperkecil jadi 10px. */
    @media (max-width: 375px) {
        .dashboard-section .dash-absence-grid [class^="absence-label-"],
        .dashboard-section .dash-absence-grid [class*=" absence-label-"] {
            font-size: 0.625rem !important;  /* 10px */
        }
        .dashboard-section .dash-absence-grid .fw-bold {
            font-size: 0.625rem !important;
        }
        .dashboard-section .schedule-title,
        .dashboard-section .schedule-date {
            font-size: 0.8rem !important;  /* ~13px */
        }
    }

    /* ==================================================================
       JADWAL OPERASIONAL: label "JAM MASUK" / "BATAS TERLAMBAT" TIDAK BOLEH
       PECAH/TUMPUK jadi 2 baris, sehingga angka jam di kedua kartu SELALU
       sejajar pada satu garis horizontal (mis. saat layar di-split).
       - Label dipaksa 1 baris (nowrap), ukurannya mengecil mengikuti lebar
         kolom (container query: .dash-col jadi container),
       - Isi tiap kartu rata ke atas, jadi label & angka mulai dari garis sama.
       - Angka jam + WIB tetap 1 baris dan ikut mengecil halus bila sempit.
       ================================================================== */
    body .dashboard-section .dash-bottom-grid > .dash-col {
        container-type: inline-size;
    }
    body .dashboard-section .dash-clock-grid {
        align-items: flex-start !important;
    }
    body .dashboard-section .dashboard-clock {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: flex-start !important;
        min-width: 0;
    }
    body .dashboard-section .dashboard-clock .clock-label {
        white-space: nowrap !important;
        line-height: 1.2 !important;
        letter-spacing: 0 !important;
        text-align: center;
    }
    body .dashboard-section .dashboard-clock .clock-value {
        white-space: nowrap !important;
        line-height: 1.15 !important;
    }
    @media (min-width: 768px) {
        body .dashboard-section .dashboard-clock {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        body .dashboard-section .dashboard-clock .clock-label {
            font-size: clamp(0.5rem, 3.6cqw, 0.7rem) !important;
        }
        body .dashboard-section .dashboard-clock .clock-value {
            font-size: clamp(1.25rem, 10cqw, 2rem) !important;
        }
    }

    /* Desktop (>=768px): hanya judul Jadwal Operasional yang tampil, tanggal disembunyikan
       (sudah ditampilkan di header pojok kanan atas). */
    @media (min-width: 768px) {
        .dashboard-section .schedule-title {
            display: inline !important;
        }
        .dashboard-section .schedule-date {
            display: none !important;
        }
    }

    /* Baris kehadiran: TIDAK ada efek hover lagi (baris ini hanya
       teks, bukan tautan). Yang tersisa adalah penanda "sedang difilter"
       (.absence-row-active) - itu status, bukan efek klik. */
    .dashboard-section .absence-row-active {
        background-color: rgba(37, 99, 235, 0.05);
        border: 0;
        box-shadow: none;
        outline: 0;
        transform: none;
    }

    /* ===== PENAKLUK BORDER DARI CSS LAYOUT (khusus 6 elemen) =====
       Definisi class di layouts/app.blade.php tidak diubah (dipakai bersama),
       dan <style> layout dimuat SESUDAH style push halaman, jadi aturannya
       tetap menang di browser:
         .operasional-card-interactive { border: 1px solid var(--clean-line) !important }
         .absence-row-interactive      { border: 1px solid var(--clean-line) !important }
         .card                         { box-shadow: 0 1px 2px rgba(15,23,42,.06) !important }
       Class .dash-nobox hanya dipakai pada 6 elemen tersebut (baris
       Hadir/Sakit/Izin/Alfa + 2 kotak jam), jadi halaman lain tidak ikut berubah. */
    body .dashboard-section .dash-nobox,
    body .dashboard-section .dash-nobox:hover,
    body .dashboard-section .dash-nobox:focus,
    body .dashboard-section .dash-nobox:active,
    body .dashboard-section .dash-nobox.absence-row-active {
        border: none !important;
        border-width: 0 !important;
        border-style: none !important;
        border-color: transparent !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
        background-color: transparent !important;
    }
    body .dashboard-section .dash-nobox:focus-visible {
        outline: 2px solid var(--dash-accent) !important;
        outline-offset: 2px !important;
    }

    /* Label kecil "Aktif" pada baris yang sedang difilter */
    .dashboard-section .absence-row-active-badge {
        display: inline-block;
        background: #2563eb;
        color: #ffffff;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 600;
        padding: 1px 8px;
        margin-left: 6px;
        vertical-align: middle;
        letter-spacing: 0.04em;
    }

    /* Garis pemisah di bawah judul bagian: dihapus, jarak tetap dijaga margin. */
    .dashboard-section .dashboard-divider-off {
        border-bottom: 0 !important;
        padding-bottom: 0 !important;
    }

    /* ------------------------------------------------------------------
       TATA LETAK UNTUK LEBAR >= 641px (desktop & tablet)
       Inset horizontal tiap blok dikembalikan normal (1rem / 1.5rem),
       sama seperti tampilan awal.
       ------------------------------------------------------------------ */
    @media (min-width: 641px) {
        .dashboard-section {
            --dash-radius: 1rem;
            --dash-gap: 1.5rem;
            --dash-title-size: 0.95rem;
            --dash-title-weight: 700;
            --dash-title-color: inherit;
            --dash-sub-size: 0.7rem;
            --dash-pad-x: 1rem;
            --dash-control-radius: 6px;
            /* SATU CONTAINER BERSAMA: semua bagian memakai inset kiri-kanan
               yang sama sehingga tepi atas sampai bawah satu garis. */
            padding-left: var(--dash-pad-x);
            padding-right: var(--dash-pad-x);
            --bs-gutter-x: var(--dash-gap);
            margin-bottom: var(--dash-gap) !important;
        }

        .dashboard-box {
            border: 0 !important;
            border-radius: var(--dash-radius) !important;
            box-shadow: none !important;
        }

        .dashboard-box-title {
            font-size: var(--dash-title-size) !important;
            font-weight: var(--dash-title-weight);
            color: var(--dash-title-color);
            margin-bottom: 0 !important;
        }
        .dashboard-box-sub {
            font-size: var(--dash-sub-size) !important;
            line-height: 1.25;
        }

        /* Catatan: blok header "Monitoring & Analitik Presensi" beserta tombolnya
           sudah dihapus dari markup dashboard, jadi aturan .dashboard-monitor-box
           / .monitoring-* di bawah ini tidak lagi dipakai. */

        /* Jarak vertikal antar blok yang ditumpuk (grafik penuh lebar
           di atas, Kehadiran Hari Ini di bawahnya): 32px. */
        .dashboard-section.row {
            row-gap: 2rem !important;
        }

        /* Kartu di dalam bagian (baris status & kartu jam): tanpa garis. */
        .dashboard-inner-card {
            border: 0 !important;
            outline: 0 !important;
            box-shadow: none !important;
            background-color: transparent !important;
            border-radius: var(--dash-radius) !important;
            padding: 0.65rem 0.75rem !important;
        }

        /* Kartu jam Jadwal Operasional */
        .dashboard-clock {
            padding: 0.75rem 1rem !important;
        }
        .dashboard-clock .clock-label {
            font-size: 0.7rem !important;
            color: #0f172a !important;
            font-weight: 700 !important;
        }
        .dashboard-clock .clock-value {
            font-size: 1.55rem !important;
        }
        /* Catatan: "WIB" kini menyatu dalam satu baris bersama jamnya
           (span .clock-wib, diatur di blok "BAGIAN BAWAH" di atas), jadi
           aturan .clock-unit yang dulu mengatur baris terpisah sudah
           dihapus. Tinggi tombol "Ubah Pengaturan Jadwal" juga tidak lagi
           diatur di sini: keduanya disamakan oleh .dash-col-btn (40px). */

        /* Footer grafik (Garis Tren ... Total): teks biasa. */
        .dashboard-trend-footer {
            border-top: 0 !important;
            padding-top: 0 !important;
            margin-top: 0.5rem !important;
        }

        /* GRUP TAB Harian/Mingguan/Bulanan - satu-satunya elemen yang boleh
           berborder tipis. Tombol menempel, satu bingkai luar, pemisah 1px. */
        .dash-seg {
            display: inline-flex;
            align-items: stretch;
            flex-wrap: nowrap;
            gap: 0;
            padding: 0;
            background-color: #ffffff;
            border: 1px solid var(--dash-line);
            border-radius: var(--dash-control-radius);
            box-shadow: none;
            overflow: hidden;
        }
        .dash-seg .dash-seg__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 30px;
            min-height: 30px;
            padding: 0 0.75rem;
            margin: 0;
            border: 0;
            border-left: 1px solid var(--dash-line);
            border-radius: 0 !important;
            background-color: #ffffff;
            color: #334155;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: none !important;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .dash-seg .dash-seg__btn:first-child {
            border-left: 0;
        }
        @media (hover: hover) {
            .dash-seg .dash-seg__btn:hover {
                background-color: rgba(37, 99, 235, 0.06);
                color: #0f172a;
            }
        }
        .dash-seg .dash-seg__btn:active {
            background-color: #e2e8f0;
        }
        .dash-seg .dash-seg__btn.is-active,
        .dash-seg .dash-seg__btn--solid,
        .dash-seg .dash-seg__btn.is-active:hover,
        .dash-seg .dash-seg__btn--solid:hover,
        .dash-seg .dash-seg__btn.is-active:active,
        .dash-seg .dash-seg__btn--solid:active,
        .dash-seg .dash-seg__btn.is-active:focus,
        .dash-seg .dash-seg__btn--solid:focus,
        .dash-seg .dash-seg__btn.is-active:focus-visible,
        .dash-seg .dash-seg__btn--solid:focus-visible {
            background-color: #2563eb !important;
            color: #ffffff !important;
        }
        .dash-seg .dash-seg__btn:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: -2px;
        }

        /* Tanggal periode grafik: teks biasa, tanpa badge/kotak. */
        .dash-period {
            background-color: transparent !important;
            border: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin-left: 0;
            font-size: 0.7rem;
            font-weight: 600;
            color: #475569;
        }

        /* Jarak .75rem antara komponen segmented dan teks periode. */
        .chart-header-actions {
            gap: 0.75rem !important;
        }
    }

    /* Tier >= 768px: inset horizontal tiap blok mengikuti p-md-4 (1.5rem). */
    @media (min-width: 768px) {
        .dashboard-section {
            --dash-pad-x: 1.5rem;
        }
    }

    /* ------------------------------------------------------------------
       AREA GRAFIK: canvas dijaga tetap DI DALAM kartu.
       - overflow: hidden -> garis/label grafik tidak pernah meluber ke
         luar kartu (dulu bisa memotong sidebar di sisi kiri);
       - canvas display:block -> tidak ada celah baseline inline yang
         membuat tinggi area melebar.
       ------------------------------------------------------------------ */
    .chart-canvas-wrap {
        overflow: hidden;
        /* Ruang tambahan di bawah area gambar untuk label sumbu X, supaya
           garis tren 0% tidak pernah menempel pada labelnya. */
        padding-bottom: 6px;
    }
    .chart-canvas-wrap canvas {
        display: block;
        max-width: 100%;
    }

    /* ------------------------------------------------------------------
       BANTUAN HEADER (tidak mengubah tampilannya): date picker tanggal.
       ------------------------------------------------------------------ */
    .dashboard-date-form {
        position: relative;
    }

    .dashboard-date-input {
        position: absolute !important;
        top: 0;
        right: 0;
        width: 1px !important;
        height: 1px !important;
        opacity: 0 !important;
        pointer-events: none !important;
        padding: 0 !important;
        border: 0 !important;
        margin: 0 !important;
    }

    /* Fallback: bila JS tidak dapat memanggil showPicker(), input ditampilkan
       di bawah ikon supaya user tetap bisa memilih tanggal. */
    .dashboard-date-input.is-fallback {
        position: static !important;
        width: auto !important;
        height: auto !important;
        opacity: 1 !important;
        pointer-events: auto !important;
    }

    .dashboard-date-trigger {
        background: transparent;
        border: none;
        padding: 0;
        margin: 0;
        line-height: 1;
        cursor: pointer;
        border-radius: 6px;
        transition: transform 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .dashboard-date-trigger:hover {
        transform: scale(1.08);
    }

    .dashboard-date-text {
        cursor: pointer;
    }

    /* Hormati preferensi pengguna yang mengurangi animasi. */
    @media (prefers-reduced-motion: reduce) {
        .dashboard-section .dash-stat-cell,
        .dashboard-section a.dash-stat-cell .dash-stat-value,
        .dashboard-section a.dash-stat-cell .dash-stat-label,
        .dashboard-section .dash-shortcut-item,
        .dashboard-section .dash-sc-icon,
        .absence-row-interactive,
        .dashboard-date-trigger {
            transition: none;
        }
    }
</style>
@endpush

@section('content')

    <!-- ========================================================================= -->
    <!-- 1. STATISTIK - satu blok putih, grid 3 kolom, TANPA ikon, tanpa kotak,    -->
    <!--    dan tanpa garis pemisah. Tiga sel: angka besar + label kecil.          -->
    <!--    Tautan & data dinamis tidak diubah.                                    -->
    <!-- ========================================================================= -->
    <div class="dashboard-section">
        <div class="dash-card">
            <!-- Judul bagian statistik: class & gaya SAMA PERSIS dengan
                 judul "Pintasan Cepat" (rata kiri, jarak ke isi identik). -->
            <div class="dash-shortcut-head">
                <span class="dash-card-title">{{ __('Data Sekolah') }}</span>
            </div>

            <div class="dash-stats-grid">

                <!-- 1. Siswa Aktif (angka memakai warna ikon "Data Siswa")
                     Link ke halaman Data Siswa (route helper panel_route).
                     Khusus Guru Piket: data sekolah read-only. -->
                @if(is_piket())
                <div class="dash-stat-cell" title="{{ __('Data sekolah hanya dapat dilihat') }}">
                    <strong class="dash-stat-value is-sidebar-blue">{{ number_format($totalStudents ?? 0, 0, ',', '.') }}</strong>
                    <span class="dash-stat-label">{{ __('Siswa Aktif') }}</span>
                </div>
                @else
                <a href="{{ panel_route('students.index') }}"
                   class="dash-stat-cell"
                   aria-label="{{ __('Buka data siswa') }}"
                   title="{{ __('Buka data siswa') }}">
                    <strong class="dash-stat-value is-sidebar-blue">{{ number_format($totalStudents ?? 0, 0, ',', '.') }}</strong>
                    <span class="dash-stat-label">{{ __('Siswa Aktif') }}</span>
                </a>
                @endif

                <!-- 2. Rombongan Belajar (angka memakai warna ikon "Data Kelas")
                     Link ke halaman Data Kelas.
                     Khusus Guru Piket: data sekolah read-only. -->
                @if(is_piket())
                <div class="dash-stat-cell" title="{{ __('Data sekolah hanya dapat dilihat') }}">
                    <strong class="dash-stat-value is-yellow">{{ number_format($totalClasses ?? 0, 0, ',', '.') }}</strong>
                    <span class="dash-stat-label">{{ __('Rombongan Belajar') }}</span>
                </div>
                @else
                <a href="{{ panel_route('classes.index') }}"
                   class="dash-stat-cell"
                   aria-label="{{ __('Buka data kelas') }}"
                   title="{{ __('Buka data kelas') }}">
                    <strong class="dash-stat-value is-yellow">{{ number_format($totalClasses ?? 0, 0, ',', '.') }}</strong>
                    <span class="dash-stat-label">{{ __('Rombongan Belajar') }}</span>
                </a>
                @endif

                <!-- 3. Guru Aktif (angka memakai warna ikon "Data Guru")
                     Halaman Data Guru hanya ada di panel ADMIN, jadi untuk role
                     lain kartu ini tetap berupa teks biasa (tidak bisa diklik). -->
                @if(is_admin())
                <a href="{{ route('admin.guru.index') }}"
                   class="dash-stat-cell"
                   aria-label="{{ __('Buka data guru') }}"
                   title="{{ __('Buka data guru') }}">
                @else
                <div class="dash-stat-cell">
                @endif
                    <strong class="dash-stat-value is-green">{{ number_format($totalTeachers ?? 0, 0, ',', '.') }}</strong>
                    <span class="dash-stat-label">{{ __('Guru Aktif') }}</span>
                @if(is_admin())
                </a>
                @else
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. PINTASAN CEPAT - judul lalu grid ikon 4 KOLOM yang memanjang penuh     -->
    <!--     (2 baris x 4 kolom). Tanpa kotak/border per item: hanya wadah ikon    -->
    <!--     rounded-square berlatar lembut per warna ikon + satu label pendek.    -->
    <!--     Subjudul kartu lama dihapus; ikon & tautan tetap sama persis.         -->
    <!-- ========================================================================= -->
    <div class="dashboard-section">
        <div class="dash-card">
            <div class="dash-shortcut-head">
                <i class='bx bx-zap text-warning fs-4'></i>
                <span class="dash-card-title">{{ __('Pintasan Cepat') }}</span></div>

                <div class="dash-shortcut-grid">

                <!-- 1. Presensi Hari Ini (ikon QR/Scan) - langsung ke MODE GERBANG (kiosk).
                     Kamera otomatis menyala saat halaman dibuka lewat pintasan ini; tidak
                     perlu lagi klik tombol Mode Gerbang.
                     URL dibangun ulang per role agar route guru/piket tetap valid
                     (guru & piket juga memakai route absensi.kiosk lewat panel_route). -->
                <a href="{{ panel_route('absensi.kiosk') }}"
                   class="dash-shortcut-item"
                   title="{{ __('Buka Mode Gerbang (Scanner QR otomatis nyala)') }}">
                    <span class="dash-sc-icon" style="--sc-soft: #dbeafe;">
                        <i class='bx bx-qr-scan text-primary'></i>
                    </span>
                    <span class="dash-sc-label">{{ __('Presensi Hari Ini') }}</span>
                </a>

                <!-- 2. Catatan Kehadiran (ikon List/Ceklis) - HALAMAN KEHADIRAN -->
                <a href="{{ panel_route('kehadiran') }}"
                   class="dash-shortcut-item"
                   title="{{ __('Lihat Log Kehadiran & Presensi Harian') }}">
                    <span class="dash-sc-icon" style="--sc-soft: #dcfce7;">
                        <i class='bx bx-list-check text-success'></i>
                    </span>
                    <span class="dash-sc-label">{{ __('Catatan Kehadiran') }}</span>
                </a>

                {{-- Guru Piket hanya memakai dua pintasan operasional di atas. --}}
                @if(!is_piket())
                <!-- 3. Rekapitulasi Presensi (ikon PDF) -->
                <a href="{{ panel_route('rekap') }}"
                   class="dash-shortcut-item"
                   title="{{ __('Laporan Rekapitulasi & Ekspor Dokumen') }}">
                    <span class="dash-sc-icon" style="--sc-soft: #fee2e2;">
                        <i class='bx bxs-file-pdf text-danger'></i>
                    </span>
                    <span class="dash-sc-label">{{ __('Rekap Presensi') }}</span>
                </a>

                <!-- 4. Master Data Siswa (ikon User) -->
                <a href="{{ panel_route('students.index') }}"
                   class="dash-shortcut-item"
                   title="{{ __('Kelola Master Data Siswa & Generate QR') }}">
                    <span class="dash-sc-icon" style="--sc-soft: #cffafe;">
                        <i class='bx bx-user text-info'></i>
                    </span>
                    <span class="dash-sc-label">{{ __('Data Siswa') }}</span>
                </a>

                <!-- 5. Master Data Guru (ikon Guru) - khusus admin -->
                @if(is_admin())
                <a href="{{ route('admin.guru.index') }}"
                   class="dash-shortcut-item"
                   title="{{ __('Kelola Data Guru') }}">
                    <span class="dash-sc-icon" style="--sc-soft: #f3e8ff;">
                        <i class='bx bx-user-pin text-purple'></i>
                    </span>
                    <span class="dash-sc-label">{{ __('Data Guru') }}</span>
                </a>
                @endif

                <!-- 6. Master Data Kelas (ikon Gedung) -->
                <a href="{{ panel_route('classes.index') }}"
                   class="dash-shortcut-item"
                   title="{{ __('Kelola Master Rombongan Belajar & Kelas') }}">
                    <span class="dash-sc-icon" style="--sc-soft: #fef3c7;">
                        <i class='bx bx-buildings text-warning'></i>
                    </span>
                    <span class="dash-sc-label">{{ __('Data Kelas') }}</span>
                </a>

                <!-- 7. Master Hari Libur (ikon Kalender) - khusus admin -->
                @if(is_admin())
                <a href="{{ panel_route('holidays.index') }}"
                   class="dash-shortcut-item"
                   title="{{ __('Kelola Kalender Hari Libur Nasional & Sekolah') }}">
                    <span class="dash-sc-icon" style="--sc-soft: #fee2e2;">
                        <i class='bx bx-calendar-x text-danger'></i>
                    </span>
                    <span class="dash-sc-label">{{ __('Hari Libur') }}</span>
                </a>
                @endif

                <!-- 8. Pengaturan Sistem (ikon Gear) - khusus admin -->
                @if(is_admin())
                <a href="{{ panel_route('settings.index') }}"
                   class="dash-shortcut-item"
                   title="{{ __('Konfigurasi Profil Sekolah, Jam Masuk, & Sistem') }}">
                    <span class="dash-sc-icon" style="--sc-soft: #e2e8f0;">
                        <i class='bx bx-cog text-secondary'></i>
                    </span>
                    <span class="dash-sc-label">{{ __('Pengaturan') }}</span>
                </a>
                @endif
                @endif

            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. SECTION TENGAH: GRAFIK GARIS KEHADIRAN PER ROMBEL (penuh lebar)        -->
    <!--    (mode: harian/mingguan/bulanan). Satu garis = satu rombel (kelas).     -->
    <!--    Data per rombel dibaca dari $chartDatasets (dari controller):          -->
    <!--      [ ['label' => '7A', 'data' => [..persen per label sumbu X..]], ... ] -->
    <!--    Bila $chartDatasets belum dikirim, grafik jatuh kembali ke satu garis  -->
    <!--    total ($chartValues) seperti sebelumnya.                               -->
    <!-- ========================================================================= -->
    <div class="row g-3 dashboard-section">

        <!-- KOLOM PENUH: GRAFIK GARIS KEHADIRAN (mode: harian/mingguan/bulanan) -->
        <div class="col-12">
            <div class="card border-0 rounded-4 p-3 p-md-4 h-100 d-flex flex-column justify-content-between dashboard-panel dashboard-box">
                <div>
                    <div class="d-flex align-items-center justify-content-between gap-2 pb-3 mb-3">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <span class="fw-bold text-dark text-truncate dashboard-box-title" style="font-size: 0.9rem;">{{ $chartTitle ?? 'Grafik Garis Kehadiran Mingguan' }}</span>
                        </div>

                        <div class="d-flex align-items-center gap-2 flex-shrink-0 chart-header-actions">
                            {{-- TOGGLE MODE: Harian / Mingguan / Bulanan.
                                 Hanya tautan GET ke halaman yang sama dengan ?mode=...,
                                 jadi tidak ada endpoint atau JS tambahan. --}}
                            <div class="chart-mode-toggle dash-seg" role="group" aria-label="{{ __('Mode grafik kehadiran') }}">
                                @foreach(['harian' => __('Harian'), 'mingguan' => __('Mingguan'), 'bulanan' => __('Bulanan')] as $modeKey => $modeLabel)
                                    <a href="{{ panel_route('dashboard', array_filter(['tanggal' => ($isToday ?? true) ? null : ($dateString ?? null), 'mode' => $modeKey, 'status' => request('status')])) }}"                                                class="chart-mode-btn dash-seg__btn {{ ($chartMode ?? 'harian') === $modeKey ? 'active dash-seg__btn--solid' : '' }}"
                                       title="{{ ['harian' => __('Tampilkan grafik harian'), 'mingguan' => __('Tampilkan grafik mingguan'), 'bulanan' => __('Tampilkan grafik bulanan')][$modeKey] }}">{{ $modeLabel }}</a>
                                @endforeach
                            </div>

                            <span class="badge bg-light text-secondary rounded-pill px-2 px-md-3 py-1 py-md-1.5 fw-semibold flex-shrink-0 text-nowrap dash-period" style="font-size: 0.68rem;">
                                {{-- Periode grafik: nilai controller sudah sesuai mode.
                                     Hanya mode mingguan yang butuh format ulang di view
                                     karena rentangnya dibangun dari hari ini ke belakang. --}}
                                {{ ($chartPeriodLabel ?? '') }}
                            </span>
                        </div>
                    </div>

                    <!-- Area Grafik: canvas SELALU digambar di semua mode
                         (Harian / Mingguan / Bulanan), walaupun semua
                         nilainya 0 - garis tren tetap tergambar datar di 0%
                         dan label sumbu X tetap terbaca. Tinggi canvas
                         dikunci (300px) supaya
                         layout kartu tidak lompat saat pindah mode. -->
                    <div class="pt-1 pb-1">
                        <div class="chart-canvas-wrap" style="position: relative; height: 300px; width: 100%;">
                            <canvas id="attendanceLineChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Keterangan Garis Tren & Total (selalu satu baris, tidak bertumpuk) -->
                <div class="mt-3 pt-2.5 d-flex flex-nowrap align-items-center justify-content-between gap-2 text-muted dashboard-trend-footer" style="font-size: 0.72rem;">
                    <div class="d-flex align-items-center gap-2 min-w-0 text-truncate">
                        {{-- "Total: 3 Rombel" berasal dari controller (angka & satuan).
                             Yang diterjemahkan hanya kata "Total" dan satuannya, angka
                             tetap apa adanya. --}}
                        @php
                            $__dashTotal = $chartTotalText ?? 'Total: ' . count($chartValues ?? []) . ' Hari';
                        @endphp
                        <span class="text-truncate"><strong class="text-dark">{{ __('Garis Tren Kehadiran') }}</strong> <span class="d-none d-sm-inline">({{ $chartScopeText ?? 'Per hari, 7 hari terakhir' }})</span></span>
                    </div>
                    <span class="fw-semibold text-dark flex-shrink-0 text-nowrap">{{ $__dashTotal }}</span>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 4. BAGIAN BAWAH - DUA KOLOM: "Kehadiran Hari Ini" (KIRI) dan             -->
    <!--    "Jadwal Operasional Sekolah" (KANAN).                                    -->
    <!--    - .dashboard-section : pembungkus bagian. Class ini WAJIB ada di sini    -->
    <!--      supaya aturan CSS lama tetap berlaku, mis. .dashboard-section          -->
    <!--      .dash-nobox (penihil border kotak) dan .dashboard-section             -->
    <!--      .dash-absence-grid (grid 4 kolom).                                    -->
    <!--    - .dash-bottom-grid  : 1 kolom di HP (gap-y-8 = 32px) dan 2 kolom sama  -->
    <!--      lebar di >=768px (gap-x-12 = 48px, items-stretch).                    -->
    <!--    - .dash-col          : kolom flex (flex flex-col h-full min-w-0) dengan  -->
    <!--      tinggi sama; tombol memakai mt-auto pt-6 sehingga kedua tombol        -->
    <!--      sejajar rata di bawah dan tidak menyentuh isi di atasnya.              -->
    <!--    Kontainer tetap polos: tanpa border, shadow, dan background abu-abu.     -->
    <!-- ========================================================================= -->
    <div class="dashboard-section">
        <div class="dash-bottom-grid grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8 items-stretch align-content-start">

        <!-- ===== KOLOM KIRI: KEHADIRAN HARI INI (HTSIA) ===== -->
        <div class="dash-col flex flex-col h-full min-w-0">
            <div class="card border-0 rounded-4 p-3 p-md-4 h-100 d-flex flex-column dashboard-panel dashboard-box">

                <!-- Judul (tanpa subjudul).
                     min-height pada .dash-col-head tetap dipakai supaya judul
                     kolom ini tetap sejajar dengan judul kolom kanan. -->
                <div class="d-flex items-start justify-between gap-2 clean-line-off dash-col-head">
                    <div class="min-w-0">
                        <span class="d-block fw-bold text-dark dashboard-box-title">{{ __('Kehadiran Hari Ini') }}</span>
                    </div>
                </div>

                <!-- 5 item Kehadiran Hari Ini: 5 kolom sejajar 1 baris, urutan
                     Hadir - Terlambat - Sakit - Izin - Alfa (HTSIA).
                     Angka + label saja (TIDAK bisa diklik): satu-satunya aksi di
                     kartu ini adalah tombol "Buka Tabel Presensi Hari Ini".
                     $countHadir dan $countTerlambat dikirim dari controller (jumlah
                     siswa berstatus Hadir / Terlambat pada tanggal terpilih). -->
                <div class="dash-absence-grid grid grid-cols-5">

                    <!-- 1. Hadir -->
                    <div class="dashboard-inner-card dash-nobox text-decoration-none d-flex flex-column align-items-center text-center min-w-0 {{ request('status') === 'Hadir' ? 'absence-row-active' : '' }}">
                        <span class="d-block dash-att-figure">
                            <strong class="dash-stat-value dash-att-value is-green">{{ $countHadir ?? 0 }}</strong>
                        </span>
                        <span class="fw-bold absence-label-hadir d-block mb-0 text-nowrap">{{ __('Hadir') }} @if(request('status') === 'Hadir')<span class="absence-row-active-badge">{{ __('Aktif') }}</span>@endif</span>
                    </div>

                    <!-- 2. Terlambat -->
                    <div class="dashboard-inner-card dash-nobox text-decoration-none d-flex flex-column align-items-center text-center min-w-0 {{ request('status') === 'Terlambat' ? 'absence-row-active' : '' }}">
                        <span class="d-block dash-att-figure">
                            <strong class="dash-stat-value dash-att-value is-orange">{{ $countTerlambat ?? 0 }}</strong>
                        </span>
                        <span class="fw-bold absence-label-terlambat d-block mb-0 text-nowrap">{{ __('Terlambat') }} @if(request('status') === 'Terlambat')<span class="absence-row-active-badge">{{ __('Aktif') }}</span>@endif</span>
                    </div>

                    <!-- 3. Sakit -->
                    <div class="dashboard-inner-card dash-nobox text-decoration-none d-flex flex-column align-items-center text-center min-w-0 {{ request('status') === 'Sakit' ? 'absence-row-active' : '' }}">
                        <span class="d-block dash-att-figure">
                            <strong class="dash-stat-value dash-att-value is-sidebar-blue">{{ $countSakit ?? 0 }}</strong>
                        </span>
                        <span class="fw-bold absence-label-sakit d-block mb-0 text-nowrap">{{ __('Sakit') }} @if(request('status') === 'Sakit')<span class="absence-row-active-badge">{{ __('Aktif') }}</span>@endif</span>
                    </div>

                    <!-- 4. Izin -->
                    <div class="dashboard-inner-card dash-nobox text-decoration-none d-flex flex-column align-items-center text-center min-w-0 {{ request('status') === 'Izin' ? 'absence-row-active' : '' }}">
                        <span class="d-block dash-att-figure">
                            <strong class="dash-stat-value dash-att-value is-yellow">{{ $countIzin ?? 0 }}</strong>
                        </span>
                        <span class="fw-bold absence-label-izin d-block mb-0 text-nowrap">{{ __('Izin') }} @if(request('status') === 'Izin')<span class="absence-row-active-badge">{{ __('Aktif') }}</span>@endif</span>
                    </div>

                    <!-- 5. Alfa -->
                    <div class="dashboard-inner-card dash-nobox text-decoration-none d-flex flex-column align-items-center text-center min-w-0 {{ request('status') === 'Alfa' ? 'absence-row-active' : '' }}">
                        <span class="d-block dash-att-figure">
                            <strong class="dash-stat-value dash-att-value is-red">{{ $countAlfa ?? 0 }}</strong>
                        </span>
                        <span class="fw-bold absence-label-alfa d-block mb-0 text-nowrap">{{ __('Alfa') }} @if(request('status') === 'Alfa')<span class="absence-row-active-badge">{{ __('Aktif') }}</span>@endif</span>
                    </div>

                </div>

                <!-- Tombol Menuju Tabel Presensi Harian: mt-auto pt-6 = terdorong
                     ke dasar kolom, ada jarak dari isi, dan hanya selebar
                     kolom (BUKAN selebar layar). -->
                <div class="mt-auto pt-6 clean-line-off">
                    <a href="{{ panel_route('presensi.index', array_filter(['tanggal' => $dateString, 'status' => request('status')])) }}"
                       class="btn btn-primary shadow-sm w-100 fw-bold d-inline-flex align-items-center justify-content-center gap-2 btn-modern-smooth dash-col-btn">
                        <span>{{ __('Buka Tabel Presensi Hari Ini') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- ===== KOLOM KANAN: JADWAL OPERASIONAL SEKOLAH ===== -->
        <div class="dash-col flex flex-col h-full min-w-0">
            @include('components.dashboard-schedule-card', [
                'times' => $scheduleTimes ?? null,
                'date'  => $scheduleDate ?? null,
            ])

            @if(is_admin())
            <!-- Satu tombol Ubah Pengaturan Jadwal (khusus admin) di bagian
                 bawah kolom kanan: mt-auto pt-6 sama persis dengan kolom kiri
                 supaya kedua tombol sejajar di bawah dan tinggi sama. -->
            <div class="mt-auto pt-6">
                <a href="{{ route('admin.settings.index') }}"
                   class="btn btn-primary shadow-sm w-100 fw-bold text-white text-decoration-none d-inline-flex align-items-center justify-content-center gap-2 btn-modern-smooth dashboard-schedule-btn dash-col-btn">
                    <span>{{ __('Ubah Pengaturan Jadwal') }}</span>
                </a>
            </div>
            @endif
        </div>

        </div>
    </div>

@endsection

@push('scripts')
<!-- Library Chart.js Resmi -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    /* ===== Date Picker di Header (ikon kalender) =====
       Memakai <input type="date"> native yang sudah disembunyikan di dalam
       <form method="GET">. Tidak ada library datepicker baru.
       - Klik ikon / blok tanggal  -> buka date picker browser.
       - User memilih tanggal      -> form GET submit ke halaman dashboard
                                       yang sama dengan ?tanggal=YYYY-MM-DD,
                                       lalu controller hitung ulang semua data.
       - showPicker() dipicu dari event klik (user gesture), sehingga
         tidak bergantung pada izin browser. */
    document.addEventListener('DOMContentLoaded', function () {
        var input = document.querySelector('.dashboard-date-input');
        var trigger = document.querySelector('.dashboard-date-trigger');
        var textBlock = document.querySelector('.dashboard-date-text');
        var form = document.querySelector('.dashboard-date-form');

        if (!input || !trigger || !form) {
            return;
        }

        function openPicker() {
            if (typeof input.showPicker === 'function') {
                try {
                    input.showPicker();
                    return;
                } catch (e) {
                    // showPicker ditolak -> jatuh ke fallback di bawah.
                }
            }
            // Fallback: tampilkan input aslinya supaya tetap bisa diklik manual.
            input.classList.add('is-fallback');
            if (typeof input.focus === 'function') {
                input.focus();
            }
        }

        trigger.addEventListener('click', openPicker);
        if (textBlock) {
            textBlock.addEventListener('click', openPicker);
        }

        // Setelah tanggal dipilih, form disubmit otomatis.
        input.addEventListener('change', function () {
            if (this.value) {
                form.submit();
            }
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('attendanceLineChart');
        if (!ctx) return;

        // Guard: kalau CDN Chart.js gagal dimuat (offline / diblokir), jangan
        // melempar ReferenceError yang membuat area grafik kosong tanpa
        // penjelasan. Cukup tampilkan keterangan singkat yang sama seperti
        // kondisi "belum ada catatan".
        if (typeof Chart === 'undefined') {
            const note = document.createElement('p');
            note.className = 'chart-empty-note text-center text-muted mb-0 mt-1';
            note.textContent = {!! json_encode(__('Grafik belum dapat dimuat (perpustakaan grafik gagal dimuat).')) !!};
            if (ctx.parentNode) {
                ctx.parentNode.insertAdjacentElement('afterend', note);
            }
            return;
        }

        // Label & data sudah dihitung server-side sesuai mode aktif
        // (harian / mingguan / bulanan) - lihat AdminDashboardController.
        //  - chartLabels   : label sumbu X (jam/hari/tanggal)
        //  - chartDatasets : satu entri per rombel: { label: '7A', data: [persen, ...] }
        //  - chartData     : satu garis total (dipakai bila chartDatasets kosong)
        let chartLabels = {!! json_encode($chartLabels ?? []) !!};
        let chartData = {!! json_encode($chartValues ?? []) !!};
        let rawDatasets = {!! json_encode($chartDatasets ?? []) !!};

        function toNum(v) {
            const n = Number(v);
            return (v === null || v === undefined || isNaN(n)) ? 0 : n;
        }

        // Data kosong / null / NaN diubah jadi 0 supaya garis tren SELALU
        // tergambar (datar di 0% saat belum ada data) dan tidak ada error
        // di console.
        if (!Array.isArray(chartLabels)) {
            chartLabels = [];
        }
        if (!Array.isArray(chartData)) {
            chartData = [];
        }
        // Controller bisa mengirim objek (assoc array) -> jadikan array.
        if (rawDatasets && !Array.isArray(rawDatasets) && typeof rawDatasets === 'object') {
            rawDatasets = Object.keys(rawDatasets).map(function (k) { return rawDatasets[k]; });
        }
        if (!Array.isArray(rawDatasets)) {
            rawDatasets = [];
        }
        chartData = chartData.map(toNum);
        if (chartLabels.length === 0) {
            chartLabels = ['-'];
        }
        if (chartData.length !== chartLabels.length) {
            chartData = chartLabels.map(function () { return 0; });
        }

        const isMulti = rawDatasets.length > 0;
        // Hanya satu titik di sumbu X (mode harian): titik dibuat lebih besar
        // supaya tiap rombel tetap terlihat walau tidak ada garis penghubung.
        const singlePoint = chartLabels.length === 1;
        const canvasContext = ctx.getContext('2d');
        let datasets;

        if (isMulti) {
            // Satu garis per rombel. Warna dibuat otomatis dengan sudut hue
            // "golden angle" supaya 20+ rombel tetap mudah dibedakan.
            datasets = rawDatasets.map(function (ds, i) {
                ds = ds || {};
                const color = ds.color || ('hsl(' + Math.round((i * 137.508) % 360) + ', 65%, 45%)');
                let data = Array.isArray(ds.data) ? ds.data.map(toNum) : [];
                if (data.length !== chartLabels.length) {
                    data = chartLabels.map(function () { return 0; });
                }
                return {
                    label: ds.label || ds.name || ({!! json_encode(__('Rombel')) !!} + ' ' + (i + 1)),
                    data: data,
                    borderColor: color,
                    backgroundColor: color,
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: color,
                    pointBorderWidth: 1.5,
                    pointRadius: singlePoint ? 5 : 2.5,
                    pointHoverRadius: 6
                };
            });
        } else {
            // Cadangan: satu garis total (perilaku lama).
            const gradient = canvasContext.createLinearGradient(0, 0, 0, 220);
            gradient.addColorStop(0, 'rgba(37, 99, 235, 0.20)');
            gradient.addColorStop(1, 'rgba(37, 99, 235, 0.01)');
            datasets = [{
                label: {!! json_encode(__('Tingkat Kehadiran (%)')) !!},
                data: chartData,
                borderColor: '#2563eb',
                borderWidth: 2.5,
                backgroundColor: gradient,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 2.5,
                pointRadius: 4.5,
                pointHoverRadius: 7,
                pointHoverBackgroundColor: '#2563eb',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2
            }];
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        // Legenda hanya perlu saat banyak garis (per rombel).
                        // Klik nama rombel untuk menyembunyikan / menampilkan garisnya.
                        display: isMulti,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            boxHeight: 8,
                            padding: 12,
                            color: '#334155',
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 11,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#ffffff',
                        bodyColor: '#cbd5e1',
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: isMulti,
                        // Rombel dengan persen tertinggi tampil paling atas.
                        itemSort: function (a, b) {
                            return b.parsed.y - a.parsed.y;
                        },
                        callbacks: {
                            label: function (context) {
                                if (isMulti) {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                                return {!! json_encode(__('Tingkat Kehadiran')) !!} + ': ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        /* Garis sumbu X DIMATIKAN: kalau tetap digambar, garis
                           itu menempel tepat di garis tren 0% dan terlihat
                           seperti garis nyasar di area label. */
                        border: {
                            display: false
                        },
                        ticks: {
                            color: '#475569',
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 12,
                                weight: '600'
                            },
                            /* Jarak label sumbu X dari area gambar, supaya
                               garis tren tidak pernah menyentuh labelnya. */
                            padding: 12,
                            /* Sumbu X = nama rombel: semua rombel harus tampil. */
                            autoSkip: false,
                            maxRotation: 60
                        }
                    },
                    y: {
                        min: 0,
                        max: 100,
                        grid: {
                            color: '#f1f5f9'
                        },
                        /* Garis sumbu Y juga dimatikan (cuma grid + label
                           persen yang tampil). */
                        border: {
                            display: false
                        },
                        ticks: {
                            stepSize: 25,
                            color: '#64748b',
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 11,
                                weight: '500'
                            },
                            callback: function (val) {
                                return val + '%';
                            },
                            padding: 8
                        }
                    }
                }
            }
        });
    });
</script>
@endpush