@extends('layouts.app')

@section('title', 'Data Kelas')
@section('page_title', 'Data Kelas')
@section('page_subtitle', 'Kelola rombongan belajar dan penetapan wali kelas.')

@push('styles')
<style>
    /* Zebra Striping Khusus (disamakan persis dengan Data Guru & Data Siswa) */
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
           kolom-kolomnya dipipihkan. Semua kolom (NO, KELAS, NAMA KELAS,
           STATUS WALI KELAS, JUMLAH SISWA, AKSI) ikut dihitung; nilai desktop
           tidak berubah karena tabel selalu lebih lebar dari ini. */
        min-width: 720px;
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

    /* Header tabel Data Kelas: nilai DESKTOP (>= 1024px) tidak berubah. */
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

    /* ===== Action Bar: SATU BARIS RATA (pola sama persis dengan Data Siswa & Data Guru) =====
       [input cari][ikon search][dropdown tingkat] ..... [IMPORT EXCEL][TAMBAH KELAS][HAPUS].
       Semua elemen tinggi 38px dan sejajar vertikal; ikon search MENEMPEL di samping input
       (flex-wrap: nowrap pada .search-box-wrap), bukan turun ke baris kedua. */
    .action-bar-section {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem 1rem;
    }

    .action-bar-section .action-search-form {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        flex: 1 1 380px;
        min-width: 0;
        width: auto;
        margin: 0;
    }

    /* Input pencarian + ikon search = satu kesatuan, tidak pernah turun baris */
    .action-bar-section .search-box-wrap {
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
    .action-bar-section .search-box-wrap input.form-control {
        flex: 1 1 auto;
        width: 100%;
        min-width: 0;
        height: 38px;
    }
    .action-bar-section .search-box-wrap > .btn {
        flex: 0 0 auto;
        height: 38px;
    }

    .action-bar-section .filter-box-wrap {
        flex: 0 1 200px;
        min-width: 150px;
        max-width: 200px;
        width: auto;
    }
    .action-bar-section .filter-box-wrap select.form-select {
        height: 38px;
    }

    /* Deretan tombol aksi di kanan: lebar mengikuti isi (padding sama seperti Siswa/Guru),
       bukan melebar mengisi ruang. Input yang fleksibel mengisi sisa lebar. */
    .action-bar-section .action-buttons-wrap {
        display: flex;
        flex: 0 1 auto;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-left: auto;
        width: auto;
    }
    .action-bar-section .action-buttons-wrap .btn-solid-pill {
        flex: 0 0 auto;
    }

    @media (max-width: 767.98px) {
        .action-bar-section {
            flex-direction: column;
            align-items: stretch;
        }
        .action-bar-section .action-search-form,
        .action-bar-section .action-buttons-wrap {
            flex: 1 1 auto;
            width: 100%;
            margin-left: 0;
        }
        .action-bar-section .search-box-wrap {
            flex: 1 1 calc(100% - 0.5rem);
        }
        .action-bar-section .filter-box-wrap {
            flex: 1 1 100%;
            max-width: none;
        }
        .action-bar-section .action-buttons-wrap .btn-solid-pill {
            flex: 1 1 calc(50% - 0.25rem);
        }
    }

    /* --------------------------------------------------------------------------
       NOTIFIKASI HALAMAN DATA KELAS (.alert-kelas)
       Struktur notifikasi: [ .alert-kelas-body (ikon + teks) ] [ tombol X ].
       Perataan tombol X (center vertikal, selalu di dalam kotak) dikerjakan
       oleh CSS notifikasi global di layout/app.blade.php supaya identik di
       semua halaman. Di sini hanya sisanya.
       Scoped hanya ke halaman ini -> halaman lain TIDAK ikut.
       -------------------------------------------------------------------------- */
    .alert-kelas-body {
        display: block;
    }

    .btn-solid-pill,
    button.btn-solid-pill,
    a.btn-solid-pill {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        height: 38px !important;
        border-radius: var(--clean-radius) !important; /* token radius bersama (6px), sama dengan .form-select */
        border: none !important;
        font-size: 0.85rem;
        font-weight: 600;
        color: #ffffff;
        white-space: nowrap;
        padding: 0 1rem;
        transition: filter 0.15s ease, transform 0.1s ease;
        box-shadow: none !important;
        /* === UKURAN 3 TOMBOL ACTION BAR DISAMAkan (SAMA NILAI DENGAN DATA SISWA) ===
           Tanpa min-width, tiap tombol lebarnya mengikuti panjang teksnya
           sendiri, sehingga "IMPORT EXCEL" (12 karakter) jauh lebih lebar dari
           "HAPUS" (5 karakter).
           min-width 150px — nilai yang PERSIS sama dengan acuan Data Siswa,
           yang di sana sudah cukup untuk "IMPORT EXCEL" (12 karakter) dan
           "TAMBAH SISWA" (12 karakter). Di halaman ini teks terpanjang juga
           12 karakter ("IMPORT EXCEL" dan "TAMBAH KELAS"), jadi 150px sudah
           cukup dan ketiga tombol benar-benar sama lebar, teksnya rata tengah.
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

    /* Dropdown filter disamakan bentuknya (kotak sudut tumpul) */
    .filter-select-wrap select.form-select,
    .search-filter-group select.form-select {
        border-radius: var(--clean-radius) !important;
        height: 38px;
        font-size: 0.85rem;
    }
</style>
@endpush

@section('content')
    <!-- Alert Notifikasi (3 status import: hijau sukses / kuning sebagian / merah gagal) -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 alert-kelas" role="alert">
        <div class="alert-kelas-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 alert-kelas" role="alert">
        <div class="alert-kelas-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-error-circle fs-5 me-2 text-warning'></i>
                <span>{{ session('warning') }}</span>
            </div>
            @if(session('import_errors') && count(session('import_errors')) > 0)
            <ul class="mb-0 ps-4 small mt-1">
                <li class="fw-semibold">Alasan:</li>
                @foreach(array_slice(session('import_errors'), 0, 3) as $err)
                    <li>{{ $err }}</li>
                @endforeach
                @if(count(session('import_errors')) > 3)
                    <li class="text-muted">...dan {{ count(session('import_errors')) - 3 }} alasan lainnya.</li>
                @endif
            </ul>
            @endif
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 alert-kelas" role="alert">
        <div class="alert-kelas-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
                <span>{{ session('error') }}</span>
            </div>
            @if(session('import_errors') && count(session('import_errors')) > 0)
            <ul class="mb-0 ps-4 small mt-1">
                <li class="fw-semibold">Alasan:</li>
                @foreach(array_slice(session('import_errors'), 0, 3) as $err)
                    <li>{{ $err }}</li>
                @endforeach
                @if(count(session('import_errors')) > 3)
                    <li class="text-muted">...dan {{ count(session('import_errors')) - 3 }} alasan lainnya.</li>
                @endif
            </ul>
            @endif
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    {{-- Rincian baris Excel yang dilewati ketika tidak ada pesan utama (3 status) --}}
    @if(!session('success') && !session('warning') && !session('error') && session('import_errors') && count(session('import_errors')) > 0)
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 alert-kelas" role="alert">
        <div class="alert-kelas-body">
            <div class="d-flex align-items-center mb-1">
                <i class='bx bx-error-circle fs-5 me-2 text-warning'></i>
                <strong>Baris yang dilewati:</strong>
            </div>
            <ul class="mb-0 ps-3 small">
                @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    @if($errors->any())
    {{-- Error validasi form: TETAP tampil (tidak hilang otomatis) sampai user
         memperbaikinya atau menekan tombol X. --}}
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 alert-kelas" role="alert" data-flash-persist>
        <div class="alert-kelas-body">
            <div class="fw-bold mb-1"><i class='bx bx-error me-1'></i> Terjadi kesalahan input:</div>
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
    {{-- Form Hapus Semua Kelas (Hidden, dipanggil via confirmDeleteAllClasses()) --}}
    <form id="deleteAllClassesForm" action="{{ panel_route('classes.destroy-all') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
    @endif

    <!-- ========================================================================= -->
    <!-- KARTU UTAMA MASTER DATA KELAS: CLEAN ACTION BAR & TABEL TERPADU           -->
    <!-- (Layout & container disamakan persis dengan Data Guru & Data Siswa)      -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white" id="daftar-kelas">

        <!-- Action Bar & Filter (Search+Dropdown Stretch Memanjang, Tombol Radius Konsisten) -->
        <div class="p-3 p-md-4 border-bottom border-gray-100 bg-white">
            <div class="action-bar-section">

                <!-- Sisi Kiri: Form Pencarian & Dropdown Tingkat Kelas (satu baris sejajar) -->
                <form method="GET" action="{{ url()->current() }}" class="action-search-form">
                    <!-- Search Bar: input + ikon search satu kesatuan (tidak turun baris) -->
                    <div class="input-group search-box-wrap">
                        <input type="text" 
                               name="search" 
                               id="classSearchInput"
                               class="form-control shadow-none" 
                               placeholder="Cari nama kelas..." 
                               value="{{ request('search') }}"
                               aria-label="Cari nama kelas"
                               autocomplete="off"
                               style="font-size: 0.85rem; letter-spacing: 0.03em;">
                        <button class="btn shadow-none" type="submit" title="Cari" aria-label="Cari">
                            <i class='bx bx-search fs-6'></i>
                        </button>
                    </div>

                    <!-- Dropdown Filter Tingkat Kelas: ganti tingkat -> langsung memuat ulang -->
                    <div class="filter-box-wrap">
                        <select name="grade" id="classGradeFilter" onchange="this.form.submit()" class="form-select border-secondary-subtle shadow-none fw-normal w-100" style="font-size: 0.82rem; letter-spacing: 0.02em;" aria-label="Pilih tingkat kelas">
                            <option value="">Semua Tingkat</option>
                            <option value="7" {{ request('grade') == '7' ? 'selected' : '' }}>Kelas 7</option>
                            <option value="8" {{ request('grade') == '8' ? 'selected' : '' }}>Kelas 8</option>
                            <option value="9" {{ request('grade') == '9' ? 'selected' : '' }}>Kelas 9</option>
                        </select>
                    </div>

                </form>

                @if(Auth::check() && Auth::user()->role === 'admin')
                <!-- Sisi Kanan: Deretan Tombol Aksi (Stacked Layout di Mobile) -->
                <div class="action-buttons-wrap">
                    @if(Route::has('admin.classes.import'))
                    <!-- 1. Import Excel (Solid Hijau) -->
                    <button type="button" class="btn-solid-pill btn-solid-green" data-bs-toggle="modal" data-bs-target="#importClassModal">
                        <span>Import Excel</span>
                    </button>
                    @endif

                    <!-- 2. Tambah Kelas (Solid Biru, Aksi Utama) -->
                    <button type="button" class="btn-solid-pill btn-solid-blue" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <span>Tambah Kelas</span>
                    </button>

                    <!-- 3. Hapus Semua Kelas (Solid Merah - Urutan Terakhir) -->
                    <button type="button" class="btn-solid-pill btn-solid-red" title="Hapus Semua Kelas" onclick="confirmDeleteAllClasses()">
                        <span>Hapus</span>
                    </button>
                </div>
                @endif

            </div>
        </div>

        {{-- Tabel Master Data Kelas (Alignment & style disamakan persis dengan Data Guru & Data Siswa) --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <thead class="bg-slate-50 border-b border-gray-100 text-blue-500">
                    <tr class="align-middle text-blue-500 text-xs font-bold uppercase tracking-wider border-b border-gray-100">
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 1%; min-width: 45px;">NO</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 1%; min-width: 80px;">KELAS</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100">NAMA KELAS</th>
                        <th class="text-start indent-nama py-3 text-nowrap px-3 pe-4 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100">STATUS WALI KELAS</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100">JUMLAH SISWA</th>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 180px;">AKSI</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $index => $class)
                    <tr class="align-middle {{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td data-label="No" class="text-center text-nowrap px-3">{{ $classes->firstItem() + $index }}</td>
                        <td data-label="Kelas" class="text-center text-nowrap px-3">
                            {{ $class->grade ?? ($class->level == 'VII' ? '7' : ($class->level == 'VIII' ? '8' : '9')) }}
                        </td>
                        <td data-label="Nama Kelas" class="text-center text-nowrap fw-semibold text-dark px-3">
                            {{ $class->name }}
                        </td>
                        <td data-label="Status Wali Kelas" class="text-start text-nowrap indent-nama px-3 pe-4 text-secondary">
                            {{ $class->teacher->name ?? 'Belum Ditentukan' }}
                        </td>
                        <td data-label="Jumlah Siswa" class="text-center text-nowrap px-3">
                            {{ $class->students_count }} Siswa
                        </td>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <td data-label="Aksi" class="text-center text-nowrap px-3">
                            <div class="crud-center-wrapper">
                                <!-- Tombol Edit Modal -->
                                <button type="button" class="btn-row-action action-edit" data-bs-toggle="modal" data-bs-target="#editClassModal{{ $class->id }}" title="Edit" aria-label="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </button>

                                <!-- Tombol Hapus Satuan -->
                                <button type="button" class="btn-row-action action-delete" onclick="confirmDeleteClass('{{ $class->id }}', '{{ addslashes($class->name) }}')" title="Hapus" aria-label="Hapus">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                                <form id="deleteClassForm-{{ $class->id }}" action="{{ panel_route('classes.destroy', $class->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr class="align-middle">
                        <td colspan="{{ Auth::check() && Auth::user()->role === 'admin' ? 6 : 5 }}" class="text-center py-5 text-muted text-nowrap empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            @if(request('search') || request('grade'))
                                Tidak ada data kelas yang cocok dengan pencarian/filter.
                            @else
                                Belum ada data kelas yang terdaftar.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {!! render_compact_pagination($classes, 'daftar-kelas') !!}
    </div>

@if(Auth::check() && Auth::user()->role === 'admin')
<!-- ================= MODAL EDIT KELAS (DIPINDAHKAN KE LUAR TABEL) ================= -->
@foreach($classes as $class)
<div class="modal fade" id="editClassModal{{ $class->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Edit Data Kelas {{ $class->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ panel_route('classes.update', $class->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Kelas</label>
                        <input type="text" name="name" class="form-control rounded-3" value="{{ $class->name }}" placeholder="Contoh: 7A, 8B, 9C" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Kelas</label>
                        <select name="grade" class="form-select rounded-3" required>
                            <option value="7" {{ ($class->grade == '7' || $class->level == 'VII') ? 'selected' : '' }}>Kelas 7</option>
                            <option value="8" {{ ($class->grade == '8' || $class->level == 'VIII') ? 'selected' : '' }}>Kelas 8</option>
                            <option value="9" {{ ($class->grade == '9' || $class->level == 'IX') ? 'selected' : '' }}>Kelas 9</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Wali Kelas</label>
                        <select name="teacher_id" class="form-select rounded-3">
                            <option value="">-- Belum Ditentukan --</option>
                            @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ $class->teacher_id == $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }} (NIP: {{ $teacher->nip }})
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">Wali kelas diambil dinamis dari master data guru.</div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- MODAL TAMBAH KELAS -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Tambah Kelas Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ panel_route('classes.store') }}" method="POST">
                @csrf
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Kelas</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: 7A, 8B, 9C" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Kelas</label>
                        <select name="grade" class="form-select rounded-3" required>
                            <option value="7">Kelas 7</option>
                            <option value="8">Kelas 8</option>
                            <option value="9">Kelas 9</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Pilih Wali Kelas (Opsional)</label>
                        <select name="teacher_id" class="form-select rounded-3">
                            <option value="">-- Pilih Wali Kelas --</option>
                            @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">
                                {{ $teacher->name }} (NIP: {{ $teacher->nip }})
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">Pilihan wali kelas diambil dinamis dari tabel guru.</div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(Route::has('admin.classes.import'))
<!-- MODAL IMPORT EXCEL KELAS -->
<div class="modal fade" id="importClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom-0">
                <h5 class="fw-bold mb-0">Import Data Kelas Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ panel_route('classes.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="p-3 bg-white border rounded-3 small text-secondary mb-3">
                        <div class="mb-2">
                            Format kolom file Excel: <strong>Nama Kelas, Tingkat (7/8/9)</strong> (.xlsx atau .csv)
                        </div>
                        @if(Route::has('admin.classes.template'))
                        <a href="{{ panel_route('classes.template') }}" class="btn-download-green w-100" data-download>Unduh Template Excel</a>
                        @endif
                    </div>
                    <label class="form-label small fw-semibold">Pilih File Excel</label>
                    <input type="file" name="file_excel" class="form-control rounded-3" accept=".xlsx,.xls,.csv" required>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-download-green px-4" data-import>Unggah &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endif

@endsection

@if(Auth::check() && Auth::user()->role === 'admin')
@push('scripts')
<script>
    function confirmDeleteClass(id, name) {
        confirmUniversalDelete({
            title: 'Hapus Data Kelas?',
            html: `Rombel kelas <strong>${name}</strong> akan dihapus. Data bisa dipulihkan dari Tempat Sampah di Pengaturan.`,
            confirmText: 'Hapus',
            cancelText: 'Tidak',
            onConfirm: function() {
                document.getElementById(`deleteClassForm-${id}`).submit();
            }
        });
    }

    function confirmDeleteAllClasses() {
        confirmUniversalDelete({
            title: 'Hapus Seluruh Data Kelas?',
            icon: 'bx-error-circle',
            html: 'Seluruh data kelas aktif akan dihapus dan dipindahkan ke <strong>Tempat Sampah</strong> di Pengaturan. Kelas yang masih memiliki siswa aktif akan dilewati.',
            confirmText: 'Hapus Semua',
            cancelText: 'Tidak',
            checks: [
                'Saya memahami data akan dipindahkan ke Tempat Sampah dan dapat dipulihkan.',
                'Saya yakin ingin menghapus semua data kelas aktif.'
            ],
            onConfirm: function() {
                document.getElementById('deleteAllClassesForm').submit();
            }
        });
    }
</script>
@endpush
@endif