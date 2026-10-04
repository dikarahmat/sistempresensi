@extends('layouts.app')

@section('title', 'Catatan Kehadiran')
@section('page_title', 'Catatan Kehadiran')
@section('page_subtitle', 'Lihat histori kehadiran per kelas')

{{-- Kanvas dikunci setinggi satu layar; kartu tabel mengisi sisa tinggi kanvas
     sehingga tabelnya (bukan halaman) yang menggulir. Class ini diatur di layout
     bersama, sama seperti halaman acuan. --}}
@section('canvas_class', 'page-canvas-fixed')

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

    /* ==========================================================================
       FILTER SEARCH + DROPDOWN "SEMUA KELAS" - SAMA PERSIS DENGAN DATA SISWA
       (acuan: resources/views/admin/students/index.blade.php)

       MARKUP & CLASS-nya juga disamakan persis dengan Data Siswa / Data Guru /
       Data Kelas:
         - .input-group.search-box-wrap  -> input 38px + tombol ikon search
           (kaca pembesar) yang menempel di kanan input (gaya bersama di
           layout: border #cbd5e1, radius 6px, padding-right 2.75rem supaya
           teks tidak ketiban ikon);
         - .filter-box-wrap              -> pembungkus <select>;
         - .action-search-form           -> pemicu aturan susunan mobile yang
           sama persis di layout bersama (lihat blok "TOOLBAR HALAMAN DATA").

       Yang ditulis di halaman ini hanya LEBAR & JARAK, juga sama dengan
       Data Siswa:
         - input cari : flex 1 1 240px, min 170px (mengisi sisa ruang)
         - dropdown   : flex 0 1 200px, min 150px, max 200px
         - jarak      : 0.5rem, align-items center (sejajar vertikal, tinggi
                        sama-sama 38px)

       >= 1280px memakai proporsi yang sama dengan action bar Data Siswa
       (input mengisi ruang, dropdown 36% dengan min 160px).
       < 1024px aturan bersama di layout men-stack: search di atas, dropdown
       di bawah, keduanya lebar penuh - persis seperti Data Siswa.
       Fungsi search & filter TIDAK diubah: name="search", name="class_filter",
       id="filterSearch", id="classFilter", dan onchange submit tetap sama.
       ========================================================================== */
    .kehadiran-filter-form {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        /* TINGGI WRAPPER MENGIKUTI ISI - tidak ada lagi pembengkakan:
           tanpa height/min-height/flex-grow yang memaksa melar. */
        height: auto;
        min-height: 0;
        align-content: flex-start;
        gap: 0.5rem;
        flex: 1 1 380px;
        min-width: 0;
        margin: 0;
        padding: 0;
    }

    /* Input pencarian + ikon search = satu kesatuan, tidak pernah turun baris */
    .kehadiran-filter-form .search-box-wrap {
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        flex: 1 1 240px;
        min-width: 170px;
        width: auto;
        max-width: none;
        height: 38px;
        margin-bottom: 0;
    }
    .kehadiran-filter-form .search-box-wrap input.form-control {
        height: 38px;
        min-width: 0;
    }
    .kehadiran-filter-form .search-box-wrap > .btn {
        height: 38px;
        flex: 0 0 auto;
    }

    .kehadiran-filter-form .filter-box-wrap {
        flex: 0 1 200px;
        min-width: 150px;
        max-width: 200px;
        width: auto;
    }
    .kehadiran-filter-form .filter-box-wrap select.form-select {
        height: 38px;
    }

    @media (min-width: 1280px) {
        .kehadiran-filter-form .search-box-wrap {
            flex: 1 1 0%;
            min-width: 220px;
            max-width: none;
        }
        .kehadiran-filter-form .filter-box-wrap {
            flex: 0 1 36%;
            min-width: 160px;
            max-width: none;
        }
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
       Susunan vertikal (satu kartu): toolbar filter -> tabel -> paginasi,
       semuanya di dalam #daftar-kehadiran. Hanya .table-responsive yang jadi
       tempat scroll vertikal, sehingga baris terakhir tidak pernah tertutup
       paginasi dan paginasi tidak ikut ter-scroll / terpotong tepi layar.

       Toolbar filter kini jadi anak pertama kartu ini (bukan kartu terpisah),
       jadi angkanya sama persis dengan #daftar-siswa:
         >=1024 : 9.1rem  |  768-1023 : 14.25rem
         640-767 : 11.25rem  |  <640  : 10.9rem
       ========================================================================== */
    #daftar-kehadiran {
        display: flex;
        flex-direction: column;
        /* PERBAIKAN (ruang kosong): tinggi TIDAK lagi dipaksa.
           Sebelumnya `height: calc(100dvh - 13rem)` + toolbar di kartu
           terpisah membuat halaman terlalu tinggi: kartu tabel tetap
           setinggi layar meski isinya cuma beberapa baris, sehingga muncul
           kotak putih kosong besar dan tabel terdorong jauh ke bawah.

           Sekarang: `height: auto` (kartu mengikuti isinya) + `max-height`
           sebagai batas. Baris banyak -> .table-responsive yang meng-scroll
           di dalam kotak putih; baris sedikit -> kartu ikut mengecil dan
           tidak ada lagi ruang kosong. */
        height: auto !important;
        max-height: calc(100dvh - 9.1rem);
        min-height: 0 !important;
        margin-bottom: 0 !important;   /* paginasi sudah di dasar layar */
    }

    /* Toolbar di dalam kartu tabel: tinggi ikut isi, tidak pernah diremas
       oleh flex, dan tidak pernah ikut melar mengikuti tinggi kartu. */
    #daftar-kehadiran > .p-3 {
        flex: 0 0 auto;
        height: auto;
    }

    #daftar-kehadiran .table-responsive {
        flex: 1 1 auto;
        min-height: 0;
        /* lepas max-height bawaan layout (65vh) supaya tinggi ikut flex */
        max-height: none !important;
    }

    /* Offset lebih besar di layar kecil: header layout lebih tinggi (sticky +
       safe-area), tabel tampil sebagai kartu, dan ada bottom-nav. */
    @media (max-width: 1023.98px) {
        #daftar-kehadiran {
            max-height: calc(100dvh - 14.25rem);
        }
    }

    @media (max-width: 767.98px) {
        #daftar-kehadiran {
            max-height: calc(100dvh - 11.25rem);
        }
    }

    @media (max-width: 639.98px) {
        #daftar-kehadiran {
            max-height: calc(100dvh - 10.9rem);
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
           - mengosongkan kolom pencarian kembali ke tampilan default.
         Tampilan search + dropdown disamakan PERSIS dengan Data Siswa / Data
         Guru / Data Kelas (tinggi 38px, ikon search di kanan input, dropdown
         "Semua Kelas" max 200px, jarak 0.5rem). Fungsi tidak berubah. -->
    <!-- ==========================================================
         SATU KARTU: toolbar filter + tabel (PERSIS seperti Data Siswa)

         Sebelumnya toolbar berada di kartu TERPISAH di atas kartu tabel.
         Akibatnya Searching & dropdown "Semua Kelas" tampak seperti
         memiliki wrapper yang membengkak: kartu toolbar + kartu tabel
         (yang punya tinggi tetap) menyisakan kotak putih kosong besar
         sehingga tabel terdorong jauh ke bawah.

         Sekarang toolbar jadi anak PERTAMA dari kartu tabel, dengan
         padding 16px (mobile) / 24px (desktop) + garis pemisah
         `border-bottom` - persis seperti Data Siswa / Data Guru /
         Data Kelas. Tidak ada lagi kartu atau celah di antara
         search dan tabel.
         ========================================================== -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white" id="daftar-kehadiran">

        <!-- Action Bar & Filter: tinggi wrapper mengikuti isi (height:auto),
             search + tombol cari + dropdown dalam satu baris, tinggi 38px. -->
        <div class="p-3 p-md-4 border-bottom border-gray-100 bg-white">
            <form method="GET" action="{{ panel_route('kehadiran') }}" class="action-search-form kehadiran-filter-form" id="formFilterKehadiran">
                <!-- Search Bar: input + ikon search satu kesatuan (tidak turun baris) -->
                <div class="input-group search-box-wrap">
                    <input type="text"
                           name="search"
                           id="filterSearch"
                           class="form-control border-secondary-subtle border-end-0 shadow-none ps-3"
                           placeholder="Cari nama atau NIS..."
                           value="{{ $search }}"
                           aria-label="Cari nama atau NIS"
                           autocomplete="off"
                           style="font-size: 0.85rem; letter-spacing: 0.03em;">
                    <button class="btn bg-white border border-secondary-subtle border-start-0 shadow-none text-secondary px-3" type="submit" title="Cari" aria-label="Cari" style="height: 38px;">
                        <i class='bx bx-search fs-6'></i>
                    </button>
                </div>

                <!-- Dropdown Filter Kelas: ganti kelas -> langsung memuat ulang -->
                <div class="filter-box-wrap">
                    <select name="class_filter" id="classFilter" class="form-select border-secondary-subtle shadow-none fw-normal w-100" style="font-size: 0.82rem; letter-spacing: 0.02em;" aria-label="Semua Kelas">
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

        <!-- Tabel Histori Kehadiran per Siswa -->
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
            // 1. Ganti dropdown kelas -> langsung memuat ulang data (tanpa tombol).
            //    Dipindah ke sini (bukan blok script inline di atas) karena
            //    toolbar kini berada di dalam kartu tabel yang sama.
            const classFilter = document.getElementById('classFilter');
            if (classFilter) {
                classFilter.addEventListener('change', function () {
                    this.form.submit();
                });
            }

            // 2. Pencarian otomatis (debounce 400ms + tombol "x" di input)
            //    ditangani secara bersama oleh script auto-filter di
            //    layouts/app.blade.php - tidak diubah.

            // 3. Fokus ke tabel saat datang dari paginasi.
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