<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\TeacherTemplateExport;
use App\Imports\TeachersImport;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class TeacherController extends Controller
{
    /**
     * Tampilkan halaman utama data guru.
     */
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

        $teachers = $query->orderBy('name')->paginate(20);
        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.teachers.index', compact('teachers', 'classes'));
    }

    /**
     * Simpan data guru baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'required|regex:/^[0-9]{18}$/|unique:teachers,nip',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'phone_number' => 'required|regex:/^[0-9]{10,14}$/',
            'email' => 'nullable|email|max:255',
            'school_class_id' => 'nullable|exists:school_classes,id',
        ], [
            'name.required' => 'Nama lengkap guru wajib diisi.',
            'nip.regex' => 'NIP harus berupa angka 18 digit.',
            'nip.unique' => 'NIP sudah terdaftar pada guru lain.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'phone_number.regex' => 'Nomor HP harus berupa angka 10-14 digit.',
        ]);

        if (empty($validated['qr_token'])) {
            $validated['qr_token'] = (string) Str::uuid();
        }

        $validated['phone'] = $validated['phone_number'] ?? null;

        if (!empty($validated['nip'])) {
            $userEmail = !empty($validated['email'])
                ? $validated['email']
                : $validated['nip'] . '@guru.smppresensipgri.sch.id';

            $user = User::firstOrCreate(
                ['email' => $userEmail],
                [
                    'name' => $validated['name'],
                    'password' => Hash::make($validated['nip']),
                    'role' => 'guru',
                    'email_verified_at' => now(),
                ]
            );

            $validated['user_id'] = $user->id;
        }

        $classId = $validated['school_class_id'] ?? null;
        unset($validated['school_class_id'], $validated['email']);

        $teacher = Teacher::create($validated);

        if ($classId) {
            SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
            SchoolClass::where('id', $classId)->update(['teacher_id' => $teacher->id]);
        }

        return redirect()->route('admin.guru.index')->with('success', "Data Guru & Wali Kelas '{$teacher->name}' berhasil ditambahkan!");
    }

    /**
     * Update Guru & Penugasan Wali Kelas.
     */
    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        if (!$teacher->exists) {
            $id = $request->route('teacher') ?? $request->route('guru') ?? $request->route('id');
            if ($id instanceof Teacher) {
                $teacher = $id;
            } elseif ($id) {
                $teacher = Teacher::findOrFail($id);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'required|regex:/^[0-9]{18}$/|unique:teachers,nip,' . $teacher->id,
            'gender' => 'required|in:Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'phone_number' => 'required|regex:/^[0-9]{10,14}$/',
            'school_class_id' => 'nullable',
        ], [
            'name.required' => 'Nama lengkap guru wajib diisi.',
            'nip.regex' => 'NIP harus berupa angka 18 digit.',
            'nip.unique' => 'NIP sudah terdaftar pada guru lain.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'phone_number.regex' => 'Nomor HP harus berupa angka 10-14 digit.',
        ]);

        if (empty($teacher->qr_token)) {
            $validated['qr_token'] = (string) Str::uuid();
        }

        $validated['phone'] = $validated['phone_number'] ?? $teacher->phone;

        $classId = $validated['school_class_id'] ?? null;
        unset($validated['school_class_id']);

        $teacher->update($validated);

        if ($classId) {
            SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
            SchoolClass::where('id', $classId)->update(['teacher_id' => $teacher->id]);
        } else {
            SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
        }

        return redirect()->route('admin.guru.index')->with('success', "Data guru '{$teacher->name}' berhasil diperbarui!");
    }

    /**
     * Hapus Data Guru dengan proteksi transaksi.
     */
    public function destroy(Request $request, Teacher $teacher): RedirectResponse
    {
        if (!$teacher->exists) {
            $id = $request->route('teacher') ?? $request->route('guru') ?? $request->route('id');
            if ($id instanceof Teacher) {
                $teacher = $id;
            } elseif ($id) {
                $teacher = Teacher::findOrFail($id);
            }
        }

        DB::beginTransaction();
        try {
            $name = $teacher->name;

            // Lepaskan penugasan wali kelas
            SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);

            // Soft delete: data masih bisa dipulihkan dari halaman Arsip
            $teacher->delete();

            DB::commit();
            $message = 'Data guru ' . $name . ' berhasil diarsipkan! Anda dapat memulihkannya dari halaman Arsip.';
            return redirect()->route('admin.guru.index')->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.guru.index')->with('error', 'Gagal menghapus data guru: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Seluruh Data Guru Secara Massal dengan proteksi transaksi.
     */
    public function destroyAll(): RedirectResponse
    {
        DB::beginTransaction();
        try {
            Teacher::withTrashed()->whereNotNull('photo')->chunkById(200, function ($teachers) {
                foreach ($teachers as $teacher) {
                    if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
                        Storage::disk('public')->delete($teacher->photo);
                    }
                }
            });

            SchoolClass::whereNotNull('teacher_id')->update(['teacher_id' => null]);
            Teacher::withTrashed()->forceDelete();

            DB::commit();
            return redirect()->route('admin.guru.index')->with('success', 'Seluruh data guru dan penugasan wali kelas berhasil dibersihkan!');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.guru.index')->with('error', 'Gagal menghapus data guru: ' . $e->getMessage());
        }
    }

    /**
     * Unduh Template Excel.
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new TeacherTemplateExport(),
            'Template_Wali_Kelas.xlsx'
        );
    }

    /**
     * Import Data Guru dari Excel.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file_excel.required' => 'Pilih file Excel yang akan diunggah.',
            'file_excel.mimes' => 'Format file harus berekstensi .xlsx, .xls, atau .csv.',
        ]);

        try {
            $import = new TeachersImport();
            Excel::import($import, $request->file('file_excel'));

            $message = "Import selesai! {$import->getImportedCount()} data guru berhasil diimpor.";
            if ($import->getSkippedCount() > 0) {
                $message .= " ({$import->getSkippedCount()} data dilewati)";
            }

            return redirect()->route('admin.guru.index')
                ->with('success', $message)
                ->with('import_errors', $import->getErrors());
        } catch (\Throwable $e) {
            return redirect()->route('admin.guru.index')
                ->with('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan halaman arsip (guru yang dihapus / soft deleted).
     */
    public function trash(): View
    {
        $query = Teacher::onlyTrashed()->with('schoolClass');

        $teachers = $query->orderBy('deleted_at', 'desc')->paginate(50);
        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.teachers.trash', compact('teachers', 'classes'));
    }

    /**
     * Pulihkan guru dari arsip.
     */
    public function restore($id): RedirectResponse
    {
        // onlyTrashed() wajib dipakai agar global scope SoftDeletes tidak membuat
        // query mustahil (penyebab 404 sebelumnya).
        $teacher = Teacher::onlyTrashed()->findOrFail($id);
        $teacher->restore();

        return redirect()->route('admin.teachers.trash')
            ->with('success', "Data guru {$teacher->name} berhasil dipulihkan!");
    }

    /**
     * Hapus permanen guru dari arsip.
     */
    public function forceDelete($id): RedirectResponse
    {
        // Hanya guru yang ada di arsip yang bisa dihapus permanen
        $teacher = Teacher::onlyTrashed()->findOrFail($id);

        DB::beginTransaction();
        try {
            $teacherName = $teacher->name;

            // Lepaskan penugasan wali kelas
            SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);

            // Hapus permanen data guru dari database
            $teacher->forceDelete();

            DB::commit();
            return redirect()->route('admin.teachers.trash')
                ->with('success', "Data guru {$teacherName} berhasil dihapus permanen!");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.teachers.trash')
                ->with('error', 'Gagal menghapus permanen guru: ' . $e->getMessage());
        }
    }
}
