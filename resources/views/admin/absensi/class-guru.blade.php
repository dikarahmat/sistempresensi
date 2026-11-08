@extends('layouts.app')

@section('title', __('Presensi Guru Hari Ini'))
@section('page_title', __('Presensi Guru Hari Ini'))

@php
    /*
     * ===== VARIABEL YANG DIHARAPKAN DARI CONTROLLER =====
     * $teachersList : koleksi guru (urut nama A-Z). Tiap item butuh:
     *                 ->id
     *                 ->name
     *                 ->jam_masuk       (string "HH:mm" atau null)
     *                 ->current_status  ('Hadir' | 'Sakit' | 'Izin' | 'Alfa' | null)
     *                                   null/'Belum' = belum ada catatan hari ini
     * $tanggal      : tanggal presensi (Y-m-d), default hari ini
     * $formAction   : (opsional) URL simpan status. Kalau tidak dikirim, dipakai
     *                 route di bawah. GANTI nama route-nya dengan route simpan
     *                 presensi guru yang SUDAH ADA di proyek.
     * $canEdit      : (opsional) boleh ubah status? default is_admin()
     */
    $teachersList = collect($teachersList ?? []);
    $tanggal      = $tanggal ?? now()->toDateString();
    $canEdit      = $canEdit ?? (function_exists('is_admin') ? is_admin() : true);
    $formAction   = $formAction ?? panel_route('presensi.guru.store'); // <-- sesuaikan

    $validStatus  = ['Hadir', 'Sakit', 'Izin', 'Alfa'];
    $normalize    = function ($s) {
        $s = ucfirst(strtolower((string) $s));
        return $s === 'Alpha' ? 'Alfa' : $s;
    };

    $countTotal  = $teachersList->count();
    $countSudah  = $teachersList->filter(fn ($t) => in_array($normalize($t->current_status ?? ''), $validStatus, true))->count();
    $countBelum  = $countTotal - $countSudah;
@endphp

@push('styles')
<style>
    /* Tabel */
    .table-responsive { -webkit-overflow-scrolling: touch; overflow-x: auto; }
    .table-guru { min-width: 720px; }
    .table-guru thead tr > th {
        background-color: #2563eb !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 0.8rem !important;
        padding: 0.8rem 1rem;
        border-bottom: 1.5px solid #1d4ed8;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap !important;
        vertical-align: middle !important;
        text-align: center !important;
    }
    .table-guru thead tr > th:first-child {
        border-top-left-radius: 0 !important;
        background-color: #2563eb !important;
    }
    .table-guru thead tr > th:last-child  { border-top-right-radius: 8px !important; background-color: #2563eb !important; }
    .table-guru thead tr > th.th-nama { text-align: left !important; padding-left: 0.75rem; }

    .table-guru tbody td {
        padding: 0.7rem 1rem;
        font-size: 0.8rem !important;
        letter-spacing: 0.03em !important;
        color: #111827 !important;
        font-weight: 400 !important;
        text-transform: uppercase;
        vertical-align: middle;
        text-align: center;
        background-color: #ffffff !important;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-guru tbody td.td-nama { text-align: left; padding-left: 0.75rem; }
    .table-guru tbody tr:hover > td { background-color: #e2e8f0 !important; }

    /* Warna huruf status: sama dengan Rekap Presensi (font-weight 700) */
    .num-cell.is-on.hadir { color: #16a34a !important; font-weight: 700 !important; }   /* hijau */
    .num-cell.is-on.sakit { color: #2563eb !important; font-weight: 700 !important; }   /* biru */
    .num-cell.is-on.izin  { color: #7e22ce !important; font-weight: 700 !important; }   /* ungu */
    .num-cell.is-on.alfa  { color: #ef4444 !important; font-weight: 700 !important; }   /* merah */
    .num-cell.is-off      { color: #64748b !important; font-weight: 400 !important; }   /* abu untuk "-" */

    /* Aksi: dropdown + tombol centang */
    .aksi-form {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        flex-wrap: nowrap;
        margin: 0;
        padding: 0;
    }
    .aksi-form select.select-quick-status {
        height: 28px;
        font-size: 11px;
        padding: 0 24px 0 8px;
        min-width: 120px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background-color: #ffffff;
        color: #000000;
        cursor: pointer;
        appearance: none;
        text-transform: uppercase;
        font-weight: 400 !important;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='rgba(115,115,115,0.8)'><path fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/></svg>");
        background-repeat: no-repeat;
        background-position: right 8px center;
        background-size: 10px;
    }
    .aksi-form select.select-quick-status option { font-size: 11px; text-transform: uppercase; }

    .btn-save-quick {
        background: #2563eb;
        color: #ffffff;
        border: none;
        border-radius: 6px;
        width: 28px;
        height: 28px;
        min-width: 28px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.12s ease;
    }
    .btn-save-quick:not(:disabled):hover { background: #1d4ed8; }
    .btn-save-quick:disabled {
        background: #2563eb !important;
        opacity: 0.45 !important;
        cursor: not-allowed !important;
    }
    .btn-save-quick i { font-size: 13px !important; color: #ffffff !important; }
    .btn-save-quick:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }

    /* ===== MOBILE: tabel tidak tertumpuk, digeser horizontal ===== */
    @media (max-width: 767.98px) {
        .table-guru ~ .table-responsive, .table-responsive > .table-guru {
            overflow-x: auto !important;
            overflow-y: visible !important;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }
        .table-guru { min-width: 700px !important; width: auto !important; }
        .table-guru tbody td,
        .table-guru thead th {
            white-space: nowrap !important;
            padding: 0.55rem 0.9rem !important;
            font-size: 0.78rem !important;
        }
        .table-guru tbody td.td-nama {
            white-space: nowrap !important;
            padding-left: 1.25rem;
        }
        .presence-summary { flex-wrap: wrap; gap: 0.6rem; }
        .presence-summary .item { white-space: nowrap; }
    }
</style>
@endpush

@section('content')
<div class="pt-1 space-y-3">

    {{-- NOTIFIKASI --}}
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

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert" data-flash-persist>
        <div class="flash-notice-body">
            <div class="d-flex align-items-start">
                <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @php
        $isHolidayGuru = $isHoliday ?? false;
        $holidayDescGuru = $holidayDescription ?? '';
        if (!$isHolidayGuru && $tanggal) {
            $selectedDateObj = \Carbon\Carbon::parse($tanggal);
            $isWeekend = $selectedDateObj->isWeekend();
            if ($isWeekend || \App\Models\Holiday::isHoliday($tanggal)) {
                $isHolidayGuru = true;
                $holidayDescGuru = \App\Models\Holiday::getHolidayDescription($tanggal) ?? ($isWeekend ? 'Akhir Pekan (Sabtu/Minggu)' : null);
            }
        }
    @endphp

    @include('components.holiday-banner', [
        'isHoliday' => $isHolidayGuru,
        'holidayDescription' => $holidayDescGuru,
    ])

    {{-- RINGKASAN --}}
    @include('components.presence-summary', [
        'sudah' => $countSudah,
        'belum' => $countBelum,
        'total' => $countTotal,
        'label' => 'GURU',
    ])

    {{-- TABEL --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-guru">
                <thead>
                    <tr>
                        <th>{{ __('No') }}</th>
                        <th class="th-nama">{{ __('Nama Guru') }}</th>
                        <th>{{ __('Jam Masuk') }}</th>
                        <th>{{ __('Hadir') }}</th>
                        <th>{{ __('Sakit') }}</th>
                        <th>{{ __('Izin') }}</th>
                        <th>{{ __('Alfa') }}</th>
                        @if($canEdit)
                        <th>{{ __('Aksi') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachersList as $teacher)
                    @php
                        $st       = $normalize($teacher->current_status ?? '');
                        $recorded = in_array($st, $validStatus, true);
                        $val = [
                            'hadir' => $st === 'Hadir' ? 'H' : '-',
                            'sakit' => $st === 'Sakit' ? 'S' : '-',
                            'izin'  => $st === 'Izin'  ? 'I' : '-',
                            'alfa'  => $st === 'Alfa' ? 'A' : '-',
                        ];
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="td-nama">{{ $teacher->name ?? '-' }}</td>
                        <td>{{ $teacher->jam_masuk ?: '—' }}</td>
                        <td class="text-center">
                            <span class="num-cell fw-bold {{ $val['hadir'] === 'H' ? 'is-on hadir' : 'is-off' }}">{{ $val['hadir'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="num-cell fw-bold {{ $val['sakit'] === 'S' ? 'is-on sakit' : 'is-off' }}">{{ $val['sakit'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="num-cell fw-bold {{ $val['izin'] === 'I' ? 'is-on izin' : 'is-off' }}">{{ $val['izin'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="num-cell fw-bold {{ $val['alfa'] === 'A' ? 'is-on alfa' : 'is-off' }}">{{ $val['alfa'] }}</span>
                        </td>
                        @if($canEdit)
                        <td>
                            <form action="{{ $formAction }}" method="POST" class="aksi-form">
                                @csrf
                                <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                                <input type="hidden" name="date" value="{{ $tanggal }}">

                                <select name="status" class="select-quick-status" data-status-select
                                        aria-label="{{ __('Pilih status presensi') }} {{ $teacher->name ?? '' }}" {{ $isHolidayGuru ? 'disabled' : '' }}>
                                    <option value="" {{ $recorded ? '' : 'selected' }}>{{ __('Pilih Status') }}</option>
                                    @foreach($validStatus as $opt)
                                    <option value="{{ $opt }}" {{ $st === $opt ? 'selected' : '' }}>{{ __($opt) }}</option>
                                    @endforeach
                                </select>

                                <button type="submit" class="btn-save-quick" data-save-btn
                                        title="{{ __('Simpan Status') }}"
                                        aria-label="{{ __('Simpan status presensi') }} {{ $teacher->name ?? '' }}"
                                        {{ $isHolidayGuru ? 'disabled' : ($recorded ? '' : 'disabled') }}>
                                    <i class='bx bx-check'></i>
                                </button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canEdit ? 8 : 7 }}" class="text-center py-4 text-secondary">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            {{ __('BELUM ADA DATA GURU YANG TERDAFTAR.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Tombol simpan nonaktif selama dropdown masih "PILIH STATUS".
    function syncSaveBtn(sel) {
        var f = sel.closest('form');
        var b = f ? f.querySelector('[data-save-btn]') : null;
        if (b) b.disabled = (sel.value === '');
    }

    document.querySelectorAll('select[data-status-select]').forEach(syncSaveBtn);

    document.addEventListener('change', function (e) {
        var s = e.target.closest('select[data-status-select]');
        if (s) syncSaveBtn(s);
    });

    // Antisipasi restore form dari tombol back/forward browser.
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('select[data-status-select]').forEach(syncSaveBtn);
    });

    // Cegah submit kalau status masih kosong.
    document.addEventListener('submit', function (e) {
        var sel = e.target.querySelector ? e.target.querySelector('select[data-status-select]') : null;
        if (sel && sel.value === '') {
            e.preventDefault();
            sel.focus();
        }
    });
</script>
@endpush