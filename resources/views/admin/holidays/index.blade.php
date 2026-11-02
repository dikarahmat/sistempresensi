@extends('layouts.app')

@section('title', __('Hari Libur'))
@section('page_title', __('Hari Libur & Cuti'))

@push('styles')
<style>
    /* Subtitle Hari Libur: full width, tidak terpotong (mobile & desktop) */
    .header-main-subtitle {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        display: block !important;
        width: 100% !important;
    }

    /* Tombol Batal di semua modal halaman Hari Libur */
    #importHolidayModal .btn-light,
    #addHolidayModal .btn-light,
    #editHolidayModal .btn-light,
    .modal .btn-light {
        background-color: #E5E7EB !important;
        color: #374151 !important;
        border: 1px solid #D1D5DB !important;
        font-weight: 700 !important;
    }
    #importHolidayModal .btn-light:hover,
    #addHolidayModal .btn-light:hover,
    #editHolidayModal .btn-light:hover,
    .modal .btn-light:hover,
    #importHolidayModal .btn-light:focus,
    #addHolidayModal .btn-light:focus,
    #editHolidayModal .btn-light:focus,
    .modal .btn-light:focus {
        background-color: #D1D5DB !important;
        color: #374151 !important;
        border-color: #D1D5DB !important;
    }
</style>
@endpush

@section('page_header_right')
<!-- Tombol Aksi Header Versi Desktop (>= 768px): Sejajar Horizontal, Benar-benar Diam Tanpa Efek Putih/Klik -->
<div class="d-none d-md-block">
    <div class="d-flex align-items-center gap-2">
        <!-- Tombol Import Excel -->
        <button type="button" class="btn btn-green-excel btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#importHolidayModal">
            {{ __('Import Excel') }}
        </button>

        <!-- Tombol Tambah Hari Libur -->
        <button type="button" class="btn btn-add-holiday btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
            {{ __('Tambah Hari Libur') }}
        </button>

        @if(isset($holidays) && $holidays->total() > 0)
        <!-- Tombol Hapus Semua -->
        <button type="button" class="btn btn-sm shadow-xs" style="background-color: #dc2626 !important; color: #ffffff !important; border: none !important; font-weight: 600; font-size: 0.88rem; border-radius: var(--clean-radius); padding: 0.45rem 1rem;" onclick="confirmDeleteAllHolidays()">
            {{ __('Hapus Semua') }}
        </button>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    /* KUNCI UTAMA: Tombol Navigasi & Aksi agar Diam Total (Tanpa Efek Putih / Klik / Animasi) */
    .btn-green-excel,
    .btn-add-holiday,
    .btn-download-green,
    .btn-group .btn,
    .settings-nav-btn {
        transition: none !important;
        transform: none !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
    }

    .btn-green-excel {
        background-color: #059669 !important;
        color: #ffffff !important;
        border: none !important;
        font-weight: 600;
        font-size: 0.88rem;
        border-radius: var(--clean-radius);
        padding: 0.45rem 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .btn-green-excel:hover, 
    .btn-green-excel:active, 
    .btn-green-excel:focus,
    .btn-green-excel:focus-visible { 
        background-color: #047857 !important; 
        color: #ffffff !important; 
        transform: none !important;
        box-shadow: 0 1px 2px rgba(5, 150, 105, 0.15) !important;
        outline: none !important;
    }

    .btn-add-holiday {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border: none !important;
        font-weight: 600;
        font-size: 0.88rem;
        border-radius: var(--clean-radius);
        padding: 0.45rem 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .btn-add-holiday:hover, 
    .btn-add-holiday:active, 
    .btn-add-holiday:focus,
    .btn-add-holiday:focus-visible { 
        background-color: #1d4ed8 !important; 
        color: #ffffff !important; 
        transform: none !important; 
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.15) !important;
        outline: none !important;
    }

    .btn-download-green {
        background-color: #059669 !important;
        color: #ffffff !important;
        border: none !important;
        font-weight: 600;
        font-size: 0.88rem;
        border-radius: var(--clean-radius);
        padding: 0.45rem 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    .btn-download-green:hover, 
    .btn-download-green:active,
    .btn-download-green:focus {
        background-color: #047857 !important;
        color: #ffffff !important;
        transform: none !important;
        outline: none !important;
    }

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

    /* Zebra Striping Khusus */
    .table-holidays tbody tr:nth-child(even) > td,
    .table-holidays tbody tr.baris-abu > td {
        background-color: #f1f5f9 !important;
    }
    .table-holidays tbody tr:nth-child(odd) > td,
    .table-holidays tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-holidays tbody tr:hover > td {
        background-color: #e2e8f0 !important;
    }

    .table-holidays th,
    .table-holidays td {
        vertical-align: middle;
    }

    .table-holidays tbody td {
        color: #000000 !important;
        font-weight: 400 !important;
        font-size: 0.85rem;
        padding: 0.85rem 1rem;
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

    /* Kotak Info Berwarna */
    .info-box {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        padding: 0.8rem 1rem;
        border-radius: 0.75rem;
        background-color: #dbeafe;
        color: #1e293b;
        font-size: 0.85rem;
        line-height: 1.5;
    }
    .info-box i { color: #2563eb; font-size: 1.2rem; margin-top: 0.05rem; }

    /* Pagination Ringkas */
    .pagination-compact .pagination {
        margin-bottom: 0;
        font-size: 0.82rem;
    }
    .pagination-compact .page-link {
        padding: 0.25rem 0.6rem;
    }

    .alert-libur-body {
        display: block;
    }
</style>
@endpush

@section('content')
    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 alert-libur" role="alert">
        <div class="alert-libur-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 alert-libur" role="alert">
        <div class="alert-libur-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('deleted'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 alert-libur" role="alert">
        <div class="alert-libur-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-trash fs-5 me-2 text-danger'></i>
                <span>{{ session('deleted') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    <!-- Kotak Info Profesional -->
    <div class="info-box mb-3">
        <i class='bx bx-info-circle'></i>
        <span>{{ __('Hari libur yang didaftarkan di sini otomatis dikecualikan dan tidak dihitung sebagai ketidakhadiran tanpa keterangan (alpha) dalam rekapitulasi presensi siswa.') }}</span>
    </div>

    <!-- Tombol aksi mobile -->
    <div class="d-block d-md-none mb-3">
        <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white">
            <div class="d-flex flex-column gap-2">
                <button type="button" class="btn btn-green-excel btn-sm w-100 rounded-3 shadow-xs py-1.5 fw-semibold justify-content-center" data-bs-toggle="modal" data-bs-target="#importHolidayModal">
                    {{ __('Import Excel') }}
                </button>
                <button type="button" class="btn btn-add-holiday btn-sm w-100 rounded-3 shadow-xs py-1.5 fw-semibold justify-content-center" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                    {{ __('Tambah Hari Libur') }}
                </button>
                @if(isset($holidays) && $holidays->total() > 0)
                <button type="button" class="btn btn-sm w-100 rounded-3 shadow-xs py-1.5 fw-semibold justify-content-center" style="background-color: #dc2626 !important; color: #ffffff !important; border: none !important;" onclick="confirmDeleteAllHolidays()">
                    {{ __('Hapus Semua') }}
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabel Data Hari Libur -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-holidays text-nowrap">
                <thead class="bg-light">
                    <tr class="text-secondary small fw-bold align-middle text-uppercase text-nowrap" style="letter-spacing: 0.03em;">
                        <th class="text-center py-3 px-3" style="width: 6%;">{{ __('No') }}</th>
                        <th class="text-center py-3 px-3" style="width: 14%;">{{ __('Tipe') }}</th>
                        <th class="text-start indent-tanggal py-3 px-3" style="width: 28%;">{{ __('Tanggal / Periode') }}</th>
                        <th class="text-center py-3 px-3" style="width: 12%;">{{ __('Durasi') }}</th>
                        <th class="text-start indent-keterangan py-3 px-3" style="width: 25%;">{{ __('Keterangan Libur') }}</th>
                        <th class="text-center py-3 px-3" style="width: 15%;">{{ __('Aksi') }}</th>
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
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td class="text-center px-3">{{ $holidays->firstItem() + $index }}</td>
                        <td class="text-center px-3">
                            {{ $isRange ? __('Rentang') : __('Tunggal') }}
                        </td>
                        <td class="text-start indent-tanggal px-3">
                            @if($isRange)
                                {{ $start->translatedFormat('d M Y') }} &mdash; {{ $end->translatedFormat('d M Y') }}
                            @else
                                {{ $start->translatedFormat('l, d F Y') }}
                            @endif
                        </td>
                        <td class="text-center px-3">
                            {{ $daysDiff }} {{ __('Hari') }}
                        </td>
                        <td class="text-start indent-keterangan px-3">
                            {{ $holiday->description }}
                        </td>
                        <td class="text-center px-3">
                            <div class="crud-center-wrapper">
                                <button type="button" class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editHolidayModal{{ $holiday->id }}" title="{{ __('Edit') }}">
                                    {{ __('Edit') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteHoliday('{{ $holiday->id }}', '{{ addslashes($holiday->description) }}')" title="{{ __('Hapus') }}">
                                    {{ __('Hapus') }}
                                </button>
                                <form id="deleteHolidayForm-{{ $holiday->id }}" action="{{ panel_route('holidays.destroy', $holiday->id) }}" method="POST" class="d-none">
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
                                    <h5 class="fw-bold mb-0">{{ __('Edit Hari Libur') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ panel_route('holidays.update', $holiday->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body py-3">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">{{ __('Pilih Format Libur') }}</label>
                                            <div class="d-flex flex-column gap-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="type" id="editTypeSingle{{ $holiday->id }}" value="single" {{ !$isRange ? 'checked' : '' }} onchange="toggleEditDateRange('{{ $holiday->id }}', false)">
                                                    <label class="form-check-label small" for="editTypeSingle{{ $holiday->id }}">{{ __('Tanggal Tunggal (1 Hari)') }}</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="type" id="editTypeRange{{ $holiday->id }}" value="range" {{ $isRange ? 'checked' : '' }} onchange="toggleEditDateRange('{{ $holiday->id }}', true)">
                                                    <label class="form-check-label small" for="editTypeRange{{ $holiday->id }}">{{ __('Rentang Tanggal (Cuti / Semester)') }}</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="editSingleDateContainer{{ $holiday->id }}" class="mb-3" style="{{ $isRange ? 'display:none;' : '' }}">
                                            <label class="form-label small fw-semibold">{{ __('Tanggal Libur') }}</label>
                                            <input type="date" name="date" class="form-control rounded-3" value="{{ $start->format('Y-m-d') }}">
                                        </div>

                                        <div id="editRangeDateContainer{{ $holiday->id }}" class="row g-2 mb-3" style="{{ !$isRange ? 'display:none;' : '' }}">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">{{ __('Tanggal Mulai') }}</label>
                                                <input type="date" name="start_date" class="form-control rounded-3" value="{{ $start->format('Y-m-d') }}">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">{{ __('Tanggal Selesai') }}</label>
                                                <input type="date" name="end_date" class="form-control rounded-3" value="{{ $end->format('Y-m-d') }}">
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">{{ __('Keterangan / Nama Libur') }}</label>
                                            <input type="text" name="description" class="form-control rounded-3" value="{{ $holiday->description }}" placeholder="{{ __('Contoh: Hari Raya Idul Fitri / Libur Semester') }}" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top-0 pt-0">
                                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                                        <button type="submit" class="btn btn-danger rounded-3 px-4 fw-semibold btn-progress" data-dl-progress data-form-submit>{{ __('Simpan Perubahan') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class='bx bx-error fs-4 mb-2' aria-hidden='true'></i>
                            <div>{{ __('Belum ada agenda hari libur yang terdaftar.') }}</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($holidays->hasPages())
        <div class="px-4 py-2 border-top d-flex justify-content-between align-items-center bg-white small">
            <div class="text-muted">
                {{ __('Menampilkan') }} <span>{{ $holidays->firstItem() ?? 0 }}</span> &ndash; <span>{{ $holidays->lastItem() ?? 0 }}</span> {{ __('dari') }} <span>{{ $holidays->total() }}</span> {{ __('hari libur') }}
            </div>
            <div class="pagination-compact">
                {{ $holidays->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>

    <!-- MODAL IMPORT EXCEL HARI LIBUR -->
    <div class="modal fade" id="importHolidayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0">
                    <h5 class="fw-bold mb-0">{{ __('Import Data Hari Libur Excel') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ panel_route('holidays.import') }}" method="POST" enctype="multipart/form-data" id="importExcelForm">
                    @csrf
                    <div class="modal-body py-3">
                        @if($errors->any() && ($errors->has('file_excel') || $errors->has('file')))
                        <div class="alert alert-danger py-2 px-3 small mb-3 border-0 rounded-3" role="alert" style="word-break: break-word; white-space: normal;">
                            <i class='bx bx-error-circle me-1'></i> {{ $errors->first('file_excel') ?: $errors->first('file') }}
                        </div>
                        @endif
                        <div class="p-3 bg-white border rounded-3 small text-secondary mb-3">
                            <div class="mb-2">
                                {{ __('Format kolom file Excel:') }} <strong>{{ __('Tanggal, Keterangan') }}</strong> {{ __('(.xlsx atau .csv)') }}
                            </div>
                            <a href="{{ panel_route('holidays.template') }}" class="btn-download-green w-100" data-download data-no-download data-dl-progress>{{ __('Unduh Template Excel') }}</a>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">{{ __('Pilih File Excel') }}</label>
                            <input type="file" name="file_excel" id="importFileInput" class="form-control rounded-3 @error('file_excel') is-invalid @enderror @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required onchange="validateImportForm()">
                            @error('file_excel')<div class="invalid-feedback d-block" style="word-break: break-word; white-space: normal;">{{ $message }}</div>@enderror
                            @error('file')<div class="invalid-feedback d-block" style="word-break: break-word; white-space: normal;">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal" id="btnBatalImportHoliday">{{ __('Batal') }}</button>
                        <button type="submit" id="submitImportBtn" class="btn-download-green px-4 btn-progress" data-import data-dl-progress disabled style="opacity: 0.45; cursor: not-allowed; background-color: #059669 !important;">{{ __('Unggah & Import') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH HARI LIBUR -->
    <div class="modal fade" id="addHolidayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">{{ __('Tambah Agenda Hari Libur') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ panel_route('holidays.store') }}" method="POST" id="addHolidayForm">
                    @csrf
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Pilih Format Libur') }}</label>
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="addTypeSingle" value="single" checked onchange="toggleAddDateRange(false)">
                                    <label class="form-check-label small" for="addTypeSingle">{{ __('Tanggal Tunggal (1 Hari)') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="type" id="addTypeRange" value="range" onchange="toggleAddDateRange(true)">
                                    <label class="form-check-label small" for="addTypeRange">{{ __('Rentang Tanggal (Cuti / Semester)') }}</label>
                                </div>
                            </div>
                        </div>

                        <!-- Container Single Date -->
                        <div id="addSingleDateContainer" class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Tanggal Libur') }}</label>
                            <input type="date" name="date" id="addDate" class="form-control rounded-3 holiday-input">
                        </div>

                        <!-- Container Range Date -->
                        <div id="addRangeDateContainer" class="row g-2 mb-3" style="display:none;">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">{{ __('Tanggal Mulai') }}</label>
                                <input type="date" name="start_date" id="addStartDate" class="form-control rounded-3 holiday-input">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">{{ __('Tanggal Selesai') }}</label>
                                <input type="date" name="end_date" id="addEndDate" class="form-control rounded-3 holiday-input">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">{{ __('Keterangan / Nama Libur') }}</label>
                            <input type="text" name="description" id="addDescription" class="form-control rounded-3 holiday-input" placeholder="{{ __('Contoh: Hari Raya Idul Fitri / Libur Semester') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                        <button type="submit" id="submitAddHoliday" class="btn btn-primary rounded-3 px-4 fw-semibold btn-progress" data-dl-progress data-form-submit disabled style="opacity: 0.55; cursor: not-allowed;">{{ __('Simpan Hari Libur') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="deleteAllHolidaysForm" action="{{ route('admin.holidays.destroy-all') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
<script>
    // Auto-hide flash messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert-libur');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                if (bsAlert) {
                    bsAlert.close();
                } else {
                    alert.classList.remove('show');
                    setTimeout(function() { alert.remove(); }, 150);
                }
            }, 5000);
        });

        const importModal = document.getElementById('importHolidayModal');
        if (importModal) {
            importModal.addEventListener('dl-busy-start', function() {
                const btnBatal = document.getElementById('btnBatalImportHoliday');
                const btnTemplate = importModal.querySelector('a[data-download][data-dl-progress]');
                if (btnBatal) { btnBatal.disabled = true; btnBatal.classList.add('disabled'); }
                if (btnTemplate) { btnTemplate.classList.add('disabled'); btnTemplate.style.pointerEvents = 'none'; btnTemplate.style.opacity = '0.6'; }
            });
            importModal.addEventListener('dl-busy-end', function() {
                const btnBatal = document.getElementById('btnBatalImportHoliday');
                const btnTemplate = importModal.querySelector('a[data-download][data-dl-progress]');
                if (btnBatal) { btnBatal.disabled = false; btnBatal.classList.remove('disabled'); }
                if (btnTemplate) { btnTemplate.classList.remove('disabled'); btnTemplate.style.pointerEvents = ''; btnTemplate.style.opacity = ''; }
            });
        }
    });

    function validateImportForm() {
        const fileInput = document.getElementById('importFileInput');
        const submitBtn = document.getElementById('submitImportBtn');

        if (fileInput.files && fileInput.files.length > 0) {
            submitBtn.removeAttribute('disabled');
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.style.opacity = '0.45';
            submitBtn.style.cursor = 'not-allowed';
        }
    }

    function toggleAddDateRange(isRange) {
        const singleContainer = document.getElementById('addSingleDateContainer');
        const rangeContainer = document.getElementById('addRangeDateContainer');
        const addDate = document.getElementById('addDate');
        const addStartDate = document.getElementById('addStartDate');
        const addEndDate = document.getElementById('addEndDate');

        if (isRange) {
            singleContainer.style.display = 'none';
            rangeContainer.style.display = 'flex';
            addDate.removeAttribute('required');
            addStartDate.setAttribute('required', 'required');
            addEndDate.setAttribute('required', 'required');
            addDate.value = '';
        } else {
            singleContainer.style.display = 'block';
            rangeContainer.style.display = 'none';
            addDate.setAttribute('required', 'required');
            addStartDate.removeAttribute('required');
            addEndDate.removeAttribute('required');
            addStartDate.value = '';
            addEndDate.value = '';
        }
        validateAddForm();
    }

    function toggleEditDateRange(id, isRange) {
        const singleContainer = document.getElementById('editSingleDateContainer' + id);
        const rangeContainer = document.getElementById('editRangeDateContainer' + id);
        const dateInput = singleContainer.querySelector('input');
        const startInput = rangeContainer.querySelectorAll('input')[0];
        const endInput = rangeContainer.querySelectorAll('input')[1];

        if (isRange) {
            singleContainer.style.display = 'none';
            rangeContainer.style.display = 'flex';
            dateInput.removeAttribute('required');
            startInput.setAttribute('required', 'required');
            endInput.setAttribute('required', 'required');
            dateInput.value = '';
        } else {
            singleContainer.style.display = 'block';
            rangeContainer.style.display = 'none';
            dateInput.setAttribute('required', 'required');
            startInput.removeAttribute('required');
            endInput.removeAttribute('required');
            startInput.value = '';
            endInput.value = '';
        }
    }

    function validateAddForm() {
        const isRange = document.getElementById('addTypeRange').checked;
        const desc = document.getElementById('addDescription').value.trim();
        const submitBtn = document.getElementById('submitAddHoliday');

        let isValid = false;
        if (desc.length > 0) {
            if (isRange) {
                const start = document.getElementById('addStartDate').value;
                const end = document.getElementById('addEndDate').value;
                if (start && end) isValid = true;
            } else {
                const date = document.getElementById('addDate').value;
                if (date) isValid = true;
            }
        }

        if (isValid) {
            submitBtn.removeAttribute('disabled');
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.style.opacity = '0.55';
            submitBtn.style.cursor = 'not-allowed';
        }
    }

    document.querySelectorAll('#addHolidayModal input').forEach(input => {
        input.addEventListener('input', validateAddForm);
        input.addEventListener('change', validateAddForm);
    });

    function confirmDeleteHoliday(id, description) {
        if (typeof window.confirmUniversalDelete === 'function') {
            var holidayName = (description || '').toString();
            var htmlContent = '<div style="text-align:center;">'
                + '<div style="font-size:13px; white-space:nowrap;">' + {!! json_encode(__('Tindakan ini permanen. Hapus agenda libur berikut?')) !!} + '</div>'
                + '<div style="margin-top:8px; font-size:14px; font-weight:700; word-break: normal; white-space: normal;"><b class="text-dark">' + holidayName.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</b></div>'
                + '</div>';
            window.confirmUniversalDelete({
                title: {!! json_encode(__('Hapus Hari Libur?')) !!},
                html: htmlContent,
                confirmText: {!! json_encode(__('Hapus')) !!},
                cancelText: {!! json_encode(__('Batal')) !!},
                onConfirm: function () {
                    document.getElementById('deleteHolidayForm-' + id).submit();
                }
            });
        } else {
            if (confirm({!! json_encode(__('Hapus hari libur: ')) !!} + description + '?')) {
                document.getElementById('deleteHolidayForm-' + id).submit();
            }
        }
    }

    function confirmDeleteAllHolidays() {
        if (typeof window.confirmUniversalDelete === 'function') {
            var htmlContent = '<div style="text-align:center;">'
                + '<div>' + {!! json_encode(__('Tindakan ini tidak bisa dibatalkan.')) !!} + '</div>'
                + '<div style="margin-top:8px; word-break: normal; white-space: normal;">' + {!! json_encode(__('Semua data hari libur akan dihapus permanen.')) !!} + '</div>'
                + '</div>';
            window.confirmUniversalDelete({
                title: {!! json_encode(__('Hapus semua data hari libur?')) !!},
                html: htmlContent,
                confirmText: {!! json_encode(__('Hapus Semua')) !!},
                cancelText: {!! json_encode(__('Batal')) !!},
                onConfirm: function () {
                    document.getElementById('deleteAllHolidaysForm').submit();
                }
            });
        } else {
            if (confirm({!! json_encode(__('Hapus semua data hari libur? Tindakan ini tidak bisa dibatalkan.')) !!})) {
                document.getElementById('deleteAllHolidaysForm').submit();
            }
        }
    }
</script>
@endpush