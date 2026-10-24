<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Kartu Presensi Siswa') }} - {{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</title>

    <style>
        @page { size: A4 portrait; margin: 0; }
        html, body { margin:0; padding:0; background:#e5e7eb; font-family: Arial, Helvetica, sans-serif; }
        /* Netralkan margin auto di partial agar kartu ikut padding wrapper (kiri/atas),
           bukan ter-center. Hanya berlaku di view ini — massal tidak terpengaruh. */
        table.presensi-card-table { margin:0 !important; }
    </style>
</head>
<body>
@php
    $studentItem = $student ?? $studentItem ?? null;

    $schoolName = $schoolName ?? ($pengaturan->school_name ?? ($setting->school_name ?? \App\Models\Setting::getSchoolName()));

    // Logo base64 (dompdf tidak bisa fetch asset)
    $logoSrc = $logoBase64 ?? null;
    if (!$logoSrc) {
        $logoSetting = $pengaturan->logo ?? ($setting->logo ?? \App\Models\Setting::getLogo());
        $logoPath = public_path($logoSetting);
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        } else {
            $logoSrc = asset($logoSetting);
        }
    }

    // QR base64
    $qrSrc = $qrBase64 ?? null;
    if (!$qrSrc && $studentItem) {
        $token = $studentItem->nisn ?: $studentItem->qr_token;
        try {
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(140)->margin(0)->generate($token);
            $qrSrc = 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable $e) {
            $qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=" . urlencode($token);
        }
    }

    $studentName  = $studentName  ?? ($studentItem->name ?? $studentItem->nama ?? '-');
    $studentClass = $studentClass ?? ($studentItem->schoolClass->name ?? $studentItem->kelas ?? '-');
    $studentNisn  = $studentNisn  ?? ($studentItem->nisn ?? null);
@endphp

{{-- Kartu memakai partial DESAIN BARU card-single (khusus fitur single).
     Jarak dari kiri/atas diberikan lewat padding wrapper (margin @page diabaikan dompdf). --}}
<div style="padding:14mm 0 0 14mm;">
@include('admin.students.partials.card-single', [
    'studentItem'  => $studentItem,
    'logoSrc'      => $logoSrc,
    'qrSrc'        => $qrSrc,
    'schoolName'   => $schoolName,
    'studentName'  => $studentName,
    'studentNisn'  => $studentNisn,
    'studentClass' => $studentClass,
])
</div>
</body>
</html>
