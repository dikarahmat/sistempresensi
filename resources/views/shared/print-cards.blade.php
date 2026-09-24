<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Presensi Siswa - {{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</title>

    <style>
        /* Pengaturan Kertas A4 & Margins */
        @page {
            size: A4 portrait;
            margin: 6mm 6mm 6mm 6mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            color: #0f172a;
        }

        /* Saat Cetak / Export PDF */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .page-container {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                box-shadow: none !important;
            }
            .page-table {
                page-break-inside: avoid !important;
            }
        }

        .no-print {
            display: block;
        }

        .page-break {
            page-break-after: always !important;
            break-after: page !important;
            height: 0;
            line-height: 0;
        }

        /* Tabel Pengatur Posisi Kartu (Grid 2 Kolom x 4 Baris = 8 Kartu / Lembar) */
        .page-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0 auto;
        }

        .page-cell {
            width: 50%;
            vertical-align: top;
            padding: 2mm 2.5mm;
            box-sizing: border-box;
        }

        /* Kotak Kartu Pelajar / Presensi */
        .card-container {
            width: 100%;
            border: 1px solid #94a3b8;
            border-collapse: collapse;
            table-layout: fixed;
            background-color: #ffffff;
            border-radius: 4px;
            overflow: hidden;
        }

        /* Styling Toolbar Action Bar */
        .toolbar-wrap {
            max-width: 900px;
            margin: 16px auto;
            padding: 12px 18px;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07);
        }
        .btn-action {
            display: inline-block;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
            cursor: pointer;
            border: none;
        }
        .btn-back {
            background-color: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
            margin-right: 8px;
        }
        .btn-back:hover {
            background-color: #f1f5f9;
        }
        .btn-print {
            background-color: #1d4ed8;
            color: #ffffff;
        }
        .btn-print:hover {
            background-color: #1e40af;
        }
    </style>
</head>
<body>

@php
    // Deteksi mode PDF: jika dikirim dari Pdf::loadView atau ada request pdf/post
    $isPdf = $isPdf ?? (request()->has('pdf') || request()->isMethod('post') || isset($isDomPdf));

    // Normalisasi variabel
    if (isset($student) && !isset($students)) {
        $students = collect([$student]);
    } elseif (!isset($students)) {
        $students = collect();
    } elseif (is_array($students)) {
        $students = collect($students);
    }

    $schoolName = $schoolName ?? \App\Models\Setting::getSchoolName();
    $activeYear = $activeYear ?? \App\Models\AcademicYear::getActive();

    // Pastikan Logo Sekolah dalam Base64
    $logoSrc = $logoBase64 ?? null;
    if (!$logoSrc) {
        $logoPath = public_path(\App\Models\Setting::getLogo());
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        } else {
            $logoSrc = asset(\App\Models\Setting::getLogo());
        }
    }
@endphp

@if(!$isPdf)
    <!-- ACTION BAR: Hanya tampil di browser saat preview, tidak masuk saat print atau PDF -->
    <div class="no-print toolbar-wrap">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: middle;">
                    <div style="font-size: 16px; font-weight: bold; color: #0f172a;">
                        Pratinjau Kartu Presensi Siswa
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                        Total: <strong>{{ $students->count() }} Kartu</strong> &bull; Format Standar Kertas A4 (2 Kolom &times; 4 Baris = 8 Kartu / Lembar)
                    </div>
                </td>
                <td style="vertical-align: middle; text-align: right;">
                    <button type="button" 
                            onclick="if(window.history.length > 1) { window.history.back(); } else { window.close(); }" 
                            class="btn-action btn-back">
                        Kembali
                    </button>
                    <button type="button" 
                            onclick="window.print()" 
                            class="btn-action btn-print">
                        &#128438; Cetak / Simpan PDF
                    </button>
                </td>
            </tr>
        </table>
    </div>
@endif

<div class="page-container" style="max-width: 900px; margin: 0 auto;">
    @forelse($students->chunk(8) as $pageIndex => $pageStudents)
        @if($pageIndex > 0)
            <div class="page-break"></div>
        @endif

        <table class="page-table">
            @foreach($pageStudents->chunk(2) as $row)
                <tr>
                    @foreach($row as $item)
                        @php
                            // Resolusi Foto Base64
                            $photoSrc = $item->photo_base64 ?? null;
                            if (!$photoSrc && $item->photo && file_exists(public_path('storage/' . $item->photo))) {
                                $pPath = public_path('storage/' . $item->photo);
                                $pMime = mime_content_type($pPath) ?: 'image/jpeg';
                                $photoSrc = 'data:' . $pMime . ';base64,' . base64_encode(file_get_contents($pPath));
                            }

                            // Resolusi QR Code Base64
                            $qrSrc = $item->qr_base64 ?? null;
                            if (!$qrSrc) {
                                $token = $item->qr_token ?? $item->nis;
                                try {
                                    $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->margin(0)->generate($token);
                                    $qrSrc = 'data:image/svg+xml;base64,' . base64_encode($svg);
                                } catch (\Throwable $e) {
                                    $qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($token);
                                }
                            }

                            $genderVal = ($item->gender ?? $item->jenis_kelamin ?? '') === 'Perempuan' ? 'Perempuan' : 'Laki-laki';
                            $className = $item->schoolClass->name ?? $item->kelas ?? '-';
                        @endphp

                        <td class="page-cell">
                            <table class="card-container">
                                <!-- 1. HEADER KARTU (Navy + Border Emas) -->
                                <tr>
                                    <td colspan="3" style="background-color: #1e3a8a; border-bottom: 2px solid #f59e0b; padding: 4px 6px; color: #ffffff;">
                                        <table style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="width: 26px; vertical-align: middle;">
                                                    <img src="{{ $logoSrc }}" style="width: 24px; height: 24px; vertical-align: middle;" alt="Logo">
                                                </td>
                                                <td style="vertical-align: middle; padding-left: 5px;">
                                                    <div style="font-size: 8pt; font-weight: bold; text-transform: uppercase; color: #ffffff; line-height: 1;">
                                                        {{ $schoolName }}
                                                    </div>
                                                    <div style="font-size: 5pt; font-weight: bold; color: #fcd34d; text-transform: uppercase; margin-top: 2px; letter-spacing: 0.5px;">
                                                        KARTU PRESENSI DIGITAL
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- 2. BODY KARTU (Foto 3x4 | Biodata Siswa | QR Code) -->
                                <tr>
                                    <!-- Foto Siswa (3x4) -->
                                    <td style="width: 23mm; vertical-align: middle; text-align: center; padding: 4px 2px 4px 4px;">
                                        <div style="width: 20mm; height: 25mm; border: 1px dashed #94a3b8; background-color: #f8fafc; margin: 0 auto; text-align: center; overflow: hidden;">
                                            @if($photoSrc)
                                                <img src="{{ $photoSrc }}" style="width: 100%; height: 100%; display: block;" alt="{{ $item->name }}">
                                            @else
                                                <table style="width: 100%; height: 100%; border-collapse: collapse;">
                                                    <tr>
                                                        <td style="vertical-align: middle; text-align: center; font-size: 6pt; color: #64748b; font-weight: bold; line-height: 1.2;">
                                                            FOTO<br>3 x 4
                                                        </td>
                                                    </tr>
                                                </table>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Biodata Siswa -->
                                    <td style="vertical-align: middle; padding: 3px 2px 3px 3px;">
                                        @php
                                            $nameLen = mb_strlen($item->name ?? '');
                                            $nameFontSize = $nameLen > 30 ? '6.5pt' : ($nameLen > 22 ? '7.2pt' : '8pt');
                                        @endphp
                                        <div style="font-size: {{ $nameFontSize }}; font-weight: bold; color: #0f172a; margin-bottom: 2px; line-height: 1.15; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; white-space: normal;">
                                            {{ $item->name }}
                                        </div>
                                        <table style="width: 100%; font-size: 6.5pt; color: #334155; border-collapse: collapse; line-height: 1.25;">
                                            <tr>
                                                <td style="width: 26px; font-weight: bold; color: #64748b; padding: 1px 0;">NIS</td>
                                                <td style="width: 6px; padding: 1px 0;">:</td>
                                                <td style="font-weight: bold; color: #0f172a; padding: 1px 0;">{{ $item->nis }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: bold; color: #64748b; padding: 1px 0;">NISN</td>
                                                <td style="padding: 1px 0;">:</td>
                                                <td style="padding: 1px 0;">{{ $item->nisn ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: bold; color: #64748b; padding: 1px 0;">Kelas</td>
                                                <td style="padding: 1px 0;">:</td>
                                                <td style="font-weight: bold; color: #1d4ed8; padding: 1px 0;">{{ $className }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: bold; color: #64748b; padding: 1px 0;">JK</td>
                                                <td style="padding: 1px 0;">:</td>
                                                <td style="padding: 1px 0;">{{ $genderVal }}</td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- QR Code Presensi -->
                                    <td style="width: 23mm; vertical-align: middle; text-align: center; padding: 4px 4px 4px 2px;">
                                        <div style="width: 20mm; height: 20mm; border: 1px solid #cbd5e1; background-color: #ffffff; margin: 0 auto; padding: 1px; text-align: center;">
                                            <img src="{{ $qrSrc }}" style="width: 100%; height: 100%; vertical-align: middle;" alt="QR">
                                        </div>
                                        <div style="font-size: 5pt; font-weight: bold; color: #0f172a; text-transform: uppercase; margin-top: 2px; letter-spacing: 0.3px;">
                                            SCAN PRESENSI
                                        </div>
                                    </td>
                                </tr>

                                <!-- 3. FOOTER KARTU -->
                                <tr>
                                    <td colspan="3" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 2px 6px; font-size: 5.5pt;">
                                        <table style="width: 100%; border-collapse: collapse;">
                                            <tr>
                                                <td style="color: #64748b; font-style: italic;">
                                                    Tunjukkan kartu saat presensi masuk
                                                </td>
                                                <td style="color: #1d4ed8; font-weight: bold; text-align: right; text-transform: uppercase;">
                                                    TA {{ $activeYear->name ?? date('Y') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    @endforeach

                    @if($row->count() == 1)
                        <td class="page-cell"></td>
                    @endif
                </tr>
            @endforeach
        </table>
    @empty
        <div style="padding: 50px 20px; text-align: center; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 20px;">
            <div style="font-size: 16px; font-weight: bold; color: #334155;">Tidak ada data siswa yang ditemukan</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Pastikan data siswa telah diinput dan aktif pada kelas yang dipilih.</div>
        </div>
    @endforelse
</div>

</body>
</html>
