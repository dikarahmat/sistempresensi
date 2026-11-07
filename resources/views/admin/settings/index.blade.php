@extends('layouts.app')

@section('title', __('Pengaturan Sistem'))
@section('page_title', __('PENGATURAN'))

{{-- Kanvas dikunci setinggi satu layar dan isi form menggulir di dalam kanvas,
     sama seperti halaman acuan Presensi / Data Guru / Data Kelas. Class ini
     diatur di layout bersama, layouts/app.blade.php.

     Class "pengaturan-no-scrollbar" sengaja ditambahkan ke class <main> sebagai
     PENANDA unik halaman ini: aturan CSS pada blok styles pertama memakai
     penanda itu untuk menyembunyikan scrollbar vertikal area konten.
     Halaman lain tidak memakai penanda ini, sehingga scrollbar-nya tetap. --}}
@section('canvas_class', 'page-canvas-fixed pengaturan-no-scrollbar')

@push('styles')
<style>
    /* Subtitle Pengaturan: full width, tidak terpotong (mobile & desktop) */
    .header-main-subtitle {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        display: block !important;
        width: 100% !important;
    }

    /* ===== SCROLLBAR VERTIKAL AREA KONTEN DISEMBUNYIKAN (hanya Pengaturan) =====
       Scrollbar disembunyikan sebagai tampilan saja, FUNGSI SCROLLNYA TETAP
       JALAN: mouse wheel, sentuhan (touch), dan tombol panah keyboard tetap bisa
       menggulir. Yang hilang hanya batang scrollbar di sisi kanan.

       Setiap aturan DIKUNCI ke class penanda .pengaturan-no-scrollbar yang hanya
       menempel pada elemen main di halaman Pengaturan (lihat section
       canvas_class di bagian atas file ini), sehingga halaman lain tidak ikut
       terpengaruh - termasuk halaman lain yang juga memakai .page-canvas-fixed.

       Dua area yang perlu ditutup:
         - >= 768px : area scroll = main > .flex-1. Aturan layout bersama
                      justru memberi scrollbar 10px di sini, jadi harus DITIMPA
                      (bukan sekadar menambah aturan baru),
         - < 768px  : area scroll = .content-scroll-wrapper (sudah disembunyikan
                      oleh layout bersama, tapi tetap dicantumkan agar aman).

       PENTING: selector di bawah diberi awalan "body" dengan sengaja. Tanpa
       itu, spesifisitasnya SAMA PERSIS dengan aturan layout
       ".content-scroll-wrapper > main.page-canvas-fixed > .flex-1::-webkit-scrollbar",
       padahal blok style milik layout berada SETELAH blok styles halaman ini -
       aturan layout akan menang dan scrollbar tetap terlihat. Dengan awalan
       "body" spesifisitasnya jadi lebih tinggi, sehingga menimpa apa pun urutan.

       Catatan penting: blok ini dieksekusi PHP, bukan sekadar teks CSS.
       Karakter "@" di dalam <style> TIDAK aman - Blade memindai seluruh file,
       termasuk isi <style>, dan akan mengganti "@" + nama directive dengan
       kode PHP. Karena itu di dalam komentar ini sengaja tidak ada satu pun
       "@" yang diikuti nama directive. Contoh fatalnya: menulis nama directive
       "section" dengan didahului "@" di dalam komentar menghasilkan PHP tidak
       valid dan seluruh halaman jadi 500. */
    body .content-scroll-wrapper > main.pengaturan-no-scrollbar > .flex-1 {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }

    body .content-scroll-wrapper > main.pengaturan-no-scrollbar > .flex-1::-webkit-scrollbar {
        width: 0 !important;
        height: 0 !important;
        display: none !important;
    }

    body .content-scroll-wrapper:has(main.pengaturan-no-scrollbar) {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }

    body .content-scroll-wrapper:has(main.pengaturan-no-scrollbar)::-webkit-scrollbar {
        width: 0 !important;
        height: 0 !important;
        display: none !important;
    }
</style>
@endpush

@push('styles')
<style>
    .section-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 14px !important;
    }

    .section-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    /* ===== Optimalisasi Mobile (< 768px): Clean Look — desktop tidak tersentuh ===== */
    @media (max-width: 767.98px) {
        .section-header {
            gap: 0.6rem;
            margin-bottom: 14px !important;
        }
        .section-icon {
            width: 32px;
            height: 32px;
            font-size: 1.05rem;
            border-radius: 8px;
        }
        .section-header h5 {
            font-size: 1rem;
        }
    }

    /* Header ke konten: hilangkan margin-bottom bawaan layout bersama khusus di Pengaturan,
       agar jarak header -> konten murni diatur oleh margin-top wrapper (38px). */
    body .content-scroll-wrapper > main.pengaturan-no-scrollbar .app-header-bar {
        margin-bottom: 0 !important;
    }

    /* ===== ALERT NOTIFIKASI DI PENGATURAN =====
       Berada tepat di tengah vertikal antara deskripsi header dan konten:
       - Jarak atas notifikasi (dari deskripsi header): 22px (rentang 20-24px)
       - Jarak bawah notifikasi (ke konten): 22px (rentang 20-24px)
       - Saat TIDAK ada notifikasi, jarak header ke konten tetap 38px semula.
       - Rata kiri dan lebar penuh (width: 100%). */
    .pengaturan-alert {
        margin-top: 22px !important;
        margin-bottom: 22px !important;
        width: 100% !important;
    }

    /* Saat ada notifikasi tepat sebelum .pengaturan-page-body, hilangkan margin-top
       wrapper konten agar jarak bawah alert ke konten murni ditentukan margin-bottom alert (22px) */
    body .content-scroll-wrapper > main.pengaturan-no-scrollbar .pengaturan-alert + .pengaturan-page-body,
    body .content-scroll-wrapper > main.pengaturan-no-scrollbar:has(.pengaturan-alert) .pengaturan-page-body {
        margin-top: 0 !important;
    }

    /* ===== WRAPPER KONTEN: jarak atas 38px dari header ===== */
    .pengaturan-page-body {
        margin-top: 38px !important;
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    /* ===== WADAH UTAMA FORM: Selebar penuh, flex kolom dengan gap seragam 28px ===== */
    .settings-form-wrapper {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 28px !important;
    }

    .settings-section {
        width: 100%;
    }

    /* ===== IDENTITAS & LOGO SEKOLAH =====
       Wadah luar 3 kolom sama lebar (repeat(3, 1fr)), gap 16px.
       Kolom 1-2: Wrapper grid dalam 2 kolom sama lebar (repeat(2, 1fr)), gap 14px 16px.
       Kolom 3: Blok Logo align-self: stretch, display: flex, flex-direction: column, justify-content: space-between. */
    .identitas-outer-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        align-items: start;
        width: 100%;
    }

    .identitas-fields-wrap {
        grid-column: 1 / span 2;
        width: 100%;
    }

    .identitas-inner-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px 16px;
        width: 100%;
    }

    .identitas-inner-grid .identitas-field {
        min-width: 0;
        width: 100%;
    }

    .identitas-inner-grid .identitas-field .form-control {
        width: 100% !important;
        height: 38px !important;
        font-size: 0.875rem !important;
        color: #0f172a !important;
        font-weight: 400 !important;
    }

    .settings-form-wrapper .form-control::placeholder {
        color: #94a3b8 !important;
        font-weight: 400 !important;
    }

    /* Jarak label ke input 6px rapat */
    .settings-form-wrapper label.form-label {
        margin-bottom: 6px !important;
        margin-top: 0 !important;
        padding-top: 0 !important;
        color: #0f172a !important;
        font-weight: 600 !important;
        display: block;
    }

    /* Kolom 3: Blok Logo (Desktop >= 1024px)
       align-self: stretch setinggi 3 baris field, space-between
       Bagian atas: label + pratinjau logo 130px x 130px
       Bagian bawah: input file sejajar NIP baris 3 + catatan */
    .identitas-logo-col {
        grid-column: 3;
        align-self: stretch;
        width: 100%;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background: transparent !important;
        border: 0 !important;
        padding: 0 !important;
        position: relative;
    }

    .logo-top-group {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
    }

    /* Preview logo sekolah: dua kotak sejajar + panah kanan (desktop), vertikal (mobile) */
    .logo-row {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
    }

    .logo-box-col {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        gap: 4px !important;
    }

    .logo-field-label {
        font-size: 10px !important;
        color: #0f172a !important;
        font-weight: 600 !important;
    }

    .logo-arrow {
        color: #0f172a !important;
        font-size: 1.15rem !important;
        flex-shrink: 0 !important;
        transition: transform 0.2s ease !important;
    }

    /* Layar < 1024px: logo menumpuk vertikal, panah menghadap ke bawah */
    @media (max-width: 1023.98px) {
        .logo-row {
            flex-direction: column !important;
            align-items: center !important;
            gap: 8px !important;
        }
        .logo-box-col {
            width: 100% !important;
            max-width: 100% !important;
        }
        .logo-arrow {
            transform: rotate(90deg) !important;
            align-self: center !important;
        }
        .logo-field-label {
            font-size: 11px !important;
        }
    }

    .logo-preview-box {
        width: 130px !important;
        height: 130px !important;
        min-width: 130px !important;
        min-height: 130px !important;
        aspect-ratio: 1 / 1 !important;
        border-radius: 12px !important;
        border: 2px dashed #cbd5e1 !important;
        background-color: #f8fafc !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        overflow: hidden !important;
        flex-shrink: 0 !important;
    }
    .logo-preview-box img {
        max-width: 100% !important;
        max-height: 100% !important;
        object-fit: contain !important;
    }

    .logo-bottom-group {
        width: 100%;
        min-width: 0;
        position: relative;
    }

    .identitas-logo-col input[type="file"].form-control {
        width: 100% !important;
        max-width: 100% !important;
        height: 38px !important;
        box-sizing: border-box !important;
        font-size: 0.8125rem !important;
        color: #0f172a !important;
        font-weight: 400 !important;
    }

    /* Error state: border merah 1px saja, hilangkan glow/ring/outline */
    #school_logo.is-invalid {
        border: 1px solid #dc2626 !important;
        box-shadow: none !important;
        outline: none !important;
    }
    #school_logo.is-invalid:focus {
        box-shadow: none !important;
        outline: none !important;
    }

    /* Catatan tepat di bawah input file, menjorok sedikit tanpa menambah tinggi baris */
    .logo-sub-text {
        position: relative;
        margin-bottom: -24px;
        width: 100%;
    }

    .logo-note {
        color: #0f172a !important;
        font-weight: 400 !important;
    }

    /* Layar < 1024px: wrapper field span 3 kolom penuh, blok logo turun di bawahnya susunan vertikal biasa */
    @media (max-width: 1023.98px) {
        .identitas-outer-grid {
            grid-template-columns: 1fr !important;
            gap: 16px !important;
        }
        .identitas-fields-wrap {
            grid-column: 1 / -1 !important;
        }
        .identitas-logo-col {
            grid-column: 1 / -1 !important;
            max-width: 100% !important;
            align-self: auto !important;
            justify-content: flex-start !important;
            gap: 10px !important;
        }
        .logo-sub-text {
            position: static !important;
            margin-bottom: 0 !important;
        }
    }

    /* Layar < 768px: grid dalam jadi 1 kolom */
    @media (max-width: 767.98px) {
        .identitas-inner-grid {
            grid-template-columns: 1fr !important;
            gap: 14px !important;
        }
    }

    /* ===== KARTU JADWAL OPERASIONAL: 2 Kartu, Watermark Ikon Bayangan di Sisi Kiri ===== */
    .jadwal-cards-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 16px !important;
        width: 100% !important;
        align-items: stretch !important;
    }
    @media (max-width: 767.98px) {
        .jadwal-cards-grid {
            grid-template-columns: 1fr !important;
        }
    }

    .jadwal-card {
        position: relative !important;
        overflow: hidden !important;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 16px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02) !important;
        padding: 1.25rem 1.5rem !important;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .jadwal-card:focus-within {
        border-color: #cbd5e1 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06), 0 2px 4px -1px rgba(0, 0, 0, 0.04) !important;
    }

    /* Ikon BESAR sebagai bayangan/watermark di sisi KIRI kartu */
    .jadwal-card-watermark {
        position: absolute !important;
        left: -8px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        font-size: 135px !important;
        line-height: 1 !important;
        pointer-events: none !important;
        z-index: 0 !important;
        user-select: none !important;
    }
    .jadwal-card-watermark.watermark-masuk {
        color: #3b82f6 !important; /* Biru muda */
        opacity: 0.10 !important;   /* 0.08 - 0.12 */
    }
    .jadwal-card-watermark.watermark-terlambat {
        color: #f59e0b !important; /* Oranye */
        opacity: 0.10 !important;   /* 0.08 - 0.12 */
    }

    /* Isi kartu: display flex, justify-content space-between, align-items center */
    .jadwal-card-content {
        position: relative !important;
        z-index: 1 !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
    }
    .jadwal-card-left {
        flex-shrink: 0;
    }
    .jadwal-card-right {
        text-align: right;
    }

    .dashboard-time-input {
        background: transparent !important;
        border: 0 !important;
        outline: none !important;
        box-shadow: none !important;
        font-size: 34px !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em !important;
        line-height: 1.15 !important;
        padding: 0 !important;
        margin: 0 !important;
        height: auto !important;
        cursor: pointer;
        font-family: inherit;
        width: 145px !important;
        max-width: 155px !important;
        display: inline-block !important;
        text-align: right !important;
    }
    .dashboard-time-input.clock-time-masuk {
        color: #0f172a !important; /* Hitam */
    }
    .dashboard-time-input.clock-time-terlambat {
        color: #d97706 !important; /* Oranye */
    }
    .dashboard-time-input::-webkit-calendar-picker-indicator {
        cursor: pointer;
        opacity: 0.5;
        transition: opacity 0.15s ease;
        margin-left: 4px;
    }
    .dashboard-time-input:hover::-webkit-calendar-picker-indicator,
    .dashboard-time-input:focus::-webkit-calendar-picker-indicator {
        opacity: 1;
    }
    .dashboard-time-input:focus {
        outline: none !important;
        box-shadow: none !important;
    }
    .dashboard-time-input.is-invalid {
        color: #dc2626 !important;
    }
    .clock-time-wrap .time-client-error {
        width: 100% !important;
        flex-basis: 100% !important;
        display: block !important;
        text-align: right !important;
    }

    /* ===== JADWAL PER HARI: clean, tanpa tabel dan garis ===== */
    .schedule-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }
    .schedule-head,
    .schedule-row {
        display: grid;
        grid-template-columns: 110px repeat(4, minmax(0, 1fr));
        column-gap: 16px;
        align-items: center;
        width: 100%;
    }
    .schedule-head span {
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
    }
    .schedule-day {
        font-weight: 600;
        color: #0f172a;
    }
    .schedule-cell {
        min-width: 0;
    }
    .schedule-cell-label {
        display: none;
    }
    .schedule-input {
        width: 100%;
        height: 38px;
        border-radius: 8px;
        font-size: 0.875rem;
        color: #0f172a;
    }
    .schedule-error {
        grid-column: 1 / -1;
    }

    /* HP dan tablet kecil: tiap hari jadi satu blok 2 kolom, label muncul di atas input */
    @media (max-width: 767.98px) {
        .schedule-head { display: none; }
        .schedule-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            row-gap: 10px;
            padding-bottom: 14px;
        }
        .schedule-day {
            grid-column: 1 / -1;
            font-size: 1rem;
        }
        .schedule-cell-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 4px;
        }
    }
</style>
@endpush

@section('content')
    <!-- Alert Notifikasi: [ .flash-notice-body (ikon + teks) ] [ tombol X ].
         Tombol X center vertikal oleh CSS notifikasi global di layout. -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs pengaturan-alert" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs pengaturan-alert" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-error-circle fs-5 me-2'></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(isset($errors) && $errors->any())
    {{-- Error validasi form: TETAP tampil (tidak hilang otomatis) sampai user
         memperbaikinya atau menekan tombol X. --}}
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs pengaturan-alert" role="alert" data-flash-persist>
        <div class="flash-notice-body">
            <div class="fw-bold mb-1"><i class='bx bx-error-circle me-1'></i> {{ __('Data belum bisa disimpan. Periksa isian yang bertanda merah.') }}</div>
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    <!-- WRAPPER KONTEN PENGATURAN: Menambah jarak vertikal dari header (38px) -->
    <div class="pengaturan-page-body">
        {{-- Tombol TAHUN AJARAN / HARI LIBUR / TEMPAT SAMPAH DIHAPUS dari halaman ini.
             Akses ke ketiganya sudah ada di submenu Pengaturan pada sidebar.
             Route dan halamannya TIDAK dihapus. --}}

        <form action="{{ panel_route('settings.update') }}" method="POST" enctype="multipart/form-data"
              class="settings-mobile-form settings-form-wrapper">
            @csrf

            <!-- Bagian 1: Identitas & Logo Sekolah -->
            <div class="settings-section">
                <div class="section-header">
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #0f172a;">{{ __('Identitas & Logo Sekolah') }}</h5>
                    </div>
                </div>

                <!-- Wadah Luar: Grid 3 Kolom Sama Lebar (repeat(3, 1fr)), gap 16px -->
                <div class="identitas-outer-grid">
                    <!-- Kolom 1-2: Wrapper Grid Dalam 2 Kolom Sama Lebar (repeat(2, 1fr)), gap 14px 16px -->
                    <div class="identitas-fields-wrap">
                        <div class="identitas-inner-grid">
                            <!-- 1. Nama Sekolah Resmi -->
                            <div class="identitas-field">
                                <label class="form-label small fw-semibold" for="school_name">{{ __('Nama Sekolah Resmi') }}</label>
                                <input type="text" name="school_name" id="school_name" class="form-control rounded-3 @error('school_name') is-invalid @enderror" value="{{ old('school_name', $settings['school_name'] ?? '') }}" required placeholder="{{ __('Contoh: SMP Presensi PGRI') }}">
                                @error('school_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- 2. NPSN Sekolah -->
                            <div class="identitas-field">
                                <label class="form-label small fw-semibold" for="school_npsn">{{ __('NPSN Sekolah') }}</label>
                                <input type="text" name="school_npsn" id="school_npsn" inputmode="numeric" pattern="[0-9]*" maxlength="8" class="form-control rounded-3 @error('school_npsn') is-invalid @enderror" value="{{ old('school_npsn', $settings['school_npsn'] ?? '') }}" placeholder="{{ __('Contoh: 20102030') }}">
                                @error('school_npsn')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div id="npsnClientError" class="text-danger small mt-1" style="display:none;">{{ __('NPSN harus 8 digit angka.') }}</div>
                            </div>

                            <!-- 3. Alamat Sekolah -->
                            <div class="identitas-field">
                                <label class="form-label small fw-semibold" for="school_address">{{ __('Alamat Sekolah') }}</label>
                                <input type="text" name="school_address" id="school_address" class="form-control rounded-3 @error('school_address') is-invalid @enderror" value="{{ old('school_address', $settings['school_address'] ?? '') }}" placeholder="{{ __('Jl. Raya Pendidikan...') }}">
                                @error('school_address')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- 4. No. Telepon / Kontak -->
                            <div class="identitas-field">
                                <label class="form-label small fw-semibold" for="school_phone">{{ __('No. Telepon / Kontak') }}</label>
                                <input type="text" name="school_phone" id="school_phone" inputmode="numeric" pattern="[0-9]*" maxlength="15" class="form-control rounded-3 @error('school_phone') is-invalid @enderror" value="{{ old('school_phone', $settings['school_phone'] ?? '') }}" placeholder="{{ __('Contoh: 081234567890') }}">
                                @error('school_phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div id="phoneClientError" class="text-danger small mt-1" style="display:none;">{{ __('Nomor telepon harus 10-15 digit angka.') }}</div>
                            </div>

                            <!-- 5. Nama Kepala Sekolah -->
                            <div class="identitas-field">
                                <label class="form-label small fw-semibold" for="headmaster_name">{{ __('Nama Kepala Sekolah') }}</label>
                                <input type="text" name="headmaster_name" id="headmaster_name" class="form-control rounded-3 @error('headmaster_name') is-invalid @enderror" value="{{ old('headmaster_name', $settings['headmaster_name'] ?? '') }}" placeholder="{{ __('Contoh: Drs. H. Ahmad Sudrajat, M.Pd') }}">
                                @error('headmaster_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- 6. NIP Kepala Sekolah -->
                            <div class="identitas-field">
                                <label class="form-label small fw-semibold" for="headmaster_nip">{{ __('NIP Kepala Sekolah') }}</label>
                                <input type="text" name="headmaster_nip" id="headmaster_nip" inputmode="numeric" pattern="[0-9]*" maxlength="18" class="form-control rounded-3 @error('headmaster_nip') is-invalid @enderror" value="{{ old('headmaster_nip', $settings['headmaster_nip'] ?? '') }}" placeholder="{{ __('18 digit angka tanpa spasi') }}">
                                @error('headmaster_nip')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div id="nipClientError" class="text-danger small mt-1" style="display:none;">{{ __('NIP harus 18 digit angka.') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom 3: Blok Logo Sekolah (Polos, align-self: stretch) -->
                    <div class="identitas-logo-col">
                        <div class="logo-top-group">
                            <label class="form-label small fw-semibold" for="school_logo">{{ __('Logo Sekolah (.webp, .png, .jpg, .jpeg)') }}</label>
                            <div class="logo-row">
                                <div class="logo-box-col">
                                    <span class="logo-field-label d-none" id="logoCurrentLabel">{{ __('Logo saat ini') }}</span>
                                    <div class="logo-preview-box">
                                        <img id="logoPreview" src="{{ \App\Models\Setting::getLogoUrl() }}" onerror="this.outerHTML='<i class=\'bx bx-image text-muted fs-1\'></i>'" alt="{{ __('Logo') }}">
                                    </div>
                                </div>
                                <i class="bx bx-right-arrow-alt logo-arrow d-none" id="logoArrow" aria-hidden="true"></i>
                                <div class="logo-box-col">
                                    <span class="logo-field-label d-none" id="logoNewLabel">{{ __('Logo baru') }}</span>
                                    <div id="logoNewPreviewWrap" style="display:none;">
                                        <div class="logo-preview-box mt-1">
                                            <img id="logoNewPreview" alt="{{ __('Pratinjau logo baru') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="logo-bottom-group">
                            <input type="file" name="school_logo" id="school_logo" class="form-control rounded-3 w-100 @error('school_logo') is-invalid @enderror" accept=".webp,.png,.jpg,.jpeg">
                            <div class="logo-sub-text">
                                <span class="logo-note d-block mt-1" style="font-size: 11px;">{{ __('Ukuran rasio 1:1 disarankan. Maksimal 1 MB.') }}</span>
                                @error('school_logo')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div id="logoClientError" class="text-danger small mt-1" style="display:none;"></div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Jadwal Operasional Presensi per Hari -->
            <div class="settings-section">
                <div class="section-header">
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #0f172a;">{{ __('Jadwal Operasional Presensi per Hari') }}</h5>
                    </div>
                </div>

                <div class="schedule-list" id="scheduleList">
                    <div class="schedule-head" aria-hidden="true">
                        <span>{{ __('Hari') }}</span>
                        <span>{{ __('Jam Masuk') }}</span>
                        <span>{{ __('Batas Keterlambatan') }}</span>
                        <span>{{ __('Jam Pulang') }}</span>
                        <span>{{ __('Batas Waktu Pulang') }}</span>
                    </div>

                    @foreach($schedules as $day => $schedule)
                    <div class="schedule-row">
                        <div class="schedule-day">{{ $schedule['label'] }}</div>

                        <div class="schedule-cell">
                            <label class="schedule-cell-label" for="sch_{{ $day }}_in">{{ __('Jam Masuk') }}</label>
                            <input type="time" id="sch_{{ $day }}_in" name="schedules[{{ $day }}][check_in_time]" class="form-control schedule-input @error('schedules.'.$day.'.check_in_time') is-invalid @enderror" value="{{ old('schedules.'.$day.'.check_in_time', $schedule['check_in_time']) }}" required>
                            @error('schedules.'.$day.'.check_in_time')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="schedule-cell">
                            <label class="schedule-cell-label" for="sch_{{ $day }}_late">{{ __('Batas Keterlambatan') }}</label>
                            <input type="time" id="sch_{{ $day }}_late" name="schedules[{{ $day }}][late_limit_time]" class="form-control schedule-input @error('schedules.'.$day.'.late_limit_time') is-invalid @enderror" value="{{ old('schedules.'.$day.'.late_limit_time', $schedule['late_limit_time']) }}" required>
                            @error('schedules.'.$day.'.late_limit_time')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="schedule-cell">
                            <label class="schedule-cell-label" for="sch_{{ $day }}_out">{{ __('Jam Pulang') }}</label>
                            <input type="time" id="sch_{{ $day }}_out" name="schedules[{{ $day }}][check_out_time]" class="form-control schedule-input @error('schedules.'.$day.'.check_out_time') is-invalid @enderror" value="{{ old('schedules.'.$day.'.check_out_time', $schedule['check_out_time']) }}" required>
                            @error('schedules.'.$day.'.check_out_time')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="schedule-cell">
                            <label class="schedule-cell-label" for="sch_{{ $day }}_limit">{{ __('Batas Waktu Pulang') }}</label>
                            <input type="time" id="sch_{{ $day }}_limit" name="schedules[{{ $day }}][check_out_limit_time]" class="form-control schedule-input @error('schedules.'.$day.'.check_out_limit_time') is-invalid @enderror" value="{{ old('schedules.'.$day.'.check_out_limit_time', $schedule['check_out_limit_time']) }}" required>
                            @error('schedules.'.$day.'.check_out_limit_time')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    @endforeach
                </div>

                @error('schedules')
                <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>

            <!-- Tombol Simpan -->
            <div class="w-100 m-0">
                <!-- Tombol SIMPAN: selebar penuh wadah (width 100%), teks saja (tanpa ikon). -->
                <button type="submit" class="btn btn-primary btn-sm w-100 py-2.5 rounded-3 fw-semibold shadow-xs d-flex align-items-center justify-content-center">
                    {{ __('Simpan Semua Pengaturan') }}
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Field angka hanya menerima angka saat diketik (HP, NIP, NPSN).
        ['school_phone', 'headmaster_nip', 'school_npsn'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function () {
                el.value = el.value.replace(/[^0-9]/g, '');
            });
        });

        // 2. Preview logo baru + nama file sebelum disimpan, sekaligus validasi
        //    klien (format & 2MB, Bahasa Indonesia). Server tetap memvalidasi
        //    ulang; ini hanya agar gagal cepat dengan pesan di dalam form.
        var logoInput = document.getElementById('school_logo');
        var previewWrap = document.getElementById('logoNewPreviewWrap');
        var previewImg = document.getElementById('logoNewPreview');
        var logoCurrentLabel = document.getElementById('logoCurrentLabel');
        var logoNewLabel = document.getElementById('logoNewLabel');
        var logoArrow = document.getElementById('logoArrow');
        var logoErr = document.getElementById('logoClientError');
        if (logoInput) {
            logoInput.addEventListener('change', function () {
                var file = logoInput.files && logoInput.files[0];
                if (logoErr) { logoErr.style.display = 'none'; logoErr.textContent = ''; }
                logoInput.classList.remove('is-invalid');
                if (!file) {
                    previewWrap.style.display = 'none';
                    logoCurrentLabel.classList.add('d-none');
                    logoNewLabel.classList.add('d-none');
                    logoArrow.classList.add('d-none');
                    return;
                }
                var allowed = ['image/webp', 'image/png', 'image/jpeg'];
                var extOk = /\.(webp|png|jpe?g)$/i.test(file.name || '');
                if ((file.type && allowed.indexOf(file.type) === -1) || !extOk) {
                    if (logoErr) { logoErr.textContent = {!! json_encode(__('Format logo harus .webp, .png, .jpg, atau .jpeg.')) !!}; logoErr.style.display = ''; }
                    logoInput.classList.add('is-invalid');
                    logoInput.value = '';
                    previewWrap.style.display = 'none';
                    logoCurrentLabel.classList.add('d-none');
                    logoNewLabel.classList.add('d-none');
                    logoArrow.classList.add('d-none');
                    return;
                }
                if (file.size > 1 * 1024 * 1024) {
                    if (logoErr) { logoErr.textContent = {!! json_encode(__('Ukuran logo maksimal 1 MB.')) !!}; logoErr.style.display = ''; }
                    logoInput.classList.add('is-invalid');
                    logoInput.value = '';
                    previewWrap.style.display = 'none';
                    logoCurrentLabel.classList.add('d-none');
                    logoNewLabel.classList.add('d-none');
                    logoArrow.classList.add('d-none');
                    return;
                }
                var reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewWrap.style.display = '';
                    logoCurrentLabel.classList.remove('d-none');
                    logoNewLabel.classList.remove('d-none');
                    logoArrow.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            });
        }

        // 3. Baris jadwal per hari (Senin sampai Jumat) dan 4 field jamnya.
        var scheduleRows = Array.prototype.slice.call(document.querySelectorAll('#scheduleList .schedule-row'));
        var scheduleFields = ['check_in_time', 'late_limit_time', 'check_out_time', 'check_out_limit_time'];

        function scheduleToMinutes(value) {
            var parts = (value || '').split(':');
            return parts.length === 2 ? (parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10)) : NaN;
        }

        // 4. Validasi klien sebelum submit. Server tetap memvalidasi ulang;
        //    ini hanya agar pesan muncul tepat di bawah baris hari yang salah.
        var form = document.querySelector('.settings-mobile-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                var nip = document.getElementById('headmaster_nip');
                var phone = document.getElementById('school_phone');
                var npsn = document.getElementById('school_npsn');
                var ok = true;

                [nip, phone, npsn].forEach(function (el) {
                    if (el) el.classList.remove('is-invalid');
                });
                var nipErr = document.getElementById('nipClientError');
                var phoneErr = document.getElementById('phoneClientError');
                var npsnErr = document.getElementById('npsnClientError');
                if (nipErr) nipErr.style.display = 'none';
                if (phoneErr) phoneErr.style.display = 'none';
                if (npsnErr) npsnErr.style.display = 'none';

                scheduleRows.forEach(function (row) {
                    scheduleFields.forEach(function (field) {
                        var el = row.querySelector('input[name$="[' + field + ']"]');
                        if (el) el.classList.remove('is-invalid');
                    });
                    row.querySelectorAll('.time-client-error').forEach(function (el) {
                        el.remove();
                    });
                });

                function tandai(el) {
                    el.classList.add('is-invalid');
                    ok = false;
                }

                if (nip && nip.value !== '' && !/^[0-9]{18}$/.test(nip.value)) {
                    tandai(nip);
                    if (nipErr) nipErr.style.display = '';
                }
                if (phone && phone.value !== '' && !/^[0-9]{10,15}$/.test(phone.value)) {
                    tandai(phone);
                    if (phoneErr) phoneErr.style.display = '';
                }
                if (npsn && npsn.value !== '' && !/^[0-9]{8}$/.test(npsn.value)) {
                    tandai(npsn);
                    if (npsnErr) npsnErr.style.display = '';
                }

                scheduleRows.forEach(function (row) {
                    var values = {};
                    scheduleFields.forEach(function (field) {
                        var el = row.querySelector('input[name$="[' + field + ']"]');
                        values[field] = el ? el.value : '';
                    });

                    var checkIn = scheduleToMinutes(values.check_in_time);
                    var lateLimit = scheduleToMinutes(values.late_limit_time);
                    var checkOut = scheduleToMinutes(values.check_out_time);
                    var checkOutLimit = scheduleToMinutes(values.check_out_limit_time);

                    if (!(checkIn < lateLimit && lateLimit < checkOut && checkOut <= checkOutLimit)) {
                        var target = row.querySelector('input[name$="[check_out_time]"]');
                        if (target) {
                            tandai(target);
                            var errDiv = document.createElement('div');
                            errDiv.className = 'text-danger small time-client-error schedule-error';
                            errDiv.textContent = {!! json_encode(__('Urutan jam harus Masuk < Batas Keterlambatan < Jam Pulang <= Batas Waktu Pulang.')) !!};
                            row.appendChild(errDiv);
                        }
                    }
                });

                if (!ok) {
                    e.preventDefault();
                    alert({!! json_encode(__('Data belum bisa disimpan. Periksa isian yang bertanda merah.')) !!});
                }
            });
        }
    });
    </script>
    @endpush
@endsection