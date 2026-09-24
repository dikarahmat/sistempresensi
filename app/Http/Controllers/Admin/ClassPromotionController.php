<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClassPromotionController extends Controller
{
    /**
     * Tampilan Halaman Kenaikan Kelas / Mutasi Rombel Massal.
     */
    public function index(Request $request): View
    {
        $classes = SchoolClass::withCount(['students' => fn($q) => $q->where('status', 'Aktif')])
            ->orderBy('grade')
            ->orderBy('name')
            ->get();

        $activeYear = AcademicYear::getActive();
        $sourceClassId = $request->input('source_class_id');
        $targetClassId = $request->input('target_class_id');

        $students = collect();
        $sourceClass = null;

        if ($sourceClassId) {
            $sourceClass = SchoolClass::find($sourceClassId);
            if ($sourceClass) {
                $students = Student::where('school_class_id', $sourceClassId)
                    ->where('status', 'Aktif')
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('admin.promotions.index', compact(
            'classes',
            'activeYear',
            'sourceClassId',
            'targetClassId',
            'sourceClass',
            'students'
        ));
    }

    /**
     * API JSON untuk mengambil daftar siswa berdasarkan kelas asal.
     */
    public function getStudents(SchoolClass $schoolClass): JsonResponse
    {
        $students = Student::where('school_class_id', $schoolClass->id)
            ->where('status', 'Aktif')
            ->orderBy('name')
            ->get(['id', 'nis', 'nisn', 'name', 'gender', 'status']);

        return response()->json([
            'success' => true,
            'total' => $students->count(),
            'students' => $students,
        ]);
    }

    /**
     * Eksekusi Proses Kenaikan / Pemindahan Siswa (Bulk & Individual).
     */
    public function promote(Request $request): RedirectResponse
    {
        $request->validate([
            'source_class_id' => 'required|exists:school_classes,id',
            'action_type'     => 'required|in:promote,graduate',
            'target_class_id' => 'nullable|required_if:action_type,promote|exists:school_classes,id',
            'student_ids'     => 'required|array|min:1',
            'student_ids.*'   => 'exists:students,id',
        ], [
            'source_class_id.required' => 'Pilih kelas asal siswa terlebih dahulu.',
            'target_class_id.required_if' => 'Pilih kelas tujuan pemindahan/kenaikan.',
            'student_ids.required' => 'Pilih minimal satu siswa untuk dipindahkan.',
            'student_ids.min' => 'Pilih minimal satu siswa untuk dipindahkan.',
        ]);

        $sourceClass = SchoolClass::findOrFail($request->source_class_id);
        $studentIds = $request->input('student_ids', []);
        $actionType = $request->input('action_type');
        $count = count($studentIds);

        if ($actionType === 'promote') {
            if ($request->source_class_id == $request->target_class_id) {
                return back()->withInput()->with('error', 'Kelas tujuan tidak boleh sama dengan kelas asal!');
            }

            $targetClass = SchoolClass::findOrFail($request->target_class_id);

            DB::transaction(function () use ($studentIds, $targetClass) {
                Student::whereIn('id', $studentIds)->update([
                    'school_class_id' => $targetClass->id,
                ]);
            });

            return redirect()->route('admin.kenaikan-kelas.index', [
                'source_class_id' => $targetClass->id,
            ])->with('success', "Sukses! Sebanyak {$count} siswa dari Kelas {$sourceClass->name} berhasil dinaikkan / dipindahkan ke Kelas {$targetClass->name}.");
        }

        if ($actionType === 'graduate') {
            DB::transaction(function () use ($studentIds) {
                Student::whereIn('id', $studentIds)->update([
                    'status' => 'Lulus',
                ]);
            });

            return redirect()->route('admin.kenaikan-kelas.index', [
                'source_class_id' => $sourceClass->id,
            ])->with('success', "Sukses! Sebanyak {$count} siswa dari Kelas {$sourceClass->name} telah berhasil diubah statusnya menjadi Lulus / Alumni.");
        }

        return back()->with('error', 'Tindakan tidak valid.');
    }
}
