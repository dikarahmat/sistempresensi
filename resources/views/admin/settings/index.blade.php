@extends('layouts.app')

@section('title', __('Pengaturan Sistem'))
@section('page_title', __('PENGATURAN'))
@section('page_subtitle', __('Kelola identitas sekolah, jadwal presensi, tahun ajaran, hari libur, dan tempat sampah.'))

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
/* ===== Tombol Pengelolaan Sistem: susunan ke BAWAH di mobile =====
       Breakpoint sama dengan susunan tombol action bar Data Siswa / Data Guru
       (max-width: 1023.98px = saat bottom navigation muncul), jadi benar-benar
       sama persis dengan halaman-halaman itu dan desktop >= 1024px tidak
       tersentuh. Yang berubah hanya ARAH dan LEBAR: tombol turun satu per
       satu, lebar penuh, jarak antar tombol sama (0.5rem). Urutan, warna, dan
       teks tombol tetap apa adanya. */
    @media (max-width: 1023.98px) {
        .pengaturan-modul-wrap {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 0.5rem !important;
        }

        .pengaturan-modul-wrap > .btn {
            flex: 1 1 100% !important;
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
        }
    }

    /* Header ke konten: hilangkan margin-bottom bawaan layout bersama khusus di Pengaturan,
       agar jarak header -> tombol murni diatur oleh margin-top wrapper (38px),
       sehingga presisi sama dengan margin-bottom tombol -> Identitas (38px). */
    body .content-scroll-wrapper > main.pengaturan-no-scrollbar .app-header-bar {
        margin-bottom: 0 !important;
    }

    /* ===== ALERT NOTIFIKASI DI PENGATURAN =====
       Berada tepat di tengah vertikal antara deskripsi header dan baris tiga tombol:
       - Jarak atas notifikasi (dari deskripsi header): 22px (rentang 20-24px)
       - Jarak bawah notifikasi (ke baris tiga tombol): 22px (rentang 20-24px)
       - Saat TIDAK ada notifikasi, jarak header ke tiga tombol tetap 38px semula.
       - Rata kiri dan lebar penuh (width: 100%). */
    .pengaturan-alert {
        margin-top: 22px !important;
        margin-bottom: 22px !important;
        width: 100% !important;
    }

    /* Saat ada notifikasi tepat sebelum .pengaturan-page-body, hilangkan margin-top
       wrapper konten agar jarak bawah alert ke tombol murni ditentukan margin-bottom alert (22px) */
    body .content-scroll-wrapper > main.pengaturan-no-scrollbar .pengaturan-alert + .pengaturan-page-body,
    body .content-scroll-wrapper > main.pengaturan-no-scrollbar:has(.pengaturan-alert) .pengaturan-page-body {
        margin-top: 0 !important;
    }

    /* ===== POSISI TIGA TOMBOL DI TENGAH: Jarak atas & bawah identik 38px ===== */
    .pengaturan-page-body {
        margin-top: 38px !important;
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .pengaturan-modul-wrap {
        margin-bottom: 38px !important;
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

    /* ===== SATU CLASS BERSAMA buat ketiga tombol Pengelolaan Sistem =====
       TAHUN AJARAN (btn-primary / biru), HARI LIBUR (btn-success / hijau) dan
       TEMPAT SAMPAH (btn-danger / merah) semuanya memakai
       .pengaturan-modul-btn ini, jadi ukurannya ditulis SATU KALI dan dijamin
       seragam: tinggi, padding, radius, bobot font, lebar, perataan teks, dan
       satu baris (tidak patah). SATU-SATUNYA yang membedakan ketiganya adalah
       class warna di markup.

       Angka di bawah adalah nilai yang SELAMA INI memang tampil
       (btn-sm px-3 py-2 + radius 12px bawaan layout .btn), jadi TIDAK ada
       perubahan ukuran/font - hanya dikonsolidasi ke satu class, ditambah
       lebar diseragamkan lewat min-width supaya ketiganya sama lebar.

       Yang sengaja TIDAK disentuh: font-size (tetap bawaan .btn-sm +
       override mobile layout), font-family, letter-spacing, dan text-transform
       (semuanya datang dari layout bersama) -> teks tombol tidak berubah. */
    .pengaturan-modul-wrap .pengaturan-modul-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        white-space: nowrap !important;   /* satu baris, tidak patah */
        padding: 0.5rem 1rem !important;  /* = px-3 py-2 (ketiganya sama) */
        border-radius: var(--clean-radius) !important; /* token radius bersama (6px) */
        font-weight: 600 !important;      /* = fw-semibold (ketiganya sama) */
        min-width: 12rem !important;      /* 192px: muat "TEMPAT SAMPAH" */
    }

    /* ===== Ikon di dalam tombol Pengelolaan Sistem =====
       Ikon tong sampah di tombol TEMPAT SAMPAH: ukuran 18px, rata tengah,
       dengan margin-right 8px dari teks. */
    .pengaturan-modul-wrap .pengaturan-modul-btn > svg {
        display: inline-block !important;
        vertical-align: middle !important;
        flex-shrink: 0;
        margin-right: 8px;
    }

    /* ===== WARNA "TAHUN AJARAN": biru primer yang sudah dipakai web ini =====
       Tidak ada satu pun hex baru di sini. Warna diambil dari class
       .btn-primary bawaan Bootstrap yang SUDAH dipakai aplikasi - antara lain
       tombol "Simpan Pengaturan" di halaman ini sendiri, tombol "Tambah Tahun
       Ajaran" di halaman Tahun Ajaran, tombol Dashboard, tombol Buka Scanner,
       dan tombol form Simpan lainnya.

       Nilai class itu (Bootstrap 5.3.3 CDN, token --bs-btn-*):
         base  #0d6efd (--bs-btn-bg)
         hover #0b5ed7 (--bs-btn-hover-bg)
         active #0a58ca (--bs-btn-active-bg)
         cincin fokus rgba(13, 110, 253, ...) (--bs-btn-focus-*)
       Karena hover/active/focus ikut class yang sama, pola gelap-saat-hover
       PERSIS sepadan dengan tombol "HARI LIBUR" (.btn-success) dan "TEMPAT
       SAMPAH" (.btn-danger) di sebelahnya yang juga murni bawaan Bootstrap -
       tanpa override warna sama sekali. Teks tetap putih; kontras putih di
       atas #0d6efd sekitar 4.5:1 (lolos ambang terbaca).

       Karena TIDAK ditulis satupun aturan override warna, tidak ada selector
       yang bisa mengenai elemen lain: tombol "Simpan Pengaturan" di halaman
       ini (btn-primary juga, tapi di luar card ini), tombol biru di halaman
       lain, serta tombol hijau & merah di sebelahnya semuanya tetap persis
       seperti sekarang. Warna ketiga tombol murni dari class masing-masing.

       (Kalau suatu saat mau pakai biru tema --primary-blue #3b62f6 /
       --primary-blue-hover #2563eb ala tombol "Lihat" Catatan Kehadiran,
       cukup tambahkan override ter-scope di sini - tidak perlu ubah markup.) */
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

    <!-- WRAPPER KONTEN PENGATURAN: Menambah jarak vertikal dari header (44px) -->
    <div class="pengaturan-page-body">
        <!-- PENGELOLAAN SISTEM: modul yang dikelola dari halaman Pengaturan.
             Tombol memakai route() nama route yang SUDAH ADA (tidak route baru)
             dan href/aksi tiap tombol tidak berubah.
             Ukuran & perataan ketiga tombol dibawa SATU class bersama
             .pengaturan-modul-btn (lihat CSS di atas); SATU-SATUNYA pembeda
             adalah warna kelas Bootstrap:
               Tahun Ajaran  -> btn-primary (biru primer)  TANPA ikon
               Hari Libur    -> btn-success (hijau)        TANPA ikon
               Tempat Sampah -> btn-danger  (merah)        TANPA ikon (ikon
                               tong sampah sudah dihapus, tidak ada sisa margin)
             Susunan dalam satu array $pengaturanModules supaya mudah ditambah
             tombol lain tanpa mengubah struktur.

             CATATAN: tombol "KELOLA AKUN" yang tadinya ada di baris ini SUDAH
             DIHAPUS (21 Okt 2026) karena jalur masuknya dipindah ke item
             "Kelola Akun" pada pop-up kartu akun di sidebar (sidebar.blade.php),
             memakai route yang sama persis (admin.accounts.index). Route dan
             halaman Kelola Akun (/admin/kelola-akun) TIDAK dihapus. -->
        @php
            $pengaturanModules = [
                ['route' => 'admin.academic-years.index', 'class' => 'btn-primary', 'label' => __('TAHUN AJARAN')],
                ['route' => 'admin.holidays.index', 'class' => 'btn-success', 'label' => __('HARI LIBUR')],
                ['route' => 'admin.students.trash', 'class' => 'btn-danger', 'label' => __('TEMPAT SAMPAH')], // route yang SUDAH ADA; sekarang jadi halaman unified (tab Siswa | Guru | Kelas)
            ];
        @endphp
        <div class="d-flex flex-wrap gap-2 pengaturan-modul-wrap">
            @foreach($pengaturanModules as $modul)
            {{-- Satu class bersama .pengaturan-modul-btn untuk ukuran ketiganya;
                 TEMPAT SAMPAH memakai varian ikon saja (kotak 40px, ikon trash),
                 dua lainnya tetap teks. Route/href tidak diubah. --}}
            <a href="{{ route($modul['route']) }}"
               class="btn btn-sm pengaturan-modul-btn {{ $modul['class'] }}"
               title="{{ ucwords(strtolower($modul['label'])) }}" aria-label="{{ ucwords(strtolower($modul['label'])) }}">
                {{ $modul['label'] }}
            </a>
            @endforeach
        </div>

        <form action="{{ panel_route('settings.update') }}" method="POST" enctype="multipart/form-data" 
              class="settings-mobile-form settings-form-wrapper"
              x-data="{ 
                  checkIn: '{{ old('check_in_time', $settings['check_in_time'] ?? '06:45') }}', 
                  lateLimit: '{{ old('late_limit_time', $settings['late_limit_time'] ?? '07:15') }}' 
              }">
            @csrf

            <!-- Bagian 1: Identitas & Logo Sekolah -->
            <div class="settings-section">
                <div class="section-header">
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #0f172a;">{{ __('Identitas & Logo Sekolah') }}</h5>
                        <div class="small" style="color: #0f172a;">{{ __('Data ini muncul di halaman scan, kartu pelajar, dan laporan PDF.') }}</div>
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
                            <div class="logo-preview-box">
                                <img id="logoPreview" src="{{ \App\Models\Setting::getLogoUrl() }}" onerror="this.outerHTML='<i class=\'bx bx-image text-muted fs-1\'></i>'" alt="{{ __('Logo') }}">
                            </div>
                        </div>
                        <div class="logo-bottom-group">
                            <input type="file" name="school_logo" id="school_logo" class="form-control rounded-3 w-100 @error('school_logo') is-invalid @enderror" accept=".webp,.png,.jpg,.jpeg">
                            <div class="logo-sub-text">
                                <span class="logo-note d-block mt-1" style="font-size: 11px;">{{ __('Ukuran rasio 1:1 disarankan. Maksimal 2MB.') }}</span>
                                @error('school_logo')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div id="logoClientError" class="text-danger small mt-1" style="display:none;"></div>
                                <div id="logoFileInfo" class="small mt-1" style="display:none;">
                                    <i class='bx bx-image me-1'></i><span id="logoFileName"></span>
                                </div>
                                <div id="logoNewPreviewWrap" class="mt-2" style="display:none;">
                                    <div style="font-size: 11px; color: #0f172a;">{{ __('Pratinjau logo baru:') }}</div>
                                    <div class="logo-preview-box mt-1">
                                        <img id="logoNewPreview" alt="{{ __('Pratinjau logo baru') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Pengaturan Waktu Presensi -->
            <div class="settings-section">
                <div class="section-header">
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #0f172a;">{{ __('Jadwal Presensi & Toleransi Keterlambatan') }}</h5>
                        <div class="small" style="color: #0f172a;">{{ __('Atur jam masuk dan batas waktu agar siswa dicatat tepat waktu atau terlambat.') }}</div>
                    </div>
                </div>

                <!-- Dua Kartu Berdampingan (Gaya Dashboard Modern) -->
                <div class="jadwal-cards-grid">
                    <!-- Kartu JAM MASUK -->
                    <div class="jadwal-card">
                        <!-- Watermark Icon Besar di Sisi Kiri -->
                        <i class='bx bx-log-in jadwal-card-watermark watermark-masuk' aria-hidden="true"></i>

                        <!-- Isi Kartu: Kiri & Kanan -->
                        <div class="jadwal-card-content">
                            <div class="jadwal-card-left">
                                <span class="text-uppercase fw-bold clock-card-label" style="font-size: 0.72rem; letter-spacing: 0.05em; color: #0f172a;">{{ __('JAM MASUK') }}</span>
                            </div>
                            <div class="jadwal-card-right d-flex align-items-baseline clock-time-wrap flex-wrap justify-content-end">
                                <input type="time" name="check_in_time" id="check_in_time" x-model="checkIn" class="dashboard-time-input clock-time-masuk" value="{{ old('check_in_time', $settings['check_in_time'] ?? '') }}" required>
                                <span class="clock-wib" style="font-size: 0.75rem; font-weight: 700; color: #0f172a; margin-left: 4px; letter-spacing: 0.02em;">WIB</span>
                            </div>
                        </div>
                        @error('check_in_time')
                        <div class="text-danger small mt-1 text-end position-relative" style="z-index: 1;">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Kartu BATAS TERLAMBAT -->
                    <div class="jadwal-card">
                        <!-- Watermark Icon Besar di Sisi Kiri -->
                        <i class='bx bx-alarm-exclamation jadwal-card-watermark watermark-terlambat' aria-hidden="true"></i>

                        <!-- Isi Kartu: Kiri & Kanan -->
                        <div class="jadwal-card-content">
                            <div class="jadwal-card-left">
                                <span class="text-uppercase fw-bold clock-card-label" style="font-size: 0.72rem; letter-spacing: 0.05em; color: #0f172a;">{{ __('BATAS TERLAMBAT') }}</span>
                            </div>
                            <div class="jadwal-card-right d-flex align-items-baseline clock-time-wrap flex-wrap justify-content-end">
                                <input type="time" name="late_limit_time" id="late_limit_time" x-model="lateLimit" class="dashboard-time-input clock-time-terlambat" value="{{ old('late_limit_time', $settings['late_limit_time'] ?? '') }}" required>
                                <span class="clock-wib" style="font-size: 0.75rem; font-weight: 700; color: #0f172a; margin-left: 4px; letter-spacing: 0.02em;">WIB</span>
                            </div>
                        </div>
                        @error('late_limit_time')
                        <div class="text-danger small mt-1 text-end position-relative" style="z-index: 1;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Catatan Biru -->
            <div class="alert alert-info border-0 m-0 small w-100" style="display: flex; width: 100%; align-items: center; flex-wrap: wrap; padding: 10px 14px; border-radius: 8px; gap: 4px; line-height: 1.6; color: #0f172a;">
                <i class='bx bx-info-circle text-primary me-1' style="font-size: 14px; vertical-align: -0.125em;"></i>
                <span>{{ __('Siswa yang melakukan scan antara') }}</span>
                <strong style="color: #166534; font-weight: 700;" x-text="checkIn">{{ $settings['check_in_time'] ?? '' }}</strong>
                <span>{{ __('hingga') }}</span>
                <strong style="color: #c2410c; font-weight: 700;" x-text="lateLimit">{{ $settings['late_limit_time'] ?? '' }}</strong>
                <span>{{ __('dicatat Tepat Waktu.') }}</span>
                <span>{{ __('Scan di atas') }}</span>
                <strong style="color: #c2410c; font-weight: 700;" x-text="lateLimit">{{ $settings['late_limit_time'] ?? '' }}</strong>
                <span>{{ __('otomatis tercatat') }}</span>
                <strong style="color: #c2410c; font-weight: 700;">{{ __('Terlambat') }}</strong>
                <span>{{ __('lengkap dengan selisih menit keterlambatannya.') }}</span>
            </div>

            <!-- Bagian 4: Tombol Simpan -->
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
        var fileInfo = document.getElementById('logoFileInfo');
        var fileName = document.getElementById('logoFileName');
        var previewWrap = document.getElementById('logoNewPreviewWrap');
        var previewImg = document.getElementById('logoNewPreview');
        var logoErr = document.getElementById('logoClientError');
        if (logoInput) {
            logoInput.addEventListener('change', function () {
                var file = logoInput.files && logoInput.files[0];
                if (logoErr) { logoErr.style.display = 'none'; logoErr.textContent = ''; }
                logoInput.classList.remove('is-invalid');
                if (!file) {
                    fileInfo.style.display = 'none';
                    previewWrap.style.display = 'none';
                    return;
                }
                var allowed = ['image/webp', 'image/png', 'image/jpeg'];
                var extOk = /\.(webp|png|jpe?g)$/i.test(file.name || '');
                if ((file.type && allowed.indexOf(file.type) === -1) || !extOk) {
                    if (logoErr) { logoErr.textContent = {!! json_encode(__('Format logo harus .webp, .png, .jpg, atau .jpeg.')) !!}; logoErr.style.display = ''; }
                    logoInput.classList.add('is-invalid');
                    logoInput.value = '';
                    fileInfo.style.display = 'none';
                    previewWrap.style.display = 'none';
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    if (logoErr) { logoErr.textContent = {!! json_encode(__('Ukuran logo maksimal 2 MB.')) !!}; logoErr.style.display = ''; }
                    logoInput.classList.add('is-invalid');
                    logoInput.value = '';
                    fileInfo.style.display = 'none';
                    previewWrap.style.display = 'none';
                    return;
                }
                fileName.textContent = file.name;
                fileInfo.style.display = '';
                var reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewWrap.style.display = '';
                };
                reader.readAsDataURL(file);
            });
        }

        // 3. Validasi klien sebelum submit. Server tetap memvalidasi ulang;
        //    ini hanya agar pesan muncul tepat di bawah field yang salah.
        var form = document.querySelector('.settings-mobile-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                var nip = document.getElementById('headmaster_nip');
                var phone = document.getElementById('school_phone');
                var npsn = document.getElementById('school_npsn');
                var checkIn = document.getElementById('check_in_time');
                var lateLimit = document.getElementById('late_limit_time');
                var ok = true;

                [nip, phone, npsn, checkIn, lateLimit].forEach(function (el) {
                    if (el) el.classList.remove('is-invalid');
                });
                var nipErr = document.getElementById('nipClientError');
                var phoneErr = document.getElementById('phoneClientError');
                var npsnErr = document.getElementById('npsnClientError');
                if (nipErr) nipErr.style.display = 'none';
                if (phoneErr) phoneErr.style.display = 'none';
                if (npsnErr) npsnErr.style.display = 'none';

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
                if (checkIn && lateLimit && checkIn.value && lateLimit.value && lateLimit.value <= checkIn.value) {
                    tandai(lateLimit);
                    if (lateLimit) {
                        // Tampilkan error di bawah field batas terlambat
                        var existingErr = lateLimit.parentNode.querySelector('.time-client-error');
                        if (!existingErr) {
                            var errDiv = document.createElement('div');
                            errDiv.className = 'text-danger small mt-1 time-client-error';
                            errDiv.textContent = {!! json_encode(__('Batas toleransi harus lebih besar dari jam buka masuk.')) !!};
                            lateLimit.parentNode.appendChild(errDiv);
                        }
                    }
                }

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