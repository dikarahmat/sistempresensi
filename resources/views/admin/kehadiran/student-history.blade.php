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

    /* Date Filter - full width bagi 3
       UKURAN DIPERBESAR (input tanggal & tombol Tampilkan) supaya mudah
       dibaca dan diklik - warna dan font family tetap sama. */
    .date-filter-group {
        display: flex;
        align-items: stretch;
        gap: 0.5rem;
        width: 100%;
    }
    .date-filter-group input[type="date"] {
        flex: 1;
        min-width: 0;
        padding: 0.55rem 0.75rem;
        font-size: 1rem;
        height: 46px;
        cursor: pointer;
    }
    .date-filter-group .btn {
        flex: 0 0 auto;
        white-space: nowrap;
        padding: 0.55rem 1.25rem;
        font-size: 0.95rem;
        height: 46px;
        cursor: pointer;
    }

    /* Keterangan periode aktif di atas tabel */
    .period-caption {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
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

    /* Status: TEKS MURNI (tanpa badge/container), warna diselaraskan dengan
       halaman Presensi agar konsisten & kontrasnya tinggi. */
    .status-hadir { color: #065f46; font-weight: 400; }
    .status-terlambat { color: #92400e; font-weight: 400; }
    .status-sakit { color: #1d4ed8; font-weight: 400; }
    .status-izin { color: #5b21b6; font-weight: 400; }
    .status-alfa { color: #991b1b; font-weight: 400; }
    .status-libur, .status-belum { color: #475569; font-weight: 400; }

    /* Tanggal di tabel dibuat lebih besar & mudah dibaca.
       Font family, warna, dan uppercase tidak diubah. */
    .table-history tbody td.col-tanggal {
        font-size: 1rem;
        line-height: 1.4;
    }

    .table-history tbody td.col-jam {
        font-size: 1rem;
        line-height: 1.4;
    }

    /* Mobile */
    @media (max-width: 767.98px) {
        .stat-value { font-size: 0.68rem; }
        .stat-label { font-size: 0.55rem; }
        .table-history thead th { font-size: 0.72rem; padding: 0.5rem 0.35rem; }
        .table-history tbody td { font-size: 0.78rem; padding: 0.5rem 0.35rem; }
        .period-link { font-size: 0.78rem; padding: 0.3rem 0.4rem; }
        /* Di mobile input tanggal tetap diperbesar (jangan mengecil lagi),
           hanya tinggi & font-nya dikecilkan sedikit agar tetap muat. */
        .date-filter-group input[type="date"] { font-size: 0.95rem; height: 40px; padding: 0.35rem 0.6rem; }
        .date-filter-group .btn { font-size: 0.85rem; height: 40px; padding: 0.35rem 0.9rem; }
        .period-caption { font-size: 0.75rem; }
    }
</style>
@endpush

@section('content')
    <!-- Info Siswa -->
    <div class="mb-3">
        <h5 class="mb-1" style="color: #0f172a; text-transform: uppercase; font-weight: 400;">{{ $student->name }}</h5>
        <p class="text-secondary mb-0" style="font-size: 0.85rem; text-transform: uppercase;">
            NISN: {{ $student->nisn ?: '-' }} &bull; Kelas: {{ $student->schoolClass->name ?? '-' }}
        </p>
    </div>

    <!-- 1. Statistik + Filter - 1 Container Kompak -->
    <div class="card border-0 shadow-sm rounded-4 p-2 mb-2 bg-white">
        <form method="GET" action="{{ panel_route('kehadiran.student-history', $student->id) }}">
            <input type="hidden" name="period" value="{{ $period }}">
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

            <!-- Period Nav
         Setiap tab membawa `tanggal` yang sedang dipilih agar berpindah mode
         TIDAK mengembalikan tanggal ke default. -->
            <div class="period-nav mb-2">
                <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'harian', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'harian' ? 'active' : '' }}">
                    Harian
                </a>
                <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'mingguan', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'mingguan' ? 'active' : '' }}">
                    Mingguan
                </a>
                <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'bulanan', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'bulanan' ? 'active' : '' }}">
                    Bulanan
                </a>
            </div>

            <!-- Teks Periode hasil mode + tanggal yang dipilih -->
            <div class="period-caption text-secondary mb-2">
                Periode: <strong>{{ $periodLabel }}</strong>
                <span class="text-muted">({{ ucfirst($period) }})</span>
            </div>

            <!-- Date Filter: satu tanggal acuan,.ukuran diperbesar agar mudah
                 diklik / dibaca (pengguna kemungkinan orang tua).
                 UKURAN & AREA KLIK DIPERBESAR - warna & font family tidak diubah. -->
            <div class="date-filter-group">
                <input type="date" name="tanggal" id="studentHistoryDate" onchange="this.form.submit()" class="form-control" value="{{ $anchor->toDateString() }}" aria-label="Pilih tanggal">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
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
                        <th title="Diisi dari kolom Catatan / Keterangan pada form Ubah Presensi di halaman Presensi Kelas">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                    @php
                        // Status TAMPILAN memakai effective_status: di database
                        // "Terlambat" tersimpan sebagai status 'Hadir' +
                        // time_remark, jadi status mentah akan selalu "Hadir".
                        $statusTampil = strtolower($attendance->effective_status);
                    @endphp
                    <tr>
                        <td data-label="No" class="text-secondary">{{ $loop->iteration }}</td>
                        <td data-label="Tanggal" class="col-tanggal">{{ \Carbon\Carbon::parse($attendance->date)->format('d M Y') }}</td>
                        <td data-label="Hari">{{ \Carbon\Carbon::parse($attendance->date)->translatedFormat('l') }}</td>
                        <td data-label="Status">
                            <span class="status-{{ $statusTampil }}">{{ ucfirst($attendance->effective_status) }}</span>
                        </td>
                        <td data-label="Jam Masuk" class="col-jam">{{ $attendance->check_in ? substr($attendance->check_in, 0, 5) : '-' }}</td>
                        <td data-label="Catatan" class="text-start">{{ $attendance->notes && $attendance->notes !== '-' ? $attendance->notes : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            BELUM ADA DATA PRESENSI UNTUK PERIODE INI.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
 @endsection
