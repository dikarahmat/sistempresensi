{{--
| KARTU PRESENSI — DESAIN BARU (fitur single & massal)
|--------------------------------------------------------------------------
| Standar Dimensi : CR80 / KTP (53,98 mm x 85,6 mm), sudut 3,18 mm
| Latar           : PNG base64 (public/images/card-bg-single.png) — navy header
|                   13,25 mm + garis emas 0,75 mm, badan biru langit #d6e4fa
|                   dengan grafis geometris, footer navy + garis emas.
| Elemen absolut di atas latar (dompdf kompatibel, tanpa flexbox/grid):
|   - Logo 6,5 mm (tanpa lingkaran/latar) di header
|   - Nama sekolah putih bold 7pt + "KARTU PRESENSI DIGITAL" #fcd34d 5pt
|   - Nama/NISN/Kelas center hitam bold tanpa label (font sama dengan
|     card.blade.php: nama 7pt nowrap, NISN 6,8pt, kelas 8pt), mulai ~17,5 mm
|     (line-height 1,2, margin 0,5mm — posisi & jarak baris normal)
|   - QR 30 mm di atas kotak putih 31 mm (padding 0,5 mm, siku / radius 0),
|     posisi eksplisit left 11,49 mm top 33,07 mm (center, tanpa margin:auto);
|     sisi bawah kotak (33,07+31=64,07mm) PAS di top trapesium kanan
|     (polygon y=200+translate 56 = 256 unit SVG × 85,6/342 = 64,07mm);
|     QR SVG data-URI margin(0) — modul hitam-putih dari tepi ke tepi,
|     tanpa quiet-zone bawaan generator (putih = padding div saja).
|   - "SCAN PRESENSI" bold 5pt letter-spacing 0,3 mm, top 65,07 mm (gap 1mm
|     di bawah kotak), center sempurna (div left 0, width 53,98 mm,
|     text-align center)
|   - Footer italic 4,5pt #dbe4f5 center
| Variabel SAMA dengan card.blade.php: $logoSrc, $qrSrc, $schoolName,
| $studentName, $studentNisn, $studentClass (+ fallback identik).
|----------------------------------------------------------------------
--}}
@php
    $studentItem = $studentItem ?? null;
    $schoolName  = $schoolName ?? \App\Models\Setting::getSchoolName();

    $studentName  = $studentName  ?? ($studentItem->name ?? $studentItem->nama ?? '-');
    $studentClass = $studentClass ?? ($studentItem->schoolClass->name ?? $studentItem->kelas ?? '-');

    // NISN sebagai TEKS (angka 0 di depan tetap tampil); baris sembunyi bila kosong.
    $studentNisn = trim((string) ($studentNisn ?? ($studentItem->nisn ?? '')));
    if ($studentNisn === '-' || $studentNisn === 'null') {
        $studentNisn = '';
    }

    // Ukuran font identik dengan card.blade.php.
    $nameFontSize  = '7pt';
    $nisnFontSize  = '6.8pt';
    $classFontSize = '8pt';

    // Latar kartu (PNG dengan sudut transparan) — di-cache per render.
    static $cardBgDataUri = null;
    if ($cardBgDataUri === null) {
        $cardBgDataUri = '';
        $cardBgPath = public_path('images/card-bg-single.png');
        if (file_exists($cardBgPath)) {
            $cardBgDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($cardBgPath));
        }
    }

    // Fallback logo (identik dengan card.blade.php)
    $logoSrc = $logoSrc ?? null;
    if (!$logoSrc) {
        $logoPath = public_path(\App\Models\Setting::getLogo());
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        } else {
            $logoSrc = asset(\App\Models\Setting::getLogo());
        }
    }

    // QR sebagai SVG data-URI — margin(0) agar modul dari tepi ke tepi
    // (tanpa quiet-zone bawaan generator). Jalan di browser & dompdf.
    // Rect latar putih di-strip agar putih hanya berasal dari div kotak.
    $qrImg = null;
    $qrToken = $studentItem->nisn ?: ($studentItem->qr_token ?? null);
    if ($qrToken) {
        try {
            $qrSvg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->margin(0)->generate($qrToken);
            // Buang rect latar putih bawaan generator.
            $qrSvg = preg_replace('/<rect[^>]*fill\s*=\s*["\']#?fff(?:fff)?["\'][^>]*\/?>/i', '', $qrSvg);
            $qrImg = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
        } catch (\Throwable $e) {
            $qrImg = null;
        }
    }
    if (!$qrImg) {
        // Fallback: $qrSrc dari pemanggil (harus data-URI valid).
        $qrImg = $qrSrc ?? null;
        if ($qrImg && str_starts_with($qrImg, 'data:image/svg+xml')) {
            $svgRaw = base64_decode(substr($qrImg, strpos($qrImg, ',') + 1));
            $svgRaw = preg_replace('/<rect[^>]*fill\s*=\s*["\']#?fff(?:fff)?["\'][^>]*\/?>/i', '', $svgRaw);
            $qrImg = 'data:image/svg+xml;base64,' . base64_encode($svgRaw);
        }
    }
@endphp

<div style="position: relative; width: 53.98mm; height: 85.6mm; font-family: Arial, Helvetica, sans-serif;">
    {{-- Latar kartu (sudut melengkung sudah transparan di PNG) --}}
    @if($cardBgDataUri !== '')
        <img src="{{ $cardBgDataUri }}" style="position: absolute; left: 0; top: 0; width: 53.98mm; height: 85.6mm;" alt="">
    @endif

    {{-- Logo sekolah di header (6,5mm, tanpa lingkaran/latar) --}}
    <img src="{{ $logoSrc }}" style="position: absolute; left: 3mm; top: 3.4mm; width: 6.5mm; height: 6.5mm;" alt="Logo">

     {{-- Nama sekolah + KARTU PRESENSI DIGITAL (tengah blok = tengah logo, 6,65mm) --}}
            <div style="position: absolute; left: 10.8mm; top: 4mm; right: 2mm; text-align: left;">
        <div style="color: #ffffff; font-size: 7pt; font-weight: bold; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
            {{ strtoupper($schoolName) }}
        </div>
        <div style="color: #fcd34d; font-size: 4.5pt; font-weight: bold; line-height: 1.1; letter-spacing: 0.2mm; margin-top: -0.2mm;">
            {{ __('KARTU PRESENSI DIGITAL') }}
        </div>
    </div>

    {{-- Nama, NISN, Kelas — center, tanpa label/badge/strip.
         Posisi & jarak baris normal (line-height 1,2, margin 0,5mm).
         Bawah teks kelas ~27,7mm → jarak ke kotak QR (33,07mm) ~5,4mm. --}}
    <div style="position: absolute; left: 2.5mm; right: 2.5mm; top: 17.5mm; text-align: center;">
        <div style="font-size: {{ $nameFontSize }}; font-weight: bold; text-transform: uppercase; color: #0f172a; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: clip;">
            {{ mb_strtoupper($studentName) }}
        </div>
        @if($studentNisn !== '')
            <div style="font-size: {{ $nisnFontSize }}; font-weight: bold; color: #0f172a; line-height: 1.2; margin-top: 0.5mm; letter-spacing: 0.4px;">
                {{ $studentNisn }}
            </div>
        @endif
        <div style="font-size: {{ $classFontSize }}; font-weight: bold; color: #0f172a; line-height: 1.2; margin-top: 0.5mm; text-transform: uppercase;">
            {{ $studentClass }}
        </div>
    </div>

     {{-- QR: kotak putih 31mm siku, center (53.98-31)/2 = 11.49mm.
         Top 36mm: grup (kotak + SCAN PRESENSI) berada di tengah antara
         bawah teks kelas (~27,7mm) dan garis oranye footer (~78mm). --}}
            <div style="position: absolute; left: 11.49mm; top: 31.07mm; width: 31mm; height: 31mm; background: #ffffff; border-radius: 0;">
        <img src="{{ $qrImg }}" style="position: absolute; left: 0.5mm; top: 0.5mm; width: 30mm; height: 30mm;" alt="QR">
    </div>

       {{-- SCAN PRESENSI: center sempurna, 1mm di bawah kotak putih. --}}
                    <div style="position: absolute; left: 0; top: 63.77mm; width: 53.98mm; text-align: center; font-size: 5pt; font-weight: bold; letter-spacing: 0.3mm; color: #0f172a;">
        {{ __('SCAN PRESENSI') }}
    </div>

    {{-- Teks footer di area navy --}}
    <div style="position: absolute; left: 0; top: 80.6mm; width: 53.98mm; text-align: center; font-size: 4.5pt; font-style: italic; color: #dbe4f5;">
        {{ __('Tunjukkan kartu saat presensi masuk') }}
    </div>
</div>
