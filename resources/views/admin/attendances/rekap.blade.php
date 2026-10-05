@extends('layouts.app')

@section('title', 'Rekap Presensi Siswa')
@section('page_title', 'Rekap Presensi Siswa')
@section('page_subtitle', 'Kelola rekapitulasi kehadiran siswa harian, mingguan, dan bulanan.')

{{-- Kanvas dikunci setinggi satu layar; kartu tabel mengisi sisa tinggi kanvas
     sehingga tabelnya (bukan halaman) yang menggulir. Class ini diatur di layout
     bersama, sama seperti halaman acuan. --}}
@section('canvas_class', 'page-canvas-fixed')

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

    /* Buttons Export Responsif */
    .btn-green-excel {
        background-color: #059669; color: #ffffff !important;
        font-size: 0.82rem; font-weight: 600; border: none; border-radius: var(--clean-radius);
        min-width: 160px; height: 40px; padding: 0 1.25rem; display: inline-flex;
        align-items: center; justify-content: center; text-decoration: none;
        white-space: nowrap; box-shadow: 0 1px 2px rgba(5, 150, 105, 0.2);
        transition: all 0.15s ease-in-out; flex-shrink: 0;
        text-transform: uppercase; letter-spacing: 0.03em; font-family: 'Poppins', 'Roboto', sans-serif;
    }
    .btn-green-excel:hover { background-color: #047857; color: #ffffff !important; transform: translateY(-1px); }

    .btn-red-pdf {
        background-color: #ef4444; color: #ffffff !important;
        font-size: 0.82rem; font-weight: 600; border: none; border-radius: var(--clean-radius);
        min-width: 160px; height: 40px; padding: 0 1.25rem; display: inline-flex;
        align-items: center; justify-content: center; text-decoration: none;
        white-space: nowrap; box-shadow: 0 1px 2px rgba(239, 68, 68, 0.2);
        transition: all 0.15s ease-in-out; flex-shrink: 0;
        text-transform: uppercase; letter-spacing: 0.03em; font-family: 'Poppins', 'Roboto', sans-serif;
    }
    .btn-red-pdf:hover { background-color: #dc2626; color: #ffffff !important; transform: translateY(-1px); }

    @media (min-width: 768px) {
        .btn-green-excel, .btn-red-pdf { min-width: 180px; height: 40px; padding: 0 1.5rem; }
    }

    /* Period Switcher Tabs */
    .period-container { display: flex; align-items: center; justify-content: flex-start; margin-bottom: 0.75rem; }
    .period-nav { display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: 10px; gap: 3px; }
    .period-link {
        display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.42rem 0.9rem;
        font-size: 0.82rem; font-weight: 500; color: #64748b; text-decoration: none;
        border-radius: 8px; background-color: transparent; transition: all 0.15s ease;
    }
    .period-link:hover { color: #1e293b; background-color: #e2e8f0; }
    .period-link.active {
        background-color: #2563eb; color: #ffffff; font-weight: 600;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }

    @media (max-width: 575.98px) {
        .period-container { width: 100%; }
        .period-nav { width: 100%; display: flex; }
        .period-link { flex: 1; justify-content: center; padding: 0.45rem 0.35rem; font-size: 0.8rem; text-align: center; }
    }

    .filter-card { background: transparent !important; border: none !important; margin-bottom: 1.25rem !important; }

    /* ===== REVISI CLEAN LOOK: jarak antar field filter =====
       - Desktop: form memakai utilitas .gap-2 (8px); dinaikkan jadi 10px
         supaya rapat tapi tidak rapat sekali (target 10-12px).
       - Mobile: kelompok field memakai utilitas .mb-2 (8px); dinaikkan 10px.
       Hanya JARAK yang diubah - urutan field, label, border, dan fungsinya
       (onchange submit) tidak disentuh. */
    .filter-card > form {
        gap: 0.625rem !important;
    }
    .recap-mobile-card .mb-2 {
        margin-bottom: 0.625rem !important;
    }

    .filter-label { font-size: 0.74rem; font-weight: 600; color: #64748b; margin-bottom: 0.25rem; display: block; }
    .filter-input {
        height: 40px; font-size: 0.82rem; border-radius: var(--clean-radius); border-color: #cbd5e1;
        background-color: #ffffff; padding: 0.35rem 0.65rem; transition: all 0.15s ease-in-out;
        border: 1px solid #cbd5e1;
    }
    .filter-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15); }
    .filter-btn { height: 36px; font-size: 0.82rem; border-radius: var(--clean-radius); display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; font-weight: 600; }

    /* Tables */
    .table-responsive { -webkit-overflow-scrolling: touch; overflow-x: auto; width: 100%; }
    .table-matrix { margin-bottom: 0; width: 100%; min-width: 800px; border-collapse: collapse; font-size: 0.82rem; }
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
    .table-matrix tbody td { padding: 0.7rem 0.5rem; text-align: center; vertical-align: middle; border-right: 1px solid #f1f5f9; color: #1e293b; white-space: nowrap; text-transform: uppercase; letter-spacing: 0.03em; font-family: 'Poppins', 'Roboto', sans-serif; font-weight: 400; }

    /* Status Colors */
    .cell-present { color: #16a34a !important; font-weight: 700; }
    .cell-late { color: #d97706 !important; font-weight: 700; }
    .cell-sick { color: #2563eb !important; font-weight: 700; }
    .cell-permission { color: #7e22ce !important; font-weight: 700; }
    .cell-alpha { color: #ef4444 !important; font-weight: 700; }
    .cell-holiday { color: #64748b !important; font-weight: 600; }
    .cell-future { color: #cbd5e1 !important; font-weight: 400; }

    /* ===== LEGENDA/KETERANGAN: ringkas tapi tetap jelas =====
       Nilai disatukan dari dua blok lama (dasar + "poin 2: diperbesar") karena
       di HP 390px legenda melipat jadi 4-5 baris & terlihat gemuk. Yang dijaga:
       warna, border putus-putus, latar, huruf Keterangan tetap sama; hanya
       padding, gap, dan ukuran huruf diturunkan sedikit supaya muat 2-3 baris. */
    .rekap-legend {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.3rem 0.5rem;
        padding: 0.4rem 0.6rem; border: 1px dashed #e2e8f0; border-radius: 10px;
        background-color: #f8fafc; color: #64748b; font-size: 0.78rem;
    }
    .rekap-legend-label { font-weight: 700; color: #475569; text-transform: uppercase; font-size: 0.7rem; }
    .rekap-legend-items { display: flex; flex-wrap: wrap; align-items: center; gap: 0.3rem 0.6rem; }
    .rekap-legend-item { display: inline-flex; align-items: center; gap: 0.2rem; white-space: nowrap; font-size: 0.82rem; }
    .rekap-legend-item strong { font-size: 0.9rem; font-weight: 700; }

    /* ===== JK (poin 1): warna cerah, huruf medium (bukan bold) =====
       L = biru cerah (#3B82F6), P = merah cerah (#EF4444),
       font-weight 500, ukuran 0.82rem — PERSIS SAMA dengan teks kolom
       tabel lainnya supaya tidak terlihat "menonjol"/tegas berlebihan.

       Kenapa !important tetap dipakai di sini (hanya pada .rekap-jk):
         - .table-matrix tbody td menetapkan `color` (#1e293b);
         - .table-hover Bootstrap 5.3 menimpa `color` pada <td> saat hover;
         - baris abu-abu/hover hanya mengubah background, bukan warna teks.
       Karena span ini punya deklarasi `color` sendiri, nilainya selalu menang
       atas pewarisan (inheritance) dari <td> — di hover, di baris abu-abu,
       maupun di baris putih, jadi warnanya tidak pernah pudar/tertimpa.
       TIDAK ada `opacity` di sini: tidak ada aturan lain yang membuat
       L/P terlihat pucat, jadi opacity hanya perlu dihapus, bukan ditambahkan. */
    .rekap-jk {
        font-size: 0.82rem;           /* 13px, sama dengan teks tabel lain */
        font-weight: 500;
        line-height: 1.2;
    }
    .rekap-jk.is-laki {
        color: #3B82F6 !important;    /* biru cerah */
        font-weight: 500 !important;
    }
    .rekap-jk.is-perempuan {
        color: #EF4444 !important;    /* merah cerah */
        font-weight: 500 !important;
    }

    /* ======================================================================
       LAYOUT KARTU REKAP (poin 7)
       Pola sama dengan Presensi & Catatan Kehadiran: kartu tabel mengisi sisa
       tinggi layar, hanya .table-responsive yang jadi area scroll (scrollbar
       kanan + header sticky), dan pagination berada DI DALAM area scroll tepat
       di bawah baris ke-100.
       ====================================================================== */
    #daftar-rekap {
        display: block;
        height: auto;
        min-height: 0;
        margin-bottom: 0 !important;
    }

    #daftar-rekap .table-responsive {
        max-height: none !important;
        overflow-y: visible !important;
    }

    /* Header sticky bersih: latar pekat & selalu di atas baris data. */
    #daftar-rekap .table-responsive > table > thead { z-index: 5 !important; }
    #daftar-rekap .table-responsive > table > thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 5 !important;
    }
    #daftar-rekap .table-responsive > table > tbody > tr > td {
        position: relative;
        z-index: 1;
    }

    /* ======================================================================
       PAGINASI REKAP (poin 7)
       NILAI & GAYA PERSIS SAMA dengan Catatan Kehadiran (kelas .kehadiran-pagination*).
       Sengaja memakai nama kelas yang sama supaya tidak ada gaya baru:
       block CSS di bawah merupakan SALINAN nilai yang sama, karena CSS Catatan
       Kehadiran berada di blok push styles milik view lain dan tidak bisa
       dipakai lintas halaman tanpa menyentuh file yang dilarang sesi ini.
       (Catatan: teks "push styles" sengaja ditulis tanpa tanda @ karena
        directive Blade harus ditutup, bukan disebut di dalam komentar CSS.)
       ====================================================================== */
    .kehadiran-pagination {
        padding: 1.25rem 0.5rem 1.5rem;
        margin: 0;
        text-align: center;
        border-top: 1px solid #f1f5f9;
        background-color: #ffffff;
        width: 100%;
        box-sizing: border-box;
    }

    .kehadiran-pagination-list {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 0.15rem 1.1rem;
        list-style: none;
        margin: 0 0 0.35rem;
        padding: 0;
    }

    .kehadiran-pagination-step,
    .kehadiran-pagination-page {
        display: inline-block;
        padding: 0.45rem 0.3rem;
        font-size: 0.95rem;
        font-weight: 400;
        line-height: 1.2;
        text-decoration: none;
        background: none !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        color: #64748b;
        cursor: pointer;
    }

    .kehadiran-pagination-page.is-active {
        color: #2563eb !important;
        font-weight: 700;
        border-bottom: 2px solid #2563eb !important;
        cursor: default;
    }

    .kehadiran-pagination-step:hover,
    .kehadiran-pagination-page:hover {
        color: #2563eb !important;
        text-decoration: underline;
    }

    .kehadiran-pagination-step:focus-visible,
    .kehadiran-pagination-page:focus-visible {
        outline: 2px solid #2563eb !important;
        outline-offset: 1px;
        border-radius: 0 !important;
    }

    .kehadiran-pagination-step.is-disabled {
        color: #cbd5e1;
        cursor: not-allowed;
        pointer-events: none;
    }

    .kehadiran-pagination-step.is-disabled:hover {
        color: #cbd5e1;
        text-decoration: none;
    }

    .kehadiran-pagination-ellipsis {
        display: inline-block;
        padding: 0.45rem 0.1rem;
        font-size: 0.95rem;
        color: #94a3b8;
        line-height: 1.2;
    }

    .kehadiran-pagination-info {
        margin: 0;
        font-size: 0.85rem;
        color: #64748b;
        letter-spacing: 0.02em;
    }

    /* Gaya Pagination Disamakan Persis dengan Data Siswa */
    .pagination-compact .pagination {
        margin-bottom: 0;
        font-size: 0.82rem;
    }
    .pagination-compact .page-link {
        padding: 0.35rem 0.65rem;
        border-radius: 6px !important;
        font-family: 'Poppins', 'Roboto', sans-serif;
    }
    @media (max-width: 767.98px) {
        .pagination-compact .pagination {
            justify-content: center !important;
            flex-wrap: wrap;
            gap: 2px;
        }
    }
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

    <!-- 1. MOBILE VIEW -->
    <div class="d-block d-md-none mb-3">
        {{-- DULU: <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white recap-mobile-card">
             REVISI CLEAN LOOK: kontainer/card pembungkus filter DIHAPUS (border,
             latar, shadow, radius, padding). Filter kini menempel langsung di
             kanvas putih tanpa indent bekas kontainer - persis pola filter di
             halaman Data Siswa/Guru/Kelas. Border INPUT/DROPDOWN/TOMBOL sendiri
             tetap. class .recap-mobile-card dipertahankan sebagai jangkar CSS. --}}
        <div class="recap-mobile-card">
            <form method="GET" action="{{ panel_route('rekap') }}" class="recap-mobile-period mb-2">
                @foreach(request()->except(['type', 'page']) as $key => $value)
                    @if(is_scalar($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="mobileRecapType" class="form-label text-muted small fw-semibold mb-1">Periode</label>
                <select id="mobileRecapType" name="type" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3" onchange="debounceRekapFilterSubmit(this.form)">
                    <option value="harian" {{ $type === 'harian' ? 'selected' : '' }}>Harian</option>
                    <option value="mingguan" {{ $type === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                    <option value="bulanan" {{ $type === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                </select>
            </form>

            <div class="d-flex flex-column gap-2 recap-mobile-actions">
                <a href="{{ panel_route('rekap.export-excel', $exportParams) }}"
                   class="btn btn-success btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-2 px-2 fw-semibold" data-download>
                    <span class="text-nowrap" style="font-size: 0.8rem;">Ekspor Excel</span>
                </a>
                <a href="{{ panel_route('rekap.export-pdf', $exportParams) }}"
                   class="btn btn-danger btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-2 px-2 fw-semibold" data-download>
                    <span class="text-nowrap" style="font-size: 0.8rem;">PDF Report</span>
                </a>
            </div>

            <hr class="border-secondary-subtle my-3 recap-mobile-divider">

            <form method="GET" action="{{ panel_route('rekap') }}" class="m-0 recap-mobile-filters">
                <input type="hidden" name="type" value="{{ $type }}">
                @if($type === 'harian')
                    <div class="mb-2">
                        <label class="form-label text-muted small fw-semibold mb-1">Tanggal Presensi</label>
                        <input type="date" name="date" id="mobileRekapDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm border-secondary-subtle shadow-sm rounded-3" value="{{ $date ?? date('Y-m-d') }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted small fw-semibold mb-1">Pilih Kelas</label>
                        <select name="class_id" id="mobileRekapClassId" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>Kelas {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif($type === 'mingguan')
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="mobileRekapStartDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm border-secondary-subtle shadow-sm rounded-3" value="{{ $startDate }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Tanggal Selesai</label>
                            <input type="date" name="end_date" id="mobileRekapEndDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm border-secondary-subtle shadow-sm rounded-3" value="{{ $endDate }}">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted small fw-semibold mb-1">Pilih Kelas</label>
                        <select name="class_id" id="mobileRekapClassId" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>Kelas {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Bulan</label>
                            <select name="month" id="mobileRekapMonth" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate($year, $m, 1)->translatedFormat('F') }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold mb-1">Tahun</label>
                            <select name="year" id="mobileRekapYear" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                                @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted small fw-semibold mb-1">Pilih Kelas</label>
                        <select name="class_id" id="mobileRekapClassId" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm border-secondary-subtle shadow-sm rounded-3">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>Kelas {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <!-- 2. DESKTOP VIEW -->
    <div class="d-none d-md-block">
        <div class="period-container">
            <div class="period-nav">
                <a href="{{ panel_route('rekap', array_merge(request()->except(['type', 'page']), ['type' => 'harian'])) }}" class="period-link {{ $type === 'harian' ? 'active' : '' }}">
                    <i class='bx bx-calendar-event'></i> <span>Harian</span>
                </a>
                <a href="{{ panel_route('rekap', array_merge(request()->except(['type', 'page']), ['type' => 'mingguan'])) }}" class="period-link {{ $type === 'mingguan' ? 'active' : '' }}">
                    <i class='bx bx-calendar-week'></i> <span>Mingguan</span>
                </a>
                <a href="{{ panel_route('rekap', array_merge(request()->except(['type', 'page']), ['type' => 'bulanan'])) }}" class="period-link {{ $type === 'bulanan' ? 'active' : '' }}">
                    <i class='bx bx-calendar'></i> <span>Bulanan</span>
                </a>
            </div>
        </div>

        <div class="filter-card">
            <form method="GET" action="{{ panel_route('rekap') }}" class="d-flex flex-wrap align-items-center gap-2">
                <input type="hidden" name="type" value="{{ $type }}">
                @if($type === 'harian')
                    <div class="d-flex flex-column" style="width: 180px;">
                        <label class="filter-label">Tanggal Presensi</label>
                        <input type="date" name="date" id="rekapDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm filter-input" value="{{ $date ?? date('Y-m-d') }}">
                    </div>
                @elseif($type === 'mingguan')
                    <div class="d-flex flex-column" style="width: 160px;">
                        <label class="filter-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="rekapStartDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm filter-input" value="{{ $startDate }}">
                    </div>
                    <div class="d-flex flex-column" style="width: 160px;">
                        <label class="filter-label">Tanggal Selesai</label>
                        <input type="date" name="end_date" id="rekapEndDate" onchange="debounceRekapFilterSubmit(this.form)" class="form-control form-control-sm filter-input" value="{{ $endDate }}">
                    </div>
                @else
                    <div class="d-flex flex-column" style="width: 140px;">
                        <label class="filter-label">Bulan</label>
                        <select name="month" id="rekapMonth" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm filter-input">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate($year, $m, 1)->translatedFormat('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="d-flex flex-column" style="width: 100px;">
                        <label class="filter-label">Tahun</label>
                        <select name="year" id="rekapYear" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm filter-input">
                            @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                @endif
                <div class="d-flex flex-column" style="min-width: 200px; flex: 1;">
                    <label class="filter-label">Pilih Kelas</label>
                    <select name="class_id" id="rekapClassId" onchange="debounceRekapFilterSubmit(this.form)" class="form-select form-select-sm filter-input rounded-3">
                        <option value="">Semua Kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>Kelas {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto" style="margin-top: 22px;">
                    <a href="{{ panel_route('rekap.export-excel', $exportParams) }}" class="btn-green-excel" title="Export Excel (.xlsx)" aria-label="Export Excel (.xlsx)" data-download>
                        <span>Ekspor Excel</span>
                    </a>
                    <a href="{{ panel_route('rekap.export-pdf', $exportParams) }}" class="btn-red-pdf" title="Cetak PDF Report" aria-label="Cetak PDF Report" data-download>
                        <span>PDF Report</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if($type === 'harian' && $isHoliday)
    <div class="alert alert-warning border-0 rounded-4 p-3 mb-3 d-flex align-items-center gap-3 shadow-xs">
        <i class='bx bx-calendar-exclamation fs-3 text-warning'></i>
        <div>
            <h6 class="fw-bold mb-0">Hari Ini Merupakan Hari Libur: {{ $holidayDesc }}</h6>
            <small class="text-muted">Presensi tidak diwajibkan untuk tanggal ini.</small>
        </div>
    </div>
    @endif

    @if($type !== 'harian')
    <div class="rekap-legend mb-3">
        <span class="rekap-legend-label">Keterangan:</span>
        <div class="rekap-legend-items">
            <span class="rekap-legend-item"><strong class="text-success">H</strong> Hadir</span>
            <span class="rekap-legend-item"><strong style="color: #d97706;">T</strong> Terlambat</span>
            <span class="rekap-legend-item"><strong class="text-primary">S</strong> Sakit</span>
            <span class="rekap-legend-item"><strong style="color: #7e22ce;">I</strong> Izin</span>
            <span class="rekap-legend-item"><strong style="color: #ef4444;">A</strong> Alfa</span>
            <span class="rekap-legend-item"><strong style="color: #64748b;">L</strong> Libur</span>
            <span class="rekap-legend-item"><strong style="color: #94a3b8;">-</strong> Belum Hadir</span>
        </div>
    </div>
    @endif

    <!-- TABEL UTAMA -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" id="daftar-rekap">
        <div class="table-responsive">
            @if($type === 'harian')
                <table class="table table-hover align-middle mb-0 table-matrix text-nowrap">
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
                        @forelse($dataRows as $idx => $item)
                        @php
                            $st = is_array($item) ? ($item['student'] ?? null) : $item;
                            $status = is_array($item) ? ($item['status'] ?? '-') : 'Libur';
                            $checkIn = is_array($item) ? ($item['check_in'] ?? '-') : '-';
                            $lateText = is_array($item) ? ($item['late_text'] ?? '-') : '-';
                            $proofDoc = is_array($item) ? ($item['proof_document'] ?? null) : null;
                            $notes = is_array($item) ? ($item['notes'] ?? '-') : '-';
                            $rowNum = ($dataRows instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                ? ($dataRows->firstItem() + $loop->index)
                                : ($loop->iteration);
                        @endphp
                        @if($st)
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                            <td class="text-center text-secondary">{{ $rowNum }}</td>
                            <td>{{ $st->nis }}</td>
                            <td class="text-start text-truncate" style="max-width: 200px;">
                                <a href="{{ panel_route('students.show', $st->id) }}" class="text-decoration-none text-dark">{{ $st->name }}</a>
                            </td>
                            <td>{{ $st->schoolClass ? $st->schoolClass->name : '-' }}</td>
                            <td class="text-center">{{ $checkIn }}</td>
                            <td class="text-center">
                                @if($lateText === 'Tepat Waktu')
                                    <span class="text-success small">Tepat Waktu</span>
                                @elseif($status === 'Terlambat')
                                    {{-- Format ramah dari rekap_format_late_minutes(): "15 MNT" / "1 JAM 5 MNT" --}}
                                    <span class="text-warning-emphasis small fw-semibold">{{ $lateText }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($status === 'Hadir') <span class="cell-present">Hadir</span>
                                @elseif($status === 'Terlambat') <span class="cell-late">Terlambat</span>
                                @elseif($status === 'Sakit') <span class="cell-sick">Sakit</span>
                                @elseif($status === 'Izin') <span class="cell-permission">Izin</span>
                                @elseif($status === 'Alpha') <span class="cell-alpha">Alpha</span>
                                @elseif($status === 'Libur') <span class="cell-holiday">Libur</span>
                                @else <span class="text-secondary">{{ $status }}</span> @endif
                            </td>
                            <td class="text-start">
                                @if(!empty($proofDoc))
                                    <a href="{{ asset('storage/' . $proofDoc) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-2 me-1" style="font-size: 0.75rem;">Surat</a>
                                @endif
                                <span class="small text-muted">{{ $notes !== '-' ? $notes : '' }}</span>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary empty-state">
                                <i class='bx bx-error' aria-hidden='true'></i>
                                Tidak ada data siswa atau presensi untuk tanggal dan filter yang dipilih.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            @elseif($type === 'mingguan')
                <table class="table table-hover align-middle mb-0 table-matrix text-nowrap">
                    <thead>
                        <tr class="align-middle">
                            <th style="width: 45px;">NO</th>
                            <th style="width: 85px;">NIS</th>
                            <th class="text-start" style="min-width: 170px;">Nama Siswa</th>
                            <th style="width: 80px;">Kelas</th>
                            @foreach($dateColumns as $col)
                                <th class="{{ $col['is_holiday'] ? 'th-holiday' : '' }}" style="min-width: 65px;">{{ $col['label'] }}</th>
                            @endforeach
                            <th style="width: 35px;" class="text-success">H</th>
                            <th style="width: 35px; color: #d97706;">T</th>
                            <th style="width: 35px;" class="text-primary">S</th>
                            <th style="width: 35px; color: #7e22ce;">I</th>
                            <th style="width: 35px; color: #ef4444;">A</th>
                            <th style="width: 55px;" class="text-secondary">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataRows as $index => $item)
                        @php
                            $student = is_array($item) ? ($item['student'] ?? null) : $item;
                            $days = is_array($item) ? ($item['days'] ?? []) : [];
                            $rowNum = ($dataRows instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                ? ($dataRows->firstItem() + $loop->index)
                                : ($loop->iteration);
                        @endphp
                        @if($student)
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                            <td class="text-center text-secondary">{{ $rowNum }}</td>
                            <td>{{ $student->nis }}</td>
                            <td class="text-start text-truncate" style="max-width: 200px;">
                                <a href="{{ panel_route('students.show', $student->id) }}" class="text-decoration-none text-dark">{{ $student->name }}</a>
                            </td>
                            <td>{{ $student->schoolClass ? $student->schoolClass->name : '-' }}</td>
                            @foreach($dateColumns as $col)
                                @php $code = $days[$col['date']] ?? '-'; @endphp
                                <td>
                                    @if($code === 'H') <span class="cell-present">H</span>
                                    @elseif($code === 'T') <span class="cell-late">T</span>
                                    @elseif($code === 'S') <span class="cell-sick">S</span>
                                    @elseif($code === 'I') <span class="cell-permission">I</span>
                                    @elseif($code === 'A') <span class="cell-alpha">A</span>
                                    @elseif($code === 'L') <span class="cell-holiday">L</span>
                                    @else <span class="cell-future">-</span> @endif
                                </td>
                            @endforeach
                            <td class="text-success fw-bold">{{ is_array($item) ? ($item['hadir'] ?? 0) : 0 }}</td>
                            <td class="fw-bold" style="color: #d97706 !important;">{{ is_array($item) ? ($item['terlambat'] ?? 0) : 0 }}</td>
                            <td class="text-primary fw-bold">{{ is_array($item) ? ($item['sakit'] ?? 0) : 0 }}</td>
                            <td class="fw-bold" style="color: #7e22ce !important;">{{ is_array($item) ? ($item['izin'] ?? 0) : 0 }}</td>
                            <td class="fw-bold" style="color: #ef4444 !important;">{{ is_array($item) ? ($item['alfa'] ?? 0) : 0 }}</td>
                            <td class="text-dark fw-bold">{{ is_array($item) ? ($item['percentage'] ?? 0) : 0 }}%</td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="{{ 4 + count($dateColumns) + 6 }}" class="text-center py-5 text-secondary empty-state">
                                <i class='bx bx-error' aria-hidden='true'></i>
                                Tidak ada data siswa untuk rentang tanggal dan kelas yang dipilih.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            @else
                <table class="table table-hover align-middle mb-0 table-matrix text-nowrap">
                    <thead>
                        <tr class="align-middle">
                            <th style="width: 45px;">NO</th>
                            <th style="width: 85px;">NIS</th>
                            <th class="text-start" style="min-width: 170px;">Nama Siswa</th>
                            <th style="width: 80px;">Kelas</th>
                            <th style="width: 35px;">JK</th>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php $isHol = isset($holidayMap[$d]); @endphp
                                <th class="{{ $isHol ? 'th-holiday' : '' }}" style="width: 28px;">{{ $d }}</th>
                            @endfor
                            <th style="width: 35px;" class="text-success">H</th>
                            <th style="width: 35px; color: #d97706;">T</th>
                            <th style="width: 35px;" class="text-primary">S</th>
                            <th style="width: 35px; color: #7e22ce;">I</th>
                            <th style="width: 35px; color: #ef4444;">A</th>
                            <th style="width: 55px;" class="text-secondary">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataRows as $index => $item)
                        @php
                            $student = is_array($item) ? ($item['student'] ?? null) : $item;
                            $days = is_array($item) ? ($item['days'] ?? []) : [];
                            $rowNum = ($dataRows instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                ? ($dataRows->firstItem() + $loop->index)
                                : ($loop->iteration);
                        @endphp
                        @if($student)
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                            <td class="text-center text-secondary">{{ $rowNum }}</td>
                            <td>{{ $student->nis }}</td>
                            <td class="text-start text-truncate" style="max-width: 200px;">
                                <a href="{{ panel_route('students.show', $student->id) }}" class="text-decoration-none text-dark">{{ $student->name }}</a>
                            </td>
                            <td>{{ $student->schoolClass ? $student->schoolClass->name : '-' }}</td>
                            <td class="text-center">
                                {{-- JK: L biru cerah, P merah cerah, huruf medium (bukan bold). --}}
                                <span class="rekap-jk {{ optional($student)->gender == 'Perempuan' ? 'is-perempuan' : 'is-laki' }}">
                                    {{ optional($student)->gender == 'Perempuan' ? 'P' : 'L' }}
                                </span>
                            </td>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php $code = $days[$d] ?? '-'; @endphp
                                <td>
                                    @if($code === 'H') <span class="cell-present">H</span>
                                    @elseif($code === 'T') <span class="cell-late">T</span>
                                    @elseif($code === 'S') <span class="cell-sick">S</span>
                                    @elseif($code === 'I') <span class="cell-permission">I</span>
                                    @elseif($code === 'A') <span class="cell-alpha">A</span>
                                    @elseif($code === 'L') <span class="cell-holiday">L</span>
                                    @else <span class="cell-future">-</span> @endif
                                </td>
                            @endfor
                            <td class="text-success fw-bold">{{ is_array($item) ? ($item['hadir'] ?? 0) : 0 }}</td>
                            <td class="fw-bold" style="color: #d97706 !important;">{{ is_array($item) ? ($item['terlambat'] ?? 0) : 0 }}</td>
                            <td class="text-primary fw-bold">{{ is_array($item) ? ($item['sakit'] ?? 0) : 0 }}</td>
                            <td class="fw-bold" style="color: #7e22ce !important;">{{ is_array($item) ? ($item['izin'] ?? 0) : 0 }}</td>
                            <td class="fw-bold" style="color: #ef4444 !important;">{{ is_array($item) ? ($item['alfa'] ?? 0) : 0 }}</td>
                            <td class="text-dark fw-bold">{{ is_array($item) ? ($item['percentage'] ?? 0) : 0 }}%</td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="{{ 5 + $daysInMonth + 6 }}" class="text-center py-5 text-secondary empty-state">
                                <i class='bx bx-error' aria-hidden='true'></i>
                                Tidak ada data siswa atau presensi untuk bulan dan kelas yang dipilih.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif

        <!-- ==========================================================================
             PAGINASI REKAP - TEKS MURNI (TANPA KONTAINER)
             Gaya PERSIS SAMA dengan Catatan Kehadiran (kelas .kehadiran-pagination*).
             POSISI: DI DALAM .table-responsive, tepat di bawah baris ke-100, jadi
             ikut ter-scroll dan baru terlihat setelah user scroll ke baris terakhir.
             ====================================================================== -->
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
            <nav class="kehadiran-pagination" id="rekap-pagination" aria-label="Navigasi halaman rekap presensi">
                <ul class="kehadiran-pagination-list">
                    @if ($pgCurrent <= 1)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">&lsaquo; Sebelumnya</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $pgPaginator->previousPageUrl() }}{{ $pgHash }}" rel="prev">&lsaquo; Sebelumnya</a></li>
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
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">Berikutnya &rsaquo;</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $pgPaginator->nextPageUrl() }}{{ $pgHash }}" rel="next">Berikutnya &rsaquo;</a></li>
                    @endif
                </ul>

                <p class="kehadiran-pagination-info">
                    Menampilkan {{ $pgPaginator->firstItem() ?? 0 }}&ndash;{{ $pgPaginator->lastItem() ?? 0 }}
                    dari {{ $pgTotal }} siswa
                </p>
            </nav>
        @endif
        </div><!-- /.table-responsive : pagination ikut di dalam area scroll -->
    </div>

    @push('scripts')
    <script>
        /* Filter otomatis Rekap: debounce 250ms supaya ganti beberapa
           dropdown berturut-turut hanya memicu SATU submit form GET yang
           sama persis seperti tombol kaca pembesar sebelumnya (parameter
           identik). Pakai event change (bukan input/keyup) sehingga navigasi
           panah keyboard tidak memicu reload; submit hanya saat pilihan
           dikomit (klik/Enter/blur). Loader global tampil otomatis. */
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
                    // Scroll INSTAN (tanpa animasi) di dalam area scroll halaman.
                    // scrollIntoView smooth sebelumnya memicu animasi scroll yang
                    // menggerakkan header sticky/address-bar mobile berulang
                    // sehingga navbar tampak berkedip/lompat tiap ganti halaman.
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
    </script>
    @endpush
@endsection