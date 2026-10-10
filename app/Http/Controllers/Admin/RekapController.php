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
                $status = 'Belum Hadir';
                $checkIn = '-';
                $lateMinutes = 0;
                $lateText = '-';
                $notes = '-';
                $proofDoc = null;

                if ($att) {
                    $status = $att->status;
                    $notes = $att->notes ?? '-';
                    $proofDoc = $att->proof_document;

                    if ($status === 'Hadir') {
                        $checkIn = $att->check_in ? substr($att->check_in, 0, 5) . ' WIB' : '-';
                        if ($att->time_remark === 'Terlambat') {
                            // Cara menghitung selisih menit TIDAK diubah.
                            // $diff dibulatkan ke integer karena diffInMinutes()
                            // bisa menghasilkan float (mis. 892.0166666666667).
                            $diff = Carbon::parse($att->check_in)->diffInMinutes(Carbon::parse($lateLimitTime), false);
                            $lateMinutes = $diff < 0 ? (int) round(abs($diff)) : 0;
                            // Format tampilan ramah: "15 MNT" / "1 JAM 5 MNT".
                            $lateText = rekap_format_late_minutes($lateMinutes);
                            $status = 'Terlambat';
                            $totalTerlambat++;
                            $totalHadir++;
                        } else {
                            $lateText = 'Tepat Waktu';
                            $totalHadir++;
                        }
                    } elseif ($status === 'Sakit') {
                        $totalSakit++;
                    } elseif ($status === 'Izin') {
                        $totalIzin++;
                    } elseif ($status === 'Alfa') {
                        $totalAlfa++;
                    }
                } else {
                    if ($isHoliday) {
                        $status = 'Libur';
                    } elseif ($date < $now->toDateString()) {
                        $status = 'Alfa';
                        $totalAlfa++;
                    } elseif ($date === $now->toDateString() && $now->format('H:i') > $lateLimitTime) {
                        $status = 'Alfa';
                        $totalAlfa++;
                    }
                }

                $dataRows[] = [
                    'student' => $student,
                    'check_in' => $checkIn,
                    'late_text' => $lateText,
                    'late_minutes' => $lateMinutes,
                    'status' => $status,
                    'notes' => $notes,
                    'proof_document' => $proofDoc,
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
                    if ($att->status === 'Hadir') {
                        if ($att->time_remark === 'Terlambat') {
                            $code = 'T';
                            $t++;
                            $h++;
                        } else {
                            $code = 'H';
                            $h++;
                        }
                    } elseif ($att->status === 'Sakit') {
                        $code = 'S';
                        $s++;
                    } elseif ($att->status === 'Izin') {
                        $code = 'I';
                        $i++;
                    } elseif ($att->status === 'Alfa') {
                        $code = 'A';
                        $a++;
                    }
                } else {
                    if ($col['is_holiday']) {
                        $code = 'L';
                    } elseif ($dStr <= $now->toDateString()) {
                        $code = 'A';
                        $a++;
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

        // Add semester-specific data
        if ($type === 'semester') {
            $extra['period_semester'] = $inputs['period_semester'] ?? 'ganjil';
            $extra['period_academic_year'] = $inputs['period_academic_year'] ?? $activeYear?->id;
        }

        // Add tahunan-specific data
        if ($type === 'tahunan') {
            $extra['period_academic_year'] = $inputs['period_academic_year'] ?? $activeYear?->id;
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
                // Semester: pakai tanggal tahun ajaran bila tersedia.
                // Fallback kalender: Ganjil 1 Jul - 31 Des, Genap 1 Jan - 30 Jun.
                $semester = ($inputs['period_semester'] ?? 'ganjil') === 'genap' ? 'genap' : 'ganjil';
                $ay = AcademicYear::find($inputs['period_academic_year'] ?? ($activeYear?->id ?? 0)) ?: $activeYear;

                if ($ay && $ay->start_date) {
                    $ayStart = Carbon::parse($ay->start_date);
                    if ($semester === 'ganjil') {
                        $startDate = $ayStart->copy()->startOfMonth()->toDateString();
                        $endDate = $ayStart->copy()->addMonths(5)->endOfMonth()->toDateString();
                    } else {
                        $startDate = $ayStart->copy()->addMonths(6)->startOfMonth()->toDateString();
                        $endDate = $ay->end_date ? Carbon::parse($ay->end_date)->toDateString() : $ayStart->copy()->addMonths(11)->endOfMonth()->toDateString();
                    }
                } else {
                    $refYear = $now->year;
                    if ($semester === 'ganjil') {
                        $startDate = Carbon::createFromDate($refYear, 7, 1)->toDateString();
                        $endDate = Carbon::createFromDate($refYear, 12, 31)->toDateString();
                    } else {
                        $startDate = Carbon::createFromDate($refYear, 1, 1)->toDateString();
                        $endDate = Carbon::createFromDate($refYear, 6, 30)->toDateString();
                    }
                }
                return [
                    'start' => $startDate,
                    'end' => $endDate,
                    'label' => 'Semester ' . ucfirst($semester) . ' ' . ($ay->name ?? Carbon::parse($startDate)->format('Y')),
                ];

            case 'tahunan':
                // Tahunan: satu tahun ajaran penuh.
                $ay = AcademicYear::find($inputs['period_academic_year'] ?? ($activeYear?->id ?? 0)) ?: $activeYear;
                if ($ay && $ay->start_date) {
                    $startDate = Carbon::parse($ay->start_date)->toDateString();
                    $endDate = $ay->end_date
                        ? Carbon::parse($ay->end_date)->toDateString()
                        : Carbon::parse($ay->start_date)->addMonths(11)->endOfMonth()->toDateString();
                } else {
                    $startDate = Carbon::createFromDate($now->year, 7, 1)->toDateString();
                    $endDate = Carbon::createFromDate($now->year + 1, 6, 30)->toDateString();
                }
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
            return redirect()->back()->with('error', 'Gagal membuat file export. Silakan coba lagi atau hubungi admin.');
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

                // Ambil logo sekolah jika ada
                $logoPath = Setting::getLogo();
                $logoBase64 = null;
                if (file_exists(public_path($logoPath))) {
                    $logoData = file_get_contents(public_path($logoPath));
                    $logoBase64 = 'data:image/webp;base64,' . base64_encode($logoData);
                }

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
            return redirect()->back()->with('error', 'Gagal membuat file export. Silakan coba lagi atau hubungi admin.');
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

                // 3. Ambil data rekap dari getRecapData (sumber kebenaran yang sama dengan tabel)
                $recap = $this->getRecapData($periodType, null, $inputs, false);

                // 4. Build studentsData dari dataRows getRecapData + jam masuk/pulang dari presensi
                $lateLimitTime = Setting::getLateLimitTime();
                Carbon::setLocale('id');

                $studentIds = $students->pluck('id');
                $allAttendances = Attendance::whereIn('student_id', $studentIds)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->get()
                    ->groupBy('student_id');

                // Build date columns (same as getRecapData for mingguan/bulanan)
                $dateColumns = [];
                $cur = Carbon::parse($startDate)->copy();
                $endObj = Carbon::parse($endDate);
                while ($cur->lte($endObj)) {
                    $curDateStr = $cur->toDateString();
                    $dateColumns[] = [
                        'date' => $curDateStr,
                        'carbon' => $cur->copy(),
                        'is_holiday' => $cur->isWeekend() || Holiday::isHoliday($curDateStr),
                    ];
                    $cur->addDay();
                }

                $studentsData = [];
                foreach ($students as $student) {
                    $stAtts = $allAttendances->get($student->id, collect())->keyBy(fn($a) => $a->date->toDateString());

                    $stats = ['hadir' => 0, 'terlambat' => 0, 'sakit' => 0, 'izin' => 0, 'alfa' => 0, 'libur' => 0];
                    $rows = [];

                    foreach ($dateColumns as $col) {
                        $dStr = $col['date'];
                        $att = $stAtts->get($dStr);

                        $status = '-';
                        $jamMasuk = '-';
                        $jamPulang = '-';
                        $keterlambatan = '-';
                        $keterangan = '-';

                        if ($att) {
                            $status = $att->effective_status;
                            $jamMasuk = $att->check_in ? substr($att->check_in, 0, 5) . ' WIB' : '-';
                            $jamPulang = $att->check_out ? substr($att->check_out, 0, 5) . ' WIB' : '-';
                            $keterangan = $att->notes ?? '-';

                            if ($att->status === 'Hadir' && $att->time_remark === 'Terlambat') {
                                $diff = Carbon::parse($att->check_in)->diffInMinutes(Carbon::parse($lateLimitTime), false);
                                $lateMinutes = $diff < 0 ? (int) round(abs($diff)) : 0;
                                $keterlambatan = rekap_format_late_minutes($lateMinutes);
                            }
                        } else {
                            if ($col['is_holiday']) {
                                $status = 'Libur';
                            } elseif ($dStr <= $now->toDateString()) {
                                $status = 'Alfa';
                            }
                        }

                        switch ($status) {
                            case 'Hadir': $stats['hadir']++; break;
                            case 'Terlambat': $stats['terlambat']++; break;
                            case 'Sakit': $stats['sakit']++; break;
                            case 'Izin': $stats['izin']++; break;
                            case 'Alfa': $stats['alfa']++; break;
                            case 'Libur': $stats['libur']++; break;
                        }

                        $rows[] = [
                            'tanggal' => $col['carbon']->translatedFormat('l, d F Y'),
                            'tanggal_raw' => $dStr,
                            'jam_masuk' => $jamMasuk,
                            'jam_pulang' => $jamPulang,
                            'status' => $status,
                            'keterlambatan' => $keterlambatan,
                            'keterangan' => $keterangan,
                        ];
                    }

                    $totalEffective = count(array_filter($dateColumns, fn($c) => !$c['is_holiday']));
                    $totalHadir = $stats['hadir'] + $stats['terlambat'];
                    $percentage = $totalEffective > 0 ? round(($totalHadir / $totalEffective) * 100, 1) : 0;

                    $studentsData[] = [
                        'student' => $student,
                        'rows' => $rows,
                        'stats' => $stats,
                        'totalEffective' => $totalEffective,
                        'totalHadir' => $totalHadir,
                        'percentage' => $percentage,
                    ];
                }

                // 5. Generate PDF atau Excel
                if ($format === 'pdf') {
                    return $this->generatePdfStudentProfile($request, $studentsData, $periodType, $periodLabel, $studentScope);
                } else {
                    return $this->generateExcelStudentProfile($request, $studentsData, $periodType, $periodLabel, $studentScope);
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
     * Build detailed attendance data per student for a period (Rekap calculation rules).
     * Returns array of student data with: student, rows (detail per date), stats, totals.
     */
    protected function buildStudentAttendanceDetail($students, string $periodType, string $startDate, string $endDate, $allAttendances, Carbon $now): array
    {
        Carbon::setLocale('id');
        $lateLimitTime = Setting::getLateLimitTime();

        // Build date columns for the period
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

        $studentsData = [];
        foreach ($students as $student) {
            // keyBy date only (YYYY-MM-DD), not datetime string
            $stAtts = $allAttendances->get($student->id, collect())->keyBy(fn($a) => $a->date->toDateString());
            
            $stats = [
                'hadir' => 0,
                'terlambat' => 0,
                'sakit' => 0,
                'izin' => 0,
                'alfa' => 0,
                'libur' => 0,
            ];

            $rows = [];
            foreach ($dateColumns as $col) {
                $dStr = $col['date'];
                $att = $stAtts->get($dStr);
                
                $status = '-';
                $jamMasuk = '-';
                $jamPulang = '-';
                $keterlambatan = '-';
                $keterangan = '-';

                if ($att) {
                    $status = $att->effective_status;
                    $jamMasuk = $att->check_in ? substr($att->check_in, 0, 5) . ' WIB' : '-';
                    $jamPulang = $att->check_out ? substr($att->check_out, 0, 5) . ' WIB' : '-';
                    $keterangan = $att->notes ?? '-';

                    if ($att->status === 'Hadir' && $att->time_remark === 'Terlambat') {
                        $diff = Carbon::parse($att->check_in)->diffInMinutes(Carbon::parse($lateLimitTime), false);
                        $lateMinutes = $diff < 0 ? (int) round(abs($diff)) : 0;
                        $keterlambatan = rekap_format_late_minutes($lateMinutes);
                    }
                } else {
                    if ($col['is_holiday']) {
                        $status = 'Libur';
                    } elseif ($dStr <= $now->toDateString()) {
                        $status = 'Alfa';
                    }
                }

                // Count stats (Rekap rules)
                switch ($status) {
                    case 'Hadir':
                        $stats['hadir']++;
                        break;
                    case 'Terlambat':
                        $stats['terlambat']++;
                        break;
                    case 'Sakit':
                        $stats['sakit']++;
                        break;
                    case 'Izin':
                        $stats['izin']++;
                        break;
                    case 'Alfa':
                        $stats['alfa']++;
                        break;
                    case 'Libur':
                        $stats['libur']++;
                        break;
                }

                $rows[] = [
                    'tanggal' => $col['carbon']->translatedFormat('l, d F Y'),
                    'tanggal_raw' => $dStr,
                    'jam_masuk' => $jamMasuk,
                    'jam_pulang' => $jamPulang,
                    'status' => $status,
                    'keterlambatan' => $keterlambatan,
                    'keterangan' => $keterangan,
                ];
            }

            $totalEffective = count(array_filter($dateColumns, fn($c) => !$c['is_holiday']));
            $totalHadir = $stats['hadir'] + $stats['terlambat'];
            $percentage = $totalEffective > 0 ? round(($totalHadir / $totalEffective) * 100, 1) : 0;

            $studentsData[] = [
                'student' => $student,
                'rows' => $rows,
                'stats' => $stats,
                'totalEffective' => $totalEffective,
                'totalHadir' => $totalHadir,
                'percentage' => $percentage,
            ];
        }

        return $studentsData;
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

        return \App\Services\DownloadCacheService::downloadRekapFile('pdf', $type, $classId, $inputs, function() use ($type, $classId, $recap, $className, $inputs) {
            $activeYear = AcademicYear::getActive();
            $schoolName = Setting::getSchoolName();
            $schoolAddress = Setting::getSchoolAddress();

            $logoPath = public_path(Setting::getLogo());
            $logoBase64 = null;
            if (file_exists($logoPath)) {
                $logoData = file_get_contents(public_path(Setting::getLogo()));
                $logoBase64 = 'data:image/webp;base64,' . base64_encode($logoData);
            }

            $sections = MultiPeriodAttendanceExport::buildSections(
                $recap['dataRows']->toArray(),
                $recap['type'],
                $inputs,
                $classId
            );

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.attendances.pdf_multi_rekap', array_merge($recap, [
                'title' => "Laporan Presensi {$recap['type']} - {$className}",
                'schoolName' => Setting::getSchoolName(),
                'schoolAddress' => Setting::getSchoolAddress(),
                'logoBase64' => $logoBase64,
                'activeYear' => $activeYear,
                'headmasterName' => Setting::getHeadmasterName(),
                'headmasterNip' => Setting::getHeadmasterNip(),
                'sections' => $sections,
            ]))->setPaper('a4', 'landscape');

            return $pdf->output();
        }, "Rekap_Presensi_{$type}_" . Str::slug($className) . ".pdf");
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
    }

    /**
     * Generate PDF Student Profile (per Siswa - profil lengkap).
     */
    protected function generatePdfStudentProfile(Request $request, array $studentsData, string $periodType, string $periodLabel, ?string $studentScope)
    {
        // Request cetak Per Siswa boleh berat: satu kelas x ~40 siswa x 180 hari
        // (semester) = ribuan baris tabel yang di-render dompdf sekali jalan.
        // Limit dinaikkan KHUSUS jalur ini; Per Kelas tidak tersentuh.
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '300');
        @set_time_limit(300);

        Carbon::setLocale('id');

        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();
        $headmasterName = Setting::getHeadmasterName();
        $headmasterNip = Setting::getHeadmasterNip();

        $logoBase64 = $this->studentProfileLogoBase64();

        // Satu request = satu file (ZIP sudah dihapus). Tiap siswa mulai di
        // halaman baru dan header tabel diulang tiap halaman oleh view
        // (page-break-after + thead display:table-header-group).
        $fileName = $this->studentProfileFileName($studentsData, $periodType, $studentScope, 'pdf');

        // PERBAIKAN (2026-10-10): jalur cetak PER SISWA TIDAK boleh memakai
        // DownloadCacheService. Kunci cache lama hanya 'rekap_{type}_all_pdf_' .
        // md5([]) . '{attendanceStamp}_{settingStamp}' karena $allInputs = [] dan
        // $classId = null. Kunci itu:
        //   1) tidak pernah berubah => view/design baru tidak pernah meng-invalidasi,
        //   2) tidak membedakan siswa mana yang dicetak => unduhan siswa kedua
        //     -serving ulang PDF siswa pertama,
        //   3) membuat "Dicetak pada" (Carbon::now) terkunci di waktu render pertama
        //      sehingga jam CETAK selalu sama di semua unduhan berikutnya.
        // Karena itu PDF per siswa sekarang SELALU render baru per request dan
        // ditulis ke file sementara unik (tidak pernah dipakai ulang), lalu
        // dihapus otomatis setelah terkirim. Per Kelas TIDAK tersentuh.
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.attendances.pdf_student_profile', [
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
        ])->setPaper('a4', 'portrait');

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
     * Generate Excel Student Profile (per Siswa - profil lengkap).
     */
    protected function generateExcelStudentProfile(Request $request, array $studentsData, string $periodType, string $periodLabel, ?string $studentScope)
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '300');
        @set_time_limit(300);

        Carbon::setLocale('id');

        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        // Satu request = satu file: satu sheet per siswa di dalam 1 workbook.
        $fileName = $this->studentProfileFileName($studentsData, $periodType, $studentScope, 'xlsx');

        $response = Excel::download(
            new \App\Exports\StudentProfileExport($studentsData, $periodType, $periodLabel, $activeYear, $schoolName, $schoolAddress),
            $fileName
        );
        // Maatwebsite tidak selalu mengisi Content-Type untuk multi-sheet.
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return $response;
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
    protected function studentProfileLogoBase64(): ?string
    {
        static $cache = null;
        static $resolved = false;

        if ($resolved) {
            return $cache;
        }
        $resolved = true;

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

