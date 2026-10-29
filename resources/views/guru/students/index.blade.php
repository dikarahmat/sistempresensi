@extends('layouts.app')

@section('title', __('Data Siswa'))
@section('page_title', __('Data Siswa'))
@section('page_subtitle', __('Kelola data siswa pendidik'))

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

    /* Zebra Striping: pakai gaya Data Siswa yang sama */
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
        table-layout: fixed;
        min-width: 860px;
        margin-bottom: 0;
    }

    .table-zebra-custom th,
    .table-zebra-custom td {
        vertical-align: middle;
        padding-left: 12px !important;
        padding-right: 12px !important;
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

@media (max-width: 767px) {
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        .table-zebra-custom {
            table-layout: auto;
            border-collapse: collapse;
        }

        .table-zebra-custom th,
        .table-zebra-custom td {
            vertical-align: middle;
            padding: 10px 6px !important;
            font-size: 11px !important;
        }

        .table-zebra-custom th.no, .table-zebra-custom td.no { display: none; }
        .table-zebra-custom th.nama, .table-zebra-custom td.nama { width: auto; text-align: left; white-space: normal; word-break: break-word; }
        .table-zebra-custom th.jenis_kelamin, .table-zebra-custom td.jenis_kelamin { width: 1%; text-align: center; }
        .table-zebra-custom th.aksi, .table-zebra-custom td.aksi { width: 1%; white-space: nowrap; }

        .table-zebra-custom thead th.no,
        .table-zebra-custom thead th.lulus,
        .table-zebra-custom thead th.jumlah_sks,
        .table-zebra-custom thead th.jenis_kelamin,
        .table-zebra-custom thead th.aksi { text-align: center; }
        .table-zebra-custom thead th.nama { text-align: left; }

        .table-zebra-custom tbody td.no,
        .table-zebra-custom tbody td.lulus,
        .table-zebra-custom tbody td.jumlah_sks,
        .table-zebra-custom tbody td.jenis_kelamin,
        .table-zebra-custom tbody td.aksi { text-align: center; }
        .table-zebra-custom tbody td.nama { text-align: left; }

        .table-zebra-custom tbody td.aksi {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        .table-zebra-custom tbody td.aksi .crud-center-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .nama-siswa-wrapper {
            padding-left: 0 !important;
            padding-right: 0 !important;
            text-align: left !important;
        }
    }

    /* Header tabel Data Siswa: nilai DESKTOP (>= 1024px) tidak berubah. */
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

    /* TABLET & HP (<= 1023.98px): header jadi LATAR PUTIH + teks gelap
       semibold + SATU garis tipis (sebelumnya latar abu #f8fafc). Desktop
       >= 1024px sengaja TIDAK disentuh. */
    @media (max-width: 1023.98px) {
        .table-zebra-custom thead th {
            background-color: #ffffff !important;
            color: var(--clean-ink) !important;
            border-bottom: 1px solid var(--clean-line) !important;
        }
    }

    /* ===== Action Bar Layout =====
       Satu baris rata tengah: [input cari][ikon search][dropdown kelas]
       ..... [CETAK KARTU]. (Tanpa Import, Tambah, Hapus.)
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

    .btn-solid-pill,
    button.btn-solid-pill,
    a.btn-solid-pill {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 0.4rem;
        height: 38px !important;
        border-radius: var(--clean-radius) !important;
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
        min-width: 150px;
    }
    .btn-solid-pill:hover {
        filter: brightness(0.94);
        color: #ffffff;
    }
    .btn-solid-pill:active {
        transform: scale(0.98);
    }
    .btn-solid-blue { background-color: #2563eb; }

    /* ===== Data Siswa: aturan MOBILE (max-width: 767px) ===== */
    @media (max-width: 767px) {
        .table-responsive {
            width: 100% !important;
            display: block !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
            max-height: none !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior-x: contain !important;
        }
        .table-zebra-custom {
            table-layout: fixed !important;
            min-width: 650px !important;
        }
        .table-zebra-custom col:nth-child(1)  { width: 40px !important; }
        .table-zebra-custom col:nth-child(2)  { width: 170px !important; }
        .table-zebra-custom col:nth-child(3)  { width: 220px !important; }
        .table-zebra-custom col:nth-child(4)  { width: 110px !important; }
        .table-zebra-custom col:nth-child(5)  { width: 130px !important; }
        .table-zebra-custom col:nth-child(6)  { width: 140px !important; }
        .table-zebra-custom thead th:nth-child(1)  { width: 40px !important; text-align: center !important; }
        .table-zebra-custom thead th:nth-child(2)  { width: 170px !important; text-align: left !important; white-space: nowrap !important; }
        .table-zebra-custom thead th:nth-child(3)  { width: 220px !important; text-align: left !important; white-space: normal !important; word-break: break-word !important; }
        .table-zebra-custom thead th:nth-child(4)  { width: 110px !important; text-align: center !important; }
        .table-zebra-custom thead th:nth-child(5)  { width: 130px !important; text-align: center !important; }
        .table-zebra-custom thead th:nth-child(6)  { width: 140px !important; text-align: center !important; }
        .table-zebra-custom tbody td:nth-child(1)  { width: 40px !important; text-align: center !important; }
        .table-zebra-custom tbody td:nth-child(2)  { width: 170px !important; text-align: left !important; white-space: nowrap !important; }
        .table-zebra-custom tbody td:nth-child(3)  { width: 220px !important; text-align: left !important; white-space: normal !important; word-break: break-word !important; }
        .table-zebra-custom tbody td:nth-child(4)  { width: 110px !important; text-align: center !important; }
        .table-zebra-custom tbody td:nth-child(5)  { width: 130px !important; text-align: center !important; }
        .table-zebra-custom tbody td:nth-child(6)  { width: 140px !important; text-align: center !important; }
        .table-zebra-custom th,
        .table-zebra-custom td {
            padding-left: 8px !important;
            padding-right: 8px !important;
        }
    }

    /* ===== Data Siswa: kolom Nama LENGKAP SISWA rata kiri di DESKTOP =====
       Hanya berlaku di lebar >= 768px; mobile (di bawah 768px) tidak terpengaruh. */
    @media (min-width: 768px) {
        .table-zebra-custom thead th:nth-child(3),
        .table-zebra-custom tbody td:nth-child(3) {
            text-align: left !important;
            padding-left: 225px !important;
        }
        .table-zebra-custom tbody td:nth-child(3) {
            white-space: normal !important;
        }
    }
</style>
@endpush

@section('content')

    <!-- Kartu utama Data Siswa -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden md:mb-4 bg-white" id="daftar-guru">

        <!-- Action Bar & Search -->
        <div class="p-3 p-md-4 border-bottom border-gray-100 bg-white">
            <div class="action-bar-section d-flex flex-column flex-md-row justify-content-between">

                <!-- Sisi Kiri: Form Pencarian -->
                <form method="GET" action="{{ url()->current() }}" class="action-search-form">
                    <div class="input-group search-box-wrap">
                        <input type="text"
                               name="search"
                               id="studentSearchInput"
                               class="form-control shadow-none"
                               placeholder="{{ __('Cari nama atau NIP...') }}"
                               value="{{ request('search') }}"
                               aria-label="{{ __('Cari nama atau NIP') }}"
                               autocomplete="off"
                               style="height: 38px; font-size: 0.85rem;">
                        <button class="btn shadow-none" type="submit" title="{{ __('Cari') }}" aria-label="{{ __('Cari') }}">
                            <i class='bx bx-search fs-6'></i>
                        </button>
                    </div>
                </form>

                <!-- Sisi Kanan: Deretan Tombol Aksi -->
                <div class="action-buttons-wrap">
                    <button type="button" class="btn-solid-pill btn-solid-blue" data-bs-toggle="modal" data-bs-target="#printCardsModal">
                        <span>{{ __('Cetak Kartu') }}</span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Tabel Data Siswa: read-only, kolom NO, NISN, NAMA LENGKAP SISWA, JENIS KELAMIN, KELAS, NO. TELEPON / WA, AKSI(ikon mata) -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <colgroup>
                    <col style="width: 5%;">
                    <col style="width: 15%;">
                    <col style="width: 19%;">
                    <col style="width: 15%;">
                    <col style="width: 16%;">
                    <col style="width: 17%;">
                    <col style="width: 14%;">
                </colgroup>
                <thead class="bg-slate-50 border-b border-gray-100 text-blue-500">
                    <tr class="align-middle text-blue-500 text-xs font-bold uppercase tracking-wider border-b border-gray-100">
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 5%;">{{ __('No') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 15%;">{{ __('NIP') }}</th>
                        <th class="text-start indent-nama py-3 text-nowrap px-3 pe-4 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 19%;">{{ __('Nama Lengkap Guru') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 15%;">{{ __('Jenis Kelamin') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 16%;">{{ __('Tugas Kelas') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 17%;">{{ __('No. Telepon / WA') }}</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 14%;">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $index => $student)
                    @php
                        $assignedClass = $student->school_class_id ?? null;
                    @endphp
                    <tr class="align-middle {{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td data-label="{{ __('No') }}" class="text-center text-nowrap px-3">{{ $students->firstItem() + $index }}</td>
                        <td data-label="{{ __('NIP') }}" class="text-center text-nowrap px-3">{{ $student->nisn ?? '-' }}</td>
                        <td data-label="{{ __('Nama Guru') }}" class="text-start text-nowrap indent-nama fw-semibold text-dark px-3 pe-4">{{ $student->name }}</td>
                        <td data-label="{{ __('Jenis Kelamin') }}" class="text-center text-nowrap px-3">
                            {{ ($student->gender == 'Perempuan' || $student->gender == 'P') ? __('Perempuan') : __('Laki-laki') }}
                        </td>
                        <td data-label="{{ __('Tugas Kelas') }}" class="text-center text-nowrap px-3">
                            @if($assignedClass)
                                {{ __('Kelas ') }}{{ $assignedClass->name }}
                            @else
                                {{ __('Siswa Pekerja') }}
                            @endif
                        </td>
                        <td data-label="{{ __('No. Telepon / WA') }}" class="text-center text-nowrap px-3">
                            {{ $student->phone_number ?? $student->phone ?? '-' }}
                        </td>
                        <td data-label="{{ __('Aksi') }}" class="text-center text-nowrap px-3">
                            <div class="crud-center-wrapper">
                                <a href="{{ panel_route('students.show', $student->id) }}" class="btn-row-action action-detail" title="{{ __('Detail') }}" aria-label="{{ __('Detail') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="align-middle">
                        <td colspan="7" class="text-center py-5 text-muted text-nowrap empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            @if(request('search'))
                                {{ __('Tidak ada data siswa yang cocok dengan pencarian.') }}
                            @else
                                {{ __('BELUM ADA DATA SISWA YANG TERDAFTAR.') }}
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {!! render_compact_pagination($students, 'daftar-siswa') !!}
    </div>

    <!-- MODAL CETAK KARTU MASSAL (sama persis pola Data Siswa) -->
    <div class="modal fade" id="printCardsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0">{{ __('Cetak Kartu Presensi Massal') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('guru.print-cards') }}" method="POST">
                    @csrf
                    <div class="modal-body py-3">
                        <p class="text-secondary small mb-3">
                            {{ __('Pilih opsi cetak kartu presensi guru. Berkas PDF akan siap dicetak pada kertas A4 landscape.') }}
                        </p>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Pilihan Cetak') }} <span class="text-danger">*</span></label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="print_type" id="printTypeAll" value="all" checked onchange="togglePrintScope()">
                                <label class="form-check-label fw-medium small" for="printTypeAll">
                                    {{ __('Cetak Semua Siswa Aktif') }}
                                </label>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-2" id="printInfoBox">
                            <i class='bx bx-info-circle me-1'></i> {{ __('Kartu akan digenerate dengan QR Code presensi siswa secara otomatis.') }}
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                        <button type="submit" class="btn-download-blue btn-progress px-4" data-download data-dl-progress data-dl-progress-mode="persen" id="kartuUnduhBtn">{{ __('Generate & Unduh') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function togglePrintScope() {
        const isAll = document.getElementById('printTypeAll').checked;
        const infoBox = document.getElementById('printInfoBox');
        if (isAll) {
            infoBox.style.display = 'block';
        } else {
            infoBox.style.display = 'none';
        }
    }
</script>
@endpush
