{{--
|--------------------------------------------------------------------------
| KARTU PRESENSI DIGITAL — SATU SUMBER DESAIN (PARTIAL)
|--------------------------------------------------------------------------
| Ukuran kartu : KTP/CR80 (53,98 mm x 85,6 mm) dengan sudut lengkung 3,18 mm
| Dipakai oleh : admin.students.print-card (cetak satuan + preview browser)
|                shared.print-cards (cetak massal 10 kartu / A4 landscape)
|                (+ semua controller yang memanggil kedua view di atas)
|
| Variabel kiriman: $studentItem, $logoSrc, $qrSrc, $schoolName, $activeYear,
|                   $studentName, $studentNis, $studentNisn, $studentClass,
|                   $studentGender
|
| Tinggi kartu (wajib jumlah = 85,6 mm):
|   HEADER  12,0 mm = 11,2 mm konten navy + 0,8 mm garis kuning (#f59e0b)
|   BIODATA 17,0 mm = NAMA + KELAS saja (NIS tidak ditampilkan)
|   QR      48,0 mm = blok QR 36 mm + label SCAN PRESENSI
|   FOOTER   8,6 mm
|
| Aturan DomPDF: hanya <table>/<div> + inline style; tanpa flexbox/grid/
|                absolute/fixed/CSS variable/calc()/transform; ukuran mm/pt.
|                DomPDF selalu memakai content-box, sehingga tinggi + padding
|                tidak boleh berada pada elemen yang sama (pakai elemen dalam).
|                Border-radius diterapkan per bagian (header/footer) karena
|                DomPDF tidak meng-clip anak dengan overflow:hidden.
|--------------------------------------------------------------------------
--}}
@php
    // Fallback aman bila pemanggil belum mengirim seluruh variabel.
    $studentItem = $studentItem ?? null;
    $schoolName  = $schoolName ?? '';
    $activeYear  = $activeYear ?? null;

    $studentName   = $studentName   ?? ($studentItem->name ?? $studentItem->nama ?? '-');
    $studentNis    = $studentNis    ?? ($studentItem->nis ?? '-');
    $studentNisn   = $studentNisn   ?? ($studentItem->nisn ?? '-');
    $studentClass  = $studentClass  ?? ($studentItem->schoolClass->name ?? $studentItem->kelas ?? '-');
    $studentGender = $studentGender ?? ((($studentItem->gender ?? $studentItem->jenis_kelamin ?? '') === 'Perempuan') ? 'Perempuan' : 'Laki-laki');

    // Ukuran font nama adaptif supaya nama panjang tetap muat (maks. 2 baris).
    $nameLen      = mb_strlen((string) $studentName);
    $nameFontSize = $nameLen > 28 ? '5pt' : ($nameLen > 22 ? '5.5pt' : '6pt');

    // Nama sekolah tetap di rentang 6,5-7 pt dan maksimal 2 baris.
    $schoolLen      = mb_strlen((string) $schoolName);
    $schoolFontSize = $schoolLen > 26 ? '6.5pt' : '7pt';
@endphp
@once
    <style>
        /* Style kartu KTP — hanya properti yang didukung DomPDF. */
        .pcard { page-break-inside: avoid !important; }
        .pcard-row { page-break-inside: avoid !important; }
    </style>
@endonce
<table class="pcard" style="width: 53.98mm; height: 85.6mm; border-collapse: collapse; table-layout: fixed; background-color: #ffffff; margin: 0 auto;">
    <!-- 1. HEADER (11,2 mm navy + garis kuning 0,8 mm = 12 mm) -->
    <tr>
        <td style="height: 11.2mm; padding: 0; vertical-align: middle; background-color: #1e3a8a; border-top-left-radius: 3.18mm; border-top-right-radius: 3.18mm;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td style="width: 20.4%; padding: 1.5mm 0 1.5mm 2mm; vertical-align: middle; text-align: left;">
                        <img src="{{ $logoSrc }}" style="width: 8mm; height: 8mm; vertical-align: middle;" alt="Logo">
                    </td>
                    <td style="padding: 1.5mm 0 1.5mm 1.5mm; vertical-align: middle; text-align: left;">
                        <div style="font-size: 6pt; font-weight: bold; text-transform: uppercase; color: #ffffff; line-height: 1.1; word-wrap: break-word; overflow-wrap: break-word;">
                            {{ strtoupper($schoolName) }}
                        </div>
                        <div style="font-size: 4.2pt; font-weight: bold; color: #fcd34d; text-transform: uppercase; margin-top: 0.5mm; letter-spacing: 0.3px;">
                            KARTU PRESENSI DIGITAL
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <!-- Garis kuning 0,8 mm, nempel di dasar header (tanpa ruang kosong) -->
    <tr>
        <td style="height: 0.8mm; padding: 0; background-color: #f59e0b;"></td>
    </tr>
    <!-- 2. BIODATA (17 mm, hanya NAMA & KELAS) -->
    <tr>
        <td style="height: 17mm; padding: 0; vertical-align: top; text-align: center; background-color: #ffffff;">
            <div style="padding-top: 2mm;">
                <table style="width: 46mm; margin: 0 auto; border-collapse: collapse; table-layout: fixed; font-size: 6pt; line-height: 1.25;">
                    <tr>
                        <td style="width: 23.9%; font-weight: bold; color: #64748b; padding: 0; vertical-align: top; text-align: left;">NAMA</td>
                        <td style="width: 6.5%; font-weight: bold; color: #64748b; padding: 0; vertical-align: top; text-align: left;">:</td>
                        <td style="width: 69.6%; font-weight: bold; color: #0f172a; font-size: {{ $nameFontSize }}; padding: 0; vertical-align: top; text-align: left; line-height: 1.15; word-wrap: break-word; overflow-wrap: break-word;">{{ mb_strtoupper($studentName) }}</td>
                    </tr>
                    <tr>
                        <td style="width: 23.9%; font-weight: bold; color: #64748b; padding: 0; vertical-align: top; text-align: left;">KELAS</td>
                        <td style="width: 6.5%; font-weight: bold; color: #64748b; padding: 0; vertical-align: top; text-align: left;">:</td>
                        <td style="width: 69.6%; font-weight: bold; color: #1d4ed8; font-size: 6.5pt; padding: 0; vertical-align: top; text-align: left;">{{ $studentClass }}</td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
    <!-- 3. AREA QR (48 mm, wajib center) -->
    <tr>
        <td style="height: 48mm; padding: 0; vertical-align: top; text-align: center; background-color: #ffffff;">
            <div style="padding-top: 2mm;">
                <table style="width: 36mm; border-collapse: collapse; table-layout: fixed; background-color: #ffffff; margin: 0 auto;">
                    <tr>
                        <td style="width: 100%; padding: 1.5mm; text-align: center; vertical-align: middle; background-color: #ffffff;">
                            <img src="{{ $qrSrc }}" style="width: 33mm; height: 33mm; margin: 0 auto;" alt="QR">
                        </td>
                    </tr>
                </table>
                <div style="font-size: 5pt; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; text-align: center; margin-top: 1.5mm;">
                    SCAN PRESENSI
                </div>
            </div>
        </td>
    </tr>
    <!-- 4. FOOTER (8.6 mm) -->
    <tr>
        <td style="height: 8.6mm; background-color: #f8fafc; padding: 0 3mm; vertical-align: middle; border-bottom-left-radius: 3.18mm; border-bottom-right-radius: 3.18mm;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td style="width: 68%; font-size: 4pt; font-style: italic; color: #64748b; text-align: left; vertical-align: middle; padding: 0;">
                        Tunjukkan kartu saat presensi masuk
                    </td>
                    <td style="width: 32%; font-size: 4.5pt; font-weight: bold; color: #1d4ed8; text-align: right; vertical-align: middle; padding: 0; text-transform: uppercase;">
                        TA {{ $activeYear->name ?? date('Y') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
