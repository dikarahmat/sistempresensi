@extends('layouts.app')

@section('title', __('Histori Kehadiran Kelas') . ' ' . ($schoolClass->name ?? ''))
@section('page_title', __('Histori Kehadiran: Kelas') . ' ' . ($schoolClass->name ?? '-'))
@section('page_subtitle')
    <span class="text-secondary fw-normal">{{ __('Guru Kelas:') }} {{ $schoolClass->teacher->name ?? __('Belum ditentukan') }}</span>
@endsection

@push('styles')
<style>
    .table-custom-card {
        background: #ffffff;
        border: 0 !important;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }

    .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
    }

    .table-enterprise {
        min-width: 680px;
    }

    /* Header Bold Eksklusif */
    .table-enterprise thead th {
        background-color: #f8fafc;
        color: #0f172a !important;
        font-weight: 700 !important;
        font-size: 0.78rem;
        padding: 0.85rem 1rem;
        border-bottom: 1.5px solid #edf2f7;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        vertical-align: middle;
        white-space: nowrap;
    }

    /* Zebra Striping Khusus (Sesuai Benchmark Data Siswa) */
    .table-enterprise tbody tr:nth-child(even) > td,
    .table-enterprise tbody tr.baris-abu > td,
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f1f5f9 !important;
    }
    .table-enterprise tbody tr:nth-child(odd) > td,
    .table-enterprise tbody tr.baris-putih > td,
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-enterprise tbody tr.baris-abu:hover > td,
    .table-enterprise tbody tr.baris-putih:hover > td,
    .table-enterprise tbody tr:hover > td,
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td {
        background-color: #e2e8f0 !important;
    }
    .table-enterprise tbody td {
        padding: 0.8rem 1rem;
        font-size: 0.86rem;
        font-weight: 400 !important;
        color: #000000 !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
</style>
@endpush

@section('content')
    <!-- Tabel Daftar Siswa & Rekap Histori Kehadiran (Format Formal, Teks Regular Standar, Angka Hitam, NISN Tunggal) -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-enterprise table-zebra-custom">
                <thead class="bg-light">
                    <tr class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.03em;">
                        <th class="text-center py-3" style="width: 5%;">{{ __('No') }}.</th>
                        <th class="text-center py-3" style="width: 16%;">{{ __('NISN') }}</th>
                        <th class="py-3" style="width: 27%;">{{ __('Nama Siswa') }}</th>
                        <th class="text-center py-3" style="width: 8%;">{{ __('Hadir') }}</th>
                        <th class="text-center" style="width: 8%;">{{ __('Telat') }}</th>
                        <th class="text-center" style="width: 8%;">{{ __('Sakit') }}</th>
                        <th class="text-center" style="width: 8%;">{{ __('Izin') }}</th>
                        <th class="text-center" style="width: 8%;">{{ __('Alpha') }}</th>
                        <th class="text-center" style="width: 12%;">{{ __('Kehadiran') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($studentRecaps ?? [] as $idx => $st)
                    @php
                        $pct = $st->persentase ?? 0;
                    @endphp
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td class="text-center text-muted fw-normal">{{ $idx + 1 }}</td>
                        <td class="text-center text-nowrap">
                            <span class="text-dark fw-normal">{{ $st->nisn ?: '-' }}</span>
                        </td>
                        <td class="text-nowrap">
                            <span class="text-dark fw-normal">{{ $st->name ?? '-' }}</span>
                        </td>
                        <td class="text-center text-dark fw-normal">{{ $st->hadir ?? 0 }}</td>
                        <td class="text-center text-dark fw-normal">{{ $st->terlambat ?? 0 }}</td>
                        <td class="text-center text-dark fw-normal">{{ $st->sakit ?? 0 }}</td>
                        <td class="text-center text-dark fw-normal">{{ $st->izin ?? 0 }}</td>
                        <td class="text-center text-dark fw-normal">{{ $st->alfa ?? 0 }}</td>
                        <td class="text-center text-dark fw-normal">
                            {{ (($st->total_absen ?? 0) > 0) ? $pct : 0 }}%
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted fw-normal empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            {{ __('Tidak ada siswa yang terdaftar di kelas ini.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
