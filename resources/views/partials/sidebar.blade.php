@php
    // ========================================================================
    // SIDEBAR BERSAMA ADMIN, GURU & GURU PIKET (single source of truth).
    //   admin : Dashboard, Presensi, Kehadiran, Rekap, Siswa, Guru, Kelas, Pengaturan
    //   guru  : Dashboard, Presensi, Kehadiran, Rekap, Siswa, Kelas
    //   piket : Dashboard, Presensi, Kehadiran, Rekap
    // ========================================================================
    $userRole = Auth::user()->role ?? 'admin';
    $isAdminPanel = ($userRole === 'admin');
    $isPiketPanel = ($userRole === 'piket');

    $dashboardRoute = panel_route('dashboard');
    $isDashboardActive = request()->routeIs('admin.dashboard', 'guru.dashboard', 'piket.dashboard');

    $absensiRoute = panel_route('absensi.index');
    $isAbsensiActive = panel_is('absensi*') || panel_is('presensi*');

    $kehadiranRoute = panel_route('kehadiran');
    $isKehadiranActive = panel_is('kehadiran*');

    $rekapRoute = panel_route('rekap');
    $isRekapActive = panel_is('rekap*');

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

    $isPengaturanActive = request()->routeIs(
        'admin.settings.*',
        'admin.academic-years.*',
        'admin.holidays.*',
        'admin.accounts.*',
        'admin.students.trash',
        'admin.teachers.trash',
        'admin.classes.trash'
    );

    $navStyle = 'margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;';
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

            {{-- KEHADIRAN: semua role --}}
            @if($kehadiranRoute)
            <li class="nav-item">
                <a href="{{ $kehadiranRoute }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isKehadiranActive ? $activeCls : '' }}"
                   data-label="{{ __('Kehadiran') }}" style="{{ $navStyle }}">
                    <i class='bx bx-calendar-check fs-5 {{ $isKehadiranActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Kehadiran') }}</span>
                </a>
            </li>
            @endif

            {{-- REKAP: semua role --}}
            @if($rekapRoute)
            <li class="nav-item">
                <a href="{{ $rekapRoute }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isRekapActive ? $activeCls : '' }}"
                   data-label="{{ __('Rekap') }}" style="{{ $navStyle }}">
                    <i class='bx bx-folder-open fs-5 {{ $isRekapActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Rekap') }}</span>
                </a>
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

            {{-- PENGATURAN: admin saja. Juga menyala di Tahun Ajaran, Hari Libur, Kelola Akun, dan Tempat Sampah. --}}
            @if($isAdminPanel)
            <li class="nav-item">
                <a href="{{ route('admin.settings.index') }}"
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isPengaturanActive ? $activeCls : '' }}"
                   data-label="{{ __('Pengaturan') }}" style="{{ $navStyle }}">
                    <i class='bx bx-cog fs-5 {{ $isPengaturanActive ? 'text-primary' : 'text-white' }}'></i>
                    <span class="sidebar-nav-label">{{ __('Pengaturan') }}</span>
                </a>
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