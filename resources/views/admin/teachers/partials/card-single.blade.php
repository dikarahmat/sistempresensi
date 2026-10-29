@php
    $teacherItem = $teacherItem ?? null;
    $schoolName = $schoolName ?? \App\Models\Setting::getSchoolName();

    $teacherName = $teacherName ?? ($teacherItem->name ?? '-');

    $teacherNipRaw = trim((string) ($teacherNip ?? ($teacherItem->nip ?? '')));
    $teacherNip = ($teacherNipRaw === '' || $teacherNipRaw === '-' || strcasecmp($teacherNipRaw, 'null') === 0)
        ? '-'
        : $teacherNipRaw;

    static $__bgGreen = null;

    if ($__bgGreen === null) {
        $__bgPath = public_path('images/card-bg-single-green.png');
        $__bgGreen = file_exists($__bgPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($__bgPath))
            : '';
    }

    $__bg = $__bgGreen;

    $qrSrc = $qrSrc ?? ($qrBase64 ?? ($teacherItem->qr_base64 ?? null));

    if (!$qrSrc && $teacherItem) {
        try {
            $__token = \App\Services\TeacherCardService::qrTokenFor($teacherItem->nip ?? null, (string) ($teacherItem->id ?? ''));
            $__svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->margin(0)->generate($__token);
            $qrSrc = 'data:image/svg+xml;base64,' . base64_encode($__svg);
        } catch (\Throwable $e) {
            $qrSrc = null;
        }
    }

    $logoSrc = $logoSrc ?? ($logoBase64 ?? null);

    if (!$logoSrc) {
        $__logoPath = public_path(\App\Models\Setting::getLogo());
        if (file_exists($__logoPath)) {
            $__logoMime = mime_content_type($__logoPath) ?: 'image/png';
            $logoSrc = 'data:' . $__logoMime . ';base64,' . base64_encode(file_get_contents($__logoPath));
        }
    }
@endphp
<div style="position:relative; width:53.9mm; height:85.6mm; overflow:hidden; margin:0; padding:0; border:0; font-family:Arial, Helvetica, sans-serif; background:#dcefe2; -webkit-print-color-adjust:exact; print-color-adjust:exact; page-break-inside:avoid;">

    @if($__bg)
        <img src="{{ $__bg }}" alt="" style="position:absolute; left:0; top:0; width:53.9mm; height:85.6mm; object-fit:cover; display:block;">
    @endif

    @if($logoSrc)
        <img src="{{ $logoSrc }}" alt="" style="position:absolute; left:3mm; top:3.1mm; width:7mm; height:7mm;">
    @endif

    <div style="position:absolute; left:10.7mm; top:3.8mm; width:40mm; font-size:7pt; font-weight:bold; color:#ffffff; line-height:normal; white-space:nowrap; overflow:hidden;">{{ mb_strtoupper($schoolName) }}</div>
    <div style="position:absolute; left:10.8mm; top:6.3mm; width:40mm; font-size:5pt; font-weight:bold; color:#fcd34d; line-height:normal; white-space:nowrap; overflow:hidden;">KARTU PRESENSI DIGITAL</div>

    <div style="position:absolute; left:0; top:16.5mm; width:53.9mm; font-size:7pt; font-weight:bold; color:#0f172a; text-align:center; line-height:normal; white-space:nowrap; overflow:hidden;">{{ mb_strtoupper($teacherName) }}</div>
    <div style="position:absolute; left:0; top:20.6mm; width:53.9mm; font-size:6.8pt; font-weight:bold; color:#0f172a; text-align:center; line-height:normal; white-space:nowrap; overflow:hidden;">{{ $teacherNip }}</div>
    <div style="position:absolute; left:0; top:24.6mm; width:53.9mm; font-size:8pt; font-weight:bold; color:#14532d; text-align:center; line-height:normal; white-space:nowrap; overflow:hidden;">GURU</div>

    <div style="position:absolute; left:10.95mm; top:30.5mm; width:32mm; height:32mm; background:#ffffff;">
        @if($qrSrc)
            <img src="{{ $qrSrc }}" alt="" style="position:absolute; left:1mm; top:1mm; width:30mm; height:30mm;">
        @endif
    </div>
    <div style="position:absolute; left:0; top:64.8mm; width:53.9mm; font-size:5pt; font-weight:bold; letter-spacing:0.4mm; color:#14532d; text-align:center;">SCAN PRESENSI</div>

    <div style="position:absolute; left:0; top:{{ !empty($isPdf) ? '76.5mm' : '78.6mm' }}; width:53.9mm; height:7mm; line-height:7mm; display:flex; align-items:center; justify-content:center; text-align:center; font-size:4.5pt; font-style:italic; color:#ffffff;">Tunjukkan kartu saat presensi masuk</div>
</div>
