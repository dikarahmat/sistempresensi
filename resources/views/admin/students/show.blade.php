@extends('layouts.app')

@section('title', __('Detail Siswa'))
@section('page_title', __('Detail Siswa'))
@section('page_subtitle', __('Informasi biodata, kelas, dan kode QR presensi siswa'))

@push('styles')
<style>
    .card-modern {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.02);
    }

    .info-label {
        font-size: 0.85rem;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 1.1rem;
    }

    .info-value:last-child {
        margin-bottom: 0 !important;
    }

    /* Tombol download: selebar isi box */
    .btn-download-green {
        background-color: #10b981;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: var(--clean-radius, 8px);
        border: none;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        width: 100%;
        min-height: 42px;
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
    }
    .btn-download-green:hover { background-color: #059669; color: #ffffff; box-shadow: 0 6px 8px rgba(16, 185, 129, 0.3); }

    /* Preview kartu: 1.6x ukuran CR80, di tengah box */
    .kp-card-box {
        width: calc(53.98mm * 1.6);
        height: calc(85.6mm * 1.6);
        max-width: 100%;
        margin: 0 auto 1.5rem auto;
        position: relative;
        overflow: hidden;
    }

    .kp-card-scale {
        width: 53.98mm;
        height: 85.6mm;
        transform: scale(1.6);
        transform-origin: top left;
    }

    .kp-card-scale > * {
        margin: 0;
    }

    
</style>
@endpush

@section('content')
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-4" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-4" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    <div class="row g-4 align-items-start">
        <!-- Informasi Siswa (Kolom Kiri) -->
        <div class="col-12 col-lg-7">
            <div class="card card-modern p-4">
                <h5 class="fw-bold mb-4" style="color: #0f172a;">{{ __('Informasi Siswa') }}</h5>

                <div class="info-label">{{ __('Kelas') }}</div>
                <div class="info-value">{{ __('Kelas ') }}{{ $student->schoolClass->name ?? '-' }}</div>

                <div class="info-label">{{ __('NISN') }}</div>
                <div class="info-value">{{ $student->nisn ?: '-' }}</div>

                <div class="info-label">{{ __('Nama Lengkap') }}</div>
                <div class="info-value">{{ $student->name }}</div>

                <div class="info-label">{{ __('Jenis Kelamin') }}</div>
                <div class="info-value mb-0">{{ __($student->gender ?? 'Laki-laki') }}</div>
            </div>
        </div>

        <!-- Box Kartu Presensi (Kolom Kanan) -->
        <div class="col-12 col-lg-5">
            <div class="card card-modern p-4">
                <h5 class="fw-bold mb-4" style="color: #0f172a;">{{ __('Kartu Presensi') }}</h5>

                <!-- Preview kartu (tengah) -->
                <div class="kp-card-box">
                    <div class="kp-card-scale">@include('admin.students.partials.card-single', [
                        'studentItem' => $student,
                        'studentName' => $student->name,
                        'studentNisn' => $student->nisn,
                        'studentClass' => $student->schoolClass->name ?? '-',
                    ])</div>
                </div>

                <!-- Tombol download (tepat di bawah kartu, selebar box, di luar kp-card-box) -->
                <a href="{{ panel_route('students.download-card', $student->id) }}" class="btn-download-green btn-progress" id="btnDownloadCard" data-download data-no-download data-dl-progress>{{ __('Download Kartu Presensi') }}</a>
            </div>
        </div>
    </div>
@endsection

