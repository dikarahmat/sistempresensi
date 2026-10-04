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
       KOLOM GURU KELAS — solusi final:
       - <td> : text-align CENTER -> memposisikan BLOK sebagai satu
                kesatuan tetap di tengah kolom, di bawah header.
       - .wali-kelas-inner : lebar TETAP & SAMA untuk semua baris, dengan
                text-align LEFT di dalamnya. Karena lebarnya sama persis
                untuk setiap baris, titik mulai huruf (mis. huruf "B" pada
                Bianca) akan identik untuk semua baris — tidak lagi geser
                sendiri-sendiri kayak sebelumnya, tapi tetap terlihat
                center sebagai grup di bawah "GURU KELAS".
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
       LAYOUT KARTU CATATAN KEHADIRAN
       Susunan vertikal: filter (kartu terpisah di atas) -> kartu tabel yang
       mengisi sisa tinggi layar -> paginasi menempel di paling bawah.
       Hanya .table-responsive yang jadi tempat scroll vertikal, sehingga baris
       terakhir tidak pernah tertutup paginasi dan paginasi tidak ikut
       ter-scroll / terpotong tepi layar.
       ========================================================================== */
    #daftar-kehadiran {
        display: flex;
        flex-direction: column;
        height: calc(100dvh - 13rem);
        min-height: 20rem;
        margin-bottom: 0 !important;   /* paginasi sudah di dasar layar */
    }

    #daftar-kehadiran .table-responsive {
        flex: 1 1 auto;
        min-height: 0;
        /* lepas max-height bawaan layout (65vh) supaya tinggi ikut flex */
        max-height: none !important;
    }

    /* Offset lebih besar di layar kecil: header layout lebih tinggi (sticky +
       safe-area) dan kartu filter jadi 2 kolom. */
    @media (max-width: 767.98px) {
        #daftar-kehadiran {
            height: calc(100dvh - 22rem);
        }
    }

    /* ==========================================================================
       HEADER STICKY - BERSIH, BARIS TIDAK MENIMPA / TIDAK TERPOTONG
       Latar header dibuat pekat (opaque) dan berada di atas semua baris,
       sehingga tidak ada baris yang "bocor" melewati header saat digulir.
       Warna, border, dan font header TIDAK diubah - hanya posisi & layering.
       ========================================================================== */
    #daftar-kehadiran .table-responsive > table > thead {
        z-index: 5 !important;
    }

    #daftar-kehadiran .table-responsive > table > thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 5 !important;
        /* palet sama seperti aslinya, dipaksa pekat untuk sticky header */
        background-color: #f8fafc !important;
    }

    /* Baris data selalu berlapis di bawah header. */
    #daftar-kehadiran .table-responsive > table > tbody > tr > td {
        position: relative;
        z-index: 1;
    }

    /* ==========================================================================
       PAGINASI CATATAN KEHADIRAN - TEKS MURNI
       Tanpa background, border, shadow, pill, lingkaran, atau kotak.
       Area klik dibuat nyaman lewat PADDING, bukan lewat bentuk.
       ========================================================================== */
    .kehadiran-pagination {
        /* Sekarang berada DI DALAM area scroll, jadi bukan lagi flex item kartu.
           Padding atas/bawah lega supaya tidak menempel baris terakhir dan tidak
           terpotong tepi bawah layar saat scroll sudah mentok. */
        padding: 1.25rem 0.5rem 1.5rem;
        margin: 0;
        text-align: center;
        border-top: 1px solid #f1f5f9;
        background-color: #ffffff;
        /* Lebar penuh area scroll, tidak melebar Horizontal. */
        width: 100%;
        box-sizing: border-box;
    }

    .kehadiran-pagination-list {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 0.15rem 1.1rem;
        list-style: none;
        margin: 0 0 0.35rem;
        padding: 0;
    }

    .kehadiran-pagination-step,
    .kehadiran-pagination-page {
        display: inline-block;
        padding: 0.45rem 0.3rem;
        font-size: 0.95rem;
        font-weight: 400;
        line-height: 1.2;
        text-decoration: none;
        background: none !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        color: #64748b;
        cursor: pointer;
    }

    /* Halaman aktif: biru tema, tebal, garis bawah tipis. */
    .kehadiran-pagination-page.is-active {
        color: #2563eb !important;
        font-weight: 700;
        border-bottom: 2px solid #2563eb !important;
        cursor: default;
    }

    /* Hover: hanya warna teks, tanpa bentuk muncul. */
    .kehadiran-pagination-step:hover,
    .kehadiran-pagination-page:hover {
        color: #2563eb !important;
        text-decoration: underline;
    }

    /* Fokus keyboard: outline tipis pada TEKS saja. */
    .kehadiran-pagination-step:focus-visible,
    .kehadiran-pagination-page:focus-visible {
        outline: 2px solid #2563eb !important;
        outline-offset: 1px;
        border-radius: 0 !important;
    }

    /* Redup + tidak bisa diklik di halaman pertama / terakhir. */
    .kehadiran-pagination-step.is-disabled {
        color: #cbd5e1;
        cursor: not-allowed;
        pointer-events: none;
    }

    .kehadiran-pagination-step.is-disabled:hover {
        color: #cbd5e1;
        text-decoration: none;
    }

    .kehadiran-pagination-ellipsis {
        display: inline-block;
        padding: 0.45rem 0.1rem;
        font-size: 0.95rem;
        color: #94a3b8;
        line-height: 1.2;
    }

    .kehadiran-pagination-info {
        margin: 0;
        font-size: 0.85rem;
        color: #64748b;
        letter-spacing: 0.02em;
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
    <!-- Filter Search & Kelas (tanpa label, dropdown full-width di mobile)
         CATATAN: tombol RESET DIHAPUS. Tidak ada lagi aksi manual yang
         diperlukan karena:
           - dropdown kelas langsung memuat ulang data saat diganti,
           - pencarian berjalan otomatis (debounce 400ms) + tetap bisa Enter,
           - mengosongkan kolom pencarian kembali ke tampilan default. -->
    <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4 bg-white">
        <form method="GET" action="{{ panel_route('kehadiran') }}" class="d-flex flex-column flex-md-row gap-2" id="formFilterKehadiran">
            <div class="input-group">
                <input type="text" name="search" id="filterSearch" class="form-control form-control-sm filter-input" placeholder="Cari nama atau NIS..." value="{{ $search }}" aria-label="Cari siswa" autocomplete="off">
                <button type="submit" class="btn btn-sm btn-light border" title="Cari" aria-label="Cari">
                    <i class='bx bx-search'></i>
                </button>
            </div>
            <div class="flex-md-shrink-0" style="min-width: 180px;">
                <select name="class_filter" id="classFilter" class="form-select form-select-sm filter-input w-100" aria-label="Semua Kelas">
                    {{-- Opsi "Semua Kelas" memakai nilai kosong (tidak ada filter). --}}
                    <option value="" {{ request('class_filter') == '' ? 'selected' : '' }}>Semua Kelas</option>
                    {{-- Daftar kelas SELALU lengkap: tidak lagi ikut ter-filter
                         oleh kata pencarian. --}}
                    @foreach($classHistories as $class)
                        <option value="{{ $class->name }}" {{ request('class_filter') == $class->name ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <script>
        // 1. Ganti dropdown kelas -> langsung memuat ulang data (tanpa tombol).
        document.getElementById('classFilter').addEventListener('change', function () {
            this.form.submit();
        });

        // 2. Pencarian otomatis (debounce 400ms + tombol "x" di input) ditangani
        //    secara bersama oleh script auto-filter di layouts/app.blade.php.
    </script>

    <!-- Tabel Histori Kehadiran per Siswa -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" id="daftar-kehadiran">
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
                    @php
                        $rowNumber = ($studentHistories instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            ? ($studentHistories->firstItem() + $idx)
                            : ($idx + 1);
                    @endphp
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td class="text-center text-muted fw-normal">{{ $rowNumber }}</td>
                        <td class="text-center text-nowrap fw-normal">{{ $item->nis }}</td>
                        <td class="text-start text-nowrap fw-normal">{{ $item->name }}</td>
                        <td class="text-center text-nowrap fw-normal">{{ $item->class_name }}</td>
                        <td class="text-center fw-normal" style="color: #16a34a;">{{ $item->hadir }}</td>
                        <td class="text-center fw-normal" style="color: #d97706;">{{ $item->terlambat }}</td>
                        <td class="text-center fw-normal" style="color: #2563eb;">{{ $item->sakit }}</td>
                        <td class="text-center fw-normal" style="color: #7e22ce;">{{ $item->izin }}</td>
                        <td class="text-center fw-normal" style="color: #ef4444;">{{ $item->alfa }}</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ panel_route('kehadiran.student-history', $item->id) }}" class="btn-action-lihat" title="Lihat Riwayat {{ $item->name }}">
                                Lihat
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted fw-normal">
                            <i class='bx bx-info-circle fs-2 d-block mb-2'></i>
                            @if(request('search') || request('class_filter'))
                                TIDAK ADA DATA SISWA YANG COCOK DENGAN PENCARIAN/FILTER.
                            @else
                                BELUM ADA DATA SISWA YANG TERDAFTAR.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

        <!-- ==========================================================================
         PAGINASI CATATAN KEHADIRAN - TEKS MURNI (TANPA KONTAINER)
         Markup sendiri, BUKAN helper render_compact_pagination, supaya helper
         bersama yang dipakai halaman lain (Guru, Kelas, Rekap, Arsip, dll.)
         tidak ikut berubah. Tanpa background/border/shadow/pill - murni teks.

         POSISI: blok ini sengaja DI DALAM .table-responsive (area scroll),
         tepat di bawah baris terakhir. Jadi pagination ikut ter-scroll dan
         baru terlihat setelah user menggulir sampai baris ke-100.
         ======================================================================= -->
        @php
            $pgCurrent = $studentHistories->currentPage();
            $pgLast = $studentHistories->lastPage();
            $pgHash = '#daftar-kehadiran';

            // Semua nomor ditampilkan bila <= 7 halaman (500 siswa / 100 = 5 hal).
            // Kalau lebih, sisipkan "..." di kiri & kanan sekitar halaman aktif.
            $pgPages = [];
            if ($pgLast <= 7) {
                $pgPages = range(1, $pgLast);
            } else {
                $pgPages[] = 1;
                $pgStart = max(2, $pgCurrent - 1);
                $pgEnd = min($pgLast - 1, $pgCurrent + 1);
                if ($pgStart > 2) {
                    $pgPages[] = '...';
                }
                for ($i = $pgStart; $i <= $pgEnd; $i++) {
                    $pgPages[] = $i;
                }
                if ($pgEnd < $pgLast - 1) {
                    $pgPages[] = '...';
                }
                $pgPages[] = $pgLast;
            }
        @endphp

        @if ($pgLast > 1)
            <nav class="kehadiran-pagination" id="kehadiran-pagination" aria-label="Navigasi halaman catatan kehadiran">
                <ul class="kehadiran-pagination-list">
                    @if ($pgCurrent <= 1)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">&lsaquo; Sebelumnya</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $studentHistories->previousPageUrl() }}{{ $pgHash }}" rel="prev">&lsaquo; Sebelumnya</a></li>
                    @endif

                    @foreach ($pgPages as $pgItem)
                        @if ($pgItem === '...')
                            <li><span class="kehadiran-pagination-ellipsis" aria-hidden="true">&hellip;</span></li>
                        @elseif ($pgItem === $pgCurrent)
                            <li><span class="kehadiran-pagination-page is-active" aria-current="page">{{ $pgItem }}</span></li>
                        @else
                            <li><a class="kehadiran-pagination-page" href="{{ $studentHistories->url($pgItem) }}{{ $pgHash }}">{{ $pgItem }}</a></li>
                        @endif
                    @endforeach

                    @if ($pgCurrent >= $pgLast)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">Berikutnya &rsaquo;</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $studentHistories->nextPageUrl() }}{{ $pgHash }}" rel="next">Berikutnya &rsaquo;</a></li>
                    @endif
                </ul>

                <p class="kehadiran-pagination-info">
                    Menampilkan {{ $studentHistories->firstItem() ?? 0 }}&ndash;{{ $studentHistories->lastItem() ?? 0 }}
                    dari {{ $studentHistories->total() }} siswa
                </p>
            </nav>
        @endif
        </div><!-- /.table-responsive : penutup area scroll, pagination ikut di dalam -->
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('page')) {
                const el = document.getElementById('daftar-kehadiran');
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    </script>
    @endpush
@endsection