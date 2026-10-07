@extends('layouts.app')

@section('title', 'Data Guru')
@section('page_title', 'Data Guru')
@section('page_subtitle', 'Kelola data guru pendidik')

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
</style>
@endpush

@push('styles')
<style>
    /* Zebra Striping Khusus (disamakan persis dengan Data Siswa) */
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
        /* Lantai lebar tabel: di layar sempit tabel digeser kiri-kanan, bukan
           kolom-kolomnya dipipihkan. Semua kolom (NO, NIP, NAMA LENGKAP GURU,
           JENIS KELAMIN, TUGAS KELAS, NO. TELEPON / WA, AKSI) ikut dihitung;
           nilai desktop tidak berubah karena tabel selalu lebih lebar dari ini. */
        min-width: 760px;
        margin-bottom: 0;
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
            padding-left: 2rem !important;
        }
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
    }

    /* Header tabel Data Guru: nilai DESKTOP (>= 1024px) tidak berubah. */
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

    /* TABLET & HP (<= 1023.98px): header jadi LATAR PUTIH + teks gelap
       semibold + SATU garis tipis (sebelumnya latar abu #f8fafc). Desktop
       >= 1024px sengaja TIDAK disentuh, sesuai batas yang diminta. */
    @media (max-width: 1023.98px) {
        .table-zebra-custom thead th {
            background-color: #ffffff !important;
            color: var(--clean-ink) !important;
            border-bottom: 1px solid var(--clean-line) !important;
        }
    }

    /* ===== Action bar: SATU BARIS RATA (pola sama dengan Data Siswa) ===== */
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

    /* Input pencarian + ikon search = satu kesatuan, tidak pernah turun baris */
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

    /* Deretan tombol aksi di kanan: tidak mengecil dan tidak menumpuk */
    .action-buttons-wrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        flex: 0 0 auto;
        margin-left: auto;
    }

    @media (max-width: 767.98px) {
        .action-search-form {
            flex: 1 1 100%;
        }
        .action-search-form .search-box-wrap {
            flex: 1 1 calc(100% - 0.5rem);
        }
        .action-buttons-wrap {
            flex: 1 1 100%;
            margin-left: 0;
        }
        .action-buttons-wrap .btn-solid-pill {
            flex: 1 1 auto;
        }
    }

    /* Notifikasi import: tombol X + posisi diatur CSS notifikasi global di layout.
       Yang khas halaman ini hanya daftar alasan error yang boleh discroll. */
    .guru-import-notes {
        max-height: 200px;
        overflow-y: auto;
        padding-right: 0.5rem;
    }
    .btn-solid-pill,
    button.btn-solid-pill,
    a.btn-solid-pill {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        height: 38px !important;
        border-radius: var(--clean-radius) !important; /* token radius bersama (6px), sama dengan .form-control */
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
        /* === UKURAN 3 TOMBOL ACTION BAR DISAMAkan (SAMA NILAI DENGAN DATA SISWA) ===
           Tanpa min-width, tiap tombol lebarnya mengikuti panjang teksnya
           sendiri, sehingga "IMPORT EXCEL" (12 karakter) jauh lebih lebar dari
           "HAPUS" (5 karakter).
           min-width 150px — nilai yang PERSIS sama dengan acuan Data Siswa,
           yang di sana sudah cukup untuk "IMPORT EXCEL" (12 karakter) dan
           "TAMBAH SISWA" (12 karakter). Di halaman ini teks terpanjang juga
           12 karakter ("IMPORT EXCEL"), jadi 150px sudah cukup dan ketiga
           tombol benar-benar sama lebar, teksnya rata tengah.
           Tinggi sudah sama sejak awal (height: 38px di atas), dan jarak antar
           tombol juga sudah sama (gap 0.5rem pada .action-buttons-wrap).
           Di bawah 1024px, aturan layout bersama sudah memaksa tiap tombol
           full width satu per satu, jadi tampilan mobile tidak berubah. */
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

    /* Row Action Buttons (Edit/Hapus per baris): Bentuk & Radius SAMA dengan tombol atas */
    .btn-row-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        border: none;
        border-radius: var(--clean-radius) !important;
        padding: 0.32rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 600;
        color: #ffffff;
        white-space: nowrap;
        transition: filter 0.15s ease;
        box-shadow: none !important;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-family: 'Poppins', 'Roboto', sans-serif;
    }
    /* Tombol ikon di kolom AKSI: ukuran seragam 40x34px, ikon di tengah, rounded 10px */
    .btn-row-action {
        width: 40px;
        height: 34px;
        padding: 0;
        font-size: 16px;
        border-radius: 10px !important;
    }
    .btn-row-action svg {
        display: block;
        margin: 0;
        padding: 0;
        width: 20px;
        height: 20px;
    }
    @media (max-width: 767.98px) {
        .btn-row-action {
            width: 36px;
            height: 32px;
        }
        .btn-row-action svg {
            width: 18px;
            height: 18px;
        }
    }
    .btn-row-action:hover {
        filter: brightness(0.94);
        color: #ffffff;
    }
    .btn-row-action.action-edit { background-color: #f59e0b; }
    .btn-row-action.action-delete { background-color: #dc2626; }

    /* Search bar disamakan bentuknya (kotak sudut tumpul) */
    .search-input-wrap input.form-control,
    .search-filter-group input.form-control,
    .search-filter-group .btn {
        border-radius: var(--clean-radius) !important;
    }
    .search-input-wrap .input-group input.form-control,
    .search-filter-group .input-group input.form-control {
        border-radius: var(--clean-radius) 0 0 var(--clean-radius) !important;
    }
    .search-input-wrap .input-group button.btn,
    .search-filter-group .input-group button.btn {
        border-radius: 0 var(--clean-radius) var(--clean-radius) 0 !important;
    }
</style>
@endpush

@section('content')

    {{-- Alert Notifikasi: [ .flash-notice-body (ikon + teks) ] [ tombol X ].
         Tombol X center vertikal oleh CSS notifikasi global di layout. --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
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
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    {{-- Notifikasi import Excel. Warna mengikuti session('import_status'):
         success = hijau, warning = kuning, danger = merah (0 baris terimpor).
         Tombol X adalah anak LANGSUNG dari kotak alert supaya center vertikal. --}}
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
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    {{-- Error validasi form: TETAP tampil (tidak hilang otomatis) sampai user
         memperbaikinya atau menekan tombol X. --}}
    @if($errors->any() && !session('open_modal'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert" data-flash-persist>
        <div class="flash-notice-body">
            <div class="fw-bold mb-1"><i class='bx bx-error me-1'></i> Periksa data input:</div>
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    @if(Auth::check() && Auth::user()->role === 'admin')
    {{-- Form Hapus Semua Guru (Hidden, dipanggil via confirmDeleteAllTeachers()) --}}
    <form id="deleteAllTeachersForm" action="{{ panel_route('guru.destroy-all') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
    @endif

    <!-- ========================================================================= -->
    <!-- KARTU UTAMA DATA GURU: CLEAN ACTION BAR & TABEL TERPADU      -->
    <!-- (Layout & container disamakan persis dengan Data Siswa)                  -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white" id="daftar-guru">

        <!-- Action Bar & Search (Stretch Memanjang, Tombol Radius Konsisten) -->
        <div class="p-3.5 p-md-4 border-bottom border-gray-100 bg-white">
            <div class="action-bar-section d-flex flex-column flex-md-row justify-content-between">

                <!-- Sisi Kiri: Form Pencarian -->
                <form method="GET" action="{{ url()->current() }}" class="action-search-form" id="teacherSearchForm">
                    <div class="input-group search-box-wrap">
                        <input type="text"
                               name="search"
                               id="teacherSearchInput"
                               class="form-control shadow-none"
                               placeholder="Cari nama atau NIP..."
                               value="{{ request('search') }}"
                               aria-label="Cari nama atau NIP"
                               autocomplete="off"
                               style="height: 38px; font-size: 0.85rem;">
                        <button class="btn shadow-none" type="submit" title="Cari" aria-label="Cari">
                            <i class='bx bx-search fs-6'></i>
                        </button>
                    </div>

                </form>

                @if(Auth::check() && Auth::user()->role === 'admin')
                <!-- Sisi Kanan: Deretan Tombol Aksi -->
                <div class="action-buttons-wrap">
                    <!-- 1. Import Excel (Solid Hijau) -->
                    <button type="button" class="btn-solid-pill btn-solid-green" data-bs-toggle="modal" data-bs-target="#importTeacherModal">
                        <span>Import Excel</span>
                    </button>

                    <!-- 2. Tambah Guru (Solid Biru, Aksi Utama) -->
                    <button type="button" class="btn-solid-pill btn-solid-blue" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                        <span>Tambah Guru</span>
                    </button>

                    <!-- 3. Hapus Semua Guru (Solid Merah - Urutan Terakhir) -->
                    <button type="button" class="btn-solid-pill btn-solid-red" title="Hapus Semua Data Guru" onclick="confirmDeleteAllTeachers()">
                        <span>HAPUS</span>
                    </button>
                </div>
                @endif

            </div>
        </div>

        {{-- Tabel Data Guru (Alignment & style disamakan persis dengan Data Siswa) --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <thead class="bg-slate-50 border-b border-gray-100 text-blue-500">
                    <tr class="align-middle text-blue-500 text-xs font-bold uppercase tracking-wider border-b border-gray-100">
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 1%; min-width: 45px;">NO</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 12%;">NIP</th>
                        <th class="text-start indent-nama py-3 text-nowrap px-3 pe-4 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100">NAMA LENGKAP GURU</th>
                        <th class="text-start py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100">JENIS KELAMIN</th>
                        <th class="text-start py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100">TUGAS KELAS</th>
                        <th class="text-start py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100">NO. TELEPON / WA</th>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 180px;">AKSI</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $index => $teacher)
                    @php
                        $assignedClass = $teacher->schoolClass ?? null;
                    @endphp
                    <tr class="align-middle {{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td data-label="No" class="text-center text-nowrap px-3">{{ $teachers->firstItem() + $index }}</td>
                        <td data-label="NIP" class="text-center text-nowrap font-monospace px-3">{{ $teacher->nip ?? '-' }}</td>
                        <td data-label="Nama Guru" class="text-start text-nowrap indent-nama fw-semibold text-dark px-3 pe-4">{{ $teacher->name }}</td>
                        <td data-label="Jenis Kelamin" class="text-start text-nowrap px-3">
                            {{ ($teacher->gender == 'Perempuan' || $teacher->gender == 'P') ? 'Perempuan' : 'Laki-laki' }}
                        </td>
                        <td data-label="Tugas Kelas" class="text-start text-nowrap px-3 text-secondary">
                            @if($assignedClass)
                                Kelas {{ $assignedClass->name }}
                            @else
                                Guru Pengajar
                            @endif
                        </td>
                        <td data-label="No. Telepon / WA" class="text-start text-nowrap px-3 text-secondary">
                            {{ $teacher->phone_number ?? $teacher->phone ?? '-' }}
                        </td>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <td data-label="Aksi" class="text-center text-nowrap px-3">
                            <div class="crud-center-wrapper">
                                <!-- 1. Tombol Edit Guru -->
                                <button type="button" class="btn-row-action action-edit"
                                        title="Edit Data Guru"
                                        aria-label="Edit Data Guru"
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

                                <!-- 2. Tombol Hapus Guru -->
                                <button type="button" class="btn-row-action action-delete"
                                        title="Hapus Data Guru"
                                        aria-label="Hapus Data Guru"
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
                                Tidak ada data guru yang cocok dengan pencarian.
                            @else
                                BELUM ADA DATA GURU YANG TERDAFTAR.
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
    <!-- ========================================================================= -->
    <!-- MODAL 1: TAMBAH GURU                                                      -->
    <!-- ========================================================================= -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">Tambah Data Guru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ panel_route('guru.store') }}" method="POST" novalidate id="addTeacherForm" data-teacher-form>
                    @csrf
                    <div class="modal-body py-3">
                        <div class="alert alert-primary border-0 rounded-3 py-2 px-3 small mb-3 d-flex align-items-center gap-2" style="background-color: #eff6ff; color: #1d4ed8;">
                            <i class='bx bx-info-circle fs-5 flex-shrink-0'></i>
                            <span>Akun login guru otomatis dibuat dengan <strong>NIP</strong> sebagai kata sandi default.</span>
                        </div>

                        @if(session('open_modal') === 'add' && session('form_error'))
                        <div class="alert alert-danger border-0 rounded-3 py-2 px-3 small mb-3">
                            <i class='bx bx-x-circle fs-6 me-1'></i> {{ session('form_error') }}
                        </div>
                        @endif

                        @if(session('open_modal') === 'add' && $errors->any())
                        <div class="alert alert-danger border-0 rounded-3 py-2 px-3 small mb-3">
                            <i class='bx bx-error-circle fs-6 me-1'></i> Data belum bisa disimpan. Periksa isian yang bertanda merah.
                        </div>
                        @endif

                        <div class="text-muted mb-3" style="font-size: 0.72rem;"><span class="text-danger">*</span> wajib diisi</div>

                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label small fw-semibold" for="add_name">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="add_name" class="form-control rounded-3 {{ $errors->has('name') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" placeholder="Contoh: Dra. Hj. Siti Aminah, M.Pd" value="{{ session('open_modal') === 'add' ? old('name') : '' }}" required maxlength="255">
                                <div class="invalid-feedback {{ $errors->has('name') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="name">{{ session('open_modal') === 'add' ? $errors->first('name') : '' }}</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold" for="add_nip">NIP (Nomor Induk Pegawai) <span class="text-danger">*</span></label>
                                <input type="text" name="nip" id="add_nip" class="form-control rounded-3 {{ $errors->has('nip') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" placeholder="18 digit angka" value="{{ session('open_modal') === 'add' ? old('nip') : '' }}" inputmode="numeric" pattern="[0-9]{18}" maxlength="18" required>
                                <div class="invalid-feedback {{ $errors->has('nip') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="nip">{{ session('open_modal') === 'add' ? $errors->first('nip') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="add_gender">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="gender" id="add_gender" class="form-select rounded-3 {{ $errors->has('gender') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" required>
                                    <option value="Laki-laki" {{ session('open_modal') === 'add' && old('gender') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="Perempuan" {{ session('open_modal') === 'add' && old('gender') === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                <div class="invalid-feedback {{ $errors->has('gender') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="gender">{{ session('open_modal') === 'add' ? $errors->first('gender') : '' }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="add_class">Penugasan Kelas</label>
                                <select name="school_class_id" id="add_class" class="form-select rounded-3 {{ $errors->has('school_class_id') && session('open_modal') === 'add' ? 'is-invalid' : '' }}">
                                    <option value="">-- Tidak Ditugaskan (Guru Pengajar Saja) --</option>
                                    @foreach($classes as $c)
                                    <option value="{{ $c->id }}" {{ session('open_modal') === 'add' && (string) old('school_class_id') === (string) $c->id ? 'selected' : '' }}>Kelas {{ $c->name }} (Tingkat {{ $c->grade ?? $c->level }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback {{ $errors->has('school_class_id') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="school_class_id">{{ session('open_modal') === 'add' ? $errors->first('school_class_id') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="add_birth_place">Tempat Lahir</label>
                                <input type="text" name="birth_place" id="add_birth_place" class="form-control rounded-3 {{ $errors->has('birth_place') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" placeholder="Kota / Kabupaten Lahir" value="{{ session('open_modal') === 'add' ? old('birth_place') : '' }}" maxlength="100">
                                <div class="invalid-feedback {{ $errors->has('birth_place') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="birth_place">{{ session('open_modal') === 'add' ? $errors->first('birth_place') : '' }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="add_birth_date">Tanggal Lahir</label>
                                <input type="date" name="birth_date" id="add_birth_date" class="form-control rounded-3 {{ $errors->has('birth_date') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'add' ? old('birth_date') : '' }}">
                                <div class="invalid-feedback {{ $errors->has('birth_date') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="birth_date">{{ session('open_modal') === 'add' ? $errors->first('birth_date') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="add_phone_number">No. Telepon / WhatsApp <span class="text-danger">*</span></label>
                                <input type="text" name="phone_number" id="add_phone_number" class="form-control rounded-3 {{ $errors->has('phone_number') && session('open_modal') === 'add' ? 'is-invalid' : '' }}" placeholder="08xxxxxxxxxx (10-15 digit)" value="{{ session('open_modal') === 'add' ? old('phone_number') : '' }}" inputmode="numeric" pattern="[0-9]{10,15}" maxlength="15" required>
                                <div class="invalid-feedback {{ $errors->has('phone_number') && session('open_modal') === 'add' ? 'd-block' : '' }}" data-error-for="phone_number">{{ session('open_modal') === 'add' ? $errors->first('phone_number') : '' }}</div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Simpan Data Guru</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: EDIT GURU                                                        -->
    <!-- ========================================================================= -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">Edit Data Guru</h5>
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
                            <i class='bx bx-error-circle fs-6 me-1'></i> Data belum bisa disimpan. Periksa isian yang bertanda merah.
                        </div>
                        @endif

                        <div class="text-muted mb-3" style="font-size: 0.72rem;"><span class="text-danger">*</span> wajib diisi</div>

                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label small fw-semibold" for="edit_name">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control rounded-3 {{ $errors->has('name') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'edit' ? old('name') : '' }}" required maxlength="255">
                                <div class="invalid-feedback {{ $errors->has('name') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="name">{{ session('open_modal') === 'edit' ? $errors->first('name') : '' }}</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold" for="edit_nip">NIP <span class="text-danger">*</span></label>
                                <input type="text" name="nip" id="edit_nip" class="form-control rounded-3 {{ $errors->has('nip') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'edit' ? old('nip') : '' }}" inputmode="numeric" pattern="[0-9]{18}" maxlength="18" required>
                                <div class="invalid-feedback {{ $errors->has('nip') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="nip">{{ session('open_modal') === 'edit' ? $errors->first('nip') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="edit_gender">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="gender" id="edit_gender" class="form-select rounded-3 {{ $errors->has('gender') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" required>
                                    <option value="Laki-laki" {{ session('open_modal') === 'edit' && old('gender') === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="Perempuan" {{ session('open_modal') === 'edit' && old('gender') === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                <div class="invalid-feedback {{ $errors->has('gender') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="gender">{{ session('open_modal') === 'edit' ? $errors->first('gender') : '' }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="edit_class_id">Penugasan Kelas</label>
                                <select name="school_class_id" id="edit_class_id" class="form-select rounded-3 {{ $errors->has('school_class_id') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}">
                                    <option value="none">-- Tidak Ditugaskan (Guru Pengajar Saja) --</option>
                                    @foreach($classes as $c)
                                    <option value="{{ $c->id }}" {{ session('open_modal') === 'edit' && (string) old('school_class_id') === (string) $c->id ? 'selected' : '' }}>Kelas {{ $c->name }} (Tingkat {{ $c->grade ?? $c->level }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback {{ $errors->has('school_class_id') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="school_class_id">{{ session('open_modal') === 'edit' ? $errors->first('school_class_id') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="edit_birth_place">Tempat Lahir</label>
                                <input type="text" name="birth_place" id="edit_birth_place" class="form-control rounded-3 {{ $errors->has('birth_place') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'edit' ? old('birth_place') : '' }}" maxlength="100">
                                <div class="invalid-feedback {{ $errors->has('birth_place') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="birth_place">{{ session('open_modal') === 'edit' ? $errors->first('birth_place') : '' }}</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="edit_birth_date">Tanggal Lahir</label>
                                <input type="date" name="birth_date" id="edit_birth_date" class="form-control rounded-3 {{ $errors->has('birth_date') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'edit' ? old('birth_date') : '' }}">
                                <div class="invalid-feedback {{ $errors->has('birth_date') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="birth_date">{{ session('open_modal') === 'edit' ? $errors->first('birth_date') : '' }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="edit_phone">No. Telepon / WhatsApp <span class="text-danger">*</span></label>
                                <input type="text" name="phone_number" id="edit_phone" class="form-control rounded-3 {{ $errors->has('phone_number') && session('open_modal') === 'edit' ? 'is-invalid' : '' }}" value="{{ session('open_modal') === 'edit' ? old('phone_number') : '' }}" inputmode="numeric" pattern="[0-9]{10,15}" maxlength="15" required>
                                <div class="invalid-feedback {{ $errors->has('phone_number') && session('open_modal') === 'edit' ? 'd-block' : '' }}" data-error-for="phone_number">{{ session('open_modal') === 'edit' ? $errors->first('phone_number') : '' }}</div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning text-white rounded-3 px-4 fw-semibold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: IMPORT EXCEL                                                     -->
    <!-- ========================================================================= -->
    <div class="modal fade" id="importTeacherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0">
                    <h5 class="fw-bold mb-0">Import Data Guru dari Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ panel_route('guru.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @if(session('open_modal') === 'import' && $errors->any())
                        <div class="alert alert-danger border-0 rounded-3 py-2 px-3 small mb-3">
                            <i class='bx bx-error-circle fs-6 me-1'></i> Import belum bisa diproses. Periksa isian yang bertanda merah.
                        </div>
                        @endif

                        <div class="mb-3">
                            <a href="{{ panel_route('guru.template') }}" class="btn-download-green w-100" data-download>Unduh Template Excel</a>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pilih File Excel / CSV <span class="text-danger">*</span></label>
                            <input type="file" name="file_excel" class="form-control rounded-3 {{ $errors->has('file_excel') && session('open_modal') === 'import' ? 'is-invalid' : '' }}" accept=".xlsx,.xls,.csv" required>
                            @if($errors->has('file_excel') && session('open_modal') === 'import')
                            <div class="invalid-feedback d-block">{{ $errors->first('file_excel') }}</div>
                            @endif
                            <div class="text-muted mt-1" style="font-size: 0.72rem;">Maksimal ukuran file 5 MB.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-download-green px-4" data-import>Mulai Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
    // Pencarian otomatis saat mengetik (debounce 400ms). Enter & klik ikon search
    // tetap berfungsi karena input berada di dalam form GET yang sama.
    (function () {
        var input = document.getElementById('teacherSearchInput');
        if (!input) return;
        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                input.form.submit();
            }, 400);
        });
        input.form.addEventListener('submit', function () {
            clearTimeout(timer);
        });
    })();
</script>
@endpush

@if(Auth::check() && Auth::user()->role === 'admin')
@push('scripts')
<script>
    // 1. Buka Modal Edit dengan Nilai yang Sudah Terisi
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

    // 4. Buka kembali modal yang gagal divalidasi server (isian & pesan error tetap ada)
    (function () {
        var openModal = @json(session('open_modal'));
        var openTeacherId = @json(session('open_teacher_id'));

        if (openModal === 'add') {
            new bootstrap.Modal(document.getElementById('addTeacherModal')).show();
        } else if (openModal === 'edit' && openTeacherId) {
            var editForm = document.getElementById('editTeacherForm');
            if (editForm) {
                editForm.action = '{{ url('admin/guru') }}/' + openTeacherId;
            }
            new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
        } else if (openModal === 'import') {
            new bootstrap.Modal(document.getElementById('importTeacherModal')).show();
        }
    })();

    // 5. Validasi klien: pesan tampil di dalam form, field salah diberi penanda merah
    (function () {
        var rules = {
            name: {
                validate: function (v) {
                    return v.trim() !== '' ? '' : 'Nama lengkap wajib diisi.';
                }
            },
            nip: {
                digitOnly: true,
                validate: function (v) {
                    if (v.trim() === '') return 'NIP wajib diisi.';
                    return /^[0-9]{18}$/.test(v) ? '' : 'NIP harus 18 digit angka.';
                }
            },
            phone_number: {
                digitOnly: true,
                validate: function (v) {
                    if (v.trim() === '') return 'Nomor telepon wajib diisi.';
                    return /^[0-9]{10,15}$/.test(v) ? '' : 'Nomor telepon harus 10-15 digit angka.';
                }
            }
        };

        function applyError(form, name, msg) {
            var field = form.querySelector('[name="' + name + '"]');
            var box = form.querySelector('[data-error-for="' + name + '"]');
            if (!field || !box) return;
            if (msg) {
                field.classList.add('is-invalid');
                box.textContent = msg;
                box.classList.add('d-block');
            } else {
                field.classList.remove('is-invalid');
                box.textContent = '';
                box.classList.remove('d-block');
            }
        }

        document.querySelectorAll('form[data-teacher-form]').forEach(function (form) {
            form.addEventListener('input', function (e) {
                var t = e.target;
                var rule = rules[t.name];
                if (!rule) return;
                if (rule.digitOnly) {
                    var cleaned = t.value.replace(/[^0-9]/g, '');
                    if (cleaned !== t.value) t.value = cleaned;
                }
                applyError(form, t.name, rule.validate(t.value));
            });

            form.addEventListener('submit', function (e) {
                var first = null;
                Object.keys(rules).forEach(function (name) {
                    var field = form.querySelector('[name="' + name + '"]');
                    if (!field) return;
                    var msg = rules[name].validate(field.value);
                    applyError(form, name, msg);
                    if (msg && !first) first = field;
                });
                if (first) {
                    e.preventDefault();
                    e.stopPropagation();
                    first.focus();
                }
            });
        });
    })();

    // 2. Konfirmasi Hapus Guru Satuan
    function confirmDeleteTeacher(id, name) {
        confirmUniversalDelete({
            title: 'Hapus Data Guru?',
            html: `Data guru <strong>${name}</strong> akan dihapus. Data bisa dipulihkan dari Tempat Sampah di Pengaturan.`,
            confirmText: 'Hapus',
            cancelText: 'Tidak',
            onConfirm: function() {
                document.getElementById('deleteTeacherForm-' + id).submit();
            }
        });
    }

    // 3. Konfirmasi Hapus Semua Guru Massal.
    // Memakai confirmUniversalDelete() dari layout bersama supaya tema,
    // ukuran, animasi, dan tombol nonaktif-hingga-checkbox-nya SAMA PERSIS
    // dengan dialog hapus di halaman lain.
    function confirmDeleteAllTeachers() {
        confirmUniversalDelete({
            title: 'Hapus Seluruh Data Guru?',
            icon: 'bx-error-circle',
            html: 'Seluruh data guru aktif akan dihapus dan dipindahkan ke <strong>Tempat Sampah</strong> di Pengaturan.',
            confirmText: 'Hapus Semua',
            cancelText: 'Tidak',
            checks: [
                'Saya memahami data akan dipindahkan ke Tempat Sampah dan dapat dipulihkan.',
                'Saya yakin ingin menghapus semua data guru aktif.'
            ],
            onConfirm: function () {
                document.getElementById('deleteAllTeachersForm').submit();
            }
        });
    }
</script>
@endpush
@endif