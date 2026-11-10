@extends('layouts.app')

@section('title', __('Data Siswa'))
@section('page_title', __('Data Siswa'))

{{-- Kanvas dikunci setinggi satu layar; kartu tabel mengisi sisa tinggi kanvas
     sehingga tabelnya (bukan halaman) yang menggulir. Class ini diatur di layout bersama. --}}
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

    .field-error {
        min-height: 0;
        margin-top: 4px;
        font-size: 12px;
        line-height: 1.2;
        color: #dc2626;
    }
    .field-error:empty {
        display: none;
    }
    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 .15rem rgba(220, 38, 38, .12) !important;
    }

    /* Tambah Siswa Baru modal: jarak seragam grup kolom */
    #addStudentModal .modal-body > .mb-3 {
        margin-bottom: 14px !important;
    }
    #addStudentModal .modal-body > .mb-3:last-child {
        margin-bottom: 18px !important;
    }
    #addStudentModal .form-label {
        margin-bottom: 6px !important;
    }
    #addStudentModal .field-error {
        display: block;
        width: 100%;
        white-space: normal;
        word-break: break-word;
        margin-top: 4px;
        font-size: 12px;
        color: #dc2626;
        min-height: 0;
    }
    #addStudentModal .field-error:empty {
        display: none;
    }

    /* ==========================================================================
       FILTER BAR / ACTION BAR
       ========================================================================== */
    .action-bar-section {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem 1rem;
    }

    form.action-search-form {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    @media (min-width: 1024px) {
        form.action-search-form {
            flex-direction: row;
            align-items: center;
            flex: 1 1 auto !important;
            gap: 12px;
        }
    }

    /* Pembungkus Search dan Tombol Reset (Khusus Mobile di samping search) */
    .search-and-reset-wrapper {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        gap: 8px !important;
        width: 100% !important;
    }

    @media (min-width: 1024px) {
        .search-and-reset-wrapper {
            flex: 1 1 auto !important;
            width: auto !important;
        }
    }

    .search-and-reset-wrapper .search-box-wrap {
        flex: 1 1 auto !important;
        width: 100% !important;
        min-width: 0 !important;
        margin: 0 !important;
        display: flex !important;
    }

    .search-and-reset-wrapper .search-box-wrap .form-control {
        height: 38px !important;
        font-size: 0.85rem !important;
        min-width: 0 !important;
    }

    .search-and-reset-wrapper .search-box-wrap .btn {
        height: 38px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 0 0 auto !important;
    }

    .search-and-reset-wrapper .search-box-wrap .btn i,
    .search-and-reset-wrapper .search-box-wrap .btn .bx {
        font-size: 18px !important;
        line-height: 1;
    }

    /* Tombol Reset (Ukuran Proporsional: 38px) */
    .btn-reset {
        flex: 0 0 38px !important;
        width: 38px !important;
        height: 38px !important;
        min-width: 38px !important;
        max-width: 38px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-sizing: border-box !important;
        padding: 0 !important;
        background: #64748b !important;
        border: 1px solid #64748b !important;
        border-radius: 6px !important;
        color: #ffffff !important;
        text-decoration: none !important;
        cursor: pointer !important;
        outline: none !important;
        transition: background-color .15s ease, border-color .15s ease;
    }

    .btn-reset svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
        fill: none;
    }

    .btn-reset:hover,
    .btn-reset:focus-visible {
        background: #475569 !important;
        border-color: #475569 !important;
        color: #ffffff !important;
        text-decoration: none;
    }

    .btn-reset:focus-visible {
        box-shadow: 0 0 0 3px rgba(100, 116, 139, .3);
    }

    /* Dropdown Filter Kelas */
    .filter-box-wrap {
        width: 100% !important;
        margin: 0 !important;
    }

    @media (min-width: 1024px) {
        .filter-box-wrap {
            width: 190px !important;
            flex: 0 0 190px !important;
        }
    }

    .filter-box-wrap select.form-select {
        height: 38px !important;
        min-height: 38px !important;
        width: 100% !important;
        padding-left: 12px;
        padding-right: 32px;
        font-size: 0.85rem;
        box-sizing: border-box;
    }

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
        table-layout: fixed;
        min-width: 760px;
        margin-bottom: 0;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        display: block;
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

    .nama-siswa-wrapper {
        display: block;
        width: 100%;
        max-width: 100%;
        padding-left: 0;
        text-align: left;
        white-space: normal;
        word-break: normal;
        overflow-wrap: break-word;
        line-height: 1.35;
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
        .table-zebra-custom th.kelas, .table-zebra-custom td.kelas { width: 1%; text-align: center; }
        .table-zebra-custom th.nisn, .table-zebra-custom td.nisn { width: 1%; text-align: center; }
        .table-zebra-custom th.nama, .table-zebra-custom td.nama { width: auto; text-align: left; white-space: normal; word-break: break-word; }
        .table-zebra-custom th.jenis_kelamin, .table-zebra-custom td.jenis_kelamin { width: 1%; text-align: center; }
        .table-zebra-custom th.aksi, .table-zebra-custom td.aksi { width: 1%; white-space: nowrap; }

        .table-zebra-custom thead th.no, .table-zebra-custom thead th.kelas,
        .table-zebra-custom thead th.nisn, .table-zebra-custom thead th.jenis_kelamin,
        .table-zebra-custom thead th.aksi { text-align: center; }
        .table-zebra-custom thead th.nama { text-align: left; }

        .table-zebra-custom tbody td.no, .table-zebra-custom tbody td.kelas,
        .table-zebra-custom tbody td.nisn, .table-zebra-custom tbody td.jenis_kelamin,
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
        .table-zebra-custom { min-width: 640px; }
        .table-zebra-custom tbody td:nth-child(4) {
            white-space: normal !important;
            min-width: 180px;
            word-break: normal !important;
            overflow-wrap: break-word !important;
            text-align: left !important;
        }
        .table-zebra-custom thead th:nth-child(4) { text-align: left !important; }
    }

    /* Header tabel Data Siswa: nilai DESKTOP (>= 1024px) */
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

    /* TABLET & HP (<= 1023.98px): header latar putih */
    @media (max-width: 1023.98px) {
        .table-zebra-custom thead th {
            background-color: #ffffff !important;
            color: var(--clean-ink) !important;
            border-bottom: 1px solid var(--clean-line) !important;
        }
    }

    /* Row Action Buttons (Pure Icon) */
    .btn-row-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent !important;
        border-radius: 0 !important;
        padding: 0.2rem;
        font-size: 16px;
        color: inherit;
        white-space: nowrap;
        transition: color 0.15s ease, opacity 0.15s ease;
        box-shadow: none !important;
        cursor: pointer;
        font-family: 'Poppins', 'Roboto', sans-serif;
    }
    .btn-row-action svg {
        display: block;
        width: 20px;
        height: 20px;
    }
    @media (max-width: 767.98px) {
        .btn-row-action svg {
            width: 18px;
            height: 18px;
        }
    }
    .btn-row-action:hover {
        opacity: 0.8;
    }
    .btn-row-action:focus-visible {
        outline: 2px solid currentColor;
        outline-offset: 2px;
    }
    .btn-row-action.action-edit { color: #f59e0b; }
    .btn-row-action.action-edit:hover { color: #d97706; }
    .btn-row-action.action-delete { color: #dc2626; }
    .btn-row-action.action-delete:hover { color: #b91c1c; }

    /* Matikan Cetak Kartu saat data kosong */
    .btn-cetak-kartu.is-disabled {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        pointer-events: none;
        box-shadow: none !important;
    }

    /* Data Siswa: kolom Nama Siswa rata kiri di DESKTOP (>= 768px) */
    @media (min-width: 768px) {
        .table-zebra-custom thead th:nth-child(4),
        .table-zebra-custom tbody td:nth-child(4) {
            text-align: left !important;
            padding-left: 16px !important;
            padding-right: 16px !important;
        }
        .table-zebra-custom tbody td:nth-child(4) {
            white-space: normal !important;
            word-break: normal !important;
            overflow-wrap: break-word !important;
        }
        /* Kolom lain: satu baris, tidak ikut menumpuk */
        .table-zebra-custom tbody td:not(:nth-child(4)),
        .table-zebra-custom thead th:not(:nth-child(4)) {
            white-space: nowrap !important;
            text-align: center !important;
        }
        .table-zebra-custom tbody td:nth-child(4) .nama-siswa-wrapper {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
    }

    /* ==========================================================================
       ANTI-TABRAKAN (split screen / layar sempit)
       Selector diperkuat dengan #daftar-siswa agar tidak kalah oleh CSS layout.
       ========================================================================== */
    #daftar-siswa .action-bar-section {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: 10px 12px !important;
    }
    #daftar-siswa form.action-search-form {
        flex: 1 1 460px !important;
        min-width: 440px !important;
        width: auto !important;
        max-width: 100% !important;
    }
    #daftar-siswa .search-and-reset-wrapper .search-box-wrap { min-width: 160px !important; }
    #daftar-siswa .filter-box-wrap { min-width: 160px !important; flex-shrink: 0 !important; }
    #daftar-siswa .btn-reset { flex-shrink: 0 !important; }
    #daftar-siswa .action-buttons-wrap {
        flex: 0 0 auto !important;
        max-width: 100%;
        margin-left: auto !important;
    }
    #daftar-siswa .action-buttons-wrap .btn-solid-pill {
        flex: 0 0 auto;
        min-width: 0 !important;
        padding: 0 14px !important;
    }
    /* Lebar area kartu (bukan lebar layar) yang menentukan: saat sidebar terbuka
       atau split screen, tombol pindah ke baris sendiri sehingga search tidak terjepit. */
    .filter-bar-container { container-type: inline-size; container-name: filterbar; }
    @container filterbar (max-width: 1300px) {
        #daftar-siswa form.action-search-form {
            flex: 1 1 100% !important;
            width: 100% !important;
            min-width: 0 !important;
        }
        #daftar-siswa .action-buttons-wrap {
            flex: 1 1 100% !important;
            width: 100%;
            margin-left: 0 !important;
            justify-content: stretch;
        }
        #daftar-siswa .action-buttons-wrap .btn-solid-pill { flex: 1 1 0 !important; }
    }
    @media (max-width: 1023.98px) {
        #daftar-siswa form.action-search-form {
            flex: 1 1 100% !important;
            width: 100% !important;
            min-width: 0 !important;
        }
        #daftar-siswa .filter-box-wrap { flex: 1 1 100% !important; width: 100% !important; }
        #daftar-siswa .action-buttons-wrap {
            flex: 1 1 100% !important;
            width: 100%;
            justify-content: stretch;
        }
        #daftar-siswa .action-buttons-wrap .btn-solid-pill { flex: 1 1 0 !important; }
    }

    /* Deretan tombol aksi di kanan */
    .action-buttons-wrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        flex: 0 1 auto;
        margin-left: auto;
    }

    .btn-solid-pill,
    button.btn-solid-pill,
    a.btn-solid-pill {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
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
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-family: 'Poppins', 'Roboto', sans-serif;
        min-width: 150px;
        text-align: center;
    }
    .btn-solid-pill:hover {
        filter: brightness(0.94);
        color: #ffffff;
    }
    .btn-solid-pill:active {
        transform: scale(0.98);
    }

    /* Warna tombol aksi */
    .btn-solid-green { background-color: #059669 !important; color: #ffffff !important; }
    .btn-solid-blue { background-color: #2563eb !important; color: #ffffff !important; }
    .btn-solid-red { background-color: #dc2626 !important; color: #ffffff !important; }

    #btnImportSubmit:disabled {
        background-color: #059669 !important;
        color: #ffffff !important;
        opacity: .5 !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
        border: none !important;
    }

    #btnImportSubmit.is-saving,
    #btnImportSubmit.is-saving:disabled {
        opacity: 1 !important;
        cursor: default !important;
        pointer-events: none !important;
        background-color: #059669 !important;
        color: #ffffff !important;
    }

    #btnSaveStudent:disabled,
    #btnSaveStudent[disabled] {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
        box-shadow: none !important;
    }
    #btnSaveStudent:not(:disabled) {
        opacity: 1 !important;
        cursor: pointer !important;
        pointer-events: auto !important;
    }

    #btnImportSubmit .imp-bar {
        background: #064e3b !important;
        opacity: 1 !important;
    }
    #btnImportSubmit .imp-txt {
        color: #ffffff !important;
        font-weight: 700;
    }

    .import-summary {
        display: block;
        line-height: 1.4;
        word-wrap: break-word;
    }
    .import-summary + *,
    .alert .flash-notice-body .d-flex > div {
        min-width: 0;
    }

    .flash-notice-body .d-flex {
        align-items: center !important;
        line-height: 1;
    }
    .flash-notice-body .bx {
        flex-shrink: 0 !important;
        line-height: 1;
    }
    .flash-notice-body span,
    .flash-notice-body .import-summary {
        line-height: 1;
    }
    .alert-dismissible .btn-close {
        align-self: center !important;
    }

    #importAlertBox .alert.alert-success,
    .alert.alert-success {
        background-color: #dcfce7 !important;
        color: #166534 !important;
        border: 0 !important;
    }
    #importAlertBox .alert.alert-success .text-success,
    .alert.alert-success .text-success {
        color: #16a34a !important;
    }
    #importAlertBox .alert.alert-success .bx-check-circle,
    .alert.alert-success .bx-check-circle {
        color: #16a34a !important;
    }
    /* ==========================================================================
       FILTER BAR: dropdown kelas + tombol reset = satu kelompok
       - >= 768px : reset berada di KANAN dropdown (sama seperti Data Kelas)
       - <  768px : reset di samping kolom search, dropdown satu baris penuh
       Kelompok tidak bisa menyusut, jadi reset tidak pernah tertutup tombol aksi.
       ========================================================================== */
    #daftar-siswa .filter-reset-group {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        gap: 8px !important;
        min-width: 0;
    }
    #daftar-siswa .filter-reset-group .filter-box-wrap {
        margin: 0 !important;
        min-width: 0 !important;
    }
    #daftar-siswa .filter-reset-group .desktop-reset-btn {
        flex: 0 0 38px !important;
        width: 38px !important;
        min-width: 38px !important;
        height: 38px !important;
    }
    @media (min-width: 768px) {
        #daftar-siswa .search-and-reset-wrapper .btn-reset { display: none !important; }
        #daftar-siswa .filter-reset-group .desktop-reset-btn { display: flex !important; }
    }
    @media (max-width: 767.98px) {
        #daftar-siswa .filter-reset-group { flex: 1 1 100% !important; width: 100% !important; }
        #daftar-siswa .filter-reset-group .filter-box-wrap { flex: 1 1 100% !important; width: 100% !important; }
        #daftar-siswa .filter-reset-group .desktop-reset-btn { display: none !important; }
    }
    @media (min-width: 768px) and (max-width: 1023.98px) {
        /* Form tersusun vertikal: dropdown melebar, reset tetap di kanannya */
        #daftar-siswa .filter-reset-group { flex: 1 1 100% !important; width: 100% !important; }
        #daftar-siswa .filter-reset-group .filter-box-wrap { flex: 1 1 auto !important; width: auto !important; }
    }
    @media (min-width: 1024px) {
        /* Form satu baris: kelompok dropdown+reset lebar tetap; kalau sempit, turun ke baris bawah (tidak menimpa tombol aksi) */
        #daftar-siswa form.action-search-form { flex-wrap: wrap !important; }
        #daftar-siswa .search-and-reset-wrapper { flex: 1 1 200px !important; min-width: 160px !important; }
        #daftar-siswa .filter-reset-group { flex: 0 0 auto !important; width: auto !important; }
        #daftar-siswa .filter-reset-group .filter-box-wrap { flex: 0 0 190px !important; width: 190px !important; min-width: 160px !important; }
    }
    /* ==========================================================================
       FILTER BAR DATA SISWA: SATU BARIS SEJAJAR (seperti Data Kelas / Data Guru)
       Ukuran mengikuti lebar KARTU (container), bukan lebar layar, jadi tetap
       satu baris saat sidebar terbuka atau layar di-split.
       Urutan: [Search] [Dropdown Kelas] [Reset] .......... [Tombol aksi]
       Di bawah 601px (HP) tombol aksi turun ke baris sendiri.
       ========================================================================== */
    @container filterbar (min-width: 601px) {
        #daftar-siswa .action-bar-section {
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 12px !important;
        }
        #daftar-siswa .action-bar-section form.action-search-form {
            flex: 1 1 0 !important;
            width: auto !important;
            min-width: 0 !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 8px !important;
        }
        #daftar-siswa .action-bar-section .search-and-reset-wrapper {
            flex: 1 1 130px !important;
            width: auto !important;
            min-width: 84px !important;
        }
        /* Reset selalu di kanan dropdown pada mode satu baris */
        #daftar-siswa .action-bar-section .search-and-reset-wrapper .btn-reset { display: none !important; }
        #daftar-siswa .action-bar-section .filter-reset-group {
            flex: 0 1 158px !important;
            width: auto !important;
            min-width: 150px !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
        }
        #daftar-siswa .action-bar-section .filter-reset-group .filter-box-wrap {
            flex: 1 1 112px !important;
            width: auto !important;
            min-width: 104px !important;
        }
        #daftar-siswa .action-bar-section .filter-reset-group .desktop-reset-btn { display: flex !important; }
        #daftar-siswa .action-bar-section .action-buttons-wrap {
            flex: 0 0 auto !important;
            width: auto !important;
            margin-left: auto !important;
            flex-wrap: nowrap !important;
            justify-content: flex-end !important;
            gap: 6px !important;
        }
        #daftar-siswa .action-bar-section .action-buttons-wrap .btn-solid-pill {
            flex: 0 0 auto !important;
            min-width: 0 !important;
        }
    }
    /* Kartu sedang/sempit (split screen, sidebar terbuka): tombol aksi dipadatkan supaya muat satu baris */
    @container filterbar (min-width: 601px) and (max-width: 1300px) {
        #daftar-siswa .action-bar-section .action-buttons-wrap .btn-solid-pill {
            padding: 0 10px !important;
            font-size: 0.7rem !important;
            letter-spacing: 0 !important;
        }
    }
</style>
@endpush

@section('content')

    @if(session('success'))
    <div class="alert alert-success flash-server alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2 text-success flex-shrink-0'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('danger'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-trash fs-5 me-2 text-danger flex-shrink-0'></i>
                <span>{{ session('danger') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-x-circle fs-5 me-2 text-danger flex-shrink-0'></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('deleted'))
    <div class="alert alert-danger alert-deleted alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-trash fs-5 me-2 text-danger flex-shrink-0'></i>
                <span>{{ session('deleted') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-error-circle fs-5 me-2 text-warning flex-shrink-0'></i>
                <span>{{ session('warning') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')) > 0)
    <div class="alert alert-warning alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center mb-1">
                <i class='bx bx-error-circle fs-5 me-2 text-warning flex-shrink-0'></i>
                <strong>{{ __('Baris yang dilewati:') }}</strong>
            </div>
            <ul class="mb-0 ps-3 small">
                @foreach(collect(session('import_errors'))->take(10) as $err)
                    <li>{{ $err }}</li>
                @endforeach
                @if(count(session('import_errors')) > 10)
                    <li>{{ __('… dan :count baris lainnya.', ['count' => count(session('import_errors')) - 10]) }}</li>
                @endif
            </ul>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    <form id="deleteAllStudentsForm" action="{{ route('admin.students.destroy-all-active') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="confirm_active" value="1">
        <input type="hidden" name="confirm_all" value="1">
    </form>

    <div id="importAlertBox"></div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden md:mb-4 bg-white" id="daftar-siswa">
        <div class="p-3 p-md-4 border-bottom border-gray-100 bg-white filter-bar-container">
            <div class="action-bar-section d-flex flex-column flex-md-row justify-content-between">
                <form method="GET" action="{{ panel_route('students.index') }}" class="action-search-form" id="studentSearchForm">
                    <!-- Search & Tombol Reset (sebaris, sama seperti halaman Data Guru) -->
                    <div class="search-and-reset-wrapper">
                        <div class="input-group search-box-wrap">
                            <input type="text"
                                   name="search"
                                   id="studentSearchInput"
                                   class="form-control shadow-none"
                                   placeholder="{{ __('Cari nama atau NISN...') }}"
                                   value="{{ request('search') }}"
                                   aria-label="{{ __('Cari nama atau NISN') }}"
                                   autocomplete="off">
                            <button class="btn shadow-none" type="submit" title="{{ __('Cari') }}" aria-label="{{ __('Cari') }}">
                                <i class='bx bx-search fs-6'></i>
                            </button>
                        </div>

                        <!-- Tombol Reset Mobile (di samping search, hanya layar < 768px) -->
                        <a href="{{ panel_route('students.index') }}" class="btn-reset" role="button" title="{{ __('Reset pencarian') }}" aria-label="{{ __('Reset pencarian') }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Dropdown Filter Kelas + Tombol Reset Desktop (satu kelompok, tidak bisa terpisah/tertimpa) -->
                    <div class="filter-reset-group">
                    <div class="filter-box-wrap">
                        <select name="class_id" id="studentClassFilter" onchange="this.form.submit()" class="form-select border-secondary-subtle shadow-none fw-normal w-100" aria-label="{{ __('Pilih kelas') }}">
                            <option value="">{{ __('Semua Kelas') }}</option>
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                                {{ __('Kelas ') }}{{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tombol Reset Desktop (di sebelah kanan dropdown kelas, layar >= 768px) -->
                    <a href="{{ panel_route('students.index') }}" class="btn-reset desktop-reset-btn" role="button" title="{{ __('Reset pencarian') }}" aria-label="{{ __('Reset pencarian') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>
                        </svg>
                    </a>
                    </div>
                </form>

                <div class="action-buttons-wrap">
                    <button type="button" class="btn-solid-pill btn-solid-blue btn-cetak-kartu {{ $totalStudents == 0 ? 'is-disabled' : '' }}"
                            data-bs-toggle="modal"
                            data-bs-target="#printCardsModal"
                            {{ $totalStudents == 0 ? 'disabled aria-disabled="true" tabindex="-1" title="Belum ada data untuk dicetak"' : '' }}>
                        <span>{{ __('Cetak Kartu') }}</span>
                    </button>

                    @if(Auth::check() && Auth::user()->role === 'admin')
                    <button type="button" class="btn-solid-pill btn-solid-green" data-bs-toggle="modal" data-bs-target="#importModal">
                        <span>{{ __('Import Excel') }}</span>
                    </button>

                    <button type="button" class="btn-solid-pill btn-solid-blue" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <span>{{ __('Tambah Siswa') }}</span>
                    </button>

                    <button type="button" class="btn-solid-pill btn-solid-red" title="{{ __('Hapus Semua Data Siswa') }}" onclick="confirmDeleteAllStudents()">
                        <span>{{ __('Hapus') }}</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <colgroup>
                    <col style="width: 6%;">
                    <col style="width: 10%;">
                    <col style="width: 17%;">
                    <col style="width: 26%;">
                    <col style="width: 20%;">
                    <col style="width: 21%;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">{{ __('No') }}</th>
                        <th class="text-center">{{ __('Kelas') }}</th>
                        <th class="text-center">{{ __('NISN') }}</th>
                        <th class="text-left">{{ __('Nama Siswa') }}</th>
                        <th class="text-center">{{ __('Jenis Kelamin') }}</th>
                        <th class="text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                    @php
                        $rawJk = strtolower(trim($student->jenis_kelamin ?? $student->gender ?? ''));
                        $isMale = in_array($rawJk, ['laki-laki', 'laki - laki', 'l', 'pria', 'male']);
                    @endphp
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td data-label="{{ __('No') }}" class="text-center">{{ $loop->iteration + ($students->firstItem() ? $students->firstItem() - 1 : 0) }}</td>
                        <td data-label="{{ __('Kelas') }}" class="text-center">{{ $student->schoolClass->name ?? $student->kelas ?? '-' }}</td>
                        <td data-label="{{ __('NISN') }}" class="text-center">{{ $student->nisn ?: '-' }}</td>
                        <td data-label="{{ __('Nama Siswa') }}" class="text-start fw-semibold text-dark">
                            <div class="nama-siswa-wrapper">{{ $student->nama ?? $student->name }}</div>
                        </td>
                        <td data-label="{{ __('Jenis Kelamin') }}" class="text-center">{{ $isMale ? __('Laki-laki') : __('Perempuan') }}</td>
                        <td data-label="{{ __('Aksi') }}" class="text-center">
                            <div class="crud-center-wrapper">
                                <a href="{{ panel_route('students.show', $student->id) }}" class="btn-row-action action-edit" title="{{ __('Lihat / Edit') }}" aria-label="{{ __('Lihat / Edit') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </a>
                                @if(Auth::check() && Auth::user()->role === 'admin')
                                <button type="button" class="btn-row-action action-delete" title="{{ __('Hapus') }}" aria-label="{{ __('Hapus') }}" data-student-id="{{ $student->id }}" data-student-name="{{ addslashes($student->nama ?? $student->name) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                                <form id="deleteStudentForm-{{ $student->id }}" action="{{ route('admin.students.destroy', $student->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            @if(request('search') || request('class_id'))
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

        @php
            $pgCurrent = $students->currentPage();
            $pgLast = $students->lastPage();
            $pgHash = '#daftar-siswa';

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
            <nav class="kehadiran-pagination" id="siswa-pagination" aria-label="{{ __('Navigasi halaman data siswa') }}">
                <ul class="kehadiran-pagination-list">
                    @if ($pgCurrent <= 1)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">&lsaquo; {{ __('Sebelumnya') }}</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $students->previousPageUrl() }}{{ $pgHash }}" rel="prev">&lsaquo; {{ __('Sebelumnya') }}</a></li>
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
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">{{ __('Berikutnya') }} &rsaquo;</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $students->nextPageUrl() }}{{ $pgHash }}" rel="next">{{ __('Berikutnya') }} &rsaquo;</a></li>
                    @endif
                </ul>

                <p class="kehadiran-pagination-info">
                    {{ __('Menampilkan') }} {{ $students->firstItem() ?? 0 }}&ndash;{{ $students->lastItem() ?? 0 }}
                    {{ __('dari') }} {{ $students->total() }} {{ __('siswa') }}
                </p>
            </nav>
        @endif
    </div>

<!-- MODAL CETAK KARTU MASSAL -->
<div class="modal fade" id="printCardsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">{{ __('Cetak Kartu Presensi Massal') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ panel_route('students.print-cards') }}" method="POST">
                @csrf
                <div class="modal-body py-3">
                    <p class="text-secondary small mb-3">
                        {{ __('Pilih kartu siswa yang ingin dicetak. Hasil unduhan berupa file ZIP berisi satu kartu (JPG) untuk setiap siswa.') }}
                    </p>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Pilihan Cetak') }} <span class="text-danger">*</span></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="print_type" id="printTypeClass" value="class" checked onchange="togglePrintScope()">
                            <label class="form-check-label fw-medium small" for="printTypeClass">
                                {{ __('Cetak per Kelas') }}
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="print_type" id="printTypeAll" value="all" onchange="togglePrintScope()">
                            <label class="form-check-label fw-medium small" for="printTypeAll">
                                {{ __('Cetak Semua Siswa Aktif') }}
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="classSelectContainer">
                        <label class="form-label small fw-semibold">{{ __('Pilih Kelas') }}</label>
                        <select name="class_id" id="modalClassId" class="form-select rounded-3">
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ __('Kelas ') }}{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-2" id="printInfoBox">
                        <i class='bx bx-info-circle me-1'></i> {{ __('Setiap kartu dilengkapi QR Code presensi siswa yang dibuat otomatis.') }}
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn-download-blue btn-progress px-4" data-download data-dl-progress data-dl-progress-mode="persen" id="kartuUnduhBtn">{{ __('Unduh Kartu') }}</button>
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
                <h5 class="fw-bold mb-0">{{ __('Tambah Siswa Baru') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addStudentForm" action="{{ route('admin.students.store') }}" method="POST" novalidate data-no-loader>
                @csrf
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Nama Siswa') }} <span class="text-danger">*</span></label>
                        <input id="studentName" type="text" name="name" class="form-control rounded-3 @error('name') is-invalid @enderror" placeholder="{{ __('Masukkan nama lengkap siswa') }}" value="{{ old('name') }}" autocomplete="off">
                        <div class="field-error">{{ $errors->first('name') }}</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">{{ __('NISN') }} <span class="text-danger">*</span></label>
                            <input id="studentNisn" type="text" name="nisn" class="form-control rounded-3 @error('nisn') is-invalid @enderror" placeholder="{{ __('10 digit angka') }}" value="{{ old('nisn') }}" inputmode="numeric" maxlength="10" autocomplete="off">
                            <div class="field-error">{{ $errors->first('nisn') }}</div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">{{ __('Kelas') }} <span class="text-danger">*</span></label>
                            <select id="studentKelas" name="school_class_id" class="form-select rounded-3 @error('school_class_id') is-invalid @enderror">
                                <option value="">{{ __('Pilih Kelas') }}</option>
                                @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ old('school_class_id') == $c->id ? 'selected' : '' }}>{{ __('Kelas ') }}{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <div class="field-error">{{ $errors->first('school_class_id') }}</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">{{ __('Jenis Kelamin') }} <span class="text-danger">*</span></label>
                            <select id="studentJk" name="gender" class="form-select rounded-3 @error('gender') is-invalid @enderror">
                                <option value="" {{ old('gender') ? '' : 'selected' }}>{{ __('Pilih jenis kelamin') }}</option>
                                <option value="Laki-laki" {{ old('gender') == 'Laki-laki' ? 'selected' : '' }}>{{ __('Laki-laki') }}</option>
                                <option value="Perempuan" {{ old('gender') == 'Perempuan' ? 'selected' : '' }}>{{ __('Perempuan') }}</option>
                            </select>
                            <div class="field-error">{{ $errors->first('gender') }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold" id="btnSaveStudent" disabled aria-disabled="true">{{ __('Simpan Siswa') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL IMPORT EXCEL -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom-0">
                <h5 class="fw-bold mb-0">{{ __('Import Data Siswa Excel') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="studentImportForm" action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data" data-no-loader>
                @csrf
                <div class="modal-body">
                    @if($errors->any() && $errors->has('file_excel'))
                    <div class="alert alert-danger py-2 px-3 small mb-3 border-0 rounded-3" role="alert">
                        <i class='bx bx-error-circle me-1'></i> {{ __('Data belum bisa disimpan. Periksa isian yang bertanda merah.') }}
                    </div>
                    @endif

                    <div class="p-3 bg-white border rounded-3 small text-secondary mb-3">
                        <div class="mb-2">
                            {{ __('Format kolom file Excel:') }} <strong>{{ __('NISN, Nama Lengkap, Kelas, Jenis Kelamin') }}</strong> {{ __('(.xlsx atau .csv)') }}
                        </div>
                        <div class="mb-2">
                            {{ __('NISN boleh dikosongkan. Siswa tanpa NISN tetap mendapat QR Code.') }}
                        </div>
                        <a href="{{ route('admin.students.template') }}" class="btn-download-green w-100" data-download data-no-download data-dl-progress>{{ __('Unduh Template Excel') }}</a>
                    </div>
                    <label class="form-label small fw-semibold">{{ __('Pilih File Excel') }}</label>
                    <input type="file" name="file_excel" class="form-control rounded-3 @error('file_excel') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                    @error('file_excel')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">{{ __('Batal') }}</button>
                    <button type="submit" class="btn-download-green px-4" id="btnImportSubmit" disabled>{{ __('Unggah & Import') }}</button>
                </div>
                <div id="importMessage" class="px-3 pb-3 small d-none" style="min-height:1.2rem;"></div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
/* ===== Import Excel ===== */
(function () {
  var form = document.getElementById('studentImportForm');
  var btn = document.getElementById('btnImportSubmit');
  if (!form || !btn) return;
  var file = form.querySelector('input[type="file"]');
  var importing = false;
  function sync() {
    if (importing) return;
    btn.disabled = !(file.files && file.files.length);
  }
  file.addEventListener('change', sync);
  document.addEventListener('click', function () { setTimeout(sync, 0); });
  sync();

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    if (btn.disabled || importing) return;
    importing = true;
    var original = btn.innerHTML;
    btn.classList.add('is-saving');
    btn.disabled = true;
    btn.style.position = 'relative';
    btn.style.overflow = 'hidden';
    btn.innerHTML = '<span class="imp-bar" style="position:absolute;left:0;top:0;bottom:0;width:0%;background:#064e3b;transition:width .15s"></span><span class="imp-txt" style="position:relative">0%</span>';
    var bar = btn.querySelector('.imp-bar'), txt = btn.querySelector('.imp-txt');
    var shown = 0, real = 0, done = false;
    var timer = setInterval(function () {
      var target = done ? 100 : Math.max(real, Math.min(shown + 1, 92));
      if (shown < target) {
        shown++;
        bar.style.width = shown + '%';
        txt.textContent = shown + '%';
      }
      if (shown >= 100) {
        clearInterval(timer);
        setTimeout(function () { location.reload(); }, 400);
      }
    }, 40);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', form.action);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.upload.onprogress = function (ev) {
      if (!ev.lengthComputable) return;
      real = Math.min(90, Math.round(ev.loaded / ev.total * 90));
    };
    xhr.onload = function () {
      var json = {};
      try { json = JSON.parse(xhr.responseText); } catch (_) {}
      sessionStorage.setItem('importAlert', JSON.stringify({
        status: json.status || (xhr.status < 400 ? 'success' : 'error'),
        msg: json.message || (xhr.status < 400 ? 'Import selesai.' : 'Import gagal. Periksa file lalu coba lagi.'),
        details: json.details || []
      }));
      done = true;
    };
    xhr.onerror = function () {
      clearInterval(timer);
      importing = false;
      btn.classList.remove('is-saving');
      btn.style.removeProperty('position');
      btn.style.removeProperty('overflow');
      btn.innerHTML = original; sync();
      alert('Import gagal. Periksa koneksi lalu coba lagi.');
    };
    xhr.send(new FormData(form));
  }, true);

  var modal = document.getElementById('importModal');
  if (modal) modal.addEventListener('hidden.bs.modal', function () { if (importing) return; form.reset(); sync(); });
})();

/* ===== Notifikasi ===== */
(function () {
  var box = document.getElementById('importAlertBox');

  function fadeOutRemove(el, delay) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 400);
    }, delay);
  }

  var raw = null;
  try { raw = sessionStorage.getItem('importAlert'); } catch (e) {}
  var data = {};
  if (raw) {
    try { sessionStorage.removeItem('importAlert'); } catch (e) {}
    try { data = JSON.parse(raw) || {}; } catch (e) { data = {}; }
  }
  var status = data.status || 'error';
  var msg = data.msg || data.message || '';
  var details = Array.isArray(data.details) ? data.details : [];
  var hasClientAlert = !!(raw && box && (msg || details.length));

  var serverAlerts = document.querySelectorAll('.alert.alert-success.flash-server');
  Array.prototype.forEach.call(serverAlerts, function (el) {
    if (hasClientAlert && status === 'success') {
      if (el.parentNode) el.parentNode.removeChild(el);
    } else {
      fadeOutRemove(el, 3000);
    }
  });

  if (!hasClientAlert) return;

  var palette = {
    success: { bg: '#dcfce7', fg: '#166534', icon: 'bx-check-circle' },
    partial: { bg: '#fef3c7', fg: '#854d0e', icon: 'bx-error-circle' },
    warning: { bg: '#fef3c7', fg: '#854d0e', icon: 'bx-error-circle' },
    error: { bg: '#fee2e2', fg: '#991b1b', icon: 'bx-error-circle' }
  };
  var color = palette[status] || palette.error;
  var iconTone = status === 'success' ? 'success' : (status === 'error' ? 'danger' : 'warning');
  var alertEl = document.createElement('div');
  alertEl.className = 'alert alert-dismissible fade show border-0 rounded-3 small mb-3';
  alertEl.setAttribute('role', 'alert');
  alertEl.style.backgroundColor = color.bg;
  alertEl.style.color = color.fg;
  alertEl.style.maxHeight = 'none';
  alertEl.style.overflow = 'visible';
  var body = document.createElement('div');
  body.className = 'flash-notice-body';
  var head = document.createElement('div');
  head.className = 'd-flex align-items-center';
  var icon = document.createElement('i');
  icon.className = 'bx ' + color.icon + ' fs-5 me-2 text-' + iconTone + ' flex-shrink-0';
  var span = document.createElement('span');
  span.textContent = msg;
  head.appendChild(icon);
  head.appendChild(span);
  body.appendChild(head);
  alertEl.appendChild(body);
  if (details.length) {
    var list = document.createElement('ul');
    list.className = 'mb-0 mt-2 ps-3 small';
    details.forEach(function (item) {
      var li = document.createElement('li');
      li.textContent = String(item);
      list.appendChild(li);
    });
    alertEl.appendChild(list);
  }
  var closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.className = 'btn-close btn-close-sm';
  closeBtn.setAttribute('data-bs-dismiss', 'alert');
  closeBtn.setAttribute('aria-label', 'Tutup');
  closeBtn.addEventListener('click', function () { alertEl.remove(); });
  alertEl.appendChild(closeBtn);
  box.appendChild(alertEl);
  if (status === 'success') fadeOutRemove(alertEl, 3000);
})();
</script>

<script>
/* ===== Cetak kartu ===== */
function togglePrintScope() {
    var rbClass = document.getElementById('printTypeClass');
    var classContainer = document.getElementById('classSelectContainer');
    var classSelect = document.getElementById('modalClassId');
    if (rbClass && rbClass.checked) {
        if (classContainer) classContainer.style.display = '';
        if (classSelect) classSelect.disabled = false;
    } else {
        if (classContainer) classContainer.style.display = 'none';
        if (classSelect) classSelect.disabled = true;
    }
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str == null ? '' : String(str)));
    return div.innerHTML;
}

/* ===== Hapus siswa ===== */
function confirmDeleteStudent(id, name) {
    confirmUniversalDelete({
        title: {!! json_encode(__('Hapus Data Siswa?')) !!},
        html: {!! json_encode(__('Siswa')) !!} + ' <strong>' + escapeHtml(name) + '</strong> ' + {!! json_encode(__('akan dihapus. Data bisa dipulihkan dari Tempat Sampah di Pengaturan.')) !!},
        confirmText: {!! json_encode(__('Hapus')) !!},
        cancelText: {!! json_encode(__('Tidak')) !!},
        onConfirm: function () {
            var form = document.getElementById('deleteStudentForm-' + id);
            if (!form) return;
            var qs = window.location.search;
            if (qs && form.action.indexOf('?') === -1) form.action = form.action + qs;
            form.submit();
        }
    });
}

document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-student-id]');
    if (!b) return;
    var id = b.getAttribute('data-student-id');
    var name = b.getAttribute('data-student-name');
    if (id && name !== null) {
        e.preventDefault();
        confirmDeleteStudent(id, name);
    }
}, true);

function confirmDeleteAllStudents() {
    if (typeof confirmUniversalDelete === 'function') {
        confirmUniversalDelete({
            title: {!! json_encode(__('Hapus seluruh data siswa?')) !!},
            icon: 'bx-error-circle',
            html: '',
            sub: {!! json_encode(__('Data akan dipindahkan ke Tempat Sampah di Pengaturan.')) !!},
            confirmText: {!! json_encode(__('Hapus Semua')) !!},
            cancelText: {!! json_encode(__('Tidak')) !!},
            checks: [
                '<span style="display:block; white-space:nowrap">Saya memahami data akan dipindahkan ke Tempat</span><span style="display:block; white-space:nowrap">Sampah dan dapat dipulihkan.</span>',
                {!! json_encode(__('Saya yakin ingin menghapus semua data siswa aktif.')) !!}
            ],
            onConfirm: function () { document.getElementById('deleteAllStudentsForm').submit(); }
        });
    } else if (confirm({!! json_encode(__('Hapus seluruh data siswa?')) !!})) {
        document.getElementById('deleteAllStudentsForm').submit();
    }
}

window.togglePrintScope = togglePrintScope;
window.confirmDeleteStudent = confirmDeleteStudent;
window.confirmDeleteAllStudents = confirmDeleteAllStudents;
</script>

<script>
/* ===== Tambah Siswa ===== */
(function () {
    var form = document.getElementById('addStudentForm');
    var btn = document.getElementById('btnSaveStudent');
    var modalEl = document.getElementById('addStudentModal');
    if (!form || !btn) return;

    var nameEl = form.querySelector('[name="name"]');
    var nisnEl = form.querySelector('[name="nisn"]');
    var kelasEl = form.querySelector('[name="school_class_id"]');
    var jkEl = form.querySelector('[name="gender"]');

    var RE_NAMA_BUANG = /[^\p{L}\s.,'\-]/gu;
    var RE_NAMA_OK = /^[\p{L}\s.,'\-]+$/u;

    function setErr(el, msg) {
        if (!el) return;
        var box = el.parentNode.querySelector('.field-error');
        if (box) box.textContent = msg || '';
        if (msg) el.classList.add('is-invalid'); else el.classList.remove('is-invalid');
    }

    function bersihkan(el) {
        if (!el) return;
        var before = el.value, after = before;
        if (el === nameEl) after = before.replace(RE_NAMA_BUANG, '').slice(0, 255);
        if (el === nisnEl) after = before.replace(/\D/g, '').slice(0, 10);
        if (after !== before) {
            var pos = (el.selectionStart != null) ? el.selectionStart : before.length;
            el.value = after;
            pos = Math.min(pos, after.length);
            try { el.setSelectionRange(pos, pos); } catch (e) {}
        }
    }

    function valid() {
        var n = (nameEl.value || '').trim();
        var i = (nisnEl.value || '').trim();
        return n !== '' && RE_NAMA_OK.test(n)
            && /^\d{10}$/.test(i)
            && (kelasEl.value || '') !== ''
            && (jkEl.value || '') !== '';
    }

    function sync() {
        var ok = valid();
        btn.disabled = !ok;
        btn.setAttribute('aria-disabled', ok ? 'false' : 'true');
        if (ok) {
            btn.style.removeProperty('opacity');
            btn.style.removeProperty('cursor');
            btn.style.removeProperty('pointer-events');
        } else {
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            btn.style.pointerEvents = 'none';
        }
    }

    function onChange(e) {
        var el = e.target;
        if (el === nameEl || el === nisnEl) bersihkan(el);
        if (el === nameEl || el === nisnEl || el === kelasEl || el === jkEl) {
            setErr(el, '');
            sync();
        }
    }
    form.addEventListener('input', onChange);
    form.addEventListener('change', onChange);
    form.addEventListener('paste', function (e) { setTimeout(function () { onChange(e); }, 0); });
    form.addEventListener('drop', function (e) { setTimeout(function () { onChange(e); }, 0); });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        bersihkan(nameEl);
        bersihkan(nisnEl);
        if (!valid()) {
            sync();
            return;
        }

        if (window.bootstrap && modalEl) {
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
        }

        var formData = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json, text/javascript, */*'
            }
        }).then(function (response) {
            window.location.reload();
        }).catch(function () {
            window.location.reload();
        });
    });

    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            try { form.reset(); } catch (e) {}
            [nameEl, nisnEl, kelasEl, jkEl].forEach(function (el) { setErr(el, ''); });
            sync();
        });
        modalEl.addEventListener('shown.bs.modal', function () { setTimeout(sync, 0); setTimeout(sync, 150); });
        modalEl.addEventListener('hidden.bs.modal', function () {
            try { form.reset(); } catch (e) {}
            setTimeout(sync, 0); setTimeout(sync, 150);
        });
    }

    bersihkan(nameEl);
    bersihkan(nisnEl);
    sync();
    setTimeout(sync, 0);
    setTimeout(sync, 150);
})();
</script>
@endpush