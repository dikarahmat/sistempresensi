@extends('layouts.app')

@section('title', __('Data Siswa'))
@section('page_title', __('Data Siswa'))
@section('page_subtitle', __('Kelola informasi siswa'))

{{-- Kanvas dikunci setinggi satu layar; kartu tabel mengisi sisa tinggi kanvas
     sehingga tabelnya (bukan halaman) yang menggulir. Class ini diatur di layout
     bersama, sama seperti halaman acuan. --}}
@section('canvas_class', 'page-canvas-fixed')    @push('styles')
<style>
    /* Subtitle Data Siswa: full width, tidak terpotong (mobile & desktop) */
    .header-main-subtitle {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        display: block !important;
        width: 100% !important;
    }

    #btnSimpanSiswa {
        transition: background-color .15s ease, color .15s ease, box-shadow .15s ease;
    }
    #btnSimpanSiswa:disabled {
        background-color: #cbd5e1 !important;
        color: #64748b !important;
        border-color: #cbd5e1 !important;
        cursor: not-allowed !important;
        opacity: 1 !important;
        box-shadow: none !important;
    }
    .field-error {
        min-height: 0;
        margin-top: 4px;
        font-size: 12px;
        line-height: 1.2;
        color: #dc2626;
    }
    .field-error:empty {
        display: none;
    }
    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 .15rem rgba(220, 38, 38, .12) !important;
    }

    /* Tambah Siswa Baru modal: jarak seragam grup kolom */
    #addStudentModal .modal-body > .mb-3 {
        margin-bottom: 14px !important;
    }
    #addStudentModal .modal-body > .mb-3:last-child {
        margin-bottom: 18px !important;
    }
    #addStudentModal .form-label {
        margin-bottom: 6px !important;
    }
</style>
@endpush


@push('styles')
<style>
    /* Zebra Striping Khusus */
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f8fafc !important;
    }
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td {
        background-color: #f1f5f9 !important;
    }

    .table-zebra-custom {
        width: 100%;
        table-layout: fixed;
        min-width: 860px;
        margin-bottom: 0;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        display: block;
    }

    .table-zebra-custom th,
    .table-zebra-custom td {
        vertical-align: middle;
        padding-left: 12px !important;
        padding-right: 12px !important;
    }
    .table-zebra-custom tbody td {
        color: #1e293b !important;
        font-weight: 400 !important;
        letter-spacing: 0.03em;
        font-family: 'Poppins', 'Roboto', sans-serif;
        font-size: 0.78rem;
    }

    .indent-nama {
        padding-left: 1.25rem !important;
    }
    @media (min-width: 992px) {
        .indent-nama {
            padding-left: 2rem !important;
        }
    }

    .nama-siswa-wrapper {
        display: block;
        width: 100%;
        max-width: 100%;
        padding-left: 0;
        text-align: left;
        white-space: normal;
        word-break: break-word;
    }

    .crud-center-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
    }

    .pagination-compact .pagination {
        margin-bottom: 0;
        font-size: 0.82rem;
    }
    .pagination-compact .page-link {
        padding: 0.35rem 0.65rem;
        border-radius: 6px !important;
    }

@media (max-width: 767px) {
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        .table-zebra-custom {
            table-layout: auto;
            border-collapse: collapse;
        }

        .table-zebra-custom th,
        .table-zebra-custom td {
            vertical-align: middle;
            padding: 10px 6px !important;
            font-size: 11px !important;
        }

        .table-zebra-custom th.no, .table-zebra-custom td.no { display: none; }
        .table-zebra-custom th.kelas, .table-zebra-custom td.kelas { width: 1%; text-align: center; }
        .table-zebra-custom th.nisn, .table-zebra-custom td.nisn { width: 1%; text-align: center; }
        .table-zebra-custom th.nama, .table-zebra-custom td.nama { width: auto; text-align: left; white-space: normal; word-break: break-word; }
        .table-zebra-custom th.jenis_kelamin, .table-zebra-custom td.jenis_kelamin { width: 1%; text-align: center; }
        .table-zebra-custom th.aksi, .table-zebra-custom td.aksi { width: 1%; white-space: nowrap; }

        .table-zebra-custom thead th.no, .table-zebra-custom thead th.kelas,
        .table-zebra-custom thead th.nisn, .table-zebra-custom thead th.jenis_kelamin,
        .table-zebra-custom thead th.aksi { text-align: center; }
        .table-zebra-custom thead th.nama { text-align: left; }

        .table-zebra-custom tbody td.no, .table-zebra-custom tbody td.kelas,
        .table-zebra-custom tbody td.nisn, .table-zebra-custom tbody td.jenis_kelamin,
        .table-zebra-custom tbody td.aksi { text-align: center; }
        .table-zebra-custom tbody td.nama { text-align: left; }

        .table-zebra-custom tbody td.aksi {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        .table-zebra-custom tbody td.aksi .crud-center-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .nama-siswa-wrapper {
            padding-left: 0 !important;
            padding-right: 0 !important;
            text-align: left !important;
        }
    }

    /* Header tabel Data Siswa: nilai DESKTOP (>= 1024px) tidak berubah. */
    .table-zebra-custom thead th {
        color: #475569 !important;
        font-size: 0.72rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.03em;
        border-bottom: 1.5px solid #edf2f7 !important;
        background-color: #f8fafc !important;
        padding: 0.75rem 0.75rem;
        font-family: 'Poppins', 'Roboto', sans-serif;
        text-transform: uppercase;
    }

    /* TABLET & HP (<= 1023.98px): header jadi LATAR PUTIH + teks gelap
       semibold + SATU garis tipis. Desktop >= 1024px sengaja TIDAK disentuh. */
    @media (max-width: 1023.98px) {
        .table-zebra-custom thead th {
            background-color: #ffffff !important;
            color: var(--clean-ink) !important;
            border-bottom: 1px solid var(--clean-line) !important;
        }
    }

    /* ===== Action Bar Layout =====
       Satu baris rata tengah: [input cari][ikon search][dropdown kelas]
       ..... [CETAK KARTU][IMPORT EXCEL][TAMBAH SISWA]. -->
    .action-bar-section {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem 1rem;
    }

    .action-search-form {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        flex: 1 1 380px;
        min-width: 0;
        margin: 0;
    }

    .action-search-form .search-box-wrap {
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        flex: 1 1 240px;
        min-width: 170px;
        width: auto;
        max-width: none;
        height: 38px;
        margin-bottom: 0;
    }
    .action-search-form .search-box-wrap input.form-control {
        height: 38px;
        min-width: 0;
    }
    .action-search-form .search-box-wrap button.btn {
        height: 38px;
        flex: 0 0 auto;
    }

    .action-search-form .filter-box-wrap {
        flex: 0 1 200px;
        min-width: 150px;
        max-width: 200px;
        width: auto;
    }
    .action-search-form .filter-box-wrap select.form-select {
        height: 38px;
    }

    .action-buttons-wrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        flex: 0 1 auto;
        margin-left: auto;
    }
    .action-buttons-wrap .btn-solid-pill {
        flex: 0 0 auto;
    }

    .btn-solid-pill,
    button.btn-solid-pill,
    a.btn-solid-pill {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 0.4rem;
        height: 38px !important;
        border-radius: var(--clean-radius) !important;
        border: none !important;
        font-size: 0.85rem;
        font-weight: 600;
        color: #ffffff;
        white-space: nowrap;
        padding: 0 1rem;
        transition: filter 0.15s ease, transform 0.1s ease;
        box-shadow: none !important;
        letter-spacing: 0.03em;
        font-family: 'Poppins', 'Roboto', sans-serif;
        min-width: 150px;
        text-align: center;
    }
    .btn-solid-pill:hover {
        filter: brightness(0.94);
        color: #ffffff;
    }
    .btn-solid-pill:active {
        transform: scale(0.98);
    }


    .btn-row-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent !important;
        border-radius: 0 !important;
        padding: 0.2rem;
        font-size: 16px;
        color: inherit;
        white-space: nowrap;
        transition: color 0.15s ease, opacity 0.15s ease;
        box-shadow: none !important;
        cursor: pointer;
        font-family: 'Poppins', 'Roboto', sans-serif;
    }
    .btn-row-action svg {
        display: block;
        width: 20px;
        height: 20px;
    }
    @media (max-width: 767.98px) {
        .btn-row-action svg {
            width: 18px;
            height: 18px;
        }
    }
    .btn-row-action:hover {
        opacity: 0.8;
    }
    .btn-row-action:focus-visible {
        outline: 2px solid currentColor;
        outline-offset: 2px;
    }
    .btn-row-action.action-edit { color: #f59e0b; }
    .btn-row-action.action-edit:hover { color: #d97706; }
    .btn-row-action.action-delete { color: #dc2626; }
    .btn-row-action.action-delete:hover { color: #b91c1c; }

    /* === Matikan Cetak Kartu saat data kosong, biarkan tombol lain normal ===
       Hanya selector .btn-cetak-kartu ditargetkan, bukan class tombol umum.
       Saat data ada: tombol Cetak Kartu tetap biru normal (btn-solid-blue). */
    .btn-cetak-kartu.is-disabled {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        pointer-events: none;
        box-shadow: none !important;
    }

    /* ===== Validasi format (client-side) =====
     - Nama   : blokir ANGKA (blacklist). Valid: huruf, spasi, titik, koma, apostrof, strip.
     - NISN   : blokir semua selain angka (replace /\D/g, '') dan batasi 10 digit.
     - Kelas  : wajib pilih (placeholder value="").
     - Jenis Kelamin: wajib pilih (placeholder value="" + opsi pertama). */
     /* Row Action Buttons (Pure Icon): tanpa background box, hanya ikon berwarna */
    .btn-row-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent !important;
        border-radius: 0 !important;
        padding: 0.2rem;
        font-size: 16px;
        color: inherit;
        white-space: nowrap;
        transition: color 0.15s ease, opacity 0.15s ease;
        box-shadow: none !important;
        cursor: pointer;
        font-family: 'Poppins', 'Roboto', sans-serif;
    }
    .btn-row-action svg {
        display: block;
        width: 20px;
        height: 20px;
    }
    @media (max-width: 767.98px) {
        .btn-row-action svg {
            width: 18px;
            height: 18px;
        }
    }
    .btn-row-action:hover {
        opacity: 0.8;
    }
    .btn-row-action:focus-visible {
        outline: 2px solid currentColor;
        outline-offset: 2px;
    }
    .btn-row-action.action-edit { color: #f59e0b; }
    .btn-row-action.action-edit:hover { color: #d97706; }
    .btn-row-action.action-delete { color: #dc2626; }
    .btn-row-action.action-delete:hover { color: #b91c1c; }

    /* ===== Data Siswa: kolom Nama Siswa rata kiri di DESKTOP =====
       Hanya berlaku di lebar >= 768px; mobile (di bawah 768px) tidak terpengaruh.
       padding-left 120px pada th dan td agar judul & isi nama berawal di garis vertikal
       yang sama, berada di tengah celah antara NISN dan Jenis Kelamin (~x=850-900 di 1920px). */
    @media (min-width: 768px) {
        .table-zebra-custom thead th:nth-child(4),
        .table-zebra-custom tbody td:nth-child(4) {
            text-align: left !important;
            padding-left: 225px !important;
        }
        .table-zebra-custom tbody td:nth-child(4) {
            white-space: normal !important;
        }
        .table-zebra-custom tbody td:nth-child(4) .nama-siswa-wrapper {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
    }

    /* Eksplisit: warna latar + teks putih pakai !important biar tidak ter-override */
    .btn-solid-green { background-color: #059669 !important; color: #ffffff !important; }
    .btn-solid-blue { background-color: #2563eb !important; color: #ffffff !important; }
    .btn-solid-red { background-color: #dc2626 !important; color: #ffffff !important; }
    #btnImportSubmit:disabled { background-color:#059669 !important; color:#ffffff !important; opacity:.5 !important; cursor:not-allowed !important; box-shadow:none !important; border:none !important; }

    /* Alert import: teks tidak bertumpuk dengan ikon, baris pendek dan rapi */
    .import-summary {
        display: block;
        line-height: 1.4;
        word-wrap: break-word;
    }
    .import-summary + *,
    .alert .flash-notice-body .d-flex > div {
        min-width: 0;
    }
</style>
@endpush

@section('content')

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('danger'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" style="background-color: #fee2e2 !important; color: #991b1b !important; max-height: none; overflow: visible;" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-start">
                <i class='bx bx-error-circle fs-5 me-2 mt-1' style="color: #991b1b !important;"></i>
                <div>
                    <div class="import-summary">{{ session('danger') }}</div>
                </div>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('deleted'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-trash fs-5 me-2 text-danger'></i>
                <span>{{ session('deleted') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert" style="max-height: none; overflow: visible;">
        <div class="flash-notice-body">
            <div class="d-flex align-items-start">
                <i class='bx bx-error-circle fs-5 me-2 mt-1 text-warning'></i>
                <div>
                    <div class="import-summary">{{ session('warning') }}</div>
                </div>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')) > 0)
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center mb-1">
                <i class='bx bx-error-circle fs-5 me-2 text-warning'></i>
                <strong>{{ __('Baris yang dilewati:') }}</strong>
            </div>
            <ul class="mb-0 ps-3 small">
                @foreach(collect(session('import_errors'))->take(10) as $err)
                    <li>{{ $err }}</li>
                @endforeach
                @if(count(session('import_errors')) > 10)
                    <li>{{ __('… dan :count baris lainnya.', ['count' => count(session('import_errors')) - 10]) }}</li>
                @endif
            </ul>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    <form id="deleteAllStudentsForm" action="{{ panel_route('students.destroy-all') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="confirm_active" value="1">
        <input type="hidden" name="confirm_all" value="1">
    </form>

    <div id="importAlertBox"></div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden md:mb-4 bg-white" id="daftar-siswa">
        <div class="p-3 p-md-4 border-bottom border-gray-100 bg-white">
            <div class="action-bar-section">
                <form method="GET" action="{{ panel_route('students.index') }}" class="action-search-form">
                    <div class="input-group search-box-wrap">
                        <input type="text"
                               name="search"
                               id="studentSearchInput"
                               class="form-control shadow-none"
                               placeholder="{{ __('Cari nama atau NISN...') }}"
                               value="{{ request('search') }}"
                               aria-label="{{ __('Cari nama atau NISN') }}"
                               autocomplete="off"
                               style="font-size: 0.85rem; letter-spacing: 0.03em;">
                        <button class="btn shadow-none" type="submit" title="{{ __('Cari') }}" aria-label="{{ __('Cari') }}">
                            <i class='bx bx-search fs-6'></i>
                        </button>
                    </div>

                    <div class="filter-box-wrap">
                        <select name="class_id" id="studentClassFilter" onchange="this.form.submit()" class="form-select border-secondary-subtle shadow-none fw-normal w-100" style="font-size: 0.82rem; letter-spacing: 0.02em;" aria-label="{{ __('Pilih kelas') }}">
                            <option value="">{{ __('Semua Kelas') }}</option>
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                                {{ __('Kelas ') }}{{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="action-buttons-wrap">
                    <button type="button" class="btn-solid-pill btn-solid-blue btn-cetak-kartu {{ $totalStudents == 0 ? 'is-disabled' : '' }}"
                            data-bs-toggle="modal"
                            data-bs-target="#printCardsModal"
                            {{ $totalStudents == 0 ? 'disabled aria-disabled="true" tabindex="-1" title="Belum ada data untuk dicetak"' : '' }}>
                        <span>{{ __('Cetak Kartu') }}</span>
                    </button>

                    @if(Auth::check() && Auth::user()->role === 'admin')
                    <button type="button" class="btn-solid-pill btn-solid-green" data-bs-toggle="modal" data-bs-target="#importModal">
                        <span>{{ __('Import Excel') }}</span>
                    </button>

                    <button type="button" class="btn-solid-pill btn-solid-blue" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <span>{{ __('Tambah Siswa') }}</span>
                    </button>

                    <button type="button" class="btn-solid-pill btn-solid-red" title="{{ __('Hapus Semua Data Siswa') }}" onclick="confirmDeleteAllStudents()">
                        <span>{{ __('Hapus') }}</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <colgroup>
                    <col style="width: 5%;">
                    <col style="width: 10%;">
                    <col style="width: 15%;">
                    <col style="width: 35%;">
                    <col style="width: 15%;">
                    <col style="width: 20%;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">{{ __('No') }}</th>
                        <th class="text-center">{{ __('Kelas') }}</th>
                        <th class="text-center">{{ __('NISN') }}</th>
                        <th class="text-left">{{ __('Nama Siswa') }}</th>
                        <th class="text-center">{{ __('Jenis Kelamin') }}</th>
                        <th class="text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                    @php
                        $rawJk = strtolower(trim($student->jenis_kelamin ?? $student->gender ?? ''));
                        $isMale = in_array($rawJk, ['laki-laki', 'laki - laki', 'l', 'pria', 'male']);
                    @endphp
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td data-label="{{ __('No') }}" class="text-center">{{ $loop->iteration + ($students->firstItem() ? $students->firstItem() - 1 : 0) }}</td>
                        <td data-label="{{ __('Kelas') }}" class="text-center">{{ $student->schoolClass->name ?? $student->kelas ?? '-' }}</td>
                        <td data-label="{{ __('NISN') }}" class="text-center">{{ $student->nisn ?: '-' }}</td>
                        <td data-label="{{ __('Nama Siswa') }}" class="text-start fw-semibold text-dark">
                            <div class="nama-siswa-wrapper">{{ $student->nama ?? $student->name }}</div>
                        </td>
                        <td data-label="{{ __('Jenis Kelamin') }}" class="text-center">{{ $isMale ? __('Laki-laki') : __('Perempuan') }}</td>
                        <td data-label="{{ __('Aksi') }}" class="text-center">
                            <div class="crud-center-wrapper">
                                <a href="{{ panel_route('students.show', $student->id) }}" class="btn-row-action action-edit" title="{{ __('Lihat / Edit') }}" aria-label="{{ __('Lihat / Edit') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </a>
                                @if(Auth::check() && Auth::user()->role === 'admin')
                                <button type="button" class="btn-row-action action-delete" title="{{ __('Hapus') }}" aria-label="{{ __('Hapus') }}" onclick="confirmDeleteStudent('{{ $student->id }}', '{{ addslashes($student->nama ?? $student->name) }}')">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                                <form id="deleteStudentForm-{{ $student->id }}" action="{{ panel_route('students.destroy', $student->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            @if(request('search') || request('class_id'))
                                {{ __('Tidak ada data siswa yang cocok dengan pencarian.') }}
                            @else
                                {{ __('BELUM ADA DATA SISWA YANG TERDAFTAR.') }}
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @php
            $pgCurrent = $students->currentPage();
            $pgLast = $students->lastPage();
            $pgHash = '#daftar-siswa';

            $pgPages = [];
            if ($pgLast <= 7) {
                $pgPages = range(1, $pgLast);
            } else {
                $pgPages[] = 1;
                $pgStart = max(2, $pgCurrent - 1);
                $pgEnd = min($pgLast - 1, $pgCurrent + 1);
                if ($pgStart > 2) {
                    $pgPages[] = '...';
                }
                for ($i = $pgStart; $i <= $pgEnd; $i++) {
                    $pgPages[] = $i;
                }
                if ($pgEnd < $pgLast - 1) {
                    $pgPages[] = '...';
                }
                $pgPages[] = $pgLast;
            }
        @endphp

        @if ($pgLast > 1)
            <nav class="kehadiran-pagination" id="siswa-pagination" aria-label="{{ __('Navigasi halaman data siswa') }}">
                <ul class="kehadiran-pagination-list">
                    @if ($pgCurrent <= 1)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">&lsaquo; {{ __('Sebelumnya') }}</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $students->previousPageUrl() }}{{ $pgHash }}" rel="prev">&lsaquo; {{ __('Sebelumnya') }}</a></li>
                    @endif

                    @foreach ($pgPages as $pgItem)
                        @if ($pgItem === '...')
                            <li><span class="kehadiran-pagination-ellipsis" aria-hidden="true">&hellip;</span></li>
                        @elseif ($pgItem === $pgCurrent)
                            <li><span class="kehadiran-pagination-page is-active" aria-current="page">{{ $pgItem }}</span></li>
                        @else
                            <li><a class="kehadiran-pagination-page" href="{{ $students->url($pgItem) }}{{ $pgHash }}">{{ $pgItem }}</a></li>
                        @endif
                    @endforeach

                    @if ($pgCurrent >= $pgLast)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">{{ __('Berikutnya') }} &rsaquo;</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $students->nextPageUrl() }}{{ $pgHash }}" rel="next">{{ __('Berikutnya') }} &rsaquo;</a></li>
                    @endif
                </ul>

                <p class="kehadiran-pagination-info">
                    {{ __('Menampilkan') }} {{ $students->firstItem() ?? 0 }}&ndash;{{ $students->lastItem() ?? 0 }}
                    {{ __('dari') }} {{ $students->total() }} {{ __('siswa') }}
                </p>
            </nav>
        @endif
        </div><!-- /.table-responsive : penutup area scroll, pagination ikut di dalam -->
    </div>

<!-- MODAL CETAK KARTU MASSAL -->
<div class="modal fade" id="printCardsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">{{ __('Cetak Kartu Presensi Massal') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ panel_route('students.print-cards') }}" method="POST">
                @csrf
                <div class="modal-body py-3">
                    <p class="text-secondary small mb-3">
                        {{ __('Pilih kartu siswa yang ingin dicetak. Hasil unduhan berupa file ZIP berisi satu kartu (JPG) untuk setiap siswa.') }}
                    </p>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Pilihan Cetak') }} <span class="text-danger">*</span></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="print_type" id="printTypeClass" value="class" checked onchange="togglePrintScope()">
                            <label class="form-check-label fw-medium small" for="printTypeClass">
                                {{ __('Cetak per Kelas') }}
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="print_type" id="printTypeAll" value="all" onchange="togglePrintScope()">
                            <label class="form-check-label fw-medium small" for="printTypeAll">
                                {{ __('Cetak Semua Siswa Aktif') }}
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="classSelectContainer">
                        <label class="form-label small fw-semibold">{{ __('Pilih Kelas') }}</label>
                        <select name="class_id" id="modalClassId" class="form-select rounded-3">
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ __('Kelas ') }}{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-2" id="printInfoBox">
                        <i class='bx bx-info-circle me-1'></i> {{ __('Setiap kartu dilengkapi QR Code presensi siswa yang dibuat otomatis.') }}
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn-download-blue btn-progress px-4" data-download data-dl-progress data-dl-progress-mode="persen" id="kartuUnduhBtn">{{ __('Unduh Kartu') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(is_admin())
<!-- MODAL TAMBAH SISWA -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">{{ __('Tambah Siswa Baru') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addStudentForm" action="{{ panel_route('students.store') }}" method="POST" novalidate data-no-loader>
                @csrf
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Nama Siswa') }} <span class="text-danger">*</span></label>
                        <input id="studentName" type="text" name="name" class="form-control rounded-3 @error('name') is-invalid @enderror" placeholder="{{ __('Masukkan nama lengkap siswa') }}" value="{{ old('name') }}">
                        <div class="field-error">{{ $errors->first('name') }}</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">{{ __('NISN') }} <span class="text-danger">*</span></label>
                            <input id="studentNisn" type="text" name="nisn" class="form-control rounded-3 @error('nisn') is-invalid @enderror" placeholder="{{ __('Contoh: 0081234567') }}" value="{{ old('nisn') }}" inputmode="numeric" maxlength="10" autocomplete="off">
                            <div class="field-error">{{ $errors->first('nisn') }}</div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">{{ __('Kelas') }} <span class="text-danger">*</span></label>
                            <select id="studentKelas" name="school_class_id" class="form-select rounded-3 @error('school_class_id') is-invalid @enderror">
                                <option value="">{{ __('Pilih Kelas') }}</option>
                                @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ old('school_class_id') == $c->id ? 'selected' : '' }}>{{ __('Kelas ') }}{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <div class="field-error">{{ $errors->first('school_class_id') }}</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">{{ __('Jenis Kelamin') }} <span class="text-danger">*</span></label>
                            <select id="studentJk" name="gender" class="form-select rounded-3 @error('gender') is-invalid @enderror">
                                <option value="" {{ old('gender') ? '' : 'selected' }}>{{ __('Pilih jenis kelamin') }}</option>
                                <option value="Laki-laki" {{ old('gender') == 'Laki-laki' ? 'selected' : '' }}>{{ __('Laki-laki') }}</option>
                                <option value="Perempuan" {{ old('gender') == 'Perempuan' ? 'selected' : '' }}>{{ __('Perempuan') }}</option>
                            </select>
                            <div class="field-error">{{ $errors->first('gender') }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button id="btnSaveStudent" type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold" disabled>{{ __('Simpan Siswa') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL IMPORT EXCEL -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom-0">
                <h5 class="fw-bold mb-0">{{ __('Import Data Siswa Excel') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="studentImportForm" action="{{ panel_route('students.import') }}" method="POST" enctype="multipart/form-data" data-no-loader>
                @csrf
                <div class="modal-body">
                    @if($errors->any() && $errors->has('file_excel'))
                    <div class="alert alert-danger py-2 px-3 small mb-3 border-0 rounded-3" role="alert">
                        <i class='bx bx-error-circle me-1'></i> {{ __('Data belum bisa disimpan. Periksa isian yang bertanda merah.') }}
                    </div>
                    @endif

                    <div class="p-3 bg-white border rounded-3 small text-secondary mb-3">
                        <div class="mb-2">
                            {{ __('Format kolom file Excel:') }} <strong>{{ __('NISN, Nama Lengkap, Kelas, Jenis Kelamin') }}</strong> {{ __('(.xlsx atau .csv)') }}
                        </div>
                        <a href="{{ panel_route('students.template') }}" class="btn-download-green w-100" data-download data-no-download data-dl-progress>{{ __('Unduh Template Excel') }}</a>
                    </div>
                    <label class="form-label small fw-semibold">{{ __('Pilih File Excel') }}</label>
                    <input type="file" name="file_excel" class="form-control rounded-3 @error('file_excel') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                    @error('file_excel')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn-download-green px-4" id="btnImportSubmit" disabled>{{ __('Unggah & Import') }}</button>
                </div>
                <div id="importMessage" class="px-3 pb-3 small d-none" style="min-height:1.2rem;"></div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
@push('scripts')
<script>
console.log('[import-handler] aktif');
(function () {
  var form = document.getElementById('studentImportForm');
  var btn = document.getElementById('btnImportSubmit');
  if (!form || !btn) return;
  var file = form.querySelector('input[type="file"]');
  function sync() { btn.disabled = !(file.files && file.files.length); }
  file.addEventListener('change', sync);
  document.addEventListener('click', function () { setTimeout(sync, 0); });
  sync();

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    if (btn.disabled) return;
    var original = btn.innerHTML;
    btn.disabled = true;
    btn.style.position = 'relative';
    btn.style.overflow = 'hidden';
    btn.innerHTML = '<span class="imp-bar" style="position:absolute;left:0;top:0;bottom:0;width:0%;background:rgba(0,0,0,.25);transition:width .15s"></span><span class="imp-txt" style="position:relative">0%</span>';
    var bar = btn.querySelector('.imp-bar'), txt = btn.querySelector('.imp-txt');
    var shown = 0, real = 0, done = false;
    var timer = setInterval(function () {
      var target = done ? 100 : Math.max(real, Math.min(shown + 1, 92));
      if (shown < target) {
        shown++;
        bar.style.width = shown + '%';
        txt.textContent = shown + '%';
      }
      if (shown >= 100) {
        clearInterval(timer);
        setTimeout(function () { location.reload(); }, 400);
      }
    }, 40);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', form.action);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.upload.onprogress = function (ev) {
      if (!ev.lengthComputable) return;
      real = Math.min(90, Math.round(ev.loaded / ev.total * 90));
    };
    xhr.onload = function () {
      var json = {};
      try { json = JSON.parse(xhr.responseText); } catch (_) {}
      sessionStorage.setItem('importAlert', JSON.stringify({
        status: json.status || (xhr.status < 400 ? 'success' : 'error'),
        msg: json.message || (xhr.status < 400 ? 'Import selesai.' : 'Import gagal. Periksa file lalu coba lagi.'),
        details: json.details || []
      }));
      done = true;
    };
    xhr.onerror = function () {
      clearInterval(timer);
      btn.innerHTML = original; sync();
      alert('Import gagal. Periksa koneksi lalu coba lagi.');
    };
    xhr.send(new FormData(form));
  }, true);

  var modal = document.getElementById('importModal');
  if (modal) modal.addEventListener('hidden.bs.modal', function () { form.reset(); sync(); });
})();
(function () {
  var box = document.getElementById('importAlertBox');
  if (!box) return;
  var raw = sessionStorage.getItem('importAlert');
  if (!raw) return;
  sessionStorage.removeItem('importAlert');
  var data = {};
  try { data = JSON.parse(raw) || {}; } catch (e) { data = {}; }
  var status = data.status || 'error';
  var msg = data.msg || data.message || '';
  var details = Array.isArray(data.details) ? data.details : [];
  if (!msg && !details.length) return;
  var palette = {
    success: { bg: '#dcfce7', fg: '#166534', icon: 'bx-check-circle' },
    partial: { bg: '#fef3c7', fg: '#854d0e', icon: 'bx-error-circle' },
    error: { bg: '#fee2e2', fg: '#991b1b', icon: 'bx-x-circle' }
  };
  var color = palette[status] || palette.error;
  var alertEl = document.createElement('div');
  alertEl.className = 'alert alert-dismissible fade show border-0 rounded-3 small mb-3';
  alertEl.setAttribute('role', 'alert');
  alertEl.style.backgroundColor = color.bg;
  alertEl.style.color = color.fg;
  alertEl.style.maxHeight = 'none';
  alertEl.style.overflow = 'visible';
  var head = document.createElement('div');
  head.className = 'd-flex align-items-start';
  var icon = document.createElement('i');
  icon.className = 'bx ' + color.icon + ' fs-5 me-2 mt-1';
  var textDiv = document.createElement('div');
  var summary = document.createElement('div');
  summary.className = 'import-summary';
  summary.textContent = msg;
  textDiv.appendChild(summary);
  head.appendChild(icon);
  head.appendChild(textDiv);
  alertEl.appendChild(head);
  if (details.length) {
    var list = document.createElement('ul');
    list.className = 'mb-0 mt-2 ps-3 small';
    details.forEach(function (item) {
      var li = document.createElement('li');
      li.textContent = String(item);
      list.appendChild(li);
    });
    alertEl.appendChild(list);
  }
  var closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.className = 'btn-close';
  closeBtn.setAttribute('data-bs-dismiss', 'alert');
  closeBtn.setAttribute('aria-label', 'Tutup');
  closeBtn.addEventListener('click', function () { alertEl.remove(); });
  alertEl.appendChild(closeBtn);
  box.appendChild(alertEl);
})();
</script>
<script>
(function () {
    console.log('psit: import handler memo dipasang untuk', document.getElementById('importModal') ? 'tampak' : 'tidak');
})();
/* ===== Cetak Kartu: toggle pilihan cetak per kelas / semua ===== */
    function downloadProgressNoteFromSource() {
        var target = document.getElementById('kartuUnduhBtn');
        if (!target) return;
        var bagianChar = target.querySelector('.btn-progress__fill');
        var labelChar = target.querySelector('.btn-progress__label');
        console.log('psit: cetak kartu', target, 'fill', bagianChar, 'label', labelChar);
    }
    downloadProgressNoteFromSource();
    function togglePrintScope() {
        const rbClass = document.getElementById('printTypeClass');
        const rbAll = document.getElementById('printTypeAll');
        const classContainer = document.getElementById('classSelectContainer');
        const classSelect = document.getElementById('modalClassId');

        if (rbClass && rbClass.checked) {
            if (classContainer) classContainer.style.display = '';
            if (classSelect) classSelect.disabled = false;
        } else {
            if (classContainer) classContainer.style.display = 'none';
            if (classSelect) classSelect.disabled = true;
        }
    }

    /* Pesan error kecil di bawah field (clear + set). */
    function setFieldError(field, msg) {
        if (!field) return;
        const msgEl = field.parentNode.querySelector('.field-error');
        if (!msgEl) return;
        if (!msg) {
            msgEl.textContent = '';
            field.classList.remove('is-invalid');
            field.style.borderColor = '';
            return;
        }
        msgEl.textContent = msg;
        field.classList.add('is-invalid');
        field.style.borderColor = '#dc2626';
    }

    /* --- Validasi UI tombolTambahSiswa (scope btnSaveStudent) --- */
    if (false) (function () {
        var btn = document.getElementById('btnSaveStudent');
        if (!btn) return;
        var form = btn.closest('form');
        if (!form) return;
        var nameField = form.querySelector('[name="name"]');
        var nisnField = form.querySelector('[name="nisn"]');
        var kelasField = form.querySelector('[name="school_class_id"]');
        var jkField = form.querySelector('[name="gender"]');

        function valid() {
            var namaVal = (nameField && nameField.value ? nameField.value : '').trim();
            var nisnVal = (nisnField && nisnField.value ? nisnField.value : '').trim();
            var kelasVal = (kelasField && kelasField.value ? kelasField.value : '');
            var jkVal = (jkField && jkField.value ? jkField.value : '');
            var namaOk = !!namaVal && /^[À-￿a-zA-Z\s.,'\-]+$/u.test(namaVal);
            var nisnOk = !!nisnVal && /^\d+$/.test(nisnVal);
            return namaOk && nisnOk && !!kelasVal && !!jkVal;
        }

        function setButton() {
            btn.disabled = !valid();
        }

        function attach(el, evt, fn) {
            if (!el) return;
            if (el.addEventListener) {
                el.addEventListener(evt, fn, false);
            } else if (el.attachEvent) {
                el.attachEvent('on' + evt, fn);
            }
        }

        attach(nameField, 'input', setButton);
        attach(nameField, 'change', setButton);
        attach(nisnField, 'input', setButton);
        attach(nisnField, 'change', setButton);
        attach(kelasField, 'input', setButton);
        attach(kelasField, 'change', setButton);
        attach(jkField, 'input', setButton);
        attach(jkField, 'change', setButton);

        form.addEventListener('reset', function () {
            setTimeout(setButton, 0);
        });

        form.addEventListener('submit', function (e) {
            if (!valid()) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });

        document.addEventListener('click', function () {
            setTimeout(setButton, 0);
        });

        setButton();
    })();

    /* ===== Validasi form Tambah Siswa (JS murni, tanpa jQuery). */
    function cekValiditasSiswa(showErrors) {
        const name = document.getElementById('studentName');
        const nisn = document.getElementById('studentNisn');
        const kelas = document.getElementById('studentKelas');
        const jk = document.getElementById('studentJk');
        const btn = document.getElementById('btnSaveStudent');
        let ok = true;

        function apply(field, message) {
            if (showErrors || !message) {
                setFieldError(field, message);
            }
        }

        const nama = (name ? name.value : '').trim();
        if (nama === '') {
            apply(name, 'Nama wajib diisi.');
            ok = false;
        } else if (!/^[\p{L}\s.,'\-]+$/u.test(nama)) {
            apply(name, 'Nama hanya boleh huruf.');
            ok = false;
        } else {
            apply(name, '');
        }

        const nisnRaw = (nisn ? nisn.value : '').trim();
        if (!/^\d{10}$/.test(nisnRaw)) {
            apply(nisn, 'NISN harus 10 digit angka.');
            ok = false;
        } else {
            apply(nisn, '');
        }

        const kelasVal = (kelas ? kelas.value : '').trim();
        if (kelasVal === '') {
            apply(kelas, 'Pilih kelas.');
            ok = false;
        } else {
            apply(kelas, '');
        }

        const jkVal = (jk ? jk.value : '').trim();
        if (jkVal === '') {
            apply(jk, 'Pilih jenis kelamin.');
            ok = false;
        } else {
            apply(jk, '');
        }

        return ok;
    }

    /* Tombol Simpan aktif bila keempat kolom terisi (non-kosong), tanpa cek format. */
    function updateTombolSimpan() {
        var btn = document.getElementById('btnSaveStudent');
        if (!btn || btn.dataset.saving === '1') return;
        var nama = (document.getElementById('studentName') && document.getElementById('studentName').value) ? document.getElementById('studentName').value.trim() : '';
        var nisn = (document.getElementById('studentNisn') && document.getElementById('studentNisn').value) ? document.getElementById('studentNisn').value.trim() : '';
        var kelas = (document.getElementById('studentKelas') && document.getElementById('studentKelas').value) ? document.getElementById('studentKelas').value.trim() : '';
        var jk = (document.getElementById('studentJk') && document.getElementById('studentJk').value) ? document.getElementById('studentJk').value.trim() : '';
        var ok = nama !== '' && nisn !== '' && kelas !== '' && jk !== '';
        btn.disabled = !ok;
        btn.setAttribute('aria-disabled', ok ? 'false' : 'true');
    }

    /* Hapus angka saat mengetik di NISN; kursor terjaga. */
    function sanitizeNisnInput(e) {
        const el = e.target;
        if (!el || el.id !== 'studentNisn') return;
        if (e.inputType === 'insertFromPaste' || e.inputType === 'insertFromDrop' || e.inputType === 'insertReplacementText') {
            const cleaned = el.value.replace(/[^0-9]/g, '').slice(0, 10);
            const start = el.selectionStart;
            const prevLen = el.value.length;
            el.value = cleaned;
            const newLen = el.value.length;
            const keepStart = Math.min(start, newLen);
            el.setSelectionRange(keepStart, keepStart);
            return;
        }
        if (e.inputType === 'insertText' || e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward' || !e.inputType) {
            const before = el.value;
            let cleaned = before.replace(/[^0-9]/g, '').slice(0, 10);
            if (cleaned !== before) {
                const start = el.selectionStart;
                const prevLen = before.length;
                el.value = cleaned;
                const newLen = el.value.length;
                let newStart = Math.min(start, newLen);
                if (e.inputType === 'insertText' && e.data) {
                    const digit = String(e.data).replace(/[^0-9]/g, '');
                    if (digit && start < prevLen && /[^0-9]/.test(before[start])) {
                        newStart = start;
                    }
                }
                el.setSelectionRange(newStart, newStart);
            }
        }
    }

    /* Hapus angka di nama saat mengetik/paste. Kursor terjaga. */
    function sanitizeNameInput(e) {
        const el = e.target;
        if (!el || el.id !== 'studentName') return;
        const before = el.value;
        const cleaned = before.replace(/\p{Nd}/gu, '');
        if (cleaned !== before) {
            const start = el.selectionStart;
            const prevLen = before.length;
            el.value = cleaned;
            let newStart = Math.min(start, el.value.length);
            if (e.inputType === 'insertText' && e.data) {
                const badRun = String(e.data).match(/\p{Nd}+/u);
                if (badRun && start <= prevLen) {
                    newStart = Math.min(start, el.value.length);
                }
            }
            if (e.inputType === 'insertFromPaste' || e.inputType === 'insertFromDrop' || e.inputType === 'insertReplacementText') {
                newStart = Math.min(start, el.value.length);
            }
            el.setSelectionRange(newStart, newStart);
        }
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str == null ? '' : String(str)));
        return div.innerHTML;
    }

    /* ===== Konfirmasi hapus SATU siswa ===== */
    function confirmDeleteStudent(id, name) {
        confirmUniversalDelete({
            title: {!! json_encode(__('Hapus Data Siswa?')) !!},
            html: {!! json_encode(__('Siswa')) !!} + ' <strong>' + escapeHtml(name) + '</strong> ' + {!! json_encode(__('akan dihapus. Data bisa dipulihkan dari Tempat Sampah di Pengaturan.')) !!},
            confirmText: {!! json_encode(__('Hapus')) !!},
            cancelText: {!! json_encode(__('Tidak')) !!},
            onConfirm: function () {
                var form = document.getElementById('deleteStudentForm-' + id);
                if (!form) {
                    console.error('HAPUS: form deleteStudentForm-' + id + ' tidak ditemukan. Muat ulang halaman.');
                    return;
                }
                var qs = window.location.search;
                if (qs && form.action.indexOf('?') === -1) {
                    form.action = form.action + qs;
                }
                form.submit();
            }
        });
    }

    /* ===== Konfirmasi hapus SEMUA siswa ===== */
    function confirmDeleteAllStudents() {
        if (typeof confirmUniversalDelete === 'function') {
            confirmUniversalDelete({
                title: {!! json_encode(__('Pindahkan SEMUA siswa aktif ke Tempat Sampah?')) !!},
                icon: 'bx-error-circle',
                html: {!! json_encode(__('Seluruh data siswa aktif akan dihapus dan dipindahkan ke')) !!} + ' <strong>' + {!! json_encode(__('Tempat Sampah')) !!} + '</strong> ' + {!! json_encode(__('di Pengaturan.')) !!},
                confirmText: {!! json_encode(__('Hapus Semua')) !!},
                cancelText: {!! json_encode(__('Tidak')) !!},
                checks: [
                    {!! json_encode(__('Saya memahami data akan dipindahkan ke Tempat Sampah dan dapat dipulihkan.')) !!},
                    {!! json_encode(__('Saya yakin ingin menghapus semua data siswa aktif.')) !!}
                ],
                onConfirm: function () {
                    document.getElementById('deleteAllStudentsForm').submit();
                }
            });
        } else if (confirm({!! json_encode(__('Pindahkan SEMUA siswa aktif ke Tempat Sampah?')) !!})) {
            document.getElementById('deleteAllStudentsForm').submit();
        }
    }

    /* ===== Handler form Tambah Siswa ===== */
    (function () {
        var nameEl = document.getElementById('studentName');
        var nisnEl = document.getElementById('studentNisn');
        var kelasEl = document.getElementById('studentKelas');
        var jkEl = document.getElementById('studentJk');
        var form = document.getElementById('addStudentForm');

        function attach(el, evt, fn) {
            if (!el) return;
            if (el.addEventListener) {
                el.addEventListener(evt, fn, false);
            } else if (el.attachEvent) {
                el.attachEvent('on' + evt, fn);
            }
        }

        /* Sinkronisasi status tombol saat modal/dokumen berubah. */
        var btnUi = document.getElementById('btnSaveStudent');
        if (false) {
            function sync() { try { btnUi.disabled = !valid(); } catch (e) {} }
            var formUi = btnUi.closest('form');
            if (formUi) {
                var nameUi = formUi.querySelector('[name="name"]');
                var nisnUi = formUi.querySelector('[name="nisn"]');
                var kelasUi = formUi.querySelector('[name="school_class_id"]');
                var jkUi = formUi.querySelector('[name="gender"]');
                function valid() {
                    var nv = (nameUi && nameUi.value ? nameUi.value : '').trim();
                    var iv = (nisnUi && nisnUi.value ? nisnUi.value : '').trim();
                    var kv = (kelasUi && kelasUi.value ? kelasUi.value : '');
                    var gv = (jkUi && jkUi.value ? jkUi.value : '');
                    var nOk = !!nv && /^[À-￿a-zA-Z\s.,'\-]+$/u.test(nv);
                    var iOk = !!iv && /^\d+$/.test(iv);
                    return nOk && iOk && !!kv && !!gv;
                }
                attach(nameUi, 'input', sync);
                attach(nameUi, 'change', sync);
                attach(nisnUi, 'input', sync);
                attach(nisnUi, 'change', sync);
                attach(kelasUi, 'input', sync);
                attach(kelasUi, 'change', sync);
                attach(jkUi, 'input', sync);
                attach(jkUi, 'change', sync);
                formUi.addEventListener('reset', function () { setTimeout(sync, 0); });
            }
            sync();
        }

        attach(nisnEl, 'input', sanitizeNisnInput);
        attach(nisnEl, 'beforeinput', function (e) {
            if (e && e.inputType === 'insertFromPaste' || e && e.inputType === 'insertFromDrop' || e && e.inputType === 'insertReplacementText') {
                sanitizeNisnInput(e);
            }
        });
        attach(nameEl, 'input', sanitizeNameInput);
        attach(nameEl, 'beforeinput', sanitizeNameInput);

        function onSelectionChange() {
            cekValiditasSiswa();
        }

        [nameEl, nisnEl, kelasEl, jkEl].forEach(function (el) {
            if (!el) return;
            attach(el, 'input', function () {
                setFieldError(el, '');
                updateTombolSimpan();
            });
            attach(el, 'change', function () {
                setFieldError(el, '');
                updateTombolSimpan();
            });
            attach(el, 'blur', function () {
                setFieldError(el, '');
                updateTombolSimpan();
            });
        });

        var btnSubmit = document.getElementById('btnSaveStudent');
        var originalBtnHtml = btnSubmit ? btnSubmit.innerHTML : '';

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if (!cekValiditasSiswa(true)) {
                var firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) firstInvalid.focus();
                return;
            }

            if (btnSubmit) {
                btnSubmit.dataset.saving = '1';
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...';
            }

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                redirect: 'manual'
            })
            .then(function (response) {
                if (response.status === 422) {
                    return response.json().then(function (data) {
                        var errors = data.errors || {};
                        Object.keys(errors).forEach(function (key) {
                            var field = form.querySelector('[name="' + key + '"]');
                            if (field) setFieldError(field, errors[key][0]);
                        });
                        if (btnSubmit) {
                            delete btnSubmit.dataset.saving;
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = originalBtnHtml;
                        }
                        var firstInvalid = form.querySelector('.is-invalid');
                        if (firstInvalid) firstInvalid.focus();
                    });
                }
                if (response.type === 'opaqueredirect' || response.status === 0) {
                    window.location.reload();
                    return;
                }
                if (!response.ok) {
                    return response.json().catch(function () { return {}; }).then(function () {
                        if (btnSubmit) {
                            delete btnSubmit.dataset.saving;
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = originalBtnHtml;
                        }
                        alert('Gagal menyimpan data. Coba lagi.');
                    });
                }
                window.location.reload();
            })
            .catch(function () {
                if (btnSubmit) {
                    delete btnSubmit.dataset.saving;
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalBtnHtml;
                }
                alert('Gagal menyimpan data. Periksa koneksi lalu coba lagi.');
            });
        }, true);

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            document.addEventListener('show.bs.modal', function (e) {
                var modal = e.target;
                if (modal && (modal.id === 'addStudentModal' || (modal.querySelector && modal.querySelector('#addStudentForm')))) {
                    setFieldError(document.getElementById('studentName'), '');
                    setFieldError(document.getElementById('studentNisn'), '');
                    setFieldError(document.getElementById('studentKelas'), '');
                    setFieldError(document.getElementById('studentJk'), '');
                    cekValiditasSiswa();
                    updateTombolSimpan();
                }
            });

            document.addEventListener('hidden.bs.modal', function (e) {
                var modal = e.target;
                if (modal && (modal.id === 'addStudentModal' || (modal.querySelector && modal.querySelector('#addStudentForm')))) {
                    var formEl = document.getElementById('addStudentForm');
                    if (formEl) {
                        var saved = formEl.saveStudentFormSnapshot;
                        if (saved) {
                            formEl.reset();
                            if (saved.name) document.getElementById('studentName').value = saved.name;
                            if (saved.nisn) document.getElementById('studentNisn').value = saved.nisn;
                            if (saved.school_class_id) document.getElementById('studentKelas').value = saved.school_class_id;
                            if (saved.gender) document.getElementById('studentJk').value = saved.gender;
                        }
                        cekValiditasSiswa();
                    }
                }
            });
        }

        var addStudentFormEl = document.getElementById('addStudentForm');
        if (addStudentFormEl) {
            var _oldReset = addStudentFormEl.reset;
            addStudentFormEl.reset = function () {
                this.saveStudentFormSnapshot = {
                    name: (document.getElementById('studentName') && document.getElementById('studentName').value) || '',
                    nisn: (document.getElementById('studentNisn') && document.getElementById('studentNisn').value) || '',
                    school_class_id: (document.getElementById('studentKelas') && document.getElementById('studentKelas').value) || '',
                    gender: (document.getElementById('studentJk') && document.getElementById('studentJk').value) || ''
                };
                _oldReset.call(this);
            };
        }

        cekValiditasSiswa();
    })();

    /* ===== Import Siswa via AJAX dengan overlay loading ===== */
    (function () {
        var form = document.getElementById('importForm');
        if (!form) return;

        var btn = form.querySelector('button[type="submit"]');
        var alertBox = document.getElementById('importAlertBox');

        var loadingOverlay = null;

        function escapeHtml(str) {
            var div = document.createElement('div');
            var txt = document.createTextNode(str);
            div.appendChild(txt);
            return div.innerHTML;
        }

        function findCsrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute('content')) return meta.getAttribute('content');
            var input = document.querySelector('input[name="_token"]');
            if (input && input.value) return input.value;
            return '';
        }

        function showLoadingOverlay() {
            if (loadingOverlay) return;
            loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'importLoading';
            loadingOverlay.innerHTML = '<div class="import-loading-card"><div class="import-loading-spinner"></div><p>Memproses import...</p></div>';
            if (!loadingOverlay.style.position || loadingOverlay.style.position === '') {
                loadingOverlay.style.position = 'fixed';
            }
            if (!loadingOverlay.style.inset || loadingOverlay.style.inset === '') {
                loadingOverlay.style.top = '0';
                loadingOverlay.style.right = '0';
                loadingOverlay.style.bottom = '0';
                loadingOverlay.style.left = '0';
            }
            loadingOverlay.style.zIndex = '99999';
            loadingOverlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.alignItems = 'center';
            loadingOverlay.style.justifyContent = 'center';
            if (loadingOverlay.firstChild) {
                loadingOverlay.firstChild.style.background = '#ffffff';
                loadingOverlay.firstChild.style.borderRadius = '0.5rem';
                loadingOverlay.firstChild.style.padding = '1rem 1.5rem';
                loadingOverlay.firstChild.style.boxShadow = '0 10px 30px rgba(0,0,0,0.25)';
                loadingOverlay.firstChild.style.textAlign = 'center';
                loadingOverlay.firstChild.style.fontSize = '0.9rem';
            }
            var spinnerEl = loadingOverlay.querySelector('.import-loading-spinner');
            if (spinnerEl) {
                spinnerEl.style.width = '24px';
                spinnerEl.style.height = '24px';
                spinnerEl.style.border = '3px solid #e5e7eb';
                spinnerEl.style.borderTopColor = '#2563eb';
                spinnerEl.style.borderRadius = '50%';
                spinnerEl.style.animation = 'spin 0.8s linear infinite';
                spinnerEl.style.display = 'inline-block';
                spinnerEl.style.marginBottom = '0.5rem';
            }
            document.body.appendChild(loadingOverlay);
        }

        function hideLoadingOverlay() {
            if (loadingOverlay) {
                loadingOverlay.remove();
                loadingOverlay = null;
            }
        }

        function showAlert(status, message, details) {
            if (!alertBox) return;
            alertBox.innerHTML = '';
            if (!message) return;

            var colorMap = {
                success: { bg: '#d3f4fb', text: '#0b5394', icon: 'bs-check-circle', textColor: '#0b5394' },
                partial: { bg: '#fef3c7', text: '#854d0e', icon: 'bs-x-circle', textColor: '#854d0e' },
                error: { bg: '#fee2e2', text: '#991b1b', icon: 'bs-x-circle', textColor: '#991b1b' }
            };
            var style = colorMap[status] || colorMap.error;

            var alert = document.createElement('div');
            alert.className = 'alert alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3';
            alert.setAttribute('role', 'alert');
            alert.style.backgroundColor = style.bg;
            alert.style.color = style.text;

            var body = document.createElement('div');
            body.className = 'flash-notice-body';

            var top = document.createElement('div');
            top.className = 'd-flex align-items-center';
            top.innerHTML = '<i class="bx ' + style.icon + ' fs-5 me-2" style="color:' + style.text + '"></i>' +
                '<span>' + escapeHtml(message) + '</span>';
            body.appendChild(top);

            if (details && details.length > 0) {
                var ul = document.createElement('ul');
                ul.className = 'mb-0 ps-3 small mt-1';
                for (var i = 0; i < details.length; i++) {
                    var li = document.createElement('li');
                    li.innerHTML = escapeHtml(details[i]);
                    ul.appendChild(li);
                }
                body.appendChild(ul);
            }

            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close btn-close-sm';
            closeBtn.setAttribute('data-bs-dismiss', 'alert');
            closeBtn.setAttribute('aria-label', 'Tutup');
            closeBtn.innerHTML = '<span aria-hidden="true">&times;</span>';
            alert.appendChild(body);
            alert.appendChild(closeBtn);
            alertBox.appendChild(alert);

            try {
                var bsAlert = (typeof bootstrap !== 'undefined' && bootstrap.Alert) ? bootstrap.Alert : null;
                if (bsAlert) {
                    new bsAlert(alert);
                }
            } catch (e) {}
        }

        function readImportAlertFromStorage() {
            try {
                var raw = sessionStorage.getItem('importAlert_legacy');
                if (!raw) return null;
                var parsed = JSON.parse(raw);
                sessionStorage.removeItem('importAlert_legacy');
                return parsed;
            } catch (e) {
                return null;
            }
        }

        function renderStoredAlert() {
            var stored = readImportAlertFromStorage();
            if (!stored) return;
            var status = stored.status || 'error';
            var message = stored.message || '';
            var details = Array.isArray(stored.details) ? stored.details : [];
            showAlert(status, message, details);
        }
    })();

    (function () {
        document.addEventListener('submit', async function (e) {
            return;
            var form = e.target;
            if (!form || !form.querySelector || !form.querySelector('input[type="file"]')) return;
            if (!/import/i.test(form.action || '')) return;
            e.preventDefault();
            e.stopImmediatePropagation();

            var fileInput = form.querySelector('input[type="file"]');
            if (!fileInput.files || !fileInput.files.length) {
                alert('Pilih file Excel terlebih dahulu.');
                return;
            }
            var data = new FormData(form);

            // 1) SEMBUNYIKAN MODAL: naik dari form sampai elemen position:fixed (pembungkus modal)
            var el = form, hidden = [];
            while (el && el !== document.body) {
                var cs = getComputedStyle(el);
                if (cs.position === 'fixed') { el.style.setProperty('display', 'none', 'important'); hidden.push(el); }
                el = el.parentElement;
            }
            document.querySelectorAll('.modal-backdrop').forEach(function (b) { b.remove(); });

            // 2) TAMPILKAN LOADER
            var ov = document.createElement('div');
            ov.id = 'importLoading';
            ov.style.cssText = 'position:fixed;inset:0;z-index:2147483647;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;';
            ov.innerHTML = '<div style="background:#fff;border-radius:16px;padding:28px 36px;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,.25)">'
              + '<div style="width:44px;height:44px;margin:0 auto 14px;border:4px solid #e2e8f0;border-top-color:#059669;border-radius:50%;animation:impspin .8s linear infinite"></div>'
              + '<div style="font-weight:600;color:#0f172a">Memproses import...</div></div>'
              + '<style>@keyframes impspin{to{transform:rotate(360deg)}}</style>';
            document.body.appendChild(ov);

            // 3) KIRIM VIA FETCH
            try {
                var res = await fetch(form.action, {
                    method: 'POST', body: data,
                    headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
                });
                var json = {};
                try { json = await res.json(); } catch (_) {}
                sessionStorage.setItem('importAlert_legacy', JSON.stringify({
                    status: json.status || (res.ok ? 'success' : 'error'),
                    message: json.message || (res.ok ? 'Import selesai.' : 'Import gagal. Periksa file lalu coba lagi.'),
                    details: json.details || []
                }));
                location.reload();
            } catch (err) {
                ov.remove();
                hidden.forEach(function (h) { h.style.removeProperty('display'); });
                alert('Import gagal. Periksa koneksi lalu coba lagi.');
            }
        }, true);

        /* Baca notifikasi import dari sessionStorage saat halaman dimuat. */
        (function () {
            var alertBox = document.getElementById('importAlertBox');
            if (!alertBox) {
                alertBox = document.createElement('div');
                alertBox.id = 'importAlertBox';
                var antes = document.querySelector('.d-flex.justify-content-between.align-items-center');
                if (antes && antes.parentNode) antes.parentNode.insertBefore(alertBox, antes);
                else {
                    var wrap = document.querySelector('.modal-content > .modal-footer');
                    if (wrap) wrap.parentNode.insertBefore(alertBox, wrap);
                }
            }

            function escapeHtml(str) {
                if (!str) return '';
                return str.toString().replace(/[&<>"']/, function (ch) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
                });
            }

            function showAlert(status, message) {
                if (!alertBox) return;
                alertBox.innerHTML = '';
                var kolor = status === 'success' ? 'bg-success text-success' : status === 'partial' ? 'bg-warning text-warning' : 'bg-danger text-danger';
                var ikon = status === 'success' ? 'check-circle' : status === 'partial' ? 'alert-circle' : 'x-circle';
                var label = status === 'success' ? 'Berhasil' : status === 'partial' ? 'Sebagian' : 'Gagal';
                alertBox.className = 'alert alert-dismissible fade show alert-box ' + kolor;
                alertBox.setAttribute('role', 'alert');
                alertBox.innerHTML = '<button type="button" class="close" data-bs-dismiss="alert" aria-label="Tutup">' +
                    '<span aria-hidden="true">&times;</span></button>' +
                    '<strong>' + label + '. </strong><span>' + escapeHtml(message) + '</span>';
            }

            function readImportAlert() {
                try {
                    var raw = sessionStorage.getItem('importAlert_legacy');
                    if (!raw) return null;
                    var parsed = JSON.parse(raw);
                    sessionStorage.removeItem('importAlert_legacy');
                    return parsed;
                } catch (e) {
                    return null;
                }
            }

            var stored = readImportAlert();
            if (stored) {
                var status = stored.status || 'error';
                var message = stored.message || '';
                showAlert(status, message);
            }
        })();
    })();

    (function () {
        var alertBox = document.getElementById('importAlertBox');
        if (!alertBox) return;

    /* ===== Import Data Siswa Excel: handler global (capture), loader layar penuh ===== */
    (function () {
        document.addEventListener('submit', async function (e) {
            return;
            var form = e.target;
            if (!form || !form.querySelector || !form.querySelector('input[type="file"]')) return;
            if (!/import/i.test(form.action || '')) return;
            e.preventDefault();
            e.stopImmediatePropagation();

            var fileInput = form.querySelector('input[type="file"]');
            if (!fileInput.files || !fileInput.files.length) {
                alert('Pilih file Excel terlebih dahulu.');
                return;
            }
            var data = new FormData(form);

            // 1) SEMBUNYIKAN MODAL: naik dari form sampai elemen position:fixed (pembungkus modal)
            var el = form, hidden = [];
            while (el && el !== document.body) {
                var cs = getComputedStyle(el);
                if (cs.position === 'fixed') { el.style.setProperty('display', 'none', 'important'); hidden.push(el); }
                el = el.parentElement;
            }
            document.querySelectorAll('.modal-backdrop').forEach(function (b) { b.remove(); });

            // 2) TAMPILKAN LOADER
            var ov = document.createElement('div');
            ov.id = 'importLoading';
            ov.style.cssText = 'position:fixed;inset:0;z-index:2147483647;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;';
            ov.innerHTML = '<div style="background:#fff;border-radius:16px;padding:28px 36px;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,.25)">'
              + '<div style="width:44px;height:44px;margin:0 auto 14px;border:4px solid #e2e8f0;border-top-color:#059669;border-radius:50%;animation:impspin .8s linear infinite"></div>'
              + '<div style="font-weight:600;color:#0f172a">Memproses import...</div></div>'
              + '<style>@keyframes impspin{to{transform:rotate(360deg)}}</style>';
            document.body.appendChild(ov);

            // 3) KIRIM VIA FETCH
            try {
                var res = await fetch(form.action, {
                    method: 'POST', body: data,
                    headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
                });
                var json = {};
                try { json = await res.json(); } catch (_) {}
                sessionStorage.setItem('importAlert_legacy', JSON.stringify({
                    status: json.status || (res.ok ? 'success' : 'error'),
                    message: json.message || (res.ok ? 'Import selesai.' : 'Import gagal. Periksa file lalu coba lagi.'),
                    details: json.details || []
                }));
                location.reload();
            } catch (err) {
                ov.remove();
                hidden.forEach(function (h) { h.style.removeProperty('display'); });
                alert('Import gagal. Periksa koneksi lalu coba lagi.');
            }
        }, true);

        /* Baca notifikasi import dari sessionStorage saat halaman dimuat. */
        (function () {
            var alertBox = document.getElementById('importAlertBox');
            if (!alertBox) {
                alertBox = document.createElement('div');
                alertBox.id = 'importAlertBox';
                if (document.getElementById('daftar-siswa')) {
                    document.getElementById('daftar-siswa').parentNode.insertBefore(alertBox, document.getElementById('daftar-siswa'));
                } else {
                    document.body.prepend(alertBox);
                }
            }

            function escapeHtml(str) {
                var div = document.createElement('div');
                var txt = document.createTextNode(str);
                div.appendChild(txt);
                return div.innerHTML;
            }

            function showAlert(status, message, details) {
                if (!alertBox) return;
                alertBox.innerHTML = '';
                if (!message) return;

                var colorMap = {
                    success: { bg: '#dcfce7', text: '#166534', icon: 'bx-check-circle', textColor: '#166534' },
                    partial: { bg: '#fef9c3', text: '#854d0e', icon: 'bx-error-circle', textColor: '#854d0e' },
                    error: { bg: '#fee2e2', text: '#991b1b', icon: 'bx-x-circle', textColor: '#991b1b' }
                };
                var style = colorMap[status] || colorMap.error;

                var alert = document.createElement('div');
                alert.className = 'alert alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3';
                alert.setAttribute('role', 'alert');
                alert.style.backgroundColor = style.bg;
                alert.style.color = style.text;

                var body = document.createElement('div');
                body.className = 'flash-notice-body';

                var top = document.createElement('div');
                top.className = 'd-flex align-items-center';
                top.innerHTML = '<i class="bx ' + style.icon + ' fs-5 me-2" style="color:' + style.text + '"></i>' +
                    '<span>' + escapeHtml(message) + '</span>';
                body.appendChild(top);

                if (details && details.length > 0) {
                    var ul = document.createElement('ul');
                    ul.className = 'mb-0 ps-3 small mt-1';
                    for (var i = 0; i < details.length; i++) {
                        var li = document.createElement('li');
                        li.innerHTML = escapeHtml(details[i]);
                        ul.appendChild(li);
                    }
                    body.appendChild(ul);
                }

                var closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'btn-close btn-close-sm';
                closeBtn.setAttribute('data-bs-dismiss', 'alert');
                closeBtn.setAttribute('aria-label', 'Tutup');
                closeBtn.innerHTML = '<span aria-hidden="true">&times;</span>';
                alert.appendChild(body);
                alert.appendChild(closeBtn);
                alertBox.appendChild(alert);

                try {
                    var bsAlert = (typeof bootstrap !== 'undefined' && bootstrap.Alert) ? bootstrap.Alert : null;
                    if (bsAlert) { new bsAlert(alert); }
                } catch (e) {}
            }

            function readImportAlertFromStorage() {
                try {
                    var raw = sessionStorage.getItem('importAlert_legacy');
                    if (!raw) return null;
                    var parsed = JSON.parse(raw);
                    sessionStorage.removeItem('importAlert_legacy');
                    return parsed;
                } catch (e) { return null; }
            }

            var stored = readImportAlertFromStorage();
            if (stored) {
                var status = stored.status || 'error';
                var message = stored.message || '';
                var details = Array.isArray(stored.details) ? stored.details : [];
                showAlert(status, message, details);
            }
        })();
    })();

    })();

    window.togglePrintScope = togglePrintScope;
    window.confirmDeleteStudent = confirmDeleteStudent;
    window.confirmDeleteAllStudents = confirmDeleteAllStudents;
</script>
@endpush


