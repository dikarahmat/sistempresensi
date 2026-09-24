<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $academicYears = AcademicYear::withCount(['schoolClasses', 'attendances'])
            ->orderBy('is_active', 'desc')
            ->orderBy('start_date', 'desc')
            ->paginate(15);

        return view('admin.academic_years.index', compact('academicYears'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'semester' => 'required|in:Ganjil,Genap',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        $isActive = $request->boolean('is_active');

        if ($isActive) {
            AcademicYear::query()->update(['is_active' => false]);
        }

        AcademicYear::create([
            'name' => trim($request->name),
            'semester' => $request->semester,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $isActive,
        ]);

        return redirect()->route('admin.academic-years.index')->with('success', 'Tahun ajaran berhasil ditambahkan!');
    }

    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'semester' => 'required|in:Ganjil,Genap',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        $isActive = $request->boolean('is_active');

        if ($isActive && !$academicYear->is_active) {
            AcademicYear::query()->update(['is_active' => false]);
        }

        $academicYear->update([
            'name' => trim($request->name),
            'semester' => $request->semester,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $isActive,
        ]);

        return redirect()->route('admin.academic-years.index')->with('success', 'Tahun ajaran berhasil diperbarui!');
    }

    public function toggleActive(AcademicYear $academicYear): RedirectResponse
    {
        // Hanya boleh ada 1 tahun ajaran aktif pada satu waktu
        $academicYear->makeActive();

        return redirect()->route('admin.academic-years.index')
            ->with('success', "Tahun ajaran {$academicYear->name} ({$academicYear->semester}) sekarang aktif!");
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->is_active) {
            return redirect()->route('admin.academic-years.index')
                ->with('error', 'Tahun ajaran yang sedang AKTIF tidak dapat dihapus!');
        }

        if ($academicYear->schoolClasses()->exists() || $academicYear->attendances()->exists()) {
            return redirect()->route('admin.academic-years.index')
                ->with('error', 'Tahun ajaran tidak dapat dihapus karena memiliki data kelas atau presensi terkait!');
        }

        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')->with('success', 'Tahun ajaran berhasil dihapus!');
    }
}
