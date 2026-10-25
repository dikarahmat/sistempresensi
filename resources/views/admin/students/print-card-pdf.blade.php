<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Presensi Siswa - {{ $schoolName ?? '' }}</title>
    <style>
        @page { size: a4 portrait; margin: 0; }
        html, body { margin: 0; padding: 0; background: #ffffff; }
        .wrap { padding: 10mm 0 0 10mm; width: 53.98mm; height: 85.6mm; }
    </style>
</head>
<body>
<div class="wrap">
    @include('admin.students.partials.card-single', [
        'studentItem'  => $student ?? $studentItem ?? null,
        'logoSrc'      => $logoBase64 ?? null,
        'qrSrc'        => $qrBase64 ?? null,
        'schoolName'   => $schoolName ?? '',
        'studentName'  => $studentName ?? ($student->name ?? ''),
        'studentNisn'  => $studentNisn ?? ($student->nisn ?? ''),
        'studentClass' => $studentClass ?? ($student->schoolClass->name ?? '-'),
        'isPdf'        => true,
    ])
</div>
</body>
</html>