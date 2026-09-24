@extends('layouts.app')

@section('title', 'Dashboard Kesiswaan')
@section('page_title', 'Monitoring Presensi Kesiswaan')
@section('page_subtitle', 'Pemantauan real-time tingkat kehadiran, keterlambatan, dan rekapitulasi rombel seluruh sekolah.')

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

    <!-- ========================================================================= -->
    <!-- 1. BARIS ATAS: 3 KARTU STATISTIK UTAMA (HOVER SCALE UP & SOFT SHADOW)    -->
    <!-- ========================================================================= -->
    <div class="row g-3 mb-4">
        
        <!-- Card 1: Total Siswa Aktif -->
        <div class="col-12 col-md-4">
            <a href="{{ route('kesiswaan.students.index') }}" class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-row align-items-center justify-content-between text-decoration-none stat-card-modern" title="Buka data siswa aktif">
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
        <div class="col-12 col-md-4">
            <a href="{{ route('kesiswaan.classes.index') }}" class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-row align-items-center justify-content-between text-decoration-none stat-card-modern" title="Buka data rombongan belajar">
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

        <!-- Card 3: Guru & Wali Kelas Aktif -->
        <div class="col-12 col-md-4">
            <a href="{{ route('kesiswaan.teachers.index') }}" class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-row align-items-center justify-content-between text-decoration-none stat-card-modern" title="Buka data guru dan wali kelas">
                <div>
                    <span class="text-secondary fw-semibold text-uppercase d-block mb-1.5" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                        Guru &amp; Wali Kelas Aktif
                    </span>
                    <h3 class="fw-bolder mb-1 text-dark" style="font-size: 2rem; letter-spacing: -0.02em;">
                        {{ number_format($totalTeachers ?? 0, 0, ',', '.') }}
                    </h3>
                    <span class="text-secondary small d-block" style="font-size: 0.78rem;">
                        Tenaga pendidik terverifikasi
                    </span>
                </div>
                <i class='bx bxs-id-card text-success' style="font-size: 2.5rem;"></i>
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
                        <h6 class="fw-bold text-dark mb-0.5" style="font-size: 0.98rem;">Monitoring &amp; Analitik Presensi</h6>
                        <p class="text-secondary small mb-0" style="font-size: 0.76rem;">
                            Periode Berjalan: {{ \Carbon\Carbon::parse($startOfWeek)->translatedFormat('d M') }} - {{ \Carbon\Carbon::parse($endOfWeek)->translatedFormat('d M Y') }}
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2.5">
                    <a href="{{ route('kesiswaan.kehadiran', ['tanggal' => $dateString]) }}" class="btn btn-primary rounded-3 shadow-sm px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-2 btn-modern-smooth" style="font-size: 0.82rem;">
                        <i class='bx bx-calendar-check'></i>
                        <span>Presensi Hari Ini</span>
                    </a>
                    <a href="{{ route('kesiswaan.rekap.index') }}" class="btn btn-primary rounded-3 shadow-sm px-4 py-2 fw-semibold text-white d-inline-flex align-items-center gap-2 btn-modern-smooth" style="font-size: 0.82rem;">
                        <i class='bx bx-bar-chart-alt-2'></i>
                        <span>Rekap Lengkap</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. SECTION TENGAH: GRAFIK GARIS (70%) & KETIDAKHADIRAN (30%)             -->
    <!-- ========================================================================= -->
    <div class="row g-3 mb-4">
        
        <!-- KOLOM KIRI (70%): CARD GRAFIK GARIS KEHADIRAN MINGGUAN -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom border-light-subtle mb-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <i class='bx bx-line-chart text-primary fs-4'></i>
                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">Grafik Garis Kehadiran Mingguan Per Rombel</span>
                        </div>
                        <span class="badge bg-light text-secondary rounded-pill px-3 py-1.5 fw-semibold" style="font-size: 0.74rem;">
                            Minggu Ini: {{ \Carbon\Carbon::parse($startOfWeek)->translatedFormat('d M') }} - {{ \Carbon\Carbon::parse($endOfWeek)->translatedFormat('d M Y') }}
                        </span>
                    </div>

                    <!-- Canvas Line Chart Chart.js -->
                    <div class="pt-1 pb-1">
                        @if(count($classesAttendance ?? []) > 0)
                            <div style="position: relative; height: 240px; width: 100%;">
                                <canvas id="attendanceLineChart"></canvas>
                            </div>
                        @else
                            <div class="w-100 py-12 text-center text-muted small">
                                <i class='bx bx-line-chart fs-1 text-secondary opacity-50 d-block mb-1'></i>
                                Belum ada data kehadiran rombel pada minggu ini.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Keterangan Garis Tren & Total Rombel -->
                <div class="mt-3 pt-2.5 border-top border-light-subtle d-flex flex-wrap align-items-center justify-content-between text-muted" style="font-size: 0.75rem;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: #2563eb;"></span>
                        <strong class="text-dark">Garis Tren Persentase Kehadiran</strong> (Rombel {{ $classesList->first()->name ?? '7A' }} s/d {{ $classesList->last()->name ?? '9C' }})
                    </div>
                    <span class="fw-semibold text-dark">Total: {{ count($classesAttendance ?? []) }} Rombel</span>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN (30%): CARD KETIDAKHADIRAN HARI INI (INTERAKTIF HOVER) -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom border-light-subtle mb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-0.5" style="font-size: 0.95rem;">Ketidakhadiran Hari Ini</h6>
                            <p class="text-secondary small mb-0" style="font-size: 0.74rem;">Siswa yang berhalangan hadir</p>
                        </div>
                        <span class="badge {{ ($totalKetidakhadiran ?? 0) > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} px-3 py-1.5 fw-bold rounded-pill" style="font-size: 0.76rem;">
                            {{ $totalKetidakhadiran ?? 0 }} Siswa
                        </span>
                    </div>

                    <!-- 3 Baris Detail Ketidakhadiran Hari Ini (Interaktif dengan Hover Background) -->
                    <div class="d-flex flex-column gap-2.5">
                        
                        <!-- 1. Sakit -->
                        <a href="{{ route('kesiswaan.kehadiran', ['tanggal' => $dateString, 'status' => 'Sakit']) }}" 
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
                        <a href="{{ route('kesiswaan.kehadiran', ['tanggal' => $dateString, 'status' => 'Izin']) }}" 
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
                        <a href="{{ route('kesiswaan.kehadiran', ['tanggal' => $dateString, 'status' => 'Alfa']) }}" 
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

                <!-- Tombol Menuju Tabel Presensi Harian -->
                <div class="pt-3 mt-3 border-top border-light-subtle">
                    <a href="{{ route('kesiswaan.kehadiran', ['tanggal' => $dateString]) }}" 
                       class="btn btn-primary rounded-3 shadow-sm w-100 fw-bold py-2.5 d-inline-flex align-items-center justify-content-center gap-2 btn-modern-smooth" 
                       style="font-size: 0.85rem;">
                        <i class='bx bx-list-check fs-5'></i>
                        <span>Buka Tabel Presensi Hari Ini</span>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 4. SECTION BAWAH: JADWAL OPERASIONAL & PINTASAN CEPAT                     -->
    <!-- ========================================================================= -->
    <div class="row g-3">
        
        <!-- CARD JADWAL OPERASIONAL SEKOLAH (MONITORING ONLY) -->
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

                <!-- Tombol Aksi Kesiswaan: Menuju Monitoring Harian -->
                <a href="{{ route('kesiswaan.kehadiran', ['tanggal' => $dateString]) }}" 
                   class="btn btn-primary rounded-3 shadow-sm w-100 mt-3 py-2.5 px-3 fw-semibold text-white text-decoration-none d-inline-flex align-items-center justify-content-center gap-2 btn-modern-smooth"
                   style="font-size: 0.85rem;">
                    <i class='bx bx-calendar-check'></i>
                    <span>Pantau Presensi Hari Ini</span>
                </a>
            </div>
        </div>

        <!-- CARD PINTASAN CEPAT (6 MODUL KESISWAAN) -->
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

                    <!-- Grid 6 Pintasan Cepat Kesiswaan (Pure Icon) -->
                    <div class="row g-3">
                        
                        <!-- 1. Presensi Hari Ini -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('kesiswaan.kehadiran', ['tanggal' => $dateString]) }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Monitoring Presensi Harian Siswa">
                                <i class='bx bx-calendar-check text-primary fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Presensi Hari Ini</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Monitoring Harian</span>
                            </a>
                        </div>

                        <!-- 2. Catatan Kehadiran -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('kesiswaan.kehadiran') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Lihat Log Kehadiran & Presensi Seluruh Siswa">
                                <i class='bx bx-list-check text-success fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Catatan Kehadiran</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Log Kehadiran Siswa</span>
                            </a>
                        </div>

                        <!-- 3. Rekapitulasi Presensi -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('kesiswaan.rekap.index') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Laporan Rekapitulasi & Ekspor Dokumen">
                                <i class='bx bxs-file-pdf text-danger fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Rekap Presensi</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Laporan &amp; Ekspor</span>
                            </a>
                        </div>

                        <!-- 4. Data Siswa -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('kesiswaan.students.index') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Lihat Data Siswa Seluruh Kelas">
                                <i class='bx bx-user text-info fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Data Siswa</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Daftar Siswa Sekolah</span>
                            </a>
                        </div>

                        <!-- 5. Data Guru -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('kesiswaan.teachers.index') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Lihat Data Guru & Wali Kelas">
                                <i class='bx bx-user-pin text-purple fs-2 mb-2' style="color: #9333ea;"></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Data Guru</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Daftar Tenaga Pendidik</span>
                            </a>
                        </div>

                        <!-- 6. Data Kelas -->
                        <div class="col-6 col-md-4">
                            <a href="{{ route('kesiswaan.classes.index') }}" 
                               class="card border-0 shadow-sm rounded-4 bg-light p-3 text-center d-flex flex-column align-items-center justify-content-center text-decoration-none h-100 shortcut-card-interactive"
                               title="Lihat Data Rombongan Belajar">
                                <i class='bx bx-buildings text-warning fs-2 mb-2'></i>
                                <span class="fw-bold text-dark d-block leading-tight" style="font-size: 0.83rem;">Data Kelas</span>
                                <span class="text-secondary small d-block mt-0.5 text-truncate w-100" style="font-size: 0.7rem;">Rombongan Belajar</span>
                            </a>
                        </div>

                    </div>
                </div>

                <div class="mt-3 text-center">
                    <span class="text-secondary fw-medium" style="font-size: 0.74rem;">Semua tautan terhubung langsung ke modul kesiswaan</span>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<!-- Library Chart.js Resmi -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('attendanceLineChart');
        if (!ctx) return;

        const chartLabels = {!! json_encode($classesAttendance->pluck('name')) !!};
        const chartData = {!! json_encode($classesAttendance->pluck('percentage')) !!};

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
