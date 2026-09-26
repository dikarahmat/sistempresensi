@extends('layouts.app')

@section('title', 'Data Guru & Wali Kelas')
@section('page_title', 'Data Guru & Wali Kelas')
@section('page_subtitle', 'Kelola master guru pendidik, penugasan wali kelas, dan informasi kontak.')



@push('styles')
<style>
    /* Tombol Aksi Header */
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

    .btn-add-teacher {
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
    .btn-add-teacher:hover { 
        background-color: #2563eb; 
        color: #ffffff; 
        transform: translateY(-1px);
    }

    /* Tabel Data Guru Zebra Striping (Sesuai Gaya Data Siswa & Kelas) */
    .table-zebra-custom {
        width: 100%;
        min-width: 820px;
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

    .nowrap-col {
        white-space: nowrap !important;
    }

    /* Pembungkus Kolom Aksi Terstandarisasi Solid */
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
        padding: 0.25rem 0.6rem;
    }

    /* ===== Action Bar Layout: Search Stretch Memanjang, Sejajar 1 Baris ===== */
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

    /* Responsive Search */
    @media (max-width: 991.98px) {
        .search-box-wrap {
            max-width: 100% !important;
            width: 100% !important;
        }
    }
    @media (min-width: 992px) {
        .search-box-wrap {
            max-width: 350px !important;
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

    @if(Auth::check() && Auth::user()->role === 'admin')
    {{-- Form Hapus Semua Guru (Hidden, dipanggil via confirmDeleteAllTeachers()) --}}
    <form id="deleteAllTeachersForm" action="{{ route('admin.guru.destroy-all') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
    @endif

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

    @if(session('import_errors') && count(session('import_errors')) > 0)
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5 px-3" role="alert">
        <div class="d-flex align-items-center mb-1">
            <i class='bx bx-error-circle fs-5 me-2 text-warning'></i>
            <strong>Catatan Import Excel:</strong>
        </div>
        <ul class="mb-0 ps-3 small">
            @foreach(session('import_errors') as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5 px-3" role="alert">
        <div class="fw-bold mb-1"><i class='bx bx-error me-1'></i> Periksa data input:</div>
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- ========================================================================= -->
    <!-- KARTU UTAMA DATA GURU & WALI KELAS: CLEAN ACTION BAR & TABEL TERPADU      -->
    <!-- (Layout & container disamakan persis dengan Data Siswa)                  -->
    <!-- ========================================================================= -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">

        <!-- Action Bar & Search (Stretch Memanjang, Tombol Radius Konsisten) -->
        <div class="p-3.5 p-md-4 border-bottom border-gray-100 bg-white">
            <div class="action-bar-section">

                <!-- Sisi Kiri: Form Pencarian (Flex Column on Mobile, Row on Desktop) -->
                <form method="GET" action="{{ url()->current() }}" class="d-flex flex-column flex-md-row gap-2 w-100 m-0">
                    <div class="input-group w-100 search-box-wrap" style="max-width: 350px;">
                        <input type="text"
                               name="search"
                               class="form-control border-secondary-subtle border-end-0 shadow-none ps-3 w-100"
                               placeholder="Cari berdasarkan nama lengkap atau NIP..."
                               value="{{ request('search') }}"
                               aria-label="Cari berdasarkan nama lengkap atau NIP"
                               style="height: 38px; font-size: 0.85rem;">
                        <button class="btn bg-white border border-secondary-subtle border-start-0 shadow-none text-secondary px-3" type="submit" style="height: 38px;">
                            <i class='bx bx-search fs-6'></i>
                        </button>
                    </div>

                    @if(request('search'))
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
                    <!-- 1. Import Excel (Solid Hijau) -->
                    <button type="button" class="btn-solid-pill btn-solid-green w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#importTeacherModal">
                        <i class='bx bx-file'></i>
                        <span>Import Excel</span>
                    </button>

                    <!-- 2. Tambah Guru (Solid Biru, Aksi Utama) -->
                    <button type="button" class="btn-solid-pill btn-solid-blue w-100 w-md-auto" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                        <i class='bx bx-plus'></i>
                        <span>Tambah Guru</span>
                    </button>

                    <!-- 3. Hapus Semua Guru (Solid Merah - Urutan Terakhir) -->
                    <button type="button" class="btn-solid-pill btn-solid-red w-100 w-md-auto" title="Hapus Semua Data Guru" onclick="confirmDeleteAllTeachers()">
                        <i class='bx bx-trash'></i>
                        <span>Hapus</span>
                    </button>
                </div>
                @endif

            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <thead class="bg-light">
                    <tr class="text-secondary small fw-bold text-uppercase align-middle text-nowrap" style="letter-spacing: 0.03em;">
                        <th class="text-center py-3 text-nowrap px-3" style="width: 5%;">NO</th>
                        <th class="text-center py-3 text-nowrap px-3 pe-4">NIP</th>
                        <th class="text-start py-3 text-nowrap px-3 pe-4">NAMA LENGKAP GURU</th>
                        <th class="text-center py-3 text-nowrap px-3">JENIS KELAMIN</th>
                        <th class="text-center py-3 text-nowrap px-3">TUGAS / WALI KELAS</th>
                        <th class="text-center py-3 text-nowrap px-3">NO. TELEPON / WA</th>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <th class="text-center py-3 text-nowrap px-3" style="width: 160px;">AKSI</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $index => $teacher)
                    @php
                        $assignedClass = $teacher->schoolClass ?? null;
                    @endphp
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }} align-middle text-nowrap">
                        <td class="text-center text-nowrap px-3">{{ $teachers->firstItem() + $index }}</td>
                        <td class="text-center font-monospace text-nowrap px-3 pe-4">{{ $teacher->nip ?? '-' }}</td>
                        <td class="text-start text-nowrap px-3 pe-4 text-dark">
                            {{ $teacher->name }}
                        </td>
                        <td class="text-center text-nowrap px-3">
                            {{ ($teacher->gender == 'Perempuan' || $teacher->gender == 'P') ? 'Perempuan' : 'Laki-laki' }}
                        </td>
                        <td class="text-center text-nowrap px-3">
                            @if($assignedClass)
                                Wali Kelas {{ $assignedClass->name }}
                            @else
                                Guru Pengajar
                            @endif
                        </td>
                        <td class="text-center text-nowrap px-3">
                            {{ $teacher->phone_number ?? $teacher->phone ?? '-' }}
                        </td>
                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <td class="text-center text-nowrap px-3">
                            <!-- Kolom Aksi Terstandarisasi Solid -->
                            <div class="crud-center-wrapper">
                                <!-- 1. Tombol Edit Guru -->
                                <button type="button" class="btn btn-sm btn-warning text-white" 
                                        title="Edit Data Guru"
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
                                    <i class='bx bx-edit-alt'></i> Edit
                                </button>

                                <!-- 2. Tombol Hapus Guru -->
                                <button type="button" class="btn btn-sm btn-danger" 
                                        title="Hapus Data Guru"
                                        onclick="confirmDeleteTeacher('{{ $teacher->id }}', '{{ addslashes($teacher->name) }}')">
                                    <i class='bx bx-trash'></i> Hapus
                                </button>
                                <form id="deleteTeacherForm-{{ $teacher->id }}" action="{{ route('admin.guru.destroy', $teacher->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ Auth::check() && Auth::user()->role === 'admin' ? 7 : 6 }}" class="text-center py-5 text-muted text-nowrap">
                            <i class='bx bx-user-x fs-1 d-block mb-2 text-slate-300'></i>
                            <div class="fw-semibold">Belum ada data guru atau wali kelas tercatat.</div>
                            <small class="text-muted">Gunakan tombol "Tambah Guru" atau "Import Excel" untuk menambahkan data.</small>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($teachers->hasPages())
        <div class="px-4 py-2 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 bg-white small">
            <div class="text-muted small">
                Menampilkan <span>{{ $teachers->firstItem() ?? 0 }}</span> - <span>{{ $teachers->lastItem() ?? 0 }}</span> dari <span>{{ $teachers->total() }}</span> guru
            </div>
            <div class="pagination-compact">
                {{ $teachers->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>

    @if(Auth::check() && Auth::user()->role === 'admin')
    <!-- ========================================================================= -->
    <!-- MODAL 1: TAMBAH GURU                                                      -->
    <!-- ========================================================================= -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">Tambah Data Guru & Wali Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.guru.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body py-3">
                        <div class="alert alert-primary border-0 rounded-3 py-2 px-3 small mb-3 d-flex align-items-center gap-2" style="background-color: #eff6ff; color: #1d4ed8;">
                            <i class='bx bx-info-circle fs-5 flex-shrink-0'></i>
                            <span>Akun login guru otomatis dibuat dengan <strong>NIP</strong> sebagai kata sandi default.</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label small fw-semibold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: Dra. Hj. Siti Aminah, M.Pd" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold">NIP (Nomor Induk Pegawai)</label>
                                <input type="text" name="nip" class="form-control rounded-3" placeholder="Nomor Induk Pegawai">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select rounded-3" required>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Penugasan Wali Kelas</label>
                                <select name="school_class_id" class="form-select rounded-3">
                                    <option value="">-- Bukan Wali Kelas (Guru Pengajar Saja) --</option>
                                    @foreach($classes as $c)
                                    <option value="{{ $c->id }}">Kelas {{ $c->name }} (Tingkat {{ $c->grade ?? $c->level }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Tempat Lahir</label>
                                <input type="text" name="birth_place" class="form-control rounded-3" placeholder="Kota / Kabupaten Lahir">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Tanggal Lahir</label>
                                <input type="date" name="birth_date" class="form-control rounded-3">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">No. Telepon / WhatsApp</label>
                                <input type="text" name="phone_number" class="form-control rounded-3" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Foto Profil (Opsional)</label>
                                <input type="file" name="photo" class="form-control rounded-3" accept="image/*">
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
                    <h5 class="fw-bold mb-0">Edit Data Guru & Wali Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editTeacherForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body py-3">
                        <div class="row g-3">
                            <div class="col-12 col-md-8">
                                <label class="form-label small fw-semibold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control rounded-3" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold">NIP</label>
                                <input type="text" name="nip" id="edit_nip" class="form-control rounded-3">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="gender" id="edit_gender" class="form-select rounded-3" required>
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Penugasan Wali Kelas</label>
                                <select name="school_class_id" id="edit_class_id" class="form-select rounded-3">
                                    <option value="none">-- Bukan Wali Kelas (Guru Pengajar Saja) --</option>
                                    @foreach($classes as $c)
                                    <option value="{{ $c->id }}">Kelas {{ $c->name }} (Tingkat {{ $c->grade ?? $c->level }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Tempat Lahir</label>
                                <input type="text" name="birth_place" id="edit_birth_place" class="form-control rounded-3">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Tanggal Lahir</label>
                                <input type="date" name="birth_date" id="edit_birth_date" class="form-control rounded-3">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">No. Telepon / WhatsApp</label>
                                <input type="text" name="phone_number" id="edit_phone" class="form-control rounded-3">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Ubah Foto Profil (Opsional)</label>
                                <input type="file" name="photo" class="form-control rounded-3" accept="image/*">
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
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">Import Data Guru dari Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.guru.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Unduh Template Format</label>
                            <a href="{{ route('admin.guru.template') }}" class="d-flex align-items-center justify-content-between p-2.5 rounded-3 border bg-light text-decoration-none hover:bg-slate-100 transition-all">
                                <div class="d-flex align-items-center gap-2">
                                    <i class='bx bx-download text-success fs-4'></i>
                                    <div>
                                        <div class="fw-bold text-dark small">Template_Wali_Kelas.xlsx</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Gunakan format ini untuk import data massal</div>
                                    </div>
                                </div>
                                <span class="badge bg-success small">Unduh</span>
                            </a>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pilih File Excel / CSV <span class="text-danger">*</span></label>
                            <input type="file" name="file_excel" class="form-control rounded-3" accept=".xlsx,.xls,.csv" required>
                            <div class="text-muted mt-1" style="font-size: 0.72rem;">Maksimal ukuran file 5 MB.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success text-white rounded-3 px-4 fw-semibold">Mulai Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection

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

    // 2. Konfirmasi Hapus Guru Satuan
    function confirmDeleteTeacher(id, name) {
        confirmUniversalDelete({
            title: 'Hapus Data Guru?',
            html: `Tindakan ini bersifat permanen. Anda akan menghapus data guru <b class="text-dark">${name}</b> dari sistem. Penugasan wali kelas yang bersangkutan akan otomatis dilepaskan.`,
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
            onConfirm: function() {
                document.getElementById('deleteTeacherForm-' + id).submit();
            }
        });
    }

    // 3. Konfirmasi Hapus Semua Guru Massal
    function confirmDeleteAllTeachers() {
        confirmUniversalDelete({
            title: 'Hapus Seluruh Data Guru?',
            html: 'Tindakan ini bersifat permanen. Anda akan menghapus <b class="text-dark">seluruh data guru</b> serta melepaskan penugasan wali kelas dari semua rombel.',
            confirmText: 'Ya, Hapus Semua',
            cancelText: 'Batal',
            onConfirm: function() {
                document.getElementById('deleteAllTeachersForm').submit();
            }
        });
    }
</script>
@endpush
@endif