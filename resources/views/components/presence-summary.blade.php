@props([
    'sudah' => 0,
    'belum' => 0,
    'total' => 0,
    'label' => 'SISWA',
])

@php
    $sudah  = (int) $sudah;
    $belum  = (int) $belum;
    $total  = (int) $total;
    $label  = (string) $label;
@endphp

<!-- Ringkasan presensi harian: tiga item kecil sejajar (sama dengan halaman guru).
     label = "GURU" | "SISWA" (ditampilkan di akhir baris "Total ..."). -->
<div class="presence-summary">
    <span class="item sudah"><i class='bx bx-check'></i> {{ $sudah }} {{ __('Sudah Presensi') }}</span>
    <span class="item belum"><i class='bx bx-x'></i> {{ $belum }} {{ __('Belum Presensi') }}</span>
    <span class="item total"><i class='bx bx-user'></i> {{ $total }} {{ __('Total ') }}{{ $label }}</span>
</div>

@push('styles')
<style>
    /* Presence Summary Bar - digunakan bersama oleh halaman Presensi Guru,
       Presensi Siswa Hari Ini, dan Presensi Kelas. */
    .presence-summary {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1.25rem;
        margin: 0.25rem 0 0.9rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }
    .presence-summary .item {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    .presence-summary .item i {
        font-size: 0.95rem;
    }
    .presence-summary .sudah { color: #059669; }
    .presence-summary .belum { color: #dc2626; }
    .presence-summary .total { color: #2563eb; }

    /* Responsif: di layar kecil bar tetap SATU baris (tidak wrap), item rata
       tengah terhadap layar dengan gap seragam ~12px, tidak mepet tepi layar.
       Desktop (>= 641px) tidak diubah. */
    @media (max-width: 640px) {
        .presence-summary {
            display: flex !important;
            flex-wrap: nowrap !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 0.75rem !important;            /* ~12px antar item */
            width: 100% !important;
            font-size: 0.625rem !important;    /* 10px */
            font-weight: 700 !important;
            letter-spacing: 0 !important;      /* rapat */
            text-transform: uppercase !important;
            margin: 1rem 0 !important;         /* 16px atas & bawah */
            padding: 0 1rem !important;        /* 16px kiri-kanan, tidak mepet tepi */
            box-sizing: border-box !important;
        }
        .presence-summary .item {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.25rem !important;           /* ~4px ikon ke teks */
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }
        .presence-summary .item i {
            font-size: 0.75rem !important;     /* 12px */
        }
    }
</style>
@endpush
