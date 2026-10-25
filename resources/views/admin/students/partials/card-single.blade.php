@php
    $studentItem = $studentItem ?? null;
    $schoolName  = $schoolName ?? \App\Models\Setting::getSchoolName();

    $studentName  = $studentName ?? ($studentItem->name ?? $studentItem->nama ?? '-');
    $studentClass = $studentClass ?? ($studentItem->schoolClass->name ?? $studentItem->kelas ?? '-');

    $studentNisn = trim((string) ($studentNisn ?? ($studentItem->nisn ?? '')));
    if ($studentNisn === '-' || $studentNisn === 'null') {
        $studentNisn = '';
    }

    // Background SVG/PNG (di-cache sekali per request)
    static $__bgSvg = null;
    static $__bgPng = null;
    if ($__bgSvg === null) {
        $__p = public_path('images/card-bg-single.svg');
        $__bgSvg = file_exists($__p)
            ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($__p))
            : '';
    }
    if ($__bgPng === null) {
        $__pngPath = public_path('images/card-bg-pdf.png');
        $__bgPng = file_exists($__pngPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($__pngPath))
            : '';
    }
    
    $__bg = !empty($isPdf) ? ($__bgPng ?: $__bgSvg) : $__bgSvg;

    $qrSrc = $qrSrc ?? ($qrBase64 ?? null);
    if (!$qrSrc && $studentItem) {
        $__token = $studentItem->nisn ?: ($studentItem->qr_token ?? '');
        if ($__token !== '') {
            try {
                $__svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->margin(0)->generate((string) $__token);
                $qrSrc = 'data:image/svg+xml;base64,' . base64_encode($__svg);
            } catch (\Throwable $e) {
                $qrSrc = null;
            }
        }
    }

    $logoSrc = $logoSrc ?? ($logoBase64 ?? null);
    if (!$logoSrc) {
        $__lp = public_path(\App\Models\Setting::getLogo());
        if (file_exists($__lp)) {
            $__mime = mime_content_type($__lp) ?: 'image/png';
            $logoSrc = 'data:' . $__mime . ';base64,' . base64_encode(file_get_contents($__lp));
        }
    }
@endphp
<div style="position:relative; width:53.98mm; height:85.6mm; overflow:hidden; margin:0; padding:0; border:0; font-family:Arial, Helvetica, sans-serif; background:#d6e4fa;">

    @if($__bg)
        <img src="{{ $__bg }}" alt="" style="position:absolute; left:0; top:0; width:53.98mm; height:85.6mm;">
    @endif

    @if($logoSrc)
        <img src="{{ $logoSrc }}" alt="" style="position:absolute; left:3mm; top:3.1mm; width:7mm; height:7mm;">
    @endif
    <div style="position:absolute; left:10.7mm; top:3.8mm; width:41mm; font-size:7pt; font-weight:bold; color:#ffffff; line-height:normal; white-space:nowrap; overflow:hidden;">{{ mb_strtoupper($schoolName) }}</div>
<div style="position:absolute; left:10.8mm; top:6.3mm; width:41mm; font-size:5pt; font-weight:bold; color:#fcd34d; line-height:normal; white-space:nowrap;">KARTU PRESENSI DIGITAL</div>

    <div style="position:absolute; left:0; top:16.5mm; width:53.98mm; font-size:7pt; font-weight:bold; color:#0f172a; text-align:center; line-height:normal; white-space:nowrap; overflow:hidden;">{{ mb_strtoupper($studentName) }}</div>
    <div style="position:absolute; left:0; top:20.6mm; width:53.98mm; font-size:6.8pt; font-weight:bold; color:#0f172a; text-align:center; line-height:normal; white-space:nowrap; overflow:hidden;">{{ $studentNisn }}</div>
    <div style="position:absolute; left:0; top:24.6mm; width:53.98mm; font-size:8pt; font-weight:bold; color:#0f172a; text-align:center; line-height:normal; white-space:nowrap; overflow:hidden;">{{ $studentClass }}</div>

    <div style="position:absolute; left:10.99mm; top:30.5mm; width:32mm; height:32mm; background:#ffffff;">
        @if($qrSrc)
            <img src="{{ $qrSrc }}" alt="" style="position:absolute; left:1mm; top:1mm; width:30mm; height:30mm;">
        @endif
    </div>
    <div style="position:absolute; left:0; top:64.8mm; width:53.98mm; font-size:5pt; font-weight:bold; letter-spacing:0.4mm; color:#0f172a; text-align:center;">SCAN PRESENSI</div>

<div style="position:absolute; left:0; top:{{ !empty($isPdf) ? '76.5mm' : '78.6mm' }}; width:53.98mm; height:7mm; line-height:7mm; text-align:center; font-size:4.5pt; font-style:italic; color:#ffffff;">Tunjukkan kartu saat presensi masuk</div>
</div>