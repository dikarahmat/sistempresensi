@extends('layouts.app')

@section('title', 'Master Hari Libur')
@section('page_title', 'Master Hari Libur & Cuti')
@section('page_subtitle', 'Daftar hari libur nasional, libur semester, dan cuti bersama yang dikecualikan dari presensi.')

@section('page_header_right')
<!-- Tombol Aksi Header Versi Desktop (>= 768px): Sejajar Horizontal Asli -->
<div class="d-none d-md-block">
    <div class="d-flex align-items-center gap-2">
        <!-- Tombol Import Excel -->
        <button type="button" class="btn btn-green-excel btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#importHolidayModal">
            <i class='bx bx-file'></i> Import Excel
        </button>

        <!-- Tombol Tambah Hari Libur -->
        <button type="button" class="btn btn-add-holiday btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
            <i class='bx bx-calendar-plus'></i> Tambah Hari Libur
        </button>
    </div>
</div>
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
        padding: 0.45rem 1rem;
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

    .btn-add-holiday {
        background-color: #ef4444;
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 0.88rem;
        border-radius: 8px;
        padding: 0.45rem 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 1px 2px rgba(239, 68, 68, 0.15);
        transition: all 0.15s ease;
    }
    .btn-add-holiday:hover { background-color: #dc2626; color: #ffffff; transform: translateY(-1px); }

    /* Tabel Hari Libur */
    .table-holidays {
        width: 100%;
        min-width: 780px;
        margin-bottom: 0;
    }

    .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
    }

    /* Zebra Striping Khusus (Sesuai Benchmark Data Siswa) */
    .table-holidays tbody tr:nth-child(even) > td,
    .table-holidays tbody tr.baris-abu > td,
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f1f5f9 !important;
    }
    .table-holidays tbody tr:nth-child(odd) > td,
    .table-holidays tbody tr.baris-putih > td,
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-holidays tbody tr.baris-abu:hover > td,
    .table-holidays tbody tr.baris-putih:hover > td,
    .table-holidays tbody tr:hover > td,
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td {
        background-color: #e2e8f0 !important;
    }

    .table-holidays th,
    .table-holidays td {
        vertical-align: middle;
    }

    .table-holidays tbody td {
        color: #000000 !important;
        font-weight: 400 !important;
        font-size: 0.88rem;
        padding: 0.95rem 1rem;
    }

    /* Indentasi Teks Kiri */
    .indent-tanggal {
        padding-left: 2rem !important;
    }
    .indent-keterangan {
        padding-left: 1.5rem !important;
    }

    /* Kontainer Tombol Aksi */
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
    <!-- 1. KONTEN KHUSUS MOBILE (< 768px): KARTU TERPADU TOMBOL & ALERT            -->
    <!-- ========================================================================= -->
    <div class="d-block d-md-none mb-3">
        <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white">
            <!-- Grid 2 Tombol Aksi Mobile -->
            <div class="row g-2">
                <div class="col-6">
                    <button type="button" class="btn btn-success btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#importHolidayModal">
                        <i class='bx bx-file fs-6'></i>
                        <span class="text-nowrap" style="font-size: 0.8rem;">Import Excel</span>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-danger btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                        <i class='bx bx-calendar-plus fs-6'></i>
                        <span class="text-nowrap" style="font-size: 0.8rem;">Tambah Hari Libur</span>
                    </button>
                </div>
            </div>

            <!-- Sekat Pembatas -->
            <hr class="border-secondary-subtle my-3">

            <!-- Catatan Integrasi Otomatis Presensi (Mobile) -->
            <p class="text-danger small mt-2 mb-0" style="font-size: 0.8rem; line-height: 1.4;">
                <span class="fw-semibold">Catatan:</span> Hari libur yang didaftarkan di sini tidak akan dihitung sebagai "Alpha" dalam rekapitulasi presensi siswa.
            </p>
        </div>
    </div>

    <!-- Catatan Integrasi Otomatis Presensi Versi Desktop (>= 768px) -->
    <div class="d-none d-md-block mb-3">
        <p class="text-danger small mt-2 mb-0">
            <span class="fw-semibold">Catatan:</span> Hari libur yang didaftarkan di sini tidak akan dihitung sebagai "Alpha" dalam rekapitulasi presensi siswa.
        </p>
    </div>

    <!-- Tabel Data Hari Libur -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-holidays table-zebra-custom text-nowrap">
                <thead class="bg-light text-nowrap">
                    <tr class="text-secondary small fw-bold text-uppercase text-nowrap" style="letter-spacing: 0.03em;">
                        <th class="text-center py-3 px-3 text-nowrap" style="width: 6%;">NO</th>
                        <th class="text-center py-3 px-3 text-nowrap" style="width: 14%;">Tipe</th>
                        <th class="text-start indent-tanggal py-3 px-3 text-nowrap" style="width: 28%;">Tanggal / Periode</th>
                        <th class="text-center py-3 px-3 text-nowrap" style="width: 12%;">Durasi</th>
                        <th class="text-start indent-keterangan py-3 px-3 text-nowrap" style="width: 25%;">Keterangan Libur</th>
                        <th class="text-center py-3 px-3 text-nowrap" style="width: 15%;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-nowrap">
                    @forelse($holidays as $index => $holiday)
                    @php
                        $start = \Carbon\Carbon::parse($holiday->start_date ?? $holiday->date);
                        $end = \Carbon\Carbon::parse($holiday->end_date ?? $holiday->date);
                        $daysDiff = $start->diffInDays($end) + 1;
                        $isRange = $daysDiff > 1;
                    @endphp
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }} text-nowrap">
                        <td class="text-center px-3 text-nowrap">{{ $holidays->firstItem() + $index }}</td>
                        <td class="text-center px-3 text-nowrap">
                            {{ $isRange ? 'Rentang' : 'Tunggal' }}
                        </td>
                        <td class="text-start indent-tanggal px-3 text-nowrap">
                            @if($isRange)
                                {{ $start->translatedFormat('d M Y') }} &mdash; {{ $end->translatedFormat('d M Y') }}
                            @else
                                {{ $start->translatedFormat('l, d F Y') }}
                            @endif
                        </td>
                        <td class="text-center px-3 text-nowrap">
                            {{ $daysDiff }} Hari
                        </td>
                        <td class="text-start indent-keterangan px-3 text-nowrap">
                            {{ $holiday->description }}
                        </td>
                        <td class="text-center px-3 text-nowrap">
                            <div class="crud-center-wrapper">
                                <!-- Tombol Edit Modal -->
                                <button type="button" class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editHolidayModal{{ $holiday->id }}" title="Edit">
                                    <i class='bx bx-edit-alt'></i> Edit
                                </button>

                                <!-- Tombol Hapus Satuan -->
                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteHoliday('{{ $holiday->id }}', '{{ addslashes($holiday->description) }}')" title="Hapus">
                                    <i class='bx bx-trash'></i> Hapus
                                </button>
                                <form id="deleteHolidayForm-{{ $holiday->id }}" action="{{ route('admin.holidays.destroy', $holiday->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- MODAL EDIT HARI LIBUR -->
                    <div class="modal fade" id="editHolidayModal{{ $holiday->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <div class="modal-header border-bottom-0 pb-0">
                                    <h5 class="fw-bold mb-0">Edit Hari Libur</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('admin.holidays.update', $holiday->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body py-3">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Pilih Tipe Tanggal Libur</label>
                                            <div class="d-flex gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="type" id="editTypeSingle{{ $holiday->id }}" value="single" {{ !$isRange ? 'checked' : '' }} onchange="toggleEditDateRange('{{ $holiday->id }}', false)">
                                                    <label class="form-check-label small" for="editTypeSingle{{ $holiday->id }}">Tanggal Tunggal</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="type" id="editTypeRange{{ $holiday->id }}" value="range" {{ $isRange ? 'checked' : '' }} onchange="toggleEditDateRange('{{ $holiday->id }}', true)">
                                                    <label class="form-check-label small" for="editTypeRange{{ $holiday->id }}">Rentang Tanggal</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Container Single Date -->
                                        <div id="editSingleDateContainer{{ $holiday->id }}" class="mb-3" style="{{ $isRange ? 'display:none;' : '' }}">
                                            <label class="form-label small fw-semibold">Tanggal Libur</label>
                                            <input type="date" name="date" class="form-control rounded-3" value="{{ $start->format('Y-m-d') }}">
                                        </div>

                                        <!-- Container Range Date -->
                                        <div id="editRangeDateContainer{{ $holiday->id }}" class="row g-2 mb-3" style="{{ !$isRange ? 'display:none;' : '' }}">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Tanggal Mulai</label>
                                                <input type="date" name="start_date" class="form-control rounded-3" value="{{ $start->format('Y-m-d') }}">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Tanggal Selesai</label>
                                                <input type="date" name="end_date" class="form-control rounded-3" value="{{ $end->format('Y-m-d') }}">
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Keterangan Libur</label>
                                            <input type="text" name="description" class="form-control rounded-3" value="{{ $holiday->description }}" placeholder="Contoh: Libur Semester Ganjil" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top-0 pt-0">
                                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger rounded-3 px-4 fw-semibold">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @empty
                    <tr class="text-nowrap">
                        <td colspan="6" class="text-center py-5 text-muted text-nowrap">
                            <i class='bx bx-info-circle fs-2 d-block mb-2'></i>
                            Belum ada agenda hari libur yang terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($holidays->hasPages())
        <div class="px-4 py-2 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 bg-white small">
            <div class="text-muted small">
                Menampilkan <span>{{ $holidays->firstItem() ?? 0 }}</span> - <span>{{ $holidays->lastItem() ?? 0 }}</span> dari <span>{{ $holidays->total() }}</span> hari libur
            </div>
            <div class="pagination-compact">
                {{ $holidays->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>

<!-- MODAL TAMBAH HARI LIBUR -->
<div class="modal fade" id="addHolidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Tambah Agenda Hari Libur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.holidays.store') }}" method="POST">
                @csrf
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilih Format Libur</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="addTypeSingle" value="single" checked onchange="toggleAddDateRange(false)">
                                <label class="form-check-label small" for="addTypeSingle">Tanggal Tunggal (1 Hari)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="addTypeRange" value="range" onchange="toggleAddDateRange(true)">
                                <label class="form-check-label small" for="addTypeRange">Rentang Tanggal (Cuti / Semester)</label>
                            </div>
                        </div>
                    </div>

                    <!-- Single Date Input -->
                    <div id="addSingleDateContainer" class="mb-3">
                        <label class="form-label small fw-semibold">Tanggal Libur</label>
                        <input type="date" name="date" class="form-control rounded-3" value="{{ date('Y-m-d') }}">
                    </div>

                    <!-- Range Date Inputs -->
                    <div id="addRangeDateContainer" class="row g-2 mb-3" style="display: none;">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control rounded-3" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="end_date" class="form-control rounded-3" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Keterangan / Nama Libur</label>
                        <input type="text" name="description" class="form-control rounded-3" placeholder="Contoh: Hari Raya Idul Fitri / Libur Semester" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-4 fw-semibold">Simpan Hari Libur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL IMPORT EXCEL HARI LIBUR -->
<div class="modal fade" id="importHolidayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0">
                <h5 class="fw-bold mb-0">Import Data Hari Libur Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.holidays.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="p-3 bg-light rounded-3 small text-secondary mb-3">
                        <div class="mb-2">
                            Format kolom file Excel: <strong>Tanggal, Keterangan</strong> (.xlsx atau .csv)
                        </div>
                        <a href="{{ route('admin.holidays.template') }}" class="btn btn-sm btn-outline-success rounded-3 fw-semibold w-100">
                            <i class='bx bx-download me-1'></i> Unduh Template Excel (.xlsx) Kosong
                        </a>
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

@endsection

@push('scripts')
<script>
    function toggleAddDateRange(isRange) {
        document.getElementById('addSingleDateContainer').style.display = isRange ? 'none' : 'block';
        document.getElementById('addRangeDateContainer').style.display = isRange ? 'flex' : 'none';
    }

    function toggleEditDateRange(id, isRange) {
        document.getElementById('editSingleDateContainer' + id).style.display = isRange ? 'none' : 'block';
        document.getElementById('editRangeDateContainer' + id).style.display = isRange ? 'flex' : 'none';
    }

    function confirmDeleteHoliday(id, description) {
        confirmUniversalDelete({
            title: 'Hapus Hari Libur?',
            html: `Tindakan ini bersifat permanen. Anda akan menghapus agenda libur <b class="text-dark">${description}</b> dari kalender presensi sekolah.`,
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
            onConfirm: function() {
                document.getElementById(`deleteHolidayForm-${id}`).submit();
            }
        });
    }
</script>
@endpush