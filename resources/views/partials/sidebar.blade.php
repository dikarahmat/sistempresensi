@php
    // ========================================================================
    // SIDEBAR BERSAMA ADMIN, GURU & GURU PIKET (single source of truth).
    //   admin : Dashboard, Presensi, Kehadiran(dropdown), Rekap(dropdown), Siswa, Guru, Kelas, Pengaturan
    //   guru  : Dashboard, Presensi, Kehadiran(dropdown), Rekap(dropdown), Siswa, Kelas
    //   piket : Dashboard, Presensi, Kehadiran(dropdown), Rekap(dropdown)
    // ========================================================================
    $userRole = Auth::user()->role ?? 'admin';
    $isAdminPanel = ($userRole === 'admin');
    $isPiketPanel = ($userRole === 'piket');

    $dashboardRoute = panel_route('dashboard');
    $isDashboardActive = request()->routeIs('admin.dashboard', 'guru.dashboard', 'piket.dashboard');

    $absensiRoute = panel_route('absensi.index');
    $isAbsensiActive = panel_is('absensi*') || panel_is('presensi*');

    // KEHADIRAN (dropdown: Siswa & Guru)
    $kehadiranSiswaRoute    = panel_route('kehadiran.siswa');
    $kehadiranGuruRoute     = panel_route('kehadiran.guru');
    $isKehadiranSiswaActive = panel_is('kehadiran.siswa*');
    $isKehadiranGuruActive  = panel_is('kehadiran.guru*');
    $isKehadiranActive      = $isKehadiranSiswaActive || $isKehadiranGuruActive || panel_is('kehadiran*');

    // REKAP (dropdown: Siswa & Guru)
    $rekapSiswaRoute    = panel_route('rekap.siswa');
    $rekapGuruRoute     = panel_route('rekap.guru');
    $isRekapSiswaActive = panel_is('rekap.siswa*');
    $isRekapGuruActive  = panel_is('rekap.guru*');
    $isRekapActive      = $isRekapSiswaActive || $isRekapGuruActive || panel_is('rekap*');

    $siswaRoute = panel_route('students.index');

    // Halaman TEMPAT SAMPAH kini modul di bawah PENGATURAN, bukan di bawah
    // SISWA/GURU/KELAS, jadi dikeluarkan dari syarat aktif modul masing-masing.
    $isTrashPage = request()->routeIs(
        'admin.students.trash',
        'admin.teachers.trash',
        'admin.classes.trash'
    );
    $isSiswaActive = panel_is('students*') && ! $isTrashPage;

    $kelasRoute = panel_route('classes.index');
    $isKelasActive = (panel_is('classes*') || panel_is('kelas*')) && ! $isTrashPage;

    $isGuruActive = (request()->routeIs('admin.guru.*') || request()->routeIs('admin.teachers.*')) && ! $isTrashPage;

    // PENGATURAN (dropdown: Identitas Sekolah, Tahun Ajaran, Hari Libur, Tempat Sampah)
    $isIdentitasActive    = request()->routeIs('admin.settings.*');
    $isTahunAjaranActive  = request()->routeIs('admin.academic-years.*');
    $isHariLiburActive    = request()->routeIs('admin.holidays.*');
    $isSampahActive       = $isTrashPage;
    $isPengaturanActive   = $isIdentitasActive || $isTahunAjaranActive || $isHariLiburActive
                            || $isSampahActive || request()->routeIs('admin.accounts.*');

    // Sesuaikan kalau route halaman "Tempat Sampah" lu beda.
    $sampahRoute = \Illuminate\Support\Facades\Route::has('admin.trash.index')
        ? route('admin.trash.index')
        : route('admin.students.trash');

    $navStyle = 'margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.03em;';
    $activeCls = 'active bg-white text-primary shadow-sm fw-medium';
@endphp

{{-- Sidebar Bootstrap 5. Layout dikendalikan CSS (app-sidebar-drawer), bukan inline style.
     Radius sisi kanan <aside> sengaja tidak dipasang di desktop (diatur di app.blade.php). --}}
<aside class="d-flex flex-column flex-shrink-0 text-white app-sidebar-panel main-sidebar sidebar-container">

    {{-- ATAS: Header logo & nama sekolah --}}
    <div class="sidebar-brand flex-shrink-0">
        <div class="sidebar-brand-row d-flex align-items-center gap-2.5 pb-3 border-bottom border-light border-opacity-25">
            <img src="{{ \App\Models\Setting::getLogoUrl() }}"
                 alt="{{ __('Logo Sekolah') }}"
                 class="sidebar-brand-logo" style="width: 36px; height: 36px; object-fit: contain;"
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
            <div class="sidebar-brand-text d-flex flex-column text-uppercase fw-bold text-nowrap" style="font-size: 0.9rem; letter-spacing: 0px; line-height: 1.2;">
                <span id="sidebar-brand-line1" style="display: inline-block;">{{ $brandLine1 }}</span>
                @if(!empty($brandLine2))
                <span id="sidebar-brand-line2" style="display: inline-block;">{{ $brandLine2 }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Menu sidebar (scrollable) --}}
    <div class="flex-grow-1 py-2 sidebar-menu-scroll sidebar-menu-wrapper menu-container">
        <ul class="nav nav-pills flex-column mb-auto" style="gap: 0;">

            {{-- DASHBOARD: semua role --}}
            <li class="nav-item">
                <a href="{{ $dashboardRoute }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isDashboardActive ? $activeCls : '' }}"
                   data-label="{{ __('Dashboard') }}" style="{{ $navStyle }}">
                    <i class='bx bxs-dashboard fs-5 {{ $isDashboardActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Dashboard') }}</span>
                </a>
            </li>

            {{-- PRESENSI: semua role --}}
            @if($absensiRoute)
            <li class="nav-item">
                <a href="{{ $absensiRoute }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isAbsensiActive ? $activeCls : '' }}"
                   data-label="{{ __('Presensi') }}" style="{{ $navStyle }}">
                    <i class='bx bx-qr-scan fs-5 {{ $isAbsensiActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Presensi') }}</span>
                </a>
            </li>
            @endif

            {{-- KEHADIRAN: dropdown Siswa & Guru (semua role) --}}
            @if($kehadiranSiswaRoute || $kehadiranGuruRoute)
            <li class="nav-item">
                <a href="#submenuKehadiran"
                   class="nav-link text-white d-flex align-items-center gap-3 sidebar-dropdown-toggle {{ $isKehadiranActive ? $activeCls : '' }}"
                   data-bs-toggle="collapse"
                   role="button"
                   aria-expanded="{{ $isKehadiranActive ? 'true' : 'false' }}"
                   aria-controls="submenuKehadiran"
                   data-label="{{ __('Kehadiran') }}" style="{{ $navStyle }}">
                    <i class='bx bx-calendar-check fs-5 {{ $isKehadiranActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Kehadiran') }}</span>
                    <i class='bx bx-chevron-down sidebar-dropdown-chevron ms-auto'></i>
                </a>
                <div class="collapse {{ $isKehadiranActive ? 'show' : '' }}" id="submenuKehadiran">
                    <ul class="nav flex-column sidebar-submenu">
                        @if($kehadiranSiswaRoute)
                        <li class="nav-item">
                            <a href="{{ $kehadiranSiswaRoute }}"
                               class="nav-link sidebar-submenu-link {{ $isKehadiranSiswaActive ? 'is-active' : '' }}">
                                <i class='bx bx-user'></i>
                                <span class="sidebar-nav-label">{{ __('Kehadiran Siswa') }}</span>
                            </a>
                        </li>
                        @endif
                        @if($kehadiranGuruRoute)
                        <li class="nav-item">
                            <a href="{{ $kehadiranGuruRoute }}"
                               class="nav-link sidebar-submenu-link {{ $isKehadiranGuruActive ? 'is-active' : '' }}">
                                <i class='bx bx-group'></i>
                                <span class="sidebar-nav-label">{{ __('Kehadiran Guru') }}</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>
            @endif

            {{-- REKAP: dropdown Siswa & Guru (semua role) --}}
            @if($rekapSiswaRoute || $rekapGuruRoute)
            <li class="nav-item">
                <a href="#submenuRekap"
                   class="nav-link text-white d-flex align-items-center gap-3 sidebar-dropdown-toggle {{ $isRekapActive ? $activeCls : '' }}"
                   data-bs-toggle="collapse"
                   role="button"
                   aria-expanded="{{ $isRekapActive ? 'true' : 'false' }}"
                   aria-controls="submenuRekap"
                   data-label="{{ __('Rekap') }}" style="{{ $navStyle }}">
                    <i class='bx bx-folder-open fs-5 {{ $isRekapActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Rekap') }}</span>
                    <i class='bx bx-chevron-down sidebar-dropdown-chevron ms-auto'></i>
                </a>
                <div class="collapse {{ $isRekapActive ? 'show' : '' }}" id="submenuRekap">
                    <ul class="nav flex-column sidebar-submenu">
                        @if($rekapSiswaRoute)
                        <li class="nav-item">
                            <a href="{{ $rekapSiswaRoute }}"
                               class="nav-link sidebar-submenu-link {{ $isRekapSiswaActive ? 'is-active' : '' }}">
                                <i class='bx bx-user'></i>
                                <span class="sidebar-nav-label">{{ __('Rekap Siswa') }}</span>
                            </a>
                        </li>
                        @endif
                        @if($rekapGuruRoute)
                        <li class="nav-item">
                            <a href="{{ $rekapGuruRoute }}"
                               class="nav-link sidebar-submenu-link {{ $isRekapGuruActive ? 'is-active' : '' }}">
                                <i class='bx bx-group'></i>
                                <span class="sidebar-nav-label">{{ __('Rekap Guru') }}</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>
            @endif

            {{-- SISWA: admin & guru (bukan piket) --}}
            @if(! $isPiketPanel && $siswaRoute)
            <li class="nav-item">
                <a href="{{ $siswaRoute }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isSiswaActive ? $activeCls : '' }}"
                   data-label="{{ __('Siswa') }}" style="{{ $navStyle }}">
                    <i class='bx bx-user fs-5 {{ $isSiswaActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Siswa') }}</span>
                </a>
            </li>
            @endif

            {{-- GURU: admin saja --}}
            @if($isAdminPanel)
            <li class="nav-item">
                <a href="{{ route('admin.guru.index') }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isGuruActive ? $activeCls : '' }}"
                   data-label="{{ __('Guru') }}" style="{{ $navStyle }}">
                    <i class='bx bx-group fs-5 {{ $isGuruActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Guru') }}</span>
                </a>
            </li>
            @endif

            {{-- KELAS: admin & guru (bukan piket) --}}
            @if(! $isPiketPanel && $kelasRoute)
            <li class="nav-item">
                <a href="{{ $kelasRoute }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isKelasActive ? $activeCls : '' }}"
                   data-label="{{ __('Kelas') }}" style="{{ $navStyle }}">
                    <i class='bx bx-buildings fs-5 {{ $isKelasActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Kelas') }}</span>
                </a>
            </li>
            @endif

            {{-- PENGATURAN: admin saja. Dropdown: Identitas Sekolah, Tahun Ajaran, Hari Libur, Tempat Sampah. --}}
            @if($isAdminPanel)
            <li class="nav-item">
                <a href="#submenuPengaturan"
                   class="nav-link text-white d-flex align-items-center gap-3 sidebar-dropdown-toggle {{ $isPengaturanActive ? $activeCls : '' }}"
                   data-bs-toggle="collapse"
                   role="button"
                   aria-expanded="{{ $isPengaturanActive ? 'true' : 'false' }}"
                   aria-controls="submenuPengaturan"
                   data-label="{{ __('Pengaturan') }}" style="{{ $navStyle }}">
                    <i class='bx bx-cog fs-5 {{ $isPengaturanActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Pengaturan') }}</span>
                    <i class='bx bx-chevron-down sidebar-dropdown-chevron ms-auto'></i>
                </a>
                <div class="collapse {{ $isPengaturanActive ? 'show' : '' }}" id="submenuPengaturan">
                    <ul class="nav flex-column sidebar-submenu">
                        <li class="nav-item">
                            <a href="{{ route('admin.settings.index') }}"
                               class="nav-link sidebar-submenu-link {{ $isIdentitasActive ? 'is-active' : '' }}">
                                <i class='bx bx-info-circle'></i>
                                <span class="sidebar-nav-label">{{ __('Informasi Sekolah') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.academic-years.index') }}"
                               class="nav-link sidebar-submenu-link {{ $isTahunAjaranActive ? 'is-active' : '' }}">
                                <i class='bx bx-calendar'></i>
                                <span class="sidebar-nav-label">{{ __('Tahun Ajaran') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.holidays.index') }}"
                               class="nav-link sidebar-submenu-link {{ $isHariLiburActive ? 'is-active' : '' }}">
                                <i class='bx bx-sun'></i>
                                <span class="sidebar-nav-label">{{ __('Hari Libur') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ $sampahRoute }}"
                               class="nav-link sidebar-submenu-link {{ $isSampahActive ? 'is-active' : '' }}">
                                <i class='bx bx-trash'></i>
                                <span class="sidebar-nav-label">{{ __('Tempat Sampah') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endif

        </ul>
    </div>

    {{-- KARTU AKUN + POP-UP LOGOUT.
         Klik "Logout" membuka modal konfirmasi #logoutConfirmModal (form POST + @csrf di layout). --}}
    @php
        $accountUser   = Auth::user();
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
                aria-label="{{ __('Menu akun ') }}{{ $accountRole }}">
            <span class="sidebar-account-avatar" aria-hidden="true">
                <i class='bx bx-user'></i>
            </span>
            <span class="sidebar-account-text">
                <span class="sidebar-account-name">{{ __($accountRole) }}</span>
            </span>
            <i class='bx bx-chevron-up sidebar-account-chevron'
               :class="accountOpen ? 'is-open' : ''"
               aria-hidden="true"></i>
        </button>

        {{-- Pop-up akun: muncul di atas kartu (bottom-full + mb-2).
             Isi: 1) Kelola Akun (admin saja), 2) Bahasa ID|EN, 3) garis + Logout. --}}
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

            @if($isAdminPanel)
            <a href="{{ route('admin.accounts.index') }}"
               class="sidebar-account-item sidebar-account-link"
               role="menuitem"
               @click="accountOpen = false">
                <i class='bx bx-cog' aria-hidden="true"></i>
                <span class="sidebar-account-item-text">{{ __('Kelola Akun') }}</span>
            </a>
            @endif

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

{{-- CSS dropdown Kehadiran & Rekap.
     Boleh dipindah ke CSS sidebar / app.blade.php kalau mau lebih rapi. --}}
<style>
    /* Tanpa animasi sama sekali: dropdown buka/tutup langsung, nggak ada geser/transisi */
    .sidebar-menu-scroll,
    .sidebar-menu-scroll * { transition: none !important; animation: none !important; }
    .sidebar-menu-scroll .collapsing { transition: none !important; height: auto !important; }

    /* Font & ikon diam di tempat: berat font sama di semua kondisi (aktif/nggak aktif),
       jadi teks nggak berubah lebar / geser saat diklik */
    .sidebar-menu-scroll .nav-link,
    .sidebar-menu-scroll .nav-link.active,
    .sidebar-submenu-link,
    .sidebar-submenu-link.is-active { font-weight: 600 !important; }
    .sidebar-menu-scroll .nav-link i:not(.sidebar-dropdown-chevron),
    .sidebar-submenu-link i { transform: none !important; }

    /* Kunci lebar supaya sidebar nggak geser/melebar */
    .sidebar-menu-scroll { overflow-x: hidden !important; }
    .sidebar-menu-scroll .nav-pills,
    .sidebar-menu-scroll .nav-item { min-width: 0; max-width: 100%; }
    .sidebar-menu-scroll .collapse,
    .sidebar-menu-scroll .collapsing { overflow: hidden; }

    .sidebar-dropdown-chevron { font-size: 1.1rem; flex-shrink: 0; }
    .sidebar-dropdown-toggle[aria-expanded="true"] .sidebar-dropdown-chevron { transform: rotate(180deg); }
    .sidebar-dropdown-toggle.active .sidebar-dropdown-chevron { color: var(--bs-primary); }

    .sidebar-submenu { margin: .25rem .75rem .25rem 1rem; gap: 2px; flex-wrap: nowrap; min-width: 0; }
    .sidebar-submenu-link {
        display: flex; align-items: center; gap: .6rem;
        min-width: 0; white-space: nowrap; overflow: hidden;
        padding: .45rem .6rem; border-radius: 10px;
        color: rgba(255,255,255,.8); font-size: .72rem;
        text-transform: uppercase; letter-spacing: .02em;
    }
    .sidebar-submenu-link i { flex-shrink: 0; }
    .sidebar-submenu-link .sidebar-nav-label { overflow: hidden; text-overflow: ellipsis; }
    .sidebar-submenu-link:hover { background: rgba(255,255,255,.12); color: #fff; }
    .sidebar-submenu-link.is-active { background: rgba(255,255,255,.2); color: #fff; }

    /* Warna submenu TETAP di semua kondisi (klik/fokus/aktif/visited), nggak jadi biru bawaan Bootstrap */
    .sidebar-submenu-link,
    .sidebar-submenu-link:visited,
    .sidebar-submenu-link:focus,
    .sidebar-submenu-link:focus-visible,
    .sidebar-submenu-link:active { color: rgba(255,255,255,.8) !important; background: transparent; outline: none !important; box-shadow: none !important; }
    .sidebar-submenu-link:hover { color: #fff !important; background: rgba(255,255,255,.12); }
    .sidebar-submenu-link.is-active,
    .sidebar-submenu-link.is-active:focus,
    .sidebar-submenu-link.is-active:active,
    .sidebar-submenu-link.is-active:hover { color: #fff !important; background: rgba(255,255,255,.2); }

    /* Menu utama & toggle dropdown: nggak ada outline/shadow biru pas diklik */
    .sidebar-menu-scroll .nav-link:focus,
    .sidebar-menu-scroll .nav-link:focus-visible,
    .sidebar-menu-scroll .nav-link:active { outline: none !important; box-shadow: none; }

    /* Overlay loading (spinner + layar gelap) saat pindah halaman: dimatikan.
       Nama class/ID ini tebakan umum; kalau overlay lu masih muncul, kirim app.blade.php-nya. */
    #page-loader, #pageLoader, #loading-overlay, #loadingOverlay, #preloader,
    .page-loader, .loading-overlay, .app-loader, .page-loading, .preloader { display: none !important; }
</style>