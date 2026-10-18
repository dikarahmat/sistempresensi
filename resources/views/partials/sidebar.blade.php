@php
    // ========================================================================
    // SIDEBAR BERSAMA ADMIN, GURU & GURU PIKET (single source of truth).
    // Item menu ditentukan oleh role: admin melihat menu penuh, guru melihat
    // DASHBOARD, PRESENSI, KEHADIRAN, REKAP, SISWA, KELAS, sedangkan GURU
    // PIKET hanya melihat PRESENSI dan KEHADIRAN (plus tombol Logout).
    // ========================================================================
    $userRole = Auth::user()->role ?? 'admin';
    $isAdminPanel = ($userRole === 'admin');
    $isPiketPanel = ($userRole === 'piket');

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
                 alt="{{ __('Logo Sekolah') }}" 
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
            @if(!$isPiketPanel)
            <li class="nav-item">
                <a href="{{ $dashboardRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isDashboardActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="{{ __('Dashboard') }}"
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bxs-dashboard fs-5 {{ $isDashboardActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">{{ __('Dashboard') }}</span>
                </a>
            </li>
            @endif

            @if($absensiRoute || $kehadiranRoute || $rekapRoute)
            <li class="nav-item">
                
                @if($absensiRoute)
                <a href="{{ $absensiRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isAbsensiActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="{{ __('Presensi') }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-qr-scan fs-5 {{ $isAbsensiActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">{{ __('Presensi') }}</span>
                </a>
                @endif

                @if($kehadiranRoute)
                <a href="{{ $kehadiranRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isKehadiranActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="{{ __('Kehadiran') }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-calendar-check fs-5 {{ $isKehadiranActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">{{ __('Kehadiran') }}</span>
                </a>
                @endif

                @if(!$isPiketPanel)
                <a href="{{ $rekapRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isRekapActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="{{ __('Rekap') }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-folder-open fs-5 {{ $isRekapActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">{{ __('Rekap') }}</span>
                </a>
                @endif
            </li>
            @endif

            @if($userRole === 'admin')
            <!-- CATATAN FASE 1: item TAHUN AJARAN & HARI LIBUR sengaja dihapus dari
                 sidebar. Modul tersebut kini diakses dari halaman PENGATURAN
                 (bagian "Pengelolaan Sistem"). Item Pengaturan di bawah tetap
                 menyala di kedua halaman tersebut (lihat kondisi active). -->
            @endif

            @if(!$isPiketPanel)
            <li class="nav-item">
                
                <a href="{{ $siswaRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isSiswaActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="{{ __('Siswa') }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-user fs-5 {{ $isSiswaActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">{{ __('Siswa') }}</span>
                </a>

                @if($isAdminPanel)
                <a href="{{ route('admin.guru.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ (request()->routeIs('admin.guru.*') || request()->routeIs('admin.teachers.*')) && ! $isTrashPage ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="{{ __('Guru') }}"
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-group fs-5 {{ (request()->routeIs('admin.guru.*') || request()->routeIs('admin.teachers.*')) && ! $isTrashPage ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">{{ __('Guru') }}</span>
                </a>
                @endif

                <a href="{{ $kelasRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isKelasActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="{{ __('Kelas') }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-buildings fs-5 {{ $isKelasActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">{{ __('Kelas') }}</span>
                </a>
            </li>
            @endif

            @if($isAdminPanel)
            <!-- PENGATURAN juga menyala di halaman Tahun Ajaran, Hari Libur, dan
                     Tempat Sampah (Siswa / Guru / Kelas), karena semua modul itu kini
                     hidup di bawah Pengaturan. -->
            @php $isPengaturanActive = request()->routeIs(
                'admin.settings.*',
                'admin.academic-years.*',
                'admin.holidays.*',
                'admin.accounts.*',
                'admin.students.trash',
                'admin.teachers.trash',
                'admin.classes.trash'
            ); @endphp
            <li class="nav-item">
                <a href="{{ route('admin.settings.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isPengaturanActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}"
                   data-label="{{ __('Pengaturan') }}"
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-cog fs-5 {{ $isPengaturanActive ? 'text-primary' : 'text-white' }}'></i> 
                    <span class="sidebar-nav-label">{{ __('Pengaturan') }}</span>
                </a>
            </li>
            @endif

        </ul>
    </div>

    <!-- KARTU AKUN + POP-UP LOGOUT (pengganti tombol "LOG OUT" lama).
         Item "LOG OUT" beserta garis pemisah di atasnya sudah dihapus dari
         daftar menu; form POST logout TIDAK dihapus - ia tetap dipakai ulang:
         klik item "Logout" pada pop-up memanggil tombol submit milik form
         yang sama (#logoutConfirmBtn di #logoutConfirmModal), sehingga method,
         route, dan @csrf tidak berubah sama sekali.

         Nama & peran di bawah diambil DINAMIS dari user yang sedang login.
         Peran memakai User::roleLabel() (daftar role terpusat di model), dan
         "Administrator" hanya dipakai sebagai fallback bila role kosong. -->
    @php
        $accountUser   = Auth::user();
        $accountName   = trim((string) ($accountUser->name ?? ''));
        $accountName   = $accountName !== '' ? $accountName : __('Pengguna');
        $accountRole   = method_exists($accountUser, 'roleLabel')
            ? trim((string) $accountUser->roleLabel())
            : '';
        $accountRole   = $accountRole !== '' ? $accountRole : __('Administrator');
    @endphp
    <div class="sidebar-account relative flex-shrink-0"
         x-data="{ accountOpen: false }"
         @click.outside="accountOpen = false"
         @keydown.escape.window="accountOpen = false">

        <div class="sidebar-account-divider" aria-hidden="true"></div>

        <button type="button"
                class="sidebar-account-btn"
                @click="accountOpen = !accountOpen"
                aria-haspopup="menu"
                :aria-expanded="accountOpen ? 'true' : 'false'"
                aria-controls="sidebarAccountMenu"
                aria-label="{{ __('Menu akun ') }}{{ $accountName }}">
            <span class="sidebar-account-avatar" aria-hidden="true">
                <i class='bx bx-user'></i>
            </span>
            <span class="sidebar-account-text">
                <span class="sidebar-account-name">{{ $accountName }}</span>
                <span class="sidebar-account-role">{{ __($accountRole) }}</span>
            </span>
            <i class='bx bx-chevron-up sidebar-account-chevron'
               :class="accountOpen ? 'is-open' : ''"
               aria-hidden="true"></i>
        </button>

        <!-- Pop-up akun: MUNCUL DI ATAS kartu lewat `bottom-full`
             (= bottom:100%) + `mb-2`, jadi tidak pernah menimpa kartu dan
             seluruh isi kartu (avatar, nama, peran, chevron) tetap terlihat.
             Lebarnya persis selebar kartu karena memakai `left-0 right-0`
             di dalam container `relative` yang sama dengan kartunya.

             Isi dari atas ke bawah (21 Okt 2026):
               1. Kelola Akun   - hanya admin (route admin.accounts.index,
                                  sama persis dengan tombol yang dihapus dari
                                  halaman Pengaturan).
                2. Bahasa        - ID | EN, tidak berubah.
                3. garis tipis + Logout.


             Klik item "Logout" TIDAK langsung keluar: ia membuka modal
             konfirmasi yang sudah ada (#logoutConfirmModal, gaya lama) berisi
             form POST logout + @csrf, dan sekaligus menutup pop-up ini
             (accountOpen = false). Logika logout di layout tidak berubah. -->
        <div class="sidebar-account-popup absolute bottom-full left-0 right-0 mb-2 z-50"
             id="sidebarAccountMenu"
             role="menu"
             x-cloak
             x-show="accountOpen"
             x-transition:enter="sidebar-account-popup-enter"
             x-transition:enter-start="sidebar-account-popup-enter-start"
             x-transition:enter-end="sidebar-account-popup-enter-end"
             x-transition:leave="sidebar-account-popup-leave"
             x-transition:leave-start="sidebar-account-popup-leave-start"
             x-transition:leave-end="sidebar-account-popup-leave-end">
             <!-- 1. KELOLA AKUN (21 Okt 2026): dipindahkan dari baris tombol
                  "Pengelolaan Sistem" di halaman Pengaturan ke sini. Route
                  yang dipakai PERSIS sama dengan tombol yang dihapus itu
                  (admin.accounts.index -> /admin/kelola-akun), tidak ada
                  route/controller baru. Tombol ini hanya tampil untuk ADMIN,
                 restriction yang sama dengan halaman Kelola Akun (middleware
                  role:admin) sehingga guru & piket tidak pernah melihat
                  tautan yang akan mereka tolak dengan 403.
                  Baris ini hanya link biasa - tidak ada switch. Klik menutup
                  pop-up lebih dulu (@click) lalu halaman berpindah, sama
                  seperti klik menu sidebar lainnya. Ikon bx-cog dipakai
                  karena sudah dipakai project ini (Pintasan Cepat
                  "Pengaturan") - tidak ada pustaka ikon baru. -->
            @if($isAdminPanel)
            <a href="{{ route('admin.accounts.index') }}"
               class="sidebar-account-item sidebar-account-link"
               role="menuitem"
               @click="accountOpen = false">
                <i class='bx bx-cog' aria-hidden="true"></i>
                <span class="sidebar-account-item-text">{{ __('Kelola Akun') }}</span>
            </a>
            @endif

            <!-- PILIH BAHASA (ID / EN): di ATAS item Logout. Tiap pilihan
                  memakai form POST kecil (CSRF dari layout) ke route locale.set,
                  lalu halaman dimuat ulang dengan bahasa pilihan aktif. Tidak
                  ada controller baru. -->
            <div class="sidebar-account-item sidebar-locale-row" role="group" aria-label="{{ __('Bahasa') }}">
                <i class='bx bx-globe' aria-hidden="true"></i>
                <span class="sidebar-account-item-text">{{ __('Bahasa') }}</span>
                <span class="sidebar-locale-seg">
                    @foreach(['id' => 'ID', 'en' => 'EN'] as $localeCode => $localeShort)
                        <form method="POST" action="{{ route('locale.set', ['lang' => $localeCode]) }}" class="sidebar-locale-form">
                            @csrf
                            <button type="submit"
                                    class="sidebar-locale-btn {{ app()->getLocale() === $localeCode ? 'is-active' : '' }}"
                                    lang="{{ $localeCode }}"
                                    @click="accountOpen = false"
                                    aria-label="{{ $localeCode === 'id' ? 'Bahasa Indonesia' : 'English' }}"
                                    @if(app()->getLocale() === $localeCode) aria-current="true" @endif>{{ $localeShort }}</button>
                        </form>
                    @endforeach
                </span>
            </div>

            <div class="sidebar-account-sep" aria-hidden="true"></div>

            <button type="button"
                    class="sidebar-account-item"
                    role="menuitem"
                    data-bs-toggle="modal"
                    data-bs-target="#logoutConfirmModal"
                    @click="accountOpen = false">
                <i class='bx bx-log-out' aria-hidden="true"></i>
                <span>{{ __('Logout') }}</span>
            </button>
        </div>
    </div>
</aside>
