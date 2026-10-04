@extends('layouts.app')

@section('title', is_admin() ? 'Dashboard Admin' : 'Dashboard Guru')
@section('page_title', is_admin() ? 'Dashboard Administrator' : 'DASHBOARD GURU')
@section('page_subtitle', is_admin() ? 'Sistem Presensi Terintegrasi' : 'Selamat Datang, ' . (Auth::user()->name ?? 'Guru'))

{{-- Kanvas dikunci setinggi satu layar (sama seperti halaman acuan Presensi /
     Data Guru / Data Kelas) dan isi dashboard menggulir di dalam kanvas.
     Aturan class ini ada di layout bersama, layouts/app.blade.php. --}}
@section('canvas_class', 'page-canvas-fixed')

@section('page_header_right')
<!-- Tanggal di Sisi Kanan Modern
     Blok ini kini bisa diklik: ikon kalender + blok teks dibungkus <form> GET
     yang memuat <input type="date"> (pola yang sudah dipakai di halaman lain:
     academic_years & rekap). Memilih tanggal hanya submitting GET ke halaman
     dashboard yang sama dengan ?tanggal=YYYY-MM-DD, lalu controller
     menghitung ulang seluruh statistik, grafik, dan ketidakhadiran.
     Tampilan (ikon, teks, ukuran, warna) TIDAK diubah. -->
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
    /* Ubah background utama halaman menjadi abu-abu terang (bg-light) agar kartu putih terlihat menonjol */
    body,
    .content-scroll-wrapper,
    .content-scroll-wrapper main {
        background-color: #f8fafc !important;
    }

    .text-purple {
        color: #9333ea !important;
    }

    /* Efek Interaktif 3 Kartu Statistik Atas (Hover Transform, Scale Up, Shadow) */
    .stat-card-modern {
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
    }
    .stat-card-modern:hover {
        transform: translateY(-4px) scale(1.015);
        box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.08), 0 4px 8px -2px rgba(0, 0, 0, 0.03) !important;
    }
    .stat-card-modern:active {
        transform: translateY(-1px) scale(0.99);
    }

    .stat-card-modern i {
        transition: transform 0.25s ease;
        display: inline-block;
    }
    .stat-card-modern:hover i {
        transform: scale(1.12);
    }

    /* Area Ketidakhadiran Hari Ini Interaktif (Hover Background) */
    .absence-row-interactive {
        background-color: #f8fafc;
        border-radius: 0.75rem;
        padding: 0.9rem 1.1rem;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
    }
    .absence-row-interactive:hover {
        background-color: #eff6ff !important;
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
    }
    .absence-row-interactive:active {
        transform: scale(0.99);
    }

    .absence-row-interactive i {
        transition: transform 0.2s ease;
        display: inline-block;
    }

    /* ===== Ikon status pada baris Ketidakhadiran (Sakit / Izin / Alpha) =====
       Satu kelas dipakai KETIGA baris supaya ukuran, bentuk, dan posisinya
       selalu identik; yang berbeda hanya warna. Warna disuntikkan per baris
       lewat dua custom property (--absence-icon-color & --absence-icon-bg),
       sehingga tidak perlu menulis ulang ukuran per status. */
    .absence-status-icon {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1.15rem;
        line-height: 1;
        color: var(--absence-icon-color, #2563eb);
        background-color: var(--absence-icon-bg, #dbeafe);
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Hover: kotak ikon ikut membesar sedikit (bukan ikon di dalamnya), supaya
       posisi ikon, judul, dan keterangan tidak terlihat bergeser. */
    .absence-row-interactive:hover .absence-status-icon {
        transform: scale(1.08);
    }
    .absence-row-interactive:active .absence-status-icon {
        transform: scale(0.96);
    }

    /* Penanda jelas saat status ketidakhadiran sedang aktif sebagai filter */
    .absence-row-interactive.absence-row-active {
        background-color: #eff6ff !important;
        box-shadow: 0 0 0 2px #2563eb inset;
        transform: translateX(4px);
    }
    .absence-row-active-badge {
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

    /* Area Pintasan Cepat Card Mandiri Clickable (Hover Scale) */
    .shortcut-card-interactive {
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
    }
    .shortcut-card-interactive:hover {
        background-color: #ffffff !important;
        transform: translateY(-4px) scale(1.025);
        box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04) !important;
    }
    .shortcut-card-interactive:active {
        transform: scale(0.98);
    }

    .shortcut-card-interactive i {
        transition: transform 0.2s ease;
        display: inline-block;
    }
    .shortcut-card-interactive:hover i {
        transform: scale(1.15);
    }

    /* Sub-card Jadwal Operasional Interaktif */
    .operasional-card-interactive {
        transition: all 0.2s ease;
    }
    .operasional-card-interactive:hover {
        background-color: #ffffff !important;
        transform: translateY(-3px);
        box-shadow: 0 8px 16px -2px rgba(0, 0, 0, 0.06) !important;
    }

    /* Tombol Modern Efek Hover Mulus */
    .btn-modern-smooth {
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .btn-modern-smooth:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.28) !important;
    }
    .btn-modern-smooth:active {
        transform: translateY(0);
    }

    /* ===== Tanggal di Header (klik untuk buka date picker) =====
       Input[type=date] disembunyikan sepenuhnya, lalu dipicu dari ikon
       kalender lewat .showPicker() (browser modern). Kalau API tidak
       tersedia, input dikembalikan ke posisi normal agar tetap bisa dipakai
       (fallback, bukan ketergantungan mutlak pada API tersebut). */
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
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        display: inline-flex;
        align-items: center;
    }

    .dashboard-date-trigger:hover {
        transform: scale(1.08);
    }

    .dashboard-date-text {
        cursor: pointer;
    }

    /* ===== Toggle Mode Grafik (Harian / Mingguan / Bulanan) =====
     Gaya meniru komponen badge pill yang sudah dipakai di header kartu
     grafik (badge bg-light text-secondary rounded-pill). Hanya ditambahkan
     state aktif + hover; warna & font mengikuti nilai yang sudah ada. */
    .chart-mode-toggle {
        display: inline-flex;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 9999px;
        padding: 2px;
        gap: 2px;
    }

    .chart-mode-toggle .chart-mode-btn {
        border: none;
        background: transparent;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        padding: 0.25rem 0.6rem;
        border-radius: 9999px;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.2s ease, color 0.2s ease;
        white-space: nowrap;
    }

    .chart-mode-toggle .chart-mode-btn:hover {
        color: #2563eb;
    }

    .chart-mode-toggle .chart-mode-btn.active {
        background-color: #2563eb;
        color: #ffffff;
    }

    @media (max-width: 767.98px) {
        .stat-card-modern {
            min-height: 118px;
            padding: 0.75rem !important;
            gap: 0.4rem;
            border-radius: 12px !important;
        }
        .stat-card-modern > div {
            min-width: 0;
        }
        .stat-card-modern span.text-uppercase {
            font-size: 0.62rem !important;
            line-height: 1.2;
            letter-spacing: 0.02em !important;
        }
        .stat-card-modern h3 {
            font-size: 1.45rem !important;
            margin-bottom: 0.2rem !important;
        }
        .stat-card-modern span.small {
            font-size: 0.67rem !important;
            line-height: 1.2;
        }
        .stat-card-modern > i {
            flex-shrink: 0;
            font-size: 1.7rem !important;
        }
        .dashboard-panel {
            padding: 0.75rem !important;
            border-radius: 12px !important;
        }
        /* Ikon status tetap sama untuk ketiga baris, hanya dikecilkan agar
           judul + keterangan + angka tetap muat di layar sempit. */
        .absence-row-interactive {
            padding: 0.7rem 0.8rem;
        }
        .absence-status-icon {
            width: 32px;
            height: 32px;
            flex: 0 0 32px;
            font-size: 1rem;
            border-radius: 9px;
        }
        .stat-card-modern:hover,
        .shortcut-card-interactive:hover,
        .operasional-card-interactive:hover {
            transform: none;
        }
    }
</style>
@endpush

@section('content')

    <!-- ========================================================================= -->
    <!-- 1. BARIS ATAS: 3 KARTU STATISTIK UTAMA (HOVER SCALE UP & SOFT SHADOW)    -->
    <!-- ========================================================================= -->
    <div class="row g-2 g-md-3 mb-3 mb-md-4">
        
        <!-- Card 1: Total Siswa Aktif -->
        <div class="col-6 col-md-4">
            <a href="{{ panel_route('students.index') }}" class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 h-100 d-flex flex-row align-items-center justify-content-between text-decoration-none stat-card-modern" title="Buka data siswa aktif">
                <div>
                    <span class="text-secondary fw-semibold text-uppercase d-block mb-1.5" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                        Total Siswa Aktif
                    </span>
                    <h3 class="fw-bolder mb-1 text-dark" style="font-size: 2rem; letter-spacing: -0.02em;">
                        {{ number_format($totalStudents ?? 0, 0, ',', '.') }}
                    </h3>
                    <span class="text-secondary small d-block" style="font-size: 0.78rem;">
                        Siswa terdaftar aktif di sekolah
                    </span>
                </div>
                <i class='bx bxs-user-pin text-primary' style="font-size: 2.5rem;"></i>
            </a>
        </div>

        <!-- Card 2: Total Rombongan Belajar -->
        <div class="col-6 col-md-4">
            <a href="{{ panel_route('classes.index') }}" class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 h-100 d-flex flex-row align-items-center justify-content-between text-decoration-none stat-card-modern" title="Buka data rombongan belajar">
                <div>
                    <span class="text-secondary fw-semibold text-uppercase d-block mb-1.5" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                        Total Rombongan Belajar
                    </span>
                    <h3 class="fw-bolder mb-1 text-dark" style="font-size: 2rem; letter-spacing: -0.02em;">
                        {{ number_format($totalClasses ?? 0, 0, ',', '.') }}
                    </h3>
                    <span class="text-secondary small d-block" style="font-size: 0.78rem;">
                        Rombel aktif terdata
                    </span>
                </div>
                <i class='bx bxs-building-house text-info' style="font-size: 2.5rem;"></i>
            </a>
        </div>

        <!-- Card 3: Total Guru Aktif -->
        <div class="col-6 col-md-4">
            @if(is_admin())
            <a href="{{ route('admin.guru.index') }}" class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 h-100 d-flex flex-row align-items-center justify-content-between text-decoration-none stat-card-modern" title="Buka data guru">
            @else
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 h-100 d-flex flex-row align-items-center justify-content-between text-decoration-none stat-card-modern" title="Data guru">
            @endif
                <div>
                    <span class="text-secondary fw-semibold text-uppercase d-block mb-1.5" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                        Guru Aktif
                    </span>
                    <h3 class="fw-bolder mb-1 text-dark" style="font-size: 2rem; letter-spacing: -0.02em;">
                        {{ number_format($totalTeachers ?? 0, 0, ',', '.') }}
                    </h3>
                    <span class="text-secondary small d-block" style="font-size: 0.78rem;">
                        Tenaga pendidik terverifikasi
                    </span>
                </div>
                <i class='bx bxs-id-card text-success' style="font-size: 2.5rem;"></i>
            @if(is_admin())
            </a>
            @else
            </div>
            @endif
        </div>

        <div class="col-6 d-md-none">
            <a href="{{ panel_route('presensi.index', ['tanggal' => $dateString]) }}" class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 d-flex flex-row align-items-center justify-content-between text-decoration-none stat-card-modern" title="Lihat ketidakhadiran hari ini">
                <div>
                    <span class="text-secondary fw-semibold text-uppercase d-block mb-1.5" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                        Tidak Hadir Hari Ini
                    </span>
                    <h3 class="fw-bolder mb-1 text-dark" style="font-size: 2rem; letter-spacing: -0.02em;">
                        {{ number_format($totalKetidakhadiran ?? 0, 0, ',', '.') }}
                    </h3>
                    <span class="text-secondary small d-block" style="font-size: 0.78rem;">
                        Sakit, izin, atau alpha
                    </span>
                </div>
                <i class='bx bx-user-x text-danger' style="font-size: 2.5rem;"></i>
            </a>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 2. PINTASAN CEPAT (dipindah ke atas, tepat di bawah kartu statistik)        -->
    <!--     supaya modul cepat terlihat tanpa harus scroll. Isi & tampilan kartu  -->
    <!--     TIDAK diubah, hanya posisi & lebarnya.                                 -->
    <!-- ========================================================================= -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 h-100 d-flex flex-column justify-content-between dashboard-panel">
                <div>
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom border-light-subtle mb-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <i class='bx bx-zap text-warning fs-4'></i>
                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">Pintasan Cepat</span>
                        </div>
                        <span class="badge bg-light text-secondary rounded-pill px-3 py-1.5 fw-semibold" style="font-size: 0.72rem;">
                            Akses Cepat Modul
                        </span>
                    </div>

                    <!-- Grid 8 Pintasan Cepat (App Drawer Style) -->
                    <div class="row g-3">
                        
                        <!-- 1. Presensi Hari Ini (ikon QR/Kamera)
                             Kartu pertama, paling kiri baris pertama.
                             Arahkan ke HALAMAN PRESENSI (route yang sama dengan menu
                             Presensi di sidebar: absensi.index), BUKAN route scanner,
                             supaya kamera tidak ikut menyala. Tombol "Buka Scanner QR"
                             & "Mode Gerbang" tetap tersedia di halaman tujuan. -->
                        <div class="col-6 col-md-4 col-xl-3">
                            <a href="{{ panel_route('absensi.index') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Buka halaman Presensi (Scanner QR &amp; Mode Gerbang)">
                                <i class='bx bx-qr-scan text-primary fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Presensi Hari Ini</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Scanner QR Siswa</span>
                            </a>
                        </div>

                        <!-- 2. Catatan Kehadiran (ikon List/Ceklis)
                             Menuju HALAMAN KEHADIRAN (route yang sama dengan menu
                             Kehadiran di sidebar: kehadiran), bukan halaman Presensi,
                             supaya tidak tumpang tindih dengan kartu 1. -->
                        <div class="col-6 col-md-4 col-xl-3">
                            <a href="{{ panel_route('kehadiran') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Lihat Log Kehadiran &amp; Presensi Harian">
                                <i class='bx bx-list-check text-success fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Catatan Kehadiran</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Log Presensi Harian</span>
                            </a>
                        </div>

                        <!-- 3. Rekapitulasi Presensi (ikon Laporan/PDF) -->
                        <div class="col-6 col-md-4 col-xl-3">
                            <a href="{{ panel_route('rekap') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Laporan Rekapitulasi & Ekspor Dokumen">
                                <i class='bx bxs-file-pdf text-danger fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Rekap Presensi</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Laporan &amp; Ekspor</span>
                            </a>
                        </div>

                        <!-- 4. Master Data Siswa (ikon User/Siswa) -->
                        <div class="col-6 col-md-4 col-xl-3">
                            <a href="{{ panel_route('students.index') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Kelola Master Data Siswa & Generate QR">
                                <i class='bx bx-user text-info fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Data Siswa</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Master Biodata Siswa</span>
                            </a>
                        </div>

                        <!-- 5. Master Data Guru (ikon Guru/User Tie) - khusus admin -->
                        @if(is_admin())
                        <div class="col-6 col-md-4 col-xl-3">
                            <a href="{{ panel_route('teachers.index') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Kelola Data Guru">
                                <i class='bx bx-user-pin text-purple fs-2 mb-2' style="color: #9333ea;"></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Data Guru</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Guru Pengajar</span>
                            </a>
                        </div>
                        @endif

                        <!-- 6. Master Data Kelas (ikon Gedung/Kelas) -->
                        <div class="col-6 col-md-4 col-xl-3">
                            <a href="{{ panel_route('classes.index') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Kelola Master Rombongan Belajar & Kelas">
                                <i class='bx bx-buildings text-warning fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Data Kelas</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Rombongan Belajar</span>
                            </a>
                        </div>

                        <!-- 7. Master Hari Libur (ikon Kalender/Libur) - khusus admin -->
                        @if(is_admin())
                        <div class="col-6 col-md-4 col-xl-3">
                            <a href="{{ panel_route('holidays.index') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Kelola Kalender Hari Libur Nasional & Sekolah">
                                <i class='bx bx-calendar-x text-danger fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Hari Libur</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Kalender &amp; Libur</span>
                            </a>
                        </div>
                        @endif

                        <!-- 8. Pengaturan Sistem (ikon Gear) - khusus admin -->
                        @if(is_admin())
                        <div class="col-6 col-md-4 col-xl-3">
                            <a href="{{ panel_route('settings.index') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Konfigurasi Profil Sekolah, Jam Masuk, & Sistem">
                                <i class='bx bx-cog text-secondary fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Pengaturan</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Profil &amp; Konfigurasi</span>
                            </a>
                        </div>
                        @endif

                    </div>
                </div>

                <div class="mt-3 text-center">
                    <span class="text-secondary fw-medium" style="font-size: 0.74rem;">Semua tautan terhubung langsung ke modul administrasi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. AREA FILTER & KONTROL MONITORING (CARD UTAMA SHADOW-SM ROUNDED-4)     -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3.5">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <i class='bx bx-filter-alt text-primary fs-3'></i>
                    <div>
                        <h6 class="fw-bold text-dark mb-0.5" style="font-size: 0.98rem;">Monitoring &amp; Analitik Presensi</h6>
                        <p class="text-secondary small mb-0" style="font-size: 0.76rem;">
                            Periode Berjalan: {{ \Carbon\Carbon::parse($startOfWeek)->translatedFormat('d M') }} - {{ \Carbon\Carbon::parse($endOfWeek)->translatedFormat('d M Y') }}
                        </p>
                    </div>
                </div>
                {{-- CATATAN: tombol "Scan QR" di section ini sudah dihapus.
                     Akses scanner kini HANYA lewat pintasan "Presensi Hari Ini"
                     (tabel presensi) dan menu Presensi di sidebar, supaya user
                     tidak bisa terpental langsung ke kamera dari header analitik.
                     Dua tombol di bawah tetap, styling & posisinya tidak diubah. --}}
                <div class="d-flex align-items-center gap-2.5 flex-wrap">
                    <a href="{{ panel_route('presensi.index', ['tanggal' => $dateString]) }}" class="btn btn-primary rounded-3 shadow-sm px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2 btn-modern-smooth" style="font-size: 0.82rem;">
                        <span>Presensi Hari Ini</span>
                    </a>
                    <a href="{{ panel_route('rekap') }}" class="btn btn-primary rounded-3 shadow-sm px-4 py-2 fw-semibold text-white d-inline-flex align-items-center gap-2 btn-modern-smooth" style="font-size: 0.82rem;">
                        <span>Rekap Lengkap</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. SECTION TENGAH: GRAFIK GARIS (70%) & KETIDAKHADIRAN (30%)             -->
    <!-- ========================================================================= -->
    <div class="row g-3 mb-4">
        
        <!-- KOLOM KIRI (70%): CARD GRAFIK GARIS KEHADIRAN (mode: harian/mingguan/bulanan) -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 h-100 d-flex flex-column justify-content-between dashboard-panel">
                <div>
                    <div class="d-flex align-items-center justify-content-between gap-2 pb-3 border-bottom border-light-subtle mb-3">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <i class='bx bx-line-chart text-primary fs-4 flex-shrink-0'></i>
                            <span class="fw-bold text-dark text-truncate" style="font-size: 0.9rem;">{{ $chartTitle ?? 'Grafik Garis Kehadiran Mingguan' }}</span>
                        </div>

                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            {{-- TOGGLE MODE: Harian / Mingguan / Bulanan.
                                 Hanya tautan GET ke halaman yang sama dengan ?mode=...,
                                 jadi tidak ada endpoint atau JS tambahan. --}}
                            <div class="chart-mode-toggle" role="group" aria-label="Mode grafik kehadiran">
                                @foreach(['harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan'] as $modeKey => $modeLabel)
                                    <a href="{{ route(panel_role() === 'guru' ? 'guru.dashboard' : 'admin.dashboard', array_filter(['tanggal' => ($isToday ?? true) ? null : ($dateString ?? null), 'mode' => $modeKey, 'status' => request('status')])) }}"
                                       class="chart-mode-btn {{ ($chartMode ?? 'mingguan') === $modeKey ? 'active' : '' }}"
                                       title="Tampilkan grafik {{ strtolower($modeLabel) }}">{{ $modeLabel }}</a>
                                @endforeach
                            </div>

                            <span class="badge bg-light text-secondary rounded-pill px-2 px-md-3 py-1 py-md-1.5 fw-semibold flex-shrink-0 text-nowrap" style="font-size: 0.68rem;">
                                {{ $chartPeriodLabel ?? (\Carbon\Carbon::parse($startOfWeek)->translatedFormat('d M') . ' - ' . \Carbon\Carbon::parse($endOfWeek)->translatedFormat('d M Y')) }}
                            </span>
                        </div>
                    </div>

                    <!-- Canvas Line Chart Chart.js -->
                    <div class="pt-1 pb-1">
                        @if(($totalChartPoints ?? 0) > 0 && ($chartHasData ?? false))
                            <div style="position: relative; height: 240px; width: 100%;">
                                <canvas id="attendanceLineChart"></canvas>
                            </div>
                        @else
                            <div class="w-100 py-12 text-center text-muted small">
                                <i class='bx bx-line-chart fs-1 text-secondary opacity-50 d-block mb-1'></i>
                                {{ $chartEmptyText ?? 'Belum ada data kehadiran rombel pada minggu ini.' }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Keterangan Garis Tren & Total (selalu satu baris, tidak bertumpuk) -->
                <div class="mt-3 pt-2.5 border-top border-light-subtle d-flex flex-nowrap align-items-center justify-content-between gap-2 text-muted" style="font-size: 0.72rem;">
                    <div class="d-flex align-items-center gap-2 min-w-0 text-truncate">
                        <span class="d-inline-block rounded-circle flex-shrink-0" style="width: 10px; height: 10px; background: #2563eb;"></span>
                        <span class="text-truncate"><strong class="text-dark">Garis Tren Kehadiran</strong> <span class="d-none d-sm-inline">({{ $chartScopeText ?? (($classesList->first()->name ?? '7A') . ' s/d ' . ($classesList->last()->name ?? '9C')) }})</span></span>
                    </div>
                    <span class="fw-semibold text-dark flex-shrink-0 text-nowrap">{{ $chartTotalText ?? ('Total: ' . count($classesAttendance ?? []) . ' Rombel') }}</span>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN (30%): CARD KETIDAKHADIRAN HARI INI (INTERAKTIF HOVER) -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 h-100 d-flex flex-column justify-content-between dashboard-panel">
                <div>
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom border-light-subtle mb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-0.5" style="font-size: 0.95rem;">Ketidakhadiran Hari Ini</h6>
                            <p class="text-secondary small mb-0" style="font-size: 0.74rem;">Siswa yang berhalangan hadir</p>
                        </div>
                        <span class="fw-bold text-dark" style="font-size: 0.76rem;">
                            {{ request('status') === 'Sakit' ? ($countSakit ?? 0) : (request('status') === 'Izin' ? ($countIzin ?? 0) : (request('status') === 'Alfa' ? ($countAlfa ?? 0) : ($totalKetidakhadiran ?? 0))) }} Siswa
                        </span>
                    </div>

                    <!-- 3 Baris Detail Ketidakhadiran Hari Ini (Interaktif dengan Hover Background) -->
                    <div class="d-flex flex-column gap-2.5">
                        
                        <!-- 1. Sakit -->
                        <a href="{{ route(panel_role() === 'guru' ? 'guru.dashboard' : 'admin.dashboard', array_filter(['tanggal' => ($isToday ?? true) ? null : ($dateString ?? null), 'mode' => $chartMode ?? 'mingguan', 'status' => request('status') === 'Sakit' ? null : 'Sakit'])) }}" 
                           class="absence-row-interactive text-decoration-none d-flex align-items-center justify-content-between {{ request('status') === 'Sakit' ? 'absence-row-active' : '' }}"
                           title="Klik untuk menyaring siswa sakit hari ini; klik lagi untuk melepas">
                            <div class="d-flex align-items-center gap-3">
                                {{-- Ikon biru: plester (Boxicons yang sudah dipakai project) --}}
                                <span class="absence-status-icon" style="--absence-icon-color: #2563eb; --absence-icon-bg: #dbeafe;" aria-hidden="true">
                                    <i class='bx bxs-band-aid'></i>
                                </span>
                                <div>
                                    <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.9rem;">Sakit @if(request('status') === 'Sakit')<span class="absence-row-active-badge">Aktif</span>@endif</span>
                                    <span class="text-secondary small d-block" style="font-size: 0.73rem;">Surat keterangan dokter</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="fs-4 fw-bold text-primary d-block font-monospace leading-none">{{ $countSakit ?? 0 }}</span>
                                <span class="text-secondary small fw-medium" style="font-size: 0.72rem;">Siswa</span>
                            </div>
                        </a>

                        <!-- 2. Izin -->
                        <a href="{{ route(panel_role() === 'guru' ? 'guru.dashboard' : 'admin.dashboard', array_filter(['tanggal' => ($isToday ?? true) ? null : ($dateString ?? null), 'mode' => $chartMode ?? 'mingguan', 'status' => request('status') === 'Izin' ? null : 'Izin'])) }}" 
                           class="absence-row-interactive text-decoration-none d-flex align-items-center justify-content-between {{ request('status') === 'Izin' ? 'absence-row-active' : '' }}"
                           title="Klik untuk menyaring siswa izin hari ini; klik lagi untuk melepas">
                            <div class="d-flex align-items-center gap-3">
                                {{-- Ikon ungu: amplop/surat izin. Ikon lama (bx bx-envelope
                                     tanpa kotak) diganti supaya ukurannya sama dengan
                                     ikon Sakit & Alpha. --}}
                                <span class="absence-status-icon" style="--absence-icon-color: #9333ea; --absence-icon-bg: #f3e8ff;" aria-hidden="true">
                                    <i class='bx bxs-envelope'></i>
                                </span>
                                <div>
                                    <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.9rem;">Izin @if(request('status') === 'Izin')<span class="absence-row-active-badge">Aktif</span>@endif</span>
                                    <span class="text-secondary small d-block" style="font-size: 0.73rem;">Pemberitahuan orang tua</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="fs-4 fw-bold d-block font-monospace leading-none" style="color: #9333ea;">{{ $countIzin ?? 0 }}</span>
                                <span class="text-secondary small fw-medium" style="font-size: 0.72rem;">Siswa</span>
                            </div>
                        </a>

                        <!-- 3. Alpha -->
                        <a href="{{ route(panel_role() === 'guru' ? 'guru.dashboard' : 'admin.dashboard', array_filter(['tanggal' => ($isToday ?? true) ? null : ($dateString ?? null), 'mode' => $chartMode ?? 'mingguan', 'status' => request('status') === 'Alfa' ? null : 'Alfa'])) }}" 
                           class="absence-row-interactive text-decoration-none d-flex align-items-center justify-content-between {{ request('status') === 'Alfa' ? 'absence-row-active' : '' }}"
                           title="Klik untuk menyaring siswa alpha hari ini; klik lagi untuk melepas">
                            <div class="d-flex align-items-center gap-3">
                                {{-- Ikon merah: user-x (siswa tidak hadir) --}}
                                <span class="absence-status-icon" style="--absence-icon-color: #dc2626; --absence-icon-bg: #fee2e2;" aria-hidden="true">
                                    <i class='bx bxs-user-x'></i>
                                </span>
                                <div>
                                    <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.9rem;">Alpha @if(request('status') === 'Alfa')<span class="absence-row-active-badge">Aktif</span>@endif</span>
                                    <span class="text-secondary small d-block" style="font-size: 0.73rem;">Tanpa keterangan sah</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="fs-4 fw-bold text-danger d-block font-monospace leading-none">{{ $countAlfa ?? 0 }}</span>
                                <span class="text-secondary small fw-medium" style="font-size: 0.72rem;">Siswa</span>
                            </div>
                        </a>

                    </div>
                </div>

                <!-- Tombol Menuju Tabel Presensi Harian -->
                <div class="pt-3 mt-3 border-top border-light-subtle">
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
    <!--     Lebar penuh karena Pintasan Cepat sudah dipindah ke atas, sehingga   -->
    <!--     tidak ada lagi kolom kosong di sebelah kanan. Isi & gaya kartu tetap. -->
    <!-- ========================================================================= -->
    <div class="row g-3">
        
        <!-- CARD JADWAL OPERASIONAL SEKOLAH -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3 p-md-4 h-100 d-flex flex-column justify-content-between dashboard-panel">
                <div>
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom border-light-subtle mb-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <i class='bx bx-time-five text-primary fs-4'></i>
                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">Jadwal Operasional Sekolah</span>
                        </div>
                        <span class="fw-bold text-dark" style="font-size: 0.72rem;">
                            Aktif
                        </span>
                    </div>

                    <div class="row g-2.5">
                        <div class="col-6">
                            @if(is_admin())
                            <a href="{{ route('admin.pengaturan.jadwal') }}" class="card border-0 shadow-sm rounded-3 bg-light p-3 text-center d-block text-decoration-none h-100 operasional-card-interactive" title="Ubah jam masuk">
                            @else
                            <div class="card border-0 shadow-sm rounded-3 bg-light p-3 text-center d-block text-decoration-none h-100 operasional-card-interactive" title="Jam masuk sekolah">
                            @endif
                                <span class="text-uppercase fw-semibold text-secondary d-block" style="font-size: 0.7rem; letter-spacing: 0.04em;">Jam Masuk</span>
                                <strong class="text-dark mt-1.5 d-block font-monospace" style="font-size: 1.3rem; font-weight: 700;">
                                    {{ $checkInTime ?? '06:45' }}
                                </strong>
                                <span class="badge bg-white text-secondary rounded-2 px-2 py-0.5 shadow-2xs mt-1" style="font-size: 0.68rem;">WIB</span>
                            @if(is_admin())
                            </a>
                            @else
                            </div>
                            @endif
                        </div>
                        <div class="col-6">
                            @if(is_admin())
                            <a href="{{ route('admin.pengaturan.jadwal') }}" class="card border-0 shadow-sm rounded-3 bg-light p-3 text-center d-block text-decoration-none h-100 operasional-card-interactive" title="Ubah batas terlambat">
                            @else
                            <div class="card border-0 shadow-sm rounded-3 bg-light p-3 text-center d-block text-decoration-none h-100 operasional-card-interactive" title="Batas keterlambatan sekolah">
                            @endif
                                <span class="text-uppercase fw-semibold text-secondary d-block" style="font-size: 0.7rem; letter-spacing: 0.04em;">Batas Terlambat</span>
                                <strong class="text-amber-600 mt-1.5 d-block font-monospace" style="font-size: 1.3rem; font-weight: 700;">
                                    {{ $lateLimitTime ?? '07:15' }}
                                </strong>
                                <span class="badge bg-white text-secondary rounded-2 px-2 py-0.5 shadow-2xs mt-1" style="font-size: 0.68rem;">WIB</span>
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
                   class="btn btn-primary rounded-3 shadow-sm w-100 mt-3 py-2.5 px-3 fw-semibold text-white text-decoration-none d-inline-flex align-items-center justify-content-center gap-2 btn-modern-smooth"
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
