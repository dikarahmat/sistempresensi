<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Presensi Siswa - {{ $schoolName ?? \App\Models\Setting::getSchoolName() }}</title>

    <style>
        /* Pengaturan Kertas A4 LANDSCAPE — margin 0 supaya background abu-abu
           bisa tercetak penuh sampai tepi kertas. Jarak aman 6 mm per sisi
           diberikan lewat body padding + .page-sheet (lihat di bawah). */
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        html {
            background-color: #e5e7eb;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0 6mm 6mm 6mm;
            background-color: #e5e7eb;
            color: #0f172a;
        }

        /* Saat Cetak / Export PDF */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #e5e7eb !important;
                margin: 0 !important;
                padding: 0 6mm 6mm 6mm !important;
            }
            .page-container {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
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

        /* Pindah halaman setelah 10 kartu (1 lembar A4 landscape) */
        .page-break {
            page-break-after: always !important;
            break-after: page !important;
            height: 0;
            line-height: 0;
        }

        /* Area cetak */
        .page-container {
            width: 100%;
            margin: 0 auto;
        }

        /* Pembungkus per lembar: memberi jarak 6 mm di atas kartu pada
           halaman pertama maupun halaman berikutnya. */
        .page-sheet {
            padding-top: 6mm;
        }

        /* Grid kartu: 5 kolom x 2 baris = 10 kartu / lembar (kartu KTP)
           5 x 53,98 mm + 4 x 3 mm = 281,9 mm, muat penuh di area cetak 285 mm */
        .page-table {
            width: 285mm;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0 auto;
        }

        .page-cell {
            width: 20%;
            padding: 1.5mm 0;
            vertical-align: middle;
            text-align: center;
        }

        /* Baris kartu tidak boleh terpotong pindah halaman */
        .card-row {
            page-break-inside: avoid !important;
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
                        Total: <strong>{{ $students->count() }} Kartu</strong> &bull; Kartu KTP (53,98 x 85,6 mm) &mdash; Kertas A4 Landscape (5 Kolom &times; 2 Baris = 10 Kartu / Lembar)
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
<div class="page-container">
    @forelse($students->chunk(10) as $pageIndex => $pageStudents)
        @if($pageIndex > 0)
            <div class="page-break"></div>
        @endif

        <div class="page-sheet">
        <table class="page-table">
            @foreach($pageStudents->chunk(5) as $row)
                <tr class="card-row">
                    @foreach($row as $item)
                        @php
                            // Resolusi Foto Base64 (logika lama dipertahankan)
                            $photoSrc = $item->photo_base64 ?? null;
                            if (!$photoSrc && $item->photo && file_exists(public_path('storage/' . $item->photo))) {
                                $pPath = public_path('storage/' . $item->photo);
                                $pMime = mime_content_type($pPath) ?: 'image/jpeg';
                                $photoSrc = 'data:' . $pMime . ';base64,' . base64_encode(file_get_contents($pPath));
                            }

                            // Resolusi QR Code Base64 (token/isi sama seperti sebelumnya)
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
                            @include('admin.students.partials.card', [
                                'studentItem' => $item,
                                'logoSrc' => $logoSrc,
                                'qrSrc' => $qrSrc,
                                'schoolName' => $schoolName,
                                'activeYear' => $activeYear,
                                'studentName' => $item->name ?? $item->nama ?? '-',
                                'studentNis' => $item->nis ?? '-',
                                'studentNisn' => $item->nisn ?? '-',
                                'studentClass' => $className,
                                'studentGender' => $genderVal,
                            ])
                        </td>
                    @endforeach

                    {{-- Sisa cell pada baris terakhir dibiarkan kosong (tidak direntangkan) --}}
                    @for($slot = $row->count(); $slot < 5; $slot++)
                        <td class="page-cell"></td>
                    @endfor
                </tr>
            @endforeach
        </table>
        </div>
    @empty
        <div style="padding: 50px 20px; text-align: center; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 20px;">
            <div style="font-size: 16px; font-weight: bold; color: #334155;">Tidak ada data siswa yang ditemukan</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Pastikan data siswa telah diinput dan aktif pada kelas yang dipilih.</div>
        </div>
    @endforelse
</div>

</body>
</html>
