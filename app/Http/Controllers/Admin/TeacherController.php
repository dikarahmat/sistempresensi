<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TeacherTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\TeachersImport;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Teacher;
use App\Services\TeacherCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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

        $teachers = $query->orderBy('name', 'asc')->paginate(20)->withQueryString();
        $classes = SchoolClass::orderBy('name')->get();
        $totalTeachers = Teacher::count();

        return view('admin.teachers.index', compact('teachers', 'classes', 'totalTeachers'));
    }

    public function show(Teacher $teacher): View
    {
        $teacher->load('schoolClass');

        return view('admin.teachers.show', compact('teacher'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s.,\'\-]+$/u'],
            'nip' => ['required', 'regex:/^(\d{16}|\d{18})$/', 'unique:teachers,nip'],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
            'phone_number' => ['required', 'regex:/^\d{10,15}$/'],
        ], [
            'name.required' => __('Nama lengkap wajib diisi.'),
            'name.regex' => __('Format nama hanya boleh huruf dan tanda baca.'),
            'nip.required' => __('NIP/NUPTK harus 16 atau 18 digit angka.'),
            'nip.regex' => __('NIP/NUPTK harus 16 atau 18 digit angka.'),
            'nip.unique' => __('NIP sudah terdaftar.'),
            'gender.required' => __('Pilih jenis kelamin.'),
            'gender.in' => __('Pilih jenis kelamin.'),
            'phone_number.required' => __('Nomor telepon harus 10-15 digit angka.'),
            'phone_number.regex' => __('Nomor telepon harus 10-15 digit angka.'),
        ]);

        if ($validator->fails()) {
            return back()->withInput()
                ->withErrors($validator)
                ->with('open_modal', 'add');
        }

        // Deteksi duplikat nama secara manual (form Tambah Guru Baru).
        $validatedData = $validator->validated();
        $namaDuplikat = Teacher::where('name', $validatedData['name'])
            ->whereNull('deleted_at')
            ->exists();
        if ($namaDuplikat) {
            return back()->withInput()
                ->withErrors(['name' => 'Nama guru sudah terdaftar.'])
                ->with('open_modal', 'add');
        }

        $validated = $validatedData;
        $validated['phone'] = $validated['phone_number'];
        $validated['qr_token'] = (string) Str::uuid();
        $validated['status'] = 'Aktif';

        Teacher::create($validated);

        return redirect()->route('admin.guru.index')
            ->with('success', __('Data guru berhasil ditambahkan!'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s.,\'\-]+$/u'],
            'nip' => ['required', 'regex:/^(\d{16}|\d{18})$/', 'unique:teachers,nip,' . $teacher->id],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
            'phone_number' => ['required', 'regex:/^\d{10,15}$/'],
        ], [
            'name.required' => __('Nama lengkap wajib diisi.'),
            'name.regex' => __('Format nama hanya boleh huruf dan tanda baca.'),
            'nip.required' => __('NIP/NUPTK harus 16 atau 18 digit angka.'),
            'nip.regex' => __('NIP/NUPTK harus 16 atau 18 digit angka.'),
            'nip.unique' => __('NIP sudah terdaftar.'),
            'gender.required' => __('Pilih jenis kelamin.'),
            'gender.in' => __('Pilih jenis kelamin.'),
            'phone_number.required' => __('Nomor telepon harus 10-15 digit angka.'),
            'phone_number.regex' => __('Nomor telepon harus 10-15 digit angka.'),
        ]);

        $validated['phone'] = $validated['phone_number'];
        $teacher->update($validated);
        $teacher->load('schoolClass');

        if ($request->expectsJson() || $request->ajax()) {
            $token = TeacherCardService::qrTokenFor($teacher->nip, $teacher->name);
            $qrSrc = null;
            if ($token) {
                try {
                    $svg = QrCode::size(300)->margin(0)->generate((string) $token);
                    $qrSrc = 'data:image/svg+xml;base64,' . base64_encode($svg);
                } catch (\Throwable $e) {}
            }

            return response()->json([
                'status' => 'success',
                'message' => __('Data guru berhasil diperbarui!'),
                'data' => [
                    'name' => $teacher->name,
                    'nip' => $teacher->nip,
                    'gender' => $teacher->gender,
                    'phone_number' => $teacher->phone_number,
                    'phone' => $teacher->phone,
                    'qr_src' => $qrSrc,
                ],
            ]);
        }

        return redirect()->route('admin.guru.show', $teacher->id)
            ->with('success', __('Data guru berhasil diperbarui!'));
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $teacherName = $teacher->name;
        $teacher->delete();

        return redirect()->route('admin.guru.index')
            ->with('danger', __('Data guru :name berhasil dipindahkan ke Tempat Sampah.', ['name' => $teacherName]))
            ->with('deleted', __('Data guru :name berhasil dipindahkan ke Tempat Sampah.', ['name' => $teacherName]));
    }

    public function destroyAllActive(Request $request): RedirectResponse
    {
        if ($request->boolean('confirm_permanent')) {
            return $this->destroyAllTrashed();
        }

        try {
            $active = Teacher::whereNull('deleted_at')->count();

            if ($active === 0) {
                return redirect()->route('admin.guru.index')
                    ->with('error', __('Tidak ada data guru aktif yang bisa dihapus.'));
            }

            $deleted = 0;
            Teacher::whereNull('deleted_at')->each(function (Teacher $t) use (&$deleted) {
                $t->delete();
                $deleted++;
            });

            return redirect()->route('admin.guru.index')
                ->with('danger', "$deleted data guru dipindahkan ke Tempat Sampah.")
                ->with('deleted', "$deleted data guru dipindahkan ke Tempat Sampah.");
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('admin.guru.index')
                ->with('error', __('Gagal menghapus data. Coba lagi.'));
        }
    }

    public function destroyAll(): RedirectResponse
    {
        return $this->destroyAllActive(request());
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

    public function downloadTemplate()
    {
        return Excel::download(new TeacherTemplateExport(), 'Template_Data_Guru.xlsx');
    }

    /**
     * Import data guru dari file Excel.
     * Selalu balas JSON {status, message}; HTTP 200 kalau sukses/partial, 422 kalau ada kesalahan.
     */
    public function import(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ], [
                'file_excel.required' => 'Pilih file Excel data guru yang akan diunggah.',
                'file_excel.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
                'file_excel.max' => 'Ukuran file maksimal 10 MB.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $import = new TeachersImport();
            DB::transaction(function () use ($request, $import) {
                Excel::import($import, $request->file('file_excel'));
            });

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $orderedErrors = $import->getOrderedErrors();
            $totalErrors = $skipped > 0 ? $skipped : count($orderedErrors);

            if ($imported > 0 && $totalErrors === 0) {
                $message = "Import berhasil: {$imported} guru ditambahkan.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => $message,
                    ]);
                }
                return redirect()->route('admin.guru.index')
                    ->with('success', $message);
            }

            if ($totalErrors === 0 && $imported === 0) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Import gagal: tidak ada data yang diimpor.',
                    ], 422);
                }
                return redirect()->route('admin.guru.index')
                    ->with('warning', 'Import gagal: tidak ada data yang diimpor.');
            }

            if ($imported > 0 && $totalErrors > 0) {
                $message = "Import selesai: {$imported} guru ditambahkan, {$totalErrors} dilewati karena sudah terdaftar.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => $message,
                    ], 200);
                }
                return redirect()->route('admin.guru.index')
                    ->with('success', $message);
            }

            if ($imported === 0 && $totalErrors > 0) {
                $message = "Import gagal: semua data guru dalam file sudah terdaftar ({$totalErrors} dilewati).";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $message,
                    ], 200);
                }
                return redirect()->route('admin.guru.index')
                    ->with('error', $message);
            }

            $message = 'Import gagal: tidak ada data yang diimpor.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                ], 422);
            }
            return redirect()->route('admin.guru.index')
                ->with('error', $message);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }
            return redirect()->route('admin.guru.index')
                ->with('error', $e->getMessage());
        }
    }

    public function trash(): View
    {
        $query = Teacher::onlyTrashed()->with('schoolClass');

        $teachers = $query->orderBy('deleted_at', 'desc')->paginate(50);
        $classes = SchoolClass::orderBy('name')->get();
        $trashedCount = Teacher::onlyTrashed()->count();

        return view('admin.teachers.trash', compact('teachers', 'classes', 'trashedCount'));
    }

    public function restore($id): RedirectResponse
    {
        $teacher = Teacher::onlyTrashed()->findOrFail($id);

        if (!empty($teacher->nip)) {
            $duplicate = Teacher::where('nip', $teacher->nip)
                ->where('id', '!=', $teacher->id)
                ->exists();

            if ($duplicate) {
                return redirect()->route('admin.teachers.trash')
                    ->with('error', __('Gagal dipulihkan: NIP :nip sudah dipakai data guru yang aktif. Perbaiki NIP lebih dulu.', ['nip' => $teacher->nip]));
            }
        }

        $teacher->restore();

        return redirect()->route('admin.teachers.trash')
            ->with('success', __('Data guru :name berhasil dipulihkan!', ['name' => $teacher->name]));
    }

    public function forceDelete($id): RedirectResponse
    {
        $teacher = Teacher::onlyTrashed()->findOrFail($id);

        DB::beginTransaction();
        try {
            $teacherName = $teacher->name;
            SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);

            if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
                Storage::disk('public')->delete($teacher->photo);
            }

            $teacher->forceDelete();
            DB::commit();

            return redirect()->route('admin.teachers.trash')
                ->with('deleted', __('Data guru :name berhasil dihapus permanen!', ['name' => $teacherName]));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->route('admin.teachers.trash')
                ->with('error', __('Gagal menghapus permanen data guru. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function destroyAllTrashed(): RedirectResponse
    {
        $count = Teacher::onlyTrashed()->count();

        if ($count === 0) {
            return redirect()->route('admin.teachers.trash')
                ->with('info', __('Tempat sampah sudah kosong.'));
        }

        DB::beginTransaction();
        try {
            $trashed = Teacher::onlyTrashed();
            $deleted = 0;
            $trashed->each(function (Teacher $t) use (&$deleted) {
                SchoolClass::where('teacher_id', $t->id)->update(['teacher_id' => null]);

                if ($t->photo && Storage::disk('public')->exists($t->photo)) {
                    Storage::disk('public')->delete($t->photo);
                }

                $t->forceDelete();
                $deleted++;
            });

            DB::commit();

            return redirect()->route('admin.teachers.trash')
                ->with('deleted', "$deleted data guru di Tempat Sampah berhasil dihapus permanen.");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->route('admin.teachers.trash')
                ->with('error', __('Gagal menghapus permanen data guru dari Tempat Sampah. Silakan coba lagi atau hubungi admin.'));
        }
    }
}
