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
use App\Models\Teacher;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
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
        $type = in_array($type, ['harian', 'mingguan', 'bulanan']) ? $type : 'bulanan';
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
        } elseif ($type === 'mingguan') {
            $startDate = $inputs['start_date'] ?? $now->copy()->startOfWeek()->toDateString();
            $endDate = $inputs['end_date'] ?? $now->copy()->startOfWeek()->addDays(4)->toDateString();

            $startObj = Carbon::parse($startDate);
            $endObj = Carbon::parse($endDate);

            $dateColumns = [];
            $cur = $startObj->copy();
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
                $stAtts = $attendances->get($student->id, collect())->keyBy('date');
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

            return compact(
                'type', 'startDate', 'endDate', 'startObj', 'endObj',
                'dateColumns', 'students', 'dataRows', 'totalHadir',
                'totalTerlambat', 'totalSakit', 'totalIzin', 'totalAlfa',
                'studentPaginator'
            );
        } else {
            // BULANAN
            $month = (int) ($inputs['month'] ?? $now->month);
            $year = (int) ($inputs['year'] ?? $now->year);
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

            $holidayMap = [];
            $dayDateStrings = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $cDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $dayDateStrings[$d] = $cDate;
                $isWeekend = Carbon::createFromDate($year, $month, $d)->isWeekend();
                $isHol = Holiday::isHoliday($cDate);
                if ($isWeekend || $isHol) {
                    $holidayMap[$d] = Holiday::getHolidayDescription($cDate) ?? 'Akhir Pekan';
                }
            }

            $startDate = $dayDateStrings[1];
            $endDate = $dayDateStrings[$daysInMonth];
            $nowDateStr = $now->toDateString();

            $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
                ->whereIn('student_id', $students->pluck('id'))
                ->select(['id', 'student_id', 'date', 'status', 'time_remark'])
                ->get()
                ->groupBy('student_id');

            $totalEffective = max(1, $daysInMonth - count($holidayMap));

            foreach ($students as $student) {
                $stAtts = $attendances->get($student->id, collect())->keyBy(function ($item) {
                    return (int) substr($item->date, 8, 2);
                });

                $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;
                $days = [];

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $cDate = $dayDateStrings[$d];
                    $att = $stAtts->get($d);
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
                        if (isset($holidayMap[$d])) {
                            $code = 'L';
                        } elseif ($cDate <= $nowDateStr) {
                            $code = 'A';
                            $a++;
                        }
                    }

                    $days[$d] = $code;
                }

                $percentage = round(($h / $totalEffective) * 100);

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

            return compact(
                'type', 'month', 'year', 'daysInMonth', 'holidayMap',
                'students', 'dataRows', 'totalHadir', 'totalTerlambat',
                'totalSakit', 'totalIzin', 'totalAlfa', 'studentPaginator'
            );
        }
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
            // rekap error 500 ("Attempt to read property 'nis' on null").
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

}
