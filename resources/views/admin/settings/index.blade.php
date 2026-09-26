@extends('layouts.app')

@section('title', 'Pengaturan Sistem')
@section('page_title', 'Pengaturan Sistem Dinamis')
@section('page_subtitle', 'Sesuaikan profil sekolah, toleransi keterlambatan presensi, dan gateway notifikasi.')

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
</style>
@endpush

@section('content')
    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs mb-4" role="alert">
        <i class='bx bx-check-circle me-1'></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(isset($errors) && $errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs mb-4" role="alert">
        <div class="fw-bold mb-1"><i class='bx bx-error-circle me-1'></i> Terjadi Kesalahan Input:</div>
        <ul class="mb-0 small">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Card 1: Identitas Sekolah -->
        <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white mb-3">
            <div class="section-header">
                <div class="section-icon bg-primary-subtle text-primary">
                    <i class='bx bx-buildings'></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="color: #0f172a;">Identitas & Logo Sekolah</h5>
                    <div class="text-muted small">Tampil pada Header Scanner QR, Cetak Kartu Pelajar, dan Rekap Laporan PDF.</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-8">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Sekolah Resmi</label>
                            <input type="text" name="school_name" class="form-control rounded-3" value="{{ old('school_name', $settings['school_name']) }}" required placeholder="Contoh: SMP Presensi PGRI">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Judul Aplikasi / Tab Browser</label>
                            <input type="text" name="app_title" class="form-control rounded-3" value="{{ old('app_title', $settings['app_title']) }}" placeholder="Contoh: Sistem Presensi Sekolah">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Alamat Sekolah</label>
                            <input type="text" name="school_address" class="form-control rounded-3" value="{{ old('school_address', $settings['school_address']) }}" placeholder="Jl. Raya Pendidikan...">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">No. Telepon / Kontak</label>
                            <input type="text" name="school_phone" class="form-control rounded-3" value="{{ old('school_phone', $settings['school_phone']) }}" placeholder="(021) xxxxxxx">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Nama Kepala Sekolah</label>
                            <input type="text" name="headmaster_name" class="form-control rounded-3" value="{{ old('headmaster_name', $settings['headmaster_name']) }}" placeholder="Contoh: Drs. H. Ahmad Sudrajat, M.Pd">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">NIP Kepala Sekolah</label>
                            <input type="text" name="headmaster_nip" class="form-control rounded-3" value="{{ old('headmaster_nip', $settings['headmaster_nip']) }}" placeholder="Contoh: 19750812 200003 1 002 atau -">
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold">Logo Sekolah (.webp, .png, .jpg)</label>
                    <div class="d-flex align-items-center gap-3">
                        <div class="logo-preview-box">
                            <img src="{{ asset(\App\Models\Setting::getLogo()) }}" onerror="this.outerHTML='<i class=\'bx bx-image text-muted fs-1\'></i>'" alt="Logo">
                        </div>
                        <div class="flex-grow-1">
                            <input type="file" name="school_logo" class="form-control rounded-3 form-control-sm" accept=".webp,.png,.jpg,.jpeg">
                            <span class="text-muted" style="font-size: 11px;">Ukuran rasio 1:1 disarankan. Maksimal 2MB.</span>
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
                <div class="section-icon bg-warning-subtle text-warning-emphasis">
                    <i class='bx bx-time-five'></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="color: #0f172a;">Jadwal Presensi & Toleransi Keterlambatan</h5>
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
            <button type="submit" class="btn btn-primary btn-sm w-100 w-md-auto px-4 py-2 rounded-3 fw-semibold shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5">
                <i class='bx bx-save fs-6'></i>
                <span>Simpan Semua Pengaturan</span>
            </button>
        </div>
    </form>
@endsection