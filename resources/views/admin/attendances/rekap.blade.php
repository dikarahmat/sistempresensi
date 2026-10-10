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

    /* ===== PROGRES DI DALAM TOMBOL UTAMA =====
       Memakai komponen bersama (.btn-progress / .btn-progress__fill) supaya
       tampilan identik dengan tombol unduhan lain: lapisan isian mewarisi
       warna tombol lalu digelapkan (brightness .82), transisi width .4s ease,
       tanpa spinner dan tanpa shimmer. Persen = kelas selesai / total. */
    #printRekapSubmitBtn {
        position: relative;
        overflow: hidden;
        min-width: 168px;
        white-space: nowrap;
        isolation: isolate;
    }
    /* Selesai semua: tombol hijau sesaat, teks "Selesai" tanpa ikon. */
    #printRekapSubmitBtn.is-done { background-color: #16a34a; border-color: #16a34a; color: #fff; }
    #printRekapSubmitBtn.is-done .btn-progress__fill { display: none; }

    /* Footer: dua tombol sama lebar, lebar tetap, mengisi baris penuh.
       Batal/Hentikan tidak pernah mengecil dan tidak jadi abu gelap. */
    #printRekapModal .dl-actions > .btn {
        flex: 1 1 168px;
        min-width: 168px;
        white-space: nowrap;
        text-align: center;
    }
    #printRekapModal .dl-actions > .btn:disabled { opacity: 1; }
    #printRekapModal .dl-actions > .btn-light:disabled {
        background-color: var(--bs-btn-bg, #f8f9fa);
        border-color: var(--bs-btn-border-color, #dee2e6);
        color: var(--bs-btn-color, #212529);
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

            <div class="d-flex flex-column gap-2 recap-mobile-actions mt-1">
                <button type="button" class="btn btn-danger btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-2 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#printRekapModal" data-format="pdf" title="Cetak PDF" aria-label="Cetak PDF">
                    <span class="text-nowrap" style="font-size: 0.8rem;">CETAK PDF</span>
                </button>
                <button type="button" class="btn btn-success btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-2 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#printRekapModal" data-format="excel" title="Cetak Excel" aria-label="Cetak Excel">
                    <span class="text-nowrap" style="font-size: 0.8rem;">CETAK EXCEL</span>
                </button>
            </div>
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
                    <button type="button" class="btn-red-pdf" data-bs-toggle="modal" data-bs-target="#printRekapModal" data-format="pdf" title="Cetak PDF" aria-label="Cetak PDF">
                        <span>CETAK PDF</span>
                    </button>
                    <button type="button" class="btn-green-excel" data-bs-toggle="modal" data-bs-target="#printRekapModal" data-format="excel" title="Cetak Excel" aria-label="Cetak Excel">
                        <span>CETAK EXCEL</span>
                    </button>
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
                            <th style="width: 85px;">NISN</th>
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
                            <td>{{ $st->nisn ?: '-' }}</td>
                            <td class="text-start text-truncate" style="max-width: 200px;">
                                {{ $st->name }}
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
                            <th style="width: 85px;">NISN</th>
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
                            <td>{{ $student->nisn ?: '-' }}</td>
                            <td class="text-start text-truncate" style="max-width: 200px;">
                                {{ $student->name }}
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
                            <th style="width: 85px;">NISN</th>
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
                            <td>{{ $student->nisn ?: '-' }}</td>
                            <td class="text-start text-truncate" style="max-width: 200px;">
                                {{ $student->name }}
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
    {{-- JSZip (bundel Vite, bukan CDN) hanya untuk popup cetak "Semua Kelas". --}}
    @vite(['resources/js/rekap-print-zip.js'])
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

        // ==================== MODAL CETAK REKAP PRESENSI ====================
        // Data kelas dikirim server ke popup (sumber: $classes + $siswaPerKelas).
        window.REKAP_KELAS_CETAK = @json($kelasCetak ?? []);
        window.REKAP_SISWA_PER_KELAS = @json($siswaPerKelas ?? []);
        window.REKAP_SISWA_TOTAL = @json($siswaTotal ?? null);

        /** Kelas yang benar-benar punya siswa aktif (kelas kosong dilewati). */
        function rekapKelasDenganSiswa() {
            return (window.REKAP_KELAS_CETAK || []).filter(function (k) {
                return Number(k.jml) > 0;
            });
        }

        // Utility: update info box text
        function updateRekapInfoText() {
            const mode = document.querySelector('input[name="print_mode"]:checked')?.value;
            const scope = document.querySelector('input[name="student_scope"]:checked')?.value;
            const format = window.rekapPrintFormat || document.getElementById('printFormat')?.value || 'pdf';
            const formatLabel = (format === 'excel') ? 'Excel (.xlsx)' : 'PDF';
            const infoText = document.getElementById('rekapInfoText');
            if (!infoText) return;

            const range = computeRekapRange();
            const rangeText = range ? range.text : '-';

            let jumlahSiswa = null;
            if (mode === 'student') {
                if (scope === 'single') jumlahSiswa = 1;
                else if (scope === 'class') jumlahSiswa = (window.REKAP_SISWA_PER_KELAS || {})[document.getElementById('rekapScopeClassId')?.value] ?? null;
                else jumlahSiswa = window.REKAP_SISWA_TOTAL ?? null;
            }

            let text = '';
            if (mode === 'class') {
                text = 'Per Kelas';
                text += ' • ' + rangeText;
                text += format === 'pdf'
                    ? ' • PDF: tiap kelas di halaman baru.'
                    : ' • Excel: 1 sheet per kelas.';
                text += ' • Format: ' + formatLabel;
            } else {
                let kelasTxt = '';
                if (scope === 'class') {
                    const sel = document.getElementById('rekapScopeClassId');
                    const opt = sel && sel.options[sel.selectedIndex];
                    kelasTxt = (opt ? opt.textContent.trim() : '-');
                }

                if (scope === 'classes') {
                    // "Semua Kelas": satu file per kelas, jumlah kelas yang punya siswa.
                    text = 'Semua Kelas • ' + rangeText
                        + ' • ' + rekapKelasDenganSiswa().length + ' kelas'
                        + ' • ' + formatLabel + ' (satu file per kelas)';
                    infoText.textContent = text;
                    return;
                }

                const label = scope === 'single' ? 'Satu Siswa' : 'Satu Kelas';
                text = label + kelasTxt + ' • ' + rangeText;
                if (jumlahSiswa !== null && jumlahSiswa !== undefined) {
                    text += ' • ' + jumlahSiswa + ' siswa';
                }
                text += ' • ' + formatLabel;
                if (format === 'excel') {
                    text += ' (1 sheet per siswa)';
                } else {
                    text += ' (1 halaman per siswa)';
                }
            }
            infoText.textContent = text;
        }

        // ==========================================================
        // SATU FUNGSI PENYELENAI: seluruh tampilan popup mengikuti
        // radio yang sedang aktif. Dipanggil dari onchange radio
        // (toggleRekapPrintScope / toggleRekapStudentScope) maupun
        // dari handler buka popup, sehingga tidak ada lagi kondisi
        // tampilan yang "nyangkut" dari mode sebelumnya.
        // ==========================================================

        /** Kosongkan pencarian siswa: input, siswa terpilih, dropdown, pesan. */
        function rekapResetPencarianSiswa() {
            const input = document.getElementById('rekapStudentSearch');
            if (input) input.value = '';

            const terpilih = document.getElementById('rekapStudentId');
            if (terpilih) terpilih.value = '';

            const hasil = document.getElementById('studentSearchResults');
            if (hasil) {
                hasil.innerHTML = '';
                hasil.style.display = 'none';
            }

            const bantuan = document.getElementById('studentSearchHelp');
            if (bantuan) {
                bantuan.textContent = 'Ketik minimal 2 karakter untuk mencari siswa';
                bantuan.classList.remove('text-danger');
            }
        }

        function syncModeUI() {
            const modeSiswa = !!document.getElementById('printModeStudent')?.checked;
            const scope = document.querySelector('input[name="student_scope"]:checked')?.value;

            const studentScopeContainer = document.getElementById('studentScopeContainer');
            const classScopeContainer = document.getElementById('classScopeContainer');
            const singleStudentContainer = document.getElementById('singleStudentContainer');

            const scopeSingle = modeSiswa && scope === 'single';
            const scopeClass = modeSiswa && scope === 'class';

            // "Cari Siswa" (dan input + teks bantuannya) HANYA tampil pada
            // mode Per Siswa + cakupan Satu Siswa. Mode Per Kelas dan
            // cakupan lain wajib tersembunyi penuh, bukan sekadar dikosongkan.
            if (studentScopeContainer) studentScopeContainer.style.display = modeSiswa ? 'block' : 'none';
            if (singleStudentContainer) singleStudentContainer.style.display = scopeSingle ? 'block' : 'none';
            if (classScopeContainer) classScopeContainer.style.display = scopeClass ? 'block' : 'none';

            // Catatan: form ini SELALU memakai data-no-download (lihat tag <form>).
            // Smart loader memasang listener submit di fase CAPTURE pada document
            // lalu melakukan stopPropagation, jadi listener submit form ini tidak
            // akan pernah jalan. Semua mode ditangani di sini: mode "Semua Kelas"
            // oleh loop per kelas (cetakSemuaKelas), mode lain oleh helper
            // downloadWithProgress().

            // Berpindah dari Per Siswa ke Per Kelas: buang sisa pencarian supaya
            // dropdown hasil & pesan error tidak ikut terbawa.
            if (!modeSiswa) {
                rekapResetPencarianSiswa();
            }

            updateRekapInfoText();
        }

        // Dipakai sebagai onchange inline pada radio "Jenis Cetak".
        function toggleRekapPrintScope() {
            syncModeUI();
        }

        // Dipakai sebagai onchange inline pada radio "Cakupan Siswa".
        function toggleRekapStudentScope() {
            syncModeUI();
        }

        function toggleRekapPeriodFields() {
            const period = document.getElementById('rekapPeriodType').value;
            const fields = ['periodHarian', 'periodMingguan', 'periodBulanan', 'periodSemester', 'periodTahunan'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                const active = (id === 'period' + period.charAt(0).toUpperCase() + period.slice(1));
                el.style.display = active ? 'block' : 'none';
                // Field tersembunyi ikut terkirim kalau hanya disembunyikan
                // (input/select tersembunyi tetap terkirim). Karena itu:
                // (1) dua <select name="period_academic_year"> saling menimpa,
                // (2) nilai periode halaman lama ikut terkirim. Nonaktifkan
                // semua kontrol di blok yang tidak aktif.
                el.querySelectorAll('input, select, textarea').forEach(ctrl => {
                    ctrl.disabled = !active;
                });
            });
            updateRekapInfoText();
        }

        // Hitung rentang periode di sisi browser untuk info box popup.
        // Mengikuti aturan yang sama dengan resolveDateRange() di server.
        const BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        const HARI = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

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
                const days = new Date(y, m, 0).getDate();
                return { text: BULAN[m - 1] + ' ' + y, days: days };
            }
            if (period === 'semester' || period === 'tahunan') {
                const sel = document.querySelector('#periodFields select[name="period_academic_year"]:not([disabled])');
                const opt = sel && sel.options[sel.selectedIndex];
                return { text: (opt ? opt.textContent.trim() : '-') + (period === 'semester' ? '' : ' (1 tahun ajaran)'), days: 0 };
            }
            return null;
        }

        // Student search with debounce
        let studentSearchTimer = null;
        const studentSearchInput = document.getElementById('rekapStudentSearch');
        const studentSearchResults = document.getElementById('studentSearchResults');
        const studentIdInput = document.getElementById('rekapStudentId');

        if (studentSearchInput) {
            studentSearchInput.addEventListener('input', function() {
                const query = this.value.trim();
                clearTimeout(studentSearchTimer);
                studentSearchResults.style.display = 'none';
                studentSearchResults.innerHTML = '';

                if (query.length < 2) {
                    studentIdInput.value = '';
                    return;
                }

                studentSearchTimer = setTimeout(() => {
                    fetch('/admin/rekap/student-search?q=' + encodeURIComponent(query), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        studentSearchResults.innerHTML = '';
                        if (data.length === 0) {
                            studentSearchResults.innerHTML = '<div class="dropdown-item text-muted small">Tidak ada siswa ditemukan</div>';
                        } else {
                            data.forEach(s => {
                                const item = document.createElement('div');
                                item.className = 'dropdown-item small py-2';
                                item.innerHTML = '<strong>' + s.name + '</strong> <span class="text-muted">(' + s.nisn + ')</span> <span class="text-muted small ms-2">Kelas ' + (s.class_name || '-') + '</span>';
                                item.style.cursor = 'pointer';
                                item.addEventListener('click', () => {
                                    studentSearchInput.value = s.name + ' (' + s.nisn + ')';
                                    studentIdInput.value = s.id;
                                    studentSearchResults.style.display = 'none';
                                });
                                studentSearchResults.appendChild(item);
                            });
                        }
                        studentSearchResults.style.display = 'block';
                    });
                }, 250);
            });

            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!studentSearchInput.contains(e.target) && !studentSearchResults.contains(e.target)) {
                    studentSearchResults.style.display = 'none';
                }
            });
        }

        // Form submit handling
        // Format cetakan dipilih dari TOMBOL yang diklik (bukan dari event.relatedTarget
        // yang rapuh: bisa null sehingga jatuh ke fallback 'pdf').
        window.rekapPrintFormat = 'pdf';
        function setRekapPrintFormatFromButton(btn) {
            const f = btn?.getAttribute('data-format');
            window.rekapPrintFormat = (f === 'excel') ? 'excel' : 'pdf';
            const hidden = document.getElementById('printFormat');
            if (hidden) hidden.value = window.rekapPrintFormat;
            updateRekapInfoText();
        }

        // Terapkan format saat submit, tepat sebelum form dikirim, supaya
        // tidak ada kode lain yang menimpanya kembali ke 'pdf'.

        // ================= CETAK "SEMUA KELAS" =================
        // Server selalu_dp_tangani satu kelas per request (student_scope=class).
        // Di sini kita mengulang request itu satu per satu, BERURUTAN, jeda 500 ms,
        // lalu mengubah tiap respons blob menjadi unduhan otomatis.
        let rekapLoopStopped = false;
        let rekapLoopRunning = false;
        let rekapGagalKelas = [];   // daftar {nama, kelas, alasan}

        function rekapPause(ms) {
            return new Promise(function (resolve) { setTimeout(resolve, ms); });
        }

        /** Tunggu display mencapai 100%, tahan ~300 ms, lalu tombol "Selesai".
            Maksimal 3 detik supaya tidak pernah menggantung. */
        function rekapTungguSelesai() {
            return new Promise(function (resolve) {
                const awal = Date.now();
                const cek = function () {
                    const label = document.getElementById('rekapBtnLabel');
                    if (label && /Selesai/.test(label.textContent)) { resolve(); return; }
                    if (Date.now() - awal > 3000) { resolve(); return; }
                    setTimeout(cek, 30);
                };
                cek();
            });
        }

        function rekapcsrf() {
            const el = document.querySelector('input[name="_token"]');
            return el ? el.value : '';
        }

        /* ---------- progres DI DALAM tombol utama ---------- */
        /* ---------- progres DI DALAM tombol utama ----------
           Persen ASLI (target) dipisahkan dari persen TAMPIL. Display naik
           1 demi 1 lewat timer komponen bersama (tidak pernah mundur, tidak
           pernah diam di 0%).

           Ceiling saat menunggu kelas ke-i dari N:
               ceiling = persenSelesai + 0.9 * (100 / N)
           Jadi angka merayap maju selama kelas diproses, tapi tidak pernah
           melewati batas kelas berikutnya sebelum kelas itu selesai. */
        const REKAP_MARGIN = 0.9;   // bagian dari 1 kelas yang boleh dimakanahead

        function rekapMargin(total) {
            const n = total || 0;
            return n > 0 ? REKAP_MARGIN * (100 / n) : 3;
        }

        function rekapIsiTombol(teks) {
            const label = document.getElementById('rekapBtnLabel');
            if (label) label.textContent = teks;
        }

        /** Kelas selesai -> target baru; display mengejar cepat tanpa lompat. */
        function rekapSetProgress(selesai, total) {
            const totalNum = total || 0;
            const persenSelesai = totalNum > 0 ? (selesai / totalNum) * 100 : 0;
            const margin = rekapMargin(totalNum);

            // Target = persen kelas yang sudah selesai, ceiling sedikit di atasnya.
            if (window.dlProgressSet) {
                window.dlProgressSet(persenSelesai, margin);
            } else {
                // Fallback tanpa komponen bersama.
                const persen = Math.round(persenSelesai);
                const fill = document.getElementById('rekapBtnFill');
                if (fill) fill.style.width = persen + '%';
                rekapIsiTombol(persen + '%');
                return persen;
            }
            return persenSelesai;
        }

        function rekapResetPanel() {
            rekapGagalKelas = [];

            const line = document.getElementById('rekapFailLine');
            if (line) line.classList.add('d-none');
            const detail = document.getElementById('rekapDetailList');
            if (detail) { detail.innerHTML = ''; detail.classList.add('d-none'); }
            const toggle = document.getElementById('rekapDetailToggle');
            if (toggle) toggle.textContent = 'Lihat detail';

            const btn = document.getElementById('printRekapSubmitBtn');
            if (btn) {
                btn.classList.remove('is-running', 'is-done');
                btn.style.backgroundColor = '';
                btn.style.borderColor = '';
            }
            rekapSetProgress(0, 0);
        }

        /* Menunggu Retry-After: kita benar-benar tidak tahu persentasenya,
           jadi dipakai mode GESER (bar gelap melintas + teks "Memproses..."). */
        function rekapTampilkanAntrean() {
            const btn = document.getElementById('printRekapSubmitBtn');
            if (!btn) return;
            btn.setAttribute('data-antre', '1');
            if (window.dlProgressGeser) window.dlProgressGeser(true);
        }

        function rekapSembunyikanAntrean() {
            const btn = document.getElementById('printRekapSubmitBtn');
            if (!btn) return;
            btn.removeAttribute('data-antre');
            if (window.dlProgressGeser) window.dlProgressGeser(false);
        }

        function rekapAktifkanTombolAwal() {
            rekapLoopRunning = false;
            const btn = document.getElementById('printRekapSubmitBtn');
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('is-running', 'is-done');
                btn.style.backgroundColor = '';
                btn.style.borderColor = '';
                btn.removeAttribute('data-mode');
                rekapIsiTombol('Generate & Unduh');
            }
            const fill = document.getElementById('rekapBtnFill');
            if (fill) fill.style.width = '0%';
            rekapSembunyikanAntrean();

            const cancel = document.getElementById('rekapCancelBtn');
            if (cancel) {
                cancel.textContent = 'Batal';
                cancel.disabled = false;
                cancel.setAttribute('data-bs-dismiss', 'modal');
            }
        }

        /* ---------- hasil akhir ---------- */

        /** Tutup popup rekap (dipakai setelah selesai / setelah dibatalkan). */
        function rekapTutupPopup() {
            const modal = document.getElementById('printRekapModal');
            if (!modal) return;
            if (!modal.classList.contains('show')) return;   // sudah tertutup, jangan recursion
            try {
                if (window.bootstrap && window.bootstrap.Modal) {
                    const inst = window.bootstrap.Modal.getInstance(modal) || new window.bootstrap.Modal(modal);
                    inst.hide();
                } else {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                }
            } catch (e) { /* popup tetap bisa ditutup manual */ }
        }

        /** Semua kelas berhasil: tombol hijau "Selesai" 1 detik lalu popup menutup sendiri.
            Teks "Selesai" SAJA — tanpa ikon centang. */
        function rekapTampilkanSukses() {
            const btn = document.getElementById('printRekapSubmitBtn');
            const cancel = document.getElementById('rekapCancelBtn');
            rekapLoopRunning = false;

            if (btn) {
                btn.disabled = true;
                btn.classList.remove('is-running');
                btn.classList.add('is-done');
                rekapIsiTombol('Selesai');
            }
            if (cancel) {
                cancel.disabled = false;
                cancel.textContent = 'Tutup';
                cancel.setAttribute('data-bs-dismiss', 'modal');
            }

            setTimeout(rekapTutupPopup, 1000);
        }

        /** Ada kelas gagal: tombol jadi "Coba lagi (n)" + satu baris merah di bawah field. */
        function rekapTampilkanGagal() {
            rekapLoopRunning = false;
            const jumlah = rekapGagalKelas.length;

            const btn = document.getElementById('printRekapSubmitBtn');
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('is-running', 'is-done');
                btn.style.backgroundColor = '';
                btn.style.borderColor = '';
                // Tombol ini jadi pemicu "Coba lagi".
                btn.dataset.mode = 'retry';
                rekapIsiTombol('Coba lagi (' + jumlah + ')');
            }

            const cancel = document.getElementById('rekapCancelBtn');
            if (cancel) {
                cancel.disabled = false;
                cancel.textContent = 'Tutup';
                cancel.setAttribute('data-bs-dismiss', 'modal');
            }

            const line = document.getElementById('rekapFailLine');
            if (line) line.classList.remove('d-none');
            const cnt = document.getElementById('rekapFailCount');
            if (cnt) cnt.textContent = jumlah + ' kelas gagal.';

            const detail = document.getElementById('rekapDetailList');
            if (detail) {
                detail.innerHTML = '';
                rekapGagalKelas.forEach(function (item) {
                    const row = document.createElement('div');
                    row.textContent = item.nama + ': ' + item.alasan;
                    detail.appendChild(row);
                });
                detail.classList.add('d-none');
            }
            const toggle = document.getElementById('rekapDetailToggle');
            if (toggle) toggle.textContent = 'Lihat detail';
        }

        /** Ubah blob + nama file dari header Content-Disposition menjadi unduhan. */
        function rekapSimpanBlob(blob, fileName) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = fileName || 'profil-presensi.pdf';
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(url); }, 4000);
        }

        /* ============ ZIP (JSZip, dimuat via Vite hanya di halaman ini) ============ */

        function rekapZipTersedia() {
            return typeof window.JSZip !== 'undefined' && window.JSZip !== null;
        }

        function rekapZipKosong() {
            if (window.REKAP_ZIP_RESET) {
                window.REKAP_ZIP_RESET();
            } else {
                window.REKAP_ZIP = rekapZipTersedia() ? new window.JSZip() : null;
                window.REKAP_ZIP_NAMA = new Set();
            }
        }

        /** Simpan satu blob kelas ke dalam ZIP (tidak langsung diunduh). */
        function rekapZipTambah(namaDasar, blob) {
            if (!rekapZipTersedia()) return null;
            if (!window.REKAP_ZIP) {
                rekapZipKosong();
            }
            const nama = (window.REKAP_ZIP_NAMA_UNIK || function (n) { return n; })(namaDasar);
            window.REKAP_ZIP.file(nama, blob);
            return nama;
        }

        function rekapZipJumlahFile() {
            return window.REKAP_ZIP ? Object.keys(window.REKAP_ZIP.files || {}).length : 0;
        }

        /**
         * Rakit ZIP lalu unduh SATU file.
         * PDF/XLSX sudah terkompresi sendiri, jadi compression 'STORE' jauh lebih
         * cepat daripada DEFLATE dan hasilnya hampir sama.
         */
        async function rekapZipUnduh() {
            if (!window.REKAP_ZIP) return false;
            const blob = await window.REKAP_ZIP.generateAsync({ type: 'blob', compression: 'STORE' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = window.REKAP_ZIP_NAMA_FILE || 'Profil_Presensi_Semua_Kelas.zip';
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(url); }, 8000);
            return true;
        }

        /** Token periode untuk nama file: mis. "10_2026" (bulanan), "2026-10-06" (harian). */
        function rekapTokenPeriode(periodType, baseFields) {
            const cari = function (nama) {
                const found = baseFields.filter(function (f) { return f[0] === nama; });
                return found.length ? found[0][1] : null;
            };
            if (periodType === 'bulanan') {
                const m = cari('period_month'), y = cari('period_year');
                return (m && y) ? (m + '_' + y) : 'bulanan';
            }
            if (periodType === 'harian' || periodType === 'mingguan') {
                const d = cari('period_date');
                return d ? d.replace(/-/g, '') : periodType;
            }
            const sem = cari('period_semester');
            const opt = document.querySelector('#periodFields select[name="period_academic_year"]');
            const tahun = opt && opt.selectedIndex >= 0 ? opt.options[opt.selectedIndex].textContent : '';
            return ((sem ? sem + '_' : '') + (tahun || 'tahun-ajaran'))
                .replace(/\s+/g, '-')
                .replace(/[^A-Za-z0-9_-]/g, '');
        }

        /** Nama file ZIP gabungan. */
        function rekapNamaZip(token) {
            return 'Profil_Presensi_Semua_Kelas_' + (token || 'periode') + '.zip';
        }

        function rekapAmbilNamaFile(header) {
            if (!header) return null;
            const utf8 = header.match(/filename\*=UTF-8''([^;]+)/i);
            if (utf8) {
                try { return decodeURIComponent(utf8[1]); } catch (e) { return utf8[1]; }
            }
            const basic = header.match(/filename="?([^";]+)"?/i);
            return basic ? basic[1] : null;
        }

        /** 429 = kondisi sementara: baca Retry-After, tunggu, lalu coba lagi. */
        function rekapRetryAfter(res) {
            const h = res && res.headers && res.headers.get ? res.headers.get('Retry-After') : null;
            const detik = parseInt(h || '', 10);
            return (isNaN(detik) || detik <= 0) ? 5 : Math.min(detik, 30);
        }

        const REKAP_MAKS_RETRY = 3;

        /** Satu request cetak untuk satu kelas, dengan retry khusus 429. */
        async function rekapAmbilKelas(url, body, namaKelas) {
            let percobaan = 0;

            while (percobaan <= REKAP_MAKS_RETRY) {
                let res;
                try {
                    res = await fetch(url, {
                        method: 'POST',
                        body: body,
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': rekapcsrf(),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': '*/*',
                        },
                    });
                } catch (err) {
                    return { ok: false, alasan: (err && err.message) ? err.message : 'gagal' };
                }

                if (res.status === 429 && percobaan < REKAP_MAKS_RETRY) {
                    percobaan++;
                    const tunggu = rekapRetryAfter(res);
                    rekapTampilkanAntrean();
                    await rekapPause(tunggu * 1000);
                    rekapSembunyikanAntrean();
                    continue;
                }

                const ct = (res.headers.get('Content-Type') || '').toLowerCase();
                if (!res.ok || ct.indexOf('application/json') !== -1) {
                    let pesan = 'HTTP ' + res.status;
                    try {
                        const j = await res.json();
                        pesan = j.message || pesan;
                    } catch (e) { /* bukan JSON */ }
                    if (res.status === 429) pesan = 'Terlalu banyak permintaan';
                    return { ok: false, alasan: pesan };
                }

                const blob = await res.blob();
                // Disimpan ke ZIP: Profil_<Kelas>_<Periode>.<ext>
                const ext = (ct.indexOf('spreadsheetml') !== -1) ? 'xlsx' : 'pdf';
                const nama = 'Profil_' + namaKelas + '_' + (window.REKAP_TOKEN_PERIODE || '') + '.' + ext;
                const tersimpan = rekapZipTambah(nama, blob);
                if (!tersimpan) {
                    return { ok: false, alasan: 'JSZip tidak tersedia' };
                }
                return { ok: true, nama: tersimpan };
            }

            return { ok: false, alasan: 'Terlalu banyak permintaan' };
        }

        async function cetakSemuaKelas(daftarOverride) {
            if (rekapLoopRunning) return;
            rekapLoopRunning = true;
            rekapLoopStopped = false;

            const btn = document.getElementById('printRekapSubmitBtn');
            const cancel = document.getElementById('rekapCancelBtn');
            const infoText = document.getElementById('rekapInfoText');

            /* Progres started LANGSUNG di baris pertama: tampil 0%, lalu naik ke 1%
               dalam <=100 ms. Tidak menunggu daftar kelas / penyiapan ZIP. */
            if (btn && window.dlProgressMulai) {
                window.dlProgressMulai(btn, 3);
                btn.classList.add('is-running', 'btn-progress');
            }

            const daftar = Array.isArray(daftarOverride) ? daftarOverride : rekapKelasDenganSiswa();

            if (!daftar.length) {
                if (window.dlProgressReset) window.dlProgressReset();
                rekapAktifkanTombolAwal();
                if (infoText) infoText.textContent = 'Tidak ada kelas yang memiliki siswa aktif.';
                return;
            }

            rekapResetPanel();
            // Pada retry, file kelas yang sudah berhasil masih ada di ZIP, jadi
            // progres dilanjutkan dari situ (bar tidak melompat balik ke 0).
            const offset = Array.isArray(daftarOverride) ? rekapZipJumlahFile() : 0;
            const totalKeseluruhan = offset + daftar.length;

            if (!Array.isArray(daftarOverride)) {
                // Unduhan baru: objek ZIP dibuang agar memori bebas.
                rekapZipKosong();
            }

            if (btn) {
                // Angkanya sudah jalan sejak klik; sekarang baru pakai ceiling
                // yang benar sesuai jumlah kelas.
                if (window.dlProgressSet) {
                    window.dlProgressSet(0, rekapMargin(totalKeseluruhan));
                } else {
                    btn.disabled = true;
                    btn.classList.add('is-running', 'btn-progress');
                    rekapIsiTombol('0%');
                    const f = document.getElementById('rekapBtnFill');
                    if (f) f.style.width = '0%';
                }
            }
            if (cancel) {
                cancel.textContent = 'Hentikan';
                cancel.removeAttribute('data-bs-dismiss');
            }

            // Field periode diambil dari BLOK periode yang aktif (yang sedang tampil),
            // bukan dengan memeriksa atribut disabled - kalau disable belum
            // berjalan, hasil sebelumnya kosong dan server tidak pernah
            // menerima periode.
            const periodTypeSel = document.getElementById('rekapPeriodType');
            const periodType = periodTypeSel && periodTypeSel.value ? periodTypeSel.value : '{{ $type }}';
            const blockIds = {
                harian: 'periodHarian', mingguan: 'periodMingguan', bulanan: 'periodBulanan',
                semester: 'periodSemester', tahunan: 'periodTahunan',
            };
            const block = document.getElementById(blockIds[periodType]);
            const baseFields = [];
            if (block) {
                block.querySelectorAll('select, input').forEach(function (ctrl) {
                    if (ctrl.name && ctrl.value) {
                        baseFields.push([ctrl.name, ctrl.value]);
                    }
                });
            }

            const url = @json(panel_route('rekap.print'));
            const csrf = rekapcsrf();
            const format = window.rekapPrintFormat === 'excel' ? 'excel' : 'pdf';

            // Nama file di dalam ZIP: Profil_<Kelas>_<Periode>.<ext>
            window.REKAP_TOKEN_PERIODE = rekapTokenPeriode(periodType, baseFields);
            window.REKAP_ZIP_NAMA_FILE = rekapNamaZip(window.REKAP_TOKEN_PERIODE);

            let selesai = 0;

            const daftarTugas = daftar;
            for (let i = 0; i < daftarTugas.length; i++) {
                if (rekapLoopStopped) break;
                const kelas = daftarTugas[i];

                const body = new FormData();
                body.append('_token', csrf);
                body.append('format', format);
                body.append('type', periodType);
                body.append('period_type', periodType);
                body.append('print_mode', 'student');
                body.append('student_scope', 'class');
                body.append('scope_class_id', kelas.id);
                baseFields.forEach(function (f) { body.append(f[0], f[1]); });

                const hasil = await rekapAmbilKelas(url, body, kelas.name);
                if (hasil.ok) {
                    selesai++;
                } else {
                    rekapGagalKelas.push({ nama: kelas.name, kelas: kelas, alasan: hasil.alasan });
                }

                rekapSetProgress(offset + selesai + rekapGagalKelas.length, totalKeseluruhan);

                // Jeda antar unduhan supaya browser sempat menyimpan file.
                if (i < daftarTugas.length - 1) await rekapPause(500);
            }

            // Ada kelas gagal? ZIP TIDAK langsung diunduh supaya pengguna bisa
            // menekan "Coba lagi (n)" lebih dulu. File yang sudah berhasil
            // tetap tersimpan di objek ZIP (tidak perlu diunduh ulang).
            if (rekapGagalKelas.length > 0) {
                rekapTampilkanGagal();
                return;
            }

            // Semua kelas sudah masuk ZIP: display mengejar 100% (+1 tiap 12 ms),
            // tahan ~300 ms, baru tombol hijau "Selesai".
            rekapSetProgress(totalKeseluruhan, totalKeseluruhan);
            if (btn) btn.classList.add('is-running', 'btn-progress');

            try {
                await rekapZipUnduh();
            } catch (err) {
                rekapAktifkanTombolAwal();
                rekapGagalKelas.push({
                    nama: 'ZIP',
                    kelas: null,
                    alasan: 'Gagal menyusun ZIP: ' + (err && err.message ? err.message : err),
                });
                rekapTampilkanGagal();
                return;
            }

            // Beri waktu display sampai benar-benar 100% sebelum "Selesai".
            if (window.dlProgressFinal) {
                window.dlProgressFinal();
            }
            await rekapTungguSelesai();
            rekapTampilkanSukses();
        }

        /** Ulangi HANYA kelas yang gagal (tombol utama berubah jadi "Coba lagi (n)"). */
        async function cetakUlangKelasGagal() {
            if (rekapLoopRunning || !rekapGagalKelas.length) return;
            // Abaikan entri kegagalan yang bukan kelas (mis. gagal menyusun ZIP).
            const sisaKelas = rekapGagalKelas
                .filter(function (item) { return item.kelas; })
                .map(function (item) { return item.kelas; });
            rekapGagalKelas = [];
            if (!sisaKelas.length) {
                // Tidak ada kelas gagal (hanya ZIP yang gagal): coba susun ulang.
                await cetakSemuaKelas();
                return;
            }
            await cetakSemuaKelas(sisaKelas);
        }

        // Tombol utama jadi pemicu retry saat sedang menampilkan "Coba lagi (n)".
        document.getElementById('printRekapSubmitBtn')?.addEventListener('click', function (e) {
            if (this.dataset.mode === 'retry') {
                e.preventDefault();
                cetakUlangKelasGagal();
            }
        }, true);

        document.getElementById('rekapDetailToggle')?.addEventListener('click', function (e) {
            e.preventDefault();
            const list = document.getElementById('rekapDetailList');
            if (!list) return;
            const tersembunyi = list.classList.contains('d-none');
            list.classList.toggle('d-none', !tersembunyi);
            this.textContent = tersembunyi ? 'Sembunyikan detail' : 'Lihat detail';
        });

        /* ---------- Batal / Hentikan ----------
           Berlaku untuk SEMUA mode: tombol Batal, tombol X, dan klik backdrop
           semuanya membatalkan unduhan (AbortController.abort()), menghentikan
           timer, lalu LANGSUNG menutup popup. Tidak ada teks pembatalan,
           tidak ada toast, tidak ada pesan error sama sekali. */
        (function () {
            const btn    = document.getElementById('printRekapSubmitBtn');
            const batal  = document.getElementById('rekapCancelBtn');
            const modal  = document.getElementById('printRekapModal');
            if (!btn || !batal || !modal) return;

            let controller = null;

            function keModeBatal() {
                controller = null;
                batal.textContent = 'Batal';
                batal.disabled = false;
                batal.setAttribute('data-bs-dismiss', 'modal');
            }

            function batalkanSemua() {
                // Loop "Semua Kelas": hentikan loop + buang objek ZIP.
                if (rekapLoopRunning) {
                    rekapLoopStopped = true;
                    rekapZipKosong();
                    rekapLoopRunning = false;
                }
                // Mode lain: abort lewat helper (senyap, tanpa pesan).
                if (typeof window.dlProgressAbort === 'function') {
                    window.dlProgressAbort();
                }
                rekapLoopStopped = true;
                keModeBatal();
                rekapTutupPopup();
            }

            btn.addEventListener('dl-busy-start', function (e) {
                if (rekapLoopRunning) return;    // loop "Semua Kelas" aturan sendiri
                controller = e.detail ? e.detail.controller : null;
                if (!controller) return;
                // "Batal" -> "Hentikan" (lebar tetap, gaya tetap).
                batal.textContent = 'Hentikan';
                batal.removeAttribute('data-bs-dismiss');
            });

            btn.addEventListener('dl-busy-end', function () {
                keModeBatal();
            });

            // Klik tombol Batal/Hentikan.
            batal.addEventListener('click', function (e) {
                if (batal.textContent !== 'Hentikan' && !rekapLoopRunning) return;
                e.preventDefault();
                e.stopPropagation();
                batalkanSemua();
            }, true);

            // Tombol X dan klik backdrop -> hide.bs.modal.
            modal.addEventListener('hide.bs.modal', function () {
                const jalan = rekapLoopRunning
                    || (typeof window.dlProgressBusy === 'function' && window.dlProgressBusy());
                if (jalan) batalkanSemua();
            });
        })();

        /* ---------- tombol utama jadi pemicu retry saat mode = retry ---------- */

        document.getElementById('printRekapForm')?.addEventListener('submit', function(e) {
            const formatInput = document.getElementById('printFormat');
            if (formatInput) {
                formatInput.value = (window.rekapPrintFormat === 'excel') ? 'excel' : 'pdf';
            }

            const mode = document.querySelector('input[name="print_mode"]:checked')?.value;
            const format = document.getElementById('printFormat')?.value;
            const scope = document.querySelector('input[name="student_scope"]:checked')?.value;

            // Validation
            if (document.getElementById('printModeStudent').checked) {
                if (scope === 'single' && !document.getElementById('rekapStudentId').value) {
                    e.preventDefault();
                    alert('Pilih siswa terlebih dahulu.');
                    return;
                }
                if (scope === 'class' && !document.getElementById('rekapScopeClassId').value) {
                    e.preventDefault();
                    alert('Pilih kelas terlebih dahulu.');
                    return;
                }
            } else if (document.getElementById('printModeClass')?.checked) {
                // Cetak per Kelas memakai class_id tersembunyi milik form ini.
                // Nilai kosong = semua kelas, itu sah jadi TIDAK diblokir.
                // (Dulu kode ini menunjuk #modalClassId yang tidak pernah ada,
                //  jadi selalu melempar TypeError.)
                const classIdInput = document.querySelector('#printRekapForm input[name="class_id"]');
                if (classIdInput && !classIdInput.value) {
                    // Semua kelas: tidak perlu konfirmasi.
                }
            }

            // Validasi periode: hanya field periode AKTIF yang penting.
            if (!computeRekapRange()) {
                e.preventDefault();
                alert('Lengkapi field periode terlebih dahulu.');
                return;
            }

            // "Semua Kelas" TIDAK dikirim sebagai satu form. Browser memecahnya
            // jadi satu request per kelas (student_scope=class), berurutan.
            if (document.getElementById('printModeStudent')?.checked && scope === 'classes') {
                e.preventDefault();
                // Kalau loop melempar error, tombol tetap harus dipulihkan.
                Promise.resolve()
                    .then(function () { return cetakSemuaKelas(); })
                    .catch(function (err) {
                        rekapAktifkanTombolAwal();
                        rekapGagalKelas.push({
                            nama: 'Unduhan',
                            kelas: null,
                            alasan: 'Gagal menjalankan unduhan: ' + (err && err.message ? err.message : err),
                        });
                        rekapTampilkanGagal();
                    });
                return;
            }

            // Mode selain "Semua Kelas": kirim lewat helper bersama supaya progres
            // persen muncul DI DALAM tombol. Logika server (format, periode,
            // scope) tetap sama karena isi form tidak berubah.
            const submitBtn = document.getElementById('printRekapSubmitBtn');
            if (typeof window.downloadWithProgress !== 'function') {
                return;   // helper tidak termuat: biarkan submit normal berjalan
            }

            e.preventDefault();
            const body = new FormData(e.target);
            window.downloadWithProgress(submitBtn, e.target.getAttribute('action') || window.location.href, {
                method: 'POST',
                body: body,
                csrf: rekapcsrf()
            }).then(function (ok) {
                // Berhasil: helper sudah menampilkan "Selesai" 1 detik, lalu tutup popup.
                // Dibatalkan / gagal: JANGAN tampilkan apa pun.
                if (ok) rekapTutupPopup();
            });
        });

        // Initialize modal when shown
        document.getElementById('printRekapModal')?.addEventListener('show.bs.modal', function(event) {
            // Format sudah diambil saat klik tombol; relatedTarget hanya cadangan.
            setRekapPrintFormatFromButton(event.relatedTarget);
            const format = window.rekapPrintFormat;

            // Set default period based on current tab
            const currentType = '{{ $type }}';
            const periodMap = { harian: 'harian', mingguan: 'mingguan', bulanan: 'bulanan' };
            const periodSelect = document.getElementById('rekapPeriodType');
            if (periodSelect && periodMap[currentType]) {
                periodSelect.value = periodMap[currentType];
            }

            // Paksa ke mode "Cetak per Kelas" setiap popup dibuka.
            document.getElementById('printModeClass').checked = true;
            document.getElementById('scopeSingle').checked = true;
            document.getElementById('rekapPeriodType').value = periodMap[currentType] || 'bulanan';

            toggleRekapPeriodFields();
            // Dulu handler ini memanggil toggleRekapStudentScope() secara
            // tak bersyarat; karena scope masih "single", blok "Cari Siswa"
            // langsung dimunculkan lagi meski radionya Per Kelas.
            // Sekarang satu fungsi syncModeUI() yang menentukan semuanya.
            syncModeUI();
            rekapResetPencarianSiswa();
            rekapResetPanel();
            rekapZipKosong();
            rekapAktifkanTombolAwal();
            // Pastikan tidak ada sisa unduhan dari sesi popup sebelumnya.
            if (typeof window.dlProgressReset === 'function') window.dlProgressReset();
            updateRekapInfoText();

            // Student search autocomplete
            const searchUrl = '{{ panel_route("rekap.student-search") }}';
            window.rekapStudentSearchUrl = searchUrl;
        });

        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            toggleRekapPeriodFields();
            // Keadaan awal popup mengikuti radio default (Per Kelas).
            syncModeUI();
            rekapResetPencarianSiswa();

            // Semua tombol pembuka modal (desktop + mobile) mengunci format-nya
            // di sini, jadi tidak bergantung pada relatedTarget.
            document.querySelectorAll('[data-bs-target="#printRekapModal"]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    setRekapPrintFormatFromButton(btn);
                });
            });

            // Info box & rentang ikut berubah begitu field periode diubah.
            const periodScope = document.getElementById('periodFields');
            if (periodScope) {
                periodScope.addEventListener('change', updateRekapInfoText);
                periodScope.addEventListener('input', updateRekapInfoText);
            }
            document.getElementById('rekapScopeClassId')?.addEventListener('change', updateRekapInfoText);
            updateRekapInfoText();
        });
    </script>
    @endpush
<!-- MODAL CETAK REKAP PRESENSI -->
<div class="modal fade" id="printRekapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Cetak Rekap Presensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
                {{-- data-no-download: smart loader (instant-download.js) memasang
                     listener submit di fase CAPTURE pada document lalu
                     preventDefault + stopPropagation, sehingga listener submit form
                     ini tidak akan pernah jalan. Semua mode (Per Kelas / Satu Kelas /
                     Satu Siswa) memakai helper bersama downloadWithProgress() supaya
                     progres persen tampil DI DALAM tombol, dan "Semua Kelas" memakai
                     loop per kelas sendiri. --}}
                <form action="{{ panel_route('rekap.print') }}" method="POST" id="printRekapForm" data-no-download>
                @csrf
                <input type="hidden" name="format" id="printFormat" value="pdf">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="class_id" value="{{ $classId ?? '' }}">

                <div class="modal-body py-3">
                    <p class="text-secondary small mb-3">
                        Pilih opsi cetak rekap presensi. Hasil akan diunduh sebagai file PDF atau Excel.
                    </p>

                    <!-- Jenis Cetak -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jenis Cetak <span class="text-danger">*</span></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="print_mode" id="printModeClass" value="class" checked onchange="toggleRekapPrintScope()">
                            <label class="form-check-label fw-medium small" for="printModeClass">
                                Cetak per Kelas (Rekap Tabel)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="print_mode" id="printModeStudent" value="student" onchange="toggleRekapPrintScope()">
                            <label class="form-check-label fw-medium small" for="printModeStudent">
                                Cetak per Siswa (Profil Lengkap)
                            </label>
                        </div>
                    </div>

                    <!-- Cakupan Siswa (hanya untuk Cetak per Siswa) -->
                    <div class="mb-3" id="studentScopeContainer" style="display: none;">
                        <label class="form-label small fw-semibold">Cakupan Siswa <span class="text-danger">*</span></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="student_scope" id="scopeSingle" value="single" checked onchange="toggleRekapStudentScope()">
                            <label class="form-check-label fw-medium small" for="scopeSingle">
                                Satu Siswa
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="student_scope" id="scopeClass" value="class" onchange="toggleRekapStudentScope()">
                            <label class="form-check-label fw-medium small" for="scopeClass">
                                Satu Kelas (Semua Siswa)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="student_scope" id="scopeClasses" value="classes" onchange="toggleRekapStudentScope()">
                            <label class="form-check-label fw-medium small" for="scopeClasses">
                                Semua Kelas (satu file ZIP)
                            </label>
                        </div>
                    </div>

                    <!-- Pilih Siswa (untuk scope single) -->
                    <div class="mb-3" id="singleStudentContainer" style="display: none;">
                        <label class="form-label small fw-semibold" for="rekapStudentSearch">Cari Siswa <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <input type="text" class="form-control form-control-sm rounded-3" id="rekapStudentSearch" name="student_search" placeholder="Ketik nama atau NISN..." autocomplete="off" aria-describedby="studentSearchHelp">
                            <div id="studentSearchResults" class="dropdown-menu w-100 mt-1 border-0 shadow-sm rounded-3" style="max-height: 200px; overflow-y: auto; display: none;"></div>
                        </div>
                        <div class="form-text small" id="studentSearchHelp">Ketik minimal 2 karakter untuk mencari siswa</div>
                        <input type="hidden" name="student_id" id="rekapStudentId">
                    </div>

                    <!-- Pilih Kelas (untuk scope class) -->
                    <div class="mb-3" id="classScopeContainer" style="display: none;">
                        <label class="form-label small fw-semibold">Pilih Kelas <span class="text-danger">*</span></label>
                        <select name="scope_class_id" id="rekapScopeClassId" class="form-select form-select-sm rounded-3">
                            <option value="">Pilih Kelas</option>
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}">Kelas {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Periode -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Periode <span class="text-danger">*</span></label>
                        <select name="period_type" id="rekapPeriodType" class="form-select form-select-sm rounded-3" onchange="toggleRekapPeriodFields()">
                            <option value="harian">Harian</option>
                            <option value="mingguan">Mingguan</option>
                            <option value="bulanan">Bulanan</option>
                            <option value="semester">Semester</option>
                            <option value="tahunan">Setahun (Tahun Ajaran)</option>
                        </select>
                    </div>

                    <!-- Periode Fields Dinamis.
                         CATATAN: nilai awal TIDAK lagi diambil dari $startDate/$endDate
                         hasil filter halaman. Dulu modal mewarisi rentang bulan bawaan
                         halaman (mis. 01 Okt - 31 Okt), jadi memilih "Mingguan" tetap
                         mengirim rentang bulan itu. Sekarang setiap periode punya
                         field sendiri dan rentang FINAL dihitung resolveDateRange()
                         dari nilai di sini, sehingga pilihan popup yang menang.
                         Field tersembunyi juga di-disable oleh JS supaya tidak ikut
                         terkirim (duplikat period_academic_year sebelumnya). -->
                    <div id="periodFields">
                        <!-- Harian: 1 tanggal -->
                        <div class="mb-3" id="periodHarian" style="display: none;">
                            <label class="form-label small fw-semibold">Tanggal</label>
                            <input type="date" name="period_date" data-role="period-date" class="form-control form-control-sm rounded-3" value="{{ $popupDate }}">
                        </div>
                        <!-- Mingguan: 1 tanggal acuan (Senin s/d Minggu dihitung otomatis) -->
                        <div class="mb-3" id="periodMingguan" style="display: none;">
                            <label class="form-label small fw-semibold">Tanggal dalam minggu ini</label>
                            <input type="date" name="period_date" data-role="period-date" class="form-control form-control-sm rounded-3" value="{{ $popupDate }}">
                            <div class="form-text small">Rentang dicetak: Senin sampai Minggu dari tanggal tersebut.</div>
                        </div>
                        <!-- Bulanan: bulan + tahun -->
                        <div class="mb-3" id="periodBulanan" style="display: none;">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Bulan</label>
                                    <select name="period_month" class="form-select form-select-sm rounded-3">
                                        @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ (int) $popupMonth === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate((int) $popupYear, $m, 1)->translatedFormat('F') }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Tahun</label>
                                    <select name="period_year" class="form-select form-select-sm rounded-3">
                                        @for($y = (int) $popupYear - 1; $y <= (int) $popupYear + 2; $y++)
                                        <option value="{{ $y }}" {{ (int) $popupYear === $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- Semester: semester + tahun ajaran -->
                        <div class="mb-3" id="periodSemester" style="display: none;">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Semester</label>
                                    <select name="period_semester" class="form-select form-select-sm rounded-3">
                                        <option value="ganjil">Ganjil</option>
                                        <option value="genap">Genap</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Tahun Ajaran</label>
                                    <select name="period_academic_year" class="form-select form-select-sm rounded-3">
                                        @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ (int) $activeYear->id === (int) $ay->id ? 'selected' : '' }}>{{ $ay->name }} ({{ $ay->semester }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- Tahunan: tahun ajaran -->
                        <div class="mb-3" id="periodTahunan" style="display: none;">
                            <label class="form-label small fw-semibold">Tahun Ajaran</label>
                            <select name="period_academic_year" class="form-select form-select-sm rounded-3">
                                @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ (int) $activeYear->id === (int) $ay->id ? 'selected' : '' }}>{{ $ay->name }} ({{ $ay->semester }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-2" id="rekapPrintInfoBox">
                        <i class='bx bx-info-circle me-1'></i> <span id="rekapInfoText">Pilih opsi cetak di atas untuk melihat ringkasan hasil.</span>
                    </div>

                    <!-- Baris kegagalan: hanya muncul kalau ada kelas yang gagal.
                         Kalau tidak ada, disembunyikan sehingga tidak memakan ruang. -->
                    <div class="small text-danger d-none mb-2" id="rekapFailLine" role="status" aria-live="polite">
                        <span id="rekapFailCount"></span>
                        <a href="#" id="rekapDetailToggle" class="ms-1">Lihat detail</a>
                        <div id="rekapDetailList" class="d-none mt-1"
                             style="max-height:120px; overflow-y:auto;"></div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <!-- Dua tombol sama lebar, lebar tetap. Saat proses berjalan
                         "Batal" berubah jadi "Hentikan" (lebar tetap). -->
                    <div class="dl-actions">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal" id="rekapCancelBtn">Batal</button>
                        <button type="submit" class="btn-download-blue btn-progress px-3" data-download id="printRekapSubmitBtn">
                            <!-- Lapisan warna = bar progres (lebar = persen) -->
                            <span class="btn-progress__fill" id="rekapBtnFill" aria-hidden="true"></span>
                            <span class="btn-progress__label" id="rekapBtnLabel">Generate &amp; Unduh</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection