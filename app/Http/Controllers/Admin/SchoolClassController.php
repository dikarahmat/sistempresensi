<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Exports\SchoolClassTemplateExport;
use App\Imports\SchoolClassesImport;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Hapus satu rombel kelas (SOFT DELETE -> masuk Tempat Sampah).
     *
     * PERUBAHAN besar pada method ini:
     *  - Dulu: hapus foto siswa, hapus riwayat presensi, HAPUS PERMANEN semua
     *    siswa di kelas itu, lalu forceDelete kelasnya.
     *  - Sekarang: kalau masih ada siswa AKTIF di kelas, hapus DITOLAK dengan
     *    pesan jelas. Kalau kelas kosong, kelas hanya di-soft delete sehingga
     *    data siswa, foto, dan riwayat presensi TIDAK ADA yang tersentuh.
     */
    public function destroy(Request $request, SchoolClass $class): RedirectResponse
    {
        if (!$class->exists) {
            $classId = $request->route('class') ?? $request->route('school_class') ?? $request->route('schoolClass');
            if ($classId) {
                $class = SchoolClass::findOrFail($classId);
            }
        }

        $className = $class->name;

        // Aturan: kelas yang masih punya siswa aktif TIDAK BOLEH dihapus.
        // Halaman ini tidak pernah menghapus data siswa.
        $activeStudents = $class->students()->count();

        if ($activeStudents > 0) {
            return redirect()->route('admin.classes.index')->with(
                'error',
                "Kelas {$className} masih memiliki {$activeStudents} siswa aktif, pindahkan siswa terlebih dahulu."
            );
        }

        DB::beginTransaction();
        try {
            // Soft delete: kelas dipindahkan ke Tempat Sampah dan masih bisa
            // dipulihkan. Foto siswa, data siswa, dan riwayat presensi utuh.
            $class->delete();

            DB::commit();
            // Notifikasi hapus memakai flash 'error' supaya tampil MERAH
            // (alert-danger), konsisten dengan Data Siswa & Data Guru.
            return redirect()->route('admin.classes.index')
                ->with('error', "Rombel kelas {$className} berhasil dihapus dan dapat dipulihkan dari Tempat Sampah di Pengaturan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.classes.index')
                ->with('error', 'Gagal menghapus kelas. Silakan coba lagi atau hubungi admin.');
        }
    }

    /**
     * Hapus rombel kelas secara massal (SOFT DELETE -> masuk Tempat Sampah).
     *
     * PERUBAHAN: dulu method ini menghapus PERMANEN seluruh siswa dan seluruh
     * riwayat presensi. Sekarang tidak ada data siswa/presensi yang dihapus.
     *
     * Kelas yang masih punya siswa AKTIF DILEWATI (tidak dihapus) dan
     * jumlahnya dilaporkan lewat pesan, supaya tidak ada data siswa yang hilang.
     */
    public function destroyAll(): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $deleted = 0;
            $skipped = [];

            SchoolClass::withTrashed()->orderBy('id')->each(function (SchoolClass $class) use (&$deleted, &$skipped) {
                // Kelas yang sudah ada di Tempat Sampah dilewati saja.
                if ($class->trashed()) {
                    return;
                }

                if ($class->students()->exists()) {
                    $skipped[] = $class->name;
                    return;
                }

                $class->delete();
                $deleted++;
            });

            DB::commit();

            // Flash 'error' supaya notifikasi hapus tampil MERAH.
            if ($deleted === 0 && $skipped !== []) {
                return redirect()->route('admin.classes.index')->with(
                    'error',
                    'Tidak ada kelas yang dihapus. Semua kelas masih memiliki siswa aktif - pindahkan siswa terlebih dahulu.'
                );
            }

            $message = "Seluruh data kelas kosong ({$deleted}) berhasil dihapus dan dipindahkan ke Tempat Sampah di Pengaturan.";

            if ($skipped !== []) {
                $message .= ' ' . count($skipped) . ' kelas dilewati karena masih ada siswa aktif: ' . implode(', ', $skipped) . '.';
            }

            return redirect()->route('admin.classes.index')->with('error', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.classes.index')->with('error', 'Gagal menghapus semua data kelas. Silakan coba lagi atau hubungi admin.');
        }
    }

    /**
     * Tampilkan halaman Tempat Sampah untuk rombel kelas.
     *
     * Pola ini meniru StudentController::trash() dan TeacherController::trash().
     * Hanya kelas yang ber-soft delete yang muncul (global scope SoftDeletes).
     */
    public function trash(): View
    {
        $classes = SchoolClass::onlyTrashed()
            ->with(['academicYear', 'teacher'])
            ->withCount(['students as active_students_count'])
            ->orderBy('deleted_at', 'desc')
            ->paginate(50);

        // Jumlah data di Tempat Sampah, dipakai dialog konfirmasi
        // "Hapus Semua dari Tempat Sampah" (tombolnya nonaktif kalau 0).
        $trashedCount = SchoolClass::onlyTrashed()->count();

        return view('admin.classes.trash', compact('classes', 'trashedCount'));
    }

    /**
     * Pulihkan rombel kelas dari Tempat Sampah.
     *
     * school_classes punya UNIQUE index (name + academic_year_id) yang TIDAK
     * mengabaikan deleted_at, jadi konflik dicek lebih dulu agar yang muncul
     * pesan yang jelas, bukan error 500.
     */
    public function restore($id): RedirectResponse
    {
        // onlyTrashed() wajib dipakai agar global scope SoftDeletes tidak
        // membuat query mustahil.
        $class = SchoolClass::onlyTrashed()->findOrFail($id);

        $duplikat = SchoolClass::where('name', $class->name)
            ->where('academic_year_id', $class->academic_year_id)
            ->where('id', '!=', $class->id)
            ->exists();

        if ($duplikat) {
            return redirect()->route('admin.classes.trash')
                ->with('error', "Gagal dipulihkan: kelas {$class->name} sudah dipakai data kelas yang aktif. Ubah nama kelas lebih dulu.");
        }

        $class->restore();

        return redirect()->route('admin.classes.trash')
            ->with('success', "Rombel kelas {$class->name} berhasil dipulihkan!");
    }

    /**
     * Hapus permanen satu rombel kelas dari Tempat Sampah.
     */
    public function forceDelete($id): RedirectResponse
    {
        $class = SchoolClass::onlyTrashed()->findOrFail($id);

        DB::beginTransaction();
        try {
            $className = $class->name;

            // Data siswa TIDAK pernah dihapus dari halaman ini - kelas yang
            // masih punya siswa aktif tidak mungkin masuk Tempat Sampah.
            $class->forceDelete();

            DB::commit();
            // Flash 'error' supaya notifikasi hapus tampil MERAH.
            return redirect()->route('admin.classes.trash')
                ->with('error', "Rombel kelas {$className} berhasil dihapus permanen!");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.classes.trash')
                ->with('error', 'Gagal menghapus permanen kelas. Silakan coba lagi atau hubungi admin.');
        }
    }

    /**
     * Hapus PERMANEN seluruh isi Tempat Sampah Kelas sekaligus.
     *
     * Dipakai oleh tombol "Hapus Semua dari Tempat Sampah" di halaman
     * classes/trash. Dialog konfirmasinya (dengan dua checkbox) dibuat di
     * sisi tampilan memakai confirmUniversalDelete() dari layout bersama.
     */
    public function destroyAllTrashed(): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $total = SchoolClass::onlyTrashed()->count();

            if ($total === 0) {
                DB::rollBack();
                return redirect()->route('admin.classes.trash')
                    ->with('error', 'Tempat Sampah Kelas sudah kosong, tidak ada yang bisa dihapus.');
            }

            SchoolClass::onlyTrashed()->forceDelete();

            DB::commit();
            // Flash 'error' supaya notifikasi hapus tampil MERAH.
            return redirect()->route('admin.classes.trash')
                ->with('error', "Seluruh data kelas di Tempat Sampah ({$total}) berhasil dihapus permanen!");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.classes.trash')
                ->with('error', 'Gagal menghapus permanen data kelas dari Tempat Sampah. Silakan coba lagi atau hubungi admin.');
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
        return \App\Services\DownloadCacheService::downloadTemplate(
            'classes',
            new SchoolClassTemplateExport(),
            'Template_Kelas.xlsx'
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file_excel.required' => 'Pilih file Excel data kelas yang akan diunggah.',
            'file_excel.mimes' => 'Format file harus berekstensi .xlsx, .xls, atau .csv.',
            'file_excel.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $import = new SchoolClassesImport();
            Excel::import($import, $request->file('file_excel'));

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $errors = $import->getErrors();

            // 1) Semua baris berhasil -> notifikasi hijau (sukses)
            if ($imported > 0 && $skipped === 0) {
                return redirect()->route('admin.classes.index')
                    ->with('success', "Import berhasil: {$imported} kelas diimpor.");
            }

            // 2) Sebagian berhasil -> notifikasi kuning (peringatan) + alasan per baris
            if ($imported > 0) {
                return redirect()->route('admin.classes.index')
                    ->with('warning', "Import sebagian berhasil: {$imported} diimpor, {$skipped} dilewati.")
                    ->with('import_errors', $errors);
            }

            // 3) Tidak ada baris yang masuk -> notifikasi merah (gagal)
            $reason = $skipped > 0
                ? "{$skipped} baris dilewati."
                : 'File tidak memiliki baris data.';

            return redirect()->route('admin.classes.index')
                ->with('error', "Import gagal: tidak ada data yang diimpor. {$reason} Periksa format file Excel.")
                ->with('import_errors', $errors);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('admin.classes.index')
                ->with('error', 'Gagal memproses file Excel. Silakan coba lagi atau hubungi admin.');
        }
    }
}
