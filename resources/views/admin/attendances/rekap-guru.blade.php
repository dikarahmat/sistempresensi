@extends('layouts.app')

@section('title', __('Rekap Presensi Guru'))
@section('page_title', __('Rekap Presensi Guru'))

@section('canvas_class', 'page-canvas-fixed')

{{-- Tombol "Mode Siswa" di header DIHAPUS.
     Pindah ke Rekap Siswa lewat dropdown Rekap di sidebar. --}}

@push('styles')
<style>
    /* Header Bar Alignment on Mobile (< 768px) */
    @media (max-width: 767.98px) {
        .recap-mobile-period { order: 0; }
        .recap-mobile-filters { order: 1; }
        .recap-mobile-actions { order: 2; flex-direction: column; gap: 0.5rem; }
        .recap-mobile-divider { display: none; }
        .recap-mobile-period .form-select,
        .recap-mobile-filters .form-control,
        .recap-mobile-filters .form-select {
            height: 36px; padding: 0.35rem 0.65rem; font-size: 0.84rem;
            color: #334155; background-color: #ffffff;
            border: 1px solid #d7dee8 !important; border-radius: var(--clean-radius) !important;
            box-shadow: none !important;
        }
        .recap-mobile-card .form-label {
            color: #64748b !important; font-size: 0.75rem; margin-bottom: 0.2rem !important;
        }
        .app-header-bar {
            flex-direction: row !important; align-items: center !important;
            justify-content: space-between !important; gap: 0.5rem !important;
            margin-bottom: 0.75rem !important;
        }
        .app-header-left { flex: 1 1 auto; min-width: 0; }
        .header-main-title {
            white-space: nowrap !important;
            overflow: hidden !important; text-overflow: ellipsis !important;
        }
        .header-main-subtitle { display: none !important; }
        .app-header-right { flex: 0 0 auto; margin-left: auto; }
    }

    /* Tombol Cetak PDF / Excel (tinggi 38px sejajar input filter) */
    .btn-green-excel,
    .btn-red-pdf {
        color: #ffffff !important;
        font-size: 0.82rem; font-weight: 600; border: none; border-radius: var(--clean-radius);
        display: inline-flex; align-items: center; justify-content: center;
        text-decoration: none; white-space: nowrap; flex-shrink: 0;
        text-transform: uppercase; letter-spacing: 0.03em; font-family: 'Poppins', 'Roboto', sans-serif;
        box-shadow: none;
        transition: none !important;
        transform: none !important;
        -webkit-tap-highlight-color: transparent;
        height: 38px;
        padding: 0 1rem;
    }
    .btn-green-excel { background-color: #059669; }
    .btn-red-pdf { background-color: #ef4444; }
    .btn-green-excel:hover, .btn-green-excel:active, .btn-green-excel:focus, .btn-green-excel:focus-visible { background-color: #059669 !important; color: #ffffff !important; transform: none !important; box-shadow: none !important; outline: none; }
    .btn-red-pdf:hover, .btn-red-pdf:active, .btn-red-pdf:focus, .btn-red-pdf:focus-visible { background-color: #ef4444 !important; color: #ffffff !important; transform: none !important; box-shadow: none !important; outline: none; }

    /* Tombol Reset: kotak 38x38 */
    .btn-reset-rekap {
        display: inline-flex; align-items: center; justify-content: center;
        box-sizing: border-box; flex-shrink: 0;
        width: 38px; height: 38px; padding: 0;
        background: #ffffff; border: 1px solid #cbd5e1; border-radius: var(--clean-radius, 6px);
        color: #64748b; text-decoration: none; outline: none;
        transition: none !important;
        -webkit-tap-highlight-color: transparent;
    }
    .btn-reset-rekap svg { width: 20px; height: 20px; stroke: currentColor; fill: none; }
    .btn-reset-rekap:hover,
    .btn-reset-rekap:focus-visible {
        color: #2563eb; border-color: #2563eb; background: #f8fafc; text-decoration: none;
    }

    .recap-mobile-actions .btn { transition: none !important; transform: none !important; }
    .recap-mobile-actions .btn-danger,
    .recap-mobile-actions .btn-danger:hover,
    .recap-mobile-actions .btn-danger:active,
    .recap-mobile-actions .btn-danger:focus,
    .recap-mobile-actions .btn-danger:focus-visible {
        background-color: #ef4444 !important; border-color: #ef4444 !important; color: #ffffff !important; box-shadow: none !important;
    }
    .recap-mobile-actions .btn-success,
    .recap-mobile-actions .btn-success:hover,
    .recap-mobile-actions .btn-success:active,
    .recap-mobile-actions .btn-success:focus,
    .recap-mobile-actions .btn-success:focus-visible {
        background-color: #059669 !important; border-color: #059669 !important; color: #ffffff !important; box-shadow: none !important;
    }

    /* Period Switcher Tabs */
    .period-container { display: flex; align-items: center; justify-content: flex-start; margin-bottom: 0.75rem; }
    .period-nav { display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: 10px; gap: 3px; }
    .period-link {
        display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.42rem 0.9rem;
        font-size: 0.82rem; font-weight: 500; color: #64748b; text-decoration: none;
        border-radius: 8px; background-color: transparent; transition: background-color 0.15s ease, color 0.15s ease;
        -webkit-tap-highlight-color: transparent;
    }
    @media (hover: hover) {
        .period-link:hover { color: #1e293b; background-color: #e2e8f0; }
    }
    .period-link:active { background-color: #cbd5e1; }
    .period-link.active {
        background-color: #2563eb !important; color: #ffffff !important; font-weight: 600;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }
    .period-link.active:hover,
    .period-link.active:active,
    .period-link.active:focus,
    .period-link.active:focus-visible {
        background-color: #2563eb !important; color: #ffffff !important;
    }
    .period-link:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }

    @media (max-width: 575.98px) {
        .period-container { width: 100%; }
        .period-nav { width: 100%; display: flex; }
        .period-link { flex: 1; justify-content: center; padding: 0.45rem 0.35rem; font-size: 0.8rem; text-align: center; }
    }

    .filter-card { background: transparent !important; border: none !important; margin-bottom: 1.25rem !important; }
    .filter-card > form { gap: 0.625rem !important; align-items: flex-end !important; }
    .recap-mobile-card .mb-2 { margin-bottom: 0.625rem !important; }

    .filter-label { font-size: 0.74rem; font-weight: 600; color: #64748b; margin-bottom: 0.25rem; display: block; }
    .filter-input {
        height: 38px; font-size: 0.82rem; border-radius: var(--clean-radius); border-color: #cbd5e1;
        background-color: #ffffff; padding: 0.35rem 0.65rem; transition: all 0.15s ease-in-out;
        border: 1px solid #cbd5e1;
    }
    .filter-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15); }

    /* Tables */
    .table-responsive { -webkit-overflow-scrolling: touch; overflow-x: auto; width: 100%; }
    .table-matrix { margin-bottom: 0; width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .table-matrix thead th {
        background-color: #f8fafc; color: #475569; font-weight: 600; padding: 0.75rem 0.5rem;
        border-bottom: 1.5px solid #edf2f7; text-align: center; vertical-align: middle; white-space: nowrap;
        text-transform: uppercase; letter-spacing: 0.03em; font-family: 'Poppins', 'Roboto', sans-serif;
    }
    .table-matrix thead th.th-holiday { background-color: #fee2e2 !important; color: #dc2626 !important; }
    .table-matrix tbody tr { border-bottom: 1px solid #f1f5f9; }
    .table-matrix tbody tr:nth-child(even) > td, .table-matrix tbody tr.baris-abu > td { background-color: #f1f5f9 !important; }
    .table-matrix tbody tr:nth-child(odd) > td, .table-matrix tbody tr.baris-putih > td { background-color: #ffffff !important; }
    .table-matrix tbody tr:hover > td { background-color: #e2e8f0 !important; }
    .table-matrix tbody td { padding: 0.7rem 0.5rem; text-align: center; vertical-align: middle; border-right: 1px solid #f1f5f9; color: #1e293b; white-space: nowrap; text-transform: uppercase; letter-spacing: 0.03em; font-family: 'Poppins', 'Roboto', sans-serif; font-weight: 400; overflow: hidden; text-overflow: ellipsis; }

    /* Kolom Nama Guru (kolom ke-3) rata kiri */
    .table-matrix th:nth-child(3),
    .table-matrix td:nth-child(3) { text-align: left !important; padding-left: 0.75rem; }

    .table-matrix .col-sum { width: 36px; }
    .table-matrix .col-pct { width: 64px; }
    .table-matrix thead th.th-sum,
    .table-matrix tbody td.td-sum { padding-left: 0.15rem; padding-right: 0.15rem; text-align: center; font-weight: 700; }
    .table-matrix tbody td.td-pct { text-align: center; font-weight: 700; font-size: 0.95rem; color: #212529; overflow: visible !important; text-overflow: clip !important; white-space: nowrap !important; }

    /* ===== WARNA KOLOM RINGKASAN (header + angka) =====
       Pakai ID + !important supaya tidak kalah dari CSS global yang
       memaksa warna thead th. Class dipasang di <th> DAN <span> di dalamnya. */
    #daftar-rekap .table-matrix .clr-h { color: #16a34a !important; }
    #daftar-rekap .table-matrix .clr-s { color: #2563eb !important; }
    #daftar-rekap .table-matrix .clr-i { color: #7e22ce !important; }
    #daftar-rekap .table-matrix .clr-a { color: #ef4444 !important; }
    #daftar-rekap .table-matrix .clr-pct { color: #212529 !important; }
    #daftar-rekap .table-matrix thead th.th-sum > span { font-weight: 700; }
    #daftar-rekap .table-matrix thead th.th-sum.clr-pct,
    #daftar-rekap .table-matrix thead th.th-sum.clr-pct > span { font-size: 0.95rem; }

    /* Status Colors */
    .cell-present { color: #16a34a !important; font-weight: 700; }
    .cell-sick { color: #2563eb !important; font-weight: 700; }
    .cell-permission { color: #7e22ce !important; font-weight: 700; }
    .cell-alpha { color: #ef4444 !important; font-weight: 700; }
    .cell-holiday { color: #64748b !important; font-weight: 600; }
    .cell-future { color: #cbd5e1 !important; font-weight: 400; }

    .rekap-legend {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.3rem 0.5rem;
        padding: 0.4rem 0.6rem; border: 1px dashed #e2e8f0; border-radius: 10px;
        background-color: #f8fafc; color: #64748b; font-size: 0.78rem;
    }
    .rekap-legend-label { font-weight: 700; color: #475569; text-transform: uppercase; font-size: 0.7rem; }
    .rekap-legend-items { display: flex; flex-wrap: wrap; align-items: center; gap: 0.3rem 0.6rem; }
    .rekap-legend-item { display: inline-flex; align-items: center; gap: 0.2rem; white-space: nowrap; font-size: 0.82rem; }
    .rekap-legend-item strong { font-size: 0.9rem; font-weight: 700; }

    .rekap-jk { font-size: 0.82rem; font-weight: 500; line-height: 1.2; }
    .rekap-jk.is-laki { color: #3B82F6 !important; font-weight: 500 !important; }
    .rekap-jk.is-perempuan { color: #EF4444 !important; font-weight: 500 !important; }

    #daftar-rekap { display: block; height: auto; min-height: 0; margin-bottom: 0 !important; }
    #daftar-rekap .table-responsive { max-height: none !important; overflow-y: visible !important; }
    #daftar-rekap .table-responsive > table > thead { z-index: 5 !important; }
    #daftar-rekap .table-responsive > table > thead th { position: sticky !important; top: 0 !important; z-index: 5 !important; }
    #daftar-rekap .table-responsive > table > tbody > tr > td { position: relative; z-index: 1; }

    .kehadiran-pagination {
        padding: 1.25rem 0.5rem 1.5rem; margin: 0; text-align: center;
        border-top: 1px solid #f1f5f9; background-color: #ffffff; width: 100%; box-sizing: border-box;
    }
    .kehadiran-pagination-list {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: center;
        gap: 0.15rem 1.1rem; list-style: none; margin: 0 0 0.35rem; padding: 0;
    }
    .kehadiran-pagination-step,
    .kehadiran-pagination-page {
        display: inline-block; padding: 0.45rem 0.3rem; font-size: 0.95rem; font-weight: 400; line-height: 1.2;
        text-decoration: none; background: none !important; border: none !important; box-shadow: none !important;
        border-radius: 0 !important; color: #64748b; cursor: pointer;
    }
    .kehadiran-pagination-page.is-active { color: #2563eb !important; font-weight: 700; border-bottom: 2px solid #2563eb !important; cursor: default; }
    .kehadiran-pagination-step:hover,
    .kehadiran-pagination-page:hover { color: #2563eb !important; text-decoration: underline; }
    .kehadiran-pagination-step:focus-visible,
    .kehadiran-pagination-page:focus-visible { outline: 2px solid #2563eb !important; outline-offset: 1px; border-radius: 0 !important; }
    .kehadiran-pagination-step.is-disabled { color: #cbd5e1; cursor: not-allowed; pointer-events: none; }
    .kehadiran-pagination-step.is-disabled:hover { color: #cbd5e1; text-decoration: none; }
    .kehadiran-pagination-ellipsis { display: inline-block; padding: 0.45rem 0.1rem; font-size: 0.95rem; color: #94a3b8; line-height: 1.2; }
    .kehadiran-pagination-info { margin: 0; font-size: 0.85rem; color: #64748b; letter-spacing: 0.02em; }

    /* Progress bar di dalam tombol Unduh */
    #printRekapSubmitBtn {
        position: relative; overflow: hidden; min-width: 0; white-space: nowrap; isolation: isolate;
        background-color: #2563eb !important; border-color: #2563eb !important; color: #fff !important;
    }
    #printRekapSubmitBtn.is-done { background-color: #16a34a !important; border-color: #16a34a !important; color: #fff !important; }
    #printRekapSubmitBtn.is-done .btn-progress__fill { display: none; }

    #printRekapModal .dl-actions > .btn { flex: 1 1 0; min-width: 0; white-space: nowrap; text-align: center; }
    #printRekapModal .dl-actions > .btn:disabled { opacity: 1; }
    #printRekapModal .dl-actions > .btn-light:disabled {
        background-color: var(--bs-btn-bg, #f8f9fa);
        border-color: var(--bs-btn-border-color, #dee2e6);
        color: var(--bs-btn-color, #212529);
    }

    /* Banner hari libur - kompak */
    .holiday-banner { padding: 0.5rem 0.85rem !important; margin-bottom: 0.75rem !important; font-size: 0.8rem; }
    .holiday-banner .text-warning { font-size: 1.15rem !important; }
    .holiday-banner .small { font-size: 0.78rem; line-height: 1.35; }
    .holiday-banner .text-muted.text-xs { font-size: 0.72rem; line-height: 1.3; }

    /* ===== MOBILE: tabel tidak tertumpuk, digeser horizontal, NO+NAMA sticky ===== */
    @media (max-width: 767.98px) {
        #daftar-rekap .table-responsive {
            overflow-x: auto !important;
            overflow-y: visible !important;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }
        #daftar-rekap .table-responsive > table {
            min-width: 1050px !important;
            width: auto !important;
        }
        #daftar-rekap .table-matrix tbody td,
        #daftar-rekap .table-matrix thead th {
            white-space: nowrap !important;
            padding: 0.6rem 0.7rem !important;
            font-size: 0.78rem !important;
        }
        /* Kolom NO sticky di kiri */
        #daftar-rekap .table-matrix tbody td:nth-child(1),
        #daftar-rekap .table-matrix thead th:nth-child(1) {
            position: sticky !important;
            left: 0 !important;
            z-index: 2 !important;
            background: #ffffff !important;
        }
        /* Kolom NAMA Guru sticky (setelah NO+NIP = 36+110=146px) */
        #daftar-rekap .table-matrix tbody td:nth-child(3),
        #daftar-rekap .table-matrix thead th:nth-child(3) {
            position: sticky !important;
            left: 146px !important;
            z-index: 2 !important;
            background: #ffffff !important;
        }
        .presence-summary { flex-wrap: wrap; gap: 0.6rem; }
        .presence-summary .item { white-space: nowrap; }
    }
</style>
@endpush

@section('content')
@php
    // Fallback: variabel modal diisi di sini kalau controller belum mengirimnya.
    $popupDate  = $popupDate  ?? ($date ?? date('Y-m-d'));
    $popupMonth = $popupMonth ?? ($month ?? date('n'));
    $popupYear  = $popupYear  ?? ($year ?? date('Y'));
    $academicYears = $academicYears ?? collect();
    $activeYear = $activeYear ?? null;

    // Baris data dari controller berupa array datar (nip, name, jk, ...); resolver ini juga menerima model Teacher.
    $resolveGuru = function ($item) {
        if (is_array($item)) {
            $g = $item['teacher'] ?? $item['guru'] ?? $item['model'] ?? null;
            if ($g) return $g;
            return isset($item['name']) ? (object) $item : null;
        }
        return $item;
    };
@endphp

    <!-- 1. MOBILE VIEW -->
    <div class="d-block d-md-none mb-3">
        <div class="recap-mobile-card">
            <form method="GET" action="{{ panel_route('rekap.guru') }}" class="recap-mobile-period mb-2">
                @foreach(request()->except(['type', 'page']) as $key => $value)
                    @if(is_scalar($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="mobileRecapType" class="form-label text-muted small fw-semibold mb-1">{{ __('Periode') }}</label>
                <select id="mobileRecapType" name="type" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3" onchange="debounceRekapFilterSubmit(this.form)">
                    <option value="harian" {{ $type === 'harian' ? 'selected' : '' }}>{{ __('Harian') }}</option>
                    <option value="mingguan" {{ $type === 'mingguan' ? 'selected' : '' }}>{{ __('Mingguan') }}</option>
                    <option value="bulanan" {{ $type === 'bulanan' ? 'selected' : '' }}>{{ __('Bulanan') }}</option>
                </select>
            </form>

            <hr class="border-secondary-subtle my-3 recap-mobile-divider">

            <form method="GET" action="{{ panel_route('rekap.guru') }}" class="m-0 recap-mobile-filters">
                <input type="hidden" name="type" value="{{ $type }}">
                @if($type === 'harian')
                    <div class="mb-2">
                        <input type="date" name="date" id="mobileRekapDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm border-secondary-subtle shadow-sm rounded-3" value="{{ $date ?? date('Y-m-d') }}">
                    </div>
                @elseif($type === 'mingguan')
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">{{ __('Tanggal Mulai') }}</label>
                            <input type="date" name="start_date" id="mobileRekapStartDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm border-secondary-subtle shadow-sm rounded-3" value="{{ $startDate }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">{{ __('Tanggal Selesai') }}</label>
                            <input type="date" name="end_date" id="mobileRekapEndDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm border-secondary-subtle shadow-sm rounded-3" value="{{ $endDate }}">
                        </div>
                    </div>
                @else
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">{{ __('Bulan') }}</label>
                            <select name="month" id="mobileRekapMonth" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate($year, $m, 1)->translatedFormat('F') }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">{{ __('Tahun') }}</label>
                            <select name="year" id="mobileRekapYear" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                                @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                @endif
            </form>

            <div class="d-flex flex-column gap-2 recap-mobile-actions mt-1">
                <button type="button" class="btn btn-danger btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-2 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#printRekapModal" data-format="pdf" title="{{ __('Cetak PDF') }}" aria-label="{{ __('Cetak PDF') }}">
                    <span class="text-nowrap" style="font-size: 0.8rem;">{{ __('CETAK PDF') }}</span>
                </button>
                <button type="button" class="btn btn-success btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-2 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#printRekapModal" data-format="excel" title="{{ __('Cetak Excel') }}" aria-label="{{ __('Cetak Excel') }}">
                    <span class="text-nowrap" style="font-size: 0.8rem;">{{ __('CETAK EXCEL') }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. DESKTOP VIEW -->
    <div class="d-none d-md-block">
        <div class="period-container">
            <div class="period-nav">
                <a href="{{ panel_route('rekap.guru', array_merge(request()->except(['type', 'page']), ['type' => 'harian'])) }}" class="period-link {{ $type === 'harian' ? 'active' : '' }}">
                    <i class='bx bx-calendar-event'></i> <span>{{ __('Harian') }}</span>
                </a>
                <a href="{{ panel_route('rekap.guru', array_merge(request()->except(['type', 'page']), ['type' => 'mingguan'])) }}" class="period-link {{ $type === 'mingguan' ? 'active' : '' }}">
                    <i class='bx bx-calendar-week'></i> <span>{{ __('Mingguan') }}</span>
                </a>
                <a href="{{ panel_route('rekap.guru', array_merge(request()->except(['type', 'page']), ['type' => 'bulanan'])) }}" class="period-link {{ $type === 'bulanan' ? 'active' : '' }}">
                    <i class='bx bx-calendar'></i> <span>{{ __('Bulanan') }}</span>
                </a>
            </div>
        </div>

        <div class="filter-card">
            <form method="GET" action="{{ panel_route('rekap.guru') }}" class="d-flex flex-wrap align-items-end gap-2">
                <input type="hidden" name="type" value="{{ $type }}">
                @if($type === 'harian')
                    <div class="d-flex flex-column" style="width: 180px;">
                        <label class="filter-label">{{ __('Tanggal') }}</label>
                        <input type="date" name="date" id="rekapDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm filter-input" value="{{ $date ?? date('Y-m-d') }}">
                    </div>
                @elseif($type === 'mingguan')
                    <div class="d-flex flex-column" style="width: 160px;">
                        <label class="filter-label">{{ __('Tanggal Mulai') }}</label>
                        <input type="date" name="start_date" id="rekapStartDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm filter-input" value="{{ $startDate }}">
                    </div>
                    <div class="d-flex flex-column" style="width: 160px;">
                        <label class="filter-label">{{ __('Tanggal Selesai') }}</label>
                        <input type="date" name="end_date" id="rekapEndDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm filter-input" value="{{ $endDate }}">
                    </div>
                @else
                    <div class="d-flex flex-column" style="width: 140px;">
                        <label class="filter-label">{{ __('Bulan') }}</label>
                        <select name="month" id="rekapMonth" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm filter-input">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate($year, $m, 1)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="d-flex flex-column" style="width: 100px;">
                        <label class="filter-label">{{ __('Tahun') }}</label>
                        <select name="year" id="rekapYear" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm filter-input">
                            @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                @endif

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn-red-pdf" data-bs-toggle="modal" data-bs-target="#printRekapModal" data-format="pdf" title="{{ __('Cetak PDF') }}" aria-label="{{ __('Cetak PDF') }}">
                        <span>{{ __('CETAK PDF') }}</span>
                    </button>
                    <button type="button" class="btn-green-excel" data-bs-toggle="modal" data-bs-target="#printRekapModal" data-format="excel" title="{{ __('Cetak Excel') }}" aria-label="{{ __('Cetak Excel') }}">
                        <span>{{ __('CETAK EXCEL') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($type === 'harian' && ($isHoliday ?? false))
    <div class="holiday-banner alert alert-warning border-0 rounded-3 mb-2 p-2 py-1 d-flex align-items-center gap-2">
        <i class='bx bx-calendar-exclamation text-warning' style="font-size: 1.15rem;"></i>
        <div>
            <div class="fw-bold text-dark text-nowrap small">{{ __('Hari Ini Merupakan Hari Libur:') }} {{ $holidayDesc ?? '' }}</div>
            <div class="text-muted text-xs">{{ __('Presensi tidak diwajibkan untuk tanggal ini.') }}</div>
        </div>
    </div>
    @endif

    @if($type !== 'harian')
    <div class="rekap-legend mb-3">
        <span class="rekap-legend-label">{{ __('Keterangan') }}:</span>
        <div class="rekap-legend-items">
            <span class="rekap-legend-item"><strong class="text-success">H</strong> {{ __('Hadir') }}</span>
            <span class="rekap-legend-item"><strong class="text-primary">S</strong> {{ __('Sakit') }}</span>
            <span class="rekap-legend-item"><strong style="color: #7e22ce;">I</strong> {{ __('Izin') }}</span>
            <span class="rekap-legend-item"><strong style="color: #ef4444;">A</strong> {{ __('Alfa') }}</span>
            <span class="rekap-legend-item"><strong style="color: #64748b;">L</strong> {{ __('Libur') }}</span>
            <span class="rekap-legend-item"><strong style="color: #94a3b8;">-</strong> {{ __('Belum') }}</span>
        </div>
    </div>
    @endif

    <!-- TABEL UTAMA -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden md:mb-4" id="daftar-rekap">
        <div class="table-responsive">
            @if($type === 'harian')
                <table class="table table-hover align-middle mb-0 table-matrix text-nowrap" style="width: 100%; table-layout: fixed;">
                    <thead>
                        <tr class="align-middle">
                            <th class="text-center" style="width: 6%;">{{ __('No') }}</th>
                            <th class="text-center" style="width: 18%;">{{ __('NIP') }}</th>
                            <th class="text-start" style="width: 34%;">{{ __('Nama Guru') }}</th>
                            <th class="text-center" style="width: 8%;">{{ __('JK') }}</th>
                            <th class="text-center" style="width: 16%;">{{ __('Jam Masuk') }}</th>
                            <th class="text-center" style="width: 18%;">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataRows as $idx => $item)
                        @php
                            $gr = $resolveGuru($item);
                            $status = is_array($item) ? ($item['status'] ?? '-') : 'Libur';
                            $checkIn = is_array($item) ? ($item['check_in'] ?? $item['jam_masuk'] ?? '-') : '-';
                            $rowNum = ($dataRows instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                ? ($dataRows->firstItem() + $loop->index)
                                : ($loop->iteration);
                        @endphp
                        @if($gr)
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                            <td class="text-center text-secondary">{{ $rowNum }}</td>
                            <td class="text-center">{{ ($gr->nip ?? null) ?: '-' }}</td>
                            <td class="text-start" title="{{ $gr->name }}">{{ $gr->name }}</td>
                            <td class="text-center">
                                <span class="rekap-jk {{ str_starts_with(strtoupper((string) ($gr->gender ?? $gr->jk ?? '')), 'P') ? 'is-perempuan' : 'is-laki' }}">
                                    {{ str_starts_with(strtoupper((string) ($gr->gender ?? $gr->jk ?? '')), 'P') ? 'P' : 'L' }}
                                </span>
                            </td>
                            <td class="text-center">{{ $checkIn }}</td>
                            <td class="text-center">
                                @if($status === 'Hadir') <span class="cell-present">{{ __('Hadir') }}</span>
                                @elseif($status === 'Sakit') <span class="cell-sick">{{ __('Sakit') }}</span>
                                @elseif($status === 'Izin') <span class="cell-permission">{{ __('Izin') }}</span>
                                @elseif($status === 'Alfa') <span class="cell-alpha">{{ __('Alfa') }}</span>
                                @elseif($status === 'Libur') <span class="cell-holiday">{{ __('Libur') }}</span>
                                @elseif(str_contains($status, 'Belum')) <span class="text-muted">-</span>
                                @else <span class="text-secondary">{{ $status }}</span> @endif
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary empty-state">
                                <i class='bx bx-error' aria-hidden='true'></i>
                                {{ __('Tidak ada data guru atau presensi untuk tanggal yang dipilih.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            @else
                @php
                    $isMingguan = $type === 'mingguan';
                    $jumlahHari = count($dateColumns);
                    $dateColWidth = $isMingguan ? '72px' : '28px';
                @endphp
                <table class="table table-hover align-middle mb-0 table-matrix text-nowrap" style="width: 100%; table-layout: fixed;">
                    <colgroup>
                        <col style="width: 36px;">
                        <col style="width: 110px;">
                        <col style="width: 240px;">
                        <col style="width: 36px;">
                        @for($dg = 0; $dg < $jumlahHari; $dg++) <col style="min-width: {{ $dateColWidth }};"> @endfor
                        <col style="width: 32px;">
                        <col style="width: 32px;">
                        <col style="width: 32px;">
                        <col style="width: 32px;">
                        <col style="width: 48px;">
                    </colgroup>
                    <thead>
                        <tr class="align-middle">
                            <th class="text-center">{{ __('No') }}</th>
                            <th class="text-center">{{ __('NIP') }}</th>
                            <th class="text-start">{{ __('Nama Guru') }}</th>
                            <th class="text-center">{{ __('JK') }}</th>
                            @foreach($dateColumns as $col)
                                <th class="text-center {{ $col['is_holiday'] ? 'th-holiday' : '' }}">{{ $col['label'] }}</th>
                            @endforeach
                            <th class="th-sum text-center clr-h"><span class="clr-h">H</span></th>
                            <th class="th-sum text-center clr-s"><span class="clr-s">S</span></th>
                            <th class="th-sum text-center clr-i"><span class="clr-i">I</span></th>
                            <th class="th-sum text-center clr-a"><span class="clr-a">A</span></th>
                            <th class="th-sum text-center clr-pct"><span class="clr-pct">%</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataRows as $index => $item)
                        @php
                            $guru = $resolveGuru($item);
                            $days = is_array($item) ? ($item['days'] ?? []) : [];
                            $rowNum = ($dataRows instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                ? ($dataRows->firstItem() + $loop->index)
                                : ($loop->iteration);
                        @endphp
                        @if($guru)
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                            <td class="text-center text-secondary">{{ $rowNum }}</td>
                            <td class="text-center">{{ ($guru->nip ?? null) ?: '-' }}</td>
                            <td class="text-start" title="{{ $guru->name }}">{{ $guru->name }}</td>
                            <td class="text-center">
                                <span class="rekap-jk {{ str_starts_with(strtoupper((string) ($guru->gender ?? $guru->jk ?? '')), 'P') ? 'is-perempuan' : 'is-laki' }}">
                                    {{ str_starts_with(strtoupper((string) ($guru->gender ?? $guru->jk ?? '')), 'P') ? 'P' : 'L' }}
                                </span>
                            </td>
                            @foreach($dateColumns as $col)
                                @php $code = $days[$col['date']] ?? '-'; @endphp
                                    <td class="text-center">
                                        @if($code === 'H') <span class="cell-present">H</span>
                                        @elseif($code === 'S') <span class="cell-sick">S</span>
                                        @elseif($code === 'I') <span class="cell-permission">I</span>
                                        @elseif($code === 'A') <span class="cell-alpha">A</span>
                                        @elseif($code === 'L') <span class="cell-holiday">L</span>
                                        @else <span class="cell-future">-</span> @endif
                                    </td>
                                @endforeach
                            <td class="td-sum text-center clr-h">{{ is_array($item) ? ($item['hadir'] ?? 0) : 0 }}</td>
                            <td class="td-sum text-center clr-s">{{ is_array($item) ? ($item['sakit'] ?? 0) : 0 }}</td>
                            <td class="td-sum text-center clr-i">{{ is_array($item) ? ($item['izin'] ?? 0) : 0 }}</td>
                            <td class="td-sum text-center clr-a">{{ is_array($item) ? ($item['alfa'] ?? 0) : 0 }}</td>
                            <td class="td-pct text-center">{{ is_array($item) ? ($item['percentage'] ?? 0) : 0 }}%</td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="{{ 4 + $jumlahHari + 5 }}" class="text-center py-5 text-secondary empty-state">
                                <i class='bx bx-error' aria-hidden='true'></i>
                                {{ $isMingguan
                                    ? __('Tidak ada data guru untuk rentang tanggal yang dipilih.')
                                    : __('Tidak ada data guru atau presensi untuk bulan yang dipilih.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif

        @php
            $pgPaginator = ($dataRows instanceof \Illuminate\Pagination\LengthAwarePaginator) ? $dataRows : null;
            $pgCurrent = $pgPaginator ? $pgPaginator->currentPage() : 1;
            $pgLast = $pgPaginator ? $pgPaginator->lastPage() : 1;
            $pgTotal = $pgPaginator ? $pgPaginator->total() : 0;
            $pgHash = '#daftar-rekap';

            $pgPages = [];
            if ($pgLast <= 7) {
                $pgPages = range(1, $pgLast);
            } else {
                $pgPages[] = 1;
                $pgStart = max(2, $pgCurrent - 1);
                $pgEnd = min($pgLast - 1, $pgCurrent + 1);
                if ($pgStart > 2) { $pgPages[] = '...'; }
                for ($i = $pgStart; $i <= $pgEnd; $i++) { $pgPages[] = $i; }
                if ($pgEnd < $pgLast - 1) { $pgPages[] = '...'; }
                $pgPages[] = $pgLast;
            }
        @endphp

        @if ($pgPaginator && $pgLast > 1)
            <nav class="kehadiran-pagination" id="rekap-pagination" aria-label="{{ __('Navigasi halaman rekap presensi') }}">
                <ul class="kehadiran-pagination-list">
                    @if ($pgCurrent <= 1)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">&lsaquo; {{ __('Sebelumnya') }}</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $pgPaginator->previousPageUrl() }}{{ $pgHash }}" rel="prev">&lsaquo; {{ __('Sebelumnya') }}</a></li>
                    @endif

                    @foreach ($pgPages as $pgItem)
                        @if ($pgItem === '...')
                            <li><span class="kehadiran-pagination-ellipsis" aria-hidden="true">&hellip;</span></li>
                        @elseif ($pgItem === $pgCurrent)
                            <li><span class="kehadiran-pagination-page is-active" aria-current="page">{{ $pgItem }}</span></li>
                        @else
                            <li><a class="kehadiran-pagination-page" href="{{ $pgPaginator->url($pgItem) }}{{ $pgHash }}">{{ $pgItem }}</a></li>
                        @endif
                    @endforeach

                    @if ($pgCurrent >= $pgLast)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">{{ __('Berikutnya') }} &rsaquo;</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $pgPaginator->nextPageUrl() }}{{ $pgHash }}" rel="next">{{ __('Berikutnya') }} &rsaquo;</a></li>
                    @endif
                </ul>

                <p class="kehadiran-pagination-info">
                    {{ __('Menampilkan') }} {{ $pgPaginator->firstItem() ?? 0 }}&ndash;{{ $pgPaginator->lastItem() ?? 0 }}
                    {{ __('dari') }} {{ $pgTotal }} {{ __('guru') }}
                </p>
            </nav>
        @endif
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            var rekapFilterTimer = null;
            window.debounceRekapFilterSubmit = function (form) {
                if (!form || !form.submit) return;
                if (rekapFilterTimer) { clearTimeout(rekapFilterTimer); }
                rekapFilterTimer = setTimeout(function () {
                    rekapFilterTimer = null;
                    form.submit();
                }, 250);
            };
        })();

        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('page')) {
                const el = document.getElementById('daftar-rekap');
                if (el) {
                    var scroller = el.closest('main');
                    scroller = scroller ? scroller.querySelector(':scope > .flex-1') : null;
                    var header = document.querySelector('.app-header-bar');
                    var offset = (header ? header.getBoundingClientRect().height : 0) + 12;
                    if (scroller) {
                        var rect = el.getBoundingClientRect();
                        var srect = scroller.getBoundingClientRect();
                        scroller.scrollTo({ top: scroller.scrollTop + (rect.top - srect.top) - offset, behavior: 'auto' });
                    } else {
                        el.scrollIntoView({ behavior: 'auto', block: 'start' });
                    }
                }
            }
        });

        window.rekapPrintFormat = 'pdf';

        const BULAN = [
            {!! json_encode(__('Januari')) !!}, {!! json_encode(__('Februari')) !!}, {!! json_encode(__('Maret')) !!},
            {!! json_encode(__('April')) !!}, {!! json_encode(__('Mei')) !!}, {!! json_encode(__('Juni')) !!},
            {!! json_encode(__('Juli')) !!}, {!! json_encode(__('Agustus')) !!}, {!! json_encode(__('September')) !!},
            {!! json_encode(__('Oktober')) !!}, {!! json_encode(__('November')) !!}, {!! json_encode(__('Desember')) !!}
        ];

        function parseYmd(v) {
            if (!v) return null;
            const p = String(v).split('-');
            if (p.length !== 3) return null;
            const d = new Date(+p[0], +p[1] - 1, +p[2]);
            return isNaN(d.getTime()) ? null : d;
        }
        function fmtTanggal(d) {
            return String(d.getDate()).padStart(2,'0') + ' ' + BULAN[d.getMonth()] + ' ' + d.getFullYear();
        }
        function activePeriodDate() {
            const el = document.querySelector('#periodFields [data-role="period-date"]:not([disabled])');
            return el ? el.value : null;
        }
        function activeSelect(name) {
            const el = document.querySelector('#periodFields select[name="' + name + '"]:not([disabled])');
            return el ? el.value : null;
        }

        function computeRekapRange() {
            const period = document.getElementById('rekapPeriodType')?.value;
            if (period === 'harian') {
                const d = parseYmd(activePeriodDate());
                return d ? { text: fmtTanggal(d), days: 1 } : null;
            }
            if (period === 'mingguan') {
                const d = parseYmd(activePeriodDate());
                if (!d) return null;
                const start = new Date(d); start.setDate(d.getDate() - ((d.getDay() + 6) % 7));
                const end = new Date(start); end.setDate(start.getDate() + 6);
                return {
                    text: String(start.getDate()).padStart(2,'0') + ' ' + BULAN[start.getMonth()] + ' - '
                        + String(end.getDate()).padStart(2,'0') + ' ' + BULAN[end.getMonth()] + ' ' + end.getFullYear(),
                    days: 7
                };
            }
            if (period === 'bulanan') {
                const m = parseInt(activeSelect('period_month') || '', 10);
                const y = parseInt(activeSelect('period_year') || '', 10);
                if (!m || !y) return null;
                return { text: BULAN[m - 1] + ' ' + y, days: new Date(y, m, 0).getDate() };
            }
            if (period === 'semester' || period === 'tahunan') {
                const sel = document.querySelector('#periodFields select[name="period_academic_year"]:not([disabled])');
                const opt = sel && sel.options[sel.selectedIndex];
                return { text: (opt ? opt.textContent.trim() : '-') + (period === 'semester' ? '' : ' (' + {!! json_encode(__('1 tahun ajaran')) !!} + ')'), days: 0 };
            }
            return null;
        }

        function updateRekapInfoText() {
            const format = window.rekapPrintFormat || document.getElementById('printFormat')?.value || 'pdf';
            const formatLabel = (format === 'excel') ? 'Excel (.xlsx)' : 'PDF';
            const infoText = document.getElementById('rekapInfoText');
            if (!infoText) return;

            const descText = document.getElementById('rekapInfoDesc');
            if (descText) {
                descText.textContent = format === 'excel'
                    ? {!! json_encode(__('Pilih opsi cetak rekap presensi guru. Hasil akan diunduh sebagai file Excel.')) !!}
                    : {!! json_encode(__('Pilih opsi cetak rekap presensi guru. Hasil akan diunduh sebagai file PDF.')) !!};
            }

            const range = computeRekapRange();
            const rangeText = range ? range.text : '-';
            infoText.textContent = {!! json_encode(__('Semua Guru')) !!} + ' • ' + rangeText + ' • ' + {!! json_encode(__('Format:')) !!} + ' ' + formatLabel;
        }

        function toggleRekapPeriodFields() {
            const period = document.getElementById('rekapPeriodType').value;
            const fields = ['periodHarian', 'periodMingguan', 'periodBulanan', 'periodSemester', 'periodTahunan'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                const active = (id === 'period' + period.charAt(0).toUpperCase() + period.slice(1));
                el.style.display = active ? 'block' : 'none';
                el.querySelectorAll('input, select, textarea').forEach(ctrl => { ctrl.disabled = !active; });
            });
            updateRekapInfoText();
        }

        function setRekapPrintFormatFromButton(btn) {
            const f = btn?.getAttribute('data-format');
            window.rekapPrintFormat = (f === 'excel') ? 'excel' : 'pdf';
            const hidden = document.getElementById('printFormat');
            if (hidden) hidden.value = window.rekapPrintFormat;
            updateRekapInfoText();
        }

        function rekapTutupPopup() {
            const modal = document.getElementById('printRekapModal');
            if (!modal) return;
            try {
                if (window.bootstrap && window.bootstrap.Modal) {
                    const inst = window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal);
                    inst.hide();
                } else {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                }
            } catch (e) {}
        }

        function rekapcsrf() {
            const el = document.querySelector('input[name="_token"]');
            return el ? el.value : '';
        }

        function rekapResetTombol() {
            const btn = document.getElementById('printRekapSubmitBtn');
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('is-running', 'is-done');
                btn.style.backgroundColor = '';
                btn.style.borderColor = '';
                btn.removeAttribute('data-mode');
                btn.removeAttribute('data-dl-persen');
            }
            const label = document.getElementById('rekapBtnLabel');
            if (label) label.textContent = {!! json_encode(__('Unduh')) !!};
            const fill = document.getElementById('rekapBtnFill');
            if (fill) fill.style.width = '0%';
            const cancel = document.getElementById('rekapCancelBtn');
            if (cancel) {
                cancel.textContent = {!! json_encode(__('Batal')) !!};
                cancel.disabled = false;
                cancel.setAttribute('data-bs-dismiss', 'modal');
            }
        }

        (function () {
            const btn   = document.getElementById('printRekapSubmitBtn');
            const batal = document.getElementById('rekapCancelBtn');
            const modal = document.getElementById('printRekapModal');
            if (!btn || !batal || !modal) return;

            function keModeBatal() {
                batal.textContent = {!! json_encode(__('Batal')) !!};
                batal.disabled = false;
                batal.setAttribute('data-bs-dismiss', 'modal');
            }

            function batalkanSemua() {
                if (typeof window.dlProgressAbort === 'function') window.dlProgressAbort();
                keModeBatal();
                rekapTutupPopup();
            }

            btn.addEventListener('dl-busy-start', function () {
                batal.textContent = {!! json_encode(__('Hentikan')) !!};
                batal.removeAttribute('data-bs-dismiss');
            });
            btn.addEventListener('dl-busy-end', keModeBatal);

            batal.addEventListener('click', function (e) {
                if (batal.textContent !== {!! json_encode(__('Hentikan')) !!}) return;
                e.preventDefault();
                e.stopPropagation();
                batalkanSemua();
            }, true);

            modal.addEventListener('hide.bs.modal', function () {
                if (typeof window.dlProgressBusy === 'function' && window.dlProgressBusy()) batalkanSemua();
            });
        })();

        document.getElementById('printRekapForm')?.addEventListener('submit', function (e) {
            const formatInput = document.getElementById('printFormat');
            if (formatInput) formatInput.value = (window.rekapPrintFormat === 'excel') ? 'excel' : 'pdf';

            if (!computeRekapRange()) {
                e.preventDefault();
                alert({!! json_encode(__('Lengkapi field periode terlebih dahulu.')) !!});
                return;
            }

            const submitBtn = document.getElementById('printRekapSubmitBtn');
            if (typeof window.downloadWithProgress !== 'function') return;

            e.preventDefault();
            const body = new FormData(e.target);
            window.downloadWithProgress(submitBtn, @json(panel_route('rekap.guru.print')), {
                method: 'POST',
                body: body,
                csrf: rekapcsrf()
            }).then(function (ok) {
                if (ok) rekapTutupPopup();
            });
        });

        document.getElementById('printRekapModal')?.addEventListener('show.bs.modal', function (event) {
            document.body.classList.add('rekap-modal-instant');
            setRekapPrintFormatFromButton(event.relatedTarget);

            const currentType = {!! json_encode($type) !!};
            const periodMap = { harian: 'harian', mingguan: 'mingguan', bulanan: 'bulanan' };
            const periodSelect = document.getElementById('rekapPeriodType');
            if (periodSelect) periodSelect.value = periodMap[currentType] || 'bulanan';

            toggleRekapPeriodFields();

            const today = new Date();
            const bulan = String(today.getMonth() + 1).padStart(2, '0');
            const tahun = String(today.getFullYear());
            const bulanan = document.getElementById('periodBulanan');
            const bulanSel = bulanan ? bulanan.querySelector('select[name="period_month"]') : null;
            const tahunSel = bulanan ? bulanan.querySelector('select[name="period_year"]') : null;
            if (bulanSel) bulanSel.value = String(parseInt(bulan, 10));
            if (tahunSel) tahunSel.value = tahun;
            const tgl = today.getFullYear() + '-' + bulan + '-' + String(today.getDate()).padStart(2, '0');
            document.querySelectorAll('#periodHarian [data-role="period-date"], #periodMingguan [data-role="period-date"]').forEach(function (el) { el.value = tgl; });

            updateRekapInfoText();
        });

        document.getElementById('printRekapModal')?.addEventListener('hidden.bs.modal', function () {
            document.body.classList.remove('rekap-modal-instant');
            rekapResetTombol();
            if (typeof window.dlProgressReset === 'function') window.dlProgressReset();
        });

        document.addEventListener('DOMContentLoaded', function () {
            rekapResetTombol();
            if (typeof window.dlProgressReset === 'function') window.dlProgressReset();

            document.querySelectorAll('[data-bs-target="#printRekapModal"]').forEach(function (btn) {
                btn.addEventListener('click', function () { setRekapPrintFormatFromButton(btn); });
            });

            const periodScope = document.getElementById('periodFields');
            if (periodScope) {
                periodScope.addEventListener('change', updateRekapInfoText);
                periodScope.addEventListener('input', updateRekapInfoText);
            }
            updateRekapInfoText();
        });
    </script>
    @endpush

<style>
    #printRekapModal,
    #printRekapModal .modal-dialog { transition: none !important; animation: none !important; }
    body.rekap-modal-instant .modal-backdrop,
    body.rekap-modal-instant .modal-backdrop.fade { transition: none !important; animation: none !important; }
</style>

<!-- MODAL CETAK REKAP PRESENSI GURU -->
<div class="modal" id="printRekapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">{{ __('Cetak Rekap Presensi Guru') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ panel_route('rekap.guru.print') }}" method="POST" id="printRekapForm" data-no-download>
                @csrf
                <input type="hidden" name="format" id="printFormat" value="pdf">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="print_mode" value="teacher">

                <div class="modal-body py-3">
                    <p class="text-secondary small mb-3" id="rekapInfoDesc">
                        {{ __('Pilih opsi cetak rekap presensi guru. Hasil akan diunduh sebagai file PDF atau Excel.') }}
                    </p>

                    <!-- Jenis Cetak (guru: hanya satu) -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Jenis Cetak') }} <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="print_scope" id="printScopeAllTeachers" value="all" checked>
                            <label class="form-check-label fw-medium small" for="printScopeAllTeachers">
                                {{ __('Cetak Semua Guru (Rekap Tabel)') }}
                            </label>
                        </div>
                    </div>

                    <!-- Periode -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Periode') }} <span class="text-danger">*</span></label>
                        <select name="period_type" id="rekapPeriodType" class="form-select form-select-sm rounded-3" onchange="toggleRekapPeriodFields()">
                            <option value="harian">{{ __('Harian') }}</option>
                            <option value="mingguan">{{ __('Mingguan') }}</option>
                            <option value="bulanan">{{ __('Bulanan') }}</option>
                            <option value="semester">{{ __('Semester') }}</option>
                            <option value="tahunan">{{ __('Setahun (Tahun Ajaran)') }}</option>
                        </select>
                    </div>

                    <!-- Periode Fields Dinamis -->
                    <div id="periodFields">
                        <div class="mb-3" id="periodHarian" style="display: none;">
                            <label class="form-label small fw-semibold">{{ __('Tanggal') }}</label>
                            <input type="date" name="period_date" data-role="period-date" class="form-control form-control-sm rounded-3" value="{{ $popupDate }}">
                        </div>
                        <div class="mb-3" id="periodMingguan" style="display: none;">
                            <label class="form-label small fw-semibold">{{ __('Tanggal dalam minggu ini') }}</label>
                            <input type="date" name="period_date" data-role="period-date" class="form-control form-control-sm rounded-3" value="{{ $popupDate }}">
                            <div class="form-text small">{{ __('Rentang dicetak: Senin sampai Minggu dari tanggal tersebut.') }}</div>
                        </div>
                        <div class="mb-3" id="periodBulanan" style="display: none;">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">{{ __('Bulan') }}</label>
                                    <select name="period_month" class="form-select form-select-sm rounded-3">
                                        @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ (int) $popupMonth === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate((int) $popupYear, $m, 1)->translatedFormat('F') }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">{{ __('Tahun') }}</label>
                                    <select name="period_year" class="form-select form-select-sm rounded-3">
                                        @for($y = (int) $popupYear - 1; $y <= (int) $popupYear + 2; $y++)
                                        <option value="{{ $y }}" {{ (int) $popupYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3" id="periodSemester" style="display: none;">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">{{ __('Semester') }}</label>
                                    <select name="period_semester" class="form-select form-select-sm rounded-3">
                                        <option value="ganjil">{{ __('Ganjil') }}</option>
                                        <option value="genap">{{ __('Genap') }}</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">{{ __('Tahun Ajaran') }}</label>
                                    <select name="period_academic_year" class="form-select form-select-sm rounded-3">
                                        @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ (int) ($activeYear->id ?? 0) === (int) $ay->id ? 'selected' : '' }}>{{ $ay->name }} ({{ $ay->semester }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3" id="periodTahunan" style="display: none;">
                            <label class="form-label small fw-semibold">{{ __('Tahun Ajaran') }}</label>
                            <select name="period_academic_year" class="form-select form-select-sm rounded-3">
                                @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ (int) ($activeYear->id ?? 0) === (int) $ay->id ? 'selected' : '' }}>{{ $ay->name }} ({{ $ay->semester }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-2" id="rekapPrintInfoBox">
                        <i class='bx bx-info-circle me-1'></i> <span id="rekapInfoText">{{ __('Pilih opsi cetak di atas untuk melihat ringkasan hasil.') }}</span>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <div class="dl-actions">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal" id="rekapCancelBtn">{{ __('Batal') }}</button>
                        <button type="submit" class="btn-download-blue btn-progress px-3" data-download formaction="{{ panel_route('rekap.guru.print') }}" data-url="{{ panel_route('rekap.guru.print') }}" id="printRekapSubmitBtn">
                            <span class="btn-progress__fill" id="rekapBtnFill" aria-hidden="true"></span>
                            <span class="btn-progress__label" id="rekapBtnLabel">{{ __('Unduh') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection