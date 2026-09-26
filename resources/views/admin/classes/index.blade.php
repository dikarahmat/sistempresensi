@extends('layouts.app')

@section('title', 'Master Data Kelas')
@section('page_title', 'Master Data Kelas')
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

    .table-zebra-custom thead th {
        color: #111827 !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em;
        border-top: none !important;
        border-left: none !important;
        border-right: none !important;
        border-bottom: 1px solid #f1f5f9 !important;
        background-color: #f8fafc !important;
    }

    /* ===== Action Bar Layout: Filter Stretch Memanjang, Sejajar 1 Baris (sama seperti Data Siswa/Guru) ===== */
    .action-bar-section {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }
    @media (min-width: 992px) {
        .action-bar-section {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
    }

    /* Responsive Search + Dropdown Filter */
    @media (max-width: 991.98px) {
        .search-box-wrap,
        .filter-box-wrap {
            max-width: 100% !important;
            width: 100% !important;
        }
    }
    @media (min-width: 992px) {
        .search-box-wrap {
            max-width: 350px !important;
        }
        .filter-box-wrap {
            max-width: 200px !important;
        }
    }
    .btn-solid-pill,
    button.btn-solid-pill,
    a.btn-solid-pill {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        height: 38px !important;
        border-radius: 6px !important; /* sama persis dengan .form-select */
        border: none !important;
        font-size: 0.85rem;
        font-weight: 600;
        color: #ffffff;
        white-space: nowrap;
        padding: 0 1rem;
        transition: filter 0.15s ease, transform 0.1s ease;
        box-shadow: none !important;
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
        border-radius: 6px !important;
        padding: 0.32rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 600;
        color: #ffffff;
        white-space: nowrap;
        transition: filter 0.15s ease;
        box-shadow: none !important;
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
        border-radius: 6px !important;
        height: 38px;
        font-size: 0.85rem;
    }
</style>
@endpush

@section('content')
    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5 px-3" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('import_errors'))
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5 px-3" role="alert">
        <div class="d-flex align-items-center mb-1">
            <i class='bx bx-error-circle fs-5 me-2 text-warning'></i>
            <strong>Beberapa baris data kelas gagal diimport:</strong>
        </div>
        <ul class="mb-0 ps-3 small">
            @foreach(session('import_errors') as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5 px-3" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5 px-3" role="alert">
        <div class="fw-bold mb-1"><i class='bx bx-error me-1'></i> Terjadi kesalahan input:</div>
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(Auth::check() && Auth::user()->role === 'admin')
    {{-- Form Hapus Semua Kelas (Hidden, dipanggil via confirmDeleteAllClasses()) --}}
    <form id="deleteAllClassesForm" action="{{ route('admin.classes.destroy-all') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
    @endif

    <!-- ========================================================================= -->
    <!-- KARTU UTAMA MASTER DATA KELAS: CLEAN ACTION BAR & TABEL TERPADU           -->
    <!-- (Layout & container disamakan persis dengan Data Guru & Data Siswa)      -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">

        <!-- Action Bar & Filter (Search+Dropdown Stretch Memanjang, Tombol Radius Konsisten) -->
        <div class="p-3.5 p-md-4 border-bottom border-gray-100 bg-white">
            <div class="action-bar-section">

                <!-- Sisi Kiri: Form Pencarian & Dropdown Tingkat Kelas (Flex Column on Mobile, Row on Desktop) -->
                <form method="GET" action="{{ url()->current() }}" class="d-flex flex-column flex-md-row gap-2 w-100 m-0">
                    <!-- Search Bar -->
                    <div class="input-group w-100 search-box-wrap" style="max-width: 350px;">
                        <input type="text" 
                               name="search" 
                               class="form-control border-secondary-subtle border-end-0 shadow-none ps-3 w-100" 
                               placeholder="Cari nama kelas..." 
                               value="{{ request('search') }}"
                               aria-label="Cari nama kelas"
                               style="height: 38px; font-size: 0.85rem;">
                        <button class="btn bg-white border border-secondary-subtle border-start-0 shadow-none text-secondary px-3" type="submit" style="height: 38px;">
                            <i class='bx bx-search fs-6'></i>
                        </button>
                    </div>

                    <!-- Dropdown Filter Tingkat Kelas -->
                    <div class="w-100 filter-box-wrap" style="max-width: 200px;">
                        <select name="grade" onchange="this.form.submit()" class="form-select border-secondary-subtle shadow-none fw-normal w-100" style="height: 38px; font-size: 0.85rem;">
                            <option value="">Semua Tingkat</option>
                            <option value="7" {{ request('grade') == '7' ? 'selected' : '' }}>Kelas 7</option>
                            <option value="8" {{ request('grade') == '8' ? 'selected' : '' }}>Kelas 8</option>
                            <option value="9" {{ request('grade') == '9' ? 'selected' : '' }}>Kelas 9</option>
                        </select>
                    </div>

                    @if(request('search') || request('grade'))
                    <div class="w-100 w-md-auto">
                        <a href="{{ url()->current() }}" class="btn btn-light d-inline-flex align-items-center justify-content-center px-2.5 flex-shrink-0 w-100 w-md-auto" style="height: 38px; border-radius: 6px; font-size: 0.85rem;" title="Reset Filter">
                            <i class='bx bx-refresh fs-5 me-1'></i> Reset
                        </a>
                    </div>
                    @endif
                </form>

                @if(Auth::check() && Auth::user()->role === 'admin')
                <!-- Sisi Kanan: Deretan Tombol Aksi (Stacked Layout di Mobile) -->
                <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
                    @if(Route::has('admin.classes.import'))
                    <!-- 1. Import Excel (Solid Hijau) -->
                    <button type="button" class="btn-solid-pill btn-solid-green w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#importClassModal">
                        <i class='bx bx-file'></i>
                        <span>Import Excel</span>
                    </button>
                    @endif

                    <!-- 2. Tambah Kelas (Solid Biru, Aksi Utama) -->
                    <button type="button" class="btn-solid-pill btn-solid-blue w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <i class='bx bx-plus'></i>
                        <span>Tambah Kelas</span>
                    </button>

                    <!-- 3. Hapus Semua Kelas (Solid Merah - Urutan Terakhir) -->
                    <button type="button" class="btn-solid-pill btn-solid-red w-100 w-md-auto" title="Hapus Semua Kelas" onclick="confirmDeleteAllClasses()">
                        <i class='bx bx-trash'></i>
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
                        <td class="text-center text-nowrap px-3">{{ $classes->firstItem() + $index }}</td>
                        <td class="text-center text-nowrap px-3">
                            {{ $class->grade ?? ($class->level == 'VII' ? '7' : ($class->level == 'VIII' ? '8' : '9')) }}
                        </td>
                        <td class="text-center text-nowrap fw-semibold text-dark px-3">
                            {{ $class->name }}
                        </td>
                        <td class="text-start text-nowrap indent-nama px-3 pe-4 text-secondary">
                            {{ $class->teacher->name ?? 'Belum Ditentukan' }}
                        </td>
                        <td class="text-center text-nowrap px-3">
                            {{ $class->students_count }} Siswa
                        </td>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <td class="text-center text-nowrap px-3">
                            <div class="crud-center-wrapper">
                                <!-- Tombol Edit Modal -->
                                <button type="button" class="btn-row-action action-edit" data-bs-toggle="modal" data-bs-target="#editClassModal{{ $class->id }}" title="Edit">
                                    <i class='bx bx-edit-alt'></i> Edit
                                </button>

                                <!-- Tombol Hapus Satuan -->
                                <button type="button" class="btn-row-action action-delete" onclick="confirmDeleteClass('{{ $class->id }}', '{{ addslashes($class->name) }}')" title="Hapus">
                                    <i class='bx bx-trash'></i> Hapus
                                </button>
                                <form id="deleteClassForm-{{ $class->id }}" action="{{ route('admin.classes.destroy', $class->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr class="align-middle">
                        <td colspan="{{ Auth::check() && Auth::user()->role === 'admin' ? 6 : 5 }}" class="text-center py-5 text-muted text-nowrap">
                            <i class='bx bx-info-circle fs-2 d-block mb-2'></i>
                            Belum ada data kelas yang sesuai dengan filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($classes->hasPages())
        <div class="px-4 py-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 bg-white small">
            <div class="text-muted small d-none d-md-block">
                Menampilkan <span>{{ $classes->firstItem() ?? 0 }}</span> - <span>{{ $classes->lastItem() ?? 0 }}</span> dari <span>{{ $classes->total() }}</span> kelas
            </div>
            <div class="pagination-compact w-100 w-md-auto d-flex justify-content-center justify-content-md-end">
                {{ $classes->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
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
            <form action="{{ route('admin.classes.update', $class->id) }}" method="POST">
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
            <form action="{{ route('admin.classes.store') }}" method="POST">
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
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0">
                <h5 class="fw-bold mb-0">Import Data Kelas Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.classes.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="p-3 bg-light rounded-3 small text-secondary mb-3">
                        <div class="mb-2">
                            Format kolom file Excel: <strong>Nama Kelas, Tingkat (7/8/9)</strong> (.xlsx atau .csv)
                        </div>
                        @if(Route::has('admin.classes.template'))
                        <a href="{{ route('admin.classes.template') }}" class="btn btn-sm btn-outline-success rounded-3 fw-semibold w-100">
                            <i class='bx bx-download me-1'></i> Unduh Template Excel (.xlsx) Kosong
                        </a>
                        @endif
                    </div>
                    <label class="form-label small fw-semibold">Pilih File Excel</label>
                    <input type="file" name="file_excel" class="form-control rounded-3" accept=".xlsx,.xls,.csv" required>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success text-white rounded-3 px-4 fw-semibold">Unggah &amp; Import</button>
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
            html: `Tindakan ini bersifat permanen. Anda akan menghapus rombel kelas <b class="text-dark">${name}</b> dari sistem. Pastikan tidak ada data siswa aktif di dalam rombel ini.`,
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
            onConfirm: function() {
                document.getElementById(`deleteClassForm-${id}`).submit();
            }
        });
    }

    function confirmDeleteAllClasses() {
        confirmUniversalDelete({
            title: 'Hapus Seluruh Data Kelas?',
            html: 'Tindakan ini bersifat permanen. Anda akan menghapus <b class="text-dark">seluruh data kelas</b> secara massal dari sistem.',
            confirmText: 'Ya, Hapus Semua',
            cancelText: 'Batal',
            onConfirm: function() {
                document.getElementById('deleteAllClassesForm').submit();
            }
        });
    }
</script>
@endpush
@endif