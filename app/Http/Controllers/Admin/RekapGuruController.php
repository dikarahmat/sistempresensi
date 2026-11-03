<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Teacher;
use Carbon\Carbon;
public function index(Request $request): \Illuminate\View\View
    {
        $period = $request->input('period', 'bulanan');
        $startDate = null;
        $endDate = null;
        
        if ($period === 'harian') {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
        } elseif ($period === 'mingguan') {
public function getTeacherRecapData(Request $request): \Illuminate\Http\JsonResponse
    {
        $type = $request->input('type', 'bulanan');
        $teacherId = $request->input('teacher_id');

        $teachers = Teacher::when($teacherId, function ($query, $teacherId) {
            return $query->where('id', $teacherId);
        })->get();

        $data = [];

        foreach ($teachers as $teacher) {
            $recapData = $this->calculateTeacherRecap($teacher->id, $type, $request->all());
            $data[] = [
                'nip' => $teacher->nip,
public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $type = $request->input('type', 'bulanan');
        $teacherId = $request->input('teacher_id');
        $inputs = $request->all();

        $teachers = Teacher::when($teacherId, function ($query, $teacherId) {
            return $query->where('id', $teacherId);
        })->get();

        $data = [];

        foreach ($teachers as $teacher) {
            $recapData = $this->calculateTeacherRecap($teacher->id, $type, $inputs);
            $data[] = [
                'nip' => $teacher->nip,
                'name' => $teacher->name,
                'jk' => $teacher->gender,
                'hadir' => $recapData['hadir'] ?? 0,
                'terlambat' => $recapData['terlambat'] ?? 0,
                'ijin' => $recapData['ijin'] ?? 0,
                'sakit' => $recapData['sakit'] ?? 0,
            ];
        }

        $schoolLogo = Setting::getLogoDataUri();
        $schoolName = Setting::getSchoolName();

        $pdf = PDF::loadView('admin.rekap.guru_pdf', [
            'data' => $data,
            'period' => $type,
            'schoolLogo' => $schoolLogo,
            'schoolName' => $schoolName,
        ]);

        return $pdf->download('rekap_guru_' . $type . '.pdf');
    }

    public function exportExcel(Request $request): \Illuminate\Http\Response
    {
        $type = $request->input('type', 'bulanan');
        $teacherId = $request->input('teacher_id');
        $inputs = $request->all();

        $teachers = Teacher::when($teacherId, function ($query, $teacherId) {
            return $query->where('id', $teacherId);
        })->get();

        $data = [];

        foreach ($teachers as $teacher) {
            $recapData = $this->calculateTeacherRecap($teacher->id, $type, $inputs);
            $data[] = [
                'nip' => $teacher->nip,
                'name' => $teacher->name,
                'jk' => $teacher->gender,
                'hadir' => $recapData['hadir'] ?? 0,
                'terlambat' => $recapData['terlambat'] ?? 0,
                'ijin' => $recapData['ijin'] ?? 0,
                'sakit' => $recapData['sakit'] ?? 0,
            ];
        }

        return response()->download('data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'rekap_guru_' . $type . '.xlsx',
            ['Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->headers->set('Content-Disposition', 'attachment; filename="rekap_guru_' . $type . '.xlsx"');
    }
}
                'name' => $teacher->name,
                'jk' => $teacher->gender,
                'hadir' => $recapData['hadir'] ?? 0,
                'terlambat' => $recapData['terlambat'] ?? 0,
                'ijin' => $recapData['ijin'] ?? 0,
                'sakit' => $recapData['sakit'] ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => $type,
        ]);
    }

    private function calculateTeacherRecap(int $teacherId, string $type, array $inputs): array
    {
        $now = Carbon::now();
        $dateRange = $this->resolveDateRange($type, $inputs);

        $query = \App\Models\TeacherAttendance::where('teacher_id', $teacherId);

        if ($dateRange['start'] && $dateRange['end']) {
            $query->whereBetween('date', [$dateRange['start'], $dateRange['end']]);
        }

        $attendances = $query->get();

        $hadir = 0;
        $terlambat = 0;
        $ijin = 0;
        $sakit = 0;

        foreach ($attendances as $attendance) {
            if ($attendance->status === 'hadir') {
                $hadir++;
            } elseif ($attendance->status === 'terlambat') {
                $terlambat++;
            } elseif ($attendance->status === 'ijin') {
                $ijin++;
            } elseif ($attendance->status === 'sakit') {
                $sakit++;
            }
        }

        return [
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'ijin' => $ijin,
            'sakit' => $sakit,
        ];
    }

    private function resolveDateRange(string $type, array $inputs): array
    {
        $now = Carbon::now();
        $start = null;
        $end = null;

        switch ($type) {
            case 'harian':
                $start = $inputs['start_date'] ?? $now->format('Y-m-d');
                $end = $inputs['end_date'] ?? $now->format('Y-m-d');
                break;
            case 'mingguan':
                $date = $inputs['date'] ?? $now;
                if (is_string($date)) {
                    $date = Carbon::parse($date);
                }
                $start = $date->startOfWeek()->format('Y-m-d');
                $end = $date->endOfWeek()->format('Y-m-d');
                break;
            case 'bulanan':
                $month = $inputs['month'] ?? $now->month;
                $year = $inputs['year'] ?? $now->year;
                $start = "$year-$month-01";
                $end = Carbon::createFromFormat('Y-m-t', "$year-$month-")->format('Y-m-d');
                break;
        }

        return ['start' => $start, 'end' => $end];
    }
}
            $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::now();
            $startDate = $date->startOfWeek()->format('Y-m-d');
            $endDate = $date->endOfWeek()->format('Y-m-d');
        } elseif ($period === 'bulanan') {
            $month = $request->input('month', Carbon::now()->month);
            $year = $request->input('year', Carbon::now()->year);
            $startDate = "$year-$month-01";
            $lastDay = Carbon::createFromFormat('Y-m-d', "$year-$month-31")->day;
            if ($lastDay > 28) {
                $lastDay = Carbon::createFromFormat('Y-m-t', "$year-$month-")->day;
            }
            $endDate = "$year-$month-$lastDay";
        }

        $schoolLogo = Setting::getLogoUrl();
        $schoolName = Setting::getSchoolName();

        return view('admin.rekap.guru', compact('period', 'startDate', 'endDate', 'schoolLogo', 'schoolName'));
    }
}
use Maatwebsite\Excel\Facades\Excel;
use PDF;
 
