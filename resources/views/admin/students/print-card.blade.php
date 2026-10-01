<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Presensi Siswa - {{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</title>

    <style>
        /* Standar Dimensi Kartu CR80 (53,98 mm x 85,6 mm / KTP) */
        @page {
            size: 53.98mm 85.6mm;
            margin: 0;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-sizing: border-box;
        }

        @if(!empty($isPdf))
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 53.98mm !important;
            height: 85.6mm !important;
            background-color: #ffffff !important;
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            overflow: hidden;
        }
        .presensi-card-table {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            margin: 0 !important;
        }
        @else
        html {
            background-color: #f1f5f9;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f1f5f9;
            color: #0f172a;
        }

        .no-print {
            display: block;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            html, body {
                background-color: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 53.98mm !important;
                height: 85.6mm !important;
            }
            .card-preview-wrapper {
                padding: 0 !important;
                margin: 0 !important;
            }
        }

        /* Toolbar Pratinjau Browser */
        .toolbar-wrap {
            max-width: 500px;
            margin: 0 auto 20px auto;
            padding: 12px 18px;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07);
        }
        .btn-action {
            display: inline-block;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }
        .btn-back {
            background-color: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
            margin-right: 8px;
        }
        .btn-back:hover { background-color: #f1f5f9; }
        .btn-print {
            background-color: #1d4ed8;
            color: #ffffff;
        }
        .btn-print:hover { background-color: #1e40af; }

        .card-preview-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px 0;
        }
        @endif
    </style>
</head>
<body>
@php
    $studentItem = $student ?? $siswa ?? (isset($students) ? $students->first() : null);
    $isPdf = !empty($isPdf) || request()->has('pdf') || isset($isDomPdf);

    $schoolName = $schoolName ?? ($pengaturan->school_name ?? ($setting->school_name ?? \App\Models\Setting::getSchoolName()));
    $activeYear = $activeYear ?? $tahun_ajaran ?? \App\Models\AcademicYear::getActive();

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

    $qrSrc = $studentItem->qr_base64 ?? null;
    if (!$qrSrc && $studentItem) {
        $token = $studentItem->qr_token ?? $studentItem->nis;
        try {
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(140)->margin(0)->generate($token);
            $qrSrc = 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable $e) {
            $qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=" . urlencode($token);
        }
    }

    $studentName = $studentItem->name ?? $studentItem->nama ?? '-';
    $studentClass = $studentItem->schoolClass->name ?? $studentItem->kelas ?? '-';
@endphp

@if(!$isPdf)
    <!-- Toolbar Aksi di Browser -->
    <div class="no-print toolbar-wrap">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: middle;">
                    <div style="font-size: 15px; font-weight: bold; color: #0f172a;">
                        Pratinjau Kartu Siswa
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                        Ukuran Fisik CR80 (54 &times; 85,6 mm / KTP)
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
                        Cetak Kartu
                    </button>
                </td>
            </tr>
        </table>
    </div>

    <div class="card-preview-wrapper">
@endif

    {{-- Single Source of Truth Template Kartu --}}
    @include('admin.students.partials.card', [
        'studentItem' => $studentItem,
        'logoSrc' => $logoSrc,
        'qrSrc' => $qrSrc,
        'schoolName' => $schoolName,
        'studentName' => $studentName,
        'studentClass' => $studentClass,
    ])

@if(!$isPdf)
    </div>
@endif

</body>
</html>
