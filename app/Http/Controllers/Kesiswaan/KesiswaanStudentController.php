<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class KesiswaanStudentController extends Controller
{
    /**
     * Tampilkan daftar seluruh siswa lintas kelas (Strict Read-Only).
     * 100% Identik dengan tabel dan data Admin.
     */
    public function index(Request $request): View
    {
        $query = Student::with(['schoolClass.teacher']);

        // 1. Pencarian berdasarkan Nama, NIS, atau NISN
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        // 2. Filter berdasarkan Kelas
        if ($request->filled('class_id')) {
            $query->where('school_class_id', $request->input('class_id'));
        }

        // 3. Filter berdasarkan Jenis Kelamin
        if ($request->filled('gender')) {
            $query->where('gender', $request->input('gender'));
        }

        // 4. Filter berdasarkan Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Urutkan berdasarkan nama siswa
        $students = $query->orderBy('name', 'asc')->paginate(50)->withQueryString();

        // Data Kelas untuk dropdown filter
        $classes = SchoolClass::orderBy('name')->get();

        // Hitung total ringkasan
        $totalAllStudents = Student::count();
        $totalActiveStudents = Student::where('status', 'Aktif')->count();

        return view('kesiswaan.students.index', compact(
            'students',
            'classes',
            'totalAllStudents',
            'totalActiveStudents'
        ));
    }

    /**
     * Detail lengkap siswa (View atau JSON untuk modal pop-up).
     */
    public function show(Request $request, Student|int $student)
    {
        if (!($student instanceof Student)) {
            $student = Student::with(['schoolClass.teacher'])->findOrFail($student);
        } else {
            $student->load(['schoolClass.teacher']);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn ?? '-',
                    'gender' => $student->gender,
                    'birth_place' => $student->birth_place ?? '-',
                    'birth_date' => $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->translatedFormat('d F Y') : '-',
                    'address' => $student->address ?? '-',
                    'parent_name' => $student->parent_name ?? '-',
                    'parent_phone' => $student->parent_phone ?? '-',
                    'class_name' => $student->schoolClass?->name ?? 'Belum Ditentukan',
                    'wali_kelas' => $student->schoolClass?->teacher?->name ?? 'Belum Ditentukan',
                    'nip_wali' => $student->schoolClass?->teacher?->nip ?? '-',
                    'status' => $student->status,
                    'photo_url' => $student->photo ? asset('storage/' . $student->photo) : null,
                ],
            ]);
        }

        return view('kesiswaan.students.show', compact('student'));
    }

    /**
     * Download QR Code Siswa (SVG).
     */
    public function downloadQr(Student $student)
    {
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
     * Unduh Kartu Pelajar / Presensi Perorangan (PDF).
     */
    public function downloadCard(Student $student)
    {
        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        $logoPath = public_path(Setting::getLogo());
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

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
        $schoolClass = $student->schoolClass;

        if (request()->has('download') || request()->boolean('pdf')) {
            $pdf = Pdf::loadView('admin.students.print-card', array_merge(compact(
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

            return $pdf->download('Kartu_Presensi_' . $student->nis . '_' . Str::slug($student->name) . '.pdf');
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
     * Cetak Kartu Presensi Massal (Read-Only).
     */
    public function printCards(Request $request)
    {
        $query = Student::where('status', 'Aktif')->with('schoolClass');

        if ($request->input('print_type') === 'class' && $request->filled('class_id')) {
            $query->where('school_class_id', $request->class_id);
        } elseif ($request->filled('class_id')) {
            $query->where('school_class_id', $request->class_id);
        }

        if ($request->filled('student_ids') && is_array($request->student_ids)) {
            $query->whereIn('id', $request->student_ids);
        }

        $students = $query->orderBy('name', 'asc')->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ada data siswa yang ditemukan untuk dicetak.');
        }

        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        $logoPath = public_path(Setting::getLogo());
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

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
            'logoBase64'
        ));
    }
}
