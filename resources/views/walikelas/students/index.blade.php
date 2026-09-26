@extends('layouts.app')

@section('title', 'Data Siswa')
@section('page_title', 'Data Siswa')
@section('page_subtitle', 'Kelola informasi siswa, cetak kartu NISN, dan data rombongan belajar kelas binaan.')

@section('page_header_right')
<!-- Tombol Aksi Header Versi Desktop (>= 768px): Sejajar Horizontal Asli -->
<div class="d-none d-md-block">
    <div class="d-flex align-items-center gap-2">
        <!-- Tombol Cetak Kartu Massal (Tersedia untuk Wali Kelas & Admin) -->
        <button type="button" class="btn btn-primary btn-sm rounded-3 shadow-xs d-inline-flex align-items-center gap-1.5 py-1.5 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#printCardsModal">
            <i class='bx bx-printer fs-6'></i>
            <span>Cetak Kartu</span>
        </button>

        @if(Auth::check() && Auth::user()->role === 'admin')
        <!-- Tombol Import Excel -->
        <button type="button" class="btn btn-success btn-sm rounded-3 shadow-xs d-inline-flex align-items-center gap-1.5 py-1.5 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class='bx bx-file fs-6'></i>
            <span>Import Excel</span>
        </button>

        <!-- Tombol Hapus Semua Siswa -->
        <button type="button" class="btn btn-danger btn-sm rounded-3 shadow-xs d-inline-flex align-items-center gap-1.5 py-1.5 px-3 fw-semibold" title="Hapus Semua Siswa" onclick="confirmDeleteAllStudents()">
            <i class='bx bx-trash fs-6'></i>
            <span>Hapus</span>
        </button>
        <form id="deleteAllStudentsForm" action="{{ route('admin.students.destroy-all') }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>

        <!-- Tombol Tambah Siswa -->
        <button type="button" class="btn btn-primary btn-sm rounded-3 shadow-xs d-inline-flex align-items-center gap-1.5 py-1.5 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            <i class='bx bx-plus fs-6'></i>
            <span>Tambah Siswa</span>
        </button>
        @endif
    </div>
</div>
@endsection

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
        .table-zebra-custom thead th {
        color: #3b82f6 !important;
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

    /* ===== Action Bar Layout: Search+Filter Stretch Memanjang, Sejajar 1 Baris ===== */
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
        border-radius: 6px !important;
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
</style>
@endpush

@section('content')

    {{-- Alert Notifikasi --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5 px-3" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
            <span>{{ session('success') }}</span>
        </div>
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

    <!-- ========================================================================= -->
    <!-- KARTU UTAMA DATA SISWA: CLEAN ACTION BAR & TABEL TERPADU (SIAKAD STYLE)   -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
        
        <!-- Action Bar & Filter (Search+Dropdown Stretch Memanjang, Tombol Radius Konsisten) -->
        <div class="p-3.5 p-md-4 border-bottom border-gray-100 bg-white">
            <div class="action-bar-section">
                
                <!-- Sisi Kiri: Form Pencarian & Dropdown Kelas Binaan (Flex Column on Mobile, Row on Desktop) -->
                <form method="GET" action="{{ route('guru.students') }}" class="d-flex flex-column flex-lg-row gap-3 w-100 m-0">
                    <!-- Search Bar -->
                    <div class="input-group w-100 search-box-wrap" style="max-width: 350px;">
                        <input type="text" 
                               name="search" 
                               class="form-control border-secondary-subtle border-end-0 shadow-none ps-3 w-100" 
                               placeholder="Cari nama atau NIS..." 
                               value="{{ request('search') }}"
                               aria-label="Cari nama atau NIS"
                               style="height: 38px; font-size: 0.85rem;">
                        <button class="btn bg-white border border-secondary-subtle border-start-0 shadow-none text-secondary px-3" type="submit" style="height: 38px;">
                            <i class='bx bx-search fs-6'></i>
                        </button>
                    </div>

                    <!-- Dropdown Filter Kelas Binaan -->
                    <div class="w-100 filter-box-wrap" style="max-width: 200px;">
                        <select name="school_class_id" onchange="this.form.submit()" class="form-select border-secondary-subtle shadow-none fw-normal w-100" style="height: 38px; font-size: 0.85rem;">
                            <option value="">Semua Kelas Binaan</option>
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ (request('school_class_id') == $c->id || (isset($schoolClass) && $schoolClass->id == $c->id && !request()->filled('school_class_id'))) ? 'selected' : '' }}>
                                Kelas {{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    @if(request('search') || request('school_class_id'))
                    <div class="w-100 w-lg-auto">
                        <a href="{{ route('guru.students') }}" class="btn btn-light d-inline-flex align-items-center justify-content-center px-2.5 flex-shrink-0 w-100 w-lg-auto" style="height: 38px; border-radius: 6px; font-size: 0.85rem;" title="Reset Filter">
                            <i class='bx bx-refresh fs-5 me-1'></i> Reset
                        </a>
                    </div>
                    @endif
                </form>

                <!-- Sisi Kanan: Deretan Tombol Aksi (Stacked Layout di Mobile) -->
                <div class="d-flex flex-column flex-lg-row gap-2 w-100 w-lg-auto ms-lg-auto">
                    <!-- 1. Cetak Kartu Massal (Solid Biru) -->
                    <button type="button" class="btn-solid-pill btn-solid-blue w-100 w-lg-auto" data-bs-toggle="modal" data-bs-target="#printCardsModal">
                        <i class='bx bx-printer'></i>
                        <span>Cetak Kartu</span>
                    </button>

                    @if(Auth::check() && Auth::user()->role === 'admin')
                    <!-- 2. Import Excel (Solid Hijau) -->
                    <button type="button" class="btn-solid-pill btn-solid-green w-100 w-lg-auto" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class='bx bx-file'></i>
                        <span>Import Excel</span>
                    </button>

                    <!-- 3. Tambah Siswa (Solid Biru, Aksi Utama) -->
                    <button type="button" class="btn-solid-pill btn-solid-blue w-100 w-lg-auto" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class='bx bx-plus'></i>
                        <span>Tambah Siswa</span>
                    </button>

                    <!-- 4. Hapus Semua Siswa (Solid Merah - Urutan Terakhir) -->
                    <button type="button" class="btn-solid-pill btn-solid-red w-100 w-lg-auto" title="Hapus Semua Siswa" onclick="confirmDeleteAllStudents()">
                        <i class='bx bx-trash'></i>
                        <span>Hapus</span>
                    </button>
                    @endif
                </div>

            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <thead class="bg-light">
                    <tr class="align-middle text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.03em;">
                        <th class="text-center py-3 text-nowrap px-3" style="width: 1%; min-width: 45px;">NO</th>
                        <th class="text-center py-3 text-nowrap px-3" style="width: 1%; min-width: 80px;">KELAS</th>
                        <th class="text-center py-3 text-nowrap px-3" style="width: 12%;">NIS</th>
                        <th class="text-start indent-nama py-3 text-nowrap px-3 pe-4">NAMA SISWA</th>
                        <th class="text-start py-3 text-nowrap px-3">JENIS KELAMIN</th>
                        <th class="text-start py-3 text-nowrap px-3">NAMA WALI</th>
                        <th class="text-center py-3 text-nowrap px-3" style="width: 180px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                    @php
                        $rawJk = strtolower(trim($student->jenis_kelamin ?? $student->gender ?? ''));
                        $isMale = in_array($rawJk, ['laki-laki', 'laki - laki', 'l', 'pria', 'male']);
                    @endphp
                    <tr class="align-middle {{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td class="text-center text-nowrap px-3">{{ $loop->iteration + ($students->firstItem() ? $students->firstItem() - 1 : 0) }}</td>
                        <td class="text-center text-nowrap px-3">{{ $student->schoolClass->name ?? $student->kelas ?? '-' }}</td>
                        <td class="text-center text-nowrap font-monospace px-3">{{ $student->nis }}</td>
                        <td class="text-start text-nowrap indent-nama fw-semibold text-dark px-3 pe-4">{{ $student->nama ?? $student->name }}</td>
                        <td class="text-start text-nowrap px-3">{{ $isMale ? 'Laki-laki' : 'Perempuan' }}</td>
                        <td class="text-start text-nowrap px-3 text-secondary">{{ $student->nama_orang_tua ?? $student->nama_wali ?? '-' }}</td>
                        <td class="text-center text-nowrap px-3">
                            <div class="crud-center-wrapper">
                                <a href="{{ route('guru.students.show', $student->id) }}" class="btn btn-sm btn-success" title="Detail">
                                    <i class='bx bx-show'></i> Detail
                                </a>
                                @if(Auth::check() && Auth::user()->role === 'admin')
                                <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-sm btn-warning text-white" title="Edit">
                                    <i class='bx bx-edit'></i> Edit
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" title="Hapus" onclick="confirmDeleteStudent('{{ $student->id }}', '{{ addslashes($student->nama ?? $student->name) }}')">
                                    <i class='bx bx-trash'></i> Hapus
                                </button>
                                <form id="deleteStudentForm-{{ $student->id }}" action="{{ route('admin.students.destroy', $student->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="align-middle">
                        <td colspan="7" class="text-center py-5 text-muted text-nowrap">
                            <i class='bx bx-info-circle fs-2 d-block mb-2'></i>
                            Belum ada data siswa yang tercatat di kelas binaan Anda.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($students, 'hasPages') && $students->hasPages())
        <div class="px-4 py-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 bg-white small">
            <div class="text-muted small d-none d-md-block">
                Menampilkan <span>{{ $students->firstItem() ?? 0 }}</span> - <span>{{ $students->lastItem() ?? 0 }}</span> dari <span>{{ $students->total() }}</span> siswa
            </div>
            <div class="pagination-compact w-100 w-md-auto d-flex justify-content-center justify-content-md-end">
                {{ $students->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>

<!-- MODAL CETAK KARTU MASSAL -->
<div class="modal fade" id="printCardsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Cetak Kartu Presensi Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('guru.students.print-cards') }}" method="POST">
                @csrf
                <div class="modal-body py-3">
                    <p class="text-secondary small mb-3">
                        Pilih opsi pencetakan kartu presensi siswa kelas binaan Anda. Berkas PDF kartu akan siap dicetak pada kertas A4 (8 kartu per lembar).
                    </p>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilihan Cetak <span class="text-danger">*</span></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="print_type" id="printTypeClass" value="class" checked onchange="togglePrintScope()">
                            <label class="form-check-label fw-medium small" for="printTypeClass">
                                Cetak per Kelas Binaan
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="print_type" id="printTypeAll" value="all" onchange="togglePrintScope()">
                            <label class="form-check-label fw-medium small" for="printTypeAll">
                                Cetak Semua Siswa Kelas Binaan
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="classSelectContainer">
                        <label class="form-label small fw-semibold">Pilih Kelas</label>
                        <select name="class_id" id="modalClassId" class="form-select rounded-3">
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ (isset($schoolClass) && $schoolClass->id == $c->id) ? 'selected' : '' }}>Kelas {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-0" id="printInfoBox">
                        <i class='bx bx-info-circle me-1'></i> Kartu akan digenerate dengan QR Code presensi dan pas foto siswa secara otomatis.
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">
                        <i class='bx bx-download me-1'></i> Generate &amp; Unduh PDF Kartu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(Auth::check() && Auth::user()->role === 'admin')
<!-- MODAL TAMBAH SISWA -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Tambah Siswa Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.students.store') }}" method="POST">
                @csrf
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Siswa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="Masukkan nama lengkap siswa" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">NIS <span class="text-danger">*</span></label>
                            <input type="text" name="nis" class="form-control rounded-3" placeholder="Nomor Induk Siswa" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">NISN <span class="text-muted">(Opsional)</span></label>
                            <input type="text" name="nisn" class="form-control rounded-3" placeholder="Nomor Induk Siswa Nasional">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Kelas <span class="text-danger">*</span></label>
                            <select name="school_class_id" class="form-select rounded-3" required>
                                <option value="">Pilih Kelas</option>
                                @foreach($classes as $c)
                                <option value="{{ $c->id }}">Kelas {{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select rounded-3" required>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tempat Lahir</label>
                            <input type="text" name="birth_place" class="form-control rounded-3" placeholder="Contoh: Bandung">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal Lahir</label>
                            <input type="date" name="birth_date" class="form-control rounded-3">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Nama Orang Tua / Wali</label>
                            <input type="text" name="parent_name" class="form-control rounded-3" placeholder="Nama Orang Tua">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">No. WhatsApp / HP</label>
                            <input type="text" name="parent_phone" class="form-control rounded-3" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Alamat Lengkap</label>
                        <textarea name="address" class="form-control rounded-3" rows="2" placeholder="Alamat tempat tinggal siswa"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Simpan Siswa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL IMPORT EXCEL -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0">
                <h5 class="fw-bold mb-0">Import Data Siswa Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="p-3 bg-light rounded-3 small text-secondary mb-3">
                        <div class="mb-2">
                            Format kolom file Excel: <strong>NIS, NISN, Nama Lengkap, Kelas, Jenis Kelamin, Tempat Lahir, Tanggal Lahir, Alamat, Nama Wali, No WhatsApp</strong> (.xlsx atau .csv)
                        </div>
                        <a href="{{ route('admin.students.template') }}" class="btn btn-sm btn-outline-success rounded-3 fw-semibold w-100">
                            <i class='bx bx-download me-1'></i> Unduh Template Excel (.xlsx) Kosong
                        </a>
                    </div>
                    <label class="form-label small fw-semibold">Pilih File Excel</label>
                    <input type="file" name="file_excel" class="form-control rounded-3" accept=".xlsx,.xls,.csv" required>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-3 px-4 fw-semibold">Unggah &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    function togglePrintScope() {
        const isClass = document.getElementById('printTypeClass').checked;
        const classContainer = document.getElementById('classSelectContainer');
        const classSelect = document.getElementById('modalClassId');
        
        if (isClass) {
            classContainer.style.display = 'block';
            classSelect.disabled = false;
        } else {
            classContainer.style.display = 'none';
            classSelect.disabled = true;
        }
    }

    @if(Auth::check() && Auth::user()->role === 'admin')
    function confirmDeleteStudent(id, name) {
        confirmUniversalDelete({
            title: 'Hapus Data Siswa?',
            html: `Tindakan ini bersifat permanen. Anda akan menghapus data siswa <b class="text-dark">${name}</b> dari sistem dan data tidak dapat dipulihkan.`,
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
            onConfirm: function() {
                document.getElementById(`deleteStudentForm-${id}`).submit();
            }
        });
    }

    function confirmDeleteAllStudents() {
        confirmUniversalDelete({
            title: 'Hapus Seluruh Data Siswa?',
            html: 'Tindakan ini bersifat permanen. Anda akan menghapus <b class="text-dark">seluruh data siswa</b> beserta riwayat presensinya dari sistem.',
            confirmText: 'Ya, Hapus Semua',
            cancelText: 'Batal',
            onConfirm: function() {
                document.getElementById('deleteAllStudentsForm').submit();
            }
        });
    }
    @endif
</script>
@endpush