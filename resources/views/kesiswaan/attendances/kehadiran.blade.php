@extends('layouts.app')

@section('title', 'Catatan Kehadiran')
@section('page_title', 'Catatan Kehadiran')
@section('page_subtitle', 'Lihat histori kehadiran per kelas')

@push('styles')
<style>
    /* Container Card Master & Tabel Enterprise Responsif */
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

    /* Header Tabel Bold Eksklusif Sesuai Benchmark */
    .table-enterprise thead th {
        background-color: #f8fafc;
        color: #475569 !important;
        font-weight: 700 !important;
        font-size: 0.78rem;
        padding: 0.85rem 1.1rem;
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
        padding: 0.85rem 1.1rem;
        font-size: 0.86rem;
        font-weight: 400 !important;
        color: #000000 !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Action Buttons (QR Code + Teks Presensi & Tombol Lihat Lengkap) */
    .btn-action-presensi,
    .btn-action-absensi {
        background-color: #2563eb;
        color: #ffffff !important;
        font-size: 0.8rem;
        font-weight: 500;
        padding: 0.42rem 0.85rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: none;
        text-decoration: none;
        white-space: nowrap;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.2);
        transition: all 0.15s ease-in-out;
    }
    .btn-action-presensi:hover,
    .btn-action-absensi:hover {
        background-color: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(37, 99, 235, 0.3);
    }

    .btn-action-lihat {
        background-color: #f8fafc;
        color: #334155 !important;
        border: 1px solid #cbd5e1;
        font-size: 0.8rem;
        font-weight: 500;
        padding: 0.42rem 0.85rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.15s ease-in-out;
    }
    .btn-action-lihat:hover {
        background-color: #e2e8f0;
        color: #0f172a !important;
        transform: translateY(-1px);
    }

    /* ==========================================================================
       PERBAIKAN MOBILE: rapatkan tabel & aksi agar tidak neurotransisi
       ========================================================================== */
    @media (max-width: 767.98px) {
        /* Lebar minimum dikecilkan agar tabel tidak terlalu lebar di layar kecil */
        .table-enterprise {
            min-width: 560px;
        }

        .table-enterprise thead th {
            font-size: 0.68rem;
            padding: 0.5rem 0.6rem;
            letter-spacing: 0.02em;
        }

        .table-enterprise thead th.py-3 {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }

        .table-enterprise tbody td {
            padding: 0.45rem 0.6rem;
            font-size: 0.78rem;
        }

        /* Offset rata-kiri versi desktop dinolkan agar kolom tidak terlihat meleset */
        .table-enterprise tbody td > div[style*="padding-left"] {
            padding-left: 0 !important;
            width: auto !important;
            justify-content: center !important;
        }

        .btn-action-presensi,
        .btn-action-lihat {
            font-size: 0.72rem;
            padding: 0.3rem 0.55rem;
            gap: 0.25rem;
            min-height: 32px;
        }

        .btn-action-presensi i,
        .btn-action-lihat i {
            font-size: 0.95rem;
        }
    }
</style>
@endpush

@section('content')
    <!-- Tabel Histori Kehadiran Bersih (Urutan: No, Kelas, Wali Kelas, Kehadiran, Aksi) -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-enterprise table-zebra-custom">
                <thead class="bg-light">
                    <tr class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.03em;">
                        <th class="text-center py-3" style="width: 6%;">No.</th>
                        <th class="text-center py-3" style="width: 18%;">Kelas</th>
                        <th class="text-center py-3" style="width: 44%;">Wali Kelas</th>
                        <th class="text-center py-3" style="width: 16%;">Kehadiran</th>
                        <th class="text-center py-3" style="width: 16%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classHistories ?? [] as $idx => $item)
                    @php
                        $shortName = preg_replace('/^kelas\s+/i', '', trim($item->name ?? '-'));
                    @endphp
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td class="text-center text-muted fw-normal">{{ $idx + 1 }}</td>
                        <td class="text-center text-nowrap">
                            <span class="text-dark fw-normal">{{ $shortName }}</span>
                        </td>
                        <td class="text-center text-nowrap">
                            <div class="d-inline-flex align-items-center justify-content-start text-start" style="width: 305px; max-width: 100%; padding-left: 3.5rem;">
                                @if(!empty($item->teacher) && $item->teacher !== 'Belum ditentukan' && $item->teacher !== '-')
                                    <span class="text-dark fw-normal">{{ $item->teacher }}</span>
                                @else
                                    <span class="text-secondary small fst-italic fw-normal">Belum ditentukan</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="text-dark fw-normal">{{ (($item->has_today_attendance ?? false) && ($item->persentase ?? 0) > 0) ? $item->persentase : 0 }}%</span>
                        </td>
                        <td class="text-center text-nowrap">
                            <div class="d-inline-flex align-items-center justify-content-center gap-1.5 flex-nowrap">
                                {{-- Tombol Presensi (Ikon QR Code + Teks Berdampingan) --}}
                                <a href="{{ route('kesiswaan.absensi.index', ['class_id' => $item->id]) }}" class="btn-action-presensi" title="Buka Presensi Hari Ini {{ $shortName }}">
                                    <i class='bx bx-qr-scan fs-6'></i>
                                    <span>Presensi</span>
                                </a>

                                {{-- Tombol Lihat (Lengkap dengan Ikon Mata bx-show & Kontainer Asli) --}}
                                <a href="{{ route('kesiswaan.kehadiran.detail', $item->id) }}" class="btn-action-lihat" title="Lihat Rekap Histori {{ $shortName }}">
                                    <i class='bx bx-show'></i>
                                    <span>Lihat</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted fw-normal">
                            <i class='bx bx-folder-open fs-1 d-block mb-2 text-slate-400'></i>
                            Belum ada data rombel yang tercatat pada sistem.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
