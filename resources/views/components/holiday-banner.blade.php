@props(['isHoliday' => false, 'holidayDescription' => ''])

@if($isHoliday)
<div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
    <div class="flash-notice-body">
        <div class="d-flex align-items-center">
            <i class='bx bx-calendar-exclamation fs-5 me-2 text-warning'></i>
            <span>
                <strong>{{ __('Hari libur:') }}</strong> {{ $holidayDescription }}. {{ __('Presensi tidak dapat diubah.') }}
            </span>
        </div>
    </div>
    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
</div>
@endif
