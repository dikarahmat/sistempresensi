<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Exports\TeacherTemplateExport;
use App\Imports\TeachersImport;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class TeacherController extends Controller
{
    /**
     * Tampilkan data seluruh guru dan wali kelas.
     */
    public function index(Request $request): View
    {
        $query = Teacher::with(['schoolClass', 'schoolClasses', 'user']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $teachers = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();
        $classes = SchoolClass::orderBy('name')->get();
        $totalTeachers = Teacher::count();
        $totalWaliKelas = SchoolClass::whereNotNull('teacher_id')->distinct('teacher_id')->count('teacher_id');

        return view('admin.teachers.index', compact(
            'teachers',
            'classes',
            'totalTeachers',
            'totalWaliKelas'
        ));
    }

    /**
     * Detail Data Guru (Bisa JSON untuk modal interaktif atau View).
     */
    public function show(Teacher $teacher, Request $request)
    {
        if (!$teacher->exists) {
            $id = $request->route('teacher') ?? $request->route('guru') ?? $request->route('id');
            if ($id instanceof Teacher) {
                $teacher = $id;
            } elseif ($id) {
                $teacher = Teacher::findOrFail($id);
            }
        }

        $teacher->load(['schoolClass', 'schoolClasses', 'user']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'nip' => $teacher->nip ?? '-',
                    'gender' => $teacher->gender ?? 'Laki-laki',
                    'birth_place' => $teacher->birth_place ?? '-',
                    'birth_date' => $teacher->birth_date ? $teacher->birth_date->format('Y-m-d') : null,
                    'birth_date_formatted' => $teacher->birth_date ? $teacher->birth_date->translatedFormat('d F Y') : '-',
                    'phone' => $teacher->phone_number ?? $teacher->phone ?? '-',
                    'photo' => $teacher->photo ? asset('storage/' . $teacher->photo) : null,
                    'qr_token' => $teacher->qr_token ?? $teacher->nip ?? (string)$teacher->id,
                    'kelas_name' => $teacher->schoolClass?->name ?? 'Bukan Wali Kelas',
                    'kelas_id' => $teacher->schoolClass?->id,
                    'user_email' => $teacher->user?->email ?? ($teacher->nip ? $teacher->nip . '@guru.smppresensipgri.sch.id' : '-'),
                ],
            ]);
        }

        return redirect()->route('admin.guru.index');
    }

    /**
     * Tambah Guru Baru & Otomatisasi Akun Login + Wali Kelas.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50|unique:teachers,nip',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'school_class_id' => 'nullable|exists:school_classes,id',
        ], [
            'name.required' => 'Nama lengkap guru wajib diisi.',
            'nip.unique' => 'NIP sudah terdaftar pada guru lain.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('teachers', 'public');
        }

        if (empty($validated['qr_token'])) {
            $validated['qr_token'] = (string) Str::uuid();
        }

        // Sinkronisasi nomor telepon
        $validated['phone'] = $validated['phone_number'] ?? null;

        // Auto buatkan akun User untuk Guru jika ada NIP
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

        // Jika dipilih sebagai wali kelas
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
            'nip' => 'nullable|string|max:50|unique:teachers,nip,' . $teacher->id,
            'gender' => 'required|in:Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'phone_number' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'school_class_id' => 'nullable',
        ], [
            'name.required' => 'Nama lengkap guru wajib diisi.',
            'nip.unique' => 'NIP sudah terdaftar pada guru lain.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        if ($request->hasFile('photo')) {
            if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $validated['photo'] = $request->file('photo')->store('teachers', 'public');
        }

        if (empty($teacher->qr_token)) {
            $validated['qr_token'] = (string) Str::uuid();
        }

        $validated['phone'] = $validated['phone_number'] ?? $teacher->phone;

        // Sinkronisasi User
        if (!empty($validated['nip']) && $teacher->user_id) {
            $user = User::find($teacher->user_id);
            if ($user) {
                $user->update([
                    'name' => $validated['name'],
                ]);
            }
        }

        $classId = $validated['school_class_id'] ?? null;
        unset($validated['school_class_id']);

        $teacher->update($validated);

        // Update penugasan wali kelas
        SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
        if (!empty($classId) && $classId !== 'none') {
            SchoolClass::where('id', $classId)->update(['teacher_id' => $teacher->id]);
        }

        return redirect()->route('admin.guru.index')->with('success', "Data Guru '{$teacher->name}' berhasil diperbarui!");
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

            if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
                Storage::disk('public')->delete($teacher->photo);
            }

            // Lepaskan penugasan wali kelas
            SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);

            $teacher->delete();

            DB::commit();
            return redirect()->route('admin.guru.index')->with('success', "Data guru '{$name}' berhasil dihapus!");
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
            Teacher::whereNotNull('photo')->chunkById(200, function ($teachers) {
                foreach ($teachers as $teacher) {
                    if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
                        Storage::disk('public')->delete($teacher->photo);
                    }
                }
            });

            SchoolClass::whereNotNull('teacher_id')->update(['teacher_id' => null]);
            Teacher::query()->delete();

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
}