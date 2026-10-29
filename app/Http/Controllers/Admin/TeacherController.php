<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TeacherTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\TeachersImport;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Teacher;
use App\Services\DownloadCacheService;
use App\Services\TeacherCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $query = Teacher::with('schoolClass');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $teachers = $query->orderBy('name')->paginate(20)->withQueryString();
        $classes = SchoolClass::orderBy('name')->get();
        $totalTeachers = Teacher::count();

        return view('admin.teachers.index', compact('teachers', 'classes', 'totalTeachers'));
    }

    public function show(Teacher $teacher): View
    {
        $teacher->load('schoolClass');

        return view('admin.teachers.show', compact('teacher'));
    }

    public function downloadCard(Teacher $teacher)
    {
        return TeacherCardService::downloadSingleCardJpg($teacher);
    }

    public function printCards(Request $request)
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '120');

        return TeacherCardService::downloadMassCards($request);
    }

    public function store(Request $request): RedirectResponse
    {
        // ... maintained unchanged ...
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        // ... maintained unchanged ...
    }

    private function syncTugasKelas(Teacher $teacher, ?int $classId): void
    {
        // ... maintained unchanged ...
    }

    public function destroy(Request $request, Teacher $teacher): RedirectResponse
    {
        // ... maintained unchanged ...
    }

    public function destroyAll(): RedirectResponse
    {
        // ... maintained unchanged ...
    }

    public function destroyAllActive(Request $request): RedirectResponse
    {
        // ... maintained unchanged ...
    }

    public function downloadTemplate()
    {
        return DownloadCacheService::downloadTemplate(
            'teachers',
            new TeacherTemplateExport,
            'Template_Guru.xlsx'
        );
    }

    public function import(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            ], [
                'file_excel.required' => 'Pilih file Excel yang akan diunggah.',
                'file_excel.file' => 'File tidak valid. Silakan pilih ulang file Excel.',
                'file_excel.mimes' => 'Format file harus berekstensi .xlsx, .xls, atau .csv.',
                'file_excel.max' => 'Ukuran file maksimal 5 MB.',
            ], [
                'file_excel' => 'file Excel',
            ]);
        } catch (ValidationException $e) {
            return $this->formErrorRedirect($e, 'import');
        }

        try {
            $import = new TeachersImport;
            Excel::import($import, $request->file('file_excel'));

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $errors = $import->getErrors();

            if ($imported > 0 && $skipped === 0) {
                $status = 'success';
                $message = "Import berhasil: {$imported} guru diimpor.";
            } elseif ($imported > 0) {
                $status = 'warning';
                $message = "Import sebagian berhasil: {$imported} diimpor, {$skipped} dilewati.";
            } else {
                $status = 'danger';
                $message = "Import gagal: tidak ada data yang diimpor. {$skipped} baris dilewati. Periksa format file Excel dan alasan di bawah.";
            }

            $redirect = redirect()->route('admin.guru.index')
                ->with('import_status', $status)
                ->with('import_message', $message);

            if (! empty($errors)) {
                $redirect->with('import_errors', $errors);
            }

            return $redirect;
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.guru.index')
                ->with('error', __('Gagal memproses file Excel. Silakan coba lagi atau hubungi admin.'));
        }
    }

    private function teacherRules(?Teacher $teacher = null): array
    {
        // ... maintained unchanged ...
    }

    private function teacherMessages(): array
    {
        // ... maintained unchanged ...
    }

    private function teacherAttributes(): array
    {
        // ... maintained unchanged ...
    }

    private function formErrorRedirect(ValidationException $e, string $modal, ?int $teacherId = null): RedirectResponse
    {
        // ... maintained unchanged ...
    }

    public function trash(): View
    {
        // ... maintained unchanged ...
    }

    public function restore($id): RedirectResponse
    {
        // ... maintained unchanged ...
    }

    public function forceDelete($id): RedirectResponse
    {
        // ... maintained unchanged ...
    }

    public function destroyAllTrashed(): RedirectResponse
    {
        // ... maintained unchanged ...
    }
}
