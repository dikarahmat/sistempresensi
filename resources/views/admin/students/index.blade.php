@extends('layouts.app')

@section('title', 'Data Siswa')
@section('page_title', 'Data Siswa')
@section('page_subtitle', 'Kelola informasi siswa')

{{-- Kanvas dikunci setinggi satu layar; kartu tabel mengisi sisa tinggi kanvas
     sehingga tabelnya (bukan halaman) yang menggulir. Class ini diatur di layout
     bersama, sama seperti halaman acuan. --}}
@section('canvas_class', 'page-canvas-fixed')

@push('styles')
<style>
    /* Subtitle Data Siswa: full width, tidak terpotong (mobile & desktop) */
    .header-main-subtitle {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        display: block !important;
        width: 100% !important;
    }
</style>
@endpush

@push('styles')
<style>
    /* Zebra Striping Khusus */
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f8fafc !important;
    }
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td {
        background-color: #f1f5f9 !important;
    }

    .table-zebra-custom {
        width: 100%;
        margin-bottom: 0;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        display: block;
    }

    .table-zebra-custom {
        width: 100%;
        /* Lantai lebar tabel: di layar sempit tabel digeser kiri-kanan, bukan
           kolom-kolomnya dipipihkan. Semua kolom (termasuk Jenis Kelamin &
           Nama Wali) ikut dihitung; nilai desktop tidak berubah karena tabel
           selalu lebih lebar dari ini. */
        min-width: 860px;
        margin-bottom: 0;
    }

    .table-zebra-custom th,
    .table-zebra-custom td {
        vertical-align: middle;
    }
    .table-zebra-custom tbody td {
        color: #1e293b !important;
        font-weight: 400 !important;
        letter-spacing: 0.03em;
        font-family: 'Poppins', 'Roboto', sans-serif;
        font-size: 0.78rem;
    }

    .indent-nama {
        padding-left: 1.25rem !important;
    }
    @media (min-width: 992px) {
        .indent-nama {
            padding-left: 2rem !important;
        }
    }

    .crud-center-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
    }

    .pagination-compact .pagination {
        margin-bottom: 0;
        font-size: 0.82rem;
    }
    .pagination-compact .page-link {
        padding: 0.35rem 0.65rem;
        border-radius: 6px !important;
    }

    @media (max-width: 767.98px) {
        .pagination-compact .pagination {
            justify-content: center !important;
            flex-wrap: wrap;
            gap: 2px;
        }
    }

    .table-zebra-custom thead th {
        color: #475569 !important;
        font-size: 0.72rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.03em;
        border-bottom: 1.5px solid #edf2f7 !important;
        background-color: #f8fafc !important;
        padding: 0.75rem 0.75rem;
        font-family: 'Poppins', 'Roboto', sans-serif;
        text-transform: uppercase;
    }

    /* ===== Action Bar Layout =====
       Satu baris rata tengah: [input cari][ikon search][dropdown kelas]
       ..... [CETAK KARTU][IMPORT EXCEL][TAMBAH SISWA].
       (Tombol ARSIP sengaja tidak ada di sini: data siswa yang dihapus
        dipindah ke Pengaturan > Pengelolaan Sistem > TEMPAT SAMPAH,
        URL tetap admin/students/trash.)
       Semua elemen tinggi 38px; wrap rapi (bukan elemen terpotong) di layar sempit. */
    .action-bar-section {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem 1rem;
    }

    .action-search-form {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        flex: 1 1 380px;
        min-width: 0;
        margin: 0;
    }

    /* Input pencarian + ikon search = satu kesatuan, tidak pernah turun baris */
    .action-search-form .search-box-wrap {
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
    .action-search-form .search-box-wrap input.form-control {
        height: 38px;
        min-width: 0;
    }
    .action-search-form .search-box-wrap button.btn {
        height: 38px;
        flex: 0 0 auto;
    }

    .action-search-form .filter-box-wrap {
        flex: 0 1 200px;
        min-width: 150px;
        max-width: 200px;
        width: auto;
    }
    .action-search-form .filter-box-wrap select.form-select {
        height: 38px;
    }

    /* Deretan tombol aksi di kanan: tidak mengecil (teks tetap utuh) dan tidak menumpuk */
    .action-buttons-wrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        flex: 0 1 auto;
        margin-left: auto;
    }
    .action-buttons-wrap .btn-solid-pill {
        flex: 0 0 auto;
    }

    @media (max-width: 767.98px) {
        .action-search-form {
            flex: 1 1 100%;
        }
        .action-search-form .search-box-wrap {
            flex: 1 1 calc(100% - 0.5rem);
        }
        .action-search-form .filter-box-wrap {
            flex: 1 1 100%;
            max-width: none;
        }
        .action-buttons-wrap {
            flex: 1 1 100%;
            margin-left: 0;
        }
        .action-buttons-wrap .btn-solid-pill {
            flex: 1 1 calc(50% - 0.25rem);
        }
    }

    .btn-solid-pill,
    button.btn-solid-pill,
    a.btn-solid-pill {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 0.4rem;
        height: 38px !important;
        border-radius: 6px !important;
        border: none !important;
        font-size: 0.85rem;
        font-weight: 600;
        color: #ffffff;
        white-space: nowrap;
        padding: 0 1rem;
        transition: filter 0.15s ease, transform 0.1s ease;
        box-shadow: none !important;
        letter-spacing: 0.03em;
        font-family: 'Poppins', 'Roboto', sans-serif;
        /* === UKURAN 4 TOMBOL ACTION BAR DISAMAkan ===
           Tanpa min-width, tiap tombol lebarnya mengikuti panjang teksnya
           sendiri, sehingga "Cetak Kartu" lebih kecil dari "Tambah Siswa".
           Teks terpanjang adalah "TAMBAH SISWA" (12 karakter, uppercase,
           font 0.85rem + letter-spacing 0.03em + padding 0 1rem) yang
           membutuhkan sekitar 138px. min-width 150px sengaja sedikit lebih
           besar agar keempat tombol benar-benar sama persis (tidak ada satu
           pun yang melebihi nilai ini), dan teks tetap rata tengah.
           Di layar kecil (< 1024px) aturan layout bersama membuat keempat
           tombol full-width, jadi ukurannya tetap seragam. */
        min-width: 150px;
    }
    .btn-solid-pill:hover {
        filter: brightness(0.94);
        color: #ffffff;
    }
    .btn-solid-pill:active {
        transform: scale(0.98);
    }
    .btn-solid-green { background-color: #16a34a; }
    .btn-solid-blue { background-color: #2563eb; }
    .btn-solid-red { background-color: #dc2626; }

    /* Row Action Buttons */
    .btn-row-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        border: none;
        border-radius: 6px !important;
        padding: 0.32rem 0.7rem;
        font-size: 0.78rem;
        font-weight: 600;
        color: #ffffff;
        white-space: nowrap;
        transition: filter 0.15s ease;
        box-shadow: none !important;
        letter-spacing: 0.03em;
        font-family: 'Poppins', 'Roboto', sans-serif;
    }
    .btn-row-action:hover {
        filter: brightness(0.94);
        color: #ffffff;
    }
    .btn-row-action.action-detail { background-color: #16a34a; }
    .btn-row-action.action-edit { background-color: #f59e0b; }
    .btn-row-action.action-delete { background-color: #dc2626; }

    /* Search bar & dropdown filter */
    .search-input-wrap input.form-control,
    .filter-select-wrap select.form-select,
    .search-filter-group input.form-control,
    .search-filter-group select.form-select,
    .search-filter-group .btn {
        border-radius: 6px !important;
    }
    .search-input-wrap .input-group input.form-control,
    .search-filter-group .input-group input.form-control {
        border-radius: 6px 0 0 6px !important;
    }
    .search-input-wrap .input-group button.btn,
    .search-filter-group .input-group button.btn {
        border-radius: 0 6px 6px 0 !important;
    }

    /* ==========================================================================
       LAYOUT KARTU DATA SISWA - SAMA PERSIS dengan Catatan Kehadiran & Rekap
       - kartu tabel mengisi sisa tinggi layar sampai mentok bawah,
       - hanya .table-responsive yang jadi area scroll vertikal (scrollbar kanan),
       - header kolom sticky & bersih (latar pekat, di atas baris data),
       - pagination berada DI DALAM area scroll, tepat di bawah baris ke-100,
       - bar pencarian + tombol aksi ada di luar area scroll (tidak ikut scroll),
       - margin-bottom kartu dipaksa 0 supaya tidak ada ruang kosong di bawah.
       ========================================================================== */
    #daftar-siswa {
        display: flex;
        flex-direction: column;
        /* Tinggi kartu = sisa tinggi layar, jadi kartu MENTOK ke bawah
           (tanpa ruang kosong) dan hanya .table-responsive yang men-scroll.
           Angka offset dihitung dari tata letak layout bersama:
             wrapper padding (10+10, hanya >=768)
           + padding main atas/bawah
           + tinggi header halaman + margin bawah header
           + padding bawah kontainer isi (.flex-1 = 1rem, aturan bersama
             "JARAK BAWAH KONTEN SERAGAM" di layouts/app.blade.php)
           Offset >=1024 :
             10+24+45+16+16+24        = 145.6px = 9.1rem
           Fallback di bawah 1024px (tabel tampil sebagai kartu, tinggi
           kartu dipaksa `auto` oleh aturan layout bersama):
             768-1023: 10+24+72+16+96   = 228px = 14.25rem
             640-767 : 0+0+68+16+96      = 180px = 11.25rem
             <640    : 0+0+68+10+96      = 174px = 10.9rem
           (96 = padding bawah main di layar kecil: bottom-nav 56 + 40.)
           Pola & tujuan sama dengan #daftar-kehadiran (13rem) dan
           #daftar-rekap (17rem) - cuma angkanya menyesuaikan isi halaman ini. */
        height: calc(100dvh - 9.1rem);
        min-height: 20rem;
        margin-bottom: 0 !important;   /* tidak ada ruang kosong di bawah card */
    }

    @media (max-width: 1023.98px) {
        #daftar-siswa {
            height: calc(100dvh - 14.25rem);
        }
    }

    @media (max-width: 767.98px) {
        #daftar-siswa {
            height: calc(100dvh - 11.25rem);
        }
    }

    @media (max-width: 639.98px) {
        #daftar-siswa {
            height: calc(100dvh - 10.9rem);
        }
    }

    /* Bar aksi (pencarian + tombol) TIDAK ikut mengecil/terpotong: berada di
       luar area scroll, jadi selalu terlihat penuh. */
    #daftar-siswa > .p-3 {
        flex: 0 0 auto;
    }

    #daftar-siswa .table-responsive {
        flex: 1 1 auto;
        min-height: 0;
        /* lepas max-height bawaan layout (65vh) supaya tinggi ikut flex */
        max-height: none !important;
    }

    /* Header sticky - bersih, baris tidak menimpa / tidak bocor melewatinya. */
    #daftar-siswa .table-responsive > table > thead { z-index: 5 !important; }
    #daftar-siswa .table-responsive > table > thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 5 !important;
        background-color: #f8fafc !important;
    }

    /* Baris data selalu berlapis di bawah header. */
    #daftar-siswa .table-responsive > table > tbody > tr > td {
        position: relative;
        z-index: 1;
    }

    /* Gaya pagination TIDAK ditulis di sini: memakai blok bersama
       .kehadiran-pagination* di layouts/app.blade.php (satu sumber gaya,
       sama persis dengan Catatan Kehadiran & Rekap). */
</style>
@endpush

@section('content')

    {{-- Notifikasi: [ .flash-notice-body (ikon + teks) ] [ tombol X ].
         Tombol X center vertikal oleh CSS notifikasi global di layout. --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    {{-- Notifikasi import sebagian berhasil (kuning/oranye) --}}
    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-error-circle fs-5 me-2 text-warning'></i>
                <span>{{ session('warning') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    {{-- Rincian baris Excel yang dilewati --}}
    @if(session('import_errors') && count(session('import_errors')) > 0)
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center mb-1">
                <i class='bx bx-error-circle fs-5 me-2 text-warning'></i>
                <strong>Baris yang dilewati:</strong>
            </div>
            <ul class="mb-0 ps-3 small">
                @foreach(collect(session('import_errors'))->take(10) as $err)
                    <li>{{ $err }}</li>
                @endforeach
                @if(count(session('import_errors')) > 10)
                    <li>… dan {{ count(session('import_errors')) - 10 }} baris lainnya.</li>
                @endif
            </ul>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    <!-- Form Hapus Semua Siswa (Hidden, dipanggil via confirmDeleteAllStudents()).
         Ditempatkan DI LUAR blok script, persis seperti pola halaman Data
         Guru & Data Kelas. Dua input konfirmasi ikut dikirim karena
         controller memvalidasinya di server, bukan hanya lewat checkbox. -->
    @if(Auth::check() && Auth::user()->role === 'admin')
    <form id="deleteAllStudentsForm" action="{{ panel_route('students.destroy-all-active') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="confirm_active" value="1">
        <input type="hidden" name="confirm_all" value="1">
    </form>
    @endif

    <!-- KARTU UTAMA DATA SISWA -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white" id="daftar-siswa">
        
        <!-- Action Bar & Filter -->
        <div class="p-3 p-md-4 border-bottom border-gray-100 bg-white">
            <div class="action-bar-section">
                
                <!-- Sisi Kiri: Form Pencarian & Dropdown Kelas (satu baris sejajar) -->
                <form method="GET" action="{{ panel_route('students.index') }}" class="action-search-form">
                    <!-- Search Bar: input + ikon search satu kesatuan (tidak turun baris) -->
                    <div class="input-group search-box-wrap">
                        <input type="text" 
                               name="search" 
                               id="studentSearchInput"
                               class="form-control border-secondary-subtle border-end-0 shadow-none ps-3" 
                               placeholder="Cari nama atau NIS..." 
                               value="{{ request('search') }}"
                               aria-label="Cari nama atau NIS"
                               autocomplete="off"
                               style="font-size: 0.85rem; letter-spacing: 0.03em;">
                        <button class="btn bg-white border border-secondary-subtle border-start-0 shadow-none text-secondary px-3" type="submit" title="Cari" aria-label="Cari" style="height: 38px;">
                            <i class='bx bx-search fs-6'></i>
                        </button>
                    </div>

                    <!-- Dropdown Filter Kelas: ganti kelas -> langsung memuat ulang (pola Catatan Kehadiran) -->
                    <div class="filter-box-wrap">
                        <select name="class_id" id="studentClassFilter" onchange="this.form.submit()" class="form-select border-secondary-subtle shadow-none fw-normal w-100" style="font-size: 0.82rem; letter-spacing: 0.02em;" aria-label="Pilih kelas">
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                                Kelas {{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                </form>

                <!-- Sisi Kanan: Deretan Tombol Aksi -->
                <div class="action-buttons-wrap">
                    <button type="button" class="btn-solid-pill btn-solid-blue" data-bs-toggle="modal" data-bs-target="#printCardsModal">
                        <span>Cetak Kartu</span>
                    </button>

                    @if(Auth::check() && Auth::user()->role === 'admin')
                    <button type="button" class="btn-solid-pill btn-solid-green" data-bs-toggle="modal" data-bs-target="#importModal">
                        <span>Import Excel</span>
                    </button>

                    <button type="button" class="btn-solid-pill btn-solid-blue" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <span>Tambah Siswa</span>
                    </button>

                    <!-- Hapus Semua Siswa (merah) - di sebelah Tambah Siswa,
                         sama seperti halaman Data Guru & Data Kelas.
                         Memakai route students.destroy-all-active: siswa aktif
                         di-soft-delete sehingga bisa dipulihkan dari
                         TEMPAT SAMPAH di Pengaturan. -->
                    <button type="button" class="btn-solid-pill btn-solid-red" title="Hapus Semua Data Siswa" onclick="confirmDeleteAllStudents()">
                        <span>Hapus</span>
                    </button>
                    @endif
                </div>

            </div>
        </div>

        <!-- Tabel Data Siswa -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 45px;">No</th>
                        <th class="text-center" style="width: 80px;">Kelas</th>
                        <th class="text-center">NIS</th>
                        <th class="text-start indent-nama">Nama Siswa</th>
                        <th class="text-start">Jenis Kelamin</th>
                        <th class="text-start">Nama Wali</th>
                        <th class="text-center" style="min-width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                    @php
                        $rawJk = strtolower(trim($student->jenis_kelamin ?? $student->gender ?? ''));
                        $isMale = in_array($rawJk, ['laki-laki', 'laki - laki', 'l', 'pria', 'male']);
                    @endphp
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td data-label="No" class="text-center">{{ $loop->iteration + ($students->firstItem() ? $students->firstItem() - 1 : 0) }}</td>
                        <td data-label="Kelas" class="text-center">{{ $student->schoolClass->name ?? $student->kelas ?? '-' }}</td>
                        <td data-label="NIS" class="text-center font-monospace">{{ $student->nis }}</td>
                        <td data-label="Nama Siswa" class="text-start indent-nama fw-semibold text-dark">{{ $student->nama ?? $student->name }}</td>
                        <td data-label="Jenis Kelamin" class="text-start">{{ $isMale ? 'Laki-laki' : 'Perempuan' }}</td>
                        <td data-label="Nama Wali" class="text-start text-secondary">{{ $student->nama_orang_tua ?? $student->nama_wali ?? '-' }}</td>
                        <td data-label="Aksi" class="text-center">
                            <div class="crud-center-wrapper">
                                <a href="{{ panel_route('students.show', $student->id) }}" class="btn-row-action action-detail" title="Detail">
                                    Detail
                                </a>
                                @if(Auth::check() && Auth::user()->role === 'admin')
                                <a href="{{ panel_route('students.edit', $student->id) }}" class="btn-row-action action-edit" title="Edit">
                                    Edit
                                </a>
                                <button type="button" class="btn-row-action action-delete" title="Hapus" onclick="confirmDeleteStudent('{{ $student->id }}', '{{ addslashes($student->nama ?? $student->name) }}')">
                                    Hapus
                                </button>
                                <form id="deleteStudentForm-{{ $student->id }}" action="{{ panel_route('students.destroy', $student->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class='bx bx-info-circle fs-2 d-block mb-2'></i>
                            @if(request('search') || request('class_id'))
                                Tidak ada data siswa yang cocok dengan pencarian.
                            @else
                                BELUM ADA DATA SISWA YANG TERDAFTAR.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

        <!-- ==========================================================================
         PAGINASI DATA SISWA - TEKS MURNI (TANPA KONTAINER)
         Markup & gaya SAMA PERSIS dengan Catatan Kehadiran dan Rekap:
         "‹ Sebelumnya  1 2 3 4 5  Berikutnya ›" + "Menampilkan 1-100 dari 500 siswa".
         Gaya memakai kelas .kehadiran-pagination* dari blok bersama di
         layouts/app.blade.php (bukan salinan baru, bukan helper lama).

         POSISI: blok ini sengaja DI DALAM .table-responsive (area scroll),
         tepat di bawah baris terakhir, jadi ikut ter-scroll dan baru terlihat
         setelah user menggulir sampai baris ke-100.
         ======================================================================= -->
        @php
            $pgCurrent = $students->currentPage();
            $pgLast = $students->lastPage();
            $pgHash = '#daftar-siswa';

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
            <nav class="kehadiran-pagination" id="siswa-pagination" aria-label="Navigasi halaman data siswa">
                <ul class="kehadiran-pagination-list">
                    @if ($pgCurrent <= 1)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">&lsaquo; Sebelumnya</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $students->previousPageUrl() }}{{ $pgHash }}" rel="prev">&lsaquo; Sebelumnya</a></li>
                    @endif

                    @foreach ($pgPages as $pgItem)
                        @if ($pgItem === '...')
                            <li><span class="kehadiran-pagination-ellipsis" aria-hidden="true">&hellip;</span></li>
                        @elseif ($pgItem === $pgCurrent)
                            <li><span class="kehadiran-pagination-page is-active" aria-current="page">{{ $pgItem }}</span></li>
                        @else
                            <li><a class="kehadiran-pagination-page" href="{{ $students->url($pgItem) }}{{ $pgHash }}">{{ $pgItem }}</a></li>
                        @endif
                    @endforeach

                    @if ($pgCurrent >= $pgLast)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">Berikutnya &rsaquo;</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $students->nextPageUrl() }}{{ $pgHash }}" rel="next">Berikutnya &rsaquo;</a></li>
                    @endif
                </ul>

                <p class="kehadiran-pagination-info">
                    Menampilkan {{ $students->firstItem() ?? 0 }}&ndash;{{ $students->lastItem() ?? 0 }}
                    dari {{ $students->total() }} siswa
                </p>
            </nav>
        @endif
        </div><!-- /.table-responsive : penutup area scroll, pagination ikut di dalam -->
    </div>

<!-- MODAL CETAK KARTU MASSAL -->
<div class="modal fade" id="printCardsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Cetak Kartu Presensi Massal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ panel_route('students.print-cards') }}" method="POST">
                @csrf
                <div class="modal-body py-3">
                    <p class="text-secondary small mb-3">
                        Pilih opsi pencetakan kartu presensi siswa. Berkas PDF kartu akan siap dicetak pada kertas A4 landscape (10 kartu per lembar, ukuran kartu CR80 portrait).
                    </p>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pilihan Cetak <span class="text-danger">*</span></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="print_type" id="printTypeClass" value="class" checked onchange="togglePrintScope()">
                            <label class="form-check-label fw-medium small" for="printTypeClass">
                                Cetak per Kelas
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="print_type" id="printTypeAll" value="all" onchange="togglePrintScope()">
                            <label class="form-check-label fw-medium small" for="printTypeAll">
                                Cetak Semua Siswa Aktif
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="classSelectContainer">
                        <label class="form-label small fw-semibold">Pilih Kelas</label>
                        <select name="class_id" id="modalClassId" class="form-select rounded-3">
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}">Kelas {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-0" id="printInfoBox">
                        <i class='bx bx-info-circle me-1'></i> Kartu akan digenerate dengan QR Code presensi siswa secara otomatis (tanpa pas foto).
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-download-blue px-4" data-download>Generate &amp; Unduh PDF Kartu</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(is_admin())
<!-- MODAL TAMBAH SISWA -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Tambah Siswa Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ panel_route('students.store') }}" method="POST">
                @csrf
                <div class="modal-body py-3">
                    @if($errors->any() && !$errors->has('file_excel'))
                    <div class="alert alert-danger py-2 px-3 small mb-3 border-0 rounded-3" role="alert">
                        <i class='bx bx-error-circle me-1'></i> Data belum bisa disimpan. Periksa isian yang bertanda merah.
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Siswa <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control rounded-3 @error('name') is-invalid @enderror" placeholder="Masukkan nama lengkap siswa" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">NIS <span class="text-danger">*</span></label>
                            <input type="text" name="nis" class="form-control rounded-3 @error('nis') is-invalid @enderror" placeholder="Nomor Induk Siswa" value="{{ old('nis') }}" inputmode="numeric" pattern="[0-9]*" maxlength="30" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
                            @error('nis')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">NISN <span class="text-muted">(Opsional)</span></label>
                            <input type="text" name="nisn" class="form-control rounded-3 @error('nisn') is-invalid @enderror" placeholder="10 digit angka" value="{{ old('nisn') }}" inputmode="numeric" pattern="[0-9]*" maxlength="10" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                            @error('nisn')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Kelas <span class="text-danger">*</span></label>
                            <select name="school_class_id" class="form-select rounded-3 @error('school_class_id') is-invalid @enderror" required>
                                <option value="">Pilih Kelas</option>
                                @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ old('school_class_id') == $c->id ? 'selected' : '' }}>Kelas {{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('school_class_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select rounded-3 @error('gender') is-invalid @enderror" required>
                                <option value="Laki-laki" {{ old('gender') == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                <option value="Perempuan" {{ old('gender') == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                            </select>
                            @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tempat Lahir</label>
                            <input type="text" name="birth_place" class="form-control rounded-3 @error('birth_place') is-invalid @enderror" placeholder="Contoh: Bandung" value="{{ old('birth_place') }}">
                            @error('birth_place')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal Lahir</label>
                            <input type="date" name="birth_date" class="form-control rounded-3 @error('birth_date') is-invalid @enderror" value="{{ old('birth_date') }}">
                            @error('birth_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Nama Orang Tua / Wali</label>
                            <input type="text" name="parent_name" class="form-control rounded-3 @error('parent_name') is-invalid @enderror" placeholder="Nama Orang Tua" value="{{ old('parent_name') }}">
                            @error('parent_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">No. WhatsApp / HP</label>
                            <input type="text" name="parent_phone" class="form-control rounded-3 @error('parent_phone') is-invalid @enderror" placeholder="08xxxxxxxxxx" value="{{ old('parent_phone') }}" inputmode="numeric" pattern="[0-9]*" maxlength="15" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                            @error('parent_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Alamat Lengkap</label>
                        <textarea name="address" class="form-control rounded-3 @error('address') is-invalid @enderror" rows="2" placeholder="Alamat tempat tinggal siswa">{{ old('address') }}</textarea>
                        @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Simpan Siswa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL IMPORT EXCEL -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0">
                <h5 class="fw-bold mb-0">Import Data Siswa Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ panel_route('students.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    @if($errors->any() && $errors->has('file_excel'))
                    <div class="alert alert-danger py-2 px-3 small mb-3 border-0 rounded-3" role="alert">
                        <i class='bx bx-error-circle me-1'></i> Data belum bisa disimpan. Periksa isian yang bertanda merah.
                    </div>
                    @endif

                    <div class="p-3 bg-light rounded-3 small text-secondary mb-3">
                        <div class="mb-2">
                            Format kolom file Excel: <strong>NIS, NISN, Nama Lengkap, Kelas, Jenis Kelamin, Tempat Lahir, Tanggal Lahir, Alamat, Nama Wali, No WhatsApp</strong> (.xlsx atau .csv)
                        </div>
                        <a href="{{ panel_route('students.template') }}" class="btn-download-green w-100" data-download>Unduh Template Excel</a>
                    </div>
                    <label class="form-label small fw-semibold">Pilih File Excel</label>
                    <input type="file" name="file_excel" class="form-control rounded-3 @error('file_excel') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                    @error('file_excel')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-download-green px-4" data-import>Unggah &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    function togglePrintScope() {
        const isClass = document.getElementById('printTypeClass').checked;
        const classContainer = document.getElementById('classSelectContainer');
        const classSelect = document.getElementById('modalClassId');
        
        if (isClass) {
            classContainer.style.display = 'block';
            classSelect.disabled = false;
        } else {
            classContainer.style.display = 'none';
            classSelect.disabled = true;
        }
    }

    function confirmDeleteStudent(id, name) {
        confirmUniversalDelete({
            title: 'Hapus Data Siswa?',
            html: `Siswa <strong>${name}</strong> akan dihapus. Data bisa dipulihkan dari Tempat Sampah di Pengaturan.`,
            confirmText: 'Hapus',
            cancelText: 'Tidak',
            onConfirm: function() {
                // Dicek null dulu: kalau form tidak ada,error ini akan
                // dilempar sebagai TypeError dan tombol Hapus akan terlihat
                // "tidak berfungsi" tanpa pesan apa pun.
                const form = document.getElementById(`deleteStudentForm-${id}`);
                if (!form) {
                    console.error('HAPUS: form deleteStudentForm-' + id + ' tidak ditemukan. Muat ulang halaman.');
                    return;
                }
                const qs = window.location.search;
                if (qs && form.action.indexOf('?') === -1) {
                    form.action = form.action + qs;
                }
                form.submit();
            }
        });
    }

    // Konfirmasi Hapus Semua Siswa Massal.
    // Memakai confirmUniversalDelete() dari layout bersama supaya tema,
    // ukuran, animasi, dan tombol nonaktif-hingga-checkbox-nya SAMA PERSIS
    // dengan dialog hapus di halaman lain.
    function confirmDeleteAllStudents() {
        confirmUniversalDelete({
            title: 'Hapus Seluruh Data Siswa?',
            icon: 'bx-error-circle',
            html: 'Seluruh data siswa aktif akan dihapus dan dipindahkan ke <strong>Tempat Sampah</strong> di Pengaturan.',
            confirmText: 'Hapus Semua',
            cancelText: 'Tidak',
            checks: [
                'Saya memahami data akan dipindahkan ke Tempat Sampah dan dapat dipulihkan.',
                'Saya yakin ingin menghapus semua data siswa aktif.'
            ],
            onConfirm: function () {
                document.getElementById('deleteAllStudentsForm').submit();
            }
        });
    }

    // Buka kembali modal form (Tambah Siswa / Import Excel) bila validasi
    // server gagal, supaya isian lama (old) dan pesan error tetap terlihat.
    (function () {
        var hasError = {{ $errors->any() ? 'true' : 'false' }};
        if (!hasError) return;
        var targetId = @json($errors->has('file_excel') ? 'importModal' : 'addStudentModal');
        var modalEl = document.getElementById(targetId);
        if (modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    })();
</script>
@endpush