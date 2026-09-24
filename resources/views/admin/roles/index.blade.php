@extends('layouts.app')

@section('title', 'Manajemen Role & Permission')
@section('page_title', 'Manajemen Role & Permission')
@section('page_subtitle', 'Ringkasan hak akses modul dan otoritas 3 role utama sistem presensi')

@section('page_header_right')
<!-- Widget Tanggal Real-time di Sisi Kanan -->
<div class="d-flex align-items-center gap-3 bg-white px-3.5 py-2.5 rounded-3 border border-slate-200 shadow-2xs">
    <div class="rounded-2 d-flex align-items-center justify-content-center bg-blue-50 text-blue-600" style="width: 40px; height: 40px; font-size: 1.3rem;">
        <i class='bx bx-calendar-check'></i>
    </div>
    <div>
        <span class="text-slate-400 fw-bold d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.06em;">Tanggal Real-time</span>
        <strong class="text-slate-800 fw-bold d-block" style="font-size: 0.88rem;" id="realtimeDateClock">
            {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}
        </strong>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Card Role Ringkas */
    .role-card-clean {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        padding: 1.35rem 1.5rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .role-card-clean:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    }

    .role-card-clean.highlight-admin {
        border-top: 3.5px solid #3b62f6;
    }

    .role-card-clean.highlight-kesiswaan {
        border-top: 3.5px solid #10b981;
    }

    .role-card-clean.highlight-guru {
        border-top: 3.5px solid #8b5cf6;
    }

    .role-icon-box {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    /* List Izin Sederhana */
    .permission-simple-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }

    .permission-simple-list li {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        font-size: 0.82rem;
        color: #334155;
        line-height: 1.45;
        margin-bottom: 0.55rem;
    }

    .permission-simple-list li:last-child {
        margin-bottom: 0;
    }

    .permission-simple-list li i {
        font-size: 1.05rem;
        margin-top: 0.1rem;
        flex-shrink: 0;
    }

    /* Tabel Matriks Otoritas */
    .matrix-table-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
    }

    .matrix-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
    }

    .matrix-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.9rem 1.25rem;
        border-bottom: 1.5px solid #e2e8f0;
    }

    .matrix-table td {
        padding: 0.95rem 1.25rem;
        font-size: 0.84rem;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .matrix-table tr:last-child td {
        border-bottom: none;
    }

    .matrix-table tr:hover td {
        background-color: #fafbfc;
    }

    /* Status Pill Badges */
    .status-badge-clean {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        white-space: nowrap;
    }

    .status-full-access {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .status-operational {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .status-rombel {
        background: #f5f3ff;
        color: #6d28d9;
        border: 1px solid #ddd6fe;
    }

    .status-limited {
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
</style>
@endpush

@section('content')

<!-- ============================================================ -->
<!-- 1. TIGA ROLE UTAMA (CARD LIST MINIMALIS & RINGKAS)          -->
<!-- ============================================================ -->
<div class="row g-3 mb-4">

    <!-- Card 1: Administrator -->
    <div class="col-lg-4">
        <div class="role-card-clean highlight-admin">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="role-icon-box bg-blue-50 text-blue-600">
                        <i class='bx bx-shield-quarter'></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-slate-800" style="font-size: 1.05rem;">Administrator</h5>
                        <span class="text-slate-400" style="font-size: 0.74rem;">{{ $roleCounts['admin'] ?? 1 }} Akun Aktif</span>
                    </div>
                </div>
                <span class="status-badge-clean status-full-access">
                    Full Akses
                </span>
            </div>

            <p class="text-slate-500 mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                Otoritas tertinggi dengan kontrol mutlak atas konfigurasi sistem presensi, database, dan manajemen sekolah.
            </p>

            <div class="border-top border-slate-100 pt-3 flex-grow-1">
                <span class="text-slate-400 fw-bold d-block text-uppercase mb-2" style="font-size: 0.68rem; letter-spacing: 0.05em;">Cakupan Izin Akses:</span>
                <ul class="permission-simple-list">
                    <li>
                        <i class='bx bx-check-circle text-blue-600'></i>
                        <span>Akses penuh seluruh modul aplikasi tanpa batasan</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-blue-600'></i>
                        <span>Pengaturan profil sekolah & batas jam toleransi</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-blue-600'></i>
                        <span>Kelola tahun ajaran, hari libur, dan jadwal akademik</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-blue-600'></i>
                        <span>Master data siswa, guru, kelas, & kenaikan tingkat</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-blue-600'></i>
                        <span>Monitoring presensi, koreksi manual, & ekspor laporan</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Card 2: Bagian Kesiswaan -->
    <div class="col-lg-4">
        <div class="role-card-clean highlight-kesiswaan">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="role-icon-box bg-emerald-50 text-emerald-600">
                        <i class='bx bx-user-check'></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-slate-800" style="font-size: 1.05rem;">Kesiswaan</h5>
                        <span class="text-slate-400" style="font-size: 0.74rem;">{{ $roleCounts['kesiswaan'] ?? 1 }} Akun Aktif</span>
                    </div>
                </div>
                <span class="status-badge-clean status-operational">
                    Operasional & Rekap
                </span>
            </div>

            <p class="text-slate-500 mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                Fokus pada pemantauan kedisiplinan harian, rekapitulasi kehadiran sekolah, dan pelaporan berkala.
            </p>

            <div class="border-top border-slate-100 pt-3 flex-grow-1">
                <span class="text-slate-400 fw-bold d-block text-uppercase mb-2" style="font-size: 0.68rem; letter-spacing: 0.05em;">Cakupan Izin Akses:</span>
                <ul class="permission-simple-list">
                    <li>
                        <i class='bx bx-check-circle text-emerald-600'></i>
                        <span>Dashboard monitoring kehadiran siswa real-time</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-emerald-600'></i>
                        <span>Input dan verifikasi data presensi harian siswa</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-emerald-600'></i>
                        <span>Melihat data pokok siswa, rombel kelas, & wali kelas</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-emerald-600'></i>
                        <span>Rekapitulasi presensi lengkap tingkat sekolah</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-emerald-600'></i>
                        <span>Ekspor laporan format Excel, CSV, dan dokumen PDF</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Card 3: Walikelas / Guru -->
    <div class="col-lg-4">
        <div class="role-card-clean highlight-guru">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="role-icon-box bg-purple-50 text-purple-600">
                        <i class='bx bx-id-card'></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-slate-800" style="font-size: 1.05rem;">Walikelas / Guru</h5>
                        <span class="text-slate-400" style="font-size: 0.74rem;">{{ $roleCounts['guru'] ?? 0 }} Akun Aktif</span>
                    </div>
                </div>
                <span class="status-badge-clean status-rombel">
                    Rombel Binaan
                </span>
            </div>

            <p class="text-slate-500 mb-3" style="font-size: 0.8rem; line-height: 1.45;">
                Pengelolaan kehadiran siswa khusus pada rombongan belajar (kelas) yang dibina masing-masing guru.
            </p>

            <div class="border-top border-slate-100 pt-3 flex-grow-1">
                <span class="text-slate-400 fw-bold d-block text-uppercase mb-2" style="font-size: 0.68rem; letter-spacing: 0.05em;">Cakupan Izin Akses:</span>
                <ul class="permission-simple-list">
                    <li>
                        <i class='bx bx-check-circle text-purple-600'></i>
                        <span>Portal dashboard walikelas sesuai kelas binaan</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-purple-600'></i>
                        <span>Scanner QR presensi kamera/webcam di ruang kelas</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-purple-600'></i>
                        <span>Catat keterangan siswa sakit, izin, dan alpa harian</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-purple-600'></i>
                        <span>Rekap kehadiran & ekspor laporan rombel binaan</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle text-purple-600'></i>
                        <span>Unduh & cetak kartu QR presensi siswa kelas binaan</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

</div>

<!-- ============================================================ -->
<!-- 2. TABEL MATRIKS HAK AKSES MODUL (SEKALI LIHAT PAHAM)       -->
<!-- ============================================================ -->
<div class="matrix-table-card">
    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom border-slate-100 bg-white">
        <div class="d-flex align-items-center gap-2">
            <i class='bx bx-grid-alt text-blue-600 fs-5'></i>
            <h6 class="fw-bold text-slate-800 mb-0" style="font-size: 0.95rem;">Matriks Otoritas Hak Akses Per Modul</h6>
        </div>
        <span class="text-slate-400 fw-semibold" style="font-size: 0.74rem;">Standar Keamanan Sistem Presensi</span>
    </div>

    <div class="table-responsive">
        <table class="matrix-table">
            <thead>
                <tr>
                    <th style="width: 24%;">Modul Sistem</th>
                    <th style="width: 34%;">Cakupan Fungsi Fitur</th>
                    <th style="width: 14%; text-align: center;">Admin</th>
                    <th style="width: 14%; text-align: center;">Kesiswaan</th>
                    <th style="width: 14%; text-align: center;">Walikelas / Guru</th>
                </tr>
            </thead>
            <tbody>
                <!-- Row 1: Dashboard -->
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class='bx bx-home-alt text-blue-600 fs-5'></i>
                            <strong class="text-slate-800">Dashboard</strong>
                        </div>
                    </td>
                    <td class="text-slate-600">
                        Statistik kehadiran, grafik perbandingan, pemantauan real-time, dan kalender hari efektif.
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-full-access">Full Akses</span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-operational">Monitoring</span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-rombel">Rombel Binaan</span>
                    </td>
                </tr>

                <!-- Row 2: Presensi & Kehadiran -->
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class='bx bx-calendar-check text-emerald-600 fs-5'></i>
                            <strong class="text-slate-800">Presensi & Kehadiran</strong>
                        </div>
                    </td>
                    <td class="text-slate-600">
                        Scan QR presensi, mode gerbang (kiosk), input manual sakit/izin/alfa, dan override kehadiran.
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-full-access">Full Akses</span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-operational">Input & Pantau</span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-rombel">Scan & Catat Kelas</span>
                    </td>
                </tr>

                <!-- Row 3: Rekapitulasi Laporan -->
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class='bx bx-folder text-amber-600 fs-5'></i>
                            <strong class="text-slate-800">Rekapitulasi Laporan</strong>
                        </div>
                    </td>
                    <td class="text-slate-600">
                        Laporan harian/bulanan, ekspor lembar kerja Excel (.xlsx), dan cetak dokumen PDF resmi.
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-full-access">Semua Kelas</span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-operational">Semua Kelas</span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-rombel">Kelas Binaan</span>
                    </td>
                </tr>

                <!-- Row 4: Kalender Akademik & Master Data -->
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class='bx bx-data text-purple-600 fs-5'></i>
                            <strong class="text-slate-800">Kalender & Master Data</strong>
                        </div>
                    </td>
                    <td class="text-slate-600">
                        Tahun ajaran, kalender libur, data siswa, mutasi kelas, kenaikan kelas, dan penugasan wali kelas.
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-full-access">Kelola Penuh</span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-operational">Data Siswa & Kelas</span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-rombel">Data Rombel Binaan</span>
                    </td>
                </tr>

                <!-- Row 5: Pengaturan Sistem -->
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class='bx bx-cog text-slate-600 fs-5'></i>
                            <strong class="text-slate-800">Pengaturan Sistem</strong>
                        </div>
                    </td>
                    <td class="text-slate-600">
                        Profil sekolah, jam toleransi keterlambatan, serta manajemen role & permission.
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-full-access">Otoritas Penuh</span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-no-access">Tidak Ada</span>
                    </td>
                    <td class="text-center">
                        <span class="status-badge-clean status-no-access">Tidak Ada</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="p-3 bg-slate-50 border-top border-slate-100 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="text-slate-500 small">
            <i class='bx bx-info-circle text-blue-600 me-1'></i>
            Hak otoritas dikunci secara terstruktur untuk memastikan keamanan dan integritas rekam presensi.
        </span>
        <span class="badge bg-white text-slate-700 border border-slate-200 px-2.5 py-1 rounded-pill small">
            SMP PGRI Parung Panjang
        </span>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Widget Jam & Tanggal Real-time
    function updateClockWidget() {
        const clockEl = document.getElementById('realtimeDateClock');
        if (!clockEl) return;
        const now = new Date();
        const dateFormatted = new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            timeZone: 'Asia/Jakarta'
        }).format(now);
        
        const timeFormatted = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            timeZone: 'Asia/Jakarta'
        });

        clockEl.textContent = `${dateFormatted} • ${timeFormatted} WIB`;
    }

    setInterval(updateClockWidget, 1000);
    updateClockWidget();
</script>
@endpush
