@extends('layouts.app')

@section('title', 'Rekap Presensi Siswa')
@section('page_title', 'Rekap Presensi Siswa')
@section('page_subtitle', 'Kelola rekapitulasi kehadiran siswa harian, mingguan, dan bulanan.')

@section('page_header_right')
@php
    $exportParams = [
        'type' => $type,
        'class_id' => $classId,
        'date' => $date ?? date('Y-m-d'),
        'start_date' => $startDate ?? date('Y-m-d'),
        'end_date' => $endDate ?? date('Y-m-d'),
        'month' => $month ?? date('n'),
        'year' => $year ?? date('Y'),
    ];
@endphp
<div class="d-none d-md-block">
    <div class="d-flex align-items-center gap-1 gap-md-2">
        <!-- Tombol Export Excel: Ikon di Mobile, Ikon + Teks di Desktop -->
        <a href="{{ route('admin.rekap.export-excel', $exportParams) }}" 
           class="btn-green-excel" 
           title="Export Excel (.xlsx)"
           aria-label="Export Excel (.xlsx)"
           data-bs-toggle="tooltip">
            <i class='bx bx-spreadsheet fs-6'></i>
            <span class="d-none d-md-inline">Ekspor Excel</span>
        </a>
        <!-- Tombol Cetak PDF: Ikon di Mobile, Ikon + Teks di Desktop -->
        <a href="{{ route('admin.rekap.export-pdf', $exportParams) }}" 
           class="btn-red-pdf" 
           title="Cetak PDF Report"
           aria-label="Cetak PDF Report"
           data-bs-toggle="tooltip">
            <i class='bx bxs-file-pdf fs-6'></i>
            <span class="d-none d-md-inline">PDF Report</span>
        </a>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Header Bar Alignment on Mobile (< 768px): Pojok Kanan Atas Sejajar */
    @media (max-width: 767.98px) {
        .app-header-bar {
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 0.5rem !important;
            margin-bottom: 0.75rem !important;
        }
        .app-header-left {
            flex: 1 1 auto;
            min-width: 0;
        }
        .header-main-title {
            font-size: 0.95rem !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .header-main-subtitle {
            display: none !important;
        }
        .app-header-right {
            flex: 0 0 auto;
            margin-left: auto;
        }
    }

    /* Buttons Export Responsif: Ikon di Mobile, Ikon + Teks di Desktop */
    .btn-green-excel {
        background-color: #059669;
        color: #ffffff !important;
        font-size: 0.8rem;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        white-space: nowrap;
        box-shadow: 0 1px 2px rgba(5, 150, 105, 0.2);
        transition: all 0.15s ease-in-out;
        flex-shrink: 0;
    }
    .btn-green-excel:hover {
        background-color: #047857;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(5, 150, 105, 0.3);
    }

    .btn-red-pdf {
        background-color: #ef4444;
        color: #ffffff !important;
        font-size: 0.8rem;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        white-space: nowrap;
        box-shadow: 0 1px 2px rgba(239, 68, 68, 0.2);
        transition: all 0.15s ease-in-out;
        flex-shrink: 0;
    }
    .btn-red-pdf:hover {
        background-color: #dc2626;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(239, 68, 68, 0.3);
    }

    @media (min-width: 768px) {
        .btn-green-excel,
        .btn-red-pdf {
            width: auto;
            padding: 0 0.75rem;
            gap: 0.4rem;
        }
    }

    /* Period Switcher Tabs Responsif */
    .period-container {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        margin-bottom: 0.75rem;
    }

    .period-nav {
        display: inline-flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 10px;
        gap: 3px;
    }

    .period-link {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.42rem 0.9rem;
        font-size: 0.82rem;
        font-weight: 500;
        color: #64748b;
        text-decoration: none;
        border-radius: 8px;
        background-color: transparent;
        transition: all 0.15s ease;
    }

    .period-link:hover {
        color: #1e293b;
        background-color: #e2e8f0;
    }

    .period-link.active {
        background-color: #2563eb;
        color: #ffffff;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }

    @media (max-width: 575.98px) {
        .period-container {
            width: 100%;
        }
        .period-nav {
            width: 100%;
            display: flex;
        }
        .period-link {
            flex: 1;
            justify-content: center;
            padding: 0.45rem 0.35rem;
            font-size: 0.8rem;
            text-align: center;
            gap: 0.25rem;
        }
    }

    /* Filter Controls Bersih & Natural Tanpa Kotak Kontainer */
    .filter-card {
        background: transparent !important;
        border: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        margin-bottom: 1.25rem !important;
        box-shadow: none !important;
    }

    .filter-label {
        font-size: 0.74rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.25rem;
        display: block;
    }

    .filter-input {
        height: 36px;
        font-size: 0.82rem;
        border-radius: 8px;
        border-color: #cbd5e1;
        background-color: #ffffff;
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
        transition: all 0.15s ease-in-out;
    }
    .filter-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
    }

    .filter-btn {
        height: 36px;
        font-size: 0.82rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        font-weight: 600;
    }

    /* Legenda Status: catatan kecil & unobtrusive di bawah filter */
    .rekap-legend {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem 0.5rem;
        padding: 0.4rem 0.6rem;
        border: 1px dashed #e2e8f0;
        border-radius: 10px;
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.72rem;
        line-height: 1.4;
    }

    .rekap-legend-label {
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-size: 0.64rem;
    }

    .rekap-legend-items {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.3rem 0.55rem;
    }

    .rekap-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        white-space: nowrap;
    }

    .rekap-legend-item strong {
        font-weight: 700;
    }

    @media (max-width: 575.98px) {
        .rekap-legend {
            padding: 0.35rem 0.5rem;
            font-size: 0.68rem;
        }

        .rekap-legend-items {
            gap: 0.25rem 0.45rem;
        }
    }

    /* Table Custom Card & Smooth Horizontal Scroll */
    .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
        width: 100%;
    }

    .table-matrix {
        margin-bottom: 0;
        width: 100%;
        min-width: 800px;
        border-collapse: collapse;
        font-size: 0.82rem;
    }

    .table-matrix thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        padding: 0.75rem 0.5rem;
        border-bottom: 1.5px solid #edf2f7;
        text-align: center;
        vertical-align: middle;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }

    .table-matrix thead th.th-holiday {
        background-color: #fee2e2 !important;
        color: #dc2626 !important;
    }

    .table-matrix tbody tr {
        border-bottom: 1px solid #f1f5f9;
    }

    /* Zebra Striping Khusus (Sesuai Benchmark Data Siswa) */
    .table-matrix tbody tr:nth-child(even) > td,
    .table-matrix tbody tr.baris-abu > td,
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f1f5f9 !important;
    }

    .table-matrix tbody tr:nth-child(odd) > td,
    .table-matrix tbody tr.baris-putih > td,
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }

    .table-matrix tbody tr.baris-abu:hover > td,
    .table-matrix tbody tr.baris-putih:hover > td,
    .table-matrix tbody tr:hover > td,
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td {
        background-color: #e2e8f0 !important;
    }

    .table-matrix tbody td {
        padding: 0.7rem 0.5rem;
        text-align: center;
        vertical-align: middle;
        border-right: 1px solid #f1f5f9;
        color: #1e293b;
        font-weight: 400 !important;
        white-space: nowrap;
    }

    /* Status Letters (Murni Huruf Berwarna Tanpa Background Box) */
    .cell-present { color: #16a34a !important; font-weight: 700; font-size: 0.85rem; background: transparent !important; border: none !important; padding: 0 !important; display: inline-block; }
    .cell-late { color: #d97706 !important; font-weight: 700; font-size: 0.85rem; background: transparent !important; border: none !important; padding: 0 !important; display: inline-block; }
    .cell-sick { color: #2563eb !important; font-weight: 700; font-size: 0.85rem; background: transparent !important; border: none !important; padding: 0 !important; display: inline-block; }
    .cell-permission { color: #7e22ce !important; font-weight: 700; font-size: 0.85rem; background: transparent !important; border: none !important; padding: 0 !important; display: inline-block; }
    .cell-alpha { color: #ef4444 !important; font-weight: 700; font-size: 0.85rem; background: transparent !important; border: none !important; padding: 0 !important; display: inline-block; }
    .cell-holiday { color: #64748b !important; font-weight: 600; font-size: 0.85rem; background: transparent !important; border: none !important; padding: 0 !important; display: inline-block; }
    .cell-future { color: #cbd5e1 !important; font-weight: 400; font-size: 0.85rem; background: transparent !important; border: none !important; padding: 0 !important; display: inline-block; }
</style>
@endpush

@section('content')
@php
    $exportParams = [
        'type' => $type,
        'class_id' => $classId,
        'date' => $date ?? date('Y-m-d'),
        'start_date' => $startDate ?? date('Y-m-d'),
        'end_date' => $endDate ?? date('Y-m-d'),
        'month' => $month ?? date('n'),
        'year' => $year ?? date('Y'),
    ];
@endphp

    <!-- ========================================================================= -->
    <!-- 1. KONTEN KHUSUS MOBILE (< 768px): KARTU TERPADU TOMBOL & FILTER REKAP   -->
    <!-- ========================================================================= -->
    <div class="d-block d-md-none mb-3">
        <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white">
            <!-- Bagian Atas: Tombol Aksi (Export Excel, Cetak PDF, Harian, Mingguan, Bulanan) -->
            <div class="row g-2">
                <!-- 1. Export Excel -->
                <div class="col-6">
                    <a href="{{ route('admin.rekap.export-excel', $exportParams) }}" 
                       class="btn btn-success btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5 px-2 fw-semibold">
                        <i class='bx bx-spreadsheet fs-6'></i>
                        <span class="text-nowrap" style="font-size: 0.8rem;">Ekspor Excel</span>
                    </a>
                </div>

                <!-- 2. Cetak PDF -->
                <div class="col-6">
                    <a href="{{ route('admin.rekap.export-pdf', $exportParams) }}" 
                       class="btn btn-danger btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5 px-2 fw-semibold">
                        <i class='bx bxs-file-pdf fs-6'></i>
                        <span class="text-nowrap" style="font-size: 0.8rem;">PDF Report</span>
                    </a>
                </div>

                <!-- 3. Periode Harian -->
                <div class="col-4">
                    <a href="{{ route('admin.rekap', array_merge(request()->except('type'), ['type' => 'harian'])) }}"
                       class="btn {{ $type === 'harian' ? 'btn-primary' : 'btn-outline-primary' }} btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1 py-1.5 px-1 fw-semibold text-nowrap" style="font-size: 0.78rem;">
                        <i class='bx bx-calendar-event'></i> Harian
                    </a>
                </div>

                <!-- 4. Periode Mingguan -->
                <div class="col-4">
                    <a href="{{ route('admin.rekap', array_merge(request()->except('type'), ['type' => 'mingguan'])) }}"
                       class="btn {{ $type === 'mingguan' ? 'btn-primary' : 'btn-outline-primary' }} btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1 py-1.5 px-1 fw-semibold text-nowrap" style="font-size: 0.78rem;">
                        <i class='bx bx-calendar-week'></i> Mingguan
                    </a>
                </div>

                <!-- 5. Periode Bulanan -->
                <div class="col-4">
                    <a href="{{ route('admin.rekap', array_merge(request()->except('type'), ['type' => 'bulanan'])) }}"
                       class="btn {{ $type === 'bulanan' ? 'btn-primary' : 'btn-outline-primary' }} btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1 py-1.5 px-1 fw-semibold text-nowrap" style="font-size: 0.78rem;">
                        <i class='bx bx-calendar'></i> Bulanan
                    </a>
                </div>
            </div>

            <!-- Sekat Pemisah -->
            <hr class="border-secondary-subtle my-3">

            <!-- Bagian Bawah: Form Filter Sesuai Periode Aktif -->
            <form method="GET" action="{{ route('admin.rekap') }}" class="m-0">
                <input type="hidden" name="type" value="{{ $type }}">

                @if($type === 'harian')
                    <div class="mb-2">
                        <label class="form-label text-muted small fw-semibold mb-1">Tanggal Presensi</label>
                        <input type="date" name="date" class="form-control form-control-sm border-secondary-subtle shadow-sm rounded-3" value="{{ $date ?? date('Y-m-d') }}">
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-muted small fw-semibold mb-1">Pilih Kelas</label>
                        <select name="class_id" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>
                                    Kelas {{ $c->name }} (Tingkat {{ $c->grade }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3 shadow-xs fw-semibold mt-1">
                        <i class='bx bx-filter-alt me-1'></i> Tampilkan Data
                    </button>
                @elseif($type === 'mingguan')
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control form-control-sm border-secondary-subtle shadow-sm rounded-3" value="{{ $startDate }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Tanggal Selesai</label>
                            <input type="date" name="end_date" class="form-control form-control-sm border-secondary-subtle shadow-sm rounded-3" value="{{ $endDate }}">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-muted small fw-semibold mb-1">Pilih Kelas</label>
                        <select name="class_id" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>
                                    Kelas {{ $c->name }} (Tingkat {{ $c->grade }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3 shadow-xs fw-semibold mt-1">
                        <i class='bx bx-filter-alt me-1'></i> Tampilkan Data
                    </button>
                @else
                    <!-- BULANAN -->
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Bulan</label>
                            <select name="month" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::createFromDate($year, $m, 1)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Tahun</label>
                            <select name="year" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                                @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-muted small fw-semibold mb-1">Pilih Kelas</label>
                        <select name="class_id" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>
                                    Kelas {{ $c->name }} (Tingkat {{ $c->grade }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-3 shadow-xs fw-semibold mt-1">
                        <i class='bx bx-filter-alt me-1'></i> Tampilkan Data
                    </button>
                @endif
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. KONTEN KHUSUS DESKTOP (>= 768px): TAMPILAN ASLI TIDAK DIUBAH          -->
    <!-- ========================================================================= -->
    <div class="d-none d-md-block">
        <!-- Navigasi Tab Multi-Periode Responsif -->
        <div class="period-container">
            <div class="period-nav">
                <a href="{{ route('admin.rekap', array_merge(request()->except('type'), ['type' => 'harian'])) }}"
                   class="period-link {{ $type === 'harian' ? 'active' : '' }}">
                    <i class='bx bx-calendar-event'></i>
                    <span class="d-none d-md-inline">Rekap </span><span>Harian</span>
                </a>
                <a href="{{ route('admin.rekap', array_merge(request()->except('type'), ['type' => 'mingguan'])) }}"
                   class="period-link {{ $type === 'mingguan' ? 'active' : '' }}">
                    <i class='bx bx-calendar-week'></i>
                    <span class="d-none d-md-inline">Rekap </span><span>Mingguan</span>
                </a>
                <a href="{{ route('admin.rekap', array_merge(request()->except('type'), ['type' => 'bulanan'])) }}"
                   class="period-link {{ $type === 'bulanan' ? 'active' : '' }}">
                    <i class='bx bx-calendar'></i>
                    <span class="d-none d-md-inline">Rekap </span><span>Bulanan</span>
                </a>
            </div>
        </div>

        <!-- Area Filter Bersih & Natural Tanpa Kotak Kontainer Kaku -->
        <div class="filter-card">
            <form method="GET" action="{{ route('admin.rekap') }}" class="row g-2 align-items-end">
                <input type="hidden" name="type" value="{{ $type }}">

                @if($type === 'harian')
                    <div class="col-6 col-md-4 col-lg-3">
                        <label class="filter-label">Tanggal Presensi</label>
                        <input type="date" name="date" class="form-control form-control-sm filter-input" value="{{ $date ?? date('Y-m-d') }}">
                    </div>

                    <div class="col-6 col-md-5 col-lg-4">
                        <label class="filter-label">Pilih Kelas</label>
                        <select name="class_id" class="form-select form-select-sm filter-input">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>
                                    Kelas {{ $c->name }} (Tingkat {{ $c->grade }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-auto ms-md-auto d-flex align-items-end justify-content-md-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100 w-md-auto px-4 fw-semibold filter-btn">
                            <i class='bx bx-filter-alt me-1'></i> Tampilkan Data
                        </button>
                    </div>
                @elseif($type === 'mingguan')
                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="filter-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control form-control-sm filter-input" value="{{ $startDate }}">
                    </div>

                    <div class="col-6 col-md-3 col-lg-3">
                        <label class="filter-label">Tanggal Selesai</label>
                        <input type="date" name="end_date" class="form-control form-control-sm filter-input" value="{{ $endDate }}">
                    </div>

                    <div class="col-12 col-md-3 col-lg-3">
                        <label class="filter-label">Pilih Kelas</label>
                        <select name="class_id" class="form-select form-select-sm filter-input">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>
                                    Kelas {{ $c->name }} (Tingkat {{ $c->grade }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-auto ms-md-auto d-flex align-items-end justify-content-md-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100 w-md-auto px-4 fw-semibold filter-btn">
                            <i class='bx bx-filter-alt me-1'></i> Tampilkan Data
                        </button>
                    </div>
                @else
                    <!-- BULANAN -->
                    <div class="col-6 col-md-3 col-lg-2">
                        <label class="filter-label">Bulan</label>
                        <select name="month" class="form-select form-select-sm filter-input">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromDate($year, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-6 col-md-2 col-lg-2">
                        <label class="filter-label">Tahun</label>
                        <select name="year" class="form-select form-select-sm filter-input">
                            @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-12 col-md-4 col-lg-4">
                        <label class="filter-label">Pilih Kelas</label>
                        <select name="class_id" class="form-select form-select-sm filter-input">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>
                                    Kelas {{ $c->name }} (Tingkat {{ $c->grade }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-auto ms-md-auto d-flex align-items-end justify-content-md-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100 w-md-auto px-4 fw-semibold filter-btn">
                            <i class='bx bx-filter-alt me-1'></i> Tampilkan Data
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Alert Hari Libur (Jika Harian & Libur) -->
    @if($type === 'harian' && $isHoliday)
    <div class="alert alert-warning border-0 rounded-4 p-3 mb-3 d-flex align-items-center gap-3 shadow-xs">
        <i class='bx bx-calendar-exclamation fs-3 text-warning'></i>
        <div>
            <h6 class="fw-bold mb-0">Hari Ini Merupakan Hari Libur: {{ $holidayDesc }}</h6>
            <small class="text-muted">Presensi tidak diwajibkan untuk tanggal ini.</small>
        </div>
    </div>
    @endif

    <!-- Legenda Status: catatan kecil discreet di bawah filter -->
    @if($type !== 'harian')
    <div class="rekap-legend mb-3">
        <span class="rekap-legend-label">Keterangan:</span>
        <div class="rekap-legend-items">
            <span class="rekap-legend-item"><strong class="text-success">H</strong> Hadir</span>
            <span class="rekap-legend-item"><strong style="color: #d97706;">T</strong> Terlambat</span>
            <span class="rekap-legend-item"><strong class="text-primary">S</strong> Sakit</span>
            <span class="rekap-legend-item"><strong style="color: #7e22ce;">I</strong> Izin</span>
            <span class="rekap-legend-item"><strong style="color: #ef4444;">A</strong> Alpha</span>
            <span class="rekap-legend-item"><strong style="color: #64748b;">L</strong> Libur</span>
        </div>
    </div>
    @endif

    <!-- Tabel Data Sesuai Periode (Dukungan Horizontal Scroll Mulus & Benchmark Data Siswa) -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            @if($type === 'harian')
                <!-- TABEL REKAP HARIAN (SERAGAM DENGAN MINGGUAN & BULANAN) -->
                <table class="table table-hover align-middle mb-0 table-matrix table-zebra-custom text-nowrap">
                    <thead>
                        <tr class="align-middle">
                            <th style="width: 45px;">NO</th>
                            <th style="width: 85px;">NIS</th>
                            <th class="text-start" style="min-width: 170px;">Nama Siswa</th>
                            <th style="width: 80px;">Kelas</th>
                            <th style="width: 100px;">Jam Masuk</th>
                            <th style="width: 115px;">Keterlambatan</th>
                            <th style="width: 100px;">Status</th>
                            <th class="text-start" style="min-width: 140px;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataRows as $idx => $row)
                        @php
                            $st = $row['student'];
                            $status = $row['status'];
                        @endphp
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                            <td class="text-center text-secondary">{{ $idx + 1 }}</td>
                            <td>{{ $st->nis }}</td>
                            <td class="text-start text-truncate" style="max-width: 200px;">
                                <a href="{{ route('admin.students.show', $st->id) }}" class="text-decoration-none text-dark">
                                    {{ $st->name }}
                                </a>
                            </td>
                            <td>{{ $st->schoolClass ? $st->schoolClass->name : '-' }}</td>
                            <td class="text-center">
                                {{ $row['check_in'] }}
                            </td>
                            <td class="text-center">
                                @if($row['late_text'] === 'Tepat Waktu')
                                    <span class="text-success small">Tepat Waktu</span>
                                @elseif(str_starts_with($row['late_text'], '+'))
                                    <span class="text-warning-emphasis small">{{ $row['late_text'] }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($status === 'Hadir')
                                    <span class="cell-present" title="Hadir">Hadir</span>
                                @elseif($status === 'Terlambat')
                                    <span class="cell-late" title="Terlambat">Terlambat</span>
                                @elseif($status === 'Sakit')
                                    <span class="cell-sick" title="Sakit">Sakit</span>
                                @elseif($status === 'Izin')
                                    <span class="cell-permission" title="Izin">Izin</span>
                                @elseif($status === 'Alfa')
                                    <span class="cell-alpha" title="Alfa">Alfa</span>
                                @elseif($status === 'Libur')
                                    <span class="cell-holiday" title="Libur">Libur</span>
                                @else
                                    <span class="text-secondary">{{ $status }}</span>
                                @endif
                            </td>
                            <td class="text-start">
                                @if($row['proof_document'])
                                    <a href="{{ asset('storage/' . $row['proof_document']) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-2 me-1" style="font-size: 0.75rem;">
                                        <i class='bx bx-file'></i> Surat
                                    </a>
                                @endif
                                <span class="small text-muted">{{ $row['notes'] !== '-' ? $row['notes'] : '' }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary">
                                <i class='bx bx-info-circle fs-2 d-block mb-2 text-muted'></i>
                                Tidak ada data siswa atau presensi untuk tanggal dan filter yang dipilih.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            @elseif($type === 'mingguan')
                <!-- TABEL REKAP MINGGUAN (HORIZONTAL SCROLL MULUS) -->
                <table class="table table-hover align-middle mb-0 table-matrix table-zebra-custom text-nowrap">
                    <thead>
                        <tr class="align-middle">
                            <th style="width: 45px;">NO</th>
                            <th style="width: 85px;">NIS</th>
                            <th class="text-start" style="min-width: 170px;">Nama Siswa</th>
                            <th style="width: 80px;">Kelas</th>

                            <!-- Kolom Hari-Hari Mingguan -->
                            @foreach($dateColumns as $col)
                                <th class="{{ $col['is_holiday'] ? 'th-holiday' : '' }}" style="min-width: 65px;">
                                    {{ $col['label'] }}
                                </th>
                            @endforeach

                            <!-- Kolom Summary -->
                            <th style="width: 35px;" class="text-success" title="Total Hadir">H</th>
                            <th style="width: 35px; color: #d97706;" title="Total Terlambat">T</th>
                            <th style="width: 35px;" class="text-primary" title="Total Sakit">S</th>
                            <th style="width: 35px; color: #7e22ce;" title="Total Izin">I</th>
                            <th style="width: 35px; color: #ef4444;" title="Total Alpha">A</th>
                            <th style="width: 55px;" class="text-secondary" title="Persentase Kehadiran">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataRows as $index => $row)
                        @php
                            $student = $row['student'];
                            $days = $row['days'];
                        @endphp
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                            <td class="text-center text-secondary">{{ $index + 1 }}</td>
                            <td>{{ $student->nis }}</td>
                            <td class="text-start text-truncate" style="max-width: 200px;">
                                <a href="{{ route('admin.students.show', $student->id) }}" class="text-decoration-none text-dark">
                                    {{ $student->name }}
                                </a>
                            </td>
                            <td>{{ $student->schoolClass ? $student->schoolClass->name : '-' }}</td>

                            <!-- Dynamic Day Letters Tanpa Kotak -->
                            @foreach($dateColumns as $col)
                                @php $code = $days[$col['date']] ?? '-'; @endphp
                                <td>
                                    @if($code === 'H')
                                        <span class="cell-present" title="Hadir Tepat Waktu">H</span>
                                    @elseif($code === 'T')
                                        <span class="cell-late" title="Terlambat">T</span>
                                    @elseif($code === 'S')
                                        <span class="cell-sick" title="Sakit">S</span>
                                    @elseif($code === 'I')
                                        <span class="cell-permission" title="Izin">I</span>
                                    @elseif($code === 'A')
                                        <span class="cell-alpha" title="Alpha">A</span>
                                    @elseif($code === 'L')
                                        <span class="cell-holiday" title="Libur">L</span>
                                    @else
                                        <span class="cell-future">-</span>
                                    @endif
                                </td>
                            @endforeach

                            <!-- Counter H, T, S, I, A, % (Tanpa Background Box) -->
                            <td class="text-success fw-bold">{{ $row['hadir'] }}</td>
                            <td class="fw-bold" style="color: #d97706 !important;">{{ $row['terlambat'] }}</td>
                            <td class="text-primary fw-bold">{{ $row['sakit'] }}</td>
                            <td class="fw-bold" style="color: #7e22ce !important;">{{ $row['izin'] }}</td>
                            <td class="fw-bold" style="color: #ef4444 !important;">{{ $row['alfa'] }}</td>
                            <td class="text-dark fw-bold">{{ $row['percentage'] }}%</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ 4 + count($dateColumns) + 6 }}" class="text-center py-5 text-secondary">
                                Tidak ada data siswa untuk rentang tanggal dan kelas yang dipilih.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            @else
                <!-- TABEL REKAP BULANAN (HORIZONTAL SCROLL MULUS) -->
                <table class="table table-hover align-middle mb-0 table-matrix table-zebra-custom text-nowrap">
                    <thead>
                        <tr class="align-middle">
                            <th style="width: 45px;">NO</th>
                            <th style="width: 85px;">NIS</th>
                            <th class="text-start" style="min-width: 170px;">Nama Siswa</th>
                            <th style="width: 80px;">Kelas</th>
                            <th style="width: 35px;">JK</th>

                            <!-- Kolom Tanggal 1 - N -->
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $isHol = isset($holidayMap[$d]);
                                @endphp
                                <th class="{{ $isHol ? 'th-holiday' : '' }}" title="{{ $isHol ? $holidayMap[$d] : 'Tanggal ' . $d }}" style="width: 28px;">
                                    {{ $d }}
                                </th>
                            @endfor

                            <!-- Kolom Counter Summary -->
                            <th style="width: 35px;" class="text-success" title="Total Hadir">H</th>
                            <th style="width: 35px; color: #d97706;" title="Total Terlambat">T</th>
                            <th style="width: 35px;" class="text-primary" title="Total Sakit">S</th>
                            <th style="width: 35px; color: #7e22ce;" title="Total Izin">I</th>
                            <th style="width: 35px; color: #ef4444;" title="Total Alpha">A</th>
                            <th style="width: 55px;" class="text-secondary" title="Persentase Kehadiran">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataRows as $index => $row)
                        @php
                            $student = $row['student'];
                            $days = $row['days'];
                        @endphp
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                            <td class="text-center text-secondary">{{ $index + 1 }}</td>
                            <td>{{ $student->nis }}</td>
                            <td class="text-start text-truncate" style="max-width: 200px;">
                                <a href="{{ route('admin.students.show', $student->id) }}" class="text-decoration-none text-dark">
                                    {{ $student->name }}
                                </a>
                            </td>
                            <td>{{ $student->schoolClass ? $student->schoolClass->name : '-' }}</td>
                            <td>
                                <span class="small {{ $student->gender == 'Perempuan' ? 'text-pink' : 'text-primary' }}" style="{{ $student->gender == 'Perempuan' ? 'color:#ec4899;' : '' }}">
                                    {{ $student->gender == 'Perempuan' ? 'P' : 'L' }}
                                </span>
                            </td>

                            <!-- Tanggal 1 - N (Huruf Berwarna Murni Tanpa Kotak) -->
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php $code = $days[$d] ?? '-'; @endphp
                                <td>
                                    @if($code === 'H')
                                        <span class="cell-present" title="Hadir Tepat Waktu">H</span>
                                    @elseif($code === 'T')
                                        <span class="cell-late" title="Terlambat">T</span>
                                    @elseif($code === 'S')
                                        <span class="cell-sick" title="Sakit">S</span>
                                    @elseif($code === 'I')
                                        <span class="cell-permission" title="Izin">I</span>
                                    @elseif($code === 'A')
                                        <span class="cell-alpha" title="Alpha">A</span>
                                    @elseif($code === 'L')
                                        <span class="cell-holiday" title="Libur">L</span>
                                    @else
                                        <span class="cell-future">-</span>
                                    @endif
                                </td>
                            @endfor

                            <!-- Counter H, T, S, I, A, % (Tanpa Background Box) -->
                            <td class="text-success fw-bold">{{ $row['hadir'] }}</td>
                            <td class="fw-bold" style="color: #d97706 !important;">{{ $row['terlambat'] }}</td>
                            <td class="text-primary fw-bold">{{ $row['sakit'] }}</td>
                            <td class="fw-bold" style="color: #7e22ce !important;">{{ $row['izin'] }}</td>
                            <td class="fw-bold" style="color: #ef4444 !important;">{{ $row['alfa'] }}</td>
                            <td class="text-dark fw-bold">{{ $row['percentage'] }}%</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ 5 + $daysInMonth + 6 }}" class="text-center py-5 text-secondary">
                                Tidak ada data siswa atau presensi untuk bulan dan kelas yang dipilih.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
