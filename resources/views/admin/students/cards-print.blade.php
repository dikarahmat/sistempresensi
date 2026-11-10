@php
    $schoolName = \App\Models\Setting::getSchoolName();

    $__bgPath = public_path('images/card-bg-single-green.png');
    $__bg = file_exists($__bgPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($__bgPath))
        : '';

    $logoSrc = null;
    $__logoPath = public_path(\App\Models\Setting::getLogo());
    if (file_exists($__logoPath)) {
        $__logoMime = mime_content_type($__logoPath) ?: 'image/png';
        $logoSrc = 'data:' . $__logoMime . ';base64,' . base64_encode(file_get_contents($__logoPath));
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $pageTitle ?? 'Kartu Presensi Guru' }}</title>
    <style>
        @page { size: A4; margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 8mm; background: #f1f5f9; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { max-width: 210mm; margin: 0 auto 8mm; display: flex; gap: 8px; justify-content: space-between; align-items: center; }
        .toolbar button { background: #059669; color: #fff; border: 0; border-radius: 8px; padding: 10px 18px; font-weight: bold; cursor: pointer; }
        .toolbar span { font-size: 13px; color: #475569; }
        .sheet { display: flex; flex-wrap: wrap; gap: 4mm; max-width: 210mm; margin: 0 auto; }
        .card { position: relative; width: 53.9mm; height: 85.6mm; overflow: hidden; background: #dcefe2; page-break-inside: avoid; break-inside: avoid; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .card .abs { position: absolute; left: 0; width: 53.9mm; text-align: center; white-space: nowrap; overflow: hidden; line-height: normal; font-weight: bold; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { max-width: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>{{ $teachers->count() }} kartu. Di jendela cetak pilih "Simpan sebagai PDF" untuk mengunduh.</span>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <div class="sheet">
        @foreach($teachers as $teacher)
            @php
                $__nip = trim((string) ($teacher->nip ?? ''));
                $__nip = ($__nip === '' || $__nip === '-' || strcasecmp($__nip, 'null') === 0) ? '-' : $__nip;

                $qrSrc = null;
                try {
                    $__token = \App\Services\TeacherCardService::qrTokenFor($teacher->nip ?? null, $teacher->name ?? null);
                    $__svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->margin(0)->generate($__token);
                    $qrSrc = 'data:image/svg+xml;base64,' . base64_encode($__svg);
                } catch (\Throwable $e) {
                    $qrSrc = null;
                }
            @endphp
            <div class="card">
                @if($__bg)
                    <img src="{{ $__bg }}" alt="" style="position:absolute; left:0; top:0; width:53.9mm; height:85.6mm; object-fit:cover;">
                @endif
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="" style="position:absolute; left:3mm; top:3.1mm; width:7mm; height:7mm;">
                @endif

                <div style="position:absolute; left:10.7mm; top:3.8mm; width:40mm; font-size:7pt; font-weight:bold; color:#fff; white-space:nowrap; overflow:hidden;">{{ mb_strtoupper($schoolName) }}</div>
                <div style="position:absolute; left:10.8mm; top:6.3mm; width:40mm; font-size:5pt; font-weight:bold; color:#fcd34d; white-space:nowrap; overflow:hidden;">KARTU PRESENSI DIGITAL</div>

                <div class="abs" style="top:16.5mm; font-size:7pt; color:#0f172a;">{{ mb_strtoupper($teacher->name ?? '-') }}</div>
                <div class="abs" style="top:20.6mm; font-size:6.8pt; color:#0f172a;">{{ $__nip }}</div>
                <div class="abs" style="top:24.6mm; font-size:8pt; color:#14532d;">GURU</div>

                <div style="position:absolute; left:10.95mm; top:30.5mm; width:32mm; height:32mm; background:#fff;">
                    @if($qrSrc)
                        <img src="{{ $qrSrc }}" alt="" style="position:absolute; left:1mm; top:1mm; width:30mm; height:30mm;">
                    @endif
                </div>
                <div class="abs" style="top:64.8mm; font-size:5pt; letter-spacing:0.4mm; color:#14532d;">SCAN PRESENSI</div>

                <div style="position:absolute; left:0; top:78.6mm; width:53.9mm; height:7mm; line-height:7mm; text-align:center; font-size:4.5pt; font-style:italic; color:#fff;">Tunjukkan kartu saat presensi masuk</div>
            </div>
        @endforeach
    </div>

    @if(!empty($autoPrint))
        <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
    @endif
</body>
</html>