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
        text-transform: uppercase !important;
        letter-spacing: 0.03em;
        font-family: 'Poppins', 'Roboto', sans-serif;
        font-size: 0.78rem;
    }

    .indent-nama {
        padding-left: 1.25rem !important;
    }
    @media (min-width: 992px) {
        .indent-nama {
            padding-left: 4rem !important;
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
    .btn-row-action.action-detail { color: #16a34a; }
    .btn-row-action.action-detail:hover { color: #15803d; }
    .btn-row-action.action-edit { color: #f59e0b; }
    .btn-row-action.action-edit:hover { color: #d97706; }
    .btn-row-action.action-delete { color: #dc2626; }
    .btn-row-action.action-delete:hover { color: #b91c1c; }

    .btn-solid-pill.is-disabled,
    button.btn-solid-pill.is-disabled,
    a.btn-solid-pill.is-disabled {
        background-color: #9ca3af !important;
        cursor: not-allowed !important;
        opacity: 0.6;
    }
    .btn-solid-pill.is-disabled:hover,
    button.btn-solid-pill.is-disabled:hover,
    a.btn-solid-pill.is-disabled:hover {
        filter: none;
        transform: none;
        opacity: 0.6;
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

    @if(session('import_status'))
    @php
        $importStatus = in_array(session('import_status'), ['success', 'warning', 'danger'], true) ? session('import_status') : 'info';
        $importIcons = ['success' => 'bx-check-circle', 'warning' => 'bx-error-circle', 'danger' => 'bx-x-circle', 'info' => 'bx-info-circle'];
    @endphp
    <div class="alert alert-{{ $importStatus }} alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 guru-import-alert" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx {{ $importIcons[$importStatus] }} fs-5 me-2 flex-shrink-0'></i>
                <span>{{ session('import_message') }}</span>
            </div>
            @if(session('import_errors') && count(session('import_errors')) > 0)
            <div class="guru-import-notes mt-2">
                <ul class="mb-0 ps-3 small">
                    @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if($errors->any() && !session('open_modal'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert" data-flash-persist>
        <div class="flash-notice-body">
            <div class="fw-bold mb-1"><i class='bx bx-error me-1'></i> {{ __('Periksa data input:') }}</div>
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

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
                    <button type="button" class="btn-solid-pill btn-solid-blue {{ $totalTeachers == 0 ? 'is-disabled' : '' }}"
                            data-bs-toggle="modal"
                            data-bs-target="#printCardsModal"
                            {{ $totalTeachers == 0 ? 'tabindex="-1" aria-disabled="true" title="Belum ada data untuk dicetak. Buat data guru melalui Pengaturan > Data Guru lalu coba lagi."' : '' }}>
                        <span>{{ __('Cetak Kartu') }}</span>
                    </button>

                    <button type="button" class="btn-solid-pill btn-solid-green" data-bs-toggle="modal" data-bs-target="#importTeacherModal">
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
                    <col style="width: 19%;">
                    <col style="width: 15%;">
                    <col style="width: 16%;">
                    <col style="width: 17%;">
                    <col style="width: 14%;">
                </colgroup>
                <thead class="bg-slate-50 border-b border-gray-100 text-blue-500">
                    <tr class="align-middle text-blue-500 text-xs font-bold uppercase tracking-wider border-b border-gray-100">
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 5%;">{{ __('No') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 15%;">{{ __('NIP') }}</th>
                        <th class="text-start indent-nama py-3 text-nowrap px-3 pe-4 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 19%;">{{ __('Nama Lengkap Guru') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 15%;">{{ __('Jenis Kelamin') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 16%;">{{ __('Tugas Kelas') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 17%;">{{ __('No. Telepon / WA') }}</th>
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
                                <a href="{{ panel_route('guru.show', $teacher->id) }}" class="btn-row-action action-detail" title="{{ __('Detail') }}" aria-label="{{ __('Detail') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>

                                <button type="button" class="btn-row-action action-edit"
                                        title="{{ __('Edit Data Guru') }}"
                                        aria-label="{{ __('Edit Data Guru') }}"
                                        onclick="openEditModal({
                                            id: '{{ $teacher->id }}',
                                            name: '{{ addslashes($teacher->name) }}',
                                            nip: '{{ $teacher->nip ?? '' }}',
                                            gender: '{{ $teacher->gender ?? 'Laki-laki' }}',
                                            birth_place: '{{ addslashes($teacher->birth_place ?? '') }}',
                                            birth_date: '{{ $teacher->birth_date ? $teacher->birth_date->format('Y-m-d') : '' }}',
                                            phone: '{{ $teacher->phone_number ?? $teacher->phone ?? '' }}',
                                            class_id: '{{ $assignedClass?->id ?? '' }}'
                                        })">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </button>

                                <button type="button" class="btn-row-action action-delete"
                                        title="{{ __('Hapus Data Guru') }}"
                                        aria-label="{{ __('Hapus Data Guru') }}"
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
                        <td colspan="{{ Auth::check() && Auth::user()->role === 'admin' ? 7 : 6 }}" class="text-center py-5 text-muted text-nowrap empty-state">
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
    <div class="modal fade" id="addTeacherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">{{ __('Tambah Data Guru') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ panel_route('guru.store') }}" method="POST" novalidate id="addTeacherForm" data-teacher-form>
                    @csrf
                    <div class="modal-body py-3">

                        @if(session('open_modal') === 'add' && session('form_error'))
                        <div class="alert alert-danger border-0 rounded-3 py-2 px-3 small mb-3">
                            <i class='bx bx-x-circle fs-6 me-1'></i> {{ session('form_error') }}
                        </div>
                        @endif

                        @if(session('open_modal') === 'add' && $errors->any())
                        <div class="alert alert-danger border-0 rounded-3 py-2 px-3 small mb-3">
                            <i class='bx bx-error-circle fs-6 me-1'></i> {{ __('Data belum bisa disimpan. Periksa isian yang bertanda merah.') }}
                        </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="add_name">{{ __('Nama Lengkap & Gelar') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="add_name" class="form-control rounded-3 {{ $errors->has('name') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" placeholder="{{ __('Contoh: Dra. Hj. Siti Aminah, M.Pd') }}" value="{{ session('open_modal') === 'add' ? old('name') : '' }}" maxlength="255">
                                <div class="invalid-feedback {{ $errors->has('name') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="name">{{ session('open_modal') === 'add' ? $errors->first('name') : '' }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="add_nip">{{ __('NIP (Nomor Induk Pegawai)') }} <span class="text-danger">*</span></label>
                                <input type="text" name="nip" id="add_nip" class="form-control rounded-3 {{ $errors->has('nip') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" placeholder="16 atau 18 digit angka" inputmode="numeric" maxlength="18">
                                <div class="invalid-feedback {{ $errors->has('nip') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="nip">{{ session('open_modal') === 'add' ? $errors->first('nip') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="add_gender">{{ __('Jenis Kelamin') }} <span class="text-danger">*</span></label>
                                <select name="gender" id="add_gender" class="form-select rounded-3 {{ $errors->has('gender') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" required>
                                    <option value="">{{ __('Pilih jenis kelamin') }}</option>
                                    <option value="Laki-laki" {{ session('open_modal') === 'add' && old('gender') === 'Laki-laki' ? 'selected' : '' }}>{{ __('Laki-laki') }}</option>
                                    <option value="Perempuan" {{ session('open_modal') === 'add' && old('gender') === 'Perempuan' ? 'selected' : '' }}>{{ __('Perempuan') }}</option>
                                </select>
                                <div class="invalid-feedback {{ $errors->has('gender') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="gender">{{ session('open_modal') === 'add' ? $errors->first('gender') : '' }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="add_class">{{ __('Penugasan Kelas') }}</label>
                                <select name="school_class_id" id="add_class" class="form-select rounded-3 {{ $errors->has('school_class_id') && session('open_modal') === 'add' ? 'is-invalid' : '' }}">
                                    <option value="">{{ __('-- Tidak Ditugaskan (Guru Pengajar Saja) --') }}</option>
                                    @foreach($classes as $c)
                                    <option value="{{ $c->id }}" {{ session('open_modal') === 'add' && (string) old('school_class_id') === (string) $c->id ? 'selected' : '' }}>{{ __('Kelas ') }}{{ $c->name }} ({{ __('Tingkat') }} {{ $c->grade ?? $c->level }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback {{ $errors->has('school_class_id') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="school_class_id">{{ session('open_modal') === 'add' ? $errors->first('school_class_id') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="add_birth_place">{{ __('Tempat Lahir') }} <span class="text-danger">*</span></label>
                                <input type="text" name="birth_place" id="add_birth_place" class="form-control rounded-3 {{ $errors->has('birth_place') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" placeholder="{{ __('Kota / Kabupaten Lahir') }}" value="{{ session('open_modal') === 'add' ? old('birth_place') : '' }}" maxlength="100">
                                <div class="invalid-feedback {{ $errors->has('birth_place') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="birth_place">{{ session('open_modal') === 'add' ? $errors->first('birth_place') : '' }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="add_birth_date">{{ __('Tanggal Lahir') }} <span class="text-danger">*</span></label>
                                <input type="date" name="birth_date" id="add_birth_date" class="form-control rounded-3 {{ $errors->has('birth_date') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'add' ? old('birth_date') : '' }}">
                                <div class="invalid-feedback {{ $errors->has('birth_date') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="birth_date">{{ session('open_modal') === 'add' ? $errors->first('birth_date') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="add_phone">{{ __('No. Telepon / WhatsApp') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone_number" id="add_phone" class="form-control rounded-3 {{ $errors->has('phone_number') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" placeholder="{{ __('08xxxxxxxxxx (10-15 digit)') }}" value="{{ session('open_modal') === 'add' ? old('phone_number') : '' }}" inputmode="numeric" pattern="[0-9]{10,15}" maxlength="15" required>
                                <div class="invalid-feedback {{ $errors->has('phone_number') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="phone_number">{{ session('open_modal') === 'add' ? $errors->first('phone_number') : '' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">{{ __('Simpan Data Guru') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTeacherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">{{ __('Edit Data Guru') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editTeacherForm" method="POST" novalidate data-teacher-form>
                    @csrf
                    @method('PUT')
                    <div class="modal-body py-3">
                        @if(session('open_modal') === 'edit' && session('form_error'))
                        <div class="alert alert-danger border-0 rounded-3 py-2 px-3 small mb-3">
                            <i class='bx bx-x-circle fs-6 me-1'></i> {{ session('form_error') }}
                        </div>
                        @endif

                        @if(session('open_modal') === 'edit' && $errors->any())
                        <div class="alert alert-danger border-0 rounded-3 py-2 px-3 small mb-3">
                            <i class='bx bx-error-circle fs-6 me-1'></i> {{ __('Data belum bisa disimpan. Periksa isian yang bertanda merah.') }}
                        </div>
                        @endif

                        <div class="text-muted mb-3" style="font-size: 0.72rem;"><span class="text-danger">*</span> {{ __('wajib diisi') }}</div>

                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label small fw-semibold" for="edit_name">{{ __('Nama Lengkap & Gelar') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control rounded-3 {{ $errors->has('name') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'edit' ? old('name') : '' }}" maxlength="255">
                                <div class="invalid-feedback {{ $errors->has('name') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="name">{{ session('open_modal') === 'edit' ? $errors->first('name') : '' }}</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold" for="edit_nip">{{ __('NIP (Nomor Induk Pegawai)') }} <span class="text-danger">*</span></label>
                                <input type="text" name="nip" id="edit_nip" class="form-control rounded-3 {{ $errors->has('nip') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'edit' ? old('nip') : '' }}" inputmode="numeric" placeholder="16 atau 18 digit angka" maxlength="18" >
                                <div class="invalid-feedback {{ $errors->has('nip') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="nip">{{ session('open_modal') === 'edit' ? $errors->first('nip') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="edit_gender">{{ __('Jenis Kelamin') }} <span class="text-danger">*</span></label>
                                <select name="gender" id="edit_gender" class="form-select rounded-3 {{ $errors->has('gender') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" required>
                                    <option value="">{{ __('Pilih jenis kelamin') }}</option>
                                    <option value="Laki-laki" {{ session('open_modal') === 'edit' && old('gender') === 'Laki-laki' ? 'selected' : '' }}>{{ __('Laki-laki') }}</option>
                                    <option value="Perempuan" {{ session('open_modal') === 'edit' && old('gender') === 'Perempuan' ? 'selected' : '' }}>{{ __('Perempuan') }}</option>
                                </select>
                                <div class="invalid-feedback {{ $errors->has('gender') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="gender">{{ session('open_modal') === 'edit' ? $errors->first('gender') : '' }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="edit_class_id">{{ __('Penugasan Kelas') }}</label>
                                <select name="school_class_id" id="edit_class_id" class="form-select rounded-3 {{ $errors->has('school_class_id') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}">
                                    <option value="">{{ __('-- Tidak Ditugaskan (Guru Pengajar Saja) --') }}</option>
                                    @foreach($classes as $c)
                                    <option value="{{ $c->id }}" {{ session('open_modal') === 'edit' && (string) old('school_class_id') === (string) $c->id ? 'selected' : '' }}>{{ __('Kelas ') }}{{ $c->name }} ({{ __('Tingkat') }} {{ $c->grade ?? $c->level }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback {{ $errors->has('school_class_id') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="school_class_id">{{ session('open_modal') === 'edit' ? $errors->first('school_class_id') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="edit_birth_place">{{ __('Tempat Lahir') }} <span class="text-danger">*</span></label>
                                <input type="text" name="birth_place" id="edit_birth_place" class="form-control rounded-3 {{ $errors->has('birth_place') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'edit' ? old('birth_place') : '' }}" maxlength="100">
                                <div class="invalid-feedback {{ $errors->has('birth_place') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="birth_place">{{ session('open_modal') === 'edit' ? $errors->first('birth_place') : '' }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="edit_birth_date">{{ __('Tanggal Lahir') }} <span class="text-danger">*</span></label>
                                <input type="date" name="birth_date" id="edit_birth_date" class="form-control rounded-3 {{ $errors->has('birth_date') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'edit' ? old('birth_date') : '' }}">
                                <div class="invalid-feedback {{ $errors->has('birth_date') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="birth_date">{{ session('open_modal') === 'edit' ? $errors->first('birth_date') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="edit_phone">{{ __('No. Telepon / WhatsApp') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone_number" id="edit_phone" class="form-control rounded-3 {{ $errors->has('phone_number') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'edit' ? old('phone') : '' }}" inputmode="numeric" pattern="[0-9]{10,15}" maxlength="15" required>
                                <div class="invalid-feedback {{ $errors->has('phone_number') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="phone_number">{{ session('open_modal') === 'edit' ? $errors->first('phone_number') : '' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                        <button type="submit" class="btn btn-warning text-white rounded-3 px-4 fw-semibold">{{ __('Simpan Perubahan') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importTeacherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0">
                    <h5 class="fw-bold mb-0">{{ __('Import Data Guru dari Excel') }}</h5>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="importTeacherFile">{{ __('Pilih File Excel / CSV') }} <span class="text-danger">*</span></label>
                        <input type="file" name="file_excel" id="importTeacherFile" class="form-control rounded-3 @error('file_excel') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                        @error('file_excel')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="text-muted mt-1" style="font-size: 0.72rem;">{{ __('Maksimal ukuran file 5 MB.') }}</div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn-download-green px-4" data-import data-dl-progress>{{ __('Mulai Import') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="printCardsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">{{ __('Cetak Kartu Presensi Guru Massal') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ panel_route('guru.print-cards') }}" method="POST" data-download>
                    @csrf
                    <div class="modal-body py-3">
                        <p class="text-secondary small mb-3">
                            {{ __('Semua guru aktif akan dicetak. Hasil unduhan berupa file ZIP berisi satu kartu (JPG) untuk setiap guru.') }}
                        </p>

                        <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-2">
                            <i class='bx bx-info-circle me-1'></i> {{ __('QR Code kartu guru berisi NIP (jika valid); guru tanpa NIP memakai identifier sistem.') }}
                        </div>

                        <div class="alert alert-warning py-2 px-3 small border-0 rounded-3 mb-0">
                            <i class='bx bx-error-circle me-1'></i> {{ __('Guru tanpa NIP tetap dicetak; QR Code tetap unik.') }}
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                        <button type="submit" class="btn-download-blue btn-progress px-4" data-download data-dl-progress data-dl-progress-mode="persen">{{ __('Unduh ZIP Kartu') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('scripts')
<script>
    function openEditModal(data) {
        document.getElementById('editTeacherForm').action = `{{ url('admin/guru') }}/${data.id}`;
        document.getElementById('edit_name').value = data.name;
        document.getElementById('edit_nip').value = data.nip;
        document.getElementById('edit_gender').value = data.gender;
        document.getElementById('edit_birth_place').value = data.birth_place;
        document.getElementById('edit_birth_date').value = data.birth_date;
        document.getElementById('edit_phone').value = data.phone;
        document.getElementById('edit_class_id').value = data.class_id ? data.class_id : 'none';

        const modal = new bootstrap.Modal(document.getElementById('editTeacherModal'));
        modal.show();
    }
    function confirmDeleteTeacher(id, name) {
        confirmUniversalDelete({
            title: {!! json_encode(__('Hapus Data Guru?')) !!},
            html: {!! json_encode(__('Guru')) !!} + ' <strong>' + name + '</strong> ' + {!! json_encode(__('akan dihapus. Data bisa dipulihkan dari Tempat Sampah di Pengaturan.')) !!},
            confirmText: {!! json_encode(__('Hapus')) !!},
            cancelText: {!! json_encode(__('Tidak')) !!},
            onConfirm: function () {
                const form = document.getElementById(`deleteTeacherForm-${id}`);
                if (!form) {
                    console.error('HAPUS: form deleteTeacherForm-' + id + ' tidak ditemukan. Muat ulang halaman.');
                    return;
                }
                const qs = window.location.search;
                if (qs && form.action.indexOf('?') === -1) {
                    form.action = form.action + qs;
                }
                form.submit();
            }
        });
    }
    function confirmDeleteAllTeachers() {
        confirmUniversalDelete({
            title: {!! json_encode(__('Hapus Seluruh Data Guru?')) !!},
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
    }
</script>
@endpush
