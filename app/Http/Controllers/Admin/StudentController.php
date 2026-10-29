<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Services\DownloadCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::with('schoolClass');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->where('school_class_id', $request->class_id);
        }

        $students = $query->orderBy('name', 'asc')->paginate(100)->withQueryString();
        $classes = SchoolClass::orderBy('name')->get();

        return view('guru.students.index', compact('students', 'classes'));
    }

    public function show(Student $student): View
    {
        $student->load('schoolClass');
        return view('guru.students.show', compact('student'));
    }

    public function printCards(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        $query = Student::with('schoolClass')->where('status', 'Aktif');

        if ($request->filled('student_ids') && is_array($request->student_ids)) {
            $validatedIds = array_filter(array_map('intval', $request->student_ids));
            if (!empty($validatedIds)) {
                $query->whereIn('id', $validatedIds);
            }
        } elseif (!$request->boolean('all') && $request->input('print_type') !== 'all' && $request->filled('class_id') && $request->class_id !== 'all') {
            $query->where('school_class_id', $request->class_id);
        }

        $logoPath = public_path(Setting::getLogo());
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $students = collect();
        $query->orderBy('school_class_id')->orderBy('name')->chunk(100, function ($chunk) use ($students) {
            foreach ($chunk as $student) {
                $token = $student->nisn ?: $student->qr_token;
                try {
                    $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(140)->margin(0)->generate($token);
                    $student->qr_base64 = 'data:image/svg+xml;base64,' . base64_encode($svg);
                } catch (\Throwable $e) {
                    $student->qr_base64 = null;
                }

                $student->photo_base64 = null;
                if ($student->photo && file_exists(public_path('storage/' . $student->photo))) {
                    $pPath = public_path('storage/' . $student->photo);
                    $pMime = mime_content_type($pPath) ?: 'image/jpeg';
                    $student->photo_base64 = 'data:' . $pMime . ';base64,' . base64_encode(file_get_contents($pPath));
                }

                $students->push($student);
            }
        });

        if ($students->isEmpty()) {
            return back()->with('error', __('Tidak ada data siswa yang dipilih atau ditemukan untuk dicetak.'));
        }

        if ($request->isMethod('get') && !$request->has('download')) {
            return view('shared.print-cards', compact(
                'students',
                'schoolName',
                'schoolAddress',
                'activeYear',
                'logoBase64'
            ));
        }

        return DownloadCacheService::downloadMassCards($request);
    }

    public function printCard(Request $request, $id = null): View
    {
        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        if ($id) {
            $students = Student::with('schoolClass')->where('id', $id)->get();
        } elseif ($request->filled('class_id')) {
            $students = Student::with('schoolClass')->where('school_class_id', $request->class_id)->get();
        } else {
            $students = Student::with('schoolClass')->get();
        }

        $logoPath = public_path(Setting::getLogo());
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        foreach ($students as $student) {
            $token = $student->nisn ?: $student->qr_token;
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(140)->margin(0)->generate($token);
            $student->qr_base64 = 'data:image/svg+xml;base64,' . base64_encode($svg);

            $student->photo_base64 = null;
            if ($student->photo && file_exists(public_path('storage/' . $student->photo))) {
                $pPath = public_path('storage/' . $student->photo);
                $pMime = mime_content_type($pPath) ?: 'image/jpeg';
                $student->photo_base64 = 'data:' . $pMime . ';base64,' . base64_encode(file_get_contents($pPath));
            }
        }

        $tahun_ajaran = $activeYear;
        $pengaturan = Setting::first();
        $setting = $pengaturan;

        if ($id) {
            $student = $students->first();

            return view('guru.students.print-card', compact(
                'student',
                'students',
                'schoolName',
                'schoolAddress',
                'activeYear',
                'tahun_ajaran',
                'logoBase64',
                'pengaturan',
                'setting'
            ));
        }

        return view('shared.print-cards', compact(
            'students',
            'schoolName',
            'schoolAddress',
            'activeYear',
            'tahun_ajaran',
            'logoBase64',
            'pengaturan',
            'setting'
        ));
    }
}
