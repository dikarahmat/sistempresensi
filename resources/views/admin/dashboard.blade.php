@extends('layouts.app')

@section('title', is_admin() ? 'Dashboard Admin' : 'Dashboard Guru')
@section('page_title', is_admin() ? 'Dashboard Administrator' : 'DASHBOARD GURU')
@section('page_subtitle', is_admin() ? 'Sistem Presensi Terintegrasi' : 'Selamat datang, ' . (Auth::user()->name ?? 'Guru'))

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
     grafik, dan ketidakhadiran. Tampilan (ikon, teks, ukuran, warna) TIDAK diubah. -->
<form method="GET" action="{{ route(panel_role() === 'guru' ? 'guru.dashboard' : 'admin.dashboard') }}" class="dashboard-date-form d-none d-lg-flex align-items-center gap-2.5 text-end">
    {{-- Mode grafik & filter status ikut dipertahankan saat tanggal diganti --}}
    <input type="hidden" name="mode" value="{{ $chartMode ?? 'mingguan' }}">
    @if(request('status'))
    <input type="hidden" name="status" value="{{ request('status') }}">
    @endif

    <div class="dashboard-date-text">
        <span class="text-secondary fw-semibold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.05em;">{{ $dateLabel ?? 'Tanggal Hari Ini' }}</span>
        <strong class="text-dark fw-bold d-block" style="font-size: 0.88rem;">
            @php \Carbon\Carbon::setLocale('id'); @endphp
            {{ $todayFormatted ?? \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}
        </strong>
    </div>

    {{-- Input date disembunyikan (bukan diklik langsung); ikon kalender yang jadi pemicu --}}
    <input type="date" name="tanggal" value="{{ $dateString }}"
           class="dashboard-date-input" tabindex="-1" aria-hidden="true">

    <button type="button" class="dashboard-date-trigger" title="Pilih tanggal" aria-label="Pilih tanggal">
        <i class='bx bx-calendar text-primary fs-3'></i>
    </button>
</form>

{{-- Kembali ke Hari Ini: hanya tampil bila tanggal yang dipilih bukan hari ini --}}
@if(!($isToday ?? true))
<a href="{{ route(panel_role() === 'guru' ? 'guru.dashboard' : 'admin.dashboard', array_filter(['mode' => $chartMode ?? 'mingguan', 'status' => request('status')])) }}"
   class="btn btn-light border text-dark fw-semibold rounded-2 px-2.5 py-1 d-none d-lg-inline-flex align-items-center gap-1 dashboard-reset-today"
   style="font-size: 0.78rem;" title="Kembali ke data hari ini">
    <i class='bx bx-undo'></i> Hari Ini
</a>
@endif
@endsection

@push('styles')
<style>
    /* ==================================================================
       DASHBOARD — CLEAN LOOK (versi sebelum redesign terakhir)
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
       - HP: jarakcomes dari gap area scroll di atas; untuk baris yang berisi
         Grafik + Ketidakhadiran (satu blok), jarak internalnya 32px juga.
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
    }

    /* Kotak Grafik / Ketidakhadiran / Monitoring / Jadwal: putih, tanpa
       garis, tanpa shadow, dan tanpa padding luar. ISI tidak diubah. */
    body .dashboard-section .dashboard-box,
    body .dashboard-section .dashboard-monitor-box {
        padding: 0 !important;
        border: 0;
        box-shadow: none;
        outline: 0;
    }

    /* Kartu pembungkus Statistik & Pintasan Cepat (tanpa garis/shadow) */
    .dashboard-section .dash-card {
        background-color: var(--dash-card-bg);
        padding: 0;
        min-width: 0;
    }

    /* ------------------------------------------------------------------
       BAGIAN 1: STATISTIK — memakai grid yang SAMA PERSIS dengan grid
       Pintasan Cepat (jumlah kolom, lebar kolom, dan gap identik) dan
       konten tiap sel RATA TENGAH di kolomnya, sehingga titik tengah
       angka statistik lurus vertikal dengan titik tengah kolom ikon di
       bawahnya. Tanpa ikon, tanpa kotak, dan tanpa garis pemisah.
       ------------------------------------------------------------------ */
    .dashboard-section .dash-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 0.25rem;
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
        transition: background-color 0.15s ease;
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

    .dashboard-section .dash-stat-value {
        display: block;
        font-size: 1.75rem;         /* 28px di HP, 32px di desktop */
        font-weight: 800;           /* angka besar & tebal */
        letter-spacing: -0.02em;
        color: #0f172a;             /* angka: hitam pekat, sama seperti label */
        line-height: 1.1;
        margin-bottom: 0.25rem;     /* jarak rapat angka -> label (4px) */
    }

    /* Palet warna angka dashboard — dipakai SAMA PERSIS di bagian "Data Sekolah"
       dan "Ketidakhadiran Hari Ini" (satu biru, satu kuning, satu hijau, satu
       merah). Class KHUSUS elemen dashboard ini; definisi class bersama
       (text-danger, text-primary, text-purple, dll) tidak disentuh. */
    .dashboard-section .dash-stat-value.is-sidebar-blue,
    .dashboard-section .dash-att-value.is-sidebar-blue {
        color: #3b62f6 !important;   /* biru, sama dgn background sidebar */
    }
    .dashboard-section .dash-stat-value.is-yellow,
    .dashboard-section .dash-att-value.is-yellow {
        color: #eab308 !important;   /* kuning */
    }
    .dashboard-section .dash-stat-value.is-green {
        color: #16a34a !important;   /* hijau */
    }
    .dashboard-section .dash-stat-value.is-red,
    .dashboard-section .dash-att-value.is-red {
        color: #dc3545 !important;   /* merah */
    }

    @media (min-width: 768px) {
        /* 4 kolom sejajar dengan gap yang sama persis seperti grid Pintasan
           Cepat. Padding kiri/kanan sel 0 supaya sejajar dengan isi konten. */
        .dashboard-section .dash-stats-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            column-gap: 0.5rem;
        }
        .dashboard-section .dash-stat-cell {
            padding: 1.35rem 0 0;
        }
        .dashboard-section .dash-stat-label {
            font-size: 0.7rem;
        }
        .dashboard-section .dash-stat-value {
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
    .dashboard-section .dash-stat-cell:focus-visible,
    .dashboard-section .dash-shortcut-item:focus-visible {
        outline: 2px solid var(--dash-accent);
        outline-offset: 2px;
    }

    /* ------------------------------------------------------------------
       BAGIAN 2: PINTASAN CEPAT — grid ikon 4 kolom (2 baris x 4 kolom).
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
       BARIS KETIDAKHADIRAN & KOTAK JAM — tanpa kartu: tanpa background,
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

    .absence-row-interactive {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }

    /* Catatan: ikon status (plester / amplop / tanda seru) di baris
       Sakit, Izin, dan Alpha SUDAH DIHAPUS dari markup, jadi seluruh
       styling ikon status di bawah ini tidak lagi dipakai. */

    .dashboard-section .absence-row-inner {
        gap: 0.75rem !important;
    }

    /* Tiga baris Sakit/Izin/Alpha: bertumpuk di HP, SEJAJAR 3 KOLOM di
       desktop. Isi tiap item vertikal, rapat, dan rata tengah. */
    .dashboard-section .dash-absence-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 0.75rem;
    }
    @media (min-width: 992px) {
        .dashboard-section .dash-absence-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.5rem;
        }
    }

    /* Jarak judul "Ketidakhadiran Hari Ini" -> baris tiga item = 20px. */
    .dashboard-section .dash-att-head {
        padding-bottom: 0 !important;
        margin-bottom: 1.25rem !important;
    }

    /* Angka sebagai TEKS POLOS (tanpa kontainer/wadah), warna hitam sama seperti
       statistik, ukuran disamakan dengan Bagian A; label tetap di bawahnya. */
    .dashboard-section .dash-att-figure {
        margin-bottom: 0.25rem;
    }
    .dashboard-section .dash-att-value {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.1;
        color: #0f172a;
    }
    @media (min-width: 768px) {
        .dashboard-section .dash-att-value {
            font-size: 2rem;
        }
    }

    /* ==================================================================
       HANYA MOBILE (< 768px)
       4 statistik "Data Sekolah" SEJAJAR satu baris (4 kolom sama lebar)
       dan 3 item "Ketidakhadiran Hari Ini" sejajar satu baris (3 kolom),
       dengan angka & label yang mengecil supaya muat di layar 320-500px
       tanpa scroll horizontal. Desktop (md ke atas) TIDAK tersentuh: nilai
       aslinya tetap dipakai lewat blok min-width 768px / 992px di atas.
       Blok ini diletakkan SESUDAH aturan dasar karena specificity-nya sama.
       ================================================================== */
    @media (max-width: 767.98px) {
        /* Data Sekolah: 4 kolom sejajar; align-items start supaya semua
           angka berada pada satu garis despite label 2 baris. */
        .dashboard-section .dash-stats-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            column-gap: 0.25rem;
            align-items: start;
        }
        .dashboard-section .dash-stat-cell {
            padding: 0.875rem 0.25rem 0;   /* padding kolom kecil */
        }
        .dashboard-section .dash-stat-value {
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

        /* Ketidakhadiran Hari Ini: 3 kolom sejajar, angka sama ukurannya. */
        .dashboard-section .dash-absence-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.5rem;
            align-items: start;
        }
        .dashboard-section .dash-att-value {
            font-size: 1.375rem;            /* 22px */
        }
        /* Label (Sakit / Izin / Alpha) disamakan dengan label statistik HP. */
        .dashboard-section .dash-absence-grid .fw-bold {
            font-size: 0.6875rem !important;  /* 11px */
        }
    }

    /* Hover baris ketidakhadiran: aksen biru tipis (bukan abu). */
    .dashboard-section .absence-row-interactive:hover,
    .dashboard-section .absence-row-interactive:active,
    .dashboard-section .absence-row-interactive:focus,
    .dashboard-section .absence-row-interactive.absence-row-active {
        background-color: rgba(37, 99, 235, 0.05);
        border: 0;
        box-shadow: none;
        outline: 0;
        transform: none;
    }

    /* ===== PENAKLUK BORDER DARI CSS LAYOUT (khusus 5 elemen) =====
       Definisi class di layouts/app.blade.php tidak diubah (dipakai bersama),
       dan <style> layout dimuat SESUDAH style push halaman, jadi aturannya
       tetap menang di browser:
         .operasional-card-interactive { border: 1px solid var(--clean-line) !important }
         .absence-row-interactive      { border: 1px solid var(--clean-line) !important }
         .card                         { box-shadow: 0 1px 2px rgba(15,23,42,.06) !important }
       Class .dash-nobox hanya dipakai pada 5 elemen tersebut (baris
       Sakit/Izin/Alpha + 2 kotak jam), jadi halaman lain tidak ikut berubah. */
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
           di atas, Ketidakhadiran Hari Ini di bawahnya): 32px. */
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
        }
        .dashboard-clock .clock-value {
            font-size: 1.75rem !important;
            margin-top: 0.15rem !important;
        }
        .dashboard-clock .clock-unit {
            font-size: 0.7rem !important;
            margin-top: 0.1rem !important;
        }
        .dashboard-schedule-btn {
            min-height: 36px !important;
            padding: 0 0.75rem !important;
            margin-top: var(--dash-gap) !important;
            font-size: 0.7rem !important;
            border-radius: var(--dash-radius) !important;
        }

        /* Footer grafik (Garis Tren ... Total): teks biasa. */
        .dashboard-trend-footer {
            border-top: 0 !important;
            padding-top: 0 !important;
            margin-top: 0.5rem !important;
        }

        /* GRUP TAB Harian/Mingguan/Bulanan — satu-satunya elemen yang boleh
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
        }
        .dash-seg .dash-seg__btn:first-child {
            border-left: 0;
        }
        .dash-seg .dash-seg__btn:hover {
            background-color: rgba(37, 99, 235, 0.06);
            color: #0f172a;
        }
        .dash-seg .dash-seg__btn.is-active,
        .dash-seg .dash-seg__btn--solid {
            background-color: #2563eb;
            color: #ffffff;
        }
        .dash-seg .dash-seg__btn.is-active:hover,
        .dash-seg .dash-seg__btn--solid:hover {
            background-color: #1d4ed8;
            color: #ffffff;
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
    <!-- 1. STATISTIK — satu blok putih, grid 2 kolom x 2 baris, TANPA ikon,      -->
    <!--    tanpa kotak, dan tanpa garis pemisah. Empat sel: label kecil, angka    -->
    <!--    besar, keterangan kecil. Tautan & data dinamis tidak diubah.          -->
    <!-- ========================================================================= -->
    <div class="dashboard-section">
        <div class="dash-card">
            <!-- Judul bagian statistik: class & gaya SAMA PERSIS dengan
                 judul "Pintasan Cepat" (rata kiri, jarak ke isi identik). -->
            <div class="dash-shortcut-head">
                <span class="dash-card-title">Data Sekolah</span>
            </div>

            <div class="dash-stats-grid">

                <!-- 1. Siswa Aktif (angka memakai warna ikon "Data Siswa") -->
                <a href="{{ panel_route('students.index') }}" class="dash-stat-cell" title="Buka data siswa aktif">
                    <strong class="dash-stat-value is-sidebar-blue">{{ number_format($totalStudents ?? 0, 0, ',', '.') }}</strong>
                    <span class="dash-stat-label">Siswa Aktif</span>
                </a>

                <!-- 2. Rombongan Belajar (angka memakai warna ikon "Data Kelas") -->
                <a href="{{ panel_route('classes.index') }}" class="dash-stat-cell" title="Buka data rombongan belajar">
                    <strong class="dash-stat-value is-yellow">{{ number_format($totalClasses ?? 0, 0, ',', '.') }}</strong>
                    <span class="dash-stat-label">Rombongan Belajar</span>
                </a>

                <!-- 3. Guru Aktif (admin bisa klik, guru hanya lihat) -->
                @if(is_admin())
                <a href="{{ route('admin.guru.index') }}" class="dash-stat-cell" title="Buka data guru">
                @else
                <div class="dash-stat-cell" title="Data guru">
                @endif
                    <strong class="dash-stat-value is-green">{{ number_format($totalTeachers ?? 0, 0, ',', '.') }}</strong>
                    <span class="dash-stat-label">Guru Aktif</span>
                @if(is_admin())
                </a>
                @else
                </div>
                @endif

                <!-- 4. Tidak Hadir (angka memakai warna ikon "Hari Libur") -->
                <a href="{{ panel_route('presensi.index', ['tanggal' => $dateString]) }}" class="dash-stat-cell" title="Lihat ketidakhadiran hari ini">
                    <strong class="dash-stat-value is-red">{{ number_format($totalKetidakhadiran ?? 0, 0, ',', '.') }}</strong>
                    <span class="dash-stat-label">Tidak Hadir</span>
                </a>

            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. PINTASAN CEPAT — judul lalu grid ikon 4 KOLOM yang memanjang penuh     -->
    <!--     (2 baris x 4 kolom). Tanpa kotak/border per item: hanya wadah ikon    -->
    <!--     rounded-square berlatar lembut per warna ikon + satu label pendek.    -->
    <!--     Subjudul kartu lama dihapus; ikon & tautan tetap sama persis.         -->
    <!-- ========================================================================= -->
    <div class="dashboard-section">
        <div class="dash-card">
            <div class="dash-shortcut-head">
                <i class='bx bx-zap text-warning fs-4'></i>
                <span class="dash-card-title">Pintasan Cepat</span>
            </div>

            <div class="dash-shortcut-grid">

                <!-- 1. Presensi Hari Ini (ikon QR/Scan) — menuju HALAMAN PRESENSI -->
                <a href="{{ panel_route('absensi.index') }}"
                   class="dash-shortcut-item"
                   title="Buka halaman Presensi (Scanner QR &amp; Mode Gerbang)">
                    <span class="dash-sc-icon" style="--sc-soft: #dbeafe;">
                        <i class='bx bx-qr-scan text-primary'></i>
                    </span>
                    <span class="dash-sc-label">Presensi Hari Ini</span>
                </a>

                <!-- 2. Catatan Kehadiran (ikon List/Ceklis) — HALAMAN KEHADIRAN -->
                <a href="{{ panel_route('kehadiran') }}"
                   class="dash-shortcut-item"
                   title="Lihat Log Kehadiran &amp; Presensi Harian">
                    <span class="dash-sc-icon" style="--sc-soft: #dcfce7;">
                        <i class='bx bx-list-check text-success'></i>
                    </span>
                    <span class="dash-sc-label">Catatan Kehadiran</span>
                </a>

                <!-- 3. Rekapitulasi Presensi (ikon PDF) -->
                <a href="{{ panel_route('rekap') }}"
                   class="dash-shortcut-item"
                   title="Laporan Rekapitulasi &amp; Ekspor Dokumen">
                    <span class="dash-sc-icon" style="--sc-soft: #fee2e2;">
                        <i class='bx bxs-file-pdf text-danger'></i>
                    </span>
                    <span class="dash-sc-label">Rekap Presensi</span>
                </a>

                <!-- 4. Master Data Siswa (ikon User) -->
                <a href="{{ panel_route('students.index') }}"
                   class="dash-shortcut-item"
                   title="Kelola Master Data Siswa &amp; Generate QR">
                    <span class="dash-sc-icon" style="--sc-soft: #cffafe;">
                        <i class='bx bx-user text-info'></i>
                    </span>
                    <span class="dash-sc-label">Data Siswa</span>
                </a>

                <!-- 5. Master Data Guru (ikon Guru) - khusus admin -->
                @if(is_admin())
                <a href="{{ route('admin.guru.index') }}"
                   class="dash-shortcut-item"
                   title="Kelola Data Guru">
                    <span class="dash-sc-icon" style="--sc-soft: #f3e8ff;">
                        <i class='bx bx-user-pin text-purple'></i>
                    </span>
                    <span class="dash-sc-label">Data Guru</span>
                </a>
                @endif

                <!-- 6. Master Data Kelas (ikon Gedung) -->
                <a href="{{ panel_route('classes.index') }}"
                   class="dash-shortcut-item"
                   title="Kelola Master Rombongan Belajar &amp; Kelas">
                    <span class="dash-sc-icon" style="--sc-soft: #fef3c7;">
                        <i class='bx bx-buildings text-warning'></i>
                    </span>
                    <span class="dash-sc-label">Data Kelas</span>
                </a>

                <!-- 7. Master Hari Libur (ikon Kalender) - khusus admin -->
                @if(is_admin())
                <a href="{{ panel_route('holidays.index') }}"
                   class="dash-shortcut-item"
                   title="Kelola Kalender Hari Libur Nasional &amp; Sekolah">
                    <span class="dash-sc-icon" style="--sc-soft: #fee2e2;">
                        <i class='bx bx-calendar-x text-danger'></i>
                    </span>
                    <span class="dash-sc-label">Hari Libur</span>
                </a>
                @endif

                <!-- 8. Pengaturan Sistem (ikon Gear) - khusus admin -->
                @if(is_admin())
                <a href="{{ panel_route('settings.index') }}"
                   class="dash-shortcut-item"
                   title="Konfigurasi Profil Sekolah, Jam Masuk, &amp; Sistem">
                    <span class="dash-sc-icon" style="--sc-soft: #e2e8f0;">
                        <i class='bx bx-cog text-secondary'></i>
                    </span>
                    <span class="dash-sc-label">Pengaturan</span>
                </a>
                @endif

            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. SECTION TENGAH: GRAFIK GARIS (penuh lebar) & KETIDAKHADIRAN DI BAWAHNYA -->
    <!-- ========================================================================= -->
    <div class="row g-3 dashboard-section">

        <!-- KOLOM KIRI (70%): GRAFIK GARIS KEHADIRAN (mode: harian/mingguan/bulanan) -->
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
                            <div class="chart-mode-toggle dash-seg" role="group" aria-label="Mode grafik kehadiran">
                                @foreach(['harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan'] as $modeKey => $modeLabel)
                                    <a href="{{ route(panel_role() === 'guru' ? 'guru.dashboard' : 'admin.dashboard', array_filter(['tanggal' => ($isToday ?? true) ? null : ($dateString ?? null), 'mode' => $modeKey, 'status' => request('status')])) }}"
                                       class="chart-mode-btn dash-seg__btn {{ ($chartMode ?? 'mingguan') === $modeKey ? 'active dash-seg__btn--solid' : '' }}"
                                       title="Tampilkan grafik {{ strtolower($modeLabel) }}">{{ $modeLabel }}</a>
                                @endforeach
                            </div>

                            <span class="badge bg-light text-secondary rounded-pill px-2 px-md-3 py-1 py-md-1.5 fw-semibold flex-shrink-0 text-nowrap dash-period" style="font-size: 0.68rem;">
                                {{ $chartPeriodLabel ?? (\Carbon\Carbon::parse($startOfWeek)->translatedFormat('d M') . ' - ' . \Carbon\Carbon::parse($endOfWeek)->translatedFormat('d M Y')) }}
                            </span>
                        </div>
                    </div>

                    <!-- Area Grafik: canvas digambar begitu periodenya punya titik -
                         mode Harian (7 hari), Mingguan (per rombel), maupun
                         Bulanan (12 bulan) SELALU punya titik, sehingga ketiga
                         mode benar-benar menggambar grafik. Status "belum ada
                         catatan" hanya jadi keterangan singkat di bawah grafik,
                         BUKAN ikon error (ikon bx-error sebelumnya membuat
                         grafik yang isinya kosong terlihat seperti error). -->
                    <div class="pt-1 pb-1">
                        @if(($totalChartPoints ?? 0) > 0)
                            <div class="chart-canvas-wrap" style="position: relative; height: 240px; width: 100%;">
                                <canvas id="attendanceLineChart"></canvas>
                            </div>
                            @unless($chartHasData ?? false)
                                <p class="chart-empty-note text-center text-muted mb-0 mt-1">
                                    {{ $chartEmptyText ?? 'Belum ada data kehadiran pada periode ini.' }}
                                </p>
                            @endunless
                        @else
                            <p class="w-100 text-center text-muted small empty-state mb-0 py-4">
                                {{ $chartEmptyText ?? 'Belum ada data kehadiran pada periode ini.' }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Keterangan Garis Tren & Total (selalu satu baris, tidak bertumpuk) -->
                <div class="mt-3 pt-2.5 d-flex flex-nowrap align-items-center justify-content-between gap-2 text-muted dashboard-trend-footer" style="font-size: 0.72rem;">
                    <div class="d-flex align-items-center gap-2 min-w-0 text-truncate">
                        <span class="text-truncate"><strong class="text-dark">Garis Tren Kehadiran</strong> <span class="d-none d-sm-inline">({{ $chartScopeText ?? (($classesList->first()->name ?? '7A') . ' s/d ' . ($classesList->last()->name ?? '9C')) }})</span></span>
                    </div>
                    <span class="fw-semibold text-dark flex-shrink-0 text-nowrap">{{ $chartTotalText ?? ('Total: ' . count($classesAttendance ?? []) . ' Rombel') }}</span>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN (30%): KETIDAKHADIRAN HARI INI (Sakit / Izin / Alpha) -->
        <div class="col-12">
            <div class="card border-0 rounded-4 p-3 p-md-4 h-100 d-flex flex-column justify-content-between dashboard-panel dashboard-box">
                <div>
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-3 clean-line-off dash-att-head">
                        <div>
                            <h6 class="fw-bold text-dark mb-0.5 dashboard-box-title">Ketidakhadiran Hari Ini</h6>
                            <p class="text-secondary small mb-0 dashboard-box-sub">Siswa yang berhalangan hadir</p>
                        </div>
                    </div>

                    <!-- 3 Baris Detail Ketidakhadiran Hari Ini (Interaktif) -->
                    <div class="dash-absence-grid">

                        <!-- 1. Sakit -->
                        {{-- Baris Absence -> REKAP HARIAN (tanggal yang sedang ditampilkan), BUKAN
                             kembali ke dashboard. Halaman Rekap BELUM punya
                             filter status (RekapController hanya membaca
                             type/class_id/date/month/year), jadi parameter
                             ?status= sengaja TIDAK ditambahkan - tidak ada
                             backend yang diubah. --}}
                        <a href="{{ panel_route('rekap', ['type' => 'harian', 'date' => $dateString]) }}"
                           class="absence-row-interactive dashboard-inner-card dash-nobox text-decoration-none d-flex flex-column align-items-center text-center {{ request('status') === 'Sakit' ? 'absence-row-active' : '' }}"
                           title="Buka Rekap Presensi harian untuk siswa sakit">
                            <span class="d-block dash-att-figure">
                                <span class="dash-att-value is-sidebar-blue font-monospace">{{ $countSakit ?? 0 }}</span>
                            </span>
                            <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.9rem;">Sakit @if(request('status') === 'Sakit')<span class="absence-row-active-badge">Aktif</span>@endif</span>
                        </a>

                        <!-- 2. Izin -->
                        <a href="{{ panel_route('rekap', ['type' => 'harian', 'date' => $dateString]) }}"
                           class="absence-row-interactive dashboard-inner-card dash-nobox text-decoration-none d-flex flex-column align-items-center text-center {{ request('status') === 'Izin' ? 'absence-row-active' : '' }}"
                           title="Buka Rekap Presensi harian untuk siswa izin">
                            <span class="d-block dash-att-figure">
                                <span class="dash-att-value is-yellow font-monospace">{{ $countIzin ?? 0 }}</span>
                            </span>
                            <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.9rem;">Izin @if(request('status') === 'Izin')<span class="absence-row-active-badge">Aktif</span>@endif</span>
                        </a>

                        <!-- 3. Alpha -->
                        <a href="{{ panel_route('rekap', ['type' => 'harian', 'date' => $dateString]) }}"
                           class="absence-row-interactive dashboard-inner-card dash-nobox text-decoration-none d-flex flex-column align-items-center text-center {{ request('status') === 'Alfa' ? 'absence-row-active' : '' }}"
                           title="Buka Rekap Presensi harian untuk siswa alpha">
                            <span class="d-block dash-att-figure">
                                <span class="dash-att-value is-red font-monospace">{{ $countAlfa ?? 0 }}</span>
                            </span>
                            <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.9rem;">Alpha @if(request('status') === 'Alfa')<span class="absence-row-active-badge">Aktif</span>@endif</span>
                        </a>

                    </div>
                </div>

                <!-- Tombol Menuju Tabel Presensi Harian -->
                <div class="pt-3 mt-3 clean-line-off">
                    <a href="{{ panel_route('presensi.index', array_filter(['tanggal' => $dateString, 'status' => request('status')])) }}"
                       class="btn btn-primary rounded-3 shadow-sm w-100 fw-bold py-2.5 d-inline-flex align-items-center justify-content-center gap-2 btn-modern-smooth"
                       style="font-size: 0.85rem;">
                        <span>Buka Tabel Presensi Hari Ini</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 5. SECTION BAWAH: JADWAL OPERASIONAL SEKOLAH                              -->
    <!-- ========================================================================= -->
    <div class="row g-3 dashboard-section">
        <div class="col-12">
            <div class="card border-0 rounded-4 p-3 p-md-4 h-100 d-flex flex-column justify-content-between dashboard-panel dashboard-box">
                <div>
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-3 dashboard-divider-off">
                        <div class="d-flex align-items-center gap-2.5">
                            <span class="fw-bold text-dark dashboard-box-title">Jadwal Operasional Sekolah</span>
                        </div>
                        <span class="fw-bold d-inline-flex align-items-center gap-1.5" style="font-size: 0.72rem; color: #16a34a;">
                            <span class="d-inline-block rounded-circle" style="width: 8px; height: 8px; background: #16a34a;"></span>
                            Aktif
                        </span>
                    </div>

                    <div class="row g-2.5">
                        <div class="col-6">
                            @if(is_admin())
                            <a href="{{ route('admin.pengaturan.jadwal') }}" class="card border-0 rounded-3 p-3 text-center d-block text-decoration-none h-100 operasional-card-interactive dashboard-inner-card dashboard-clock dash-nobox" title="Ubah jam masuk">
                            @else
                            <div class="card border-0 rounded-3 p-3 text-center d-block text-decoration-none h-100 operasional-card-interactive dashboard-inner-card dashboard-clock dash-nobox" title="Jam masuk sekolah">
                            @endif
                                <span class="text-uppercase fw-semibold text-secondary d-block clock-label" style="font-size: 0.7rem; letter-spacing: 0.04em;">Jam Masuk</span>
                                <strong class="text-dark mt-1.5 d-block font-monospace clock-value" style="font-size: 1.3rem; font-weight: 700;">
                                    {{ $checkInTime ?? '06:45' }}
                                </strong>
                                <span class="d-block text-secondary mt-1 clock-unit" style="font-size: 0.68rem;">WIB</span>
                            @if(is_admin())
                            </a>
                            @else
                            </div>
                            @endif
                        </div>
                        <div class="col-6">
                            @if(is_admin())
                            <a href="{{ route('admin.pengaturan.jadwal') }}" class="card border-0 rounded-3 p-3 text-center d-block text-decoration-none h-100 operasional-card-interactive dashboard-inner-card dashboard-clock dash-nobox" title="Ubah batas terlambat">
                            @else
                            <div class="card border-0 rounded-3 p-3 text-center d-block text-decoration-none h-100 operasional-card-interactive dashboard-inner-card dashboard-clock dash-nobox" title="Batas keterlambatan sekolah">
                            @endif
                                <span class="text-uppercase fw-semibold text-secondary d-block clock-label" style="font-size: 0.7rem; letter-spacing: 0.04em;">Batas Terlambat</span>
                                <strong class="text-amber-600 mt-1.5 d-block font-monospace clock-value" style="font-size: 1.3rem; font-weight: 700;">
                                    {{ $lateLimitTime ?? '07:15' }}
                                </strong>
                                <span class="d-block text-secondary mt-1 clock-unit" style="font-size: 0.68rem;">WIB</span>
                            @if(is_admin())
                            </a>
                            @else
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if(is_admin())
                <!-- Tombol Ubah Pengaturan Jadwal (khusus admin) -->
                <a href="{{ route('admin.pengaturan.jadwal') }}"
                   class="btn btn-primary rounded-3 shadow-sm w-100 mt-3 py-2.5 px-3 fw-semibold text-white text-decoration-none d-inline-flex align-items-center justify-content-center gap-2 btn-modern-smooth dashboard-schedule-btn"
                   style="font-size: 0.85rem;">
                    <span>Ubah Pengaturan Jadwal</span>
                </a>
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
       - showPicker() dipicu dari event klik (user gesture), sehingga tidak
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
            note.textContent = 'Grafik belum dapat dimuat (perpustakaan grafik gagal dimuat).';
            if (ctx.parentNode) {
                ctx.parentNode.insertAdjacentElement('afterend', note);
            }
            return;
        }

        // Label & data sudah dihitung server-side sesuai mode aktif
        // (harian / mingguan / bulanan) - lihat AdminDashboardController.
        const chartLabels = {!! json_encode($chartLabels ?? []) !!};
        const chartData = {!! json_encode($chartValues ?? []) !!};

        // Guard tambahan: kalau label kosong, jangan instantiate Chart.js
        // supaya tidak error (kartu sudah menampilkan "Belum ada data").
        if (!Array.isArray(chartLabels) || chartLabels.length === 0) {
            return;
        }

        const canvasContext = ctx.getContext('2d');
        const gradient = canvasContext.createLinearGradient(0, 0, 0, 220);
        gradient.addColorStop(0, 'rgba(37, 99, 235, 0.20)');
        gradient.addColorStop(1, 'rgba(37, 99, 235, 0.01)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Tingkat Kehadiran (%)',
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
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#ffffff',
                        bodyColor: '#cbd5e1',
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function (context) {
                                return 'Tingkat Kehadiran: ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#475569',
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 12,
                                weight: '600'
                            },
                            padding: 8
                        }
                    },
                    y: {
                        min: 0,
                        max: 100,
                        grid: {
                            color: '#f1f5f9'
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