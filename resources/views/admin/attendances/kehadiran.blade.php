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
        font-weight: 700 !important;
        background-color: #f8fafc;
        color: #000000 !important;
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

    /* --------------------------------------------------------------------
       KOLOM WALI KELAS — solusi final:
       - <td> : text-align CENTER -> memposisikan BLOK sebagai satu
                kesatuan tetap di tengah kolom, di bawah header.
       - .wali-kelas-inner : lebar TETAP & SAMA untuk semua baris, dengan
                text-align LEFT di dalamnya. Karena lebarnya sama persis
                untuk setiap baris, titik mulai huruf (mis. huruf "B" pada
                Bianca) akan identik untuk semua baris — tidak lagi geser
                sendiri-sendiri kayak sebelumnya, tapi tetap terlihat
                center sebagai grup di bawah "WALI KELAS".
       - overflow: visible + white-space: nowrap -> nama yang lebih
                panjang dari lebar acuan tetap tampil utuh (tidak terpotong),
                cuma "meluber" sedikit ke kanan, bukan ke luar dari titik
                start yang sama.
       Kalau titik mulainya masih kurang pas / kurang ke tengah, TINGGAL
       UBAH angka width di bawah ini (mis. 230px jadi 250px / 210px).
       -------------------------------------------------------------------- */
    .table-enterprise td.col-wali-kelas {
        text-align: center;
    }

    .table-enterprise td.col-wali-kelas .wali-kelas-inner {
        display: inline-block;
        width: 230px;
        text-align: left;
        white-space: nowrap;
        overflow: visible;
    }

    @media (max-width: 767.98px) {
        .table-enterprise td.col-wali-kelas .wali-kelas-inner {
            width: 150px;
        }
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
        background-color: #3b82f6;
        color: #ffffff !important;
        border: none;
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
        box-shadow: 0 1px 2px rgba(59, 130, 246, 0.2);
    }
    .btn-action-lihat:hover {
        background-color: #2563eb;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(59, 130, 246, 0.3);
    }

    /* ==========================================================================
       PERBAIKAN MOBILE: rapatkan tabel & aksi agar tidak neurotransisi
       ========================================================================== */
    @media (max-width: 767.98px) {
        .table-enterprise {
            min-width: 560px;
        }

        .table-enterprise thead th {
            font-weight: 700 !important;
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
    <!-- Filter Search & Kelas (tanpa label, dropdown full-width di mobile) -->
    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4 bg-white">
        <form method="GET" action="{{ route('admin.kehadiran') }}" class="d-flex flex-column flex-md-row gap-2">
            <div class="input-group">
                <input type="text" name="search" class="form-control form-control-sm filter-input" placeholder="Cari nama atau NIS..." value="{{ $search }}" aria-label="Cari siswa">
                <button type="submit" class="btn btn-sm btn-light border" title="Cari" aria-label="Cari">
                    <i class='bx bx-search'></i>
                </button>
            </div>
            <div class="flex-md-shrink-0" style="min-width: 180px;">
                <select name="class_filter" id="classFilter" class="form-select form-select-sm filter-input w-100" aria-label="Semua Kelas">
                    <option value="">Semua Kelas</option>
                    @foreach($classHistories as $class)
                        <option value="{{ $class->name }}" {{ request('class_filter') == $class->name ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if(request('search') || request('class_filter'))
            <div class="flex-md-shrink-0 align-self-start">
                <a href="{{ route('admin.kehadiran') }}" class="btn btn-sm btn-light border rounded-3">Reset</a>
            </div>
            @endif
        </form>
    </div>

    <script>
        // Submit form hanya saat dropdown kelas berubah
        document.getElementById('classFilter').addEventListener('change', function () {
            this.form.submit();
        });
    </script>

    <!-- Tabel Histori Kehadiran per Siswa -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-enterprise table-zebra-custom">
                <thead class="bg-light">
                    <tr class="small fw-bold text-uppercase" style="letter-spacing: 0.03em; color: #000000 !important;">
                        <th class="text-center py-3" style="width: 5%;">NO</th>
                        <th class="text-center py-3" style="width: 10%;">NIS</th>
                        <th class="text-start py-3" style="width: 25%;">NAMA SISWA</th>
                        <th class="text-center py-3" style="width: 10%;">KELAS</th>
                        <th class="text-center py-3" style="width: 8%;">HADIR</th>
                        <th class="text-center py-3" style="width: 8%;">TELAT</th>
                        <th class="text-center py-3" style="width: 8%;">SAKIT</th>
                        <th class="text-center py-3" style="width: 8%;">IZIN</th>
                        <th class="text-center py-3" style="width: 8%;">ALPHA</th>
                        <th class="text-center py-3" style="width: 10%;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($studentHistories ?? [] as $idx => $item)
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td class="text-center text-muted fw-normal">{{ $idx + 1 }}</td>
                        <td class="text-center text-nowrap fw-normal">{{ $item->nis }}</td>
                        <td class="text-start text-nowrap fw-normal">{{ $item->name }}</td>
                        <td class="text-center text-nowrap fw-normal">{{ $item->class_name }}</td>
                        <td class="text-center fw-normal" style="color: #16a34a;">{{ $item->hadir }}</td>
                        <td class="text-center fw-normal" style="color: #d97706;">{{ $item->terlambat }}</td>
                        <td class="text-center fw-normal" style="color: #2563eb;">{{ $item->sakit }}</td>
                        <td class="text-center fw-normal" style="color: #7e22ce;">{{ $item->izin }}</td>
                        <td class="text-center fw-normal" style="color: #ef4444;">{{ $item->alfa }}</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('admin.kehadiran.student-history', $item->id) }}" class="btn-action-lihat" title="Lihat Riwayat {{ $item->name }}">
                                Lihat
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted fw-normal">
                            Belum ada data siswa yang tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection