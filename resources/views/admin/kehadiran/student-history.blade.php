@extends('layouts.app')

@section('title', __('Riwayat Presensi'))
@section('page_title', __('Riwayat Presensi'))
@section('page_subtitle', __('HISTORI KEHADIRAN SISWA PER PERIODE'))

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

    .table-responsive .table-history,
    .table-history {
        width: 100% !important;
        border-collapse: collapse !important;
        table-layout: auto;
    }
    .table-history thead tr > th {
        background-color: #2563eb !important;
        background-image: none !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        padding: 6px 6px !important;
        border: 1px solid #1d4ed8 !important;
        text-align: center !important;
        vertical-align: middle !important;
        white-space: nowrap !important;
        text-transform: uppercase;
        font-size: 11px !important;
        line-height: 1.2 !important;
    }
    .table-history thead tr > th:first-child {
        border-top-left-radius: 8px !important;
    }
    .table-history thead tr > th:last-child {
        border-top-right-radius: 8px !important;
    }
    .table-history thead tr > th.summary-h { background-color: #059669 !important; color: #ffffff !important; border: 1px solid #047857 !important; }
    .table-history thead tr > th.summary-t { background-color: #d97706 !important; color: #ffffff !important; border: 1px solid #b45309 !important; }
    .table-history thead tr > th.summary-s { background-color: #2563eb !important; color: #ffffff !important; border: 1px solid #1d4ed8 !important; }
    .table-history thead tr > th.summary-i { background-color: #7e22ce !important; color: #ffffff !important; border: 1px solid #7e22ce !important; }
    .table-history thead tr > th.summary-a { background-color: #dc2626 !important; color: #ffffff !important; border: 1px solid #b91c1c !important; }
    .table-history thead tr > th.summary-p { background-color: #2563eb !important; color: #ffffff !important; border: 1px solid #1d4ed8 !important; }
    
    .tabel-history-wrap {
        border-radius: 8px !important;
        overflow: hidden !important;
    }
    .table-history tbody td {
        padding: 8px 6px !important;
        text-align: center !important;
        vertical-align: middle !important;
        color: #111827 !important;
        font-weight: 400 !important;
        font-size: 11px !important;
        white-space: nowrap;
    }
    .table-history tbody td.text-left { text-align: left !important; }
    .table-history tbody td.summary-h { color: #059669 !important; font-weight: 700 !important; }
    .table-history tbody td.summary-t { color: #d97706 !important; font-weight: 700 !important; }
    .table-history tbody td.summary-s { color: #2563eb !important; font-weight: 700 !important; }
    .table-history tbody td.summary-i { color: #7e22ce !important; font-weight: 700 !important; }
    .table-history tbody td.summary-a { color: #dc2626 !important; font-weight: 700 !important; }
    .table-history tbody td.summary-p { color: #111827 !important; font-weight: 700 !important; }
    .day-h { color: #059669 !important; font-weight: 700 !important; }
    .day-t { color: #d97706 !important; font-weight: 700 !important; }
    .day-s { color: #2563eb !important; font-weight: 700 !important; }
    .day-i { color: #7e22ce !important; font-weight: 700 !important; }
    .day-a { color: #dc2626 !important; font-weight: 700 !important; }
    .badge-h { color: #059669 !important; font-weight: 700 !important; }
    .badge-t { color: #d97706 !important; font-weight: 700 !important; }
    .badge-s { color: #2563eb !important; font-weight: 700 !important; }
    .badge-i { color: #7e22ce !important; font-weight: 700 !important; }
    .badge-a { color: #dc2626 !important; font-weight: 700 !important; }
    
    .table-history tbody tr:nth-child(even) > td { background-color: #f8fafc !important; }
    .table-history tbody tr:nth-child(odd) > td { background-color: #ffffff !important; }
    .table-history tbody tr:hover > td { background-color: #e2e8f0 !important; }
</style>
@endpush

@section('content')
    <div class="mb-2">
        <h5 class="mb-1" style="color: #0f172a; text-transform: uppercase; font-weight: 400;">{{ $student->name }}</h5>
        <p class="text-secondary mb-0" style="font-size: 0.85rem; text-transform: uppercase;">
            {{ __('NISN') }}: {{ $student->nisn ?: '-' }} &bull; {{ __('Kelas:') }} {{ $student->schoolClass->name ?? '-' }}
        </p>
    </div>

    <div class="mb-3">
        <form method="GET" action="{{ panel_route('kehadiran.student-history', $student->id) }}">
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

            <?php
            $semesterLabel = 'SEMESTER/TAHUN';
            $_st = ($period === 'semua') ? ($periodSubType ?? 'semua') : 'semua';
            if ($_st === 'ganjil') { $semesterLabel = 'SEMESTER GANJIL'; }
            elseif ($_st === 'genap') { $semesterLabel = 'SEMESTER GENAP'; }
            elseif ($_st === 'tahunan') { $semesterLabel = 'TAHUNAN'; }
            $semesterTanggal = $anchor->toDateString();
            ?>
            <div class="period-nav mb-2">
                <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'harian', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'harian' ? 'active' : '' }}" data-period="harian">
                    {{ __('Harian') }}
                </a>
                <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'mingguan', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'mingguan' ? 'active' : '' }}" data-period="mingguan">
                    {{ __('Mingguan') }}
                </a>
                <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'bulanan', 'tanggal' => $anchor->toDateString()]) }}" class="period-link {{ $period === 'bulanan' ? 'active' : '' }}" data-period="bulanan">
                    {{ __('Bulanan') }}
                </a>
                <div class="period-semester-wrap" style="position:relative; display:inline-flex; flex:1; min-width:0;">
                    <button type="button" class="period-link {{ $period === 'semua' ? 'active' : '' }}" id="semesterTabBtn" data-period="semua" data-subtype="{{ $periodSubType ?? 'semua' }}" aria-expanded="false" style="position:relative; z-index:10; outline:none; width:100%; justify-content:center;">
                        {{ $semesterLabel }} <i class="bx bx-chevron-down" style="font-size:0.7rem; margin-left:0.25rem; vertical-align:middle;"></i>
                    </button>
                    <div id="semesterMenu" class="semester-menu" style="display:none; position:absolute; left:0; right:0; top:100%; z-index:1060; width:100%; min-width:0; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 10px 25px -8px rgba(15,23,42,0.35); margin-top:4px;">
                        <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'semua', 'period_subtype' => 'ganjil', 'tanggal' => $semesterTanggal]) }}" class="semester-menu-item" style="display:block; width:100%; box-sizing:border-box; padding:0.5rem 1rem; font-size:0.85rem; color:#1e293b; text-decoration:none; border-radius:0; white-space:nowrap;">{{ __('Semester Ganjil') }}</a>
                        <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'semua', 'period_subtype' => 'genap', 'tanggal' => $semesterTanggal]) }}" class="semester-menu-item" style="display:block; width:100%; box-sizing:border-box; padding:0.5rem 1rem; font-size:0.85rem; color:#1e293b; text-decoration:none; border-radius:0; white-space:nowrap;">{{ __('Semester Genap') }}</a>
                        <a href="{{ panel_route('kehadiran.student-history', [$student->id, 'period' => 'semua', 'period_subtype' => 'tahunan', 'tanggal' => $semesterTanggal]) }}" class="semester-menu-item" style="display:block; width:100%; box-sizing:border-box; padding:0.5rem 1rem; font-size:0.85rem; color:#1e293b; text-decoration:none; border-radius:0; white-space:nowrap;">{{ __('Tahun Ajaran') }}</a>
                    </div>
                </div>
            </div>

            <div class="date-filter-group">
                @if($period === 'harian')
                    <input type="date" name="tanggal" id="studentHistoryDate" class="form-control" value="{{ $anchor->toDateString() }}" aria-label="{{ __('Pilih tanggal') }}">
                @elseif($period === 'mingguan')
                    <input type="date" name="start_date" id="startDate" class="form-control" value="{{ request('start_date', $anchor->toDateString()) }}" aria-label="{{ __('Tanggal awal mingguan') }}">
                    <input type="date" name="end_date" id="endDate" class="form-control" value="{{ request('end_date', $anchor->copy()->addDays(6)->toDateString()) }}" aria-label="{{ __('Tanggal akhir mingguan') }}">
                @elseif($period === 'bulanan')
                    <input type="month" name="tanggal" id="studentHistoryDate" class="form-control" value="{{ $anchor->format('Y-m') }}" aria-label="{{ __('Pilih bulan') }}">
                @else
                    <input type="date" name="tanggal" id="studentHistoryDate" class="form-control" value="{{ $anchor->toDateString() }}" aria-label="{{ __('Pilih tanggal') }}">
                @endif
                <button type="button" class="btn btn-success" id="btnTampilkan">{{ __('Tampilkan') }}</button>
                <button type="button" class="btn btn-danger" id="printPeriodBtn">{{ __('Cetak PDF') }}</button>
            </div>
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive tabel-history-wrap">@php
                $wNo = 4; $wNisn = 9; $wNama = 20; $wKelas = 8; $wJk = 3;
                $currentPeriod = request()->get('period', $period ?? 'harian');

                if ($currentPeriod === 'mingguan' && request()->filled('start_date')) {
                    $startDateInput = \Carbon\Carbon::parse(request()->get('start_date'));
                    $dateColumns = collect();
                    for ($i = 0; $i < 7; $i++) {
                        $currentDay = $startDateInput->copy()->addDays($i);
                        $dayName = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][$currentDay->dayOfWeek];
                        $dateColumns->push([
                            'date' => $currentDay->toDateString(),
                            'label' => strtoupper($dayName) . ', ' . $currentDay->format('d/m')
                        ]);
                    }
                }
            @endphp

            <table class="table-history" id="tableHistory">
                <thead>
                    @if($currentPeriod === 'harian')
                        <tr>
                            <th style="width: {{ $wNo }}%;">{{ __('No') }}</th>
                            <th style="width: {{ $wNisn }}%;">{{ __('NISN') }}</th>
                            <th style="width: {{ $wNama }}%;">{{ __('Nama Siswa') }}</th>
                            <th style="width: {{ $wKelas }}%;">{{ __('Kelas') }}</th>
                            <th style="width: {{ $wJk }}%;">{{ __('JK') }}</th>
                            <th>{{ __('Jam Masuk') }}</th>
                            <th>{{ __('Keterlambatan') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    @elseif($currentPeriod === 'mingguan')
                        <tr>
                            <th style="width: {{ $wNo }}%;">{{ __('No') }}</th>
                            <th style="width: {{ $wNisn }}%;">{{ __('NISN') }}</th>
                            <th style="width: {{ $wNama }}%;">{{ __('Nama Siswa') }}</th>
                            <th style="width: {{ $wKelas }}%;">{{ __('Kelas') }}</th>
                            <th style="width: {{ $wJk }}%;">{{ __('JK') }}</th>
                            @foreach($dateColumns as $d)
                                <th title="{{ $d['label'] }}" style="font-size: 11px; white-space: nowrap !important; padding: 6px 8px;">{{ $d['label'] }}</th>
                            @endforeach
                            <th class="summary-h">H</th>
                            <th class="summary-t">T</th>
                            <th class="summary-s">S</th>
                            <th class="summary-i">I</th>
                            <th class="summary-a">A</th>
                            <th class="summary-p">%</th>
                        </tr>
                    @elseif($currentPeriod === 'bulanan')
                        <tr>
                            <th style="width: {{ $wNo }}%;">{{ __('No') }}</th>
                            <th style="width: {{ $wNisn }}%;">{{ __('NISN') }}</th>
                            <th style="width: {{ $wNama }}%;">{{ __('Nama Siswa') }}</th>
                            <th style="width: {{ $wKelas }}%;">{{ __('Kelas') }}</th>
                            <th style="width: {{ $wJk }}%;">{{ __('JK') }}</th>
                            @foreach($dateColumns as $d)
                                <th style="font-size: 10px; padding: 6px 1px;">{{ (int)substr($d['label'], -2) }}</th>
                            @endforeach
                            <th class="summary-h" style="width: 2.6%;">H</th>
                            <th class="summary-t" style="width: 2.6%;">T</th>
                            <th class="summary-s" style="width: 2.6%;">S</th>
                            <th class="summary-i" style="width: 2.6%;">I</th>
                            <th class="summary-a" style="width: 2.6%;">A</th>
                            <th class="summary-p" style="width: 4%;">%</th>
                        </tr>
                    @else
                        <tr>
                            <th style="width: {{ $wNo }}%;">{{ __('No') }}</th>
                            <th style="width: {{ $wNisn }}%;">{{ __('NISN') }}</th>
                            <th style="width: {{ $wNama }}%;">{{ __('Nama Siswa') }}</th>
                            <th style="width: {{ $wKelas }}%;">{{ __('Kelas') }}</th>
                            <th style="width: {{ $wJk }}%;">{{ __('JK') }}</th>
                            @foreach($months as $m)
                                <th style="font-size: 11px; padding: 8px 4px;">
                                    {{ $m['label'] }}
                                    <span class="th-sub" style="font-size: 9px; display:block; opacity:0.9;">{{ $m['year'] }}</span>
                                </th>
                            @endforeach
                            <th class="summary-h" style="width: 2.6%; font-size: 11px; padding: 8px 4px;">H</th>
                            <th class="summary-t" style="width: 2.6%; font-size: 11px; padding: 8px 4px;">T</th>
                            <th class="summary-s" style="width: 2.6%; font-size: 11px; padding: 8px 4px;">S</th>
                            <th class="summary-i" style="width: 2.6%; font-size: 11px; padding: 8px 4px;">I</th>
                            <th class="summary-a" style="width: 2.6%; font-size: 11px; padding: 8px 4px;">A</th>
                            <th class="summary-p" style="width: 4%; font-size: 11px; padding: 8px 4px;">%</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr data-student-id="{{ $row['student']->id }}">
                            <td style="width: {{ $wNo }}%; text-transform: uppercase; font-weight: 400;">{{ $row['no'] }}</td>
                            <td style="width: {{ $wNisn }}%; text-transform: uppercase; font-weight: 400;">{{ $row['student']->nisn ?: '-' }}</td>
                            <td class="nama-siswa" style="width: {{ $wNama }}%; text-transform: uppercase; font-weight: 400;">{{ $row['student']->name }}</td>
                            <td style="width: {{ $wKelas }}%; text-transform: uppercase; font-weight: 400;">{{ $row['student']->schoolClass->name ?? '-' }}</td>
                            <td style="width: {{ $wJk }}%; text-transform: uppercase; font-weight: 400;">{{ strtoupper(substr($row['harian']->gender ?? 'L', 0, 1)) }}</td>
                            
                            @if($currentPeriod === 'harian')
                                @php
                                    $_h = $row['harian'];
                                    $_lm = (int) ($_h->late_minutes ?? 0);
                                    $_lateText = $_lm > 0 ? ((int) floor($_lm / 60)).' jam '.($_lm % 60).' menit' : '-';
                                    $_status = $_h->current_status ?? '-';
                                    $_statusNorm = ucfirst(strtolower((string) $_status));
                                    $_statusColor = match($_statusNorm) {
                                        'Terlambat' => '#d97706',
                                        'Hadir' => '#059669',
                                        'Sakit' => '#2563eb',
                                        'Izin' => '#7e22ce',
                                        'Alfa' => '#dc2626',
                                        default => '#6b7280',
                                    };
                                    @endphp
                                <td style="color: #111827 !important; text-transform: uppercase; font-weight: 400;">{{ $_h->jam_masuk ?? '-' }}</td>
                                <td style="color: #111827 !important; text-transform: uppercase; font-weight: 400;">{{ $_lateText }}</td>
                                <td style="color: {{ $_statusColor }} !important; text-transform: uppercase; font-weight: 400;">{{ $_statusNorm }}</td>
                            @elseif($currentPeriod === 'mingguan')
                                @foreach($dateColumns as $d)
                                    @php
                                        $_code = strtoupper(trim((string) ($days[$d['date']] ?? '-')));
                                        $_codeColor = match($_code) {
                                            'T', 'TERLAMBAT' => '#d97706',
                                            'H', 'HADIR' => '#059669',
                                            'S', 'SAKIT' => '#2563eb',
                                            'I', 'IZIN' => '#7e22ce',
                                            'A', 'ALFA' => '#dc2626',
                                            default => '',
                                        };
                                        $_tdStyle = $_codeColor ? ' style="font-size: 10px; color: '.$_codeColor.' !important;"' : ' style="font-size: 10px;"';
                                        $_spanStyle = $_codeColor ? ' style="color: '.$_codeColor.' !important;"' : '';
                                        $_codeClass = match($_code) {
                                            'T', 'TERLAMBAT' => 'badge-t',
                                            'H', 'HADIR' => 'badge-h',
                                            'S', 'SAKIT' => 'badge-s',
                                            'I', 'IZIN' => 'badge-i',
                                            'A', 'ALFA' => 'badge-a',
                                            default => '',
                                        };
                                    @endphp
                                    <td class="{{ $_codeClass }}"{{ $_tdStyle }}><span class="{{ $_codeClass }}"{{ $_spanStyle }}>{{ $_code }}</span></td>
                                @endforeach
                                <td class="summary-h">{{ $row['hadir'] }}</td>
                                <td class="summary-t">{{ $row['terlambat'] }}</td>
                                <td class="summary-s">{{ $row['sakit'] }}</td>
                                <td class="summary-i">{{ $row['izin'] }}</td>
                                <td class="summary-a">{{ $row['alfa'] }}</td>
                                <td class="summary-p">{{ $row['percentage'] }}%</td>
                            @elseif($currentPeriod === 'bulanan')
                                @foreach($dateColumns as $d)
                                    @php
                                        $_code = strtoupper(trim((string) ($days[$d['date']] ?? '-')));
                                        $_codeColor = match($_code) {
                                            'T', 'TERLAMBAT' => '#d97706',
                                            'H', 'HADIR' => '#059669',
                                            'S', 'SAKIT' => '#2563eb',
                                            'I', 'IZIN' => '#7e22ce',
                                            'A', 'ALFA' => '#dc2626',
                                            default => '',
                                        };
                                        $_tdStyle = $_codeColor ? ' style="font-size: 10px; color: '.$_codeColor.' !important;"' : ' style="font-size: 10px;"';
                                        $_spanStyle = $_codeColor ? ' style="color: '.$_codeColor.' !important;"' : '';
                                        $_codeClass = match($_code) {
                                            'T', 'TERLAMBAT' => 'badge-t',
                                            'H', 'HADIR' => 'badge-h',
                                            'S', 'SAKIT' => 'badge-s',
                                            'I', 'IZIN' => 'badge-i',
                                            'A', 'ALFA' => 'badge-a',
                                            default => '',
                                        };
                                    @endphp
                                    <td class="{{ $_codeClass }}"{{ $_tdStyle }}><span class="{{ $_codeClass }}"{{ $_spanStyle }}>{{ $_code }}</span></td>
                                @endforeach
                                <td class="summary-h">{{ $row['hadir'] }}</td>
                                <td class="summary-t">{{ $row['terlambat'] }}</td>
                                <td class="summary-s">{{ $row['sakit'] }}</td>
                                <td class="summary-i">{{ $row['izin'] }}</td>
                                <td class="summary-a">{{ $row['alfa'] }}</td>
                                <td class="summary-p">{{ $row['percentage'] }}%</td>
                            @else
                                @foreach($months as $m)
                                    <td style="font-size: 11px; padding: 6px 4px;">{{ $row['monthly'][$m['key']] ?? '-' }}</td>
                                @endforeach
                                <td class="summary-h" style="font-size: 11px; padding: 6px 4px;">{{ $row['hadir'] }}</td>
                                <td class="summary-t" style="font-size: 11px; padding: 6px 4px;">{{ $row['terlambat'] }}</td>
                                <td class="summary-s" style="font-size: 11px; padding: 6px 4px;">{{ $row['sakit'] }}</td>
                                <td class="summary-i" style="font-size: 11px; padding: 6px 4px;">{{ $row['izin'] }}</td>
                                <td class="summary-a" style="font-size: 11px; padding: 6px 4px;">{{ $row['alfa'] }}</td>
                                <td class="summary-p" style="font-size: 11px; padding: 6px 4px;">{{ $row['percentage'] }}%</td>
                            @endif
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
    var studentId = {{ $student->id }};
    var baseUrl = window.location.origin + window.location.pathname;
    var csrfToken = {!! json_encode(csrf_token()) !!};

    function getActivePeriod() {
        var link = document.querySelector('.period-link.active');
        return link && link.dataset ? (link.dataset.period || '{{ $period }}') : '{{ $period }}';
    }
    function getPeriodSubType() {
        var btn = document.getElementById('semesterTabBtn');
        return btn ? (btn.dataset.subtype || 'semua') : 'semua';
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

            if (period === 'harian' || period === 'bulanan') {
                var tanggal = document.getElementById('studentHistoryDate').value;
                url.searchParams.set('tanggal', tanggal);
            } else if (period === 'mingguan') {
                var startDate = document.getElementById('startDate').value;
                var endDate = document.getElementById('endDate').value;
                if (startDate) url.searchParams.set('start_date', startDate);
                if (endDate)   url.searchParams.set('end_date', endDate);
            } else if (period === 'semua') {
                url.searchParams.set('tanggal', document.getElementById('studentHistoryDate').value);
                url.searchParams.set('period_subtype', getPeriodSubType());
            }
            window.location.href = url.toString();
        });
    }

    var printBtn = document.getElementById('printPeriodBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var tanggal = document.getElementById('studentHistoryDate') ? document.getElementById('studentHistoryDate').value : '{{ $anchor->toDateString() }}';
            var period = getActivePeriod();
            var periodSubType = getPeriodSubType();

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = {!! json_encode(panel_route('rekap.print')) !!};
            form.target = '_blank';

            var inputs = {
                '_token': csrfToken,
                'format': 'pdf',
                'type': period === 'semua' ? (periodSubType === 'tahunan' ? 'tahunan' : 'semester') : period,
                'period_type': period === 'semua' ? (periodSubType === 'tahunan' ? 'tahunan' : 'semester') : period,
                'print_mode': 'student',
                'student_scope': 'single',
                'student_id': studentId,
                'period_date': tanggal
            };

            for (var key in inputs) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = inputs[key];
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
            form.remove();
        });
    }
})();
</script>
@endpush