@props([
    'times' => null,
    'date' => null,
])

@php
    use App\Models\OperatingSchedule;

    $isAdmin = is_admin();
    $today   = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::now('Asia/Jakarta');
    $today->setLocale('id');

    $title     = __('Jadwal Operasional Sekolah');
    $dayName   = $today->copy()->translatedFormat('l');   // Senin, Selasa, ...
    $dateStr   = $today->copy()->translatedFormat('d F Y'); // 08 Desember 2026
    $dateShort = $today->format('d/m/Y');                  // 08/11/2026 (mobile only)
    $dayShort  = substr($dayName, 0, 3);                   // Min, Sen, Sel, Rab, Kam, Jum, Sab
    $hasSchedule = $times !== null;
    $checkIn   = $hasSchedule ? ($times['check_in_time'] ?? null) : null;
    $lateLimit = $hasSchedule ? ($times['late_limit_time'] ?? null) : null;
@endphp

<div class="card border-0 rounded-4 p-3 p-md-4 h-100 d-flex flex-column dashboard-panel dashboard-box">

    <!-- Judul kartu + tanggal pendek (mobile, satu baris: "Jadwal Operasional Sekolah - Min, 08/11/2026") -->
    <div class="d-flex items-start justify-between gap-2 dashboard-divider-off dash-col-head">
        <div class="min-w-0">
            <span class="d-inline fw-bold text-dark dashboard-box-title schedule-title whitespace-nowrap">{{ $title }}</span>
            <span class="d-inline fw-bold text-dark schedule-date whitespace-nowrap md:hidden"> - {{ $dayShort }}, {{ $dateShort }}</span>
        </div>
    </div>

    <!-- Dua kotak jam: Jam Masuk & Batas Terlambat (hanya 2 item) -->
    <div class="dash-clock-grid grid grid-cols-2 gap-3">
        <div class="card border-0 rounded-3 p-3 text-center d-block text-decoration-none h-100 operasional-card-interactive dashboard-inner-card dashboard-clock dash-nobox min-w-0" title="{{ __('Jam masuk sekolah') }}">
            <span class="text-uppercase fw-bold text-dark d-block clock-label">{{ __('Jam Masuk') }}</span>
            <strong class="text-dark mt-1.5 d-block clock-value">
                {{ $checkIn ?? '-' }}@if($checkIn) <span class="clock-wib">WIB</span>@endif
            </strong>
        </div>
        <div class="card border-0 rounded-3 p-3 text-center d-block text-decoration-none h-100 operasional-card-interactive dashboard-inner-card dashboard-clock dash-nobox min-w-0" title="{{ __('Batas keterlambatan sekolah') }}">
            <span class="text-uppercase fw-bold text-dark d-block clock-label">{{ __('Batas Terlambat') }}</span>
            <strong class="text-amber-600 mt-1.5 d-block clock-value">
                {{ $lateLimit ?? '-' }}@if($lateLimit) <span class="clock-wib">WIB</span>@endif
            </strong>
        </div>
    </div>


</div>
