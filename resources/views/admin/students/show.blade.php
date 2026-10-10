@extends('layouts.app')

@section('title', 'Detail Siswa')
@section('page_title', 'Detail Siswa')
@section('page_subtitle', 'Informasi biodata, kelas, dan kode QR presensi siswa')



@push('styles')
<style>
    .card-modern {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.02);
    }

    .btn-edit-yellow {
        background-color: #f59e0b;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.88rem;
        border: none;
        border-radius: var(--clean-radius);
        padding: 0.5rem 1.25rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        text-decoration: none;
    }
    .btn-edit-yellow:hover { background-color: #d97706; color: #ffffff; }

    .btn-back-white {
        background-color: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
        font-weight: 600;
        font-size: 0.88rem;
        border-radius: var(--clean-radius);
        padding: 0.5rem 1.25rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        text-decoration: none;
    }
    .btn-back-white:hover { background-color: #f1f5f9; color: #0f172a; }

    .info-label {
        font-size: 0.85rem;
        color: #64748b;
        margin-bottom: 0.15rem;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 1.15rem;
    }

    .qr-display-box {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        background: #ffffff;
        border-radius: 14px;
        margin-bottom: 1.5rem;
    }

    .qr-display-box svg, .qr-display-box img {
        width: 220px;
        height: 220px;
        object-fit: contain;
    }

    .btn-download-blue {
        background-color: #3b82f6;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: var(--clean-radius);
        border: none;
        padding: 0.65rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        text-decoration: none;
        margin-bottom: 0.75rem;
        transition: all 0.2s ease;
    }
    .btn-download-blue:hover { background-color: #2563eb; color: #ffffff; }

    .btn-download-green {
        background-color: #059669;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: var(--clean-radius);
        border: none;
        padding: 0.65rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        width: 100%;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-download-green:hover { background-color: #047857; color: #ffffff; }
</style>
@endpush

@section('content')
    {{-- Notifikasi: [ .flash-notice-body (ikon + teks) ] [ tombol X ].
         Tombol X center vertikal oleh CSS notifikasi global di layout. --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-4" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
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
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    <div class="row g-4">
        <!-- Informasi Siswa -->
        <div class="col-12 col-lg-7">
            <div class="card card-modern p-4">
                <h5 class="fw-bold mb-4" style="color: #0f172a;">Informasi Siswa</h5>

                <div class="info-label">Kelas</div>
                <div class="info-value">Kelas {{ $student->schoolClass->name ?? '-' }}</div>

                <div class="info-label">NISN</div>
                <div class="info-value">{{ $student->nisn ?: '-' }}</div>

                <div class="info-label">Nama Lengkap</div>
                <div class="info-value">{{ $student->name }}</div>

                <div class="info-label">Jenis Kelamin</div>
                <div class="info-value">{{ $student->gender ?? 'Laki-laki' }}</div>
            </div>
        </div>

        <!-- Box QR Code Siswa -->
        <div class="col-12 col-lg-5">
            <div class="card card-modern p-4">
                <h5 class="fw-bold mb-3" style="color: #0f172a;">QR Code Siswa</h5>

                <div class="qr-display-box d-flex justify-content-center align-items-center p-3 mb-3 bg-light rounded-3" style="border: 1px dashed #cbd5e1;">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(220)->margin(1)->generate($student->nisn ?: $student->qr_token) !!}
                </div>

                <!-- Tombol Download QR Saja (Tanpa Ikon) -->
                <a href="{{ panel_route('students.download-qr', $student->id) }}" class="btn-download-blue btn-progress" data-download data-no-download data-dl-progress>
                    Download QR Code
                </a>

                <!-- Tombol Download Kartu Presensi PDF (Tanpa Ikon) -->
                <a href="{{ panel_route('students.download-card', $student->id) }}" class="btn-download-green btn-progress" id="btnDownloadCard" style="text-decoration: none;" data-download data-no-download data-dl-progress>
                    Download Kartu Presensi
                </a>
            </div>
        </div>
    </div>
@endsection