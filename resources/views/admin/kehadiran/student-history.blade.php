@extends('layouts.app')

@section('title', 'Riwayat Presensi')
@section('page_title', 'Riwayat Presensi')
@section('page_subtitle', 'HISTORI KEHADIRAN SISWA PER PERIODE')

@push('styles')
<style>
    /* Stat Items */
    .stat-item {
        text-align: center;
        flex: 1;
        min-width: 0;
    }
    .stat-value { font-size: 0.75rem; font-weight: 400; line-height: 1.2; }
    .stat-label { font-size: 0.6rem; color: #64748b; margin-top: 0; font-weight: 400; text-transform: uppercase; }

    /* Period Nav - full width bagi 3 */
    .period-nav {
        display: flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 8px;
        gap: 3px;
        width: 100%;
    }
    .period-link {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.4rem 0.75rem;
        font-size: 0.85rem;
        font-weight: 400;
        color: #64748b;
        text-decoration: none;
        border-radius: 6px;
        background-color: transparent;
        transition: all 0.15s ease;
        text-transform: uppercase;
    }
    .period-link:hover { color: #1e293b; background-color: #e2e8f0; }
    .period-link.active {
        background-color: #2563eb;
        color: #ffffff;
        font-weight: 400;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }

    /* Date Filter - full width bagi 3 */
    .date-filter-group {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        width: 100%;
    }
    .date-filter-group input[type="date"] {
        flex: 1;
        min-width: 0;
        padding: 0.15rem 0.35rem;
        font-size: 0.68rem;
        height: 26px;
    }
    .date-filter-group .btn {
        flex: 0 0 auto;
        white-space: nowrap;
        padding: 0.15rem 0.6rem;
        font-size: 0.68rem;
        height: 26px;
    }

    /* Table */
    .table-history thead th {
        background-color: #f8fafc; color: #475569; font-weight: 400; padding: 0.75rem 0.5rem;
        border-bottom: 1.5px solid #edf2f7; text-align: center; vertical-align: middle; white-space: nowrap;
        text-transform: uppercase; letter-spacing: 0.03em; font-family: 'Poppins', 'Roboto', sans-serif;
        font-size: 0.8rem;
    }
    .table-history tbody td {
        padding: 0.7rem 0.5rem; text-align: center; vertical-align: middle;
        border-right: 1px solid #f1f5f9; color: #1e293b; white-space: nowrap;
        text-transform: uppercase; letter-spacing: 0.03em; font-family: 'Poppins', 'Roboto', sans-serif;
        font-weight: 400; font-size: 0.85rem;
    }
    .table-history tbody tr:nth-child(even) > td { background-color: #f1f5f9 !important; }
    .table-history tbody tr:nth-child(odd) > td { background-color: #ffffff !important; }
    .table-history tbody tr:hover > td { background-color: #e2e8f0 !important; }

    .status-hadir { color: #16a34a; font-weight: 400; }
    .status-terlambat { color: #d97706; font-weight: 400; }
    .status-sakit { color: #2563eb; font-weight: 400; }
    .status-izin { color: #7e22ce; font-weight: 400; }
    .status-alfa { color: #ef4444; font-weight: 400; }

    /* Mobile */
    @media (max-width: 767.98px) {
        .stat-value { font-size: 0.68rem; }
        .stat-label { font-size: 0.55rem; }
        .table-history thead th { font-size: 0.72rem; padding: 0.5rem 0.35rem; }
        .table-history tbody td { font-size: 0.78rem; padding: 0.5rem 0.35rem; }
        .period-link { font-size: 0.78rem; padding: 0.3rem 0.4rem; }
        .date-filter-group input[type="date"] { font-size: 0.55rem; height: 20px; padding: 0 0.15rem; }
        .date-filter-group .btn { font-size: 0.55rem; height: 20px; padding: 0 0.3rem; }
        .date-filter-group span { font-size: 0.6rem !important; }
    }
</style>
@endpush

@section('content')
    <!-- Info Siswa -->
    <div class="mb-3">
        <h5 class="mb-1" style="color: #0f172a; text-transform: uppercase; font-weight: 400;">{{ $student->name }}</h5>
        <p class="text-secondary mb-0" style="font-size: 0.85rem; text-transform: uppercase;">
            NIS: {{ $student->nis }} &bull; Kelas: {{ $student->schoolClass->name ?? '-' }}
        </p>
    </div>

    <!-- 1. Statistik + Filter - 1 Container Kompak -->
    <div class="card border-0 shadow-sm rounded-4 p-2 mb-2 bg-white">
        <form method="GET" action="{{ route('admin.kehadiran.student-history', $student->id) }}">
            <!-- Stats -->
            <div class="d-flex flex-wrap justify-content-between gap-1 mb-2 pb-2" style="border-bottom: 1px solid #f1f5f9;">
                <div class="stat-item">
                    <div class="stat-value" style="color: #16a34a;">{{ $stats['hadir'] }}</div>
                    <div class="stat-label">Hadir</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #d97706;">{{ $stats['terlambat'] }}</div>
                    <div class="stat-label">Terlambat</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #2563eb;">{{ $stats['sakit'] }}</div>
                    <div class="stat-label">Sakit</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #7e22ce;">{{ $stats['izin'] }}</div>
                    <div class="stat-label">Izin</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #ef4444;">{{ $stats['alfa'] }}</div>
                    <div class="stat-label">Alfa</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-dark">{{ $attendanceRate }}%</div>
                    <div class="stat-label">Kehadiran</div>
                </div>
            </div>

            <!-- Period Nav -->
            <div class="period-nav mb-2">
                <a href="{{ route('admin.kehadiran.student-history', [$student->id, 'period' => 'harian']) }}" class="period-link {{ $period === 'harian' ? 'active' : '' }}">
                    Harian
                </a>
                <a href="{{ route('admin.kehadiran.student-history', [$student->id, 'period' => 'mingguan']) }}" class="period-link {{ $period === 'mingguan' ? 'active' : '' }}">
                    Mingguan
                </a>
                <a href="{{ route('admin.kehadiran.student-history', [$student->id, 'period' => 'bulanan']) }}" class="period-link {{ $period === 'bulanan' ? 'active' : '' }}">
                    Bulanan
                </a>
            </div>

            <!-- Date Filter -->
            <div class="date-filter-group">
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                <span class="text-muted" style="font-size: 0.75rem; white-space: nowrap; text-transform: uppercase;">s/d</span>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-history">
                <thead>
                    <tr>
                        <th style="width: 45px;">No</th>
                        <th style="width: 100px;">Tanggal</th>
                        <th style="width: 80px;">Hari</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 100px;">Jam Masuk</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                    <tr>
                        <td class="text-secondary">{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($attendance->date)->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($attendance->date)->translatedFormat('l') }}</td>
                        <td>
                            @if($attendance->status === 'Hadir')
                                <span class="status-hadir">Hadir</span>
                            @elseif($attendance->status === 'Terlambat')
                                <span class="status-terlambat">Terlambat</span>
                            @elseif($attendance->status === 'Sakit')
                                <span class="status-sakit">Sakit</span>
                            @elseif($attendance->status === 'Izin')
                                <span class="status-izin">Izin</span>
                            @elseif($attendance->status === 'Alfa')
                                <span class="status-alfa">Alfa</span>
                            @else
                                <span class="text-secondary">{{ $attendance->status }}</span>
                            @endif
                        </td>
                        <td>{{ $attendance->check_in ?? '-' }}</td>
                        <td class="text-start">{{ $attendance->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">
                            <i class='bx bx-info-circle fs-2 d-block mb-2'></i>
                            BELUM ADA DATA PRESENSI UNTUK PERIODE INI.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
 @endsection
