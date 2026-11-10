@php
    // ========================================================================
    // SIDEBAR BERSAMA ADMIN, GURU & GURU PIKET (single source of truth).
    //   admin : Dashboard, Presensi(dropdown), Kehadiran(dropdown), Rekap(dropdown), Siswa, Guru, Kelas, Pengaturan
    //   guru  : Dashboard, Presensi Siswa, Kehadiran Siswa, Rekap Siswa, Data Siswa, Data Kelas (tanpa dropdown)
    //   piket : Dashboard, Presensi(dropdown Siswa & Guru), Kehadiran(dropdown Siswa & Guru)
    // ========================================================================
    $userRole = Auth::user()->role ?? 'admin';
    $isAdminPanel = ($userRole === 'admin');
    $isGuruPanel = ($userRole === 'guru');
    $isPiketPanel = ($userRole === 'piket');

    // ------------------------------------------------------------------------
    // PENCOCOKAN MENU AKTIF BERDASARKAN URL (selain nama route).
    // Aturan: halaman apapun di dalam sebuah menu (daftar, detail, riwayat,
    // edit, dll.) HARUS tetap menyalakan menu itu + induk dropdown-nya.
    // Nama route halaman turunan sering tidak diawali nama menu, jadi URL
    // dipakai sebagai penentu tambahan.
    //   $urlIs  : diawali prefix panel  (admin/..., guru/..., piket/...)
    //   $urlSeg : segmen di mana saja   (*/kehadiran/siswa*)
    // ------------------------------------------------------------------------
    $panelPrefix = $isAdminPanel ? 'admin' : ($isGuruPanel ? 'guru' : 'piket');
    $urlIs = function (string ...$paths) use ($panelPrefix) {
        return request()->is(...array_map(fn ($p) => $panelPrefix . '/' . $p, $paths));
    };
    $urlSeg = function (string ...$paths) {
        return request()->is(...array_map(fn ($p) => '*/' . $p, $paths));
    };

    $dashboardRoute = panel_route('dashboard');
    $isDashboardActive = request()->routeIs('admin.dashboard', 'guru.dashboard', 'piket.dashboard')
        || $urlIs('dashboard*');

    // PRESENSI (dropdown: Siswa & Guru untuk admin/piket; link langsung untuk guru)
    $absensiRoute       = panel_route('absensi.index');
    $presensiSiswaRoute = $absensiRoute;
    $presensiGuruRoute  = $isGuruPanel ? null : panel_route('presensi.guru');
    if ($presensiGuruRoute === '#') {
        $presensiGuruRoute = null;
    }

    $isPresensiGuruActive  = $presensiGuruRoute !== null
        && (request()->routeIs('*presensi.guru*') || request()->is('presensi/guru*', '*/presensi/guru*'));
    // Siswa = semua halaman presensi yang BUKAN presensi guru (daftar kelas, detail kelas, dll).
    $isPresensiSiswaActive = ! $isPresensiGuruActive
        && (panel_is('absensi*') || panel_is('presensi*') || $urlSeg('absensi*', 'presensi*'));
    $isAbsensiActive       = $isPresensiSiswaActive || $isPresensiGuruActive;

    // KEHADIRAN (dropdown: Siswa & Guru untuk admin/piket; link langsung untuk guru)
    $kehadiranSiswaRoute    = panel_route('kehadiran.siswa');
    $kehadiranGuruRoute     = $isGuruPanel ? null : panel_route('kehadiran.guru');
    if ($kehadiranGuruRoute === '#') {
        $kehadiranGuruRoute = null;
    }
    // Guru dicek dulu; sisanya di bawah /kehadiran (termasuk /kehadiran/siswa/{id}/history)
    // otomatis dianggap Kehadiran Siswa.
    $isKehadiranGuruActive  = $kehadiranGuruRoute !== null
        && (panel_is('kehadiran.guru*') || $urlSeg('kehadiran/guru*'));
    $isKehadiranSiswaActive = ! $isKehadiranGuruActive
        && (panel_is('kehadiran*') || $urlSeg('kehadiran*'));
    $isKehadiranActive      = $isKehadiranSiswaActive || $isKehadiranGuruActive;

    // REKAP (dropdown: Siswa & Guru untuk admin; link langsung untuk guru)
    $rekapSiswaRoute    = panel_route('rekap.siswa');
    $rekapGuruRoute     = $isGuruPanel ? null : panel_route('rekap.guru');
    if ($rekapGuruRoute === '#') {
        $rekapGuruRoute = null;
    }
    // panel_is('rekap') covers alias URL /admin/rekap (route name 'rekap',
    // yang sama halaman dengan /admin/rekap/siswa). Hati-hati: /admin/rekap
    // adalah prefix dari /admin/rekap/guru, jadi 'rekap*' tidak boleh
    // dipakai untuk menentukan siswa vs guru — hanya 'rekap' dan
    // 'rekap.siswa*' untuk siswa, 'rekap.guru*' untuk guru.
    // Guru dicek dulu; sisanya di bawah /rekap otomatis dianggap Rekap Siswa.
    $isRekapGuruActive  = $rekapGuruRoute !== null
        && (panel_is('rekap.guru*') || $urlSeg('rekap/guru*'));
    $isRekapSiswaActive = ! $isRekapGuruActive
        && (panel_is('rekap*') || $urlSeg('rekap*'));
    $isRekapActive      = $isRekapSiswaActive || $isRekapGuruActive;

    $siswaRoute = panel_route('students.index');
    $siswaLabel = __('Siswa');

    // Halaman TEMPAT SAMPAH kini modul di bawah PENGATURAN, bukan di bawah
    // SISWA/GURU/KELAS, jadi dikeluarkan dari syarat aktif modul masing-masing.
    $isTrashPage = request()->routeIs(
        'admin.students.trash',
        'admin.teachers.trash',
        'admin.classes.trash'
    ) || $urlIs('students/trash*', 'teachers/trash*', 'classes/trash*', 'trash*');
    $isSiswaActive = (panel_is('students*') || $urlIs('siswa*', 'students*')) && ! $isTrashPage;

    $kelasRoute = panel_route('classes.index');
    $kelasLabel = __('Kelas');
    $isKelasActive = (panel_is('classes*') || panel_is('kelas*') || $urlIs('kelas*', 'classes*')) && ! $isTrashPage;

    $isGuruActive = (request()->routeIs('admin.guru.*') || request()->routeIs('admin.teachers.*') || $urlIs('guru*', 'teachers*')) && ! $isTrashPage;

    // PENGATURAN (dropdown: Identitas Sekolah, Tahun Ajaran, Hari Libur, Tempat Sampah)
    $isIdentitasActive    = request()->routeIs('admin.settings.*') || $urlIs('settings*');
    $isTahunAjaranActive  = request()->routeIs('admin.academic-years.*') || $urlIs('academic-years*');
    $isHariLiburActive    = request()->routeIs('admin.holidays.*') || $urlIs('holidays*');
    $isSampahActive       = $isTrashPage;
    $isPengaturanActive   = $isIdentitasActive || $isTahunAjaranActive || $isHariLiburActive
                            || $isSampahActive || request()->routeIs('admin.accounts.*') || $urlIs('accounts*');

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

            {{-- DASHBOARD: semua role (admin, guru, piket) --}}
            <li class="nav-item">
                <a href="{{ $dashboardRoute }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isDashboardActive ? $activeCls : '' }}"
                   data-label="{{ __('Dashboard') }}" style="{{ $navStyle }}">
                    <i class='bx bxs-dashboard fs-5 {{ $isDashboardActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Dashboard') }}</span>
                </a>
            </li>

            {{-- PRESENSI: guru memakai link langsung; admin & piket memakai dropdown --}}
            @if($isGuruPanel && $presensiSiswaRoute)
            <li class="nav-item">
                <a href="{{ $presensiSiswaRoute }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isPresensiSiswaActive ? $activeCls : '' }}"
                   data-label="{{ __('Presensi') }}" style="{{ $navStyle }}">
                    <i class='bx bx-qr-scan fs-5 {{ $isPresensiSiswaActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Presensi') }}</span>
                </a>
            </li>
            @elseif($presensiSiswaRoute || $presensiGuruRoute)
            <li class="nav-item">
                <a href="#submenuPresensi"
                   class="nav-link text-white d-flex align-items-center gap-3 sidebar-dropdown-toggle {{ $isAbsensiActive ? $activeCls : '' }}"
                   data-bs-toggle="collapse"
                   role="button"
                   aria-expanded="{{ $isAbsensiActive ? 'true' : 'false' }}"
                   aria-controls="submenuPresensi"
                   data-label="{{ __('Presensi') }}" style="{{ $navStyle }}">
                    <i class='bx bx-qr-scan fs-5 {{ $isAbsensiActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Presensi') }}</span>
                    <i class='bx bx-chevron-down sidebar-dropdown-chevron ms-auto'></i>
                </a>
                <div class="collapse {{ $isAbsensiActive ? 'show' : '' }}" id="submenuPresensi">
                    <ul class="nav flex-column sidebar-submenu">
                        @if($presensiSiswaRoute)
                        <li class="nav-item">
                            <a href="{{ $presensiSiswaRoute }}"
                               class="nav-link sidebar-submenu-link {{ $isPresensiSiswaActive ? 'is-active' : '' }}">
                                <i class='bx bx-user'></i>
                                <span class="sidebar-nav-label">{{ __('PRESENSI SISWA') }}</span>
                            </a>
                        </li>
                        @endif
                        @if($presensiGuruRoute)
                        <li class="nav-item">
                            <a href="{{ $presensiGuruRoute }}"
                               class="nav-link sidebar-submenu-link {{ $isPresensiGuruActive ? 'is-active' : '' }}">
                                <i class='bx bx-group'></i>
                                <span class="sidebar-nav-label">{{ __('PRESENSI GURU') }}</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>
            @endif

            {{-- KEHADIRAN: guru memakai link langsung; admin & piket memakai dropdown --}}
            @if($isGuruPanel && $kehadiranSiswaRoute)
            <li class="nav-item">
                <a href="{{ $kehadiranSiswaRoute }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isKehadiranSiswaActive ? $activeCls : '' }}"
                   data-label="{{ __('Kehadiran') }}" style="{{ $navStyle }}">
                    <i class='bx bx-calendar-check fs-5 {{ $isKehadiranSiswaActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Kehadiran') }}</span>
                </a>
            </li>
            @elseif($kehadiranSiswaRoute || $kehadiranGuruRoute)
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

            {{-- REKAP: guru memakai link langsung; admin memakai dropdown; piket tidak punya Rekap --}}
            @if($isGuruPanel && $rekapSiswaRoute)
            <li class="nav-item">
                <a href="{{ $rekapSiswaRoute }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isRekapSiswaActive ? $activeCls : '' }}"
                   data-label="{{ __('Rekap') }}" style="{{ $navStyle }}">
                    <i class='bx bx-folder-open fs-5 {{ $isRekapSiswaActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Rekap') }}</span>
                </a>
            </li>
            @elseif(! $isPiketPanel && ($rekapSiswaRoute || $rekapGuruRoute))
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
                   data-label="{{ $siswaLabel }}" style="{{ $navStyle }}">
                    <i class='bx bx-user fs-5 {{ $isSiswaActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ $siswaLabel }}</span>
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
                   data-label="{{ $kelasLabel }}" style="{{ $navStyle }}">
                    <i class='bx bx-buildings fs-5 {{ $isKelasActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ $kelasLabel }}</span>
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
             Isi: 1) Kelola Akun (admin saja), 2) garis + Logout. --}}
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

{{-- CSS dropdown Presensi, Kehadiran & Rekap. --}}
<style>
    .sidebar-menu-scroll,
    .sidebar-menu-scroll * { transition: none !important; animation: none !important; }
    .sidebar-menu-scroll .collapsing { transition: none !important; height: auto !important; }

    .sidebar-menu-scroll .nav-link,
    .sidebar-menu-scroll .nav-link.active,
    .sidebar-submenu-link,
    .sidebar-submenu-link.is-active { font-weight: 600 !important; }
    .sidebar-menu-scroll .nav-link i:not(.sidebar-dropdown-chevron),
    .sidebar-submenu-link i { transform: none !important; }

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

    .sidebar-menu-scroll .nav-link:focus,
    .sidebar-menu-scroll .nav-link:focus-visible,
    .sidebar-menu-scroll .nav-link:active { outline: none !important; box-shadow: none; }

    #page-loader, #pageLoader, #loading-overlay, #loadingOverlay, #preloader,
    .page-loader, .loading-overlay, .app-loader, .page-loading, .preloader { display: none !important; }
</style>

<style>
    /* Pengaman: kedua baris nama sekolah TIDAK boleh dibedakan JS/apapun.
       Font, ukuran, ketebalan, spasi, tinggi baris, dan transform identik
       antara line1 dan line2, pada semua mode (desktop, rail, mobile). */
    #sidebar-brand-line1, #sidebar-brand-line2 {
        font-family: inherit !important;
        font-size: 0.9rem !important;
        font-weight: 700 !important;
        letter-spacing: 0 !important;
        line-height: 1.2 !important;
        transform: none !important;
        transform-origin: unset !important;
        zoom: 1 !important;
        white-space: nowrap !important;
        display: inline-block !important;
    }
</style>

{{-- ========================================================================
     MODE SIDEBAR CIUT (rail ikon): panah dropdown diganti titik kecil,
     submenu muncul sebagai flyout di kanan ikon (hover / klik).
     Mode ciut dideteksi dari lebar <aside> (< 110px), jadi tidak bergantung
     pada nama class tombol hamburger di layout.
     ======================================================================== --}}
<div id="sidebarFlyout" role="menu" aria-hidden="true"></div>

<style>
    .sidebar-container.is-rail .sidebar-dropdown-chevron { display: none !important; }
    .sidebar-container.is-rail .sidebar-menu-scroll .collapse,
    .sidebar-container.is-rail .sidebar-menu-scroll .collapsing { display: none !important; }
    .sidebar-container.is-rail .sidebar-dropdown-toggle { position: relative; }
    .sidebar-container.is-rail .sidebar-dropdown-toggle::after {
        content: ""; position: absolute; left: 50%; bottom: 3px;
        width: 4px; height: 4px; margin-left: -2px; border-radius: 50%;
        background: rgba(255,255,255,.85);
    }
    .sidebar-container.is-rail .sidebar-dropdown-toggle.active::after { background: var(--bs-primary, #2f5bea); }

    #sidebarFlyout {
        position: fixed; z-index: 1080; display: none; min-width: 200px;
        background: #fff; border-radius: 12px; padding: 6px;
        box-shadow: 0 8px 24px rgba(15,23,42,.18), 0 2px 6px rgba(15,23,42,.10);
        border: 1px solid rgba(15,23,42,.08);
    }
    #sidebarFlyout.is-open { display: block; }
    #sidebarFlyout .sf-title {
        font-size: .68rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
        color: #94a3b8; padding: 6px 10px 4px;
    }
    #sidebarFlyout .sf-link {
        display: flex; align-items: center; gap: .6rem; padding: .5rem .65rem;
        border-radius: 8px; font-size: .78rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .02em; color: #1e293b; text-decoration: none; white-space: nowrap;
    }
    #sidebarFlyout .sf-link i { font-size: 1.05rem; color: #64748b; }
    #sidebarFlyout .sf-link:hover { background: #f1f5f9; color: #0f172a; }
    #sidebarFlyout .sf-link.is-active { background: rgba(47,91,234,.10); color: var(--bs-primary, #2f5bea); }
    #sidebarFlyout .sf-link.is-active i { color: var(--bs-primary, #2f5bea); }
</style>

<script>
(function () {
    var aside = document.querySelector('aside.sidebar-container');
    var fly = document.getElementById('sidebarFlyout');
    if (!aside || !fly) return;

    var hideTimer = null;

    function isRail() { return aside.getBoundingClientRect().width < 110; }

    function closeFly() {
        fly.classList.remove('is-open');
        fly.setAttribute('aria-hidden', 'true');
    }

    function sync() {
        var rail = isRail();
        aside.classList.toggle('is-rail', rail);
        if (!rail) closeFly();
    }

    function openFly(toggle) {
        var target = document.querySelector(toggle.getAttribute('href'));
        var list = target ? target.querySelector('.sidebar-submenu') : null;
        if (!list) return;

        clearTimeout(hideTimer);
        fly.innerHTML = '';

        var title = document.createElement('div');
        title.className = 'sf-title';
        title.textContent = toggle.getAttribute('data-label') || '';
        fly.appendChild(title);

        list.querySelectorAll('a.sidebar-submenu-link').forEach(function (src) {
            var a = document.createElement('a');
            a.href = src.getAttribute('href');
            a.className = 'sf-link' + (src.classList.contains('is-active') ? ' is-active' : '');
            a.setAttribute('role', 'menuitem');
            var srcIcon = src.querySelector('i');
            if (srcIcon) {
                var i = document.createElement('i');
                i.className = srcIcon.className;
                a.appendChild(i);
            }
            var span = document.createElement('span');
            var label = src.querySelector('.sidebar-nav-label');
            span.textContent = (label ? label.textContent : src.textContent).trim();
            a.appendChild(span);
            fly.appendChild(a);
        });

        var r = toggle.getBoundingClientRect();
        fly.classList.add('is-open');
        fly.setAttribute('aria-hidden', 'false');
        fly.style.left = (r.right + 8) + 'px';
        var top = Math.min(r.top, window.innerHeight - fly.offsetHeight - 8);
        fly.style.top = Math.max(8, top) + 'px';
    }

    function scheduleClose() {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(closeFly, 150);
    }

    aside.querySelectorAll('.sidebar-dropdown-toggle').forEach(function (toggle) {
        toggle.addEventListener('mouseenter', function () { if (isRail()) openFly(toggle); });
        toggle.addEventListener('mouseleave', function () { if (isRail()) scheduleClose(); });
        toggle.addEventListener('click', function (e) {
            if (!isRail()) return;
            e.preventDefault();
            e.stopPropagation();
            openFly(toggle);
        });
    });

    fly.addEventListener('mouseenter', function () { clearTimeout(hideTimer); });
    fly.addEventListener('mouseleave', scheduleClose);

    document.addEventListener('click', function (e) {
        if (!fly.contains(e.target) && !aside.contains(e.target)) closeFly();
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeFly(); });
    window.addEventListener('resize', sync);

    if (window.ResizeObserver) { new ResizeObserver(sync).observe(aside); }
    sync();
})();
</script>