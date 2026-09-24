@php
    $userRole = Auth::user()->role ?? 'admin';

    $dashboardRoute = match($userRole) {
        'admin' => route('admin.dashboard'),
        'guru' => route('guru.dashboard'),
        'kesiswaan' => route('kesiswaan.dashboard'),
        default => url('/'),
    };
    $isDashboardActive = request()->routeIs('admin.dashboard') || request()->routeIs('guru.dashboard') || request()->routeIs('kesiswaan.dashboard');

    $absensiRoute = match($userRole) {
        'admin' => route('admin.absensi.index'),
        'guru' => route('guru.absensi.index'),
        'kesiswaan' => route('kesiswaan.absensi.index'),
        default => null,
    };
    $isAbsensiActive = request()->routeIs('admin.absensi*') || request()->routeIs('guru.absensi*') || request()->routeIs('guru.presensi*') || request()->routeIs('kesiswaan.absensi*');

    $kehadiranRoute = match($userRole) {
        'admin' => route('admin.kehadiran'),
        'guru' => route('guru.kehadiran'),
        'kesiswaan' => route('kesiswaan.kehadiran'),
        default => null,
    };
    $isKehadiranActive = request()->routeIs('admin.kehadiran*') || request()->routeIs('guru.kehadiran*') || request()->routeIs('kesiswaan.kehadiran*');

    $rekapRoute = match($userRole) {
        'admin' => route('admin.rekap'),
        'guru' => route('guru.rekap'),
        'kesiswaan' => route('kesiswaan.rekap.index'),
        default => '#',
    };
    $isRekapActive = request()->routeIs('admin.rekap*') || request()->routeIs('guru.rekap*') || request()->routeIs('kesiswaan.rekap*');

    $siswaRoute = match($userRole) {
        'admin' => route('admin.students.index'),
        'guru' => route('guru.students'),
        'kesiswaan' => route('kesiswaan.students.index'),
        default => '#',
    };
    $isSiswaActive = request()->routeIs('admin.students.*') || request()->routeIs('guru.students*') || request()->routeIs('kesiswaan.students.*');
@endphp

<!-- Sidebar Bootstrap 5: Pixel-Perfect Alignment, Pure Logo, Anti-Lemot -->
<aside class="d-flex flex-column flex-shrink-0 text-white rounded-end" style="width: 260px; height: 100vh; background-color: #3b62f6; position: relative; z-index: 40;">
    
<!-- ATAS: Header Logo & Nama Sekolah (Ukuran Pas & Proporsional) -->
    <div class="flex-shrink-0" style="padding: 1.5rem 1rem 1rem 1.25rem;">
        <div class="d-flex align-items-center gap-2 pb-3 border-bottom border-light border-opacity-25">
            <!-- Pure Logo dibesarkan sedikit jadi 46px -->
            <img src="{{ asset(\App\Models\Setting::getLogo()) }}" 
                 alt="Logo Sekolah" 
                 class="" style="width: 46px; height: 46px; object-fit: contain;"
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
            <div class="d-flex flex-column text-uppercase fw-bold text-nowrap" style="font-size: 0.9rem; letter-spacing: 0px; line-height: 1.2;">
                <span>{{ $brandLine1 }}</span>
                @if(!empty($brandLine2))
                <span>{{ $brandLine2 }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- TENGAH: Menu Utama (Scrollable) -->
    <div class="flex-grow-1 overflow-auto py-2">
        <ul class="nav nav-pills flex-column mb-auto gap-1">
            
            <!-- 1. GRUP UTAMA -->
            <li class="nav-item">
                <div class="text-uppercase fw-bold text-white-50 mb-2" style="font-size: 0.65rem; letter-spacing: 1px; padding: 0 1.5rem;">UTAMA</div>
                <a href="{{ $dashboardRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 mb-2 {{ $isDashboardActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;">
                    <i class='bx bxs-dashboard fs-5 {{ $isDashboardActive ? 'text-primary' : 'text-white' }}'></i> 
                    Dashboard
                </a>
            </li>

            <!-- 2. GRUP REKAP -->
            @if($absensiRoute || $kehadiranRoute || $rekapRoute)
            <li class="nav-item mt-2">
                <div class="text-uppercase fw-bold text-white-50 mb-2" style="font-size: 0.65rem; letter-spacing: 1px; padding: 0 1.5rem;">REKAP</div>
                
                @if($absensiRoute)
                <a href="{{ $absensiRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 mb-1 {{ $isAbsensiActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;">
                    <i class='bx bx-qr-scan fs-5 {{ $isAbsensiActive ? 'text-primary' : 'text-white' }}'></i> 
                    Presensi
                </a>
                @endif

                @if($kehadiranRoute)
                <a href="{{ $kehadiranRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 mb-1 {{ $isKehadiranActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;">
                    <i class='bx bx-calendar-check fs-5 {{ $isKehadiranActive ? 'text-primary' : 'text-white' }}'></i> 
                    Kehadiran
                </a>
                @endif

                <a href="{{ $rekapRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 mb-1 {{ $isRekapActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;">
                    <i class='bx bx-folder-open fs-5 {{ $isRekapActive ? 'text-primary' : 'text-white' }}'></i> 
                    Rekap
                </a>
            </li>
            @endif

            <!-- 3. GRUP KALENDER AKADEMIK -->
            @if($userRole === 'admin')
            <li class="nav-item mt-2">
                <div class="text-uppercase fw-bold text-white-50 mb-2" style="font-size: 0.65rem; letter-spacing: 1px; padding: 0 1.5rem;">KALENDER AKADEMIK</div>
                
                <a href="{{ route('admin.academic-years.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 mb-1 {{ request()->routeIs('admin.academic-years.*') ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;">
                    <i class='bx bx-time fs-5 {{ request()->routeIs('admin.academic-years.*') ? 'text-primary' : 'text-white' }}'></i> 
                    Tahun Ajaran
                </a>
                
                <a href="{{ route('admin.holidays.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 mb-1 {{ request()->routeIs('admin.holidays.*') ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;">
                    <i class='bx bx-calendar-x fs-5 {{ request()->routeIs('admin.holidays.*') ? 'text-primary' : 'text-white' }}'></i> 
                    Hari Libur
                </a>
            </li>
            @endif

            <!-- 4. GRUP KELAS -->
            <li class="nav-item mt-2">
                <div class="text-uppercase fw-bold text-white-50 mb-2" style="font-size: 0.65rem; letter-spacing: 1px; padding: 0 1.5rem;">KELAS</div>
                
                <a href="{{ $siswaRoute }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 mb-1 {{ $isSiswaActive ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;">
                    <i class='bx bx-user fs-5 {{ $isSiswaActive ? 'text-primary' : 'text-white' }}'></i> 
                    Siswa
                </a>

                @if($userRole === 'admin' || $userRole === 'kesiswaan')
                <a href="{{ $userRole === 'kesiswaan' ? route('kesiswaan.teachers.index') : route('admin.guru.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 mb-1 {{ request()->routeIs('admin.guru.*') || request()->routeIs('admin.teachers.*') || request()->routeIs('kesiswaan.teachers.*') ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;">
                    <i class='bx bx-group fs-5 {{ request()->routeIs('admin.guru.*') || request()->routeIs('admin.teachers.*') || request()->routeIs('kesiswaan.teachers.*') ? 'text-primary' : 'text-white' }}'></i> 
                    Guru & Wali Kelas
                </a>
                
                <a href="{{ $userRole === 'kesiswaan' ? route('kesiswaan.classes.index') : route('admin.classes.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 mb-1 {{ request()->routeIs('admin.classes.*') || request()->routeIs('admin.kelas.*') || request()->routeIs('kesiswaan.classes.*') ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;">
                    <i class='bx bx-buildings fs-5 {{ request()->routeIs('admin.classes.*') || request()->routeIs('admin.kelas.*') || request()->routeIs('kesiswaan.classes.*') ? 'text-primary' : 'text-white' }}'></i> 
                    Kelas
                </a>
                @endif
            </li>

            <!-- 5. GRUP PENGATURAN -->
            @if($userRole === 'admin')
            <li class="nav-item mt-2">
                <div class="text-uppercase fw-bold text-white-50 mb-2" style="font-size: 0.65rem; letter-spacing: 1px; padding: 0 1.5rem;">PENGATURAN</div>
                <a href="{{ route('admin.settings.index') }}" 
                   class="nav-link text-white d-flex align-items-center gap-3 mb-1 {{ request()->routeIs('admin.settings.*') ? 'active bg-white text-primary shadow-sm fw-medium' : '' }}" 
                   style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;">
                    <i class='bx bx-cog fs-5 {{ request()->routeIs('admin.settings.*') ? 'text-primary' : 'text-white' }}'></i> 
                    Pengaturan
                </a>
            </li>
            @endif
        </ul>
    </div>

    <!-- BAWAH: Tombol Log Out -->
    <div class="border-top border-light border-opacity-25" style="padding: 1rem 0.75rem; background-color: #3b62f6;">
        <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" 
                    class="btn w-100 d-flex align-items-center gap-3 text-white border-0" 
                    style="padding: 0.6rem 0.75rem; background-color: transparent; border-radius: 12px; transition: 0.2s;"
                    onmouseover="this.style.backgroundColor='#dc3545'" 
                    onmouseout="this.style.backgroundColor='transparent'">
                <i class='bx bx-log-out fs-5'></i> 
                Log Out
            </button>
        </form>
    </div>
</aside>