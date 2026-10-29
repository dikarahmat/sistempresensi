<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Services\DownloadCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use App\Exports\StudentTemplateExport;

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
        $totalStudents = Student::count();

        $view = request()->routeIs('guru.*') ? 'guru.students.index' : 'admin.students.index';
        return view($view, compact('students', 'classes', 'totalStudents'));
    }

    public function show(Student $student): View
    {
        $student->load('schoolClass');
        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.students.show', compact('student', 'classes'));
    }

    public function create(): View
    {
        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.students.create', compact('classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'name' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'digits:10', 'unique:students,nisn'],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
        ], [
            'nisn.required' => __('NISN harus 10 digit angka.'),
            'nisn.digits' => __('NISN harus 10 digit angka.'),
            'nisn.unique' => __('NISN sudah terdaftar.'),
        ]);

        $validated['qr_token'] = (string) Str::uuid();
        $validated['status'] = 'Aktif';

        Student::create($validated);

        return redirect()->route('admin.students.index')
            ->with('success', __('Data siswa berhasil ditambahkan!'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'school_class_id' => ['required', 'exists:school_classes,id'],
            'name' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'digits:10', 'unique:students,nisn,' . $student->id],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
        ], [
            'nisn.required' => __('NISN harus 10 digit angka.'),
            'nisn.digits' => __('NISN harus 10 digit angka.'),
            'nisn.unique' => __('NISN sudah terdaftar.'),
        ]);

        $student->update($validated);
        $student->load('schoolClass');

        if ($request->expectsJson() || $request->ajax()) {
            $token = $student->nisn ?: $student->qr_token;
            $qrSrc = null;
            if ($token) {
                try {
                    $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->margin(0)->generate((string) $token);
                    $qrSrc = 'data:image/svg+xml;base64,' . base64_encode($svg);
                } catch (\Throwable $e) {}
            }

            return response()->json([
                'status' => 'success',
                'message' => __('Data siswa berhasil diperbarui!'),
                'data' => [
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'school_class_id' => $student->school_class_id,
                    'school_class_name' => $student->schoolClass->name ?? '-',
                    'gender' => $student->gender,
                    'qr_src' => $qrSrc,
                ],
            ]);
        }

        return redirect()->route('admin.students.show', $student->id)
            ->with('success', __('Data siswa berhasil diperbarui!'));
    }

    public function destroy(Student $student): RedirectResponse
    {
        $studentName = $student->name;
        $student->delete();

        return redirect()->route('admin.students.index')
            ->with('danger', __('Data siswa :name berhasil dipindahkan ke Tempat Sampah.', ['name' => $studentName]));
    }

    public function downloadCard(Student $student)
    {
        return DownloadCacheService::downloadSingleCard($student);
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

    /** Hapus SELURUH siswa AKTIF menjadi soft delete (masuk Tempat Sampah). */
    public function destroyAllActive(Request $request): RedirectResponse
    {
        if ($request->boolean('confirm_permanent')) {
            return $this->destroyAllTrashed($request);
        }

        try {
            $active = Student::whereNull('deleted_at')->count();

            if ($active === 0) {
                return redirect()->route('admin.students.index')
                    ->with('error', __('Tidak ada data siswa aktif yang bisa dihapus.'));
            }

            $deleted = 0;
            Student::whereNull('deleted_at')->each(function (Student $s) use (&$deleted) {
                $s->delete();
                $deleted++;
            });

            return redirect()->route('admin.students.index')
                ->with('danger', "$deleted data siswa dipindahkan ke Tempat Sampah.");
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('admin.students.index')
                ->with('error', __('Gagal menghapus data. Coba lagi.'));
        }
    }

    public function trash(): View
    {
        $query = Student::onlyTrashed()->with('schoolClass');

        $students = $query->orderBy('deleted_at', 'desc')->paginate(50);
        $classes = SchoolClass::orderBy('name')->get();

        $trashedCount = Student::onlyTrashed()->count();

        return view('admin.students.trash', compact('students', 'classes', 'trashedCount'));
    }

    public function restore($id): RedirectResponse
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        $student->restore();

        return redirect()->route('admin.students.trash')
            ->with('success', __('Data siswa :name berhasil dipulihkan!', ['name' => $student->name]));
    }

    public function forceDelete($id): RedirectResponse
    {
        $student = Student::onlyTrashed()->findOrFail($id);

        DB::beginTransaction();

        try {
            SchoolClass::where('teacher_id', $student->id)->update(['teacher_id' => null]);
            $student->attendances()->delete();

            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }

            $student->forceDelete();
            DB::commit();

            return redirect()->route('admin.students.trash')
                ->with('deleted', __('Data siswa :name berhasil dihapus permanen!', ['name' => $student->name]));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->route('admin.students.trash')
                ->with('error', __('Gagal menghapus permanen data siswa. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /** Hapus SELURUH data siswa di Tempat Sampah (permanen). */
    public function destroyAllTrashed(Request $request): RedirectResponse
    {
        $count = Student::onlyTrashed()->count();

        if ($count === 0) {
            return redirect()->route('admin.students.trash')
                ->with('info', __('Tempat sampah sudah kosong.'));
        }

        DB::beginTransaction();

        try {
            $trashed = Student::onlyTrashed();
            $deleted = 0;
            $trashed->each(function (Student $s) use (&$deleted) {
                SchoolClass::where('teacher_id', $s->id)->update(['teacher_id' => null]);
                $s->attendances()->delete();

                if ($s->photo && Storage::disk('public')->exists($s->photo)) {
                    Storage::disk('public')->delete($s->photo);
                }

                $s->forceDelete();
                $deleted++;
            });

            DB::commit();

            return redirect()->route('admin.students.trash')
                ->with('deleted', "$deleted siswa dihapus permanen.");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->route('admin.students.trash')
                ->with('error', __('Gagal menghapus permanen data siswa. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Import data siswa dari file Excel.
     * Selalu balas JSON {status, message}; HTTP 200 kalau sukses/partial, 422 kalau ada kesalahan.
     */
    public function import(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ], [
                'file_excel.required' => 'Pilih file Excel data siswa yang akan diunggah.',
                'file_excel.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
                'file_excel.max' => 'Ukuran file maksimal 10 MB.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $import = new StudentsImport();
            Excel::import($import, $request->file('file_excel'));

            $imported = $import->getImportedCount();
            $orderedErrors = $import->getOrderedErrors();
            $totalErrors = count($orderedErrors);

            if ($imported > 0 && $totalErrors === 0) {
                $message = "{$imported} data berhasil diimpor.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => $message,
                    ]);
                }
                return redirect()->route('admin.students.index')
                    ->with('success', $message);
            }

            if ($totalErrors === 0 && $imported === 0) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Tidak ada data yang diimpor.',
                    ], 422);
                }
                return redirect()->route('admin.students.index')
                    ->with('warning', 'Tidak ada data yang diimpor.');
            }

            // Sebagian berhasil, sebagian gagal
            $errorGroups = [];
            foreach ($orderedErrors as [$row, $reason]) {
                $reason = trim((string) $reason);
                $reason = preg_replace('/^[Bb]aris\s+\d+\s*[:,.]?\s*/u', '', $reason);
                $reason = rtrim($reason, '.: ');
                $reason = preg_replace('/[\.\?\!]+$/', '', $reason);
                if ($reason === '') {
                    $reason = 'Data tidak valid.';
                }
                $errorGroups[$reason][] = $row;
            }

            uksort($errorGroups, function ($a, $b) use ($errorGroups) {
                return array_search($a, array_keys($errorGroups)) <=> array_search($b, array_keys($errorGroups));
            });

            $details = [];
            foreach ($errorGroups as $reason => $rows) {
                sort($rows);
                $rowsList = implode(', ', $rows);
                $details[] = "{$reason} (baris {$rowsList})";
            }

            $maxDetails = 5;
            $shownDetails = array_slice($details, 0, $maxDetails);
            $extraCount = count($details) - $maxDetails;
            if ($extraCount > 0) {
                $shownDetails[] = "Dan {$extraCount} alasan lainnya.";
            }

            $summary = "{$imported} berhasil, {$totalErrors} gagal.";

            $payload = [
                'status' => 'partial',
                'message' => $summary,
            ];
            if (!empty($shownDetails)) {
                $payload['details'] = $shownDetails;
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($payload, 200);
            }
            $flashMessage = $summary;
            if (!empty($shownDetails)) {
                $flashMessage .= ' ' . implode('; ', $shownDetails);
            }
            return redirect()->route('admin.students.index')
                ->with('warning', $flashMessage);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }
            return redirect()->route('admin.students.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Unduh template Excel import data siswa.
     */
    public function downloadTemplate()
    {
        return Excel::download(new StudentTemplateExport(), 'Template_Data_Siswa.xlsx');
    }
}
