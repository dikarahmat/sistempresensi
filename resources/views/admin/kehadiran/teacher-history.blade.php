@extends('layouts.app')

@section('title', __('Riwayat Kehadiran Guru'))
@section('page_title', __('Riwayat Kehadiran Guru'))

@push('styles')
<style>
    .stat-summary {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: stretch;
        gap: 0;
        padding: 0.35rem 0;
        margin-bottom: 0.75rem;
    }
    .stat-item {
        text-align: center;
        flex: 1 1 0;
        min-width: 0;
    }
    .stat-value {
        font-size: 1.1rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .stat-label {
        font-size: 0.65rem;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        line-height: 1.2;
        margin-top: 0.125rem;
    }

    .period-nav {
        display: flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 8px;
        gap: 3px;
        width: 100%;
    }
    .period-link {
        flex: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.4rem 0.75rem;
        font-size: 0.85rem;
        font-weight: 400;
        color: #64748b;
        text-decoration: none;
        border-radius: 6px;
        background-color: transparent;
        transition: background-color 0.15s ease, color 0.15s ease;
        text-transform: uppercase;
        -webkit-tap-highlight-color: transparent;
    }
    @media (hover: hover) {
        .period-link:hover { color: #1e293b; background-color: #e2e8f0; }
    }
    .period-link:active { background-color: #cbd5e1; }
    .period-link.active,
    .period-link.active:hover,
    .period-link.active:active,
    .period-link.active:focus,
    .period-link.active:focus-visible {
        background-color: #2563eb !important;
        color: #ffffff !important;
        font-weight: 400;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }
    .period-link:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }

    @media (max-width: 575.98px) {
        .period-link {
            flex: 0 1 auto;
            padding: 0.4rem 0.65rem;
            font-size: 0.78rem;
        }
    }

    .date-filter-group {
        display: flex;
        align-items: stretch;
        gap: 0.5rem;
        width: 100%;
    }
    .date-filter-group input[type="date"],
    .date-filter-group input[type="month"] {
        flex: 1;
        min-width: 0;
        padding: 0.35rem 0.65rem;
        font-size: 0.9rem;
        height: 38px;
        cursor: pointer;
    }
    .date-filter-group .btn {
        flex: 0 0 auto;
        white-space: nowrap;
        padding: 0.35rem 1rem;
        font-size: 0.85rem;
        height: 38px;
        cursor: pointer;
    }
    .date-filter-group .btn.btn-icon-square {
        width: 38px;
        min-width: 38px;
        height: 38px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .date-filter-group .btn.btn-icon-square i {
        font-size: 1.15rem;
        line-height: 1;
    }

    /* ===== TABEL CLEAN ===== */
    .table-responsive .table-history,
    .table-history {
        width: 100% !important;
        border-collapse: collapse !important;
        table-layout: auto;
    }
    .table-history thead tr > th {
        background: transparent !important;
        background-image: none !important;
        color: #111827 !important;
        font-weight: 400 !important;
        padding: 8px 6px !important;
        border: 0 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap !important;
        text-transform: uppercase;
        font-size: 11px !important;
        line-height: 1.2 !important;
    }
    .tabel-history-wrap {
        border-radius: 8px !important;
        overflow: auto !important;
    }
    .table-history tbody td {
        padding: 8px 6px !important;
        text-align: center !important;
        vertical-align: middle !important;
        color: #111827 !important;
        font-weight: 400 !important;
        font-size: 11px !important;
        white-space: nowrap;
        border: 0 !important;
        border-bottom: 1px solid #f1f5f9 !important;
    }
    .table-history tbody td.text-left { text-align: left !important; }
    .badge-h { color: #059669 !important; font-weight: 400 !important; }
    .badge-t { color: #d97706 !important; font-weight: 400 !important; }
    .badge-s { color: #2563eb !important; font-weight: 400 !important; }
    .badge-i { color: #7e22ce !important; font-weight: 400 !important; }
    .badge-a { color: #dc2626 !important; font-weight: 400 !important; }

    .table-history tbody tr > td { background-color: #ffffff !important; }
    .table-history tbody tr:hover > td { background-color: #f1f5f9 !important; }
</style>
@endpush

@section('content')
    <div class="mb-2">
        <h5 class="mb-1" style="color: #0f172a; text-transform: uppercase; font-weight: 400;">{{ $teacher->name }}</h5>
        <p class="text-secondary mb-0" style="font-size: 0.85rem; text-transform: uppercase;">
            {{ __('NIP') }}: {{ $teacher->nip ?: '-' }}
        </p>
    </div>

    <div class="mb-3">
        <form method="GET" action="{{ panel_route('kehadiran.guru-history', $teacher->id) }}">
            <input type="hidden" name="period" value="{{ $period }}">

            <div class="stat-summary">
                <div class="stat-item">
                    <div class="stat-value" style="color: #16a34a;">{{ $stats['hadir'] }}</div>
                    <div class="stat-label" style="color: #16a34a;">{{ __('Hadir') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #d97706;">{{ $stats['terlambat'] }}</div>
                    <div class="stat-label" style="color: #d97706;">{{ __('Terlambat') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #2563eb;">{{ $stats['sakit'] }}</div>
                    <div class="stat-label" style="color: #2563eb;">{{ __('Sakit') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #7e22ce;">{{ $stats['izin'] }}</div>
                    <div class="stat-label" style="color: #7e22ce;">{{ __('Izin') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #ef4444;">{{ $stats['alfa'] }}</div>
                    <div class="stat-label" style="color: #ef4444;">{{ __('Alfa') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-dark">{{ $attendanceRate }}%</div>
                    <div class="stat-label">{{ __('Kehadiran') }}</div>
                </div>
            </div>

            <div class="period-nav mb-2">
                <a href="{{ panel_route('kehadiran.guru-history', [$teacher->id, 'period' => 'harian', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'harian' ? 'active' : '' }}" data-period="harian">
                    {{ __('Harian') }}
                </a>
                <a href="{{ panel_route('kehadiran.guru-history', [$teacher->id, 'period' => 'mingguan', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'mingguan' ? 'active' : '' }}" data-period="mingguan">
                    {{ __('Mingguan') }}
                </a>
                <a href="{{ panel_route('kehadiran.guru-history', [$teacher->id, 'period' => 'bulanan', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'bulanan' ? 'active' : '' }}" data-period="bulanan">
                    {{ __('Bulanan') }}
                </a>
                <a href="{{ panel_route('kehadiran.guru-history', [$teacher->id, 'period' => 'semua', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'semua' ? 'active' : '' }}" data-period="semua">
                    {{ __('Semua') }}
                </a>
            </div>

            <div class="date-filter-group">
                @if($period === 'harian')
                    <input type="date" name="tanggal" id="guruHistoryDate" class="form-control" value="{{ $anchor->toDateString() }}" aria-label="{{ __('Pilih tanggal') }}">
                @elseif($period === 'mingguan')
                     <input type="date" name="start_date" id="startDate" class="form-control" value="{{ request('start_date', $anchor->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString()) }}" aria-label="{{ __('Tanggal awal mingguan') }}">
                    <input type="date" name="end_date" id="endDate" class="form-control" value="{{ request('end_date', $anchor->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString()) }}" aria-label="{{ __('Tanggal akhir mingguan') }}">
                @elseif($period === 'bulanan')
                    <input type="month" name="tanggal" id="guruHistoryDate" class="form-control" value="{{ $anchor->format('Y-m') }}" aria-label="{{ __('Pilih bulan') }}">
                @else
                    <input type="date" name="tanggal" id="guruHistoryDate" class="form-control" value="{{ $anchor->toDateString() }}" aria-label="{{ __('Pilih tanggal') }}">
                @endif
                <button type="button" class="btn btn-success btn-icon-square" id="btnTampilkan" title="{{ __('Tampilkan') }}" aria-label="{{ __('Tampilkan') }}">
                    <i class="bx bx-search"></i>
                </button>
                <button type="button" class="btn btn-danger" id="printPeriodBtn">{{ __('Cetak PDF') }}</button>
            </div>
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive tabel-history-wrap">
            <table class="table-history" id="tableHistory">
                <thead>
                    <tr>
                        <th style="width: 4%;">{{ __('No') }}</th>
                        <th style="width: 16%;">{{ __('Tanggal') }}</th>
                        <th style="width: 12%;">{{ __('Hari') }}</th>
                        <th style="width: 16%;">{{ __('Status') }}</th>
                        <th style="width: 16%;">{{ __('Keterangan') }}</th>
                        <th style="width: 14%;">{{ __('Jam Masuk') }}</th>
                        <th style="width: 22%;">{{ __('Catatan') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td style="text-transform: uppercase;">{{ $row['no'] }}</td>
                            <td style="text-transform: uppercase;">{{ $row['date'] }}</td>
                            <td style="text-transform: uppercase;">{{ $row['day'] }}</td>
                            <td style="color: {{ $row['status_color'] }} !important; text-transform: uppercase;">{{ $row['status'] }}</td>
                            <td class="text-left" style="text-transform: uppercase;">{{ $row['keterangan'] }}</td>
                            <td style="text-transform: uppercase;">{{ $row['check_in'] }}</td>
                            <td class="text-left" style="text-transform: uppercase;">{{ $row['notes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    var teacherId = {{ $teacher->id }};
    var baseUrl = window.location.origin + window.location.pathname;
    var csrfToken = {!! json_encode(csrf_token()) !!};

    function getActivePeriod() {
        var link = document.querySelector('.period-link.active');
        return link && link.dataset ? (link.dataset.period || '{{ $period }}') : '{{ $period }}';
    }

    var btnTampilkan = document.getElementById('btnTampilkan');
    if (btnTampilkan) {
        btnTampilkan.addEventListener('click', function(e) {
            e.preventDefault();
            var period = getActivePeriod();
            var url = new URL(baseUrl);
            url.searchParams.set('period', period);

            if (period === 'harian' || period === 'bulanan') {
                var tanggal = document.getElementById('guruHistoryDate').value;
                url.searchParams.set('tanggal', tanggal);
            } else if (period === 'mingguan') {
                var startDate = document.getElementById('startDate').value;
                var endDate = document.getElementById('endDate').value;
                if (startDate) url.searchParams.set('start_date', startDate);
                if (endDate)   url.searchParams.set('end_date', endDate);
            } else if (period === 'semua') {
                url.searchParams.set('tanggal', document.getElementById('guruHistoryDate').value);
            }
            window.location.href = url.toString();
        });
    }

    var printBtn = document.getElementById('printPeriodBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();

            var period = getActivePeriod();
            var tanggal = '';
            var startDate = '';
            var endDate = '';

            if (period === 'harian' || period === 'bulanan') {
                tanggal = document.getElementById('guruHistoryDate').value || '{{ $anchor->toDateString() }}';
            } else if (period === 'mingguan') {
                startDate = document.getElementById('startDate').value || '{{ $anchor->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString() }}';
                endDate = document.getElementById('endDate').value || '{{ $anchor->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString() }}';
            } else if (period === 'semua') {
                tanggal = document.getElementById('guruHistoryDate').value || '{{ $anchor->toDateString() }}';
            }

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = {!! json_encode(panel_route('kehadiran.guru-history.pdf', $teacher->id)) !!};
            form.style.display = 'none';

            var params = {
                '_token': csrfToken,
                'period': period,
                'tanggal': tanggal,
                'start_date': startDate,
                'end_date': endDate,
            };

            for (var key in params) {
                if (params.hasOwnProperty(key)) {
                    var hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = key;
                    hiddenField.value = params[key];
                    form.appendChild(hiddenField);
                }
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });
    }
})();
</script>
@endpush
