@extends('layouts.app')

@section('title', 'Master Data Kelas')
@section('page_title', 'Master Data Kelas')
@section('page_subtitle', 'Kelola rombongan belajar dan penetapan wali kelas.')

@section('page_header_right')
<!-- Tombol Aksi Header Versi Desktop (>= 768px): Sejajar Horizontal Asli -->
<div class="d-none d-md-block">
    <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
        @if(Auth::check() && Auth::user()->role === 'admin')
        @if(Route::has('admin.classes.import'))
        <!-- Tombol Import Excel -->
        <button type="button" class="btn btn-success btn-sm rounded-3 shadow-xs d-inline-flex align-items-center gap-1.5 py-1.5 px-3 fw-semibold w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#importClassModal">
            <i class='bx bx-file fs-6'></i>
            <span>Import Excel</span>
        </button>
        @endif

        <!-- Tombol Tambah Kelas -->
        <button type="button" class="btn btn-primary btn-sm rounded-3 shadow-xs d-inline-flex align-items-center gap-1.5 py-1.5 px-3 fw-semibold w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#addClassModal">
            <i class='bx bx-plus fs-6'></i>
            <span>Tambah Kelas</span>
        </button>

        <!-- Tombol Hapus Semua Kelas -->
        <button type="button" class="btn btn-danger btn-sm rounded-3 shadow-xs d-inline-flex align-items-center gap-1.5 py-1.5 px-3 fw-semibold w-100 w-md-auto" title="Hapus Semua Kelas" onclick="confirmDeleteAllClasses()">
            <i class='bx bx-trash fs-6'></i>
            <span>Hapus</span>
        </button>
        @endif
    </div>
</div>
<!-- Form Hapus Semua Kelas (Tetap ada di DOM agar dapat dipicu Desktop maupun Mobile) -->
<form id="deleteAllClassesForm" action="{{ route('admin.classes.destroy-all') }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('styles')
<style>
    /* Tombol & Card */
    .btn-green-excel {
        background-color: #059669;
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 0.88rem;
        border-radius: 8px;
        padding: 0.55rem 1.15rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 1px 2px rgba(5, 150, 105, 0.15);
        transition: all 0.15s ease;
    }
    .btn-green-excel:hover { 
        background-color: #047857; 
        color: #ffffff; 
        transform: translateY(-1px);
    }

    .btn-add-class {
        background-color: #3b82f6;
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 0.88rem;
        border-radius: 8px;
        padding: 0.55rem 1.15rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 1px 2px rgba(59, 130, 246, 0.15);
        transition: all 0.15s ease;
    }
    .btn-add-class:hover { 
        background-color: #2563eb; 
        color: #ffffff; 
        transform: translateY(-1px);
    }

    .search-container {
        background: #ffffff;
        border-radius: 10px;
        padding: 0.45rem 0.65rem 0.45rem 1rem;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .search-container input {
        border: none;
        outline: none;
        width: 100%;
        font-size: 0.9rem;
        color: #334155;
    }

    .btn-search-blue {
        background-color: #3b82f6;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0.45rem 1.25rem;
    }
    .btn-search-blue:hover { background-color: #2563eb; color: #ffffff; }

    .btn-reset-gray {
        background-color: #ffffff;
        color: #64748b;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0.45rem 1.25rem;
        text-decoration: none;
    }
    .btn-reset-gray:hover { background-color: #f1f5f9; color: #334155; }

    /* Filter Pills */
    .filter-pill {
        padding: 0.45rem 1rem;
        border-radius: 20px;
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        transition: all 0.15s ease;
    }
    .filter-pill:hover, .filter-pill.active {
        background: #3b82f6;
        color: #ffffff;
        border-color: #3b82f6;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
    }

    /* Tabel Kelas */
    .table-zebra-custom {
        width: 100%;
        min-width: 780px;
        margin-bottom: 0;
    }

    .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
    }

    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f1f5f9 !important;
    }
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td {
        background-color: #e2e8f0 !important;
    }

    .table-zebra-custom th,
    .table-zebra-custom td {
        vertical-align: middle;
    }

    .table-zebra-custom tbody td {
        color: #000000 !important;
        font-weight: 400 !important;
    }

    .crud-center-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
    }

    /* Pagination Ringkas */
    .pagination-compact .pagination {
        margin-bottom: 0;
        font-size: 0.82rem;
    }
    .pagination-compact .page-link {
        padding: 0.25rem 0.6rem;
    }


</style>
@endpush

@section('content')
    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('import_errors'))
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2" role="alert">
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
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2" role="alert">
        <div class="fw-bold mb-1"><i class='bx bx-error me-1'></i> Terjadi kesalahan input:</div>
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- ========================================================================= -->
    <!-- 1. KONTEN KHUSUS MOBILE (< 768px): KARTU TERPADU TOMBOL, CARI & FILTER     -->
    <!-- ========================================================================= -->
    <div class="d-block d-md-none mb-3">
        <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white">
            
            @if(Auth::check() && Auth::user()->role === 'admin')
            <!-- Bagian Atas: Tombol Aksi Mobile (Baris 1: Import & Hapus, Baris 2: Tambah Kelas Full-Width) -->
            <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
                @if(Route::has('admin.classes.import'))
                <!-- 1. Import Excel -->
                <button type="button" class="btn btn-success btn-sm w-100 w-md-auto rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#importClassModal">
                    <i class='bx bx-file fs-6'></i>
                    <span class="text-nowrap" style="font-size: 0.8rem;">Import Excel</span>
                </button>
                @endif

                <!-- 2. Tambah Kelas -->
                <button type="button" class="btn btn-primary btn-sm w-100 w-md-auto rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addClassModal">
                    <i class='bx bx-plus fs-6'></i>
                    <span class="text-nowrap" style="font-size: 0.8rem;">Tambah Kelas</span>
                </button>

                <!-- 3. Hapus Semua Kelas -->
                <button type="button" class="btn btn-danger btn-sm w-100 w-md-auto rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5 px-2 fw-semibold" title="Hapus Semua Kelas" onclick="confirmDeleteAllClasses()">
                    <i class='bx bx-trash fs-6'></i>
                    <span class="text-nowrap" style="font-size: 0.8rem;">Hapus</span>
                </button>
            </div>

            <!-- Sekat Pemisah -->
            <hr class="border-secondary-subtle my-3">
            @endif

            <!-- Bagian Bawah: Filter Tingkat Kelas Mobile -->
            <form method="GET" action="{{ url()->current() }}" class="d-flex flex-column flex-md-row gap-2 w-100 m-0">
                <!-- Dropdown Filter Tingkat Kelas ("Semua Tingkat") -->
                <div class="w-100">
                    <select name="grade" onchange="this.form.submit()" class="form-select border-secondary-subtle shadow-sm rounded-3 fw-normal w-100">
                        <option value="">Semua Tingkat</option>
                        <option value="7" {{ request('grade') == '7' ? 'selected' : '' }}>Kelas 7</option>
                        <option value="8" {{ request('grade') == '8' ? 'selected' : '' }}>Kelas 8</option>
                        <option value="9" {{ request('grade') == '9' ? 'selected' : '' }}>Kelas 9</option>
                    </select>
                </div>
            </form>

        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. KONTEN KHUSUS DESKTOP (>= 768px): FILTER KELAS STANDAR                  -->
    <!-- ========================================================================= -->
    <div class="d-none d-md-block mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ url()->current() }}" class="filter-pill {{ !request('grade') ? 'active' : '' }}">
                Semua Kelas
            </a>
            <a href="{{ url()->current() . '?' . http_build_query(['grade' => '7']) }}" class="filter-pill {{ request('grade') == '7' ? 'active' : '' }}">
                Kelas 7
            </a>
            <a href="{{ url()->current() . '?' . http_build_query(['grade' => '8']) }}" class="filter-pill {{ request('grade') == '8' ? 'active' : '' }}">
                Kelas 8
            </a>
            <a href="{{ url()->current() . '?' . http_build_query(['grade' => '9']) }}" class="filter-pill {{ request('grade') == '9' ? 'active' : '' }}">
                Kelas 9
            </a>
        </div>
    </div>

    <!-- Tabel Kelas -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <thead class="bg-light">
                    <tr class="text-secondary small fw-bold text-uppercase align-middle text-nowrap" style="letter-spacing: 0.03em;">
                        <th class="text-center py-3 text-nowrap px-3" style="width: 5%;">NO</th>
                        <th class="text-center py-3 text-nowrap px-3" style="width: 8%;">KELAS</th>
                        <th class="text-center py-3 text-nowrap px-3">NAMA KELAS</th>
                        <th class="text-start py-3 text-nowrap px-3 pe-4">STATUS WALI KELAS</th>
                        <th class="text-center py-3 text-nowrap px-3 ps-4">JUMLAH SISWA</th>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <th class="text-center py-3 text-nowrap px-3" style="width: 140px;">AKSI</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $index => $class)
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }} align-middle text-nowrap">
                        <td class="text-center text-nowrap px-3">{{ $classes->firstItem() + $index }}</td>
                        <td class="text-center text-nowrap px-3">
                            {{ $class->grade ?? ($class->level == 'VII' ? '7' : ($class->level == 'VIII' ? '8' : '9')) }}
                        </td>
                        <td class="text-center text-nowrap px-3">
                            {{ $class->name }}
                        </td>
                        <td class="text-start text-nowrap px-3 pe-4">
                            {{ $class->teacher->name ?? 'Belum Ditentukan' }}
                        </td>
                        <td class="text-center text-nowrap px-3 ps-4">
                            {{ $class->students_count }} Siswa
                        </td>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <td class="text-center text-nowrap px-3">
                            <div class="crud-center-wrapper">
                                <!-- Tombol Edit Modal -->
                                <button type="button" class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editClassModal{{ $class->id }}" title="Edit">
                                    <i class='bx bx-edit-alt'></i> Edit
                                </button>

                                <!-- Tombol Hapus Satuan -->
                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteClass('{{ $class->id }}', '{{ addslashes($class->name) }}')" title="Hapus">
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
                    <tr>
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
        <div class="px-4 py-2 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 bg-white small">
            <div class="text-muted small">
                Menampilkan <span>{{ $classes->firstItem() ?? 0 }}</span> - <span>{{ $classes->lastItem() ?? 0 }}</span> dari <span>{{ $classes->total() }}</span> kelas
            </div>
            <div class="pagination-compact">
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
@endif

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
                    <button type="submit" class="btn btn-green-excel rounded-3 px-4">Unggah & Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

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