<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::with('schoolClass');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->where('school_class_id', $request->class_id);
        }

        $students = $query->orderBy('name', 'asc')->paginate(50)->withQueryString();
        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.students.index', compact('students', 'classes'));
    }

    public function create(): View
    {
        $classes = SchoolClass::orderBy('name')->get();
        return view('admin.students.create', compact('classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'name' => 'required|string|max:255',
            'nis' => 'required|string|max:30|unique:students,nis',
            'nisn' => 'nullable|string|max:30|unique:students,nisn',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('students', 'public');
        }

        $validated['qr_token'] = (string) Str::uuid();
        $validated['status'] = 'Aktif';

        Student::create($validated);

        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil ditambahkan!');
    }

    public function show(Student $student): View
    {
        $student->load('schoolClass');
        return view('admin.students.show', compact('student'));
    }

    public function edit(Student $student): View
    {
        $classes = SchoolClass::orderBy('name')->get();
        return view('admin.students.edit', compact('student', 'classes'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'school_class_id' => 'required|exists:school_classes,id',
            'name' => 'required|string|max:255',
            'nis' => 'required|string|max:30|unique:students,nis,' . $student->id,
            'nisn' => 'nullable|string|max:30|unique:students,nisn,' . $student->id,
            'gender' => 'required|in:Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }
            $validated['photo'] = $request->file('photo')->store('students', 'public');
        }

        if (empty($student->qr_token)) {
            $validated['qr_token'] = (string) Str::uuid();
        }

        $student->update($validated);

        return redirect()->route('admin.students.show', $student->id)->with('success', 'Data siswa berhasil diperbarui!');
    }

    /**
     * Cetak Kartu Presensi / Pelajar Massal (PDF).
     */
    public function printCards(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        $query = Student::with('schoolClass')->where('status', 'Aktif');

        if ($request->filled('student_ids') && is_array($request->student_ids)) {
            $query->whereIn('id', $request->student_ids);
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
                $token = $student->qr_token ?? $student->nis;
                try {
                    $svg = QrCode::size(140)->margin(0)->generate($token);
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
            return back()->with('error', 'Tidak ada data siswa yang dipilih atau ditemukan untuk dicetak.');
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

        $pdf = Pdf::loadView('shared.print-cards', array_merge(compact(
            'students',
            'schoolName',
            'schoolAddress',
            'activeYear',
            'logoBase64'
        ), ['isPdf' => true]))->setPaper('a4', 'portrait');

        $fileName = 'Kartu_Presensi_Massal_' . date('Ymd_His') . '.pdf';
        return $pdf->download($fileName);
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

        $tahun_ajaran = $activeYear;
        $pengaturan = Setting::first();
        $setting = $pengaturan;

        if ($id) {
            $student = $students->first();
            $siswa = $student;

            return view('admin.students.print-card', compact(
                'student',
                'siswa',
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

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $import = new \App\Imports\StudentsImport();
            Excel::import($import, $request->file('file_excel'));

            $count = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $errors = $import->getErrors();

            $msg = "Berhasil memproses Excel: {$count} data siswa berhasil diimpor/diperbarui.";
            if ($skipped > 0) {
                $msg .= " ({$skipped} baris dilewati karena format tidak sesuai).";
            }

            $redirect = redirect()->route('admin.students.index')->with('success', $msg);
            if (!empty($errors)) {
                $redirect->with('import_errors', $errors);
            }

            return $redirect;
        } catch (\Throwable $e) {
            return redirect()->route('admin.students.index')
                ->with('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(
            new \App\Exports\StudentTemplateExport(),
            'Template_Siswa.xlsx'
        );
    }

    public function downloadCard(Student $student)
    {
        $activeYear = AcademicYear::getActive();
        $tahun_ajaran = $activeYear;
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();
        $pengaturan = Setting::first();
        $setting = $pengaturan;
        $student->load('schoolClass');
        $siswa = $student;

        $logoPath = public_path(Setting::getLogo());
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $token = $student->qr_token ?? $student->nis;
        try {
            $svg = QrCode::size(140)->margin(0)->generate($token);
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

        $students = collect([$student]);

        $pdf = Pdf::loadView('admin.students.print-card', array_merge(compact(
            'student',
            'siswa',
            'students',
            'schoolName',
            'schoolAddress',
            'activeYear',
            'tahun_ajaran',
            'logoBase64',
            'pengaturan',
            'setting'
        ), ['isPdf' => true]))->setPaper('a4', 'portrait');

        $fileName = 'Kartu_Presensi_' . $student->nis . '_' . Str::slug($student->name) . '.pdf';
        return $pdf->download($fileName);
    }

    public function destroy(Student $student): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $studentName = $student->name;

            // Hapus file foto dari storage jika ada
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }

            // Hapus seluruh riwayat presensi terkait untuk menjaga integritas database
            $student->attendances()->delete();

            // Hapus data siswa
            $student->delete();

            DB::commit();
            return redirect()->route('admin.students.index')
                ->with('success', "Data siswa {$studentName} beserta riwayat presensinya berhasil dihapus!");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.students.index')
                ->with('error', 'Gagal menghapus siswa: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus seluruh data siswa secara massal dengan proteksi transaksi.
     */
    public function destroyAll(): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // Hapus seluruh file foto dari disk public storage secara terchunk
            Student::whereNotNull('photo')->chunkById(200, function ($students) {
                foreach ($students as $student) {
                    if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                        Storage::disk('public')->delete($student->photo);
                    }
                }
            });

            // Hapus seluruh riwayat presensi terlebih dahulu guna mencegah foreign key constraint violation
            \App\Models\Attendance::query()->delete();

            // Hapus seluruh data siswa
            Student::query()->delete();

            DB::commit();
            return redirect()->route('admin.students.index')->with('success', 'Seluruh data siswa beserta riwayat presensi berhasil dibersihkan!');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.students.index')->with('error', 'Gagal menghapus semua data siswa: ' . $e->getMessage());
        }
    }
}