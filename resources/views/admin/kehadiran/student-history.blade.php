@extends('layouts.app')

@section('title', __('Riwayat Presensi'))
@section('page_title', __('Riwayat Presensi'))
@section('page_subtitle', __('HISTORI KEHADIRAN SISWA PER PERIODE'))

@push('styles')
<style>
    /* Statistik: baris ringkasan TANPA garis & tanpa kotak (revisi).
       Tidak ada border atas/bawah, tidak ada sekat antar kolom, tidak ada
       background, shadow, radius, atau padding kotak - hanya teks angka + label.
       Jarak ke baris nama siswa (atas) dan ke tab periode (bawah) ~.75rem. */
    .stat-summary {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: stretch;
        gap: 0;
        padding: 0.35rem 0;
        margin-bottom: 0.75rem;
    }
    .stat-item {
        text-align: center;
        flex: 1 1 0;
        min-width: 0;
    }
    .stat-value {
        font-size: 1.1rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .stat-label {
        font-size: 0.65rem;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        line-height: 1.2;
        margin-top: 0.125rem;
    }

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
        transition: background-color 0.15s ease, color 0.15s ease;
        text-transform: uppercase;
        -webkit-tap-highlight-color: transparent;
    }
    @media (hover: hover) {
        .period-link:hover { color: #1e293b; background-color: #e2e8f0; }
    }
    .period-link:active { background-color: #cbd5e1; }
    .period-link.active,
    .period-link.active:hover,
    .period-link.active:active,
    .period-link.active:focus,
    .period-link.active:focus-visible {
        background-color: #2563eb !important;
        color: #ffffff !important;
        font-weight: 400;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }

    /* Fokus keyboard: outline tipis biru, tidak mengubah warna latar. */
    .period-link:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }

    /* Layar kecil: tombol tetap menyatu dan di tengah, tidak overflow. */
    @media (max-width: 575.98px) {
        .period-link {
            flex: 0 1 auto;
            padding: 0.4rem 0.65rem;
            font-size: 0.78rem;
        }
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
        /* Susunan enam kolom tetap (tidak diubah); hanya ukuran angka & label
           yang dikecilkan, dan tidak ada garis/kotak tambahan. */
        .stat-value { font-size: 1rem; }
        .stat-label { font-size: 0.62rem; }
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
    <!-- Info Siswa (jarak ke baris statistik ~.75rem = mb-2) -->
    <div class="mb-2">
        <h5 class="mb-1" style="color: #0f172a; text-transform: uppercase; font-weight: 400;">{{ $student->name }}</h5>
        <p class="text-secondary mb-0" style="font-size: 0.85rem; text-transform: uppercase;">
            {{ __('NISN') }}: {{ $student->nisn ?: '-' }} &bull; {{ __('Kelas:') }} {{ $student->schoolClass->name ?? '-' }}
        </p>
    </div>

    <!-- Statistik & Filter Periode (tanpa card/box, latar putih polos) -->
    <div class="mb-3">
        <form method="GET" action="{{ panel_route('kehadiran.student-history', $student->id) }}">
            <input type="hidden" name="period" value="{{ $period }}">

            <!-- Statistik (satu bar ringkasan: 6 kolom, sekat tipis antar kolom) -->
            <div class="stat-summary">
                <div class="stat-item">
                    <div class="stat-value" style="color: #16a34a;">{{ $stats['hadir'] }}</div>
                    <div class="stat-label">{{ __('Hadir') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #d97706;">{{ $stats['terlambat'] }}</div>
                    <div class="stat-label">{{ __('Terlambat') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #2563eb;">{{ $stats['sakit'] }}</div>
                    <div class="stat-label">{{ __('Sakit') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #7e22ce;">{{ $stats['izin'] }}</div>
                    <div class="stat-label">{{ __('Izin') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #ef4444;">{{ $stats['alfa'] }}</div>
                    <div class="stat-label">{{ __('Alfa') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-dark">{{ $attendanceRate }}%</div>
                    <div class="stat-label">{{ __('Kehadiran') }}</div>
                </div>
            </div>

            <!-- Period Nav.
                 Mode "Semua" memakai periode yang sama dengan daftar Catatan
                 Kehadiran (tahun ajaran aktif), jadi angkanya bisa langsung
                 dibandingkan dengan tabel daftar. -->
            <div class="period-nav mb-2">
                <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'harian', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'harian' ? 'active' : '' }}">
                    {{ __('Harian') }}
                </a>
                <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'mingguan', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'mingguan' ? 'active' : '' }}">
                    {{ __('Mingguan') }}
                </a>
                <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'bulanan', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'bulanan' ? 'active' : '' }}">
                    {{ __('Bulanan') }}
                </a>
                <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'semua', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'semua' ? 'active' : '' }}">
                    {{ __('Per Semester/Tahun') }}
                </a>
            </div>

            <!-- Teks Periode hasil mode + tanggal yang dipilih -->
            <div class="period-caption text-secondary mb-2">
                {{ __('Periode:') }} <strong>{{ $periodLabel }}</strong>
                <span class="text-muted">({{ __(ucfirst($period)) }})</span>
            </div>

            <!-- Date Filter: tanpa card/box, latar putih polos -->
            <div class="date-filter-group">
                <input type="date" name="tanggal" id="studentHistoryDate" onchange="this.form.submit()" class="form-control" value="{{ $anchor->toDateString() }}" aria-label="{{ __('Pilih tanggal') }}">
                <button type="submit" class="btn btn-primary">{{ __('Tampilkan') }}</button>
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-history">
                <thead>
                    <tr>
                        <th style="width: 40px;">{{ __('No') }}</th>
                        <th style="width: 80px;">{{ __('Hari') }}</th>
                        <th style="width: 100px;">{{ __('Tanggal') }}</th>
                        <th style="width: 100px;">{{ __('Jam Masuk') }}</th>
                        <th style="width: 70px;">{{ __('Status') }}</th>
                        <th style="width: 70px;">{{ __('Keterlambatan') }}</th>
                        <th style="width: 200px;" title="{{ __('Diisi dari kolom Catatan / Keterangan pada form Ubah Presensi di halaman Presensi Kelas') }}">{{ __('Keterangan') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows ?? [] as $row)
@php
                    // Status TAMPILAN memakai Attendance::statusOf (satu
                    // sumber): status hanya dari catatan presensi yang tersimpan.
                    // - Ada catatan: status efektif (Hadir/Terlambat/Sakit/Izin/Alfa)
                    // - Tidak ada catatan, libur/weekend: LIBUR
                    // - Tidak ada catatan, selainnya: "-"
                    $statusTampil = $row['status'];
                    $att = $row['attendance'] ?? null;
@endphp
                    <tr>
                        <td data-label="{{ __('No') }}" class="text-secondary">{{ $loop->iteration }}</td>
                        <td data-label="{{ __('Hari') }}" class="col-hari">{{ \Carbon\Carbon::parse($row['date'])->translatedFormat('l') }}</td>
                        <td data-label="{{ __('Tanggal') }}" class="col-tanggal">{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                        <td data-label="{{ __('Jam Masuk') }}" class="col-jam">{{ ($att && $att->check_in) ? substr($att->check_in, 0, 5) : '-' }}</td>
                        <td data-label="{{ __('Status') }}">
                            @if($statusTampil === '-')
                                <span style="color: #647489; font-weight: 400;">-</span>
                            @else
                                <span class="status-{{ strtolower($statusTampil) }}">{{ ucfirst($statusTampil) }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('Keterlambatan') }}" class="text-center">{{ $row['late_text'] ?? '-' }}</td>
                        <td data-label="{{ __('Catatan') }}" class="text-start">{{ $row['notes'] ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center align-middle py-5 text-secondary empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            {{ __('Tidak ada data presensi untuk periode ini.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
 @endsection