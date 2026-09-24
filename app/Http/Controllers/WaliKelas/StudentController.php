<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentController extends Controller
{
    /**
     * Dapatkan Guru dan Kelas yang dibina oleh user login.
     * Menggunakan foreign key dan relasi yang valid.
     */
    protected function getTeacherAndClass(?Request $request = null): array
    {
        $user = Auth::user();
        if (!$user) {
            return [null, null, null, collect()];
        }

        // 1. Dapatkan objek Teacher berdasarkan user_id login
        $teacher = Teacher::where('user_id', $user->id)->first();

        // Fallback: Jika admin belum menautkan user_id ke teacher, cocokkan via nama
        if (!$teacher && !empty($user->name)) {
            $teacher = Teacher::where('name', $user->name)
                ->orWhere('name', 'like', '%' . trim($user->name) . '%')
                ->first();

            if ($teacher && empty($teacher->user_id)) {
                $teacher->update(['user_id' => $user->id]);
            }
        }

        // Kumpulkan semua teacher ID yang berkaitan dengan user ini
        $teacherIds = collect();
        if ($teacher) {
            $teacherIds->push($teacher->id);
        }
        $sameNameTeachers = Teacher::where('name', $user->name)->pluck('id');
        $teacherIds = $teacherIds->merge($sameNameTeachers)->unique();

        $activeYear = AcademicYear::getActive();

        // 2. Ambil seluruh kelas yang diampu / dibina oleh guru ini
        $teacherClasses = collect();
        if ($teacherIds->isNotEmpty()) {
            $teacherClasses = SchoolClass::whereIn('teacher_id', $teacherIds)
                ->with(['academicYear', 'teacher'])
                ->orderBy('name')
                ->get();
        }

        $schoolClass = null;

        // Cek jika ada filter class_id atau school_class_id yang dikirim dari form/query string
        $requestedClassId = $request 
            ? ($request->input('school_class_id') ?? $request->input('class_id')) 
            : (request('school_class_id') ?? request('class_id'));

        if ($requestedClassId) {
            $schoolClass = $teacherClasses->firstWhere('id', $requestedClassId);
            if (!$schoolClass) {
                abort(403, 'Akses ditolak: Anda tidak memiliki akses ke kelas ini.');
            }
        }

        // Jika belum ada kelas yang dipilih:
        // Prioritas 1: Kelas binaan di tahun ajaran aktif
        if (!$schoolClass && $teacherClasses->isNotEmpty()) {
            if ($activeYear) {
                $schoolClass = $teacherClasses->firstWhere('academic_year_id', $activeYear->id);
            }
            // Prioritas 2: Kelas yang memiliki data siswa
            if (!$schoolClass) {
                $schoolClass = $teacherClasses->first(function ($c) {
                    return Student::where('school_class_id', $c->id)->exists();
                });
            }
            // Prioritas 3: Kelas pertama yang terdaftar
            if (!$schoolClass) {
                $schoolClass = $teacherClasses->first();
            }
        }

        // Fallback terakhir: Ambil sembarang kelas yang ditugaskan ke guru
        if (!$schoolClass && $teacher) {
            $schoolClass = SchoolClass::where('teacher_id', $teacher->id)->first();
        }

        return [$teacher, $schoolClass, $activeYear, $teacherClasses];
    }

    /**
     * Tampilan Read-Only Daftar Siswa Kelas Binaan Guru.
     * Mengambil 100% data siswa tanpa memfilter status secara sepihak agar sinkron sempurna dengan Admin.
     */
    public function index(Request $request): View
    {
        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClass($request);

        $students = Student::whereRaw('1=0')->paginate(50);
        if ($schoolClass) {
            // Gunakan foreign key yang valid: school_class_id
            // Eager load relasi schoolClass agar relasi kelas terbaca instan
            $query = Student::where('school_class_id', $schoolClass->id)
                ->with('schoolClass');

            // Pencarian nama, NIS, atau NISN (case insensitive dan bersih spasi)
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            }

            // Urutkan berdasarkan nama siswa, sama persis seperti di Admin
            $students = $query->orderBy('name', 'asc')->paginate(50)->withQueryString();
        }

        $classes = $teacherClasses;

        return view('walikelas.students.index', compact('teacher', 'schoolClass', 'activeYear', 'teacherClasses', 'classes', 'students'));
    }

    /**
     * Unduh Kartu Pelajar / Presensi Perorangan (PDF).
     */
    public function downloadCard(Student $student)
    {
        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClass();

        // Validasi: periksa apakah siswa berada di kelas binaan guru ini
        $isTeacherStudent = false;
        if ($schoolClass && $student->school_class_id === $schoolClass->id) {
            $isTeacherStudent = true;
        } elseif ($teacherClasses->contains('id', $student->school_class_id)) {
            $isTeacherStudent = true;
        }

        if (!$isTeacherStudent) {
            abort(403, 'Akses ditolak! Siswa ini bukan bagian dari kelas binaan Anda.');
        }

        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        // Pre-generate logo
        $logoPath = public_path(Setting::getLogo());
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        // QR Code & Foto
        $token = $student->qr_token ?? $student->nis;
        $svg = QrCode::size(140)->margin(0)->generate($token);
        $student->qr_base64 = 'data:image/svg+xml;base64,' . base64_encode($svg);

        $student->photo_base64 = null;
        if ($student->photo && file_exists(public_path('storage/' . $student->photo))) {
            $pPath = public_path('storage/' . $student->photo);
            $pMime = mime_content_type($pPath) ?: 'image/jpeg';
            $student->photo_base64 = 'data:' . $pMime . ';base64,' . base64_encode(file_get_contents($pPath));
        }

        $student->load('schoolClass');
        $siswa = $student;
        $students = collect([$student]);
        $tahun_ajaran = $activeYear;
        $pengaturan = Setting::first();
        $setting = $pengaturan;

        if (request()->has('download') || request()->boolean('pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.students.print-card', array_merge(compact(
                'student',
                'siswa',
                'students',
                'schoolName',
                'schoolAddress',
                'activeYear',
                'tahun_ajaran',
                'logoBase64',
                'setting',
                'pengaturan',
                'schoolClass'
            ), ['isPdf' => true]))->setPaper('a4', 'portrait');

            return $pdf->download('Kartu_Presensi_' . $student->nis . '_' . \Illuminate\Support\Str::slug($student->name) . '.pdf');
        }

        return view('admin.students.print-card', compact(
            'student',
            'siswa',
            'students',
            'schoolName',
            'schoolAddress',
            'activeYear',
            'tahun_ajaran',
            'logoBase64',
            'setting',
            'pengaturan',
            'schoolClass'
        ));
    }

    /**
     * Detail Siswa Kelas Binaan Guru.
     */
    public function show(Student $student): View
    {
        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClass();

        $isTeacherStudent = false;
        if ($schoolClass && $student->school_class_id === $schoolClass->id) {
            $isTeacherStudent = true;
        } elseif ($teacherClasses->contains('id', $student->school_class_id)) {
            $isTeacherStudent = true;
        }

        if (!$isTeacherStudent) {
            abort(403, 'Akses ditolak! Siswa ini bukan bagian dari kelas binaan Anda.');
        }

        $student->load('schoolClass');

        return view('walikelas.students.show', compact('student', 'teacher', 'schoolClass', 'activeYear', 'teacherClasses'));
    }

    /**
     * Download QR Code Siswa.
     */
    public function downloadQr(Student $student)
    {
        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClass();

        $isTeacherStudent = false;
        if ($schoolClass && $student->school_class_id === $schoolClass->id) {
            $isTeacherStudent = true;
        } elseif ($teacherClasses->contains('id', $student->school_class_id)) {
            $isTeacherStudent = true;
        }

        if (!$isTeacherStudent) {
            abort(403, 'Akses ditolak! Siswa ini bukan bagian dari kelas binaan Anda.');
        }

        $token = $student->qr_token ?? $student->nis;
        $fileName = 'QR_' . $student->nis . '_' . Str::slug($student->name) . '.svg';

        $qrCode = QrCode::size(500)
            ->format('svg')
            ->margin(1)
            ->generate($token);

        return response($qrCode)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    /**
     * Cetak Kartu Presensi / Pelajar Khusus Kelas Binaan Wali Kelas (Strictly Isolated).
     */
    public function printCards(Request $request)
    {
        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClass($request);

        $allowedClassIds = $teacherClasses->pluck('id')->toArray();
        if ($schoolClass) {
            $allowedClassIds = array_unique(array_merge($allowedClassIds, [$schoolClass->id]));
        }

        if (empty($allowedClassIds)) {
            return back()->with('error', 'Anda belum memiliki kelas yang dibina.');
        }

        // ISOLASI KETAT: Hanya mengambil siswa dari kelas binaan wali kelas yang sedang login
        $query = Student::whereIn('school_class_id', $allowedClassIds)
            ->with('schoolClass');

        // Jika filter per kelas spesifik dipilih
        if ($request->input('print_type') === 'class' && $request->filled('class_id')) {
            if (in_array((int)$request->class_id, $allowedClassIds)) {
                $query->where('school_class_id', $request->class_id);
                $schoolClass = $teacherClasses->firstWhere('id', $request->class_id) ?? $schoolClass;
            }
        } elseif ($request->filled('school_class_id')) {
            if (in_array((int)$request->school_class_id, $allowedClassIds)) {
                $query->where('school_class_id', $request->school_class_id);
                $schoolClass = $teacherClasses->firstWhere('id', $request->school_class_id) ?? $schoolClass;
            }
        }

        // Jika ada filter checkbox siswa terpilih
        if ($request->filled('student_ids') && is_array($request->student_ids)) {
            $query->whereIn('id', $request->student_ids);
        }

        $students = $query->orderBy('name', 'asc')->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ada data siswa yang ditemukan untuk dicetak pada kelas ini.');
        }

        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        // Pre-generate logo
        $logoPath = public_path(Setting::getLogo());
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        // QR Code & Foto masing-masing siswa
        foreach ($students as $student) {
            $token = $student->qr_token ?? $student->nis;
            $svg = QrCode::size(140)->margin(0)->generate($token);
            $student->qr_base64 = 'data:image/svg+xml;base64,' . base64_encode($svg);

            $student->photo_base64 = null;
            if ($student->photo && file_exists(public_path('storage/' . $student->photo))) {
                $pPath = public_path('storage/' . $student->photo);
                $pMime = mime_content_type($pPath) ?: 'image/jpeg';
                $student->photo_base64 = 'data:' . $pMime . ';base64,' . base64_encode(file_get_contents($pPath));
            }
        }

        return view('shared.print-cards', compact(
            'students',
            'schoolName',
            'schoolAddress',
            'activeYear',
            'logoBase64',
            'schoolClass'
        ));
    }

    /**
     * Alias backward compatibility untuk cetak massal
     */
    public function printMassCards(Request $request)
    {
        return $this->printCards($request);
    }
}

