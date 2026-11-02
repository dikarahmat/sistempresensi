@extends('layouts.app')

@section('title', __('Tahun Ajaran'))
@section('page_title', __('Tahun Ajaran'))

@section('page_header_right')
<!-- Tombol Aksi Header Versi Desktop (>= 768px): Diam Total Tanpa Efek Putih/Klik -->
<div class="d-none d-md-block">
    <button type="button" class="btn btn-add-year btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#addYearModal">
        {{ __('Tambah Tahun Ajaran') }}
    </button>
</div>
@endsection

@push('styles')
<style>
    /* KUNCI UTAMA: Tombol Tambah Tahun Ajaran agar Diam Total (Tanpa Efek Putih / Klik / Animasi) */
    .btn-add-year {
        background-color: #3b82f6 !important;
        color: #ffffff !important;
        border: none !important;
        font-weight: 600;
        font-size: 0.88rem;
        border-radius: var(--clean-radius);
        padding: 0.45rem 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 1px 2px rgba(59, 130, 246, 0.15) !important;
        transition: none !important;
        transform: none !important;
    }
    .btn-add-year:hover, 
    .btn-add-year:active, 
    .btn-add-year:focus, 
    .btn-add-year:focus-visible { 
        background-color: #2563eb !important; 
        color: #ffffff !important; 
        transform: none !important; 
        box-shadow: 0 1px 2px rgba(59, 130, 246, 0.15) !important;
        outline: none !important;
    }

    /* Tabel Tahun Ajaran */
    .table-years { width: 100%; min-width: 600px; margin-bottom: 0; }
    .table-years thead th { text-transform: uppercase !important; }
    .table-years tbody td { text-transform: uppercase !important; }

    .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
        scrollbar-width: thin;
    }

    /* Zebra Striping */
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
    .table-years td { vertical-align: middle; }

    .table-years tbody td {
        color: #000000 !important;
        font-weight: 400 !important;
        font-size: 0.85rem;
        padding: 0.85rem 1rem;
    }

    .crud-center-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
    }

    /* Toggle status */
    .status-switch { display: inline-flex; align-items: center; gap: 0.6rem; margin: 0; }
    .status-switch .form-check { margin: 0; padding-left: 2.6em; min-height: 0; }
    .status-switch .form-check-input { width: 2.4em; height: 1.25em; cursor: pointer; margin-top: 0; }
    .status-switch .form-check-input:disabled { cursor: not-allowed; opacity: 0.55; }
    .status-switch .status-text { font-size: 0.78rem; font-weight: 700; }

    .form-check-input:checked {
        background-color: #059669;
        border-color: #059669;
    }

    /* Kotak info berwarna */
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

    .pagination-compact .pagination { margin-bottom: 0; font-size: 0.82rem; }
    .pagination-compact .page-link { padding: 0.25rem 0.6rem; }
</style>
@endpush

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        $tahunBerjalan = (int) date('n') >= 7 ? (int) date('Y') : (int) date('Y') - 1;
        $yearOptions = array_map(function ($y) {
            return $y . '/' . ($y + 1);
        }, range($tahunBerjalan - 1, $tahunBerjalan + 8));
    @endphp

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
    <div class="alert alert-danger alert-deleted alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-trash fs-5 me-2 text-danger'></i>
                <span>{{ session('deleted') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-start">
                <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    <!-- Bantuan singkat -->
    <div class="info-box mb-3">
        <i class='bx bx-info-circle'></i>
        <span>{{ __('Hanya satu tahun ajaran yang bisa aktif. Nyalakan toggle tahun ajaran lain untuk memindahkan status aktif.') }}</span>
    </div>

    <!-- Tombol tambah (mobile) -->
    <div class="d-block d-md-none mb-3">
        <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white">
            <button type="button" class="btn btn-add-year btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center py-1 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#addYearModal">
                <span>{{ __('Tambah Tahun Ajaran') }}</span>
            </button>
        </div>
    </div>

    <!-- Tabel Data Tahun Ajaran -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-years text-nowrap">
                <thead class="bg-light">
                    <tr class="text-secondary small fw-bold align-middle text-uppercase text-nowrap" style="letter-spacing: 0.03em;">
                        <th class="text-center py-3 text-nowrap px-3" style="width: 6%;">NO</th>
                        <th class="text-center py-3 text-nowrap px-3">{{ __('Tahun Ajaran') }}</th>
                        <th class="text-center py-3 text-nowrap px-3">{{ __('Semester') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 d-none d-md-table-cell">{{ __('Periode Tanggal') }}</th>
                        <th class="text-center py-3 text-nowrap px-3">{{ __('Status Aktif') }}</th>
                        <th class="text-center py-3 text-nowrap px-3" style="width: 11%;">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="text-uppercase">
                    @forelse($academicYears as $index => $year)
                    @php
                        $endDate   = \Carbon\Carbon::parse($year->end_date);
                        $locked    = (bool) $year->is_active;
                        $lockTitle = __('Nyalakan tahun ajaran lain untuk memindahkan status aktif');
                    @endphp
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }} align-middle text-nowrap">
                        <td data-label="{{ __('No') }}" class="text-center text-nowrap px-3">{{ $academicYears->firstItem() + $index }}</td>
                        <td data-label="{{ __('Tahun Ajaran') }}" class="text-center text-nowrap px-3">
                            <span>{{ $year->name }}</span>
                        </td>
                        <td data-label="{{ __('Semester') }}" class="text-center text-nowrap px-3">
                            {{ __('Semester') }} {{ $year->semester }}
                        </td>
                        <td data-label="{{ __('Periode Tanggal') }}" class="text-center text-nowrap px-3 d-none d-md-table-cell">
                            {{ \Carbon\Carbon::parse($year->start_date)->translatedFormat('d M Y') }} &mdash;
                            {{ $endDate->translatedFormat('d M Y') }}
                        </td>
                        <td data-label="{{ __('Status Aktif') }}" class="text-center text-nowrap px-3">
                            <form action="{{ panel_route('academic-years.toggle-active', $year->id) }}" method="POST"
                                  id="toggleForm-{{ $year->id }}" class="status-switch" data-no-loader>
                                @csrf
                                <input type="hidden" name="target" value="{{ $year->is_active ? 'off' : 'on' }}">
                                <span class="d-inline-flex" @if($locked) title="{{ $lockTitle }}" @endif>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input year-toggle" type="checkbox" role="switch"
                                               id="toggleActive{{ $year->id }}"
                                               data-form="toggleForm-{{ $year->id }}"
                                               data-name="{{ $year->name }}"
                                               data-semester="{{ $year->semester }}"
                                               data-target="{{ $year->is_active ? 'off' : 'on' }}"
                                               @if($year->is_active) checked @endif
                                               @if($locked) disabled @endif>
                                    </div>
                                </span>
                                <span class="status-text {{ $year->is_active ? 'text-success' : 'text-muted' }}">
                                    {{ $year->is_active ? __('Aktif') : __('Tidak Aktif') }}
                                </span>
                            </form>
                        </td>
                        <td data-label="{{ __('Aksi') }}" class="text-center text-nowrap px-3">
                            <div class="crud-center-wrapper">
                                <button type="button" class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editYearModal{{ $year->id }}" title="{{ __('Edit') }}">
                                    {{ __('Edit') }}
                                </button>

                                @if($year->is_active)
                                    <span class="d-inline-flex" title="{{ __('Tahun ajaran sedang berlangsung, tidak dapat dihapus') }}">
                                        <button type="button" class="btn btn-sm btn-danger disabled" aria-disabled="true">
                                            {{ __('Hapus') }}
                                        </button>
                                    </span>
                                @else
                                    <button type="button" class="btn btn-sm btn-danger btn-delete-year"
                                            data-id="{{ $year->id }}" data-name="{{ $year->name }}" title="{{ __('Hapus') }}">
                                        {{ __('Hapus') }}
                                    </button>
                                @endif
                                <form id="deleteYearForm-{{ $year->id }}" action="{{ panel_route('academic-years.destroy', $year->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="text-nowrap">
                        <td colspan="6" class="text-center py-5 text-muted text-nowrap empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            {{ __('Belum ada data tahun ajaran yang terdaftar.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($academicYears->hasPages())
        <div class="px-4 py-2 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 bg-white small">
            <div class="text-muted small">
                {{ __('Menampilkan') }} <span>{{ $academicYears->firstItem() ?? 0 }}</span> &ndash; <span>{{ $academicYears->lastItem() ?? 0 }}</span> {{ __('dari') }} <span>{{ $academicYears->total() }}</span> {{ __('tahun ajaran') }}
            </div>
            <div class="pagination-compact">
                {{ $academicYears->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>

    <!-- MODAL EDIT (satu per tahun ajaran) -->
    @foreach($academicYears as $year)
    @php
        $editOptions = in_array($year->name, $yearOptions) ? $yearOptions : array_merge([$year->name], $yearOptions);
    @endphp
    <div class="modal fade" id="editYearModal{{ $year->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">{{ __('Edit Tahun Ajaran') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ panel_route('academic-years.update', $year->id) }}" method="POST" class="year-form" data-mode="edit">
                    @csrf
                    @method('PUT')
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Nama Tahun Ajaran') }}</label>
                            <select name="name" class="form-select rounded-3" required>
                                @foreach($editOptions as $option)
                                    <option value="{{ $option }}" {{ $year->name == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Semester') }}</label>
                            <select name="semester" class="form-select rounded-3" required>
                                <option value="Ganjil" {{ $year->semester == 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                                <option value="Genap" {{ $year->semester == 'Genap' ? 'selected' : '' }}>Genap</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">{{ __('Tanggal Mulai') }}</label>
                                <input type="date" name="start_date" class="form-control rounded-3" value="{{ \Carbon\Carbon::parse($year->start_date)->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">{{ __('Tanggal Selesai') }}</label>
                                <input type="date" name="end_date" class="form-control rounded-3" value="{{ \Carbon\Carbon::parse($year->end_date)->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        @if($year->is_active)
                            <input type="hidden" name="is_active" value="1">
                        @endif
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">{{ __('Simpan Perubahan') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach

    <!-- MODAL TAMBAH TAHUN AJARAN -->
    <div class="modal fade" id="addYearModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">{{ __('Tambah Tahun Ajaran Baru') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ panel_route('academic-years.store') }}" method="POST" class="year-form" data-mode="add" id="addYearForm">
                    @csrf
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Nama Tahun Ajaran') }}</label>
                            <select name="name" id="addYearName" class="form-select rounded-3" required onchange="markAddFormInteracted()">
                                @foreach($yearOptions as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Semester') }}</label>
                            <select name="semester" id="addYearSemester" class="form-select rounded-3" required onchange="markAddFormInteracted()">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">{{ __('Tanggal Mulai') }}</label>
                                <input type="date" name="start_date" id="addYearStart" class="form-control rounded-3" required onchange="markAddFormInteracted()">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">{{ __('Tanggal Selesai') }}</label>
                                <input type="date" name="end_date" id="addYearEnd" class="form-control rounded-3" required onchange="markAddFormInteracted()">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                        <button type="submit" id="submitAddYearBtn" class="btn btn-primary rounded-3 px-4 fw-semibold" disabled style="opacity: 0.55; cursor: not-allowed;">{{ __('Simpan') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let hasInteractedAddForm = false;

    function markAddFormInteracted() {
        hasInteractedAddForm = true;
        validateAddYearForm();
    }

    function validateAddYearForm() {
        const submitBtn = document.getElementById('submitAddYearBtn');
        const nameVal = document.getElementById('addYearName').value;
        const semVal = document.getElementById('addYearSemester').value;
        const startVal = document.getElementById('addYearStart').value;
        const endVal = document.getElementById('addYearEnd').value;

        // Tombol hanya aktif jika user sudah berinteraksi/mengubah DAN semua field terisi
        if (hasInteractedAddForm && nameVal && semVal && startVal && endVal) {
            submitBtn.removeAttribute('disabled');
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.style.opacity = '0.55';
            submitBtn.style.cursor = 'not-allowed';
        }
    }

    // Reset status interaksi saat modal ditutup
    document.getElementById('addYearModal').addEventListener('hidden.bs.modal', function () {
        hasInteractedAddForm = false;
        validateAddYearForm();
    });

    (function () {
        /* ---------- Hapus ---------- */
        function confirmDeleteYear(id, name) {
            if (typeof window.confirmUniversalDelete !== 'function') {
                if (window.confirm(name + ' - ' + {!! json_encode(__('Hapus tahun ajaran ini?')) !!})) {
                    document.getElementById('deleteYearForm-' + id).submit();
                }
                return;
            }
            window.confirmUniversalDelete({
                title: {!! json_encode(__('Hapus Tahun Ajaran?')) !!},
                html: {!! json_encode(__('Tindakan ini bersifat permanen. Anda akan menghapus data tahun ajaran')) !!} + ' <b class="text-dark">' + name + '</b> ' + {!! json_encode(__('dari sistem.')) !!},
                confirmText: {!! json_encode(__('Hapus')) !!},
                cancelText: {!! json_encode(__('Tidak')) !!},
                onConfirm: function () {
                    document.getElementById('deleteYearForm-' + id).submit();
                }
            });
        }
        document.querySelectorAll('.btn-delete-year').forEach(function (btn) {
            btn.addEventListener('click', function () {
                confirmDeleteYear(btn.dataset.id, btn.dataset.name);
            });
        });

        /* ---------- Toggle status ---------- */
        var sudahDikirim = false;
        document.querySelectorAll('.year-toggle').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var form = document.getElementById(cb.dataset.form);

                cb.checked = !cb.checked;

                var label = {!! json_encode(__('Tahun ajaran')) !!} + ' ' + cb.dataset.name + ' - ' + cb.dataset.semester;
                var message = label + ' ' + {!! json_encode(__('akan diaktifkan. Tahun ajaran aktif saat ini akan menjadi tidak aktif.')) !!};

                var kirim = function () {
                    if (sudahDikirim) return;
                    sudahDikirim = true;
                    cb.disabled = true;
                    form.submit();
                };

                if (typeof window.confirmUniversalDelete === 'function') {
                    try {
                        window.confirmUniversalDelete({
                            title: {!! json_encode(__('Aktifkan Tahun Ajaran?')) !!},
                            text: message,
                            icon: 'bx-info-circle',
                            tone: 'primary',
                            confirmText: {!! json_encode(__('Ya, Aktifkan')) !!},
                            cancelText: {!! json_encode(__('Batal')) !!},
                            onConfirm: kirim
                        });
                        return;
                    } catch (err) {
                        console.error('Toggle tahun ajaran: dialog gagal tampil, pakai konfirmasi browser.', err);
                    }
                }
                if (window.confirm(message)) kirim();
            });
        });

        /* ---------- Tanggal otomatis mengikuti tahun & semester ---------- */
        function pad(n) { return String(n).padStart(2, '0'); }

        function rentang(nameValue, semester) {
            var y = parseInt(String(nameValue).split('/')[0], 10);
            if (!y) return null;
            return {
                min: y + '-07-01',
                max: (y + 1) + '-06-30',
                start: semester === 'Genap' ? (y + 1) + '-01-01' : y + '-07-01',
                end: semester === 'Genap' ? (y + 1) + '-06-30' : y + '-12-31'
            };
        }

        function bindForm(form, isiAwal) {
            var nameEl = form.querySelector('[name="name"]');
            var semEl = form.querySelector('[name="semester"]');
            var startEl = form.querySelector('[name="start_date"]');
            var endEl = form.querySelector('[name="end_date"]');
            if (!nameEl || !semEl || !startEl || !endEl) return;

            function terapkan(isi) {
                var r = rentang(nameEl.value, semEl.value);
                if (!r) return;
                startEl.min = r.min; startEl.max = r.max;
                endEl.min = r.min; endEl.max = r.max;
                if (isi) { startEl.value = r.start; endEl.value = r.end; }
            }

            nameEl.addEventListener('change', function () { terapkan(true); });
            semEl.addEventListener('change', function () { terapkan(true); });
            startEl.addEventListener('change', function () { if (startEl.value) endEl.min = startEl.value; });

            terapkan(isiAwal);
        }

        document.querySelectorAll('form.year-form').forEach(function (form) {
            var mode = form.dataset.mode;

            if (mode === 'add') {
                var now = new Date();
                var bulan = now.getMonth();            // 0-11
                var y = bulan >= 6 ? now.getFullYear() : now.getFullYear() - 1;
                var nameEl = form.querySelector('[name="name"]');
                var semEl = form.querySelector('[name="semester"]');
                var target = y + '/' + (y + 1);
                for (var i = 0; i < nameEl.options.length; i++) {
                    if (nameEl.options[i].value === target) { nameEl.selectedIndex = i; break; }
                }
                semEl.value = bulan >= 6 ? 'Ganjil' : 'Genap';
                bindForm(form, true);
            } else {
                bindForm(form, false);
            }
        });
    })();
</script>
@endpush