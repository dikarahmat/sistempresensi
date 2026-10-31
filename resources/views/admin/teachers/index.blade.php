@extends('layouts.app')

@section('title', __('Data Guru'))
@section('page_title', __('Data Guru'))
@section('page_subtitle', __('Kelola data guru pendidik'))

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

    #btnSaveTeacher {
        transition: background-color .15s ease, color .15s ease, opacity .15s ease;
    }
    #btnSaveTeacher:disabled {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
        cursor: not-allowed !important;
        opacity: 0.5 !important;
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
        table-layout: fixed;
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

        .table-zebra-custom th.nip-col, .table-zebra-custom td.nip-col {
            text-align: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        .table-zebra-custom td.nip-col {
            white-space: nowrap !important;
        }
        .table-zebra-custom th.no-col, .table-zebra-custom td.no-col {
            text-align: center !important;
        }
        .table-zebra-custom th.nama-col, .table-zebra-custom td.nama-col {
            text-align: left !important;
        }
        .table-zebra-custom th, .table-zebra-custom td {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
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

    /* === Matikan Cetak Kartu saat data kosong, biarkan tombol lain normal ===
       Biru aktif yang dipudarkan (opacity .5, teks putih, cursor not-allowed). BUKAN abu-abu. */
    .btn-cetak-kartu.is-disabled {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        pointer-events: none;
        box-shadow: none !important;
    }

    /* Unggah & Import hijau pudar dan disabled sampai file dipilih */
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
                <i class='bx bx-error-circle fs-5 me-2' style="color: #991b1b !important;"></i>
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
                    <div class="input-group search-box-wrap">
                        <input type="text"
                               name="search"
                               id="teacherSearchInput"
                               class="form-control shadow-none"
                               placeholder="{{ __('Cari nama atau NIP...') }}"
                               value="{{ request('search') }}"
                               aria-label="{{ __('Cari nama atau NIP') }}"
                               autocomplete="off"
                               style="height: 38px; font-size: 0.85rem;">
                        <button class="btn shadow-none" type="submit" title="{{ __('Cari') }}" aria-label="{{ __('Cari') }}">
                            <i class='bx bx-search fs-6'></i>
                        </button>
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
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 5%;">{{ __('No') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 15%;">{{ __('NIP') }}</th>
                        <th class="text-start indent-nama py-3 text-nowrap px-3 pe-4 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 25%;">{{ __('Nama Lengkap Guru') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 12%;">{{ __('Jenis Kelamin') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 14%;">{{ __('Tugas Kelas') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 15%;">{{ __('No. Telepon / WA') }}</th>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 14%;">{{ __('Aksi') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $index => $teacher)
                    @php
                        $assignedClass = $teacher->schoolClass ?? null;
                    @endphp
                    <tr class="align-middle {{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td data-label="{{ __('No') }}" class="text-center text-nowrap px-3">{{ $teachers->firstItem() + $index }}</td>
                        <td data-label="{{ __('NIP') }}" class="text-center text-nowrap px-3">{{ $teacher->nip ?? '-' }}</td>
                        <td data-label="{{ __('Nama Guru') }}" class="text-start text-nowrap indent-nama fw-semibold text-dark px-3 pe-4">{{ $teacher->name }}</td>
                        <td data-label="{{ __('Jenis Kelamin') }}" class="text-center text-nowrap px-3">
                            {{ ($teacher->gender == 'Perempuan' || $teacher->gender == 'P') ? __('Perempuan') : __('Laki-laki') }}
                        </td>
                        <td data-label="{{ __('Tugas Kelas') }}" class="text-center text-nowrap px-3">
                            @if($assignedClass)
                                {{ __('Kelas ') }}{{ $assignedClass->name }}
                            @else
                                {{ __('Guru Pengajar') }}
                            @endif
                        </td>
                        <td data-label="{{ __('No. Telepon / WA') }}" class="text-center text-nowrap px-3">
                            {{ $teacher->phone_number ?? $teacher->phone ?? '-' }}
                        </td>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <td data-label="{{ __('Aksi') }}" class="text-center text-nowrap px-3">
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
                <form action="{{ panel_route('guru.store') }}" method="POST" novalidate id="addTeacherForm" data-no-loader>
                    @csrf
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Nama Lengkap Guru') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="teacherName" class="form-control rounded-3" placeholder="{{ __('Masukkan nama lengkap & gelar guru') }}" maxlength="255" autocomplete="off">
                            <div class="field-error"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('NIP (Nomor Induk Pegawai)') }} <span class="text-danger">*</span></label>
                            <input type="text" name="nip" id="teacherNip" class="form-control rounded-3" placeholder="{{ __('16 atau 18 digit angka') }}" inputmode="numeric" maxlength="18" autocomplete="off">
                            <div class="field-error"></div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">{{ __('Jenis Kelamin') }} <span class="text-danger">*</span></label>
                                <select name="gender" id="teacherGender" class="form-select rounded-3">
                                    <option value="">{{ __('Pilih jenis kelamin') }}</option>
                                    <option value="Laki-laki">{{ __('Laki-laki') }}</option>
                                    <option value="Perempuan">{{ __('Perempuan') }}</option>
                                </select>
                                <div class="field-error"></div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">{{ __('No. Telepon / WA') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone_number" id="teacherPhone" class="form-control rounded-3" placeholder="{{ __('10-15 digit') }}" inputmode="numeric" maxlength="15" autocomplete="off">
                                <div class="field-error"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold" id="btnSaveTeacher" disabled>{{ __('Simpan Data Guru') }}</button>
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
console.log('[import-handler] aktif');
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
        if (printBtn) {
            printBtn.addEventListener('click', function (e) {
                if (this.disabled || this.classList.contains('is-disabled') || this.getAttribute('aria-disabled') === 'true') {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        }
    })();

    /* Pesan error kecil di bawah field (clear + set) */
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

    /* Validasi form Tambah Guru (JS murni) */
    function cekValiditasGuru(showErrors) {
        const name = document.getElementById('teacherName');
        const nip = document.getElementById('teacherNip');
        const jk = document.getElementById('teacherGender');
        const phone = document.getElementById('teacherPhone');
        let ok = true;

        function apply(field, message) {
            if (showErrors || !message) {
                setFieldError(field, message);
            }
        }

        const namaVal = (name ? name.value : '').trim();
        if (namaVal === '') {
            apply(name, 'Nama guru wajib diisi.');
            ok = false;
        } else if (!/^[\p{L}\s.,'\-]+$/u.test(namaVal)) {
            apply(name, 'Format nama hanya boleh huruf dan tanda baca.');
            ok = false;
        } else {
            apply(name, '');
        }

        const nipVal = (nip ? nip.value : '').trim();
        if (!/^(\d{16}|\d{18})$/.test(nipVal)) {
            apply(nip, 'NIP/NUPTK harus 16 atau 18 digit angka.');
            ok = false;
        } else {
            apply(nip, '');
        }

        const jkVal = (jk ? jk.value : '').trim();
        if (jkVal === '') {
            apply(jk, 'Pilih jenis kelamin.');
            ok = false;
        } else {
            apply(jk, '');
        }

        const phoneVal = (phone ? phone.value : '').trim();
        if (!/^\d{10,15}$/.test(phoneVal)) {
            apply(phone, 'Nomor telepon harus 10-15 digit angka.');
            ok = false;
        } else {
            apply(phone, '');
        }

        return ok;
    }

    /* Tombol Simpan aktif bila semua kolom wajib terisi dan valid */
    function updateTombolSimpan() {
        var btn = document.getElementById('btnSaveTeacher');
        if (!btn || btn.dataset.saving === '1') return;
        var isValid = cekValiditasGuru(false);
        btn.disabled = !isValid;
        btn.setAttribute('aria-disabled', isValid ? 'false' : 'true');
    }

    /* Sanitasi input NIP: hanya angka, maks 18 digit */
    function sanitizeNipInput(e) {
        const el = e.target;
        if (!el || el.id !== 'teacherNip') return;
        const before = el.value;
        const cleaned = before.replace(/[^0-9]/g, '').slice(0, 18);
        if (cleaned !== before) {
            const start = el.selectionStart;
            el.value = cleaned;
            const newStart = Math.min(start, el.value.length);
            el.setSelectionRange(newStart, newStart);
        }
    }

    /* Sanitasi input No Telepon: hanya angka, maks 15 digit */
    function sanitizePhoneInput(e) {
        const el = e.target;
        if (!el || el.id !== 'teacherPhone') return;
        const before = el.value;
        const cleaned = before.replace(/[^0-9]/g, '').slice(0, 15);
        if (cleaned !== before) {
            const start = el.selectionStart;
            el.value = cleaned;
            const newStart = Math.min(start, el.value.length);
            el.setSelectionRange(newStart, newStart);
        }
    }

    /* Sanitasi input Nama: hapus angka saat mengetik */
    function sanitizeNameInput(e) {
        const el = e.target;
        if (!el || el.id !== 'teacherName') return;
        const before = el.value;
        const cleaned = before.replace(/\p{Nd}/gu, '');
        if (cleaned !== before) {
            const start = el.selectionStart;
            el.value = cleaned;
            const newStart = Math.min(start, el.value.length);
            el.setSelectionRange(newStart, newStart);
        }
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str == null ? '' : String(str)));
        return div.innerHTML;
    }

    /* Handler form Tambah Guru */
    (function () {
        var nameEl = document.getElementById('teacherName');
        var nipEl = document.getElementById('teacherNip');
        var jkEl = document.getElementById('teacherGender');
        var phoneEl = document.getElementById('teacherPhone');
        var form = document.getElementById('addTeacherForm');
        if (!form) return;

        function attach(el, evt, fn) {
            if (!el) return;
            el.addEventListener(evt, fn);
        }

        attach(nipEl, 'input', sanitizeNipInput);
        attach(phoneEl, 'input', sanitizePhoneInput);
        attach(nameEl, 'input', sanitizeNameInput);

        [nameEl, nipEl, jkEl, phoneEl].forEach(function (el) {
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

        var btnSubmit = document.getElementById('btnSaveTeacher');
        var originalBtnHtml = btnSubmit ? btnSubmit.innerHTML : '';

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            if (!cekValiditasGuru(true)) {
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
                if (modal && modal.id === 'addTeacherModal') {
                    setFieldError(nameEl, '');
                    setFieldError(nipEl, '');
                    setFieldError(jkEl, '');
                    setFieldError(phoneEl, '');
                    cekValiditasGuru(false);
                    updateTombolSimpan();
                }
            });

            document.addEventListener('hidden.bs.modal', function (e) {
                var modal = e.target;
                if (modal && modal.id === 'addTeacherModal') {
                    form.reset();
                    setFieldError(nameEl, '');
                    setFieldError(nipEl, '');
                    setFieldError(jkEl, '');
                    setFieldError(phoneEl, '');
                    updateTombolSimpan();
                }
            });
        }

        updateTombolSimpan();
    })();

    /* Konfirmasi hapus SATU guru */
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

    /* Konfirmasi hapus SEMUA guru */
    function confirmDeleteAllTeachers() {
        if (typeof confirmUniversalDelete === 'function') {
            confirmUniversalDelete({
                title: {!! json_encode(__('Pindahkan SEMUA guru aktif ke Tempat Sampah?')) !!},
                icon: 'bx-error-circle',
                html: {!! json_encode(__('Seluruh data guru aktif akan dihapus dan dipindahkan ke')) !!} + ' <strong>' + {!! json_encode(__('Tempat Sampah')) !!} + '</strong> ' + {!! json_encode(__('di Pengaturan.')) !!},
                confirmText: {!! json_encode(__('Hapus Semua')) !!},
                cancelText: {!! json_encode(__('Tidak')) !!},
                checks: [
                    {!! json_encode(__('Saya memahami data akan dipindahkan ke Tempat Sampah dan dapat dipulihkan.')) !!},
                    {!! json_encode(__('Saya yakin ingin menghapus semua data guru aktif.')) !!}
                ],
                onConfirm: function () {
                    document.getElementById('deleteAllTeachersForm').submit();
                }
            });
        } else if (confirm({!! json_encode(__('Pindahkan SEMUA guru aktif ke Tempat Sampah?')) !!})) {
            document.getElementById('deleteAllTeachersForm').submit();
        }
    }

    window.confirmDeleteTeacher = confirmDeleteTeacher;
    window.confirmDeleteAllTeachers = confirmDeleteAllTeachers;
</script>
@endpush
