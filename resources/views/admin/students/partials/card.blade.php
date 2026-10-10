{{--
|--------------------------------------------------------------------------
| KARTU PRESENSI DIGITAL — SINGLE SOURCE OF TRUTH
|--------------------------------------------------------------------------
| Standar Dimensi Kartu : CR80 / KTP (53,98 mm x 85,6 mm), radius sudut 3,18 mm
| Acuan Desain          : Kartu Individual "AJIONO PRADIPTA 9B"
|
| Urutan Elemen (Atas ke Bawah):
|   1. HEADER: Logo sekolah + SMP PGRI PARUNGPANJANG + KARTU PRESENSI DIGITAL
|      Garis oranye (#f59e0b) tepat di bawah header
|   2. NAMA SISWA: UKURAN TETAP 7pt (sama untuk semua kartu, tidak dikecilkan
|      berdasarkan panjang nama), langsung di bawah header, TANPA label "NAMA :",
|      kapital, bold, hitam (#0f172a), SATU BARIS (nowrap; nama terpanjang tetap muat).
|   3. NISN: Tepat di bawah nama, center, TANPA label "NISN :",
|      ukuran tetap 6,8pt, bold, hitam (#0f172a),
|      teks polos 10 digit (angka 0 di depan tetap tampil).
|      Baris disembunyikan bila NISN kosong.
|   4. KELAS: Tepat di bawah NISN, center, TANPA label "KELAS :",
|      hanya teks kelas (misal "9B"), bold, ukuran 8pt & warna sama (#0f172a).
|
| Jarak antar baris rapat & merata: line-height 1.2 + margin-top 2px (0,53 mm),
| tanpa padding lain di antaranya. Blok 18 mm dijaga agar posisi QR tidak bergeser.
|   5. QR CODE: Center, di bawah kelas, ukuran optimal & tajam dapat di-scan.
|   6. TEKS "SCAN PRESENSI": Tepat di bawah QR, center, bold, kecil (#0f172a).
|   7. FOOTER: Teks "Tunjukkan kartu saat presensi masuk", center, italic,
|      warna abu-abu kebiruan (#64748b). Tanpa teks tahun ajaran.
|
| Jarak antar baris (Nama > NISN > Kelas) rapat: 0,6 mm (~2,3 px).
|
| Tinggi Kartu Total = 85,6 mm:
|   - Header  : 11,2 mm navy (#1e3a8a)
|   - Accent  : 0,8 mm garis oranje (#f59e0b)
|   - Nama, NISN & Kelas : 18,0 mm putih (#ffffff)
|   - Area QR : 49,0 mm putih (#ffffff) (QR 34 mm + Teks 5 mm + spacing)
|   - Footer  : 6,6 mm abu-abu muda (#f8fafc)
|   Jumlah    : 11.2 + 0.8 + 18.0 + 49.0 + 6.6 = 85,6 mm
| Ketiga baris (nama/nisn/kelas) muat di blok 18 mm dengan vertical-align
| middle, sehingga posisi tengah kartu & QR code tidak bergeser.
|
| Kompatibilitas DomPDF & Web:
|   - Menggunakan <table> & inline styles mm/pt murni.
|   - Border-radius diterapkan pada <td> sudut (header atas, footer bawah).
|--------------------------------------------------------------------------
--}}
@php
    $studentItem = $studentItem ?? null;
    $schoolName  = $schoolName ?? \App\Models\Setting::getSchoolName();

    $studentName  = $studentName  ?? ($studentItem->name ?? $studentItem->nama ?? '-');
    $studentClass = $studentClass ?? ($studentItem->schoolClass->name ?? $studentItem->kelas ?? '-');

    // NISN ditampilkan apa adanya sebagai TEKS 10 digit (angka 0 di depan tetap muncul).
    // Bila kosong, baris NISN disembunyikan supaya tidak menyisakan ruang kosong.
    $studentNisn = trim((string) ($studentItem->nisn ?? ($studentNisn ?? '')));
    if ($studentNisn === '-' || $studentNisn === 'null') {
        $studentNisn = '';
    }

    // UKURAN TETAP — sama untuk semua kartu (tidak ada lagi font mengecil
    // berdasarkan panjang nama). Nama tampil SATU BARIS (nowrap + overflow hidden).
    // Ukuran nama diturunkan dari 8pt menjadi 7pt (tetap untuk SEMUA kartu) supaya
    // nama terpanjang di data siswa tetap muat satu baris tanpa terpotong:
    // "KURNIAWAN RAHMAWATI NOVITASARI" pada 7pt Helvetica-Bold = 134,93 pt
    // vs lebar teks kartu 138,84 pt (53,98mm - 2 x 2,5mm padding).
    // Ukuran NISN (6,8pt) dan kelas (8pt) tetap seperti sebelumnya.
    $nameFontSize  = '7pt';
    $nisnFontSize  = '6.8pt';
    $classFontSize = '8pt';
    $lineGap       = '0.53mm'; // 2px antar baris

    // Resolusi logo fallback jika belum dikirimkan
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

    // Resolusi QR Code fallback jika belum dikirimkan
    $qrSrc = $qrSrc ?? null;
    if (!$qrSrc && $studentItem) {
        $token = $studentItem->nisn ?: $studentItem->qr_token;
        try {
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(140)->margin(0)->generate($token);
            $qrSrc = 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable $e) {
            $qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=" . urlencode($token);
        }
    }
@endphp

@once
<style>
    .presensi-card-table {
        page-break-inside: avoid !important;
        box-sizing: border-box;
    }
    .presensi-card-table td {
        box-sizing: border-box;
    }
</style>
@endonce

<table class="presensi-card-table" style="width: 53.98mm; height: 85.6mm; border-collapse: collapse; table-layout: fixed; background-color: #ffffff; margin: 0 auto; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08); border-radius: 3.18mm; overflow: hidden;">
    <!-- 1. HEADER (11,2 mm navy) -->
    <tr>
        <td style="height: 11.2mm; padding: 0; vertical-align: middle; background-color: #1e3a8a; border-top-left-radius: 3.18mm; border-top-right-radius: 3.18mm;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td style="width: 20%; padding: 1.5mm 0 1.5mm 2.2mm; vertical-align: middle; text-align: left;">
                        <img src="{{ $logoSrc }}" style="width: 8mm; height: 8mm; vertical-align: middle; display: block;" alt="Logo">
                    </td>
                    <td style="width: 80%; padding: 1.5mm 2mm 1.5mm 1.5mm; vertical-align: middle; text-align: left;">
                        <div style="font-size: 6pt; font-weight: bold; text-transform: uppercase; color: #ffffff; line-height: 1.15; word-wrap: break-word; overflow-wrap: break-word; font-family: 'Plus Jakarta Sans', Arial, sans-serif;">
                            {{ strtoupper($schoolName) }}
                        </div>
                        <div style="font-size: 4.2pt; font-weight: bold; color: #fcd34d; text-transform: uppercase; margin-top: 0.5mm; letter-spacing: 0.4px; font-family: 'Plus Jakarta Sans', Arial, sans-serif;">
                            KARTU PRESENSI DIGITAL
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- GARIS ORANYE (0,8 mm di bawah header) -->
    <tr>
        <td style="height: 0.8mm; padding: 0; background-color: #f59e0b; line-height: 0.8mm; font-size: 0;"></td>
    </tr>

    <!-- 2. NAMA SISWA, NISN & KELAS (18,0 mm, center, tanpa label "NAMA"/"NISN"/"KELAS") -->
    <tr>
        <td style="height: 18mm; padding: 1.5mm 2.5mm 1mm 2.5mm; vertical-align: middle; text-align: center; background-color: #ffffff; overflow: hidden;">
            <!-- NAMA SISWA: ukuran TETAP, SATU BARIS, bold, kapital, hitam -->
            <div style="font-size: {{ $nameFontSize }}; font-weight: bold; text-transform: uppercase; color: #0f172a; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: clip; text-align: center; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', Arial, sans-serif;">
                {{ mb_strtoupper($studentName) }}
            </div>
            <!-- NISN: Tepat di bawah nama (jarak 2px), tidak ada margin/padding lain di antaranya -->
            @if($studentNisn !== '')
                <div style="font-size: {{ $nisnFontSize }}; font-weight: bold; color: #0f172a; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: clip; text-align: center; margin: {{ $lineGap }} 0 0 0; padding: 0; letter-spacing: 0.4px; font-family: 'Plus Jakarta Sans', Arial, sans-serif;">
                    {{ $studentNisn }}
                </div>
            @endif
            <!-- KELAS: Tepat di bawah NISN (jarak 2px), bold, ukuran & warna sama dengan nama -->
            <div style="font-size: {{ $classFontSize }}; font-weight: bold; color: #0f172a; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: clip; text-align: center; margin: {{ $lineGap }} 0 0 0; padding: 0; text-transform: uppercase; font-family: 'Plus Jakarta Sans', Arial, sans-serif;">
                {{ $studentClass }}
            </div>
        </td>
    </tr>

    <!-- 4. QR CODE & TEKS "SCAN PRESENSI" (49,0 mm, center) -->
    <tr>
        <td style="height: 49mm; padding: 1mm 0 0 0; vertical-align: top; text-align: center; background-color: #ffffff;">
            <div class="qr-pure-white" style="width: 34mm; height: 34mm; margin: 0 auto; text-align: center; padding: 1mm; background-color: #ffffff;">
                <img src="{{ $qrSrc }}" style="width: 32mm; height: 32mm; display: block; margin: 0 auto;" alt="QR Code">
            </div>
            <!-- TEKS "SCAN PRESENSI": Tepat di bawah QR, center, bold, kecil -->
            <div style="font-size: 5pt; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; text-align: center; margin-top: 1.5mm; font-family: 'Plus Jakarta Sans', Arial, sans-serif;">
                SCAN PRESENSI
            </div>
        </td>
    </tr>

    <!-- 5. FOOTER (6,6 mm, hanya teks "Tunjukkan kartu saat presensi masuk", italic, abu-abu kebiruan) -->
    <tr>
        <td style="height: 6.6mm; background-color: #f8fafc; padding: 0 3mm; vertical-align: middle; text-align: center; border-bottom-left-radius: 3.18mm; border-bottom-right-radius: 3.18mm; border-top: 1px solid #e2e8f0;">
            <div style="font-size: 4.5pt; font-style: italic; color: #64748b; text-align: center; line-height: 1; font-family: 'Plus Jakarta Sans', Arial, sans-serif;">
                Tunjukkan kartu saat presensi masuk
            </div>
        </td>
    </tr>
</table>
