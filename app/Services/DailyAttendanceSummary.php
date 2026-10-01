<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailyAttendanceSummary
{
    public function summarize(Collection $classes, string $date): Collection
    {
        $classIds = $classes->modelKeys();
        if ($classIds === []) {
            return collect();
        }

        $latestAttendance = DB::table('attendances')
            ->selectRaw('student_id, MAX(id) AS attendance_id')
            ->whereDate('date', $date)
            ->groupBy('student_id');

        $summaries = DB::table('school_classes')
            ->leftJoin('students', function ($join) {
                $join->on('students.school_class_id', '=', 'school_classes.id')
                    ->where('students.status', '=', 'Aktif');
            })
            ->leftJoinSub($latestAttendance, 'daily_attendances', function ($join) {
                $join->on('daily_attendances.student_id', '=', 'students.id');
            })
            ->leftJoin('attendances', 'attendances.id', '=', 'daily_attendances.attendance_id')
            ->whereIn('school_classes.id', $classIds)
            ->select('school_classes.id as class_id')
            ->selectRaw('COUNT(students.id) AS total_students')
            ->selectRaw("COUNT(CASE WHEN attendances.status = 'Hadir' AND (attendances.time_remark IS NULL OR attendances.time_remark != 'Terlambat') AND (attendances.is_late IS NULL OR attendances.is_late = 0) THEN 1 END) AS hadir")
            ->selectRaw("COUNT(CASE WHEN attendances.status = 'Terlambat' OR (attendances.status = 'Hadir' AND (attendances.time_remark = 'Terlambat' OR attendances.is_late = 1)) THEN 1 END) AS terlambat")
            ->selectRaw("COUNT(CASE WHEN attendances.status = 'Sakit' THEN 1 END) AS sakit")
            ->selectRaw("COUNT(CASE WHEN attendances.status = 'Izin' THEN 1 END) AS izin")
            ->selectRaw("COUNT(CASE WHEN attendances.status IN ('Alfa', 'Alpha') THEN 1 END) AS alpha")
            ->groupBy('school_classes.id')
            ->get()
            ->keyBy('class_id');

        return $classes->map(function ($class) use ($summaries) {
            $summary = $summaries->get($class->id);
            $totalStudents = (int) ($summary->total_students ?? 0);
            $hadir = (int) ($summary->hadir ?? 0);
            $terlambat = (int) ($summary->terlambat ?? 0);
            $sakit = (int) ($summary->sakit ?? 0);
            $izin = (int) ($summary->izin ?? 0);
            $alpha = (int) ($summary->alpha ?? 0);
            $totalHadir = $hadir + $terlambat;
            $sudahAbsen = $totalHadir + $sakit + $izin + $alpha;

            return [
                'id' => $class->id,
                'nama_kelas' => $class->name ?? '-',
                'tingkat' => $class->grade ?? $class->level ?? '-',
                'guru_kelas' => $class->teacher?->name ?? 'Belum ditentukan',
                'total_siswa' => $totalStudents,
                'sudah_absen' => $sudahAbsen,
                'belum' => max(0, $totalStudents - $sudahAbsen),
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'total_hadir' => $totalHadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpha' => $alpha,
                'persentase' => $totalStudents > 0 ? round(($totalHadir / $totalStudents) * 100) : 0,
            ];
        });
    }
}