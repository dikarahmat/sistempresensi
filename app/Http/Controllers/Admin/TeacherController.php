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
use Illuminate\Validation\ValidationException;
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

        $teachers = $query->orderBy('name')->paginate(20)->withQueryString();
        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.teachers.index', compact('teachers', 'classes'));
    }

    /**
     * Simpan data guru baru.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate($this->teacherRules(), $this->teacherMessages(), $this->teacherAttributes());
        } catch (ValidationException $e) {
            return $this->formErrorRedirect($e, 'add');
        }

        try {
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
        } catch (\Throwable $ex) {
            report($ex);
            return redirect()->route('admin.guru.index')
                ->withInput($request->all())
                ->with('open_modal', 'add')
                ->with('form_error', 'Gagal menyimpan data guru. Coba lagi.');
        }

        return redirect()->route('admin.guru.index')->with('success', "Data guru {$teacher->name} berhasil disimpan.");
    }

    /**
     * Update Guru & Penugasan Kelas.
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

        try {
            $validated = $request->validate($this->teacherRules($teacher), $this->teacherMessages(), $this->teacherAttributes());
        } catch (ValidationException $e) {
            return $this->formErrorRedirect($e, 'edit', $teacher->id);
        }

        try {
            if (empty($teacher->qr_token)) {
                $validated['qr_token'] = (string) Str::uuid();
            }

            $validated['phone'] = $validated['phone_number'] ?? $teacher->phone;

            $classId = $validated['school_class_id'] ?? null;
            if ($classId === 'none' || $classId === '') {
                $classId = null;
            }
            unset($validated['school_class_id']);

            $teacher->update($validated);

            if ($classId) {
                SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
                SchoolClass::where('id', $classId)->update(['teacher_id' => $teacher->id]);
            } else {
                SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
            }
        } catch (\Throwable $ex) {
            report($ex);
            return redirect()->route('admin.guru.index')
                ->withInput($request->all())
                ->with('open_modal', 'edit')
                ->with('open_teacher_id', $teacher->id)
                ->with('form_error', 'Gagal menyimpan data guru. Coba lagi.');
        }

        return redirect()->route('admin.guru.index')->with('success', "Data guru {$teacher->name} berhasil disimpan.");
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

            // Lepaskan penugasan kelas
            SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);

            // Soft delete: data masih bisa dipulihkan dari halaman Arsip
            $teacher->delete();

            DB::commit();
            // Notifikasi hapus sengaja memakai flash 'error' supaya tampil
            // MERAH (alert-danger), sama seperti notifikasi hapus di Data
            // Siswa. Flash 'success' (hijau) dipakai untuk tambah/ubah.
            $message = 'Data guru ' . $name . ' berhasil dihapus! Penugasan kelasnya otomatis dilepaskan.';
            return redirect()->route('admin.guru.index')->with('error', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.guru.index')->with('error', 'Gagal menghapus data guru. Silakan coba lagi atau hubungi admin.');
        }
    }

    /**
     * Hapus Seluruh Data Guru Secara Massal dengan proteksi transaksi.
     *
     * PERUBAHAN: ini dulu HAPUS PERMANEN (forceDelete). Sekarang menjadi soft
     * delete supaya sama seperti Data Siswa: seluruh data guru aktif dipindahkan
     * ke Tempat Sampah dan masih bisa dipulihkan.
     *
     * Foto guru SENGAJA tidak dihapus dari storage supaya saat guru dipulihkan
     * fotonya masih utuh. Penugasan kelas dilepas (wali kelas jadi kosong) dan
     * TIDAK dikembalikan otomatis saat dipulihkan - admin perlu menugaskan
     * ulang lewat halaman Ubah.
     */
    public function destroyAll(): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $total = Teacher::count();

            if ($total === 0) {
                DB::rollBack();
                return redirect()->route('admin.guru.index')
                    ->with('error', 'Tidak ada data guru aktif yang bisa dihapus.');
            }

            // Lepaskan seluruh penugasan kelas lebih dulu supaya tidak ada
            // kelas yang menunjuk guru yang sudah masuk Tempat Sampah.
            SchoolClass::whereNotNull('teacher_id')->update(['teacher_id' => null]);

            // Soft delete (bukan permanen) - data bisa dipulihkan dari
            // Tempat Sampah di Pengaturan.
            Teacher::query()->delete();

            DB::commit();

            // Flash 'error' supaya notifikasi tampil MERAH (alert-danger),
            // sama seperti notifikasi hapus di Data Siswa.
            return redirect()->route('admin.guru.index')
                ->with('error', "Seluruh data guru ({$total}) berhasil dihapus dan dipindahkan ke Tempat Sampah di Pengaturan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.guru.index')
                ->with('error', 'Gagal menghapus data guru. Silakan coba lagi atau hubungi admin.');
        }
    }

    /**
     * Unduh Template Excel.
     */
    public function downloadTemplate()
    {
        return \App\Services\DownloadCacheService::downloadTemplate(
            'teachers',
            new TeacherTemplateExport(),
            'Template_Guru.xlsx'
        );
    }

    /**
     * Import Data Guru dari Excel.
     */
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
            $import = new TeachersImport();
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

            if (!empty($errors)) {
                $redirect->with('import_errors', $errors);
            }

            return $redirect;
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('admin.guru.index')
                ->with('error', 'Gagal memproses file Excel. Silakan coba lagi atau hubungi admin.');
        }
    }

    /**
     * Aturan validasi bersama Tambah/Edit guru (Bahasa Indonesia).
     */
    private function teacherRules(?Teacher $teacher = null): array
    {
        $nipUnique = $teacher
            ? 'unique:teachers,nip,' . $teacher->id
            : 'unique:teachers,nip';

        return [
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'regex:/^[0-9]{18}$/', $nipUnique],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'phone_number' => ['required', 'regex:/^[0-9]{10,15}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'school_class_id' => $teacher ? ['nullable'] : ['nullable', 'exists:school_classes,id'],
        ];
    }

    private function teacherMessages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.string' => 'Nama lengkap tidak valid.',
            'name.max' => 'Nama lengkap maksimal 255 karakter.',
            'nip.required' => 'NIP wajib diisi.',
            'nip.regex' => 'NIP harus 18 digit angka.',
            'nip.unique' => 'NIP sudah terdaftar.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin tidak valid.',
            'birth_place.string' => 'Tempat lahir tidak valid.',
            'birth_place.max' => 'Tempat lahir maksimal 100 karakter.',
            'birth_date.date' => 'Tanggal lahir tidak valid.',
            'phone_number.required' => 'Nomor telepon wajib diisi.',
            'phone_number.regex' => 'Nomor telepon harus 10-15 digit angka.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'school_class_id.exists' => 'Penugasan kelas tidak valid.',
        ];
    }

    private function teacherAttributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'nip' => 'NIP',
            'gender' => 'jenis kelamin',
            'birth_place' => 'tempat lahir',
            'birth_date' => 'tanggal lahir',
            'phone_number' => 'nomor telepon',
            'email' => 'email',
            'school_class_id' => 'penugasan kelas',
        ];
    }

    /**
     * Redirect balik ke index dengan isian lama, modal dibuka kembali,
     * dan pesan error siap ditampilkan di dalam form terkait.
     */
    private function formErrorRedirect(ValidationException $e, string $modal, ?int $teacherId = null): RedirectResponse
    {
        $redirect = redirect()->route('admin.guru.index')
            ->withInput($e->validator->getData())
            ->with('open_modal', $modal)
            ->withErrors($e->validator->errors());

        if ($teacherId !== null) {
            $redirect->with('open_teacher_id', $teacherId);
        }

        return $redirect;
    }

    /**
     * Tampilkan halaman arsip (guru yang dihapus / soft deleted).
     */
    public function trash(): View
    {
        $query = Teacher::onlyTrashed()->with('schoolClass');

        $teachers = $query->orderBy('deleted_at', 'desc')->paginate(50);
        $classes = SchoolClass::orderBy('name')->get();

        // Jumlah data di Tempat Sampah, dipakai dialog konfirmasi
        // "Hapus Semua dari Tempat Sampah" (tombolnya nonaktif kalau 0).
        $trashedCount = Teacher::onlyTrashed()->count();

        return view('admin.teachers.trash', compact('teachers', 'classes', 'trashedCount'));
    }

    /**
     * Pulihkan guru dari arsip.
     *
     * Penugasan kelas SENGAJA tidak dikembalikan otomatis: saat guru dihapus,
     * kolom school_classes.teacher_id sudah di-set NULL sehingga informasi
     * "guru ini dulunya wali kelas mana" tidak tersimpan. Admin perlu
     * menugaskan ulang lewat halaman Ubah.
     */
    public function restore($id): RedirectResponse
    {
        // onlyTrashed() wajib dipakai agar global scope SoftDeletes tidak membuat
        // query mustahil (penyebab 404 sebelumnya).
        $teacher = Teacher::onlyTrashed()->findOrFail($id);

        // teachers.nip punya UNIQUE index di database yang TIDAK mengabaikan
        // deleted_at. Tetap dijaga di sini supaya konflik tampil sebagai pesan
        // yang jelas, bukan error 500.
        $duplikat = Teacher::where('nip', $teacher->nip)
            ->where('id', '!=', $teacher->id)
            ->exists();

        if ($duplikat) {
            return redirect()->route('admin.teachers.trash')
                ->with('error', "Gagal dipulihkan: NIP {$teacher->nip} sudah dipakai data guru yang aktif. Perbaiki NIP lebih dulu.");
        }

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

            // Lepaskan penugasan kelas
            SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);

            // Hapus permanen data guru dari database
            $teacher->forceDelete();

            DB::commit();
            // Flash 'error' supaya notifikasi hapus tampil MERAH.
            return redirect()->route('admin.teachers.trash')
                ->with('error', "Data guru {$teacherName} berhasil dihapus permanen!");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.teachers.trash')
                ->with('error', 'Gagal menghapus permanen guru. Silakan coba lagi atau hubungi admin.');
        }
    }

    /**
     * Hapus PERMANEN seluruh isi Tempat Sampah Guru sekaligus.
     *
     * Dipakai oleh tombol "Hapus Semua dari Tempat Sampah" di halaman
     * teachers/trash. Dialog konfirmasinya (dengan dua checkbox) dibuat di
     * sisi tampilan memakai confirmUniversalDelete() dari layout bersama.
     */
    public function destroyAllTrashed(): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $total = Teacher::onlyTrashed()->count();

            if ($total === 0) {
                DB::rollBack();
                return redirect()->route('admin.teachers.trash')
                    ->with('error', 'Tempat Sampah Guru sudah kosong, tidak ada yang bisa dihapus.');
            }

            // Foto dihapus hanya untuk data yang memang dihapus permanen.
            Teacher::onlyTrashed()->whereNotNull('photo')->chunkById(200, function ($teachers) {
                foreach ($teachers as $teacher) {
                    if ($teacher->photo && Storage::disk('public')->exists($teacher->photo)) {
                        Storage::disk('public')->delete($teacher->photo);
                    }
                }
            });

            SchoolClass::whereNotNull('teacher_id')->update(['teacher_id' => null]);
            Teacher::onlyTrashed()->forceDelete();

            DB::commit();
            // Flash 'error' supaya notifikasi hapus tampil MERAH.
            return redirect()->route('admin.teachers.trash')
                ->with('error', "Seluruh data guru di Tempat Sampah ({$total}) berhasil dihapus permanen!");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.teachers.trash')
                ->with('error', 'Gagal menghapus permanen data guru dari Tempat Sampah. Silakan coba lagi atau hubungi admin.');
        }
    }
}
