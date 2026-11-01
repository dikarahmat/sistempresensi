<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(Request $request): View
    {
        // Diurutkan berdasarkan nama tahun ajaran (asc) dan semester (Ganjil duluan, lalu Genap)
        $academicYears = AcademicYear::withCount(['schoolClasses', 'attendances'])
            ->orderBy('name', 'asc')
            ->orderByRaw("FIELD(semester, 'Ganjil', 'Genap')")
            ->paginate(15);

        $academicYearOptions = $this->getAcademicYearOptions($request);

        return view('admin.academic_years.index', compact('academicYears', 'academicYearOptions'));
    }

    /**
     * Daftar opsi tahun ajaran untuk dropdown.
     * Urut naik (terlama di atas), tepat 10 opsi, mulai dari (tahun ajaran berjalan - 1).
     * Saat ini: 2025/2026 sampai 2034/2035. Bergeser otomatis tiap tahun.
     *
     * Logika: Juli–Desember → tahun ajaran berjalan mulai dari tahun ini;
     * Januari–Juni → mulai dari tahun lalu.
     * Default terpilih = tahun ajaran berjalan.
     */
    private function getAcademicYearOptions(Request $request): array
    {
        $now = now();
        $currentYear = (int) $now->format('Y');
        $month = (int) $now->format('n');          // 1–12

        // Tahun ajaran berjalan: Juli–Des=tahun ini/tahun depan; Jan–Jun=tahun lalu/tahun ini
        $runningStartYear = ($month >= 7) ? $currentYear : ($currentYear - 1);
        $runningName = "{$runningStartYear}/" . ($runningStartYear + 1);

        // 10 opsi, urut naik, mulai dari (tahun ajaran berjalan - 1)
        $start = $runningStartYear - 1;
        $options = [];
        for ($i = 0; $i < 10; $i++) {
            $y = $start + $i;
            $options[] = "{$y}/" . ($y + 1);
        }

        // Default terpilih = tahun ajaran berjalan
        $default = $runningName;

        return [
            'options' => $options,
            'default' => $default,
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:#^\d{4}\/\d{4}$#'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'name.regex' => 'Format tahun ajaran harus YYYY/YYYY.',
            'semester.required' => 'Semester wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
        ]);

        $name = trim($request->name);
        $semester = $request->semester;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // --- Validasi tambahan (pesan bahasa Indonesia) ---

        if (preg_match('#^(\d{4})\/(\d{4})$#', $name, $m)) {
            // 1. Tahun kedua harus = tahun pertama + 1
            if ((int) $m[2] !== (int) $m[1] + 1) {
                $validated['name'] = ['Tahun kedua harus satu lebih dari tahun pertama. Contoh: 2025/2026.'];
                return redirect()->back()->withErrors($validated)->withInput();
            }
        } else {
            $validated['name'] = ['Format tahun ajaran harus YYYY/YYYY.'];
            return redirect()->back()->withErrors($validated)->withInput();
        }

        // 2. end_date >= start_date
        if ($endDate < $startDate) {
            $validated['end_date'] = ['Tanggal selesai tidak boleh lebih awal dari tanggal mulai.'];
            return redirect()->back()->withErrors($validated)->withInput();
        }

        // 3. Kedua tanggal dalam rentang tahun ajaran (1 Juli YYYY sampai 30 Juni YYYY+1)
        $startYear = (int) $m[1];
        $rangeStart = $startYear . '-07-01';
        $rangeEnd = ($startYear + 1) . '-06-30';
        if ($startDate < $rangeStart || $startDate > $rangeEnd || $endDate < $rangeStart || $endDate > $rangeEnd) {
            $validated['start_date'] = ['Tanggal harus berada dalam rentang tahun ajaran (1 Juli ' . $startYear . ' sampai 30 Juni ' . ($startYear + 1) . ').'];
            return redirect()->back()->withErrors($validated)->withInput();
        }

        // 4. Kombinasi nama + semester tidak boleh duplikat (abaikan soft delete)
        $dupActive = AcademicYear::where('name', $name)
            ->where('semester', $semester)
            ->exists();
        if ($dupActive) {
            $validated['name'] = ['Kombinasi tahun ajaran "' . $name . ' - ' . $semester . '" sudah ada.'];
            return redirect()->back()->withErrors($validated)->withInput();
        }

        // Baru selalu nonaktif — aktifkan nanti lewat toggle di tabel
        AcademicYear::create([
            'name' => $name,
            'semester' => $semester,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => false,
        ]);

        return redirect()->route('admin.academic-years.index')->with('success', __('Tahun ajaran berhasil ditambahkan!'));
    }

    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:#^\d{4}\/\d{4}$#'],
            'semester' => ['required', 'in:Ganjil,Genap'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'name.regex' => 'Format tahun ajaran harus YYYY/YYYY.',
            'semester.required' => 'Semester wajib dipilih.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
        ]);

        $name = trim($request->name);
        $semester = $request->semester;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        if (preg_match('#^(\d{4})\/(\d{4})$#', $name, $m)) {
            if ((int) $m[2] !== (int) $m[1] + 1) {
                $validated['name'] = ['Tahun kedua harus satu lebih dari tahun pertama. Contoh: 2025/2026.'];
                return redirect()->back()->withErrors($validated)->withInput();
            }
        } else {
            $validated['name'] = ['Format tahun ajaran harus YYYY/YYYY.'];
            return redirect()->back()->withErrors($validated)->withInput();
        }

        if ($endDate < $startDate) {
            $validated['end_date'] = ['Tanggal selesai tidak boleh lebih awal dari tanggal mulai.'];
            return redirect()->back()->withErrors($validated)->withInput();
        }

        $startYear = (int) $m[1];
        $rangeStart = $startYear . '-07-01';
        $rangeEnd = ($startYear + 1) . '-06-30';
        if ($startDate < $rangeStart || $startDate > $rangeEnd || $endDate < $rangeStart || $endDate > $rangeEnd) {
            $validated['start_date'] = ['Tanggal harus berada dalam rentang tahun ajaran (1 Juli ' . $startYear . ' sampai 30 Juni ' . ($startYear + 1) . ').'];
            return redirect()->back()->withErrors($validated)->withInput();
        }

        // Kombinasi nama + semester tidak boleh duplikat (abaikan soft delete, abaikan record sendiri)
        $dupActive = AcademicYear::where('name', $name)
            ->where('semester', $semester)
            ->where('id', '!=', $academicYear->id)
            ->exists();
        if ($dupActive) {
            $validated['name'] = ['Kombinasi tahun ajaran "' . $name . ' - ' . $semester . '" sudah ada.'];
            return redirect()->back()->withErrors($validated)->withInput();
        }

        $academicYear->update([
            'name' => $name,
            'semester' => $semester,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return redirect()->route('admin.academic-years.index')->with('success', __('Tahun ajaran berhasil diperbarui!'));
    }

    /**
     * Toggle status aktif suatu tahun ajaran.
     * target = 'on'  => aktifkan (pindahkan status dari yang lain)
     * target = 'off' => tolak (harus selalu ada satu yang aktif)
     *
     * Sengaja TIDAK memakai type-hint model, supaya id yang tidak ada / sudah
     * dihapus menghasilkan notifikasi merah yang jelas, bukan halaman 404.
     * (Route tetap sama: POST academic-years/{academic_year}/toggle-active.)
     */
    public function toggleActive(Request $request, $academic_year): RedirectResponse
    {
        $academicYear = AcademicYear::find($academic_year);

        if (! $academicYear) {
            return redirect()->route('admin.academic-years.index')
                ->with('error', __('Tahun ajaran tidak ditemukan atau sudah dihapus.'));
        }

        $target = strtolower(trim($request->input('target', '')));

        if ($target === 'on') {
            // Nonaktifkan semua yang lain, lalu aktifkan satu ini — dalam satu transaksi.
            try {
                DB::transaction(function () use ($academicYear) {
                    AcademicYear::query()->where('is_active', true)
                        ->where('id', '!=', $academicYear->id)
                        ->update(['is_active' => false]);

                    $academicYear->update(['is_active' => true]);
                });
            } catch (\Throwable $e) {
                report($e);

                return redirect()->route('admin.academic-years.index')
                    ->with('error', __('Gagal mengaktifkan tahun ajaran. Coba lagi.'));
            }

            return redirect()->route('admin.academic-years.index')
                ->with('success', __('Tahun ajaran :name - :semester berhasil diaktifkan.', ['name' => $academicYear->name, 'semester' => $academicYear->semester]));
        }

        // target = 'off' — selalu ditolak, harus selalu ada satu yang aktif.
        return redirect()->route('admin.academic-years.index')
            ->with('error', __('Tahun ajaran aktif tidak dapat dimatikan. Nyalakan tahun ajaran lain untuk memindahkan status aktif.'));
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
            ->with('deleted', __('Tahun ajaran :name telah dihapus dari sistem.', ['name' => $academicYear->name]));
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