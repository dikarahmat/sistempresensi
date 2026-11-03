@extends('layouts.app')

@section('title', $viewFor === 'guru' ? __('Catatan Kehadiran Guru') : __('Catatan Kehadiran Siswa'))
@section('page_title', $viewFor === 'guru' ? __('CATATAN KEHADIRAN GURU') : __('CATATAN KEHADIRAN SISWA'))

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
       FILTER BAR: [ kolom pencarian ] [ dropdown "Semua Kelas" ] [ reset ]

       Memakai CSS GRID supaya lebarnya pasti:
         >= 1024px : 1fr | 1fr | 44px -> search dan dropdown SAMA LEBAR,
                     reset di paling kanan, semuanya satu baris.
         <  1024px : baris 1 = search + reset (kanan), baris 2 = dropdown lebar penuh.
         Halaman guru (tanpa dropdown, class kh-no-kelas): search | reset.

       Tinggi ketiganya sama: --kh-h (44px). Ubah satu angka itu untuk
       mengecilkan / membesarkan semuanya sekaligus.

       Selector memakai form#formFilterKehadiran.kehadiran-filter-form supaya
       menang atas aturan toolbar di layout bersama (tanpa mengubah layout).
       Fungsi search & filter TIDAK diubah: name="search", name="class_filter",
       id="filterSearch", id="classFilter", dan onchange submit tetap sama.
       ========================================================================== */
    form#formFilterKehadiran.kehadiran-filter-form {
        --kh-h: 44px;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) var(--kh-h);
        grid-template-areas:
            "search reset"
            "kelas  kelas";
        column-gap: 0.5rem;
        row-gap: 0.5rem;
        align-items: center;
        width: 100% !important;
        flex: 1 1 100% !important;
        height: auto;
        min-height: 0;
        min-width: 0;
        margin: 0;
        padding: 0;
    }

    @media (min-width: 1024px) {
        form#formFilterKehadiran.kehadiran-filter-form {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) var(--kh-h);
            grid-template-areas: "search kelas reset";
            row-gap: 0;
        }
    }

    /* Halaman guru: tidak ada dropdown kelas -> hanya search + reset */
    form#formFilterKehadiran.kehadiran-filter-form.kh-no-kelas {
        grid-template-columns: minmax(0, 1fr) var(--kh-h);
        grid-template-areas: "search reset";
        row-gap: 0;
    }

    /* Penempatan tiap kolom + buang lebar lama dari layout bersama */
    form#formFilterKehadiran.kehadiran-filter-form .search-box-wrap,
    form#formFilterKehadiran.kehadiran-filter-form .filter-box-wrap {
        flex: none !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: none !important;
        margin: 0 !important;
    }
    form#formFilterKehadiran.kehadiran-filter-form .search-box-wrap {
        grid-area: search;
    }
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset {
        grid-area: reset;
    }
    form#formFilterKehadiran.kehadiran-filter-form .filter-box-wrap {
        grid-area: kelas;
    }

    /* Kolom pencarian: input + ikon kaca pembesar satu kesatuan */
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap {
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        height: var(--kh-h) !important;
        min-height: var(--kh-h) !important;
    }
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .form-control,
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .btn {
        height: var(--kh-h) !important;
        min-height: var(--kh-h) !important;
        box-sizing: border-box;
    }
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .form-control {
        min-width: 0;
    }
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .btn {
        flex: 0 0 auto;
    }
    /* Ikon kaca pembesar 20px (kelas fs-6 dari Bootstrap bersifat !important) */
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .btn i,
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .btn .bx {
        font-size: 20px !important;
        line-height: 1;
    }

    /* Dropdown "Semua Kelas" */
    form#formFilterKehadiran.kehadiran-filter-form .filter-box-wrap select.form-select {
        width: 100% !important;
        height: var(--kh-h) !important;
        min-height: var(--kh-h) !important;
        box-sizing: border-box;
    }

    /* Tombol Reset: kotak 44x44, ikon abu-abu, biru saat hover/focus */
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset {
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        width: var(--kh-h);
        height: var(--kh-h);
        padding: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        color: #64748b;              /* samakan dengan warna ikon kaca pembesar */
        text-decoration: none;
        cursor: pointer;
        outline: none;
        transition: border-color .15s, color .15s, background .15s, box-shadow .15s;
    }
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset svg {
        width: 20px;
        height: 20px;
        stroke: currentColor;
        fill: none;
    }
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset:hover,
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset:focus-visible {
        color: #2563eb;
        border-color: #2563eb;
        background: #f8fafc;
        text-decoration: none;
    }
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset:focus-visible {
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .2);
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
                untuk setiap baris, titik mulai huruf identik untuk semua
                baris, tapi tetap terlihat center sebagai grup.
       - overflow: visible + white-space: nowrap -> nama panjang tetap utuh.
       Kalau titik mulainya kurang pas, UBAH angka width di bawah ini.
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

    /* Action Buttons (QR Code + Teks Presensi & Tombol Lihat Lengkap) - Pure Icon */
    .btn-action-presensi,
    .btn-action-absensi {
        background: transparent !important;
        border: none;
        border-radius: 0 !important;
        padding: 0.2rem;
        font-size: 0.8rem;
        font-weight: 500;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        text-decoration: none;
        white-space: nowrap;
        transition: color 0.15s ease, opacity 0.15s ease;
        box-shadow: none !important;
        cursor: pointer;
    }
    .btn-action-presensi:hover,
    .btn-action-absensi:hover {
        color: #1d4ed8;
        opacity: 0.85;
        transform: none;
        box-shadow: none !important;
    }
    .btn-action-presensi i,
    .btn-action-absensi i {
        font-size: 1.1rem;
    }

    .btn-action-lihat {
        background: transparent !important;
        border: none;
        border-radius: 0 !important;
        padding: 0.2rem;
        font-size: 0.8rem;
        font-weight: 500;
        color: #16a34a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        text-decoration: none;
        white-space: nowrap;
        transition: color 0.15s ease, opacity 0.15s ease;
        box-shadow: none !important;
        cursor: pointer;
    }
    .btn-action-lihat:hover {
        color: #15803d;
        opacity: 0.85;
        transform: none;
        box-shadow: none !important;
    }
    /* Tombol ikon di kolom AKSI: pure icon, tanpa ukuran kotak */
    .btn-action-lihat {
        width: auto;
        height: auto;
        padding: 0.2rem;
        border-radius: 0 !important;
    }
    .btn-action-lihat svg {
        display: block;
        width: 20px;
        height: 20px;
    }
    @media (max-width: 767.98px) {
        .btn-action-presensi,
        .btn-action-absensi,
        .btn-action-lihat {
            padding: 0.2rem;
        }
        .btn-action-lihat svg {
            width: 18px;
            height: 18px;
        }
    }

    /* ==========================================================================
       LAYOUT KARTU CATATAN KEHADIRAN
       Susunan vertikal (satu kartu): toolbar filter -> tabel -> paginasi,
       semuanya di dalam #daftar-kehadiran.
       ========================================================================== */
    #daftar-kehadiran {
        display: block;
        /* MODEL SATU AREA SCROLL: kartu mengikuti isi, tanpa batas tinggi
           dan tanpa scroll vertikal sendiri. */
        height: auto !important;
        max-height: none !important;
        min-height: 0 !important;
        margin-bottom: 0 !important;   /* paginasi sudah di dasar layar */
    }

    /* Toolbar di dalam kartu tabel: tinggi ikut isi. */
    #daftar-kehadiran > .p-3 {
        height: auto;
    }

    #daftar-kehadiran .table-responsive {
        max-height: none !important;
        overflow-y: visible !important;
    }

    /* ==========================================================================
       HEADER STICKY - BERSIH, BARIS TIDAK MENIMPA / TIDAK TERPOTONG
       Latar header dibuat pekat (opaque) dan berada di atas semua baris.
       Warna, border, dan font header TIDAK diubah - hanya posisi & layering.
       ========================================================================== */
    #daftar-kehadiran .table-responsive > table > thead {
        z-index: 5 !important;
    }

    #daftar-kehadiran .table-responsive > table > thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 5 !important;
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
        padding: 1.25rem 0.5rem 1.5rem;
        margin: 0;
        text-align: center;
        border-top: 1px solid #f1f5f9;
        background-color: #ffffff;
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
       PERBAIKAN MOBILE: rapatkan tabel & aksi
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

{{-- Tombol "Mode Guru / Mode Siswa" di header DIHAPUS.
     Pindah antar halaman siswa/guru sekarang lewat dropdown Kehadiran di sidebar. --}}

@section('content')
    {{-- SATU KARTU: toolbar filter + tabel (seperti Data Siswa).
         Filter bar: [ pencarian ] [ dropdown kelas ] [ reset ], lihat CSS di atas.
         - dropdown kelas langsung memuat ulang data saat diganti,
         - pencarian jalan saat Enter atau klik ikon kaca pembesar,
         - reset = link ke halaman kehadiran tanpa parameter (paling kanan). --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden md:mb-4 bg-white" id="daftar-kehadiran">

        <div class="p-3 p-md-4 border-bottom border-gray-100 bg-white">
            <form method="GET" action="{{ $viewFor === 'guru' ? panel_route('kehadiran.guru') : panel_route('kehadiran') }}" class="action-search-form kehadiran-filter-form {{ $viewFor === 'guru' ? 'kh-no-kelas' : '' }}" id="formFilterKehadiran">
                <!-- Kolom pencarian: input + ikon search satu kesatuan -->
                <div class="input-group search-box-wrap">
                    <input type="text"
                           name="search"
                           id="filterSearch"
                           class="form-control shadow-none"
                           placeholder="{{ $viewFor === 'guru' ? __('Cari nama atau NIP...') : __('Cari nama atau NISN...') }}"
                           value="{{ $search }}"
                           aria-label="{{ $viewFor === 'guru' ? __('Cari nama atau NIP') : __('Cari nama atau NISN') }}"
                           autocomplete="off"
                           style="font-size: 0.85rem; letter-spacing: 0.03em;">
                    <button class="btn shadow-none" type="submit" title="{{ __('Cari') }}" aria-label="{{ __('Cari') }}">
                        <i class='bx bx-search fs-6'></i>
                    </button>
                </div>

                {{-- Dropdown Filter Kelas hanya tampil di halaman siswa --}}
                @if($viewFor !== 'guru')
                <!-- Dropdown Filter Kelas: ganti kelas -> langsung memuat ulang -->
                <div class="filter-box-wrap">
                    <select name="class_filter" id="classFilter" class="form-select border-secondary-subtle shadow-none fw-normal w-100" style="font-size: 0.82rem; letter-spacing: 0.02em;" aria-label="{{ __('Semua Kelas') }}">
                        {{-- Opsi "Semua Kelas" memakai nilai kosong (tidak ada filter). --}}
                        <option value="" {{ request('class_filter') == '' ? 'selected' : '' }}>{{ __('Semua Kelas') }}</option>
                        {{-- Daftar kelas SELALU lengkap: tidak ikut ter-filter oleh kata pencarian. --}}
                        @foreach($classHistories as $class)
                            <option value="{{ $class->name }}" {{ request('class_filter') == $class->name ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Tombol Reset: paling kanan, setelah dropdown kelas -->
                <a href="{{ $viewFor === 'guru' ? panel_route('kehadiran.guru') : panel_route('kehadiran') }}" class="btn-reset" role="button" title="{{ __('Reset pencarian') }}" aria-label="{{ __('Reset pencarian') }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
                </a>
            </form>
        </div>

        <!-- Tabel Histori Kehadiran per Siswa -->
        <div class="table-responsive" style="padding: 0 24px;">
            <table class="table table-hover align-middle mb-0 table-enterprise table-zebra-custom" style="table-layout: fixed; width: 100%; padding: 0 24px;">
                <thead class="bg-light">
                    <tr class="small fw-bold text-uppercase" style="letter-spacing: 0.03em; color: #000000 !important;">
                        <th class="text-center py-3" style="width: 5%;">{{ __('No') }}</th>
                        <th class="text-center py-3" style="width: 11%;">{{ $viewFor === 'guru' ? __('NIP') : __('NISN') }}</th>
                        <th class="text-start py-3" style="width: 18%; padding-left: 32px;">{{ $viewFor === 'guru' ? __('Nama Guru') : __('Nama Siswa') }}</th>
                        @if($viewFor !== 'guru')
                        <th class="text-center py-3" style="width: 8%;">{{ __('Kelas') }}</th>
                        @endif
                        <th class="text-center py-3" style="width: 10.4%;"><span style="color:#059669 !important">{{ __('Hadir') }}</span></th>
                        <th class="text-center py-3" style="width: 11.4%;"><span style="color:#d97706 !important">{{ __('Terlambat') }}</span></th>
                        <th class="text-center py-3" style="width: 10.4%;"><span style="color:#2563eb !important">{{ __('Sakit') }}</span></th>
                        <th class="text-center py-3" style="width: 10.4%;"><span style="color:#7e22ce !important">{{ __('Izin') }}</span></th>
                        <th class="text-center py-3" style="width: 10.4%;"><span style="color:#dc2626 !important">{{ __('Alfa') }}</span></th>
                        <th class="text-center py-3" style="width: 5%;">{{ __('Aksi') }}</th>
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
                        <td class="text-center text-muted fw-normal" style="width: 5%;">{{ $rowNumber }}</td>
                        <td class="text-center text-nowrap fw-normal" style="width: 11%;">{{ $viewFor === 'guru' ? ($item->nip ?: '-') : ($item->nisn ?: '-') }}</td>
                        <td class="text-start text-nowrap fw-normal" style="width: 25%; padding-left: 32px;">{{ $item->name }}</td>
                        @if($viewFor !== 'guru')
                        <td class="text-center text-nowrap fw-normal" style="width: 8%;">{{ $item->class_name }}</td>
                        @endif
                        <td class="text-center fw-normal" style="width: 9%; color:#059669 !important"><span style="color:#059669 !important">{{ $item->hadir }}</span></td>
                        <td class="text-center fw-normal" style="width: 10%; color:#d97706 !important"><span style="color:#d97706 !important">{{ $item->terlambat }}</span></td>
                        <td class="text-center fw-normal" style="width: 9%; color:#2563eb !important"><span style="color:#2563eb !important">{{ $item->sakit }}</span></td>
                        <td class="text-center fw-normal" style="width: 9%; color:#7e22ce !important"><span style="color:#7e22ce !important">{{ $item->izin }}</span></td>
                        <td class="text-center fw-normal" style="width: 9%; color:#dc2626 !important"><span style="color:#dc2626 !important">{{ $item->alfa }}</span></td>
                        <td class="text-center text-nowrap" style="width: 5%;">
                            @if($viewFor === 'guru')
                            <a href="{{ panel_route('kehadiran.guru-history', $item->id) }}" class="btn-action-lihat" title="{{ __('Lihat riwayat presensi') }} {{ $item->name }}" aria-label="{{ __('Lihat riwayat presensi') }} {{ $item->name }}">
                            @else
                            <a href="{{ panel_route('kehadiran.student-history', $item->id) }}" class="btn-action-lihat" title="{{ __('Lihat riwayat presensi') }} {{ $item->name }}" aria-label="{{ __('Lihat riwayat presensi') }} {{ $item->name }}">
                            @endif
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $viewFor === 'guru' ? 9 : 10 }}" class="text-center py-5 text-muted fw-normal empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            @if(request('search') || request('class_filter'))
                                {{ $viewFor === 'guru' ? __('TIDAK ADA DATA GURU YANG COCOK DENGAN PENCARIAN.') : __('TIDAK ADA DATA SISWA YANG COCOK DENGAN PENCARIAN/FILTER.') }}
                            @else
                                {{ $viewFor === 'guru' ? __('BELUM ADA DATA GURU YANG TERDAFTAR.') : __('BELUM ADA DATA SISWA YANG TERDAFTAR.') }}
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

        {{-- PAGINASI CATATAN KEHADIRAN - TEKS MURNI (TANPA KONTAINER)
             Markup sendiri, BUKAN helper render_compact_pagination, supaya helper
             bersama yang dipakai halaman lain tidak ikut berubah.
             Blok ini sengaja DI DALAM .table-responsive, tepat di bawah baris terakhir. --}}
        @php
            $pgCurrent = $studentHistories->currentPage();
            $pgLast = $studentHistories->lastPage();
            $pgHash = '#daftar-kehadiran';

            // Semua nomor ditampilkan bila <= 7 halaman.
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
            <nav class="kehadiran-pagination" id="kehadiran-pagination" aria-label="{{ __('Navigasi halaman catatan kehadiran') }}">
                <ul class="kehadiran-pagination-list">
                    @if ($pgCurrent <= 1)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">&lsaquo; {{ __('Sebelumnya') }}</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $studentHistories->previousPageUrl() }}{{ $pgHash }}" rel="prev">&lsaquo; {{ __('Sebelumnya') }}</a></li>
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
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">{{ __('Berikutnya') }} &rsaquo;</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $studentHistories->nextPageUrl() }}{{ $pgHash }}" rel="next">{{ __('Berikutnya') }} &rsaquo;</a></li>
                    @endif
                </ul>

                <p class="kehadiran-pagination-info">
                    {{ __('Menampilkan') }} {{ $studentHistories->firstItem() ?? 0 }}&ndash;{{ $studentHistories->lastItem() ?? 0 }}
                    {{ __('dari') }} {{ $studentHistories->total() }} {{ $viewFor === 'guru' ? __('guru') : __('siswa') }}
                </p>
            </nav>
        @endif
        </div><!-- /.table-responsive : penutup area scroll, pagination ikut di dalam -->
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Ganti dropdown kelas -> langsung memuat ulang data (tanpa tombol).
            const classFilter = document.getElementById('classFilter');
            if (classFilter) {
                classFilter.addEventListener('change', function () {
                    this.form.submit();
                });
            }

            // 2. Pencarian HANYA saat Enter atau klik ikon submit
            //    (ditangani oleh form action GET, tanpa auto-submit).

            // 3. Fokus ke tabel saat datang dari paginasi: scroll INSTAN di
            //    dalam area scroll halaman (bukan smooth) supaya header sticky
            //    dan address-bar mobile tidak ikut bergerak (anti kedip/lompat).
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('page')) {
                const el = document.getElementById('daftar-kehadiran');
                if (el) {
                    var scroller = el.closest('main');
                    scroller = scroller ? scroller.querySelector(':scope > .flex-1') : null;
                    var header = document.querySelector('.app-header-bar');
                    var offset = (header ? header.getBoundingClientRect().height : 0) + 12;
                    if (scroller) {
                        var rect = el.getBoundingClientRect();
                        var srect = scroller.getBoundingClientRect();
                        scroller.scrollTo({ top: scroller.scrollTop + (rect.top - srect.top) - offset, behavior: 'auto' });
                    } else {
                        el.scrollIntoView({ behavior: 'auto', block: 'start' });
                    }
                }
            }
        });
    </script>
    @endpush
@endsection