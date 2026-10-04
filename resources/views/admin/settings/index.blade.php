@extends('layouts.app')

@section('title', 'Pengaturan Sistem')
@section('page_title', 'Pengaturan Sistem Dinamis')
@section('page_subtitle', 'Sesuaikan profil sekolah, toleransi keterlambatan')

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
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f1f5f9;
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

    .logo-preview-box {
        width: 90px;
        height: 90px;
        border-radius: 12px;
        border: 2px dashed #cbd5e1;
        background-color: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }
    .logo-preview-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    /* ===== Optimalisasi Mobile (< 768px): Clean Look — desktop tidak tersentuh ===== */
    @media (max-width: 767.98px) {
        .settings-mobile-form {
            padding-bottom: 1rem;
        }
        .section-header {
            gap: 0.6rem;
            margin-bottom: 1rem;
            padding-bottom: 0.6rem;
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
        .logo-preview-box {
            width: 64px;
            height: 64px;
            border-radius: 10px;
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

    /* ===== Tombol "TAHUN AJARAN" (kuning) =====
       HANYA teksnya yang diubah jadi PUTIH. Kuning Bootstrap (.btn-warning =
       #ffc107) terlalu terang sehingga teks putih tidak terbaca, jadi
       warnanya digelapkan sedikit ke #d99a00 (tetap kuning, ukuran & radius
       tombol tidak berubah). Scope-nya dikunci ke
       .pengaturan-modul-wrap .btn-warning, jadi tombol "HARI LIBUR" (hijau)
       dan "TEMPAT SAMPAH" (merah) di sebelahnya TIDAK ikut tersentuh.
       Tombol ini tidak memakai ikon, jadi tidak ada ikon yang perlu
       ikut diubah. */
    .pengaturan-modul-wrap .btn-warning {
        background-color: #d99a00 !important;
        border-color: #d99a00 !important;
        color: #ffffff !important;
    }

    .pengaturan-modul-wrap .btn-warning:hover,
    .pengaturan-modul-wrap .btn-warning:focus,
    .pengaturan-modul-wrap .btn-warning:active,
    .pengaturan-modul-wrap .btn-warning:focus-visible {
        background-color: #c28700 !important;
        border-color: #c28700 !important;
        color: #ffffff !important;
    }

    /* ===== Ikon pada tombol "TEMPAT SAMPAH" (Boxicons) =====
       HANYA tombol ini yang memakai ikon; "TAHUN AJARAN" dan "HARI LIBUR"
       tetap polos. Warna, ukuran, link, dan radius tombol tidak berubah.

       Selector-nya dikunci ke .pengaturan-modul-wrap (spesifikasi 2 kelas)
       dengan !important, karena layout bersama punya aturan `.btn` mobile
       (`font-size: 0.85rem !important`) yang menimpa `font-size` 1 kelas.
       Tanpa ini ikon ikut mengecil di HP.

       Ukuran 17px (dalam rentang 16-18px) + line-height:1 dipilih supaya
       line-box ikon (17px) TIDAK lebih besar daripada line-box teks tombol
       (14px x 1.5 = 21px di desktop, 13.6px x 1.5 = 20.4px di mobile).
       Akibatnya tinggi tombol TETAP sama persis dengan dua tombol lainnya,
       trotz ikon. Jarak ke teks 8px, dan sejajar vertikal karena tombolnya
       sudah `d-inline-flex align-items-center`. Warna ikon ikut warna teks
       tombol (putih) - tidak ada color khusus. */
    .pengaturan-modul-wrap .pengaturan-modul-icon {
        font-size: 17px !important;
        line-height: 1 !important;
        width: 17px;
        height: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        margin-right: 8px;
    }
</style>
@endpush

@section('content')
    <!-- Alert Notifikasi: [ .flash-notice-body (ikon + teks) ] [ tombol X ].
         Tombol X center vertikal oleh CSS notifikasi global di layout. -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs mb-4" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs mb-4" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-error-circle fs-5 me-2'></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    @if(isset($errors) && $errors->any())
    {{-- Error validasi form: TETAP tampil (tidak hilang otomatis) sampai user
         memperbaikinya atau menekan tombol X. --}}
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs mb-4" role="alert" data-flash-persist>
        <div class="flash-notice-body">
            <div class="fw-bold mb-1"><i class='bx bx-error-circle me-1'></i> Data belum bisa disimpan. Periksa isian yang bertanda merah.</div>
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    <!-- PENGELOLAAN SISTEM: modul yang dikelola dari halaman Pengaturan.
         Tombol memakai route() nama route yang SUDAH ADA (tidak route baru).
         Warna tombol memakai kelas Bootstrap yang sudah dipakai di halaman lain:
           Tahun Ajaran  -> btn-warning (kuning)   TANPA ikon
           Hari Libur    -> btn-success (hijau)    TANPA ikon
           Tempat Sampah -> btn-danger  (merah)    memakai ikon Boxicons "bx-trash"
         Susunan dalam satu array $pengaturanModules supaya mudah ditambah
         tombol lain tanpa mengubah struktur. Key 'icon' opsional: hanya diisi
         untuk modul yang memang perlu ikon. -->
    @php
        $pengaturanModules = [
            ['route' => 'admin.academic-years.index', 'class' => 'btn-warning', 'label' => 'TAHUN AJARAN'],
            ['route' => 'admin.holidays.index', 'class' => 'btn-success', 'label' => 'HARI LIBUR'],
            ['route' => 'admin.students.trash', 'class' => 'btn-danger', 'label' => 'TEMPAT SAMPAH', 'icon' => 'bx-trash'], // route yang SUDAH ADA; sekarang jadi halaman unified (tab Siswa | Guru | Kelas)
        ];
    @endphp
    <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white mb-3">
        <div class="section-header">
            <div>
                <h5 class="fw-bold mb-0" style="color: #0f172a;">Pengelolaan Sistem</h5>
                <div class="text-muted small">Kelola tahun ajaran, kalender libur, dan tempat sampah data.</div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 pengaturan-modul-wrap">
            @foreach($pengaturanModules as $modul)
            <a href="{{ route($modul['route']) }}"
               class="btn {{ $modul['class'] }} btn-sm rounded-3 px-3 py-2 fw-semibold d-inline-flex align-items-center justify-content-center">
                {{-- Ikon hanya untuk modul yang punya key 'icon' (Tempat Sampah);
                     Tahun Ajaran & Hari Libur tetap polos. --}}
                @if(!empty($modul['icon']))
                <i class="bx {{ $modul['icon'] }} pengaturan-modul-icon" aria-hidden="true"></i>
                @endif
                {{ $modul['label'] }}
            </a>
            @endforeach
        </div>
    </div>

    <form action="{{ panel_route('settings.update') }}" method="POST" enctype="multipart/form-data" class="settings-mobile-form">
        @csrf

        <!-- Card 1: Identitas Sekolah -->
        <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white mb-3">
            <div class="section-header">
                <div>
                    <h5 class="fw-bold mb-0" style="color: #0f172a;">Identitas &amp; Logo Sekolah</h5>
                    <div class="text-muted small">Tampil pada Header Scanner QR, Cetak Kartu Pelajar, dan Rekap Laporan PDF.</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-8">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Sekolah Resmi</label>
                            <input type="text" name="school_name" id="school_name" class="form-control rounded-3 @error('school_name') is-invalid @enderror" value="{{ old('school_name', $settings['school_name']) }}" required placeholder="Contoh: SMP Presensi PGRI">
                            @error('school_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Judul Aplikasi / Tab Browser</label>
                            <input type="text" name="app_title" id="app_title" class="form-control rounded-3 @error('app_title') is-invalid @enderror" value="{{ old('app_title', $settings['app_title']) }}" placeholder="Contoh: Sistem Presensi Sekolah">
                            @error('app_title')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Alamat Sekolah</label>
                            <input type="text" name="school_address" id="school_address" class="form-control rounded-3 @error('school_address') is-invalid @enderror" value="{{ old('school_address', $settings['school_address']) }}" placeholder="Jl. Raya Pendidikan...">
                            @error('school_address')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">No. Telepon / Kontak</label>
                            <input type="text" name="school_phone" id="school_phone" inputmode="numeric" pattern="[0-9]*" maxlength="15" class="form-control rounded-3 @error('school_phone') is-invalid @enderror" value="{{ old('school_phone', $settings['school_phone']) }}" placeholder="10-15 digit angka, tanpa spasi">
                            @error('school_phone')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div id="phoneClientError" class="text-danger small mt-1" style="display:none;">Nomor telepon harus 10-15 digit angka.</div>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Nama Kepala Sekolah</label>
                            <input type="text" name="headmaster_name" id="headmaster_name" class="form-control rounded-3 @error('headmaster_name') is-invalid @enderror" value="{{ old('headmaster_name', $settings['headmaster_name']) }}" placeholder="Contoh: Drs. H. Ahmad Sudrajat, M.Pd">
                            @error('headmaster_name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">NIP Kepala Sekolah</label>
                            <input type="text" name="headmaster_nip" id="headmaster_nip" inputmode="numeric" pattern="[0-9]*" maxlength="18" class="form-control rounded-3 @error('headmaster_nip') is-invalid @enderror" value="{{ old('headmaster_nip', $settings['headmaster_nip']) }}" placeholder="18 digit angka tanpa spasi">
                            @error('headmaster_nip')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div id="nipClientError" class="text-danger small mt-1" style="display:none;">NIP harus 18 digit angka.</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold">Logo Sekolah (.webp, .png, .jpg, .jpeg)</label>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="logo-preview-box">
                            <img id="logoPreview" src="{{ \App\Models\Setting::getLogoUrl() }}" onerror="this.outerHTML='<i class=\'bx bx-image text-muted fs-1\'></i>'" alt="Logo">
                        </div>
                        <div class="flex-grow-1">
                            <input type="file" name="school_logo" id="school_logo" class="form-control rounded-3 form-control-sm @error('school_logo') is-invalid @enderror" accept=".webp,.png,.jpg,.jpeg">
                            <span class="text-muted" style="font-size: 11px;">Ukuran rasio 1:1 disarankan. Maksimal 2MB.</span>
                            @error('school_logo')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div id="logoFileInfo" class="small text-muted" style="display:none;">
                        <i class='bx bx-image me-1'></i><span id="logoFileName"></span>
                    </div>
                    <div id="logoNewPreviewWrap" class="mt-2" style="display:none;">
                        <div class="text-muted" style="font-size: 11px;">Pratinjau logo baru:</div>
                        <div class="logo-preview-box mt-1">
                            <img id="logoNewPreview" alt="Pratinjau logo baru">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Pengaturan Waktu Presensi -->
        <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white mb-3"
             x-data="{ 
                 checkIn: '{{ old('check_in_time', $settings['check_in_time'] ?? '06:45') }}', 
                 lateLimit: '{{ old('late_limit_time', $settings['late_limit_time'] ?? '07:15') }}' 
             }">
            <div class="section-header">
                <div>
                    <h5 class="fw-bold mb-0" style="color: #0f172a;">Jadwal Presensi &amp; Toleransi Keterlambatan</h5>
                    <div class="text-muted small">Menentukan status tepat waktu atau terlambat pada scanner dan rekap kehadiran.</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Jam Buka Masuk (Check-In)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class='bx bx-log-in'></i></span>
                        <input type="time" name="check_in_time" x-model="checkIn" class="form-control rounded-end-3" value="{{ old('check_in_time', $settings['check_in_time']) }}" required>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold">Batas Toleransi Keterlambatan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-danger"><i class='bx bx-alarm-exclamation'></i></span>
                        <input type="time" name="late_limit_time" x-model="lateLimit" class="form-control rounded-end-3" value="{{ old('late_limit_time', $settings['late_limit_time']) }}" required>
                    </div>
                </div>
            </div>

            <div class="p-3 mt-3 rounded-3 bg-light border small text-secondary">
                <i class='bx bx-info-circle text-primary me-1'></i> 
                Siswa yang melakukan scan antara <strong x-text="checkIn">{{ $settings['check_in_time'] }}</strong> hingga <strong x-text="lateLimit">{{ $settings['late_limit_time'] }}</strong> tercatat <strong>Tepat Waktu</strong>. Scan di atas <strong x-text="lateLimit">{{ $settings['late_limit_time'] }}</strong> otomatis tercatat <strong>Terlambat</strong> lengkap dengan selisih menit keterlambatannya.
            </div>
        </div>

        <div class="d-flex justify-content-end mb-4">
            <!-- Tombol SIMPAN: teks saja (tanpa ikon), desktop & mobile. -->
            <button type="submit" class="btn btn-primary btn-sm w-100 w-md-auto px-4 py-2 rounded-3 fw-semibold shadow-xs d-inline-flex align-items-center justify-content-center">
                Simpan Semua Pengaturan
            </button>
        </div>
    </form>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Field angka hanya menerima angka saat diketik (HP & NIP).
        ['school_phone', 'headmaster_nip'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', function () {
                el.value = el.value.replace(/[^0-9]/g, '');
            });
        });

        // 2. Preview logo baru + nama file sebelum disimpan.
        var logoInput = document.getElementById('school_logo');
        var fileInfo = document.getElementById('logoFileInfo');
        var fileName = document.getElementById('logoFileName');
        var previewWrap = document.getElementById('logoNewPreviewWrap');
        var previewImg = document.getElementById('logoNewPreview');
        if (logoInput) {
            logoInput.addEventListener('change', function () {
                var file = logoInput.files && logoInput.files[0];
                if (!file) {
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
                var ok = true;

                [nip, phone].forEach(function (el) {
                    if (el) el.classList.remove('is-invalid');
                });
                var nipErr = document.getElementById('nipClientError');
                var phoneErr = document.getElementById('phoneClientError');
                if (nipErr) nipErr.style.display = 'none';
                if (phoneErr) phoneErr.style.display = 'none';

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

                if (!ok) {
                    e.preventDefault();
                    alert('Data belum bisa disimpan. Periksa isian yang bertanda merah.');
                }
            });
        }
    });
    </script>
    @endpush
@endsection