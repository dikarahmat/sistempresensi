<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TeacherTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\TeachersImport;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Services\DownloadCacheService;
use App\Services\TeacherCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
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
     * Tampilkan detail guru beserta preview kartu presensi.
     */
    public function show(Teacher $teacher): View
    {
        $teacher->load('schoolClass');

        return view('admin.teachers.show', compact('teacher'));
    }

    /**
     * Unduh kartu presensi satu guru (PDF).
     */
    public function downloadCard(Teacher $teacher)
    {
        return TeacherCardService::downloadSingleCardPdf($teacher);
    }

    /**
     * Cetak kartu presensi guru massal (PDF).
     */
    public function printCards(Request $request)
    {
        try {
            if ($request->isMethod('get') && ! $request->boolean('download')) {
                return TeacherCardService::preview();
            }

            return TeacherCardService::downloadMassCards();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.guru.index')
                ->with('error', __('Gagal membuat kartu presensi guru. Silakan coba lagi.'));
        }
    }

    /**
     * Simpan data guru baru.
     * NIP hanya data identitas (opsional). Tidak ada akun User yang dibuat dari NIP.
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

            // NIP kosong disimpan sebagai null
            $validated['nip'] = ! empty($validated['nip']) ? $validated['nip'] : null;

            $classId = $validated['school_class_id'] ?? null;
            if ($classId === 'none' || $classId === '') {
                $classId = null;
            }
            $classId = $classId !== null ? (int) $classId : null;
            unset($validated['school_class_id']);

            $teacher = Teacher::create($validated);

            // Sinkronkan TUGAS KELAS ke Data Kelas (SchoolClass)
            $this->syncTugasKelas($teacher, $classId);
        } catch (ValidationException $e) {
            // Tangani ValidationException dari syncTugasKelas
            return $this->formErrorRedirect($e, 'add');
        } catch (\Throwable $ex) {
            report($ex);

            return redirect()->route('admin.guru.index')
                ->withInput($request->all())
                ->with('open_modal', 'add')
                ->with('form_error', __('Gagal menyimpan data guru. Coba lagi.'));
        }

        return redirect()->route('admin.guru.index')->with('success', __('Data guru :name berhasil disimpan.', ['name' => $teacher->name]));
    }

    /**
     * Update Guru & Penugasan Kelas.
     */
    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        if (! $teacher->exists) {
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

            // NIP kosong disimpan sebagai null
            $validated['nip'] = ! empty($validated['nip']) ? $validated['nip'] : null;

            $classId = $validated['school_class_id'] ?? null;
            if ($classId === 'none' || $classId === '') {
                $classId = null;
            }
            $classId = $classId !== null ? (int) $classId : null;
            unset($validated['school_class_id']);

            $teacher->update($validated);

            // Sinkronkan TUGAS KELAS ke Data Kelas (SchoolClass)
            $this->syncTugasKelas($teacher, $classId);
        } catch (ValidationException $e) {
            // Tangani ValidationException dari syncTugasKelas
            return $this->formErrorRedirect($e, 'edit', $teacher->id);
        } catch (\Throwable $ex) {
            report($ex);

            return redirect()->route('admin.guru.index')
                ->withInput($request->all())
                ->with('open_modal', 'edit')
                ->with('open_teacher_id', $teacher->id)
                ->with('form_error', __('Gagal menyimpan data guru. Coba lagi.'));
        }

        return redirect()->route('admin.guru.index')->with('success', __('Data guru :name berhasil disimpan.', ['name' => $teacher->name]));
    }

    /**
     * Sinkronkan tugas kelas antara Teacher dan SchoolClass.
     * Sumber data tunggal: SchoolClass.teacher_id -> Teacher.schoolClass (TUGAS KELAS).
     * Dipanggil dari TeacherController (Data Guru) dan SchoolClassController (Data Kelas).
     */
    private function syncTugasKelas(Teacher $teacher, ?int $classId): void
    {
        DB::transaction(function () use ($teacher, $classId) {
            $oldClassId = $teacher->schoolClass?->id;

            if ($classId) {
                // Cek apakah kelas sudah punya wali kelas lain
                $existingClass = SchoolClass::where('id', $classId)
                    ->where('teacher_id', '!=', $teacher->id)
                    ->whereNotNull('teacher_id')
                    ->first();

                if ($existingClass) {
                    // Kelas sudah punya wali lain - tidak boleh menimpa
                    throw new ValidationException(
                        Validator::make([], [
                            'school_class_id' => 'Kelas sudah memiliki wali kelas lain. Satu kelas hanya bisa memiliki satu wali kelas.',
                        ])
                    );
                }

                // Lepaskan wali kelas lama dari guru ini (jika ada)
                if ($oldClassId && $oldClassId != $classId) {
                    SchoolClass::where('id', $oldClassId)->update(['teacher_id' => null]);
                }

                // Lepaskan guru lama dari kelas baru (jika ada)
                SchoolClass::where('id', $classId)->where('teacher_id', '!=', $teacher->id)->update(['teacher_id' => null]);

                // Assign guru ke kelas
                SchoolClass::where('id', $classId)->update(['teacher_id' => $teacher->id]);
            } else {
                // Tidak ditugaskan - lepaskan dari kelas lama
                if ($oldClassId) {
                    SchoolClass::where('id', $oldClassId)->update(['teacher_id' => null]);
                }
            }
        });
    }

    /**
     * Hapus Data Guru dengan proteksi transaksi.
     */
    public function destroy(Request $request, Teacher $teacher): RedirectResponse
    {
        if (! $teacher->exists) {
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

            // Lepaskan penugasan kelas (sinkronkan ke SchoolClass)
            SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);

            // Soft delete: data masih bisa dipulihkan dari halaman Arsip
            $teacher->delete();

            DB::commit();
            // Notifikasi hapus memakai flash 'deleted' supaya tampil MERAH
            // (alert-danger) + ikon tempat sampah, sama seperti notifikasi
            // hapus lainnya. Flash 'success' (hijau) untuk tambah/ubah.
            $message = __('Data guru :name berhasil dihapus! Penugasan kelasnya otomatis dilepaskan.', ['name' => $name]);

            return redirect()->route('admin.guru.index')->with('deleted', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->route('admin.guru.index')->with('error', __('Gagal menghapus data guru. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Hapus Seluruh Data Guru Secara Massal dengan proteksi transaksi.
     *
     * Soft delete: seluruh data guru aktif dipindahkan ke Tempat Sampah dan
     * masih bisa dipulihkan. Foto guru sengaja tidak dihapus dari storage.
     * Penugasan kelas dilepas dan TIDAK dikembalikan otomatis saat dipulihkan.
     */
    public function destroyAll(): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $total = Teacher::count();

            if ($total === 0) {
                DB::rollBack();

                return redirect()->route('admin.guru.index')
                    ->with('error', __('Tidak ada data guru aktif yang bisa dihapus.'));
            }

            // Lepaskan seluruh penugasan kelas lebih dulu supaya tidak ada
            // kelas yang menunjuk guru yang sudah masuk Tempat Sampah.
            SchoolClass::whereNotNull('teacher_id')->update(['teacher_id' => null]);

            // Soft delete (bukan permanen) - data bisa dipulihkan dari
            // Tempat Sampah di Pengaturan.
            Teacher::query()->delete();

            DB::commit();

            return redirect()->route('admin.guru.index')
                ->with('deleted', __('Seluruh data guru (:total) berhasil dihapus dan dipindahkan ke Tempat Sampah di Pengaturan.', ['total' => $total]));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->route('admin.guru.index')
                ->with('error', __('Gagal menghapus data guru. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Unduh Template Excel.
     */
    public function downloadTemplate()
    {
        return DownloadCacheService::downloadTemplate(
            'teachers',
            new TeacherTemplateExport,
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
            $import = new TeachersImport;
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

            if (! empty($errors)) {
                $redirect->with('import_errors', $errors);
            }

            return $redirect;
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.guru.index')
                ->with('error', __('Gagal memproses file Excel. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Aturan validasi bersama Tambah/Edit guru (Bahasa Indonesia).
     * NIP opsional: boleh kosong, tapi kalau diisi harus 16 atau 18 digit dan unik.
     */
    private function teacherRules(?Teacher $teacher = null): array
    {
        $nipUnique = $teacher
            ? 'unique:teachers,nip,'.$teacher->id
            : 'unique:teachers,nip';

        return [
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'regex:/^([0-9]{16}|[0-9]{18})$/', $nipUnique],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'phone_number' => ['required', 'regex:/^(\+62|0)[0-9]{9,14}$/'],
            'school_class_id' => ['nullable', 'exists:school_classes,id'],
        ];
    }

    private function teacherMessages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.string' => 'Nama lengkap tidak valid.',
            'name.max' => 'Nama lengkap maksimal 255 karakter.',
            'nip.regex' => 'NIP harus 16 atau 18 digit angka (atau dikosongkan).',
            'nip.unique' => 'NIP sudah terdaftar.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin tidak valid.',
            'birth_place.string' => 'Tempat lahir tidak valid.',
            'birth_place.max' => 'Tempat lahir maksimal 100 karakter.',
            'birth_date.date' => 'Tanggal lahir tidak valid.',
            'phone_number.required' => 'Nomor telepon wajib diisi.',
            'phone_number.regex' => 'Nomor telepon harus 10-15 digit angka.',
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
     * Penugasan kelas SENGAJA tidak dikembalikan otomatis: admin perlu
     * menugaskan ulang lewat halaman Ubah.
     */
    public function restore($id): RedirectResponse
    {
        // onlyTrashed() wajib dipakai agar global scope SoftDeletes tidak membuat
        // query mustahil (penyebab 404 sebelumnya).
        $teacher = Teacher::onlyTrashed()->findOrFail($id);

        // teachers.nip punya UNIQUE index yang TIDAK mengabaikan deleted_at.
        // Cek hanya bila NIP terisi (guru tanpa NIP boleh banyak).
        if (! empty($teacher->nip)) {
            $duplikat = Teacher::where('nip', $teacher->nip)
                ->where('id', '!=', $teacher->id)
                ->exists();

            if ($duplikat) {
                return redirect()->route('admin.teachers.trash')
                    ->with('error', __('Gagal dipulihkan: NIP :nip sudah dipakai data guru yang aktif. Perbaiki NIP lebih dulu.', ['nip' => $teacher->nip]));
            }
        }

        $teacher->restore();

        return redirect()->route('admin.teachers.trash')
            ->with('success', __('Data guru :name berhasil dipulihkan!', ['name' => $teacher->name]));
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

            return redirect()->route('admin.teachers.trash')
                ->with('deleted', __('Data guru :name berhasil dihapus permanen!', ['name' => $teacherName]));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->route('admin.teachers.trash')
                ->with('error', __('Gagal menghapus permanen guru. Silakan coba lagi atau hubungi admin.'));
        }
    }

    /**
     * Hapus PERMANEN seluruh isi Tempat Sampah Guru sekaligus.
     */
    public function destroyAllTrashed(): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $total = Teacher::onlyTrashed()->count();

            if ($total === 0) {
                DB::rollBack();

                return redirect()->route('admin.teachers.trash')
                    ->with('error', __('Tempat Sampah Guru sudah kosong, tidak ada yang bisa dihapus.'));
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

            return redirect()->route('admin.teachers.trash')
                ->with('deleted', __('Seluruh data guru di Tempat Sampah (:total) berhasil dihapus permanen!', ['total' => $total]));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->route('admin.teachers.trash')
                ->with('error', __('Gagal menghapus permanen data guru dari Tempat Sampah. Silakan coba lagi atau hubungi admin.'));
        }
    }
}
