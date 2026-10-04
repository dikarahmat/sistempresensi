@php
    // ========================================================================
    // SIDEBAR BERSAMA ADMIN & GURU (single source of truth).
    // Item menu ditentukan oleh role: admin melihat menu penuh, guru melihat
    // DASHBOARD, PRESENSI, KEHADIRAN, REKAP, SISWA, KELAS.
    // ========================================================================
    $userRole = Auth::user()->role ?? 'admin';
    $isAdminPanel = ($userRole === 'admin');

    $dashboardRoute = panel_route('dashboard');
    $isDashboardActive = request()->routeIs('admin.dashboard') || request()->routeIs('guru.dashboard');

    $absensiRoute = panel_route('absensi.index');
    $isAbsensiActive = panel_is('absensi*') || panel_is('presensi*');

    $kehadiranRoute = panel_route('kehadiran');
    $isKehadiranActive = panel_is('kehadiran*');

    $rekapRoute = panel_route('rekap');
    $isRekapActive = panel_is('rekap*');

    $siswaRoute = panel_route('students.index');

    // Halaman TEMPAT SAMPAH (admin.students.trash, admin.teachers.trash, dan
    // admin.classes.trash) kini menjadi modul di bawah PENGATURAN, bukan di bawah
    // SISWA/GURU/KELAS. Ketiga route itu dikeluarkan dari syarat aktif modulnya
    // masing-masing supaya item PENGATURAN saja yang menyala, tidak dua-duanya.
    //(admin.classes.trash sempat terlewat karena route-nya dibuat belakangan.)
    $isTrashPage = request()->routeIs(
        'admin.students.trash',
        'admin.teachers.trash',
        'admin.classes.trash'
    );
    $isSiswaActive = panel_is('students*') && ! $isTrashPage;

    $kelasRoute = panel_route('classes.index');
    $isKelasActive = (panel_is('classes*') || panel_is('kelas*')) && ! $isTrashPage;
@endphp

<!-- Sidebar Bootstrap 5: Pixel-Perfect Alignment, Pure Logo, Anti-Lemot
     Layout dikendalikan CSS (app-sidebar-drawer), bukan inline style, agar
     aturan !important di layout selalu menang di mobile & desktop. -->
<!-- CATATAN DESKTOP: utility pembulatan sudut sisi kanan SENGAJA dihapus dari
     <aside> ini. Wrapper <aside> di app.blade.php memakai `border-radius: 0`
     pada breakpoint >= 1024px supaya sidebar desktop benar-benar PERSGI dan
     menempel penuh dari atas sampai bawah. Sudut melengkung hanya untuk
     drawer mobile (diatur di dalam @media max-width: 1023.98px). -->
<aside class="d-flex flex-column flex-shrink-0 text-white app-sidebar-panel main-sidebar sidebar-container">
    
<!-- ATAS: Header Logo & Nama Sekolah (Ukuran Pas & Proporsional)
         .sidebar-brand-row ditambahkan sebagai hook CSS di layout supaya
         jarak logo & nama sekolah bisa dirapikan HANYA di desktop tanpa
         mengubah tampilan mobile (utility class tetap sama). -->
    <div class="sidebar-brand flex-shrink-0">
        <div class="sidebar-brand-row d-flex align-items-center gap-2 pb-3 border-bottom border-light border-opacity-25">
            <!-- Pure Logo dibesarkan sedikit jadi 46px -->
            <img src="{{ \App\Models\Setting::getLogoUrl() }}" 
                 alt="Logo Sekolah" 
                 class="sidebar-brand-logo" style="width: 46px; height: 46px; object-fit: contain;"
                 loading="lazy"
                 onerror="this.style.display='none'">
            @php
                $rawSchoolName = trim(\App\Models\Setting::getSchoolName());
                $words = preg_split('/\s+/', $rawSchoolName);
                if (count($words) >= 4) {
                    $brandLine1 = implode(' ', array_slice($words, 0, 2));
                    $brandLine2 = implode(' ', array_slice($words, 2));
                } elseif (count($words) >= 2) {
                    $mid = (int) ceil(count($words) / 2);
                    $brandLine1 = implode(' ', array_slice($words, 0, $mid));
                    $brandLine2 = implode(' ', array_slice($words, $mid));
                } else {
                    $brandLine1 = $rawSchoolName;
                    $brandLine2 = '';
                }
            @endphp
            <!-- Font-size dinaikkan halus ke 0.9rem.
                 .sidebar-brand-text disembunyikan saat sidebar collapsed (desktop),
                 menyisakan logo saja di bagian atas sidebar. -->
            <div class="sidebar-brand-text d-flex flex-column text-uppercase fw-medium text-nowrap" style="font-size: 0.9rem; letter-spacing: 0px; line-height: 1.2;">
                <span>{{ $brandLine1 }}</span>
                @if(!empty($brandLine2))
                <span>{{ $brandLine2 }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Scrollable sidebar menu -->
    <div class="flex-grow-1 py-2 sidebar-menu-scroll sidebar-menu-wrapper menu-container">
        <ul class="nav nav-pills flex-column mb-auto" style="gap: 0;">
            <li class="nav-item">
                <a href="{{ $dashboardRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isDashboardActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="Dashboard"
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bxs-dashboard fs-5 {{ $isDashboardActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">Dashboard</span>
                </a>
            </li>

            @if($absensiRoute || $kehadiranRoute || $rekapRoute)
            <li class="nav-item">
                
                @if($absensiRoute)
                <a href="{{ $absensiRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isAbsensiActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="Presensi" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-qr-scan fs-5 {{ $isAbsensiActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">Presensi</span>
                </a>
                @endif

                @if($kehadiranRoute)
                <a href="{{ $kehadiranRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isKehadiranActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="Kehadiran" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-calendar-check fs-5 {{ $isKehadiranActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">Kehadiran</span>
                </a>
                @endif

                <a href="{{ $rekapRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isRekapActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="Rekap" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-folder-open fs-5 {{ $isRekapActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">Rekap</span>
                </a>
            </li>
            @endif

            @if($userRole === 'admin')
            <!-- CATATAN FASE 1: item TAHUN AJARAN & HARI LIBUR sengaja dihapus dari
                 sidebar. Modul tersebut kini diakses dari halaman PENGATURAN
                 (bagian "Pengelolaan Sistem"). Item Pengaturan di bawah tetap
                 menyala di kedua halaman tersebut (lihat kondisi active). -->
            @endif

            <li class="nav-item">
                
                <a href="{{ $siswaRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isSiswaActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="Siswa" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-user fs-5 {{ $isSiswaActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">Siswa</span>
                </a>

                @if($isAdminPanel)
                <a href="{{ route('admin.guru.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ (request()->routeIs('admin.guru.*') || request()->routeIs('admin.teachers.*')) && ! $isTrashPage ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="Guru"
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-group fs-5 {{ (request()->routeIs('admin.guru.*') || request()->routeIs('admin.teachers.*')) && ! $isTrashPage ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">Guru</span>
                </a>
                @endif

                <a href="{{ $kelasRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isKelasActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="Kelas" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-buildings fs-5 {{ $isKelasActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">Kelas</span>
                </a>
            </li>

            @if($isAdminPanel)
            <!-- PENGATURAN juga menyala di halaman Tahun Ajaran, Hari Libur, dan
                     Tempat Sampah (Siswa / Guru / Kelas), karena semua modul itu kini
                     hidup di bawah Pengaturan. -->
            @php $isPengaturanActive = request()->routeIs(
                'admin.settings.*',
                'admin.academic-years.*',
                'admin.holidays.*',
                'admin.students.trash',
                'admin.teachers.trash',
                'admin.classes.trash'
            ); @endphp
            <li class="nav-item">
                <a href="{{ route('admin.settings.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isPengaturanActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="Pengaturan"
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-cog fs-5 {{ $isPengaturanActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">Pengaturan</span>
                </a>
            </li>
            @endif

            <!-- GARIS PEMBATAS: Memisahkan menu navigasi utama dengan tombol Logout.
                 Styling identik dengan garis pembatas di bawah header logo (border-light border-opacity-25). -->
            <li class="nav-item" style="padding: 0.5rem 0.75rem;">
                <div class="border-bottom border-light border-opacity-25" style="border-top-width: 0;"></div>
            </li>

            <!-- LOGOUT: Tombol keluar ditempatkan di bawah garis pembatas, terpisah secara visual.
                 TIDAK lagi mengirim form langsung: klik hanya membuka modal
                 konfirmasi (#logoutConfirmModal) yang berisi form POST + @csrf,
                 sehingga user tidak bisa logout tidak sengaja.

                 PERBAIKAN LEBAR (akar masalah tombol merah kepotong):
                 `width: calc(100% - 1.5rem)` yang lama sebenarnya MENDUPLIKASI
                 perhitungan margin secara manual (1.5rem = 0.75rem kiri + kanan).
                 Sebagian besar item menu TIDAK punya width - lebannya otomatis
                 dari `display:flex` (block-level) dikurangi margin 0 0.75rem.
                 Begitu aturan global `.app-sidebar-drawer .nav-link` memakai
                 `margin: 0 0.75rem 16px 0.75rem !important`, hasil hitungan
                 calc() melenceng sehingga tombol melewati batas sidebar.
                 Solusi: HAPUS width sepenuhnya -> tombol memakai mekanisme
                 lebar yang PERSIS sama dengan item menu lain, plus
                 `box-sizing: border-box` sebagai pengaman. Tidak ada
                 negative margin, tidak ada elemen keluar dari batas sidebar. -->
            <li class="nav-item">
                <button type="button"
                        class="nav-link sidebar-logout-link text-white d-flex align-items-center gap-3"
                        data-bs-toggle="modal"
                        data-bs-target="#logoutConfirmModal"
                        data-label="Log Out"
                        title="Log Out"
                        aria-label="Log Out"
                        style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; box-sizing: border-box; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em; background: transparent; border: none; text-align: left; cursor: pointer;">
                    <i class='bx bx-log-out fs-5 text-white'></i> 
                    <span class="sidebar-nav-label">Log Out</span>
                </button>
            </li>
        </ul>
    </div>
</aside>
