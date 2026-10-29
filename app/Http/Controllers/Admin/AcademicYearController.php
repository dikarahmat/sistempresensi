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

        return redirect()->route('admin.academic-years.index')->with('success', __('Tahun ajaran berhasil ditambahkan!'));
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

        // Tahun ajaran yang sedang aktif tidak boleh dinonaktifkan sendiri dari
        // form edit (harus mengaktifkan tahun ajaran lain terlebih dahulu),
        // supaya sistem tidak pernah berakhir tanpa tahun ajaran aktif.
        if ($academicYear->is_active && !$isActive) {
            return redirect()->route('admin.academic-years.index')
                ->with('error', __('Tahun ajaran sedang berlangsung tidak dapat dinonaktifkan. Aktifkan tahun ajaran lain terlebih dahulu.'));
        }

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

        return redirect()->route('admin.academic-years.index')->with('success', __('Tahun ajaran berhasil diperbarui!'));
    }

    /**
     * Aktifkan satu tahun ajaran (dan nonaktifkan yang lain).
     *
     * Sengaja TIDAK memakai type-hint model, supaya id yang tidak ada / sudah
     * dihapus menghasilkan notifikasi merah yang jelas, bukan halaman 404.
     * (Route tetap sama: POST academic-years/{academic_year}/toggle-active.)
     */
    public function toggleActive($academic_year): RedirectResponse
    {
        $academicYear = ctype_digit((string) $academic_year)
            ? AcademicYear::find($academic_year)
            : null;

        if (! $academicYear) {
            return redirect()->route('admin.academic-years.index')
                ->with('error', __('Tahun ajaran tidak ditemukan atau sudah dihapus.'));
        }

        try {
            // Dalam satu transaksi: hanya boleh ada satu tahun ajaran aktif dan
            // tidak pernah berakhir nol (atau dua) yang aktif.
            $academicYear->makeActive();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.academic-years.index')
                ->with('error', __('Gagal mengaktifkan tahun ajaran. Coba lagi.'));
        }

        return redirect()->route('admin.academic-years.index')
            ->with('success', __('Tahun ajaran :name - :semester berhasil diaktifkan.', ['name' => $academicYear->name, 'semester' => $academicYear->semester]));
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->is_active) {
            return redirect()->route('admin.academic-years.index')
                ->with('error', __('Tahun ajaran yang sedang aktif tidak bisa dihapus. Aktifkan tahun ajaran lain dulu.'));
        }

        if ($academicYear->schoolClasses()->exists() || $academicYear->attendances()->exists()) {
            return redirect()->route('admin.academic-years.index')
                ->with('error', __('Tahun ajaran masih punya data terkait, tidak bisa dihapus permanen.'));
        }

        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')
            ->with('success', __('Tahun ajaran dipindahkan ke Tempat Sampah.'));
    }

    /**
     * Pulihkan tahun ajaran dari Tempat Sampah.
     */
    public function restore($id): RedirectResponse
    {
        $year = AcademicYear::onlyTrashed()->findOrFail($id);
        $year->restore();

        AcademicYear::clearActiveCache();

        return redirect()->route('admin.academic-years.index')
            ->with('success', __('Tahun ajaran berhasil dipulihkan.'));
    }

    /**
     * Hapus permanen tahun ajaran dari Tempat Sampah.
     */
    public function forceDelete($id): RedirectResponse
    {
        $year = AcademicYear::onlyTrashed()->findOrFail($id);

        if ($year->schoolClasses()->exists() || $year->attendances()->exists()) {
            return redirect()->route('admin.academic-years.index')
                ->with('error', __('Tahun ajaran masih punya data terkait, tidak bisa dihapus permanen.'));
        }

        $year->forceDelete();

        return redirect()->route('admin.academic-years.index')
            ->with('success', __('Tahun ajaran permanen dihapus.'));
    }
}
