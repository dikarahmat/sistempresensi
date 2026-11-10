@extends('layouts.app')

@section('title', __('Data Guru'))
@section('page_title', __('Data Guru'))

@push('styles')
<style>
    /* Subtitle Data Guru: full width, tidak terpotong (mobile & desktop) */
    .header-main-subtitle {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        display: block !important;
        width: 100% !important;
    }

    /* Tombol Simpan Data Guru */
    #btnSaveTeacher {
        transition: background-color .15s ease, color .15s ease, opacity .15s ease;
    }

    #btnSaveTeacher:disabled,
    #btnSaveTeacher[disabled] {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
        cursor: not-allowed !important;
        opacity: 0.5 !important;
        box-shadow: none !important;
        pointer-events: none !important;
    }

    #btnSaveTeacher.is-loading,
    #btnSaveTeacher[data-saving="1"] {
        opacity: 1 !important;
        background-color: #2563eb !important;
        border-color: #2563eb !important;
        color: #ffffff !important;
        cursor: default !important;
        pointer-events: none !important;
        filter: none !important;
    }

    #btnSaveTeacher:not(:disabled) {
        opacity: 1 !important;
        cursor: pointer !important;
        pointer-events: auto !important;
    }

    #btnSaveTeacher.btn-progress.is-running,
    #btnSaveTeacher.btn-progress.is-done {
        opacity: 1 !important;
        cursor: default !important;
        pointer-events: none !important;
    }

    #btnSaveTeacher.btn-progress:disabled:not(.is-running):not(.is-done):not(.is-loading):not([data-saving="1"]),
    #btnSaveTeacher.btn-progress[disabled]:not(.is-running):not(.is-done):not(.is-loading):not([data-saving="1"]) {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
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

    /* Modal Tambah Guru: jarak seragam grup kolom */
    #addTeacherModal .modal-body > .mb-3 {
        margin-bottom: 14px !important;
    }

    #addTeacherModal .modal-body > .mb-3:last-child {
        margin-bottom: 18px !important;
    }

    #addTeacherModal .form-label {
        margin-bottom: 6px !important;
    }

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
        min-width: 760px;
        margin-bottom: 0;
        table-layout: auto;
    }

    /* Desktop / layar split: kolom punya lebar minimum supaya teks tidak
       saling menumpuk. Kalau container sempit, tabel di-scroll ke samping. */
    @media (min-width: 768px) {
        .table-zebra-custom {
            min-width: 1150px;
        }

        .table-zebra-custom th.no-col,    .table-zebra-custom td.no-col    { min-width: 60px; }
        .table-zebra-custom th.nip-col,   .table-zebra-custom td.nip-col   { min-width: 190px; white-space: nowrap; }
        .table-zebra-custom th.nama-col,  .table-zebra-custom td.nama-col  { min-width: 300px; white-space: nowrap; }
        .table-zebra-custom th.jk-col,    .table-zebra-custom td.jk-col    { min-width: 140px; white-space: nowrap; }
        .table-zebra-custom th.tugas-col, .table-zebra-custom td.tugas-col { min-width: 160px; white-space: nowrap; }
        .table-zebra-custom th.phone-col, .table-zebra-custom td.phone-col { min-width: 180px; white-space: nowrap; }
        .table-zebra-custom th.aksi-col,  .table-zebra-custom td.aksi-col  { min-width: 100px; }
    }

    .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
    }

    .table-zebra-custom th,
    .table-zebra-custom td {
        vertical-align: middle;
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

    .no-col, .nip-col {
        font-family: 'Poppins', 'Roboto', sans-serif !important;
    }

    .nip-col {
        text-align: center !important;
    }

    .jk-col, .tugas-col {
        text-align: center !important;
    }

    .phone-col {
        text-align: center !important;
        font-family: 'Poppins', 'Roboto', sans-serif !important;
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

    @media (max-width: 767.98px) {
        .pagination-compact .pagination {
            justify-content: center !important;
            flex-wrap: wrap;
            gap: 2px;
        }

        .table-zebra-custom {
            table-layout: auto !important;
        }

        .table-zebra-custom th.no-col, .table-zebra-custom td.no-col {
            width: 35px !important;
            min-width: 35px !important;
            text-align: center !important;
            padding-left: 4px !important;
            padding-right: 4px !important;
        }

        /* Header NIP & isi data NIP sama-sama center */
        .table-zebra-custom th.nip-col,
        .table-zebra-custom td.nip-col {
            text-align: center !important;
            padding-left: 6px !important;
            padding-right: 6px !important;
            min-width: 150px !important;
            white-space: nowrap !important;
        }

        .table-zebra-custom th.nama-col, .table-zebra-custom td.nama-col {
            text-align: left !important;
            padding-left: 6px !important;
            padding-right: 6px !important;
            min-width: 170px !important;
            white-space: normal !important;
            word-break: break-word !important;
        }

        .table-zebra-custom th, .table-zebra-custom td {
            padding-left: 6px !important;
            padding-right: 6px !important;
            font-size: 11px !important;
        }
    }

    .table-zebra-custom thead th {
        color: #111827 !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.03em;
        border-top: none !important;
        border-left: none !important;
        border-right: none !important;
        border-bottom: 1px solid #f1f5f9 !important;
        background-color: #f8fafc !important;
        font-family: 'Poppins', 'Roboto', sans-serif;
    }

    @media (max-width: 1023.98px) {
        .table-zebra-custom thead th {
            background-color: #ffffff !important;
            color: var(--clean-ink) !important;
            border-bottom: 1px solid var(--clean-line) !important;
        }
    }

    .action-bar-section {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem 1rem;
    }

    /* ==========================================================================
       FILTER BAR: [ Kolom Pencarian & Reset Adaptif Responsive ]
       ========================================================================== */
    form.action-search-form {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        flex: 1 1 260px !important;
    }

    .search-and-reset-wrapper {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        gap: 8px !important;
        width: 100% !important;
    }

    .search-and-reset-wrapper .search-box-wrap {
        flex: 1 1 auto !important;
        width: 100% !important;
        min-width: 0 !important;
        margin: 0 !important;
        display: flex !important;
    }

    .search-and-reset-wrapper .search-box-wrap .form-control {
        height: 38px !important;
        font-size: 0.85rem !important;
        min-width: 0 !important;
    }

    .search-and-reset-wrapper .search-box-wrap .btn {
        height: 38px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 0 0 auto !important;
    }

    /* Tombol Reset Kotak Fleksibel Menyesuaikan Tinggi Input (38px) */
    .search-and-reset-wrapper .btn-reset {
        flex: 0 0 38px !important;
        width: 38px !important;
        height: 38px !important;
        min-width: 38px !important;
        max-width: 38px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-sizing: border-box !important;
        padding: 0 !important;
        background: #64748b !important;
        border: 1px solid #64748b !important;
        border-radius: 6px !important;
        color: #ffffff !important;
        text-decoration: none !important;
        cursor: pointer !important;
        outline: none !important;
        transition: background-color .15s ease, border-color .15s ease;
    }

    .search-and-reset-wrapper .btn-reset svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
        fill: none;
    }

    .search-and-reset-wrapper .btn-reset:hover,
    .search-and-reset-wrapper .btn-reset:focus-visible {
        background: #475569 !important;
        border-color: #475569 !important;
        color: #ffffff !important;
        text-decoration: none;
    }

    .search-and-reset-wrapper .btn-reset:focus-visible {
        box-shadow: 0 0 0 3px rgba(100, 116, 139, .3);
    }

    .action-buttons-wrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        flex: 0 0 auto;
        margin-left: auto;
    }

    .btn-solid-pill,
    button.btn-solid-pill,
    a.btn-solid-pill {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
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
        text-transform: uppercase;
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

    .btn-solid-green { background-color: #16a34a; }
    .btn-solid-blue { background-color: #2563eb; }
    .btn-solid-red { background-color: #dc2626; }

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

    /* Matikan Cetak Kartu saat data kosong */
    .btn-cetak-kartu.is-disabled {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        pointer-events: none;
        box-shadow: none !important;
    }

    .btn-download-green:disabled {
        background-color: #059669 !important;
        color: #ffffff !important;
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
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
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" style="background-color: #fee2e2 !important; color: #991b1b !important;" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-trash fs-5 me-2' style="color: #991b1b !important;"></i>
                <span>{{ session('danger') }}</span>
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

    <div id="importAlertBox"></div>

    @if(Auth::check() && Auth::user()->role === 'admin')
    <form id="deleteAllTeachersForm" action="{{ panel_route('guru.destroy-all') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden md:mb-4 bg-white" id="daftar-guru">
        <div class="p-3 p-md-4 border-bottom border-gray-100 bg-white">
            <div class="action-bar-section d-flex flex-column flex-md-row justify-content-between">
                <form method="GET" action="{{ url()->current() }}" class="action-search-form" id="teacherSearchForm">
                    <div class="search-and-reset-wrapper">
                        <div class="input-group search-box-wrap">
                            <input type="text"
                                   name="search"
                                   id="teacherSearchInput"
                                   class="form-control shadow-none"
                                   placeholder="{{ __('Cari nama atau NIP...') }}"
                                   value="{{ request('search') }}"
                                   aria-label="{{ __('Cari nama atau NIP') }}"
                                   autocomplete="off">
                            <button class="btn shadow-none" type="submit" title="{{ __('Cari') }}" aria-label="{{ __('Cari') }}">
                                <i class='bx bx-search fs-6'></i>
                            </button>
                        </div>

                        <!-- Tombol Reset (Ukuran Proporsional Menyesuaikan Kolom Search) -->
                        <a href="{{ url()->current() }}" class="btn-reset" role="button" title="{{ __('Reset pencarian') }}" aria-label="{{ __('Reset pencarian') }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>
                            </svg>
                        </a>
                    </div>
                </form>

                @if(Auth::check() && Auth::user()->role === 'admin')
                <div class="action-buttons-wrap">
                    <button type="button" class="btn-solid-pill btn-solid-blue btn-cetak-kartu {{ $totalTeachers == 0 ? 'is-disabled' : '' }}"
                            data-bs-toggle="modal"
                            data-bs-target="#printCardsModal"
                            {{ $totalTeachers == 0 ? 'disabled aria-disabled="true" tabindex="-1" title="Belum ada data untuk dicetak"' : '' }}>
                        <span>{{ __('Cetak Kartu') }}</span>
                    </button>
                    <button type="button" class="btn-solid-pill btn-solid-green" data-bs-toggle="modal" data-bs-target="#importModal">
                        <span>{{ __('Import Excel') }}</span>
                    </button>
                    <button type="button" class="btn-solid-pill btn-solid-blue" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                        <span>{{ __('Tambah Guru') }}</span>
                    </button>
                    <button type="button" class="btn-solid-pill btn-solid-red" title="{{ __('Hapus Semua Data Guru') }}" onclick="confirmDeleteAllTeachers()">
                        <span>{{ __('Hapus') }}</span>
                    </button>
                </div>
                @endif
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <colgroup>
                    <col style="width: 5%;">
                    <col style="width: 15%;">
                    <col style="width: 25%;">
                    <col style="width: 12%;">
                    <col style="width: 14%;">
                    <col style="width: 15%;">
                    <col style="width: 14%;">
                </colgroup>
                <thead class="bg-slate-50 border-b border-gray-100 text-blue-500">
                    <tr class="align-middle text-blue-500 text-xs font-bold uppercase tracking-wider border-b border-gray-100">
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100 no-col" style="width: 5%;">{{ __('No') }}</th>
                        <!-- Header NIP rata tengah -->
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100 nip-col" style="width: 15%;">{{ __('NIP') }}</th>
                        <th class="text-start py-3 text-nowrap px-3 pe-4 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100 nama-col" style="width: 25%;">{{ __('Nama Lengkap Guru') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100 jk-col" style="width: 12%;">{{ __('Jenis Kelamin') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100 tugas-col" style="width: 14%;">{{ __('Tugas Kelas') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100 phone-col" style="width: 15%;">{{ __('No. Telepon / WA') }}</th>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100 aksi-col" style="width: 14%;">{{ __('Aksi') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $index => $teacher)
                    @php
                        $assignedClass = $teacher->schoolClass ?? null;
                    @endphp
                    <tr class="align-middle {{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td data-label="{{ __('No') }}" class="text-center text-nowrap px-3 no-col">{{ $teachers->firstItem() + $index }}</td>
                        <!-- Isi NIP rata tengah -->
                        <td data-label="{{ __('NIP') }}" class="text-center text-nowrap px-3 nip-col">{{ $teacher->nip ?? '-' }}</td>
                        <td data-label="{{ __('Nama Guru') }}" class="text-start text-nowrap fw-semibold text-dark px-3 pe-4 nama-col">{{ $teacher->name }}</td>
                        <td data-label="{{ __('Jenis Kelamin') }}" class="text-center text-nowrap px-3 jk-col">
                            {{ ($teacher->gender == 'Perempuan' || $teacher->gender == 'P') ? __('Perempuan') : __('Laki-laki') }}
                        </td>
                        <td data-label="{{ __('Tugas Kelas') }}" class="text-center text-nowrap px-3 tugas-col">
                            @if($assignedClass)
                                {{ __('Kelas ') }}{{ $assignedClass->name }}
                            @else
                                {{ __('Guru Pengajar') }}
                            @endif
                        </td>
                        <td data-label="{{ __('No. Telepon / WA') }}" class="text-center text-nowrap px-3 phone-col">
                            {{ $teacher->phone_number ?? $teacher->phone ?? '-' }}
                        </td>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <td data-label="{{ __('Aksi') }}" class="text-center text-nowrap px-3 aksi-col">
                            <div class="crud-center-wrapper">
                                <a href="{{ panel_route('guru.show', $teacher->id) }}" class="btn-row-action action-edit" title="{{ __('Lihat / Edit') }}" aria-label="{{ __('Lihat / Edit') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </a>
                                <button type="button" class="btn-row-action action-delete"
                                        title="{{ __('Hapus') }}"
                                        aria-label="{{ __('Hapus') }}"
                                        onclick="confirmDeleteTeacher('{{ $teacher->id }}', '{{ addslashes($teacher->name) }}')">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                                <form id="deleteTeacherForm-{{ $teacher->id }}" action="{{ panel_route('guru.destroy', $teacher->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr class="align-middle">
                        <td colspan="{{ Auth::check() && Auth::user()->role === 'admin' ? 7 : 6 }}" class="text-center py-5 text-muted empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            @if(request('search'))
                                {{ __('Tidak ada data guru yang cocok dengan pencarian.') }}
                            @else
                                {{ __('BELUM ADA DATA GURU YANG TERDAFTAR.') }}
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {!! render_compact_pagination($teachers, 'daftar-guru') !!}
    </div>

    @if(Auth::check() && Auth::user()->role === 'admin')
    <!-- MODAL TAMBAH GURU -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">{{ __('Tambah Guru Baru') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ panel_route('guru.store') }}" method="POST" id="addTeacherForm">
                    @csrf
                    <div class="modal-body py-3">
                        @if($errors->any() && session('open_modal') === 'add')
                        <div class="alert alert-danger border-0 rounded-3 py-2 px-3 small mb-3">
                            <i class='bx bx-error-circle fs-6 me-1'></i> {{ __('Periksa isian yang bertanda merah.') }}
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Nama Lengkap Guru') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="teacherName" class="form-control rounded-3 {{ $errors->has('name') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" placeholder="{{ __('Masukkan nama lengkap & gelar guru') }}" maxlength="255" autocomplete="off" value="{{ old('name') }}">
                            @if($errors->has('name') && session('open_modal') === 'add')
                            <div class="invalid-feedback d-block">{{ $errors->first('name') }}</div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('NIP (Nomor Induk Pegawai)') }} <span class="text-danger">*</span></label>
                            <input type="text" name="nip" id="teacherNip" class="form-control rounded-3 {{ $errors->has('nip') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" placeholder="{{ __('16 atau 18 digit angka') }}" inputmode="numeric" maxlength="18" autocomplete="off" value="{{ old('nip') }}">
                            @if($errors->has('nip') && session('open_modal') === 'add')
                            <div class="invalid-feedback d-block">{{ $errors->first('nip') }}</div>
                            @endif
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">{{ __('Jenis Kelamin') }} <span class="text-danger">*</span></label>
                                <select name="gender" id="teacherGender" class="form-select rounded-3 {{ $errors->has('gender') && session('open_modal') === 'add' ? 'is-invalid' : '' }}">
                                    <option value="" {{ old('gender') ? '' : 'selected' }}>{{ __('Pilih jenis kelamin') }}</option>
                                    <option value="Laki-laki" {{ old('gender') == 'Laki-laki' ? 'selected' : '' }}>{{ __('Laki-laki') }}</option>
                                    <option value="Perempuan" {{ old('gender') == 'Perempuan' ? 'selected' : '' }}>{{ __('Perempuan') }}</option>
                                </select>
                                @if($errors->has('gender') && session('open_modal') === 'add')
                                <div class="invalid-feedback d-block">{{ $errors->first('gender') }}</div>
                                @endif
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">{{ __('No. Telepon / WA') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone_number" id="teacherPhone" class="form-control rounded-3 {{ $errors->has('phone_number') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" placeholder="{{ __('10-15 digit') }}" inputmode="numeric" maxlength="15" autocomplete="off" value="{{ old('phone_number') }}">
                                @if($errors->has('phone_number') && session('open_modal') === 'add')
                                <div class="invalid-feedback d-block">{{ $errors->first('phone_number') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold btn-progress" data-dl-progress data-form-submit id="btnSaveTeacher" disabled="disabled">{{ __('Simpan Data Guru') }}</button>
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
                    <h5 class="fw-bold mb-0">{{ __('Import Data Guru Excel') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="teacherImportForm" action="{{ panel_route('guru.import') }}" method="POST" enctype="multipart/form-data" data-no-loader>
                    @csrf
                    <div class="modal-body">
                        <div class="p-3 bg-white border rounded-3 small text-secondary mb-3">
                            <div class="mb-2">
                                {{ __('Format kolom file Excel:') }} <strong>{{ __('NIP, Nama Lengkap, Jenis Kelamin, No. Telepon') }}</strong> {{ __('NIP/NUPTK 16 atau 18 digit angka.') }} {{ __('(.xlsx atau .csv)') }}
                            </div>
                            <a href="{{ panel_route('guru.template') }}" class="btn-download-green w-100" data-download data-no-download data-dl-progress>{{ __('Unduh Template Excel') }}</a>
                        </div>
                        <label class="form-label small fw-semibold">{{ __('Pilih File Excel') }}</label>
                        <input type="file" name="file_excel" class="form-control rounded-3" accept=".xlsx,.xls,.csv" required>
                        <div class="field-error"></div>
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

    <!-- MODAL CETAK KARTU MASSAL -->
    <div class="modal fade" id="printCardsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">{{ __('Cetak Kartu Presensi Guru Massal') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ panel_route('guru.print-cards') }}" method="POST">
                    @csrf
                    <div class="modal-body py-3">
                        <p class="text-secondary small mb-3">
                            {{ __('Kartu seluruh guru aktif akan diunduh sebagai file ZIP, berisi satu kartu (JPG) untuk setiap guru.') }}
                        </p>
                        <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-0" id="printInfoBox">
                            <i class='bx bx-info-circle me-1'></i> {{ __('Setiap kartu dilengkapi QR Code presensi yang dibuat otomatis.') }}
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
    @endif

@endsection

@push('scripts')
<script>
@if(session('open_modal') === 'add')
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('addTeacherModal');
    if (modalEl && window.bootstrap) {
        new bootstrap.Modal(modalEl).show();
    }
});
@endif

/* ===== Import Excel dengan progres ===== */
(function () {
  var form = document.getElementById('teacherImportForm');
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
      btn.innerHTML = original;
      sync();
      alert('Import gagal. Periksa koneksi lalu coba lagi.');
    };

    xhr.send(new FormData(form));
  }, true);

  var modal = document.getElementById('importModal');
  if (modal) modal.addEventListener('hidden.bs.modal', function () { form.reset(); sync(); });
})();

/* ===== Notifikasi hasil import ===== */
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

  var head = document.createElement('div');
  head.className = 'd-flex align-items-center';
  var icon = document.createElement('i');
  icon.className = 'bx ' + color.icon + ' fs-5 me-2';
  var text = document.createElement('span');
  text.textContent = msg;
  head.appendChild(icon);
  head.appendChild(text);
  alertEl.appendChild(head);

  if (details.length) {
    var list = document.createElement('ul');
    list.className = 'mb-0 mt-2 ps-3';
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
/* Cegah pembukaan modal Cetak Kartu jika tombol berstatus nonaktif */
(function () {
    var printBtn = document.querySelector('.btn-cetak-kartu');
    if (!printBtn) return;
    printBtn.addEventListener('click', function (e) {
        if (this.disabled || this.classList.contains('is-disabled') || this.getAttribute('aria-disabled') === 'true') {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });
})();

/* ===== Filter input & tombol Simpan Data Guru ===== */
function keepCaret(el, cleaned) {
    var start = el.selectionStart;
    el.value = cleaned;
    var pos = Math.min(start, el.value.length);
    try { el.setSelectionRange(pos, pos); } catch (e) {}
}

function filterNamaGuru(el) {
    if (!el) return;
    var cleaned = el.value.replace(/[^\p{L}\s.,'\-]/gu, '');
    if (cleaned !== el.value) keepCaret(el, cleaned);
}

function filterNipGuru(el) {
    if (!el) return;
    var cleaned = el.value.replace(/\D/g, '').slice(0, 18);
    if (cleaned !== el.value) keepCaret(el, cleaned);
}

function filterPhoneGuru(el) {
    if (!el) return;
    var cleaned = el.value.replace(/\D/g, '').slice(0, 15);
    if (cleaned !== el.value) keepCaret(el, cleaned);
}

function setSimpanGuruEnabled(btn, ok) {
    btn.disabled = !ok;
    if (ok) {
        btn.removeAttribute('disabled');
    } else {
        btn.setAttribute('disabled', 'disabled');
    }
    btn.setAttribute('aria-disabled', ok ? 'false' : 'true');
    btn.style.opacity = ok ? '1' : '0.5';
    btn.style.cursor = ok ? 'pointer' : 'not-allowed';
    btn.style.pointerEvents = ok ? 'auto' : 'none';
}

/* Satu-satunya sumber kebenaran status tombol Simpan */
function enforceSimpanGuruState() {
    var btn = document.getElementById('btnSaveTeacher');
    if (!btn) return;
    if (btn.dataset.saving === '1') return;
    if (btn.classList.contains('is-running') || btn.classList.contains('is-done')) return;

    var name = document.getElementById('teacherName');
    var nip = document.getElementById('teacherNip');
    var jk = document.getElementById('teacherGender');
    var phone = document.getElementById('teacherPhone');

    var namaVal = name ? (name.value || '').trim() : '';
    var nipVal = nip ? (nip.value || '').trim() : '';
    var jkVal = jk ? (jk.value || '').trim() : '';
    var phoneVal = phone ? (phone.value || '').trim() : '';

    var namaOk = namaVal !== '' && /^[\p{L}\s.,'\-]+$/u.test(namaVal);
    var nipOk = /^(\d{16}|\d{18})$/.test(nipVal);
    var jkOk = jkVal !== '' && jkVal !== 'Pilih jenis kelamin';
    var phoneOk = /^\d{10,15}$/.test(phoneVal);

    setSimpanGuruEnabled(btn, namaOk && nipOk && jkOk && phoneOk);
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str == null ? '' : String(str)));
    return div.innerHTML;
}

(function () {
    var nameEl = document.getElementById('teacherName');
    var nipEl = document.getElementById('teacherNip');
    var jkEl = document.getElementById('teacherGender');
    var phoneEl = document.getElementById('teacherPhone');
    var form = document.getElementById('addTeacherForm');
    var modalEl = document.getElementById('addTeacherModal');
    var btn = document.getElementById('btnSaveTeacher');

    function runAll() {
        filterNamaGuru(nameEl);
        filterNipGuru(nipEl);
        filterPhoneGuru(phoneEl);
        enforceSimpanGuruState();
    }

    function resetForm() {
        if (form) { try { form.reset(); } catch (e) {} }
        if (nameEl) nameEl.value = '';
        if (nipEl) nipEl.value = '';
        if (jkEl) jkEl.value = '';
        if (phoneEl) phoneEl.value = '';
    }

    function bind(el, events) {
        if (!el) return;
        events.forEach(function (ev) { el.addEventListener(ev, runAll); });
        el.addEventListener('paste', function () { setTimeout(runAll, 0); });
    }

    if (nipEl) { nipEl.setAttribute('inputmode', 'numeric'); nipEl.setAttribute('maxlength', '18'); }
    if (phoneEl) { phoneEl.setAttribute('inputmode', 'numeric'); phoneEl.setAttribute('maxlength', '15'); }

    bind(nameEl, ['input', 'change']);
    bind(nipEl, ['input', 'change']);
    bind(phoneEl, ['input', 'change']);
    if (jkEl) {
        jkEl.addEventListener('change', runAll);
        jkEl.addEventListener('input', runAll);
    }

    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            resetForm();
            if (btn) setSimpanGuruEnabled(btn, false);
            setTimeout(runAll, 0);
        });
        modalEl.addEventListener('shown.bs.modal', runAll);
        modalEl.addEventListener('hidden.bs.modal', function () {
            resetForm();
            runAll();
        });
    }

    if (btn && typeof MutationObserver !== 'undefined') {
        try {
            var lock = false;
            var obs = new MutationObserver(function () {
                if (lock) return;
                lock = true;
                try { enforceSimpanGuruState(); }
                finally {
                    try { obs.takeRecords(); } catch (e) {}
                    lock = false;
                }
            });
            obs.observe(btn, { attributes: true, attributeFilter: ['disabled', 'style', 'class'] });
        } catch (e) {}
    }

    if (btn) setSimpanGuruEnabled(btn, false);
    document.addEventListener('DOMContentLoaded', runAll);
    runAll();
})();

/* ===== Konfirmasi hapus SATU guru ===== */
function confirmDeleteTeacher(id, name) {
    confirmUniversalDelete({
        title: {!! json_encode(__('Hapus Data Guru?')) !!},
        html: {!! json_encode(__('Guru')) !!} + ' <strong>' + escapeHtml(name) + '</strong> ' + {!! json_encode(__('akan dihapus. Data bisa dipulihkan dari Tempat Sampah di Pengaturan.')) !!},
        confirmText: {!! json_encode(__('Hapus')) !!},
        cancelText: {!! json_encode(__('Tidak')) !!},
        onConfirm: function () {
            var form = document.getElementById('deleteTeacherForm-' + id);
            if (!form) {
                console.error('HAPUS: form deleteTeacherForm-' + id + ' tidak ditemukan. Muat ulang halaman.');
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

/* ===== Konfirmasi hapus SEMUA guru ===== */
function confirmDeleteAllTeachers() {
    if (typeof confirmUniversalDelete === 'function') {
        confirmUniversalDelete({
            title: {!! json_encode(__('Hapus seluruh data guru?')) !!},
            icon: 'bx-error-circle',
            html: '',
            sub: {!! json_encode(__('Data akan dipindahkan ke Tempat Sampah di Pengaturan.')) !!},
            confirmText: {!! json_encode(__('Hapus Semua')) !!},
            cancelText: {!! json_encode(__('Tidak')) !!},
            checks: [
                '<span style="display:block; white-space:nowrap">Saya memahami data akan dipindahkan ke Tempat</span><span style="display:block; white-space:nowrap">Sampah dan dapat dipulihkan.</span>',
                {!! json_encode(__('Saya yakin ingin menghapus semua data guru aktif.')) !!}
            ],
            onConfirm: function () {
                document.getElementById('deleteAllTeachersForm').submit();
            }
        });
    } else if (confirm({!! json_encode(__('Pindahkan semua guru aktif ke Tempat Sampah?')) !!})) {
        document.getElementById('deleteAllTeachersForm').submit();
    }
}

window.confirmDeleteTeacher = confirmDeleteTeacher;
window.confirmDeleteAllTeachers = confirmDeleteAllTeachers;
</script>
@endpush