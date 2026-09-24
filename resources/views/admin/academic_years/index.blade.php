@extends('layouts.app')

@section('title', 'Master Tahun Ajaran')
@section('page_title', 'Master Tahun Ajaran')
@section('page_subtitle', 'Kelola periode akademik dan tentukan satu tahun ajaran aktif sistem.')

@section('page_header_right')
<!-- Tombol Aksi Header Versi Desktop (>= 768px): Sejajar Horizontal Asli -->
<div class="d-none d-md-block">
    <button type="button" class="btn btn-add-year btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#addYearModal">
        <i class='bx bx-plus me-1'></i> Tambah Tahun Ajaran
    </button>
</div>
@endsection

@push('styles')
<style>
    /* Tombol & Card */
    .btn-add-year {
        background-color: #3b82f6;
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 0.88rem;
        border-radius: 8px;
        padding: 0.45rem 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 1px 2px rgba(59, 130, 246, 0.15);
        transition: all 0.15s ease;
    }
    .btn-add-year:hover { background-color: #2563eb; color: #ffffff; transform: translateY(-1px); }

    /* Tabel Tahun Ajaran */
    .table-years {
        width: 100%;
        min-width: 780px;
        margin-bottom: 0;
    }

    .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
    }

    /* Zebra Striping Khusus (Sesuai Benchmark Data Siswa) */
    .table-years tbody tr:nth-child(even) > td,
    .table-years tbody tr.baris-abu > td,
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f1f5f9 !important;
    }
    .table-years tbody tr:nth-child(odd) > td,
    .table-years tbody tr.baris-putih > td,
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-years tbody tr.baris-abu:hover > td,
    .table-years tbody tr.baris-putih:hover > td,
    .table-years tbody tr:hover > td,
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td {
        background-color: #e2e8f0 !important;
    }

    .table-years th,
    .table-years td {
        vertical-align: middle;
    }

    .table-years tbody td {
        color: #000000 !important;
        font-weight: 400 !important;
        font-size: 0.88rem;
        padding: 0.95rem 1rem;
    }

    .col-status-aktif {
        display: inline-block;
    }

    .crud-center-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
    }


    .form-check-input:checked {
        background-color: #059669;
        border-color: #059669;
    }

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

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- ========================================================================= -->
    <!-- 1. KONTEN KHUSUS MOBILE (< 768px): KARTU KONTINER TOMBOL AKSI               -->
    <!-- ========================================================================= -->
    <div class="d-block d-md-none mb-3">
        <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white">
            <button type="button" class="btn btn-primary btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#addYearModal">
                <i class='bx bx-plus fs-6'></i>
                <span>Tambah Tahun Ajaran</span>
            </button>
        </div>
    </div>

    <!-- Tabel Data Tahun Ajaran -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-years text-nowrap">
                <thead class="bg-light">
                    <tr class="text-secondary small fw-bold text-uppercase align-middle text-nowrap" style="letter-spacing: 0.03em;">
                        <th class="text-center py-3 text-nowrap px-3" style="width: 6%;">NO</th>
                        <th class="text-center py-3 text-nowrap px-3">TAHUN AJARAN</th>
                        <th class="text-center py-3 text-nowrap px-3">SEMESTER</th>
                        <th class="text-center py-3 text-nowrap px-3">PERIODE TANGGAL</th>
                        <th class="text-center py-3 text-nowrap px-3">
                            <span class="col-status-aktif">STATUS AKTIF</span>
                        </th>
                        <th class="text-center py-3 text-nowrap px-3" style="width: 11%;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($academicYears as $index => $year)
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }} align-middle text-nowrap">
                        <td class="text-center text-nowrap px-3">{{ $academicYears->firstItem() + $index }}</td>
                        <td class="text-center text-nowrap px-3">
                            <div class="d-inline-flex align-items-center justify-content-center gap-2">
                                <span>{{ $year->name }}</span>
                                @if($year->is_active)
                                    <span class="badge bg-success text-white px-2 py-0.5 rounded-pill" style="font-size: 0.7rem;">
                                        AKTIF
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="text-center text-nowrap px-3">
                            Semester {{ $year->semester }}
                        </td>
                        <td class="text-center text-nowrap px-3">
                            <i class='bx bx-calendar text-secondary me-1'></i>
                            {{ \Carbon\Carbon::parse($year->start_date)->translatedFormat('d M Y') }} &mdash; 
                            {{ \Carbon\Carbon::parse($year->end_date)->translatedFormat('d M Y') }}
                        </td>
                        <td class="text-center text-nowrap px-3">
                            <span class="col-status-aktif">
                                <form action="{{ route('admin.academic-years.toggle-active', $year->id) }}" method="POST" id="toggleForm{{ $year->id }}" class="d-inline m-0">
                                    @csrf
                                    <div class="form-check form-switch d-inline-flex align-items-center justify-content-center gap-2 m-0 p-0">
                                        <input class="form-check-input float-none m-0" type="checkbox" role="switch" id="switch{{ $year->id }}" {{ $year->is_active ? 'checked disabled' : '' }} onchange="confirmToggleActive('{{ $year->id }}', '{{ $year->name }}', '{{ $year->semester }}', this)" style="cursor: pointer; width: 38px; height: 20px;">
                                        <label class="form-check-label small fw-semibold {{ $year->is_active ? 'text-success' : 'text-muted' }}" for="switch{{ $year->id }}" style="cursor: pointer;">
                                            {{ $year->is_active ? 'Sedang Aktif' : 'Aktifkan' }}
                                        </label>
                                    </div>
                                </form>
                            </span>
                        </td>
                        <td class="text-center text-nowrap px-3">
                            <div class="crud-center-wrapper">
                                <!-- Tombol Edit Modal -->
                                <button type="button" class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editYearModal{{ $year->id }}" title="Edit">
                                    <i class='bx bx-edit-alt'></i> Edit
                                </button>

                                <!-- Tombol Hapus Satuan -->
                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteYear('{{ $year->id }}', '{{ $year->name }}')" {{ $year->is_active ? 'disabled title="Tidak dapat menghapus tahun ajaran aktif"' : 'title="Hapus"' }}>
                                    <i class='bx bx-trash'></i> Hapus
                                </button>
                                <form id="deleteYearForm-{{ $year->id }}" action="{{ route('admin.academic-years.destroy', $year->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- MODAL EDIT TAHUN AJARAN -->
                    <div class="modal fade" id="editYearModal{{ $year->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <div class="modal-header border-bottom-0 pb-0">
                                    <h5 class="fw-bold mb-0">Edit Tahun Ajaran</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('admin.academic-years.update', $year->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body py-3">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Nama Tahun Ajaran</label>
                                            <input type="text" name="name" class="form-control rounded-3" value="{{ $year->name }}" placeholder="Contoh: 2025/2026" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Semester</label>
                                            <select name="semester" class="form-select rounded-3" required>
                                                <option value="Ganjil" {{ $year->semester == 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                                                <option value="Genap" {{ $year->semester == 'Genap' ? 'selected' : '' }}>Genap</option>
                                            </select>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Tanggal Mulai</label>
                                                <input type="date" name="start_date" class="form-control rounded-3" value="{{ \Carbon\Carbon::parse($year->start_date)->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Tanggal Selesai</label>
                                                <input type="date" name="end_date" class="form-control rounded-3" value="{{ \Carbon\Carbon::parse($year->end_date)->format('Y-m-d') }}" required>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive{{ $year->id }}" {{ $year->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label small fw-semibold" for="editActive{{ $year->id }}">Set sebagai Tahun Ajaran Aktif</label>
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

                    @empty
                    <tr class="text-nowrap">
                        <td colspan="6" class="text-center py-5 text-muted text-nowrap">
                            <i class='bx bx-info-circle fs-2 d-block mb-2'></i>
                            Belum ada data tahun ajaran yang terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($academicYears->hasPages())
        <div class="px-4 py-2 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 bg-white small">
            <div class="text-muted small">
                Menampilkan <span>{{ $academicYears->firstItem() ?? 0 }}</span> - <span>{{ $academicYears->lastItem() ?? 0 }}</span> dari <span>{{ $academicYears->total() }}</span> tahun ajaran
            </div>
            <div class="pagination-compact">
                {{ $academicYears->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>

<!-- MODAL TAMBAH TAHUN AJARAN -->
<div class="modal fade" id="addYearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Tambah Tahun Ajaran Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.academic-years.store') }}" method="POST">
                @csrf
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Tahun Ajaran</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: 2025/2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Semester</label>
                        <select name="semester" class="form-select rounded-3" required>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control rounded-3" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="end_date" class="form-control rounded-3" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="addActive" checked>
                        <label class="form-check-label small fw-semibold" for="addActive">Set sebagai Tahun Ajaran Aktif Langsung</label>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function confirmDeleteYear(id, name) {
        confirmUniversalDelete({
            title: 'Hapus Tahun Ajaran?',
            html: `Tindakan ini bersifat permanen. Anda akan menghapus data tahun ajaran <b class="text-dark">${name}</b> dari sistem.`,
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
            onConfirm: function() {
                document.getElementById(`deleteYearForm-${id}`).submit();
            }
        });
    }

    function confirmToggleActive(id, name, semester, checkbox) {
        Swal.fire({
            title: 'Aktifkan Tahun Ajaran?',
            text: `Jadikan tahun ajaran ${name} (Semester ${semester}) sebagai yang aktif di sistem?`,
            icon: 'question',
            iconColor: '#3b82f6',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#f1f5f9',
            confirmButtonText: 'Ya, Aktifkan',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            customClass: {
                popup: 'shadow-lg border-0 rounded-4 p-4',
                title: 'fw-bold fs-4 text-dark mb-2',
                htmlContainer: 'text-secondary fs-6 mb-4',
                actions: 'gap-2 w-100 justify-content-center m-0',
                confirmButton: 'btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-xs',
                cancelButton: 'btn btn-light text-secondary px-4 py-2 rounded-3 fw-semibold border'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`toggleForm${id}`).submit();
            } else {
                checkbox.checked = false;
            }
        })
    }
</script>
@endpush