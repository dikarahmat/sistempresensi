@extends('layouts.app')

@section('title', 'Dashboard Wali Kelas')
@section('page_title', 'Dashboard Wali Kelas')
@section('page_subtitle', 'Sistem Presensi Siswa Terintegrasi')

@section('page_header_right')
<!-- Tanggal di Sisi Kanan Modern -->
<div class="d-none d-lg-flex align-items-center gap-2.5 text-end">
    <div>
        <span class="text-secondary fw-semibold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.05em;">Tanggal Hari Ini</span>
        <strong class="text-dark fw-bold d-block" style="font-size: 0.88rem;">
            {{ $todayFormatted ?? \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}
        </strong>
    </div>
    <i class='bx bx-calendar text-primary fs-3'></i>
</div>
@endsection

@push('styles')
<style>
    /* Ubah background utama halaman menjadi abu-abu terang agar kartu putih terlihat menonjol */
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
    .absence-row-interactive:hover i {
        transform: scale(1.15);
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
</style>
@endpush

@section('content')

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-4 small mb-4 py-2.5 px-3.5 shadow-xs" style="background: rgba(220, 252, 231, 0.95);" role="alert">
        <div class="d-flex align-items-center fw-semibold text-success-emphasis">
            <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-4 small mb-4 py-2.5 px-3.5 shadow-xs" style="background: rgba(254, 226, 226, 0.95);" role="alert">
        <div class="d-flex align-items-center text-danger-emphasis">
            <i class='bx bx-error-circle fs-5 me-2 text-danger'></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($isHoliday)
    <div class="alert alert-warning border-0 rounded-4 p-3.5 mb-4 d-flex align-items-center gap-3 shadow-xs" style="background-color: #fffbeb;">
        <i class='bx bx-calendar-event fs-2 text-warning'></i>
        <div>
            <div class="fw-bold text-warning-emphasis">Pemberitahuan Hari Libur</div>
            <div class="small text-secondary">Hari ini adalah <strong>{{ $holidayDescription }}</strong>. Sistem otomatis tidak menerapkan alpha harian.</div>
        </div>
    </div>
    @endif

    <!-- Header Tambahan: Sambutan Guru / Wali Kelas -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3.5 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <i class='bx bxs-user-badge text-primary fs-2'></i>
                <div>
                    <h5 class="fw-bold text-dark mb-0.5" style="font-size: 1.05rem;">
                        Selamat Datang, {{ $teacher->name ?? Auth::user()->name }} - Wali Kelas {{ $schoolClass->name ?? 'Belum Ditentukan' }}
                    </h5>
                    <p class="text-secondary small mb-0" style="font-size: 0.78rem;">
                        Tahun Ajaran: <strong class="text-dark">{{ $activeYear->name ?? '-' }} ({{ $activeYear->semester ?? '-' }})</strong>
                        &bull; Kelola kehadiran dan pantau kedisiplinan siswa binaan kelas Anda secara real-time.
                    </p>
                </div>
            </div>
            @if(isset($teacherClasses) && $teacherClasses->count() > 1)
            <form action="{{ route('guru.dashboard') }}" method="GET" class="d-flex align-items-center gap-2">
                <span class="text-secondary small fw-semibold text-nowrap" style="font-size: 0.75rem;">Pilih Kelas:</span>
                <select name="school_class_id" class="form-select form-select-sm rounded-3 bg-light fw-bold text-primary border-0" onchange="this.form.submit()">
                    @foreach($teacherClasses as $tc)
                        <option value="{{ $tc->id }}" {{ $schoolClass && $schoolClass->id == $tc->id ? 'selected' : '' }}>
                            Kelas {{ $tc->name }}
                        </option>
                    @endforeach
                </select>
            </form>
            @endif
        </div>
    </div>

    @if(!$schoolClass)
    <div class="card border-0 shadow-sm rounded-4 bg-white p-5 text-center my-4">
        <div class="d-inline-block text-warning mb-3">
            <i class='bx bx-info-circle' style="font-size: 3rem;"></i>
        </div>
        <h5 class="fw-bold mb-1 text-dark">Belum Ditetapkan Sebagai Wali Kelas</h5>
        <p class="text-secondary small max-w-md mx-auto mb-0">
            Akun Anda belum terhubung dengan kelas aktif pada tahun ajaran ini. Silakan hubungi Administrator sekolah untuk menetapkan kelas binaan Anda.
        </p>
    </div>
    @else

    <!-- ========================================================================= -->
    <!-- 1. BARIS ATAS: 3 KARTU STATISTIK UTAMA KELAS (HOVER SCALE UP & SOFT SHADOW) -->
    <!-- ========================================================================= -->
    <div class="row g-3 mb-4">
        
        <!-- Card 1: Total Siswa di Kelasnya -->
        <div class="col-12 col-md-4">
            <a href="{{ route('guru.students') }}" class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-row align-items-center justify-content-between text-decoration-none stat-card-modern" title="Buka data siswa kelas binaan">
                <div>
                    <span class="text-secondary fw-semibold text-uppercase d-block mb-1.5" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                        Total Siswa Kelas
                    </span>
                    <h3 class="fw-bolder mb-1 text-dark" style="font-size: 2rem; letter-spacing: -0.02em;">
                        {{ number_format($totalStudents ?? 0, 0, ',', '.') }}
                    </h3>
                    <span class="text-secondary small d-block" style="font-size: 0.78rem;">
                        Siswa terdaftar di Kelas {{ $schoolClass->name }}
                    </span>
                </div>
                <i class='bx bxs-user-pin text-primary' style="font-size: 2.5rem;"></i>
            </a>
        </div>

        <!-- Card 2: Siswa Hadir Hari Ini -->
        <div class="col-12 col-md-4">
            <a href="{{ route('guru.kehadiran') }}" class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-row align-items-center justify-content-between text-decoration-none stat-card-modern" title="Buka presensi kelas hari ini">
                <div>
                    <span class="text-secondary fw-semibold text-uppercase d-block mb-1.5" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                        Siswa Hadir Hari Ini
                    </span>
                    <h3 class="fw-bolder mb-1 text-dark" style="font-size: 2rem; letter-spacing: -0.02em;">
                        {{ number_format($countHadir ?? 0, 0, ',', '.') }}
                    </h3>
                    <span class="text-success small d-block fw-semibold" style="font-size: 0.78rem;">
                        {{ $percentageHadir }}% kehadiran terdata
                    </span>
                </div>
                <i class='bx bx-user-check text-success' style="font-size: 2.5rem;"></i>
            </a>
        </div>

        <!-- Card 3: Siswa Belum Hadir -->
        <div class="col-12 col-md-4">
            <a href="{{ route('guru.kehadiran') }}" class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-row align-items-center justify-content-between text-decoration-none stat-card-modern" title="Buka data siswa yang belum hadir">
                <div>
                    <span class="text-secondary fw-semibold text-uppercase d-block mb-1.5" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                        Siswa Belum Hadir
                    </span>
                    <h3 class="fw-bolder mb-1 text-dark" style="font-size: 2rem; letter-spacing: -0.02em;">
                        {{ number_format($countBelumHadir ?? 0, 0, ',', '.') }}
                    </h3>
                    <span class="text-secondary small d-block" style="font-size: 0.78rem;">
                        Belum presensi masuk hari ini
                    </span>
                </div>
                <i class='bx bx-time-five text-warning' style="font-size: 2.5rem;"></i>
            </a>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 2. AREA FILTER & KONTROL MONITORING (CARD UTAMA SHADOW-SM ROUNDED-4)     -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3.5">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <i class='bx bx-filter-alt text-primary fs-3'></i>
                    <div>
                        <h6 class="fw-bold text-dark mb-0.5" style="font-size: 0.98rem;">Monitoring Presensi Kelas {{ $schoolClass->name }}</h6>
                        <p class="text-secondary small mb-0" style="font-size: 0.76rem;">
                            Periode Berjalan: {{ \Carbon\Carbon::parse($startOfWeek)->translatedFormat('d M') }} - {{ \Carbon\Carbon::parse($endOfWeek)->translatedFormat('d M Y') }}
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2.5">
                    <a href="{{ route('guru.absensi.index') }}" class="btn btn-primary rounded-3 shadow-sm px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2 btn-modern-smooth" style="font-size: 0.82rem;">
                        <i class='bx bx-calendar-check'></i>
                        <span>Presensi Hari Ini</span>
                    </a>
                    <a href="{{ route('guru.rekap') }}" class="btn btn-primary rounded-3 shadow-sm px-4 py-2 fw-semibold text-white d-inline-flex align-items-center gap-2 btn-modern-smooth" style="font-size: 0.82rem;">
                        <i class='bx bx-bar-chart-alt-2'></i>
                        <span>Rekap Kelas</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. SECTION TENGAH: GRAFIK GARIS (70%) & KETIDAKHADIRAN (30%)             -->
    <!-- ========================================================================= -->
    <div class="row g-3 mb-4">
        
        <!-- KOLOM KIRI (70%): CARD GRAFIK GARIS KEHADIRAN MINGGUAN KELAS BINAAN -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom border-light-subtle mb-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <i class='bx bx-line-chart text-primary fs-4'></i>
                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">Grafik Tren Kehadiran Mingguan (Kelas {{ $schoolClass->name }})</span>
                        </div>
                        <span class="badge bg-light text-secondary rounded-pill px-3 py-1.5 fw-semibold" style="font-size: 0.74rem;">
                            Minggu Ini: {{ \Carbon\Carbon::parse($startOfWeek)->translatedFormat('d M') }} - {{ \Carbon\Carbon::parse($endOfWeek)->translatedFormat('d M Y') }}
                        </span>
                    </div>

                    <!-- Canvas Line Chart Chart.js -->
                    <div class="pt-1 pb-1">
                        @if(count($chartLabels ?? []) > 0)
                            <div style="position: relative; height: 240px; width: 100%;">
                                <canvas id="attendanceLineChart"></canvas>
                            </div>
                        @else
                            <div class="w-100 py-12 text-center text-muted small">
                                <i class='bx bx-line-chart fs-1 text-secondary opacity-50 d-block mb-1'></i>
                                Belum ada data kehadiran pada minggu ini.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Keterangan Garis Tren & Total Siswa -->
                <div class="mt-3 pt-2.5 border-top border-light-subtle d-flex flex-wrap align-items-center justify-content-between text-muted" style="font-size: 0.75rem;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: #2563eb;"></span>
                        <strong class="text-dark">Garis Tren Kehadiran Harian</strong> (Kelas {{ $schoolClass->name }})
                    </div>
                    <span class="fw-semibold text-dark">Total Siswa: {{ $totalStudents ?? 0 }} Siswa</span>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN (30%): CARD KETIDAKHADIRAN KELAS HARI INI (INTERAKTIF HOVER) -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom border-light-subtle mb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-0.5" style="font-size: 0.95rem;">Ketidakhadiran Kelas Hari Ini</h6>
                            <p class="text-secondary small mb-0" style="font-size: 0.74rem;">Siswa Kelas {{ $schoolClass->name }} berhalangan hadir</p>
                        </div>
                        <span class="badge {{ ($totalKetidakhadiran ?? 0) > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} px-3 py-1.5 fw-bold rounded-pill" style="font-size: 0.76rem;">
                            {{ $totalKetidakhadiran ?? 0 }} Siswa
                        </span>
                    </div>

                    <!-- 3 Baris Detail Ketidakhadiran Hari Ini (Interaktif dengan Hover Background) -->
                    <div class="d-flex flex-column gap-2.5">
                        
                        <!-- 1. Sakit -->
                        <a href="{{ route('guru.kehadiran', ['status' => 'Sakit']) }}" 
                           class="absence-row-interactive text-decoration-none d-flex align-items-center justify-content-between"
                           title="Lihat detail siswa sakit hari ini">
                            <div class="d-flex align-items-center gap-3">
                                <i class='bx bx-plus-medical text-primary fs-3' style="width: 26px; text-align: center;"></i>
                                <div>
                                    <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.9rem;">Sakit</span>
                                    <span class="text-secondary small" style="font-size: 0.73rem;">Surat keterangan dokter</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="fs-4 fw-bold text-primary d-block font-monospace leading-none">{{ $countSakit ?? 0 }}</span>
                                <span class="text-secondary small fw-medium" style="font-size: 0.72rem;">Siswa</span>
                            </div>
                        </a>

                        <!-- 2. Izin -->
                        <a href="{{ route('guru.kehadiran', ['status' => 'Izin']) }}" 
                           class="absence-row-interactive text-decoration-none d-flex align-items-center justify-content-between"
                           title="Lihat detail siswa izin hari ini">
                            <div class="d-flex align-items-center gap-3">
                                <i class='bx bx-envelope text-purple fs-3' style="color: #9333ea; width: 26px; text-align: center;"></i>
                                <div>
                                    <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.9rem;">Izin</span>
                                    <span class="text-secondary small" style="font-size: 0.73rem;">Pemberitahuan orang tua</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="fs-4 fw-bold d-block font-monospace leading-none" style="color: #9333ea;">{{ $countIzin ?? 0 }}</span>
                                <span class="text-secondary small fw-medium" style="font-size: 0.72rem;">Siswa</span>
                            </div>
                        </a>

                        <!-- 3. Alpha -->
                        <a href="{{ route('guru.kehadiran', ['status' => 'Alfa']) }}" 
                           class="absence-row-interactive text-decoration-none d-flex align-items-center justify-content-between"
                           title="Lihat detail siswa alpha hari ini">
                            <div class="d-flex align-items-center gap-3">
                                <i class='bx bx-x-circle text-danger fs-3' style="width: 26px; text-align: center;"></i>
                                <div>
                                    <span class="fw-bold text-dark d-block mb-0" style="font-size: 0.9rem;">Alpha</span>
                                    <span class="text-secondary small" style="font-size: 0.73rem;">Tanpa keterangan sah</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="fs-4 fw-bold text-danger d-block font-monospace leading-none">{{ $countAlfa ?? 0 }}</span>
                                <span class="text-secondary small fw-medium" style="font-size: 0.72rem;">Siswa</span>
                            </div>
                        </a>

                    </div>
                </div>

                <!-- Tombol Menuju Tabel Presensi Harian Kelas -->
                <div class="pt-3 mt-3 border-top border-light-subtle">
                    <a href="{{ route('guru.kehadiran') }}" 
                       class="btn btn-primary rounded-3 shadow-sm w-100 fw-bold py-2.5 d-inline-flex align-items-center justify-content-center gap-2 btn-modern-smooth" 
                       style="font-size: 0.85rem;">
                        <i class='bx bx-list-check fs-5'></i>
                        <span>Buka Tabel Presensi Kelas</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 4. SECTION BAWAH: JADWAL OPERASIONAL & PINTASAN CEPAT                     -->
    <!-- ========================================================================= -->
    <div class="row g-3">
        
        <!-- CARD JADWAL OPERASIONAL SEKOLAH (INFO KHUSUS GURU) -->
        <div class="col-12 col-xl-4 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom border-light-subtle mb-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <i class='bx bx-time-five text-primary fs-4'></i>
                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">Jadwal Operasional Sekolah</span>
                        </div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.72rem;">
                            Aktif
                        </span>
                    </div>

                    <div class="row g-2.5">
                        <div class="col-6">
                            <div class="card border-0 shadow-sm rounded-3 bg-light p-3 text-center d-block h-100 operasional-card-interactive">
                                <span class="text-uppercase fw-semibold text-secondary d-block" style="font-size: 0.7rem; letter-spacing: 0.04em;">Jam Masuk</span>
                                <strong class="text-dark mt-1.5 d-block font-monospace" style="font-size: 1.3rem; font-weight: 700;">
                                    {{ $checkInTime ?? '06:45' }}
                                </strong>
                                <span class="badge bg-white text-secondary rounded-2 px-2 py-0.5 shadow-2xs mt-1" style="font-size: 0.68rem;">WIB</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card border-0 shadow-sm rounded-3 bg-light p-3 text-center d-block h-100 operasional-card-interactive">
                                <span class="text-uppercase fw-semibold text-secondary d-block" style="font-size: 0.7rem; letter-spacing: 0.04em;">Batas Terlambat</span>
                                <strong class="text-amber-600 mt-1.5 d-block font-monospace" style="font-size: 1.3rem; font-weight: 700;">
                                    {{ $lateLimitTime ?? '07:15' }}
                                </strong>
                                <span class="badge bg-white text-secondary rounded-2 px-2 py-0.5 shadow-2xs mt-1" style="font-size: 0.68rem;">WIB</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Guru: Lihat Presensi Hari Ini -->
                <a href="{{ route('guru.absensi.index') }}" 
                   class="btn btn-primary rounded-3 shadow-sm w-100 mt-3 py-2.5 px-3 fw-semibold text-white text-decoration-none d-inline-flex align-items-center justify-content-center gap-2 btn-modern-smooth"
                   style="font-size: 0.85rem;">
                    <i class='bx bx-calendar-check'></i>
                    <span>Lihat Presensi Hari Ini</span>
                </a>
            </div>
        </div>

        <!-- CARD PINTASAN CEPAT (6 MODUL KELAS GURU) -->
        <div class="col-12 col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
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

                    <!-- Grid 6 Pintasan Cepat Guru (Pure Icon) -->
                    <div class="row g-3">
                        
                        <!-- 1. Presensi Kelas Binaan -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('guru.absensi.index') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Lihat Presensi Hari Ini Kelas Binaan">
                                <i class='bx bx-calendar-check text-primary fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Presensi Kelas</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Presensi Hari Ini</span>
                            </a>
                        </div>

                        <!-- 2. Input Presensi Manual -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('guru.kehadiran') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Catat Kehadiran Secara Manual">
                                <i class='bx bx-edit text-warning fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Input Presensi Manual</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Catat Kehadiran Mandiri</span>
                            </a>
                        </div>

                        <!-- 3. Log Kehadiran Kelas -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('guru.kehadiran') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Lihat Log Kehadiran Siswa Kelas Binaan">
                                <i class='bx bx-list-check text-success fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Log Kehadiran Kelas</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Presensi Siswa Binaan</span>
                            </a>
                        </div>

                        <!-- 4. Data Siswa Kelas -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('guru.students') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Lihat Data Siswa Kelas Binaan">
                                <i class='bx bx-user text-info fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Data Siswa Kelas</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Biodata &amp; Status Siswa</span>
                            </a>
                        </div>

                        <!-- 5. Rekap Presensi Kelas -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('guru.rekap') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Laporan Rekapitulasi Presensi Kelas">
                                <i class='bx bxs-file-pdf text-danger fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Rekap Presensi Kelas</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Laporan &amp; Ekspor</span>
                            </a>
                        </div>

                        <!-- 6. Cetak Kartu Siswa -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('guru.students.print-cards') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Cetak Kartu Presensi QR Siswa Kelas">
                                <i class='bx bx-id-card text-purple fs-2 mb-2' style="color: #9333ea;"></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Cetak Kartu Siswa</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Cetak Kartu QR Kelas</span>
                            </a>
                        </div>

                    </div>
                </div>

                <div class="mt-3 text-center">
                    <span class="text-secondary fw-medium" style="font-size: 0.74rem;">Semua tautan terhubung langsung ke modul kelas binaan</span>
                </div>
            </div>
        </div>

    </div>

    @endif

@endsection

@push('scripts')
<!-- Library Chart.js Resmi -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('attendanceLineChart');
        if (!ctx) return;

        const chartLabels = {!! json_encode($chartLabels ?? []) !!};
        const chartData = {!! json_encode($chartData ?? []) !!};

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
                    spanGaps: true,
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
                                if (context.parsed.y === null || context.parsed.y === undefined) {
                                    return 'Belum ada data';
                                }
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