<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Exports\SchoolClassTemplateExport;
use App\Imports\SchoolClassesImport;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class SchoolClassController extends Controller
{
    public function index(Request $request): View
    {
        $query = SchoolClass::with(['teacher', 'academicYear'])->withCount('students');

        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $classes = $query->orderBy('name')->paginate(20)->withQueryString();
        $teachers = Teacher::orderBy('name')->get();

        return view('admin.classes.index', compact('classes', 'teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:school_classes,name',
            'grade' => 'nullable|string|max:10',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        $activeYear = \App\Models\AcademicYear::getActive();

        SchoolClass::create([
            'name' => $validated['name'],
            'grade' => $validated['grade'] ?? null,
            'level' => match($validated['grade'] ?? null) {
                '7' => 'VII',
                '8' => 'VIII',
                '9' => 'IX',
                default => 'VII',
            },
            'academic_year_id' => $activeYear?->id,
            'teacher_id' => !empty($validated['teacher_id']) ? $validated['teacher_id'] : null,
        ]);

        return redirect()->route('admin.classes.index')->with('success', 'Data kelas berhasil ditambahkan!');
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse
    {
        // Fallback jika route model binding belum ter-resolve
        if (!$class->exists) {
            $classId = $request->route('class') ?? $request->route('school_class') ?? $request->route('schoolClass');
            if ($classId) {
                $class = SchoolClass::findOrFail($classId);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:school_classes,name,' . $class->id,
            'grade' => 'nullable|string|max:10',
            'teacher_id' => 'nullable|exists:teachers,id',
        ]);

        // Eksekusi update langsung secara eksplisit
        $class->name = $validated['name'];
        $class->grade = $validated['grade'] ?? null;
        if (!empty($validated['grade'])) {
            $class->level = match($validated['grade']) {
                '7' => 'VII',
                '8' => 'VIII',
                '9' => 'IX',
                default => $class->level ?? 'VII',
            };
        }
        $class->teacher_id = !empty($validated['teacher_id']) ? $validated['teacher_id'] : null;
        $class->save();

        return redirect()->route('admin.classes.index')->with('success', 'Data kelas berhasil diperbarui!');
    }

    public function destroy(Request $request, SchoolClass $class): RedirectResponse
    {
        if (!$class->exists) {
            $classId = $request->route('class') ?? $request->route('school_class') ?? $request->route('schoolClass');
            if ($classId) {
                $class = SchoolClass::findOrFail($classId);
            }
        }

        DB::beginTransaction();
        try {
            $className = $class->name;

            // Hapus file foto seluruh siswa di kelas ini dari storage
            $class->students()->whereNotNull('photo')->chunkById(200, function ($students) {
                foreach ($students as $student) {
                    if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                        Storage::disk('public')->delete($student->photo);
                    }
                }
            });

            // Hapus data riwayat presensi siswa kelas ini
            $studentIds = $class->students()->pluck('id');
            if ($studentIds->isNotEmpty()) {
                Attendance::whereIn('student_id', $studentIds)->delete();
                // Hapus data siswa di kelas ini
                $class->students()->delete();
            }

            // Hapus kelas
            $class->delete();

            DB::commit();
            return redirect()->route('admin.classes.index')
                ->with('success', "Data kelas {$className} beserta seluruh data siswa dan riwayat presensi terkait berhasil dihapus!");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.classes.index')
                ->with('error', 'Gagal menghapus kelas: ' . $e->getMessage());
        }
    }

    public function destroyAll(): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // Hapus seluruh foto siswa dari disk storage
            Student::whereNotNull('photo')->chunkById(200, function ($students) {
                foreach ($students as $student) {
                    if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                        Storage::disk('public')->delete($student->photo);
                    }
                }
            });

            // Bersihkan riwayat presensi dan siswa
            Attendance::query()->delete();
            Student::query()->delete();

            // Bersihkan seluruh data kelas
            SchoolClass::query()->delete();

            DB::commit();
            return redirect()->route('admin.classes.index')->with('success', 'Semua data kelas beserta siswa dan riwayat presensi berhasil dibersihkan!');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.classes.index')->with('error', 'Gagal menghapus semua data kelas: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(
            new SchoolClassTemplateExport(),
            'Template_Kelas.xlsx'
        );
    }

    public function template()
    {
        return $this->downloadTemplate();
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $import = new SchoolClassesImport();
            Excel::import($import, $request->file('file_excel'));

            return redirect()->route('admin.classes.index')->with('success', 'Data kelas berhasil diimpor dari Excel!');
        } catch (\Throwable $e) {
            return redirect()->route('admin.classes.index')
                ->with('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        }
    }
}