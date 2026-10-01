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
    $isSiswaActive = panel_is('students*');

    $kelasRoute = panel_route('classes.index');
    $isKelasActive = panel_is('classes*') || panel_is('kelas*');
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
    
<!-- ATAS: Header Logo & Nama Sekolah (Ukuran Pas & Proporsional) -->
    <div class="sidebar-brand flex-shrink-0">
        <div class="d-flex align-items-center gap-2 pb-3 border-bottom border-light border-opacity-25">
            <!-- Pure Logo dibesarkan sedikit jadi 46px -->
            <img src="{{ asset(\App\Models\Setting::getLogo()) }}" 
                 alt="Logo Sekolah" 
                 class="" style="width: 46px; height: 46px; object-fit: contain;"
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
            <!-- Font-size dinaikkan halus ke 0.9rem -->
            <div class="d-flex flex-column text-uppercase fw-medium text-nowrap" style="font-size: 0.9rem; letter-spacing: 0px; line-height: 1.2;">
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
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bxs-dashboard fs-5 {{ $isDashboardActive ? 'text-primary' : 'text-white' }}'></i> 
                    Dashboard
                </a>
            </li>

            @if($absensiRoute || $kehadiranRoute || $rekapRoute)
            <li class="nav-item">
                
                @if($absensiRoute)
                <a href="{{ $absensiRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isAbsensiActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-qr-scan fs-5 {{ $isAbsensiActive ? 'text-primary' : 'text-white' }}'></i> 
                    Presensi
                </a>
                @endif

                @if($kehadiranRoute)
                <a href="{{ $kehadiranRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isKehadiranActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-calendar-check fs-5 {{ $isKehadiranActive ? 'text-primary' : 'text-white' }}'></i> 
                    Kehadiran
                </a>
                @endif

                <a href="{{ $rekapRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isRekapActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-folder-open fs-5 {{ $isRekapActive ? 'text-primary' : 'text-white' }}'></i> 
                    Rekap
                </a>
            </li>
            @endif

            @if($userRole === 'admin')
            <li class="nav-item">
                
                <a href="{{ route('admin.academic-years.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ request()->routeIs('admin.academic-years.*') ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-time fs-5 {{ request()->routeIs('admin.academic-years.*') ? 'text-primary' : 'text-white' }}'></i> 
                    Tahun Ajaran
                </a>
                
                <a href="{{ route('admin.holidays.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ request()->routeIs('admin.holidays.*') ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-calendar-x fs-5 {{ request()->routeIs('admin.holidays.*') ? 'text-primary' : 'text-white' }}'></i> 
                    Hari Libur
                </a>
            </li>
            @endif

            <li class="nav-item">
                
                <a href="{{ $siswaRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isSiswaActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-user fs-5 {{ $isSiswaActive ? 'text-primary' : 'text-white' }}'></i> 
                    Siswa
                </a>

                @if($isAdminPanel)
                <a href="{{ route('admin.guru.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ request()->routeIs('admin.guru.*') || request()->routeIs('admin.teachers.*') ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-group fs-5 {{ request()->routeIs('admin.guru.*') || request()->routeIs('admin.teachers.*') ? 'text-primary' : 'text-white' }}'></i> 
                    Guru
                </a>
                @endif

                <a href="{{ $kelasRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ $isKelasActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-buildings fs-5 {{ $isKelasActive ? 'text-primary' : 'text-white' }}'></i> 
                    Kelas
                </a>
            </li>

            @if($isAdminPanel)
            <li class="nav-item">
                <a href="{{ route('admin.settings.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 {{ request()->routeIs('admin.settings.*') ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em;">
                    <i class='bx bx-cog fs-5 {{ request()->routeIs('admin.settings.*') ? 'text-primary' : 'text-white' }}'></i> 
                    Pengaturan
                </a>
            </li>
            @endif

            <!-- GARIS PEMBATAS: Memisahkan menu navigasi utama dengan tombol Logout.
                 Styling identik dengan garis pembatas di bawah header logo (border-light border-opacity-25). -->
            <li class="nav-item" style="padding: 0.5rem 0.75rem;">
                <div class="border-bottom border-light border-opacity-25" style="border-top-width: 0;"></div>
            </li>

            <!-- LOGOUT: Tombol keluar ditempatkan di bawah garis pembatas, terpisah secara visual.
                 Container menggunakan margin yang sama persis dengan menu item di atasnya.
                 Form POST langsung (tanpa JS inline) supaya aman dari CSP. -->
            <li class="nav-item">
                <form action="{{ route('logout') }}" method="POST" class="m-0 p-0">
                    @csrf
                    <button type="submit" 
                            class="nav-link text-white d-flex align-items-center gap-3"
                            style="margin: 0 0.75rem; width: calc(100% - 1.5rem); padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s; text-transform: uppercase; letter-spacing: 0.03em; background: transparent; border: none; text-align: left; cursor: pointer;">
                        <i class='bx bx-log-out fs-5 text-white'></i> 
                        Log Out
                    </button>
                </form>
            </li>
        </ul>
    </div>
</aside>