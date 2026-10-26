<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Presensi Guru - {{ $schoolName ?? '' }}</title>
    <style>
        @page { size: a4 portrait; margin: 0; }
        html, body { margin: 0; padding: 0; background: #ffffff; }
        .wrap { padding: 10mm 0 0 10mm; width: 53.9mm; height: 85.6mm; }
    </style>
</head>
<body>
<div class="wrap">
    @include('admin.teachers.partials.card-single', [
        'teacherItem' => $teacher ?? $teacherItem ?? null,
        'schoolName'  => $schoolName ?? '',
        'teacherName' => $teacherName ?? ($teacher->name ?? ''),
        'teacherNip'  => $teacherNip ?? ($teacher->nip ?? ''),
        'isPdf'       => true,
    ])
</div>
</body>
</html>
