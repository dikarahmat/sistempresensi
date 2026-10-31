<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Exports\MultiPeriodAttendanceExport;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class RekapController extends Controller
{
    /**
     * Dapatkan data rekapitulasi berdasarkan tipe periode (Harian, Mingguan, Bulanan).
     */
    protected function getRecapData(string $type, ?int $classId, array $inputs, bool $paginateStudents = false): array
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $type = in_array($type, ['harian', 'mingguan', 'bulanan', 'semester', 'tahunan']) ? $type : 'bulanan';
        $activeYear = AcademicYear::getActive();

        $query = Student::with('schoolClass');
        if ($classId) {
            $query->where('school_class_id', $classId);
        }
        $studentPaginator = $paginateStudents
            ? $query->orderBy('name', 'asc')->paginate(100)->withQueryString()
            : null;
        $students = $studentPaginator
            ? $studentPaginator->getCollection()
            : $query->orderBy('name', 'asc')->get();


        $totalHadir = 0;
        $totalTerlambat = 0;
        $totalSakit = 0;
        $totalIzin = 0;
        $totalAlfa = 0;
        $dataRows = [];

        $lateLimitTime = Setting::getLateLimitTime();

        // Determine date range based on period type
        $dateRange = $this->resolveDateRange($type, $inputs, $now, $activeYear);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        $periodLabel = $dateRange['label'];

        if ($type === 'harian') {
            $date = $inputs['date'] ?? $now->toDateString();
            $dateObj = Carbon::parse($date);
            $isWeekend = $dateObj->isWeekend();
            $isHoliday = Holiday::isHoliday($date) || $isWeekend;
            $holidayDesc = Holiday::getHolidayDescription($date) ?? ($isWeekend ? 'Akhir Pekan' : null);

            $attendances = Attendance::where('date', $date)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');

            foreach ($students as $student) {
                $att = $attendances->get($student->id);
                $row = Attendance::dataRowForRekap($att, $lateLimitTime);

                if ($att) {
                    $status = $row['status'];
                    if ($status === self::STATUS_TERLAMBAT) {
                        $totalTerlambat++;
                        $totalHadir++;
                    } elseif ($status === self::STATUS_HADIR) {
                        $totalHadir++;
                    } elseif ($status === self::STATUS_SAKIT) {
                        $totalSakit++;
                    } elseif ($status === self::STATUS_IZIN) {
                        $totalIzin++;
                    } elseif ($status === self::STATUS_ALFA) {
                        $totalAlfa++;
                    }
                } else {
                    /*
                     * Tanpa catatan presensi:
                     * - tanggal lampau (bukan hari libur/akhir pekan) -> ALFA
                     *   (hitungan tampilan, tidak ditulis ke database);
                     * - hari ini / masa depan -> BELUM;
                     * - hari libur / akhir pekan -> LIBUR.
                     * Sama dengan halaman Presensi (Attendance::statusOf).
                     */
                    $status = Attendance::statusOf(null, $date, $isHoliday);
                }

                $dataRows[] = [
                    'student' => $student,
                    'check_in' => $row['check_in'],
                    'late_text' => $row['late_text'],
                    'late_minutes' => $row['late_minutes'],
                    'status' => $status,
                    'notes' => $row['notes'],
                    'proof_document' => $row['proof_document'],
                ];
            }

            return compact(
                'type', 'date', 'dateObj', 'isHoliday', 'holidayDesc',
                'students', 'dataRows', 'totalHadir', 'totalTerlambat',
                'totalSakit', 'totalIzin', 'totalAlfa', 'studentPaginator'
            );
        }

        // For mingguan, bulanan, semester, tahunan - use date range
        $dateColumns = [];
        $cur = Carbon::parse($startDate)->copy();
        $endObj = Carbon::parse($endDate);
        while ($cur->lte($endObj)) {
            $curDateStr = $cur->toDateString();
            $isWeekend = $cur->isWeekend();
            $isHol = Holiday::isHoliday($curDateStr);
            $dateColumns[] = [
                'date' => $curDateStr,
                'carbon' => $cur->copy(),
                'label' => $cur->translatedFormat('D, d/m'),
                'is_holiday' => $isWeekend || $isHol,
            ];
            $cur->addDay();
        }

        $totalEffective = count(array_filter($dateColumns, fn($c) => !$c['is_holiday']));
        $isMonthTable = in_array($type, ['semester', 'tahunan'], true);

        if ($isMonthTable) {
            // Semester & tahunan memakai kolom BULAN, bukan kolom tanggal 1-31.
            // Agregat per siswa per bulan diambil dari SATU query terkelompok
            // (GROUP BY siswa, bulan, status) - bukan loop per hari.
            $monthlyCounts = MultiPeriodAttendanceExport::monthlyStatusCounts(
                $startDate,
                $endDate,
                $students->pluck('id')
            );
            $months = MultiPeriodAttendanceExport::monthColumnsBetween($startDate, $endDate);

            foreach ($students as $student) {
                $perMonth = $monthlyCounts[$student->id] ?? [];
                $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;
                $monthly = [];

                foreach ($months as $monthInfo) {
                    $data = $perMonth[$monthInfo['key']] ?? null;
                    if ($data === null) {
                        // Bulan tanpa data presensi ditulis "-" di sel.
                        $monthly[$monthInfo['key']] = null;
                        continue;
                    }

                    $h += $data['hadir'] + $data['terlambat'];
                    $t += $data['terlambat'];
                    $s += $data['sakit'];
                    $i += $data['izin'];
                    $a += $data['alfa'];
                    // Sel bulan = jumlah hari Hadir (H + T) pada bulan itu.
                    $monthly[$monthInfo['key']] = $data['hadir'] + $data['terlambat'];
                }

                $percentage = $totalEffective > 0 ? round(($h / $totalEffective) * 100) : 0;

                $totalHadir += $h;
                $totalTerlambat += $t;
                $totalSakit += $s;
                $totalIzin += $i;
                $totalAlfa += $a;

                $dataRows[] = [
                    'student' => $student,
                    'days' => [],
                    'monthly' => $monthly,
                    'hadir' => $h,
                    'terlambat' => $t,
                    'sakit' => $s,
                    'izin' => $i,
                    'alfa' => $a,
                    'percentage' => $percentage,
                ];
            }

            $monthExtra = [
                'type' => $type,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'periodLabel' => $periodLabel,
                'dateColumns' => $dateColumns,
                'students' => $students,
                'dataRows' => $dataRows,
                'totalHadir' => $totalHadir,
                'totalTerlambat' => $totalTerlambat,
                'totalSakit' => $totalSakit,
                'totalIzin' => $totalIzin,
                'totalAlfa' => $totalAlfa,
                'studentPaginator' => $studentPaginator,
                'months' => $months,
                'period_academic_year' => $inputs['period_academic_year'] ?? ($activeYear?->id ?? null),
            ];
            if ($type === 'semester') {
                $monthExtra['period_semester'] = $inputs['period_semester'] ?? 'ganjil';
            }

            return $monthExtra;
        }

        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');

        foreach ($students as $student) {
            // BUG FIX: keyBy('date') creates keys like '2026-10-06 00:00:00'
            // but lookup uses '2026-10-06' ÔÇö they never match!
            // Fix: use toDateString() for consistent date-only keys
            $stAtts = $attendances->get($student->id, collect())->keyBy(fn($a) => $a->date->toDateString());
            $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;
            $days = [];

            foreach ($dateColumns as $col) {
                $dStr = $col['date'];
                $att = $stAtts->get($dStr);
                $code = '-';

                if ($att) {
                    $status = $att->effective_status;
                    if ($status === 'Hadir') {
                        $code = 'H';
                        $h++;
                    } elseif ($status === 'Terlambat') {
                        $code = 'T';
                        $t++;
                        $h++;
                    } elseif ($status === 'Sakit') {
                        $code = 'S';
                        $s++;
                    } elseif ($status === 'Izin') {
                        $code = 'I';
                        $i++;
                    } elseif ($status === 'Alfa') {
                        $code = 'A';
                        $a++;
                    }
                } else {
                    if ($col['is_holiday']) {
                        $code = 'L';
                    } else {
                        $code = '-';
                    }
                }

                $days[$dStr] = $code;
            }

            $totalEffective = count(array_filter($dateColumns, fn($c) => !$c['is_holiday']));
            $percentage = $totalEffective > 0 ? round(($h / $totalEffective) * 100) : 0;

            $totalHadir += $h;
            $totalTerlambat += $t;
            $totalSakit += $s;
            $totalIzin += $i;
            $totalAlfa += $a;

            $dataRows[] = [
                'student' => $student,
                'days' => $days,
                'hadir' => $h,
                'terlambat' => $t,
                'sakit' => $s,
                'izin' => $i,
                'alfa' => $a,
                'percentage' => $percentage,
            ];
        }

        // Add period-specific data to return
        $extra = [
            'type' => $type,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodLabel' => $periodLabel,
            'dateColumns' => $dateColumns,
            'students' => $students,
            'dataRows' => $dataRows,
            'totalHadir' => $totalHadir,
            'totalTerlambat' => $totalTerlambat,
            'totalSakit' => $totalSakit,
            'totalIzin' => $totalIzin,
            'totalAlfa' => $totalAlfa,
            'studentPaginator' => $studentPaginator,
        ];

        // Add monthly-specific data for bulanan
        if ($type === 'bulanan') {
            $month = (int) ($inputs['month'] ?? $now->month);
            $year = (int) ($inputs['year'] ?? $now->year);
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            $holidayMap = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $cDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $isWeekend = Carbon::createFromDate($year, $month, $d)->isWeekend();
                $isHol = Holiday::isHoliday($cDate);
                if ($isWeekend || $isHol) {
                    $holidayMap[$d] = Holiday::getHolidayDescription($cDate) ?? 'Akhir Pekan';
                }
            }
            $extra['month'] = $month;
            $extra['year'] = $year;
            $extra['daysInMonth'] = $daysInMonth;
            $extra['holidayMap'] = $holidayMap;
        }

        return $extra;
    }

    /**
     * Resolve date range based on period type.
     */
    protected function resolveDateRange(string $type, array $inputs, Carbon $now, ?AcademicYear $activeYear): array
    {
        switch ($type) {
            case 'harian':
                // Harian: tepat satu tanggal yang dipilih.
                $date = $inputs['date'] ?? $now->toDateString();
                $date = Carbon::parse($date)->toDateString();
                return [
                    'start' => $date,
                    'end' => $date,
                    'label' => Carbon::parse($date)->translatedFormat('l, d F Y'),
                ];

            case 'mingguan':
                // Mingguan: Senin s/d Minggu dari SATU tanggal acuan (konsisten
                // dengan halaman Riwayat Presensi per Siswa).
                // Fallback ke start_date/end_date eksplisit HANYA untuk jalur
                // filter tabel halaman Rekap yang memang mengirim rentang
                // kustom; jalur cetak popup tidak pernah mengirim keduanya,
                // sehingga pilihan popup (tanggal acuan) yang selalu menang.
                if (!empty($inputs['start_date']) || !empty($inputs['end_date'])) {
                    $startDate = Carbon::parse($inputs['start_date'] ?? $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString())->toDateString();
                    $endDate = Carbon::parse($inputs['end_date'] ?? $now->copy()->startOfWeek(Carbon::MONDAY)->addDays(6)->toDateString())->toDateString();
                } else {
                    $anchor = Carbon::parse($inputs['date'] ?? $now->toDateString());
                    $startDate = $anchor->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                    $endDate = $anchor->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
                }
                if ($endDate < $startDate) {
                    [$startDate, $endDate] = [$endDate, $startDate];
                }
                return [
                    'start' => $startDate,
                    'end' => $endDate,
                    'label' => Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y'),
                ];

            case 'bulanan':
                // Bulanan: tanggal 1 sampai akhir bulan dari bulan + tahun pilihan.
                $month = (int) ($inputs['month'] ?? $now->month);
                $year = (int) ($inputs['year'] ?? $now->year);
                if ($month < 1 || $month > 12) {
                    $month = (int) $now->month;
                }
                $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
                $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
                return [
                    'start' => $startDate,
                    'end' => $endDate,
                    'label' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
                ];

            case 'semester':
                // Semester Ganjil = Juli-Desember, Genap = Januari-Juni.
                // Definisi rentang ada di satu tempat (MultiPeriodAttendanceExport)
                // agar PDF, halaman rekap, dan Excel tidak pernah berbeda.
                $semester = ($inputs['period_semester'] ?? 'ganjil') === 'genap' ? 'genap' : 'ganjil';
                $ay = AcademicYear::find($inputs['period_academic_year'] ?? ($activeYear?->id ?? 0)) ?: $activeYear;
                $range = MultiPeriodAttendanceExport::academicBounds('semester', $inputs, $activeYear);
                $startDate = $range['start'];
                $endDate = $range['end'];

                return [
                    'start' => $startDate,
                    'end' => $endDate,
                    'label' => 'Semester ' . ucfirst($semester) . ' ' . ($ay->name ?? Carbon::parse($startDate)->format('Y')),
                ];

            case 'tahunan':
                // Tahunan: satu tahun ajaran penuh (Juli-Juni).
                $ay = AcademicYear::find($inputs['period_academic_year'] ?? ($activeYear?->id ?? 0)) ?: $activeYear;
                $range = MultiPeriodAttendanceExport::academicBounds('tahunan', $inputs, $activeYear);
                $startDate = $range['start'];
                $endDate = $range['end'];

                return [
                    'start' => $startDate,
                    'end' => $endDate,
                    'label' => 'Tahun Ajaran ' . ($ay->name ?? Carbon::parse($startDate)->format('Y')),
                ];

            default:
                return [
                    'start' => $now->startOfMonth()->toDateString(),
                    'end' => $now->endOfMonth()->toDateString(),
                    'label' => $now->translatedFormat('F Y'),
                ];
        }
    }

    /**
     * Nama file unduhan Cetak Per Siswa.
     * ZIP sudah dihapus: satu request = satu file. Scope "class" menghasilkan
     * satu file berisi seluruh siswa kelas itu, scope "single" satu file untuk
     * satu siswa. Nama selalu lewat Str::slug agar bebas spasi dan garis miring.
     */
    protected function studentProfileFileName(array $studentsData, string $periodType, ?string $studentScope, string $extension): string
    {
        $first = $studentsData[0]['student'] ?? null;

        if ($studentScope === 'class' && $first?->schoolClass?->name) {
            $scope = Str::slug($first->schoolClass->name);
        } elseif ($studentScope === 'single' && $first) {
            $scope = Str::slug($first->nisn ?: $first->name);
        } else {
            $scope = Str::slug(Setting::getSchoolName() ?: 'sekolah');
        }

        return "Profil_Presensi_{$periodType}_{$scope}.{$extension}";
    }

    protected function paginateRecapRows(array $recap): array
    {
        $paginator = $recap['studentPaginator'] ?? null;
        if ($paginator instanceof LengthAwarePaginator) {
            // PENTING: dataRows dari getRecapData() berisi baris rekap berbentuk array
            // (['student' => Student, 'days' => [...], 'status' => ...]) yang urutannya
            // identik dengan koleksi siswa pada halaman paginator ini.
            //
            // JANGAN menimpa dataRows dengan $paginator (koleksi model Student):
            // view rekap (admin & guru) membaca $row['student'], sehingga bila item
            // berupa model Student maka $row['student'] selalu null dan halaman
            // rekap error 500 ("Attempt to read property 'nisn' on null").
            $paginator->setCollection(collect($recap['dataRows'] ?? [])->values());
            $recap['dataRows'] = $paginator;
        }

        unset($recap['studentPaginator']);

        return $recap;
    }

    /**
     * Halaman Rekapitulasi Presensi Admin.
     */
    public function index(Request $request): View
    {
        $type = $request->input('type', 'bulanan');
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $classes = SchoolClass::orderBy('name')->get();
        $activeYear = AcademicYear::getActive();

        // Pagination 50 siswa per halaman di desktop dan mobile (disamakan dengan Data Siswa)
        $recap = $this->getRecapData($type, $classId, $request->all(), true);
        $recap = $this->paginateRecapRows($recap);

        return view('admin.attendances.rekap', array_merge($recap, [
            'type' => $type,
            'classId' => $classId,
            'classes' => $classes,
            'activeYear' => $activeYear,

            // Data popup cetak. Nilai AWAL mengikuti filter/tab halaman, TAPI
            // popup hanya mengirim field periodenya sendiri (tidak mewarisi
            // rentang $startDate/$endDate halaman) sehingga rentang final
            // dihitung resolveDateRange() dari pilihan popup.
            'popupDate' => $request->input('date') ?: Carbon::now('Asia/Jakarta')->toDateString(),
            'popupMonth' => (int) ($request->input('month') ?: Carbon::now('Asia/Jakarta')->month),
            'popupYear' => (int) ($request->input('year') ?: Carbon::now('Asia/Jakarta')->year),
            'academicYears' => AcademicYear::orderBy('start_date', 'desc')->get(),
            'siswaPerKelas' => Student::where('status', 'Aktif')
                ->selectRaw('school_class_id, COUNT(*) as jml')
                ->groupBy('school_class_id')
                ->pluck('jml', 'school_class_id')
                ->all(),
            'siswaTotal' => Student::where('status', 'Aktif')->count(),

            // Daftar kelas untuk opsi "Semua Kelas" di popup: satu file per kelas.
            'kelasCetak' => $classes->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'jml' => (int) (Student::where('status', 'Aktif')->where('school_class_id', $c->id)->count()),
            ])->values(),
        ]));
    }

    /**
     * Ekspor Excel Admin Multi-Periode.
     */
    public function exportExcel(Request $request)
    {
        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '180');

            $type = $request->input('type', 'bulanan');
            $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
            $selectedClass = $classId ? SchoolClass::find($classId) : null;
            $className = $selectedClass ? Str::slug($selectedClass->name) : 'semua_kelas';

            $fileName = "Rekap_Presensi_{$type}_{$className}_" . date('Ymd_His') . ".xlsx";

            return \App\Services\DownloadCacheService::downloadRekapFile('xlsx', $type, $classId, $request->all(), function() use ($type, $classId, $request, $fileName) {
                return Excel::download(new MultiPeriodAttendanceExport($type, array_merge($request->all(), ['class_id' => $classId])), $fileName);
            }, $fileName);
        } catch (\Throwable $e) {
            Log::error('Gagal export Excel: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', __('Gagal membuat file export. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Ekspor PDF Admin Multi-Periode.
     */
    public function exportPdf(Request $request)
    {
        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '180');

            Carbon::setLocale('id');

            $type = $request->input('type', 'bulanan');
            $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
            $selectedClass = $classId ? SchoolClass::with('teacher')->find($classId) : null;
            $className = $selectedClass ? $selectedClass->name : 'Semua Kelas';

            $fileName = "Rekap_Presensi_{$type}_" . Str::slug($className) . ".pdf";

            return \App\Services\DownloadCacheService::downloadRekapFile('pdf', $type, $classId, $request->all(), function() use ($type, $classId, $request, $className) {
                $schoolName = Setting::getSchoolName();
                $schoolAddress = Setting::getSchoolAddress();
                $activeYear = AcademicYear::getActive();

                $recap = $this->getRecapData($type, $classId, $request->all());

                // Section per kelas: urutan kelas dari data (tingkat -> rombel),
                // nomor urut lanjut, wali kelas diambil dari Data Guru,
                // judul & sub-judul memakai helper yang SAMA dengan ekspor Excel.
                $sections = MultiPeriodAttendanceExport::buildSections(
                    $recap['dataRows'],
                    $type,
                    $request->all(),
                    $classId
                );

                $logoBase64 = optional(Setting::getLogo())->when(function ($relative) {
                    $path = public_path($relative);
                    $raw = file_exists($path) ? @file_get_contents($path) : null;
                    if (!$raw) {
                        return null;
                    }
                    if (function_exists('imagecreatefromstring')) {
                        try {
                            $src = @imagecreatefromstring($raw);
                        } catch (\Throwable $e) {
                            $src = false;
                        }
                        if ($src) {
                            try {
                                $w = imagesx($src);
                                $h = imagesy($src);
                                $max = 120;
                                $scale = min($max / max($w, 1), $max / max($h, 1), 1.0);
                                $nw = max(1, (int) round($w * $scale));
                                $nh = max(1, (int) round($h * $scale));

                                $dst = imagecreatetruecolor($nw, $nh);
                                imagealphablending($dst, false);
                                imagesavealpha($dst, true);
                                $transparent = imagecolorallocatealpha($dst, 127, 127, 127, 127);
                                imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
                                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                                imagedestroy($src);

                                ob_start();
                                imagepng($dst, null, 6);
                                $png = ob_get_clean();
                                imagedestroy($dst);

                                if ($png) {
                                    return 'data:image/png;base64,' . base64_encode($png);
                                }
                            } catch (\Throwable $e) {
                                // logo tidak wajib; cetak tetap jalan
                            }
                        }
                    }

                    return 'data:' . ($mime = @mime_content_type($path) ?: 'image/png') . ';base64,' . base64_encode($raw);
                });

            $pdf = Pdf::loadView('admin.attendances.pdf_multi_rekap', array_merge($recap, [
                    'title' => "Laporan Presensi {$type} - {$className}",
                    'schoolName' => $schoolName,
                    'schoolAddress' => $schoolAddress,
                    'logoBase64' => $logoBase64,
                    'activeYear' => $activeYear,
                    'headmasterName' => Setting::getHeadmasterName(),
                    'headmasterNip' => Setting::getHeadmasterNip(),
                    'sections' => $sections,
                ]))->setPaper('a4', 'landscape');

                return $pdf->output();
            }, $fileName);
        } catch (\Throwable $e) {
            Log::error('Gagal export PDF: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', __('Gagal membuat file export. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Cari siswa untuk autocomplete di modal cetak rekap (AJAX).
     */
    public function studentSearch(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = trim($request->input('q', ''));
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $students = Student::with('schoolClass')
            ->where('status', 'Aktif')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('nisn', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'nisn', 'school_class_id'])
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'nisn' => $s->nisn,
                    'class_name' => $s->schoolClass?->name ?? '-',
                ];
            });

        return response()->json($students);
    }

    /**
     * Cetak Rekap Presensi (PDF/Excel) via modal.
     */
    public function printRekap(Request $request)
    {
        try {
            $validated = $request->validate([
                'format' => 'required|in:pdf,excel',
                'type' => 'required|in:harian,mingguan,bulanan,semester,tahunan',
                'print_mode' => 'required|in:class,student',
                'class_id' => 'nullable|integer|exists:school_classes,id',
                'student_scope' => 'nullable|in:single,class,classes,all',
                'student_id' => 'nullable|integer|exists:students,id',
                'scope_class_id' => 'nullable|integer|exists:school_classes,id',
                'period_type' => 'required|in:harian,mingguan,bulanan,semester,tahunan',
                'period_date' => 'nullable|date',
                'period_month' => 'nullable|integer|between:1,12',
                'period_year' => 'nullable|integer|min:2020|max:2030',
                'period_semester' => 'nullable|in:ganjil,genap',
                'period_academic_year' => 'nullable|integer|exists:academic_years,id',
            ], [
                'format.required' => 'Format output wajib dipilih.',
                'type.required' => 'Tipe rekap wajib dipilih.',
                'print_mode.required' => 'Jenis cetak wajib dipilih.',
                'period_type.required' => 'Periode wajib dipilih.',
                'period_date.required' => 'Tanggal wajib diisi untuk rekap harian/mingguan.',
                'period_month.required' => 'Bulan wajib dipilih untuk rekap bulanan.',
                'period_year.required' => 'Tahun wajib dipilih untuk rekap bulanan/semester/tahunan.',
                'period_semester.required' => 'Semester wajib dipilih untuk rekap semester.',
                'period_academic_year.required' => 'Tahun ajaran wajib dipilih untuk rekap semester/tahunan.',
                'student_id.required' => 'Siswa wajib dipilih untuk cetak per siswa tunggal.',
                'scope_class_id.required' => 'Kelas wajib dipilih untuk cetak per siswa per kelas.',
            ]);

            $format = $validated['format']; // pdf or excel
            $type = $validated['type']; // harian, mingguan, bulanan, semester, tahunan
            $printMode = $validated['print_mode']; // class or student
            $classId = $validated['class_id'] ?? null;
            $studentScope = $validated['student_scope'] ?? null; // single, class, classes
            $studentId = $validated['student_id'] ?? null;
            $scopeClassId = $validated['scope_class_id'] ?? null;
            $periodType = $validated['period_type'];
            $periodDate = $validated['period_date'] ?? null;
            $periodMonth = $validated['period_month'] ?? null;
            $periodYear = $validated['period_year'] ?? null;
            $periodSemester = $validated['period_semester'] ?? null;
            $periodAcademicYear = $validated['period_academic_year'] ?? null;

            // Build inputs array for getRecapData - use period_* fields.
            // CATATAN PENTING: field tanggal/bulan/tahun BAWAAN HALAMAN REKAP TIDAK
            // lagi ikut terkirim ke jalur cetak. Dulu modal popup diisi otomatis
            // dengan $startDate/$endDate hasil filter halaman (mis. 01 Okt - 31 Okt),
            // sehingga memilih "Mingguan" tetap terkirim rentang bulan itu dan
            // PDF berisi 31 baris. Sekarang popup hanya mengirim field periodenya
            // sendiri, jadi pilihan popup yang menentukan rentang.
            $inputs = [
                'date' => $periodDate,
                'month' => $periodMonth,
                'year' => $periodYear,
                'period_semester' => $periodSemester,
                'period_academic_year' => $periodAcademicYear,
            ];

            // Determine student query based on print mode and scope
            $studentQuery = Student::with('schoolClass')->where('status', 'Aktif');

            if ($printMode === 'class') {
                // Cetak per Kelas (Rekap Tabel)
                if ($classId) {
                    $studentQuery->where('school_class_id', $classId);
                }
                // Use getRecapData for class mode
                $recap = $this->getRecapData($periodType, $classId, $inputs, false);

                $studentIds = $studentQuery->pluck('id')->toArray();
                $recap['dataRows'] = collect($recap['dataRows'])->filter(function ($row) use ($studentIds) {
                    return in_array($row['student']->id, $studentIds);
                })->values();

                if ($format === 'pdf') {
                    return $this->generatePdfRekap($request, $recap, $format, $classId, $inputs);
                } else {
                    return $this->generateExcelRekap($request, $recap, $format, $classId, $inputs);
                }
            } else {
                // ================================================================
                // CABANG PER SISWA ÔÇö tulis ulang dari nol
                // Query siswa SAMA PERSIS dengan getRecapData()/tabel Rekap.
                // Tidak filter berdasarkan ada/tidaknya data presensi.
                // ================================================================

                // 1. Tentukan cakupan siswa berdasarkan scope.
        //    single  = satu siswa
        //    class   = semua siswa pada satu kelas (satu file)
        //    classes = semua kelas; dipecah jadi SATU FILE PER KELAS oleh JS di
        //              browser. Server TIDAK pernah menangani many kelas sekaligus:
        //              tiap request hanya satu kelas (student_scope=class).
        //    all     = seluruh siswa aktif dalam satu file. Scope ini TIDAK lagi
        //              dipakai popup (diganti "classes"), tetapi tetap diterima
        //              untuk kompatibilitas pemanggil lama.
                $studentQuery = Student::with('schoolClass')->where('status', 'Aktif');

                if ($studentScope === 'single' && $studentId) {
                    $studentQuery->where('id', $studentId);
                } elseif ($studentScope === 'class' && $scopeClassId) {
                    $studentQuery->where('school_class_id', $scopeClassId);
                } elseif ($studentScope === 'classes') {
                    return response()->json([
                        'message' => 'Scope "classes" harus dipecah per kelas oleh browser. Kirim satu request per kelas dengan student_scope=class.',
                    ], 422);
                }

                $students = $studentQuery->orderBy('school_class_id')->orderBy('name')->get();

                if ($students->isEmpty()) {
                    return response()->json(['message' => 'Tidak ada siswa aktif yang ditemukan untuk dicetak.'], 422);
                }

                // 2. Resolve date range dari inputs
                $now = Carbon::now('Asia/Jakarta');
                $activeYear = AcademicYear::getActive();
                $dateRange = $this->resolveDateRange($periodType, $inputs, $now, $activeYear);
                $startDate = $dateRange['start'];
                $endDate = $dateRange['end'];
                $periodLabel = $dateRange['label'];

                // 3. Ambil data rekap dari getRecapData (sumber kebenaran yang sama dengan tabel).
                //    Karena $students hanya berisi 1 (atau beberapa siswa dalam 1 kelas),
                //    getRecapData akan menghasilkan dataRows yang sesuai.
                $recap = $this->getRecapData($periodType, null, $inputs, false);

                // 4. Filter dataRows dari getRecapData hanya untuk siswa yang dipilih.
                $studentIds = $students->pluck('id');
                $filteredDataRows = collect($recap['dataRows'])->filter(function ($row) use ($studentIds) {
                    return $row['student'] && $studentIds->contains($row['student']->id);
                })->values();

                // 5. Build studentsData dalam format yang sama dengan pdf_multi_rekap:
                //    sections berisi rows dengan no, student, days/monthly, hadir, terlambat, sakit, izin, alfa, percentage.
                $studentsData = [];
                $no = 1;
                foreach ($filteredDataRows as $row) {
                    $row['no'] = $no++;
                    $studentsData[] = $row;
                }

                // Jika tidak ada dataRows (mis. periode tanpa data sama sekali), tetap
                // buat entry kosong supaya PDF tetap ter-render.
                if (empty($studentsData)) {
                    foreach ($students as $student) {
                        if ($periodType === 'harian') {
                            // Harian butuh field check_in, late_text, status, notes
                            $studentsData[] = [
                                'no' => 1,
                                'student' => $student,
                                'check_in' => '-',
                                'late_text' => '-',
                                'status' => 'Belum',
                                'notes' => '-',
                            ];
                        } else {
                            $studentsData[] = [
                                'no' => 1,
                                'student' => $student,
                                'days' => [],
                                'hadir' => 0,
                                'terlambat' => 0,
                                'sakit' => 0,
                                'izin' => 0,
                                'alfa' => 0,
                                'percentage' => 0,
                            ];
                        }
                    }
                }

                // 5. Generate PDF atau Excel
                if ($format === 'pdf') {
                    return $this->generatePdfStudentProfile($request, $studentsData, $periodType, $periodLabel, $studentScope, $recap);
                } else {
                    return $this->generateExcelStudentProfile($request, $studentsData, $periodType, $periodLabel, $studentScope, $recap);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Gagal cetak rekap: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception' => $e
            ]);
            return response()->json(['message' => 'Gagal membuat file: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Generate PDF Rekap (per Kelas - tabel rekap).
     */
    protected function generatePdfRekap(Request $request, array $recap, string $format, ?int $classId, array $inputs)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        Carbon::setLocale('id');

        $type = $recap['type'];
        $selectedClass = $classId ? SchoolClass::with('teacher')->find($classId) : null;
        $className = $classId ? SchoolClass::find($classId)?->name : 'Semua Kelas';

        $fileName = "Rekap_Presensi_{$type}_" . Str::slug($className) . ".pdf";

        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();
        $activeYear = AcademicYear::getActive();

        $sections = MultiPeriodAttendanceExport::buildSections(
            $recap['dataRows']->toArray(),
            $recap['type'],
            $inputs,
            $classId
        );

        $logoBase64 = optional(Setting::getLogo())->when(function ($relative) {
            $path = public_path($relative);
            $raw = file_exists($path) ? @file_get_contents($path) : null;
            if (!$raw) {
                return null;
            }
            if (function_exists('imagecreatefromstring')) {
                try {
                    $src = @imagecreatefromstring($raw);
                } catch (\Throwable $e) {
                    $src = false;
                }
                if ($src) {
                    try {
                        $w = imagesx($src);
                        $h = imagesy($src);
                        $max = 120;
                        $scale = min($max / max($w, 1), $max / max($h, 1), 1.0);
                        $nw = max(1, (int) round($w * $scale));
                        $nh = max(1, (int) round($h * $scale));

                        $dst = imagecreatetruecolor($nw, $nh);
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        $transparent = imagecolorallocatealpha($dst, 127, 127, 127, 127);
                        imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                        imagedestroy($src);

                        ob_start();
                        imagepng($dst, null, 6);
                        $png = ob_get_clean();
                        imagedestroy($dst);

                        if ($png) {
                            return 'data:image/png;base64,' . base64_encode($png);
                        }
                    } catch (\Throwable $e) {
                        // logo tidak wajib; cetak tetap jalan
                    }
                }
            }

            return 'data:' . ($mime = @mime_content_type($path) ?: 'image/png') . ';base64,' . base64_encode($raw);
        });

        $pdf = Pdf::loadView('admin.attendances.pdf_multi_rekap', array_merge($recap, [
            'title' => "Laporan Presensi {$recap['type']} - {$className}",
            'schoolName' => Setting::getSchoolName(),
            'schoolAddress' => Setting::getSchoolAddress(),
            'logoBase64' => $logoBase64,
            'activeYear' => $activeYear,
            'headmasterName' => Setting::getHeadmasterName(),
            'headmasterNip' => Setting::getHeadmasterNip(),
            'sections' => $sections,
        ]));

        $response = response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);

        return $response;
    }

    /**
     * Generate Excel Rekap (per Kelas - tabel rekap).
     */
    protected function generateExcelRekap(Request $request, array $recap, string $format, ?int $classId, array $inputs)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        $type = $recap['type'];
        $selectedClass = $classId ? SchoolClass::find($classId) : null;
        $className = $classId ? SchoolClass::find($classId)?->name : 'semua_kelas';

        $fileName = "Rekap_Presensi_{$type}_" . Str::slug($className) . ".xlsx";

        return \App\Services\DownloadCacheService::downloadRekapFile('xlsx', $recap['type'], $classId, $inputs, function() use ($type, $classId, $inputs, $className) {
            $response = Excel::download(new MultiPeriodAttendanceExport($type, array_merge($inputs, ['class_id' => $classId])), "Rekap_Presensi_{$type}_" . Str::slug($className) . ".xlsx");
            // Maatwebsite tidak selalu mengisi Content-Type untuk ekspor multi-sheet,
            // sehingga header sempat kosong. Dipaksa eksplisit di sini.
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            return $response;
        }, "Rekap_Presensi_{$type}_" . Str::slug($className) . ".xlsx");
    }    /**
     * Generate PDF Student Profile (per Siswa - profil lengkap).
     * Sekarang menggunakan view yang sama dengan pdf_multi_rekap.blade.php
     * sehingga layout, gaya, kop, keterangan, dan tanda tangan identik.
     */
    protected function generatePdfStudentProfile(Request $request, array $studentsData, string $periodType, string $periodLabel, ?string $studentScope, array $recap = [])
    {
        // Request cetak Per Siswa boleh berat: satu kelas x ~40 siswa x 180 hari
        // (semester) = ribuan baris tabel yang di-render dompdf sekali jalan.
        // Limit dinaikkan KHUSUS jalur ini; Per Kelas tidak tersentuh.
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '300');
        @set_time_limit(300);

        Carbon::setLocale('id');

        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();$schoolAddress = Setting::getSchoolAddress();

        $photoshopBaseUrl = config('services.photoshop.base_url', null);
        $headmasterName = Setting::getHeadmasterName();
        $headmasterNip = Setting::getHeadmasterNip();

        $logoBase64 = $this->studentProfileLogoBase64($photoshopBaseUrl);

        // Satu request = satu file (ZIP sudah dihapus). Tiap siswa mulai di
        // halaman baru dan header tabel diulang tiap halaman oleh view
        // (page-break-after + thead display:table-header-group).
        $fileName = $this->studentProfileFileName($studentsData, $periodType, $studentScope, 'pdf');

        // Build sections array untuk view pdf_multi_rekap (1 section = 1 siswa).
        $sections = [];
        foreach ($studentsData as $data) {
            $student = $data['student'] ?? null;
            if (!$student) {
                continue;
            }
            $className = $student->schoolClass?->name ?? '-';
            $wali = $student->schoolClass?->teacher;

            // Reuse the exact same row shape produced by getRecapData() so the
            // horizontal table in pdf_multi_rekap.blade.php receives the same
            // keys the class rekap already expects (no new counting logic).
            $rowData = $data;
            unset($rowData['student']); // view membaca $section['rows'][0]['student'] sebagai model
            $rowData['student'] = $student;

            $sections[] = [
                'class' => $student->schoolClass,
                'className' => $className,
                'rows' => [$rowData],
                'waliName' => $wali?->name,
                'waliNip' => $wali?->nip,
                // Build heading/subtitle sama seperti buildSections di MultiPeriodAttendanceExport
                'heading' => $this->buildStudentProfileHeading($periodType, $recap),
                'subtitle' => $this->buildStudentProfileSubtitle($periodType, $recap, $className, $data),
            ];
        }

        // Untuk semester/tahunan, kita perlu $months dan untuk mingguan/bulanan $dateColumns
        $months = $recap['months'] ?? null;
        $dateColumns = $recap['dateColumns'] ?? [];
        $daysInMonth = $recap['daysInMonth'] ?? null;

        // Build dateLabels untuk mingguan dari dateColumns
        $dateLabels = [];
        if ($periodType === 'mingguan' && !empty($dateColumns)) {
            foreach ($dateColumns as $col) {
                $dateLabels[] = [
                    'date' => $col['date'],
                    'label' => $col['label'] ?? $col['carbon']->translatedFormat('D, d/m'),
                ];
            }
        }

        $firstStudentName = $studentsData[0]['student']['name'] ?? '-';
        $pdf = Pdf::loadView('admin.attendances.pdf_multi_rekap', [
            'title' => "Profil Presensi {$periodType} - {$firstStudentName}",
            'studentsData' => $studentsData,
            'periodType' => $periodType,
            'periodLabel' => $periodLabel,
            'activeYear' => $activeYear,
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'logoBase64' => $logoBase64,
            'headmasterName' => $headmasterName,
            'headmasterNip' => $headmasterNip,
            // Dibuat saat request, bukan nilai tersimpan.
            'printedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i') . ' WIB',
            // Data untuk view (sama seperti pdf_multi_rekap)
            'sections' => $sections,
            'months' => $months,
            'dateColumns' => $dateColumns,
            'dateLabels' => $dateLabels,
            'daysInMonth' => $daysInMonth,
            'type' => $periodType,
        ])->setPaper('a4', 'landscape');

        $tempDir = storage_path('app/temp/profile_siswa_pdf');
        File::ensureDirectoryExists($tempDir);
        $tempFile = $tempDir . '/profil_' . uniqid('', true) . '.pdf';
        File::put($tempFile, $pdf->output());

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            // Mencegah browser/proxy menyajikan PDF lama.
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Build heading untuk student profile PDF (sama seperti MultiPeriodAttendanceExport::buildHeading).
     */
    protected function buildStudentProfileHeading(string $periodType, array $recap): string
    {
        Carbon::setLocale('id');
        $schoolName = Setting::getSchoolName();

        if ($periodType === 'semester') {
            $semester = ($recap['period_semester'] ?? 'ganjil') === 'genap' ? 'genap' : 'ganjil';
            return 'LAPORAN REKAPITULASI PRESENSI SEMESTER ' . strtoupper($semester);
        }
        if ($periodType === 'tahunan') {
            return 'LAPORAN REKAPITULASI PRESENSI TAHUNAN';
        }

        $label = strtoupper($periodType);
        return 'LAPORAN REKAPITULASI PRESENSI ' . $label . ' - ' . ($schoolName ?: '-');
    }

    /**
     * Build subtitle untuk student profile PDF (sama seperti MultiPeriodAttendanceExport::buildSubtitle).
     */
    protected function buildStudentProfileSubtitle(string $periodType, array $recap, string $className, array $data): string
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');

        if ($periodType === 'harian') {
            $date = $recap['date'] ?? $now->toDateString();
            $period = 'Tanggal: ' . Carbon::parse($date)->translatedFormat('l, d F Y');
        } elseif ($periodType === 'mingguan') {
            $dateColumns = $recap['dateColumns'] ?? [];
            if (!empty($dateColumns)) {
                $start = Carbon::parse($dateColumns[0]['date']);
                $end = Carbon::parse(end($dateColumns)['date']);
                $period = 'Periode: ' . $start->translatedFormat('d M Y') . ' s/d ' . $end->translatedFormat('d M Y');
            } else {
                $period = 'Periode: -';
            }
        } elseif ($periodType === 'semester' || $periodType === 'tahunan') {
            $startDate = $recap['startDate'] ?? '';
            $endDate = $recap['endDate'] ?? '';
            $period = 'Periode: ' . Carbon::parse($startDate)->translatedFormat('F Y')
                . ' s/d ' . Carbon::parse($endDate)->translatedFormat('F Y');
        } else {
            // Bulanan
            $month = (int) ($recap['month'] ?? $now->month);
            $year = (int) ($recap['year'] ?? $now->year);
            $period = 'Bulan: ' . Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        }

        $activeYear = AcademicYear::getActive();

        return $period
            . ' | Kelas: ' . $className
            . ' | Tahun Ajaran: ' . ($activeYear ? $activeYear->name : '-');
    }

    /**
     * Generate Excel Student Profile (per Siswa - profil lengkap).
     * Transform data ke format yang diharapkan StudentProfileExport.
     */
    protected function generateExcelStudentProfile(Request $request, array $studentsData, string $periodType, string $periodLabel, ?string $studentScope, array $recap = [])
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '300');
        @set_time_limit(300);

        Carbon::setLocale('id');

        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        // Transform studentsData ke format yang diharapkan StudentProfileExport
        $excelData = $this->transformForExcelExport($studentsData, $periodType, $recap);

        // Satu request = satu file: satu sheet per siswa di dalam 1 workbook.
        $fileName = $this->studentProfileFileName($studentsData, $periodType, $studentScope, 'xlsx');

        $response = Excel::download(
            new \App\Exports\StudentProfileExport($excelData, $periodType, $periodLabel, $activeYear, $schoolName, $schoolAddress),
            $fileName
        );
        // Maatwebsite tidak selalu mengisi Content-Type untuk multi-sheet.
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return $response;
    }

    /**
     * Transform studentsData ke format yang diharapkan StudentProfileExport.
     * StudentProfileExport expects:
     * - rows: array of daily rows with tanggal_raw, jam_masuk, jam_pulang, status, keterlambatan, keterangan
     * - stats: array with hadir, terlambat, sakit, izin, alfa, libur
     * - totalEffective, totalHadir, percentage
     */
    protected function transformForExcelExport(array $studentsData, string $periodType, array $recap): array
    {
        $transformed = [];
        foreach ($studentsData as $data) {
            $student = $data['student'] ?? null;
            if (!$student) {
                continue;
            }

            // Build rows untuk Excel export
            $rows = [];
            if ($periodType === 'harian') {
                // Harian: satu baris untuk tanggal tersebut
                $rows[] = [
                    'tanggal_raw' => $recap['date'] ?? Carbon::now()->toDateString(),
                    'jam_masuk' => $data['check_in'] ?? '-',
                    'jam_pulang' => '-',  // Excel export expects jam_pulang
                    'status' => $data['status'] ?? '-',
                    'keterlambatan' => $data['late_text'] ?? '-',
                    'keterangan' => $data['notes'] ?? '-',
                ];
            } elseif (in_array($periodType, ['mingguan', 'bulanan'], true)) {
                // Mingguan/Bulanan: build dari days array
                $dateColumns = $recap['dateColumns'] ?? [];
                foreach ($dateColumns as $col) {
                    $code = $data['days'][$col['date']] ?? '-';
                    $rows[] = [
                        'tanggal_raw' => $col['date'],
                        'jam_masuk' => '-',  // Tidak tersedia di format horizontal
                        'jam_pulang' => '-',
                        'status' => $this->codeToStatus($code),
                        'keterlambatan' => $code === 'T' ? 'Terlambat' : '-',
                        'keterangan' => '-',  // Tidak tersedia di format horizontal
                    ];
                }
            } elseif (in_array($periodType, ['semester', 'tahunan'], true)) {
                // Semester/Tahunan: build dari monthly data (per bulan)
                $months = $recap['months'] ?? [];
                $monthly = $data['monthly'] ?? [];
                foreach ($months as $monthInfo) {
                    $hadirCount = $monthly[$monthInfo['key']] ?? 0;
                    // Untuk Excel, kita hanya mencatat total hadir per bulan
                    // karena detail harian tidak tersedia di format horizontal
                    $rows[] = [
                        'tanggal_raw' => $monthInfo['key'] . '-01',  // First day of month
                        'jam_masuk' => '-',
                        'jam_pulang' => '-',
                        'status' => $hadirCount > 0 ? 'Hadir' : '-',  // Simplified
                        'keterlambatan' => '-',  // Tidak tersedia
                        'keterangan' => $hadirCount > 0 ? "{$hadirCount} hari hadir" : '-',  // Info di keterangan
                    ];
                }
            }

            // Build stats
            $stats = [
                'hadir' => $data['hadir'] ?? 0,
                'terlambat' => $data['terlambat'] ?? 0,
                'sakit' => $data['sakit'] ?? 0,
                'izin' => $data['izin'] ?? 0,
                'alfa' => $data['alfa'] ?? 0,
                'libur' => 0,  // Tidak dihitung di format horizontal
            ];

            // Total hari efektif (ini estimasi based on period type)
            $totalEffective = ($data['hadir'] ?? 0) + ($data['terlambat'] ?? 0) + ($data['sakit'] ?? 0) + ($data['izin'] ?? 0) + ($data['alfa'] ?? 0);
            $totalHadir = ($data['hadir'] ?? 0) + ($data['terlambat'] ?? 0);
            $percentage = $data['percentage'] ?? 0;

            $transformed[] = [
                'student' => $student,
                'rows' => $rows,
                'stats' => $stats,
                'totalEffective' => $totalEffective,
                'totalHadir' => $totalHadir,
                'percentage' => $percentage,
            ];
        }

        return $transformed;
    }

    /**
     * Konversi kode status (H, T, S, I, A, L, -) ke status teks.
     */
    protected function codeToStatus(string $code): string
    {
        return match ($code) {
            'H' => 'Hadir',
            'T' => 'Terlambat',
            'S' => 'Sakit',
            'I' => 'Izin',
            'A' => 'Alfa',
            'L' => 'Libur',
            default => '-',
        };
    }

    /**
     * Logo sekolah untuk PDF Per Siswa, diperkecil dan di-embed SEKALI.
     *
     * Logo asli bisa berukuran ratusan KB. Pada cetak per kelas (puluhan siswa
     * x ratusan baris) logo mentahan berulang kali di dalam DOM dan di-decode
     * ulang oleh dompdf untuk tiap gambar, yang_tx memboroskan memori. Di sini
     * logo dikecilkan maksimal 120 px lalu di-encode sekali menjadi PNG, lalu
     * string base64 yang sama dipakai untuk semua siswa. Tetap tajam karena di
     * PDF logo hanya dicetak ~13 mm (52 px pada 96 dpi).
     */
    protected function studentProfileLogoBase64(?string $photoshopBaseUrl = null): ?string
    {
        static $cache = null;
        static $resolved = false;

        if ($resolved) {
            return $cache;
        }
        $resolved = true;

        // Mode Photoshop: kembalikan data kosong supaya template CONDONG
        // menggambar kwadran gelap, ikon bintang, dan nama kelas kotak biru.
        // Jalur ini hanya diaktifkan bila pengaturan sudah memenuhi syarat
        // (lihat "`config/services.php` → `photoshop.*`" pada README).
        if (is_string($photoshopBaseUrl) && preg_match('#^https?://#', $photoshopBaseUrl)) {
            return $cache = '';
        }

        $relative = Setting::getLogo();
        if (!$relative) {
            return $cache = null;
        }

        $path = public_path($relative);
        if (!is_file($path)) {
            return $cache = null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $cache = null;
        }

        // Fallback: kalau GD tidak bisa membaca (mis. WEBP tanpa dukungan), pakai
        // berkas asli apa adanya.
        $max = 120;
        if (function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor')) {
            try {
                $src = @imagecreatefromstring($raw);
            } catch (\Throwable $e) {
                $src = false;
            }
            if ($src) {
                try {
                    $w = imagesx($src);
                    $h = imagesy($src);
                    $scale = min($max / max($w, 1), $max / max($h, 1), 1.0);
                    $nw = max(1, (int) round($w * $scale));
                    $nh = max(1, (int) round($h * $scale));

                    $dst = imagecreatetruecolor($nw, $nh);
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 127, 127, 127, 127);
                    imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                    imagedestroy($src);

                    ob_start();
                    imagepng($dst, null, 6);
                    $png = ob_get_clean();
                    imagedestroy($dst);

                    if ($png) {
                        return $cache = 'data:image/png;base64,' . base64_encode($png);
                    }
                } catch (\Throwable $e) {
                    // logo tidak wajib; cetak tetap jalan tanpa logo.
                }
            }
        }

        $mime = @mime_content_type($path) ?: 'image/png';

        return $cache = 'data:' . $mime . ';base64,' . base64_encode($raw);
    }
}

