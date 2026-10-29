@extends('layouts.app')

@section('title', __('Data Siswa'))
@section('page_title', __('Data Siswa'))
@section('page_subtitle', __('Kelola informasi siswa'))

{{-- Kanvas dikunci setinggi satu layar; kartu tabel mengisi sisa tinggi kanvas
     sehingga tabelnya (bukan halaman) yang menggulir. Class ini diatur di layout
     bersama, sama seperti halaman acuan. --}}
@section('canvas_class', 'page-canvas-fixed')

@push('styles')
<style>
    /* Subtitle Data Siswa: full width, tidak terpotong (mobile & desktop) */
    .header-main-subtitle {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        display: block !important;
        width: 100% !important;
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
    .btn-row-action.action-detail { color: #16a34a; }
    .btn-row-action.action-detail:hover { color: #15803d; }
    .btn-row-action.action-edit { color: #f59e0b; }
    .btn-row-action.action-edit:hover { color: #d97706; }
    .btn-row-action.action-delete { color: #dc2626; }
    .btn-row-action.action-delete:hover { color: #b91c1c; }

    /* === Matikan Cetak Kartu saat data kosong, biarkan tombol lain normal ===
       Hanya selector .btn-cetak-kartu ditargetkan, bukan class tombol umum.
       Saat data ada: tombol Cetak Kartu tetap biru normal (btn-solid-blue). */
    .btn-cetak-kartu.is-disabled {
        background-color: #cbd5e1 !important;
        color: #64748b !important;
        border-color: #cbd5e1 !important;
        cursor: not-allowed;
        pointer-events: none;
        box-shadow: none;
        opacity: 1;
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
    .btn-row-action.action-detail { color: #16a34a; }
    .btn-row-action.action-detail:hover { color: #15803d; }
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
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-error-circle fs-5 me-2 text-warning'></i>
                <span>{{ session('warning') }}</span>
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

    <form id="deleteAllStudentsForm" action="{{ panel_route('students.destroy-all-active') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="confirm_active" value="1">
        <input type="hidden" name="confirm_all" value="1">
    </form>

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
                                <a href="{{ panel_route('students.show', $student->id) }}" class="btn-row-action action-detail" title="{{ __('Detail') }}" aria-label="{{ __('Detail') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                @if(Auth::check() && Auth::user()->role === 'admin')
                                <a href="{{ panel_route('students.edit', $student->id) }}" class="btn-row-action action-edit" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </a>
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
                    <button type="submit" class="btn-download-blue btn-progress px-4" data-download data-dl-progress data-dl-progress-mode="persen" id="kartuUnduhBtn">{{ __('Unduh ZIP Kartu') }}</button>
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
                    @if($errors->any() && !$errors->has('file_excel'))
                    <div class="alert alert-danger py-2 px-3 small mb-3 border-0 rounded-3" role="alert">
                        <i class='bx bx-error-circle me-1'></i> {{ __('Data belum bisa disimpan. Periksa isian yang bertanda merah.') }}
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Nama Siswa') }} <span class="text-danger">*</span></label>
                        <input id="studentName" type="text" name="name" class="form-control rounded-3" placeholder="{{ __('Masukkan nama lengkap siswa') }}" value="{{ old('name') }}">
                        <div class="invalid-msg small text-danger"></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">{{ __('NISN') }} <span class="text-danger">*</span></label>
                            <input id="studentNisn" type="text" name="nisn" class="form-control rounded-3" placeholder="{{ __('Contoh: 0081234567') }}" value="{{ old('nisn') }}" inputmode="numeric" maxlength="10" autocomplete="off">
                            <div class="invalid-msg small text-danger"></div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">{{ __('Kelas') }} <span class="text-danger">*</span></label>
                            <select id="studentKelas" name="school_class_id" class="form-select rounded-3">
                                <option value="">{{ __('Pilih Kelas') }}</option>
                                @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ old('school_class_id') == $c->id ? 'selected' : '' }}>{{ __('Kelas ') }}{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-msg small text-danger"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">{{ __('Jenis Kelamin') }} <span class="text-danger">*</span></label>
                            <select id="studentJk" name="gender" class="form-select rounded-3">
                                <option value="" selected>{{ __('Pilih jenis kelamin') }}</option>
                                <option value="Laki-laki">{{ __('Laki-laki') }}</option>
                                <option value="Perempuan">{{ __('Perempuan') }}</option>
                            </select>
                            <div class="invalid-msg small text-danger"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button id="btnSimpanSiswa" type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">{{ __('Simpan Siswa') }}</button>
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
            <form id="importForm" action="{{ panel_route('students.import') }}" method="POST" enctype="multipart/form-data" data-no-loader>
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
                    <button type="submit" class="btn-download-green px-4">{{ __('Unggah & Import') }}</button>
                </div>
                <div id="importMessage" class="px-3 pb-3 small" style="min-height:1.2rem;"></div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    /* ===== Cetak Kartu: toggle pilihan cetak per kelas / semua ===== */
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
        const msgEl = field.parentNode.querySelector('.invalid-msg');
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

    /* Validasi form Tambah Siswa (JS murni, tanpa jQuery). */
    function cekValiditasSiswa() {
        const name = document.getElementById('studentName');
        const nisn = document.getElementById('studentNisn');
        const kelas = document.getElementById('studentKelas');
        const jk = document.getElementById('studentJk');
        const btn = document.getElementById('btnSimpanSiswa');
        let ok = true;

        // --- Nama ---
        const nama = (name ? name.value : '').trim();
        if (nama === '') {
            setFieldError(name, 'Nama siswa wajib diisi.');
            ok = false;
        } else if (/\p{Nd}/u.test(nama)) {
            setFieldError(name, 'Nama siswa tidak boleh mengandung angka.');
            ok = false;
        } else {
            setFieldError(name, '');
        }

        // --- NISN ---
        const nisnRaw = (nisn ? nisn.value : '').trim();
        if (nisnRaw === '') {
            setFieldError(nisn, 'NISN wajib diisi.');
            ok = false;
        } else if (!/^[0-9]{10}$/.test(nisnRaw)) {
            setFieldError(nisn, 'NISN harus berupa 10 digit angka.');
            ok = false;
        } else {
            setFieldError(nisn, '');
        }

        // --- Kelas ---
        const kelasVal = (kelas ? kelas.value : '').trim();
        if (kelasVal === '') {
            setFieldError(kelas, 'Pilih kelas terlebih dahulu.');
            ok = false;
        } else {
            setFieldError(kelas, '');
        }

        // --- Jenis Kelamin ---
        const jkVal = (jk ? jk.value : '').trim();
        if (jkVal === '') {
            setFieldError(jk, 'Pilih jenis kelamin.');
            ok = false;
        } else {
            setFieldError(jk, '');
        }

        // --- Status tombol: abu biasa TANPA disabled, biru bila valid ---
        if (btn) {
            if (ok) {
                btn.className = 'btn btn-primary rounded-3 px-4 fw-semibold';
                btn.setAttribute('aria-disabled', 'false');
                btn.disabled = false;
                btn.style.backgroundColor = '';
                btn.style.color = '';
            } else {
                btn.className = 'btn btn-primary rounded-3 px-4 fw-semibold';
                btn.setAttribute('aria-disabled', 'true');
                btn.disabled = false;
                btn.style.backgroundColor = '#cbd5e1';
                btn.style.color = '#64748b';
                btn.style.cursor = 'not-allowed';
            }
        }
        return ok;
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
        confirmUniversalDelete({
            title: {!! json_encode(__('Hapus Seluruh Data Siswa?')) !!},
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

        [namaEl, nisnEl, kelasEl, jkEl].forEach(function (el) {
            if (!el) return;
            attach(el, 'change', function () {
                cekValiditasSiswa();
            });
        });

        document.addEventListener('submit', function (e) {
            if (form && e.target === form) {
                if (!cekValiditasSiswa()) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    var firstInvalid = form.querySelector('.invalid-msg');
                    var focusTarget = null;
                    [namaEl, nisnEl, kelasEl, jkEl].forEach(function (el) {
                        if (el && el.parentNode.querySelector('.invalid-msg') && el.parentNode.querySelector('.invalid-msg').textContent) {
                            focusTarget = el;
                        }
                    });
                    if (focusTarget) {
                        focusTarget.focus();
                    }
                }
            }
        });

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            document.addEventListener('show.bs.modal', function (e) {
                var modal = e.target;
                if (modal && (modal.id === 'addStudentModal' || (modal.querySelector && modal.querySelector('#addStudentForm')))) {
                    cekValiditasSiswa();
                }
            });
        }
    })();

    /* ===== Import Siswa via AJAX (JS murni, capture phase) ===== */
    (function () {
        var form = document.getElementById('importForm');
        if (!form) return;

        var btn = form.querySelector('button[type="submit"]');
        var messageEl = document.getElementById('importMessage');

        function escapeHtml(str) {
            var div = document.createElement('div');
            var txt = document.createTextNode(str);
            div.appendChild(txt);
            return div.innerHTML;
        }

        function setMessage(text, color) {
            if (!messageEl) return;
            messageEl.innerHTML = '<strong>' + escapeHtml(text) + '</strong>';
            messageEl.style.color = color || '#1e293b';
        }

        function resetButton() {
            if (!btn) return;
            btn.disabled = false;
            btn.textContent = 'Unggah & Import';
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if (!btn || !messageEl) return;

            setMessage('', '');
            btn.disabled = true;
            btn.textContent = 'Memproses...';

            var formData = new FormData(form);
            var action = form.getAttribute('action') || window.location.href;

            fetch(action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(function (res) {
                return res.json().catch(function () {
                    return null;
                });
            })
            .then(function (data) {
                var msg = (data && data.message) ? data.message : '';
                var status = (data && data.status) ? data.status : '';

                if (!msg) {
                    if (data === null || (data && data.status === undefined)) {
                        msg = 'Import gagal. Periksa format file.';
                    } else {
                        msg = 'Import selesai.';
                    }
                }

                if (status === 'error') {
                    setMessage(msg, '#b91c1c');
                } else if (status === 'partial') {
                    setMessage(msg, '#b45309');
                } else if (status === 'success') {
                    setMessage(msg, '#15803d');
                } else {
                    setMessage(msg, '#b91c1c');
                }

                if (status === 'success' || status === 'partial') {
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                }
                resetButton();
            })
            .catch(function () {
                setMessage('Import terlalu lama, coba lagi.', '#b91c1c');
                resetButton();
            });
        }, true);
    })();

</script>
@endpush
