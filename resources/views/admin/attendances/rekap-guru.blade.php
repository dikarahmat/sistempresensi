@extends('layouts.app')

@section('title', __('Rekap Presensi Guru'))
@section('page_title', 'REKAP PRESENSI GURU')

@section('canvas_class', 'page-canvas-fixed')

@section('page_header_right')
    <a href="{{ route('admin.rekap') }}"
       class="btn btn-primary btn-sm"
       style="transition: none !important;"
       title="{{ __('Mode Siswa') }}"
       aria-label="{{ __('Mode Siswa') }}">
        {{ __('Mode Siswa') }}
    </a>
@endsection

@push('styles')
<style>
    .btn-green-excel, .btn-red-pdf {
        display: inline-flex; align-items: center; justify-content: center;
        height: 38px; padding: 0 1rem; border: none; border-radius: 8px;
        color: #ffffff !important; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.02em;
        text-decoration: none; white-space: nowrap; cursor: pointer;
        transition: none !important; transform: none !important;
        -webkit-tap-highlight-color: transparent;
    }
    .btn-green-excel { background-color: #059669; }
    .btn-red-pdf { background-color: #ef4444; }
    .btn-green-excel:hover, .btn-green-excel:active, .btn-green-excel:focus, .btn-green-excel:focus-visible { background-color: #059669 !important; color: #ffffff !important; box-shadow: none !important; outline: none; }
    .btn-red-pdf:hover, .btn-red-pdf:active, .btn-red-pdf:focus, .btn-red-pdf:focus-visible { background-color: #ef4444 !important; color: #ffffff !important; box-shadow: none !important; outline: none; }

    /* Period Switcher Tabs */
    .period-container { display: flex; align-items: center; justify-content: flex-start; margin-bottom: 0.75rem; }
    .period-nav { display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: 10px; gap: 3px; }
    .period-link {
        display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.42rem 0.9rem;
        font-size: 0.82rem; font-weight: 500; color: #64748b; text-decoration: none;
        border-radius: 8px; background-color: transparent; transition: background-color 0.15s ease, color 0.15s ease;
        -webkit-tap-highlight-color: transparent;
    }
    @media (hover: hover) { .period-link:hover { color: #1e293b; background-color: #e2e8f0; } }
    .period-link:active { background-color: #cbd5e1; }
    .period-link.active {
        background-color: #2563eb !important; color: #ffffff !important; font-weight: 600;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }
    .period-link.active:hover, .period-link.active:active, .period-link.active:focus, .period-link.active:focus-visible {
        background-color: #2563eb !important; color: #ffffff !important;
    }
    .period-link:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }

    @media (max-width: 575.98px) {
        .period-container { width: 100%; }
        .period-nav { width: 100%; display: flex; }
        .period-link { flex: 1; justify-content: center; padding: 0.45rem 0.35rem; font-size: 0.8rem; text-align: center; }
    }

    .filter-card { background: transparent !important; border: none !important; margin-bottom: 1.25rem !important; }
    .filter-card > form { gap: 0.625rem !important; align-items: flex-end !important; }
    .filter-label { font-size: 0.74rem; font-weight: 600; color: #64748b; margin-bottom: 0.25rem; display: block; }
    .filter-input {
        height: 38px; font-size: 0.82rem; border-radius: var(--clean-radius); border-color: #cbd5e1;
        background-color: #ffffff; padding: 0.35rem 0.65rem; border: 1px solid #cbd5e1;
    }
    .filter-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15); }

    @media (max-width: 767.98px) {
        .filter-card > form > div { width: 100% !important; }
        .filter-card .rg-actions { width: 100%; margin-left: 0 !important; }
        .filter-card .rg-actions > a { flex: 1; }
    }

    /* Tables */
    .table-responsive { -webkit-overflow-scrolling: touch; overflow-x: auto; width: 100%; }
    .table-matrix { margin-bottom: 0; width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .table-matrix thead th {
        background-color: #f8fafc; color: #475569; font-weight: 600; padding: 0.75rem 0.5rem;
        border-bottom: 1.5px solid #edf2f7; text-align: center; vertical-align: middle; white-space: nowrap;
        text-transform: uppercase; letter-spacing: 0.03em; font-family: 'Poppins', 'Roboto', sans-serif;
    }
    .table-matrix thead th.th-holiday { background-color: #fee2e2 !important; color: #dc2626 !important; }
    .table-matrix tbody tr { border-bottom: 1px solid #f1f5f9; }
    .table-matrix tbody tr:nth-child(even) > td, .table-matrix tbody tr.baris-abu > td { background-color: #f1f5f9 !important; }
    .table-matrix tbody tr:nth-child(odd) > td, .table-matrix tbody tr.baris-putih > td { background-color: #ffffff !important; }
    .table-matrix tbody tr:hover > td { background-color: #e2e8f0 !important; }
    .table-matrix tbody td {
        padding: 0.7rem 0.5rem; text-align: center; vertical-align: middle; border-right: 1px solid #f1f5f9;
        color: #1e293b; white-space: nowrap; text-transform: uppercase; letter-spacing: 0.03em;
        font-family: 'Poppins', 'Roboto', sans-serif; font-weight: 400; overflow: hidden; text-overflow: ellipsis;
    }
    .table-matrix th:nth-child(3), .table-matrix td:nth-child(3) { text-align: left !important; padding-left: 0.75rem; }
    .table-matrix .col-sum { width: 36px; }
    .table-matrix .col-pct { width: 64px; }
    .table-matrix thead th.th-sum, .table-matrix tbody td.td-sum { padding-left: 0.15rem; padding-right: 0.15rem; text-align: center; font-weight: 700; }
    .table-matrix tbody td.td-pct { text-align: center; font-weight: 700; font-size: 0.95rem; color: #212529; }

    /* Status Colors */
    .cell-present { color: #16a34a !important; font-weight: 700; }
    .cell-late { color: #d97706 !important; font-weight: 700; }
    .cell-sick { color: #2563eb !important; font-weight: 700; }
    .cell-permission { color: #7e22ce !important; font-weight: 700; }
    .cell-alpha { color: #ef4444 !important; font-weight: 700; }
    .cell-holiday { color: #64748b !important; font-weight: 600; }
    .cell-future { color: #cbd5e1 !important; font-weight: 400; }

    .rekap-legend {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.3rem 0.5rem;
        padding: 0.4rem 0.6rem; border: 1px dashed #e2e8f0; border-radius: 10px;
        background-color: #f8fafc; color: #64748b; font-size: 0.78rem;
    }
    .rekap-legend-label { font-weight: 700; color: #475569; text-transform: uppercase; font-size: 0.7rem; }
    .rekap-legend-items { display: flex; flex-wrap: wrap; align-items: center; gap: 0.3rem 0.6rem; }
    .rekap-legend-item { display: inline-flex; align-items: center; gap: 0.2rem; white-space: nowrap; font-size: 0.82rem; }
    .rekap-legend-item strong { font-size: 0.9rem; font-weight: 700; }

    .rekap-jk { font-size: 0.82rem; font-weight: 500; line-height: 1.2; }
    .rekap-jk.is-laki { color: #3B82F6 !important; font-weight: 500 !important; }
    .rekap-jk.is-perempuan { color: #EF4444 !important; font-weight: 500 !important; }

    #daftar-rekap { display: block; height: auto; min-height: 0; margin-bottom: 0 !important; }
    #daftar-rekap .table-responsive { max-height: none !important; overflow-y: visible !important; }
</style>
@endpush

@section('content')
@php
    $type        = $type ?? 'harian';
    $dataRows    = $dataRows ?? [];
    $dateColumns = $dateColumns ?? [];
    $daysInMonth = $daysInMonth ?? count($dateColumns);
    $holidayMap  = $holidayMap ?? [];
    $isHoliday   = $isHoliday ?? false;
    $holidayDesc = $holidayDesc ?? null;
    $exportParams = request()->query();
    $exportParams['type'] = $type;
@endphp

<div class="period-container">
    <div class="period-nav">
        <a href="{{ route('admin.rekap.guru', array_merge(request()->except(['type', 'period', 'page']), ['type' => 'harian'])) }}" class="period-link {{ $type === 'harian' ? 'active' : '' }}">
            <i class='bx bx-calendar-event'></i> <span>{{ __('Harian') }}</span>
        </a>
        <a href="{{ route('admin.rekap.guru', array_merge(request()->except(['type', 'period', 'page']), ['type' => 'mingguan'])) }}" class="period-link {{ $type === 'mingguan' ? 'active' : '' }}">
            <i class='bx bx-calendar-week'></i> <span>{{ __('Mingguan') }}</span>
        </a>
        <a href="{{ route('admin.rekap.guru', array_merge(request()->except(['type', 'period', 'page']), ['type' => 'bulanan'])) }}" class="period-link {{ $type === 'bulanan' ? 'active' : '' }}">
            <i class='bx bx-calendar'></i> <span>{{ __('Bulanan') }}</span>
        </a>
    </div>
</div>

<div class="filter-card">
    <form method="GET" action="{{ route('admin.rekap.guru') }}" class="d-flex flex-wrap align-items-end gap-2" id="rekapGuruFilterForm">
        <input type="hidden" name="type" value="{{ $type }}">
        @if ($type === 'harian')
            <div class="d-flex flex-column" style="width: 180px;">
                <label class="filter-label">{{ __('Tanggal') }}</label>
                <input type="date" name="date" id="rekapDate" onchange="this.form.submit()" class="form-control form-control-sm filter-input" value="{{ $date }}">
            </div>
        @elseif ($type === 'mingguan')
            <div class="d-flex flex-column" style="width: 160px;">
                <label class="filter-label">{{ __('Tanggal Mulai') }}</label>
                <input type="date" name="start_date" id="rekapStartDate" onchange="this.form.submit()" class="form-control form-control-sm filter-input" value="{{ $startDate }}">
            </div>
            <div class="d-flex flex-column" style="width: 160px;">
                <label class="filter-label">{{ __('Tanggal Selesai') }}</label>
                <input type="date" name="end_date" id="rekapEndDate" onchange="this.form.submit()" class="form-control form-control-sm filter-input" value="{{ $endDate }}">
            </div>
        @else
            <div class="d-flex flex-column" style="width: 140px;">
                <label class="filter-label">{{ __('Bulan') }}</label>
                <select name="month" id="rekapMonth" onchange="this.form.submit()" class="form-select form-select-sm filter-input">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate((int) $year, $m, 1)->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="d-flex flex-column" style="width: 100px;">
                <label class="filter-label">{{ __('Tahun') }}</label>
                <select name="year" id="rekapYear" onchange="this.form.submit()" class="form-select form-select-sm filter-input">
                    @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
        @endif
        <div class="d-flex align-items-center gap-2 ms-auto rg-actions">
            <a href="{{ route('admin.rekap.guru.pdf', $exportParams) }}" class="btn-red-pdf" title="{{ __('Cetak PDF') }}" aria-label="{{ __('Cetak PDF') }}"><span>{{ __('CETAK PDF') }}</span></a>
            <a href="{{ route('admin.rekap.guru.excel', $exportParams) }}" class="btn-green-excel" title="{{ __('Cetak Excel') }}" aria-label="{{ __('Cetak Excel') }}"><span>{{ __('CETAK EXCEL') }}</span></a>
        </div>
    </form>
</div>

@if ($type === 'harian' && $isHoliday)
    <div class="alert alert-warning border-0 rounded-4 p-3 mb-3 d-flex align-items-center gap-3 shadow-xs">
        <i class='bx bx-calendar-exclamation fs-3 text-warning'></i>
        <div>
            <h6 class="fw-bold mb-0">{{ __('Hari Ini Merupakan Hari Libur:') }} {{ $holidayDesc }}</h6>
            <small class="text-muted">{{ __('Presensi tidak diwajibkan untuk tanggal ini.') }}</small>
        </div>
    </div>
@endif

@if ($type !== 'harian')
    <div class="rekap-legend mb-3">
        <span class="rekap-legend-label">{{ __('Keterangan') }}:</span>
        <div class="rekap-legend-items">
            <span class="rekap-legend-item"><strong class="text-success">H</strong> {{ __('Hadir') }}</span>
            <span class="rekap-legend-item"><strong style="color: #d97706;">T</strong> {{ __('Terlambat') }}</span>
            <span class="rekap-legend-item"><strong class="text-primary">S</strong> {{ __('Sakit') }}</span>
            <span class="rekap-legend-item"><strong style="color: #7e22ce;">I</strong> {{ __('Izin') }}</span>
            <span class="rekap-legend-item"><strong style="color: #ef4444;">A</strong> {{ __('Alfa') }}</span>
            <span class="rekap-legend-item"><strong style="color: #64748b;">L</strong> {{ __('Libur') }}</span>
            <span class="rekap-legend-item"><strong style="color: #94a3b8;">-</strong> {{ __('Belum') }}</span>
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-4 overflow-hidden md:mb-4" id="daftar-rekap">
    <div class="table-responsive">
        @if ($type === 'harian')
            <table class="table table-hover align-middle mb-0 table-matrix text-nowrap" style="width: 100%;">
                <thead>
                    <tr class="align-middle">
                        <th style="width: 50px;">{{ __('No') }}</th>
                        <th style="width: 170px;">{{ __('NIP') }}</th>
                        <th class="text-start">{{ __('Nama Guru') }}</th>
                        <th style="width: 60px;">{{ __('JK') }}</th>
                        <th style="width: 120px;">{{ __('Jam Masuk') }}</th>
                        <th style="width: 160px;">{{ __('Keterlambatan') }}</th>
                        <th style="width: 120px;">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dataRows as $item)
                        @php
                            $status   = $item['status'] ?? '-';
                            $lateText = $item['late_text'] ?? '-';
                        @endphp
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                            <td class="text-center text-secondary">{{ $loop->iteration }}</td>
                            <td>{{ $item['nip'] ?: '-' }}</td>
                            <td class="text-start" title="{{ $item['name'] }}">{{ $item['name'] }}</td>
                            <td class="text-center">
                                <span class="rekap-jk {{ ($item['jk'] ?? 'L') === 'P' ? 'is-perempuan' : 'is-laki' }}">{{ $item['jk'] ?? 'L' }}</span>
                            </td>
                            <td class="text-center">{{ $item['check_in'] ?? '-' }}</td>
                            <td class="text-center">
                                @if ($lateText === 'Tepat Waktu')
                                    <span class="text-success small">{{ __('Tepat Waktu') }}</span>
                                @elseif ($status === 'Terlambat')
                                    <span class="text-warning-emphasis small fw-semibold">{{ $lateText }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($status === 'Hadir') <span class="cell-present">{{ __('Hadir') }}</span>
                                @elseif ($status === 'Terlambat') <span class="cell-late">{{ __('Terlambat') }}</span>
                                @elseif ($status === 'Sakit') <span class="cell-sick">{{ __('Sakit') }}</span>
                                @elseif ($status === 'Izin') <span class="cell-permission">{{ __('Izin') }}</span>
                                @elseif ($status === 'Alfa') <span class="cell-alpha">{{ __('Alfa') }}</span>
                                @elseif ($status === 'Libur') <span class="cell-holiday">{{ __('Libur') }}</span>
                                @else <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary empty-state">
                                <i class='bx bx-error' aria-hidden='true'></i>
                                {{ __('Tidak ada data guru atau presensi untuk tanggal yang dipilih.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <table class="table table-hover align-middle mb-0 table-matrix text-nowrap" style="{{ $type === 'bulanan' ? 'min-width: 1400px;' : '' }}">
                <colgroup>
                    <col style="width: 45px;">
                    <col style="width: 150px;">
                    <col style="width: 240px;">
                    <col style="width: 48px;">
                    <col style="width: auto;" span="{{ count($dateColumns) }}">
                    <col class="col-sum" span="5">
                    <col class="col-pct">
                </colgroup>
                <thead>
                    <tr class="align-middle">
                        <th>{{ __('No') }}</th>
                        <th>{{ __('NIP') }}</th>
                        <th class="text-start">{{ __('Nama Guru') }}</th>
                        <th>{{ __('JK') }}</th>
                        @foreach ($dateColumns as $col)
                            <th class="{{ $col['is_holiday'] ? 'th-holiday' : '' }}">{{ $col['label'] }}</th>
                        @endforeach
                        <th class="th-sum" style="color: #198754;">H</th>
                        <th class="th-sum" style="color: #d97706;">T</th>
                        <th class="th-sum" style="color: #0d6efd;">S</th>
                        <th class="th-sum" style="color: #7e22ce;">I</th>
                        <th class="th-sum" style="color: #ef4444;">A</th>
                        <th class="th-sum" style="color: #212529; font-size: 0.95rem;">%</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dataRows as $item)
                        @php $days = $item['days'] ?? []; @endphp
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                            <td class="text-center text-secondary">{{ $loop->iteration }}</td>
                            <td>{{ $item['nip'] ?: '-' }}</td>
                            <td class="text-start" title="{{ $item['name'] }}">{{ $item['name'] }}</td>
                            <td class="text-center">
                                <span class="rekap-jk {{ ($item['jk'] ?? 'L') === 'P' ? 'is-perempuan' : 'is-laki' }}">{{ $item['jk'] ?? 'L' }}</span>
                            </td>
                            @foreach ($dateColumns as $col)
                                @php $code = $days[$col['date']] ?? '-'; @endphp
                                <td>
                                    @if ($code === 'H') <span class="cell-present">H</span>
                                    @elseif ($code === 'T') <span class="cell-late">T</span>
                                    @elseif ($code === 'S') <span class="cell-sick">S</span>
                                    @elseif ($code === 'I') <span class="cell-permission">I</span>
                                    @elseif ($code === 'A') <span class="cell-alpha">A</span>
                                    @elseif ($code === 'L') <span class="cell-holiday">L</span>
                                    @else <span class="cell-future">-</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="td-sum" style="color: #198754;">{{ $item['hadir'] ?? 0 }}</td>
                            <td class="td-sum" style="color: #d97706;">{{ $item['terlambat'] ?? 0 }}</td>
                            <td class="td-sum" style="color: #0d6efd;">{{ $item['sakit'] ?? 0 }}</td>
                            <td class="td-sum" style="color: #7e22ce;">{{ $item['izin'] ?? 0 }}</td>
                            <td class="td-sum" style="color: #ef4444;">{{ $item['alfa'] ?? 0 }}</td>
                            <td class="td-pct">{{ $item['percentage'] ?? 0 }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + count($dateColumns) + 6 }}" class="text-center py-5 text-secondary empty-state">
                                <i class='bx bx-error' aria-hidden='true'></i>
                                {{ __('Tidak ada data guru untuk rentang tanggal yang dipilih.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection