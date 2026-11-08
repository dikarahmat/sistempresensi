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
    .date-filter-group input[type="month"],
    .date-filter-group select {
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
    .table-history thead tr > th.summary-h { color: #059669 !important; }
    .table-history thead tr > th.summary-s { color: #2563eb !important; }
    .table-history thead tr > th.summary-i { color: #7e22ce !important; }
    .table-history thead tr > th.summary-a { color: #dc2626 !important; }
    .table-history thead tr > th.summary-p { color: #111827 !important; }

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
    .table-history tbody td.summary-h { color: #059669 !important; }
    .table-history tbody td.summary-s { color: #2563eb !important; }
    .table-history tbody td.summary-i { color: #7e22ce !important; }
    .table-history tbody td.summary-a { color: #dc2626 !important; }
    .table-history tbody td.summary-p { color: #111827 !important; }
    .badge-h { color: #059669 !important; font-weight: 400 !important; }
    .badge-s { color: #2563eb !important; font-weight: 400 !important; }
    .badge-i { color: #7e22ce !important; font-weight: 400 !important; }
    .badge-a { color: #dc2626 !important; font-weight: 400 !important; }

    .table-history tbody tr > td { background-color: #ffffff !important; }
    .table-history tbody tr:hover > td { background-color: #f1f5f9 !important; }
</style>
@endpush

@section('content')
    @php
        $currentPeriod = request()->get('period', $period ?? 'harian');
        $_subType = ($currentPeriod === 'semester' || $currentPeriod === 'tahunan')
            ? ($periodSubType ?? 'ganjil')
            : 'ganjil';

        /* Kolom bulan semester/tahunan (tahun ajaran Juli - Juni):
         *  - Ganjil  : Juli s/d Desember (6 bulan)
         *  - Genap   : Januari s/d Juni  (6 bulan)
         *  - Tahunan : Juli s/d Juni     (12 bulan)
         */
        $months = [];
        if (in_array($currentPeriod, ['semester', 'tahunan'], true)) {
            $namaBulan = ['JAN','FEB','MAR','APR','MEI','JUN','JUL','AGT','SEP','OKT','NOV','DES'];
            $startYear = $anchor->month >= 7 ? $anchor->year : $anchor->year - 1;

            if ($_subType === 'genap') {
                $startMonth = \Carbon\Carbon::create($startYear + 1, 1, 1);
                $jumlahBulan = 6;
            } elseif ($_subType === 'ganjil') {
                $startMonth = \Carbon\Carbon::create($startYear, 7, 1);
                $jumlahBulan = 6;
            } else {
                $startMonth = \Carbon\Carbon::create($startYear, 7, 1);
                $jumlahBulan = 12;
            }

            for ($i = 0; $i < $jumlahBulan; $i++) {
                $d = $startMonth->copy()->addMonthsNoOverflow($i);
                $months[] = [
                    'key'   => $d->format('Y-m'),
                    'label' => $namaBulan[$d->month - 1],
                    'year'  => $d->year,
                ];
            }
        }

        $semesterLabel = 'SEMESTER/TAHUN';
        if ($_subType === 'ganjil') { $semesterLabel = 'SEMESTER GANJIL'; }
        elseif ($_subType === 'genap') { $semesterLabel = 'SEMESTER GENAP'; }
        elseif ($_subType === 'tahunan') { $semesterLabel = 'TAHUNAN'; }
        $semesterTanggal = $anchor->toDateString();
    @endphp

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
                <div class="period-semester-wrap" style="position:relative; display:inline-flex; flex:1; min-width:0;">
                    <button type="button" class="period-link {{ in_array($period, ['semester','tahunan'], true) ? 'active' : '' }}" id="semesterTabBtn" data-period="{{ $period }}" data-subtype="{{ $_subType }}" aria-expanded="false" style="position:relative; z-index:10; outline:none; width:100%; justify-content:center;">
                        {{ $semesterLabel }} <i class="bx bx-chevron-down" style="font-size:0.7rem; margin-left:0.25rem; vertical-align:middle;"></i>
                    </button>
                    <div id="semesterMenu" class="semester-menu" style="display:none; position:absolute; left:0; right:0; top:100%; z-index:1060; width:100%; min-width:0; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 10px 25px -8px rgba(15,23,42,0.35); margin-top:4px;">
                        <a href="{{ panel_route('kehadiran.guru-history', [$teacher->id, 'period' => 'semester', 'period_subtype' => 'ganjil', 'tanggal' => $semesterTanggal]) }}" class="semester-menu-item" style="display:block; width:100%; box-sizing:border-box; padding:0.5rem 1rem; font-size:0.85rem; color:#1e293b; text-decoration:none; border-radius:0; white-space:nowrap;">{{ __('Semester Ganjil') }} (Jul - Des)</a>
                        <a href="{{ panel_route('kehadiran.guru-history', [$teacher->id, 'period' => 'semester', 'period_subtype' => 'genap', 'tanggal' => $semesterTanggal]) }}" class="semester-menu-item" style="display:block; width:100%; box-sizing:border-box; padding:0.5rem 1rem; font-size:0.85rem; color:#1e293b; text-decoration:none; border-radius:0; white-space:nowrap;">{{ __('Semester Genap') }} (Jan - Jun)</a>
                        <a href="{{ panel_route('kehadiran.guru-history', [$teacher->id, 'period' => 'tahunan', 'period_subtype' => 'tahunan', 'tanggal' => $semesterTanggal]) }}" class="semester-menu-item" style="display:block; width:100%; box-sizing:border-box; padding:0.5rem 1rem; font-size:0.85rem; color:#1e293b; text-decoration:none; border-radius:0; white-space:nowrap;">{{ __('Tahunan') }} (Jul - Jun)</a>
                    </div>
                </div>
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
                    <input type="hidden" name="tanggal" value="{{ $anchor->toDateString() }}">
                    <select name="period_academic_year" class="form-select" style="width:auto;">
                        @php $academicYears = \App\Models\AcademicYear::orderBy('start_date','desc')->get(); @endphp
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" {{ (request('period_academic_year', optional(\App\Models\AcademicYear::getActive())->id) == $ay->id) ? 'selected' : '' }}>{{ $ay->name }}</option>
                        @endforeach
                    </select>
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
                        <th style="width: 16%;">{{ __('NIP') }}</th>
                        <th style="width: 20%;">{{ __('Nama Guru') }}</th>
                        <th style="width: 8%;">{{ __('JK') }}</th>
                        @if($currentPeriod === 'harian')
                            <th style="width: 16%;">{{ __('Tanggal') }}</th>
                            <th style="width: 12%;">{{ __('Hari') }}</th>
                            <th style="width: 12%;">{{ __('Status') }}</th>
                            <th style="width: 14%;">{{ __('Jam Masuk') }}</th>
                            <th style="width: 18%;">{{ __('Catatan') }}</th>
                        @elseif(in_array($currentPeriod, ['mingguan','bulanan'], true))
                            @php
                                $wTetap = 4 + 16 + 20 + 8;
                                $wKolom = (100 - $wTetap) / max(1, count($dateColumns ?? []));
                            @endphp
                            @foreach($dateColumns ?? [] as $d)
                                <th style="width: {{ $wKolom }}%;">{{ $d['label'] }}</th>
                            @endforeach
                            <th class="summary-h" style="width: 4%;">H</th>
                            <th class="summary-s" style="width: 4%;">S</th>
                            <th class="summary-i" style="width: 4%;">I</th>
                            <th class="summary-a" style="width: 4%;">A</th>
                            <th class="summary-p" style="width: 6%;">%</th>
                        @else
                            @foreach($months as $m)
                                <th style="font-size: 11px; padding: 8px 4px;">
                                    {{ $m['label'] }}
                                    <span class="th-sub" style="font-size: 9px; display:block;">{{ $m['year'] }}</span>
                                </th>
                            @endforeach
                            <th class="summary-h" style="width: 4%; font-size: 11px; padding: 8px 4px;">H</th>
                            <th class="summary-s" style="width: 4%; font-size: 11px; padding: 8px 4px;">S</th>
                            <th class="summary-i" style="width: 4%; font-size: 11px; padding: 8px 4px;">I</th>
                            <th class="summary-a" style="width: 4%; font-size: 11px; padding: 8px 4px;">A</th>
                            <th class="summary-p" style="width: 6%; font-size: 11px; padding: 8px 4px;">%</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if(count($rows) === 1 && $rows[0]['date'] === '-')
                        <tr>
                            <td colspan="12" class="text-center py-5 text-secondary">
                                <i class='bx bx-error' aria-hidden='true'></i>
                                {{ __('Tidak ada data kehadiran guru untuk periode yang dipilih.') }}
                            </td>
                        </tr>
                    @elseif($currentPeriod === 'harian')
                        @foreach($rows as $row)
                            <tr>
                                <td style="text-transform: uppercase;">{{ $row['no'] }}</td>
                                <td style="text-transform: uppercase;">{{ $teacher->nip ?: '-' }}</td>
                                <td class="text-left" style="text-transform: uppercase;">{{ $teacher->name }}</td>
                                <td style="text-transform: uppercase;">{{ strtoupper(substr($teacher->gender ?? 'L', 0, 1)) }}</td>
                                <td style="text-transform: uppercase;">{{ $row['date'] }}</td>
                                <td style="text-transform: uppercase;">{{ $row['day'] }}</td>
                                <td style="color: {{ $row['status_color'] }} !important; text-transform: uppercase;">{{ $row['status'] }}</td>
                                <td style="text-transform: uppercase;">{{ $row['check_in'] }}</td>
                                <td class="text-left" style="text-transform: uppercase;">{{ $row['notes'] }}</td>
                            </tr>
                        @endforeach
                    @elseif(in_array($currentPeriod, ['mingguan','bulanan'], true))
                        @php
                            $jk = str_starts_with(strtolower((string)($teacher->gender ?? 'L')), 'p') ? 'P' : 'L';
                        @endphp
                        <tr>
                            <td style="text-transform: uppercase;">{{ $rows[0]['no'] ?? 1 }}</td>
                            <td style="text-transform: uppercase;">{{ $teacher->nip ?: '-' }}</td>
                            <td class="text-left" style="text-transform: uppercase;">{{ $teacher->name }}</td>
                            <td style="text-transform: uppercase;">{{ $jk }}</td>
                            @foreach($dateColumns ?? [] as $d)
                                @php
                                    $_dStr = $d['date'];
                                    $_att = $attendances->firstWhere('date', $_dStr);
                                    $_code = '-';
                                    if ($_att) {
                                        $_st = $_att->effective_status;
                                        $_code = match($_st) {
                                            'Hadir','Terlambat' => 'H',
                                            'Sakit' => 'S',
                                            'Izin' => 'I',
                                            'Alfa' => 'A',
                                            default => '-',
                                        };
                                    } elseif ($d['is_holiday'] ?? false) {
                                        $_code = 'L';
                                    }
                                @endphp
                                <td class="{{ $_code === 'H' ? 'badge-h' : ($_code === 'S' ? 'badge-s' : ($_code === 'I' ? 'badge-i' : ($_code === 'A' ? 'badge-a' : ''))) }}" style="font-size: 10px;">{{ $_code }}</td>
                            @endforeach
                            <td class="summary-h">{{ $stats['hadir'] }}</td>
                            <td class="summary-s">{{ $stats['sakit'] }}</td>
                            <td class="summary-i">{{ $stats['izin'] }}</td>
                            <td class="summary-a">{{ $stats['alfa'] }}</td>
                            <td class="summary-p">{{ $attendanceRate }}%</td>
                        </tr>
                    @else
                        @php
                            $jk = str_starts_with(strtolower((string)($teacher->gender ?? 'L')), 'p') ? 'P' : 'L';
                        @endphp
                        <tr>
                            <td style="text-transform: uppercase;">{{ $rows[0]['no'] ?? 1 }}</td>
                            <td style="text-transform: uppercase;">{{ $teacher->nip ?: '-' }}</td>
                            <td class="text-left" style="text-transform: uppercase;">{{ $teacher->name }}</td>
                            <td style="text-transform: uppercase;">{{ $jk }}</td>
                            @foreach($months as $m)
                                @php
                                    $_ym = $m['key'];
                                    $_monthAtts = $attendances->filter(fn($a) => substr($a->date->toDateString(), 0, 7) === $_ym);
                                    $_hadir = 0;
                                    foreach ($_monthAtts as $_ma) {
                                        if (in_array($_ma->effective_status, ['Hadir','Terlambat'], true)) { $_hadir++; }
                                    }
                                @endphp
                                <td style="font-size: 11px; padding: 6px 4px;">{{ $_hadir ?: '-' }}</td>
                            @endforeach
                            <td class="summary-h" style="font-size: 11px; padding: 6px 4px;">{{ $stats['hadir'] }}</td>
                            <td class="summary-s" style="font-size: 11px; padding: 6px 4px;">{{ $stats['sakit'] }}</td>
                            <td class="summary-i" style="font-size: 11px; padding: 6px 4px;">{{ $stats['izin'] }}</td>
                            <td class="summary-a" style="font-size: 11px; padding: 6px 4px;">{{ $stats['alfa'] }}</td>
                            <td class="summary-p" style="font-size: 11px; padding: 6px 4px;">{{ $attendanceRate }}%</td>
                        </tr>
                    @endif
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
    function getPeriodSubType() {
        var btn = document.getElementById('semesterTabBtn');
        return btn ? (btn.dataset.subtype || 'ganjil') : 'ganjil';
    }

    var semesterBtn = document.getElementById('semesterTabBtn');
    var semesterMenu = document.getElementById('semesterMenu');
    if (semesterBtn && semesterMenu) {
        semesterBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            var open = semesterMenu.style.display === 'block';
            semesterMenu.style.display = open ? 'none' : 'block';
            semesterBtn.setAttribute('aria-expanded', open ? 'false' : 'true');
        });
        document.addEventListener('click', function(e) {
            if (!semesterBtn.contains(e.target) && !semesterMenu.contains(e.target)) {
                semesterMenu.style.display = 'none';
                semesterBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    var btnTampilkan = document.getElementById('btnTampilkan');
    if (btnTampilkan) {
        btnTampilkan.addEventListener('click', function(e) {
            e.preventDefault();
            var period = getActivePeriod();
            var url = new URL(baseUrl);
            url.searchParams.set('period', period);
            url.searchParams.set('period_subtype', getPeriodSubType());

            if (period === 'harian' || period === 'bulanan') {
                var tanggal = document.getElementById('guruHistoryDate').value;
                url.searchParams.set('tanggal', tanggal);
            } else if (period === 'mingguan') {
                var startDate = document.getElementById('startDate').value;
                var endDate = document.getElementById('endDate').value;
                if (startDate) url.searchParams.set('start_date', startDate);
                if (endDate)   url.searchParams.set('end_date', endDate);
            } else if (period === 'semester' || period === 'tahunan') {
                url.searchParams.set('tanggal', document.getElementById('guruHistoryDate')?.value || '{{ $anchor->toDateString() }}');
                var ay = document.querySelector('select[name="period_academic_year"]');
                if (ay && ay.value) url.searchParams.set('period_academic_year', ay.value);
            }
            window.location.href = url.toString();
        });
    }

    var printBtn = document.getElementById('printPeriodBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();

            var period = getActivePeriod();
            var periodSubType = getPeriodSubType();
            var tanggal = '';
            var startDate = '';
            var endDate = '';

            if (period === 'harian' || period === 'bulanan') {
                tanggal = document.getElementById('guruHistoryDate').value || '{{ $anchor->toDateString() }}';
            } else if (period === 'mingguan') {
                startDate = document.getElementById('startDate').value || '{{ $anchor->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString() }}';
                endDate = document.getElementById('endDate').value || '{{ $anchor->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString() }}';
            } else if (period === 'semester' || period === 'tahunan') {
                tanggal = document.getElementById('guruHistoryDate')?.value || '{{ $anchor->toDateString() }}';
                var ay = document.querySelector('select[name="period_academic_year"]');
                if (ay && ay.value) {
                    // period_academic_year dikirim sebagai param tambahan
                }
            }

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = {!! json_encode(panel_route('kehadiran.guru-history.pdf', $teacher->id)) !!};
            form.style.display = 'none';

            var params = {
                '_token': csrfToken,
                'period': period,
                'period_subtype': periodSubType,
                'tanggal': tanggal,
                'start_date': startDate,
                'end_date': endDate,
            };

            // Sertakan academic_year jika ada (semester/tahunan)
            var aySel = document.querySelector('select[name="period_academic_year"]');
            if (aySel && aySel.value) {
                params['period_academic_year'] = aySel.value;
            }

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
