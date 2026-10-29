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
use Illuminate\Http\JsonResponse;
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

    /** Hapus SELURUH siswa AKTIF menjadi soft delete (masuk Tempat Sampah). */
    public function destroyAllActive(Request $request): RedirectResponse
    {
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
                ->with('success', "$deleted data siswa dipindahkan ke Tempat Sampah.");
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

    /**
     * Import data siswa dari file Excel.
     * Selalu balas JSON {status, message}; HTTP 200 kalau sukses/partial, 422 kalau ada kesalahan.
     */
    public function import(Request $request): JsonResponse
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
                return response()->json([
                    'status' => 'success',
                    'message' => "{$imported} data berhasil diimpor.",
                ]);
            }

            if ($totalErrors === 0 && $imported === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada data yang diimpor.',
                ], 422);
            }

            // Sebagian berhasil, sebagian gagal
            $max = 5;
            $shown = array_slice($orderedErrors, 0, $max);
            $parts = [];
            foreach ($shown as [$row, $reason]) {
                $parts[] = "baris {$row}: {$reason}";
            }
            $message = "{$imported} berhasil, {$totalErrors} gagal: " . implode('; ', $parts);
            $remaining = $totalErrors - $max;
            if ($remaining > 0) {
                $message .= "; ... dan {$remaining} baris lainnya.";
            }

            return response()->json([
                'status' => 'partial',
                'message' => $message,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
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
