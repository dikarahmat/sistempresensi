<div class="col-12 col-lg-7">
    <div class="card card-modern p-4">
        <h5 class="fw-bold mb-4" style="color: #0f172a;">{{ __('Informasi Guru') }}</h5>

        <div class="info-label">{{ __('NIP') }}</div>
        <div class="info-value">{{ \App\Services\TeacherCardService::displayNip($teacher->nip) }}</div>

        <div class="info-label">{{ __('Nama Lengkap') }}</div>
        <div class="info-value">{{ $teacher->name }}</div>

        <div class="info-label">{{ __('Jenis Kelamin') }}</div>
        <div class="info-value">{{ ($teacher->gender == 'Perempuan' || $teacher->gender == 'P') ? __('Perempuan') : __('Laki-laki') }}</div>

        <div class="info-label">{{ __('Tempat Lahir') }}</div>
        <div class="info-value">{{ $teacher->birth_place ?: '-' }}</div>

        <div class="info-label">{{ __('Tanggal Lahir') }}</div>
        <div class="info-value">{{ $teacher->birth_date ? $teacher->birth_date->translatedFormat('d F Y') : '-' }}</div>

        <div class="info-label">{{ __('Tugas Kelas') }}</div>
        <div class="info-value">
            @if($teacher->schoolClass)
                {{ __('Kelas ') }}{{ $teacher->schoolClass->name }}
            @else
                {{ __('Guru Pengajar') }}
            @endif
        </div>

        <div class="info-label">{{ __('No. Telepon / WA') }}</div>
        <div class="info-value mb-0">{{ $teacher->phone_number ?? $teacher->phone ?? '-' }}</div>
    </div>
</div>

<div class="col-12 col-lg-5">
    <div class="card card-modern p-4">
        <h5 class="fw-bold mb-4" style="color: #0f172a;">{{ __('Kartu Presensi') }}</h5>

        <div class="kp-card-box">
            <div class="kp-card-scale">@include('admin.teachers.partials.card-single', [
                'teacherItem' => $teacher,
                'teacherName' => $teacher->name,
                'teacherNip' => $teacher->nip ?? '',
            ])</div>
        </div>

        <a href="{{ panel_route('guru.download-card', $teacher->id) }}"
           class="btn-download-green btn-progress"
           id="btnDownloadCard"
           data-download data-no-download data-dl-progress>
            {{ __('Download Kartu Presensi') }}
        </a>
    </div>
</div>
