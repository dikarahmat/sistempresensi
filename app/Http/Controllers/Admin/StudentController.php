<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::with('schoolClass');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->where('school_class_id', $request->class_id);
        }

        // 100 baris per halaman (sebelumnya 50). withQueryString() supaya
        // search & filter kelas tetap melekat di link pagination.
        $students = $query->orderBy('name', 'asc')->paginate(100)->withQueryString();
        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.students.index', compact('students', 'classes'));
    }

    public function create(): View
    {
        $classes = SchoolClass::orderBy('name')->get();
        return view('admin.students.create', compact('classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Aturan validasi Data Siswa (server) + pesan berbahasa Indonesia.
        // Batas kolom DB: nis varchar(30), nisn varchar(30),
        // phone/parent_phone varchar(20) -> aturan aplikasi 4-30 / 10 / 10-15 digit.
        $validated = $request->validate($this->studentRules(null), $this->studentMessages(), $this->studentAttributes());

        $validated['qr_token'] = (string) Str::uuid();
        $validated['status'] = 'Aktif';

        try {
            $student = Student::create($validated);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('admin.students.index')
                ->with('error', 'Gagal menyimpan data siswa. Coba lagi.');
        }

        return redirect()->route('admin.students.index')
            ->with('success', "Siswa {$student->name} berhasil ditambahkan.");
    }

    public function show(Student $student): View
    {
        $student->load('schoolClass');
        return view('admin.students.show', compact('student'));
    }

    public function edit(Student $student): View
    {
        $classes = SchoolClass::orderBy('name')->get();
        return view('admin.students.edit', compact('student', 'classes'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate($this->studentRules($student), $this->studentMessages(), $this->studentAttributes());

        if (empty($student->qr_token)) {
            $validated['qr_token'] = (string) Str::uuid();
        }

        try {
            $student->update($validated);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('admin.students.edit', $student->id)
                ->with('error', 'Gagal menyimpan data siswa. Coba lagi.');
        }

        return redirect()->route('admin.students.show', $student->id)
            ->with('success', "Siswa {$student->name} berhasil diperbarui.");
    }

    /**
     * Aturan validasi Data Siswa (dipakai form Tambah & Edit agar identik).
     *
     * - NIS    : wajib, hanya angka, 4-30 digit (batas kolom students.nis varchar(30)), unik.
     * - NISN   : opsional, jika diisi harus tepat 10 digit angka, unik.
     * - Phone / Parent Phone (No. WhatsApp): opsional, hanya angka, 10-15 digit.
     * - Nama, Kelas, Jenis Kelamin tetap wajib.
     *
     * @param  Student|null  $student  Diisi saat proses Edit (unique mengabaikan id sendiri).
     */
    private function studentRules(?Student $student = null): array
    {
        $nisUnique = 'unique:students,nis' . ($student ? ',' . $student->id : '');
        $nisnUnique = 'unique:students,nisn' . ($student ? ',' . $student->id : '');

        return [
            'school_class_id' => 'required|exists:school_classes,id',
            'name' => 'required|string|max:255',
            'nis' => 'required|regex:/^[0-9]+$/|digits_between:4,30|' . $nisUnique,
            'nisn' => 'nullable|digits:10|' . $nisnUnique,
            'gender' => 'required|in:Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'phone' => 'nullable|digits_between:10,15',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|digits_between:10,15',
        ];
    }

    /**
     * Semua pesan validasi Data Siswa dalam Bahasa Indonesia
     * (tanpa membuat file lang baru, ditulis langsung di validator).
     */
    private function studentMessages(): array
    {
        return [
            'school_class_id.required' => 'Kelas wajib dipilih.',
            'school_class_id.exists' => 'Kelas tidak ditemukan.',
            'name.required' => 'Nama siswa wajib diisi.',
            'name.max' => 'Nama siswa maksimal 255 karakter.',
            'nis.required' => 'NIS wajib diisi.',
            'nis.regex' => 'NIS hanya boleh berisi angka.',
            'nis.digits_between' => 'NIS harus berisi angka saja, minimal 4 dan maksimal 30 digit.',
            'nis.unique' => 'NIS sudah terdaftar.',
            'nisn.digits' => 'NISN harus 10 digit angka.',
            'nisn.unique' => 'NISN sudah terdaftar.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin tidak valid.',
            'birth_place.max' => 'Tempat lahir maksimal 100 karakter.',
            'birth_date.date' => 'Tanggal lahir tidak valid.',
            'phone.digits_between' => 'Nomor WhatsApp harus 10-15 digit angka.',
            'parent_name.max' => 'Nama orang tua/wali maksimal 255 karakter.',
            'parent_phone.digits_between' => 'Nomor WhatsApp harus 10-15 digit angka.',
        ];
    }

    /**
     * Nama attribute berbahasa Indonesia (dipakai bila pesan default tetap muncul).
     */
    private function studentAttributes(): array
    {
        return [
            'school_class_id' => 'kelas',
            'name' => 'nama siswa',
            'nis' => 'NIS',
            'nisn' => 'NISN',
            'gender' => 'jenis kelamin',
            'birth_place' => 'tempat lahir',
            'birth_date' => 'tanggal lahir',
            'address' => 'alamat',
            'phone' => 'nomor WhatsApp',
            'parent_name' => 'nama orang tua/wali',
            'parent_phone' => 'nomor WhatsApp orang tua/wali',
        ];
    }

    /**
     * Cetak Kartu Presensi / Pelajar Massal (PDF).
     */
    public function printCards(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        $query = Student::with('schoolClass')->where('status', 'Aktif');

        if ($request->filled('student_ids') && is_array($request->student_ids)) {
            // Validasi: pastikan semua student_ids adalah integer dan valid
            $validatedIds = array_filter(array_map('intval', $request->student_ids));
            if (!empty($validatedIds)) {
                $query->whereIn('id', $validatedIds);
            }
        } elseif (!$request->boolean('all') && $request->input('print_type') !== 'all' && $request->filled('class_id') && $request->class_id !== 'all') {
            $query->where('school_class_id', $request->class_id);
        }

        $logoPath = public_path(Setting::getLogo());
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $students = collect();
        $query->orderBy('school_class_id')->orderBy('name')->chunk(100, function ($chunk) use ($students) {
            foreach ($chunk as $student) {
                $token = $student->qr_token ?? $student->nis;
                try {
                    $svg = QrCode::size(140)->margin(0)->generate($token);
                    $student->qr_base64 = 'data:image/svg+xml;base64,' . base64_encode($svg);
                } catch (\Throwable $e) {
                    $student->qr_base64 = null;
                }

                $student->photo_base64 = null;
                if ($student->photo && file_exists(public_path('storage/' . $student->photo))) {
                    $pPath = public_path('storage/' . $student->photo);
                    $pMime = mime_content_type($pPath) ?: 'image/jpeg';
                    $student->photo_base64 = 'data:' . $pMime . ';base64,' . base64_encode(file_get_contents($pPath));
                }

                $students->push($student);
            }
        });

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ada data siswa yang dipilih atau ditemukan untuk dicetak.');
        }

        if ($request->isMethod('get') && !$request->has('download')) {
            return view('shared.print-cards', compact(
                'students',
                'schoolName',
                'schoolAddress',
                'activeYear',
                'logoBase64'
            ));
        }

        return \App\Services\DownloadCacheService::downloadMassCards($request);
    }

    public function printCard(Request $request, $id = null): View
    {
        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        if ($id) {
            $students = Student::with('schoolClass')->where('id', $id)->get();
        } elseif ($request->filled('class_id')) {
            $students = Student::with('schoolClass')->where('school_class_id', $request->class_id)->get();
        } else {
            $students = Student::with('schoolClass')->get();
        }

        $logoPath = public_path(Setting::getLogo());
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        foreach ($students as $student) {
            $token = $student->qr_token ?? $student->nis;
            $svg = QrCode::size(140)->margin(0)->generate($token);
            $student->qr_base64 = 'data:image/svg+xml;base64,' . base64_encode($svg);

            $student->photo_base64 = null;
            if ($student->photo && file_exists(public_path('storage/' . $student->photo))) {
                $pPath = public_path('storage/' . $student->photo);
                $pMime = mime_content_type($pPath) ?: 'image/jpeg';
                $student->photo_base64 = 'data:' . $pMime . ';base64,' . base64_encode(file_get_contents($pPath));
            }
        }

        $tahun_ajaran = $activeYear;
        $pengaturan = Setting::first();
        $setting = $pengaturan;

        if ($id) {
            $student = $students->first();
            $siswa = $student;

            return view('admin.students.print-card', compact(
                'student',
                'siswa',
                'students',
                'schoolName',
                'schoolAddress',
                'activeYear',
                'tahun_ajaran',
                'logoBase64',
                'pengaturan',
                'setting'
            ));
        }

        return view('shared.print-cards', compact(
            'students',
            'schoolName',
            'schoolAddress',
            'activeYear',
            'tahun_ajaran',
            'logoBase64',
            'pengaturan',
            'setting'
        ));
    }

    public function downloadQr(Student $student)
    {
        // Validasi: hanya admin dan guru yang bisa download QR
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'guru'])) {
            abort(403, 'Akses ditolak.');
        }

        return \App\Services\DownloadCacheService::downloadQrSvg($student);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file_excel.required' => 'Pilih file Excel siswa yang akan diunggah.',
            'file_excel.mimes' => 'Format file harus berekstensi .xlsx, .xls, atau .csv.',
            'file_excel.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $import = new \App\Imports\StudentsImport();
            Excel::import($import, $request->file('file_excel'));

            $count = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $errors = $import->getErrors();

            // Notifikasi import dibedakan menjadi 3 status:
            // 1. Semua baris masuk      -> hijau
            // 2. Sebagian masuk         -> kuning (peringatan)
            // 3. Tidak ada yang masuk   -> merah
            if ($count > 0 && $skipped === 0) {
                $redirect = redirect()->route('admin.students.index')
                    ->with('success', "Import berhasil: {$count} siswa diimpor.");
            } elseif ($count > 0) {
                $msg = "Import sebagian berhasil: {$count} diimpor, {$skipped} dilewati.";
                if (!empty($errors)) {
                    $msg .= ' Alasan: ' . Str::limit((string) $errors[0], 140);
                }
                $redirect = redirect()->route('admin.students.index')->with('warning', $msg);
            } else {
                $redirect = redirect()->route('admin.students.index')
                    ->with('error', "Import gagal: tidak ada data yang diimpor. {$skipped} baris dilewati karena format tidak sesuai. Periksa format file Excel.");
            }

            if (!empty($errors)) {
                $redirect->with('import_errors', $errors);
            }

            return $redirect;
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('admin.students.index')
                ->with('error', 'Gagal memproses file Excel. Silakan coba lagi atau hubungi admin.');
        }
    }

    public function downloadTemplate()
    {
        return \App\Services\DownloadCacheService::downloadTemplate(
            'students',
            new \App\Exports\StudentTemplateExport(),
            'Template_Siswa.xlsx'
        );
    }

    public function downloadCard(Student $student)
    {
        return \App\Services\DownloadCacheService::downloadSingleCard($student);
    }

    public function destroy(Student $student): RedirectResponse
    {
        $studentName = $student->name;

        // Soft delete: data masih bisa dipulihkan dari halaman Arsip
        $student->delete();

        return redirect()->route('admin.students.index')
            ->with('success', "Siswa {$studentName} berhasil diarsipkan. Anda dapat memulihkannya dari halaman Arsip.");
    }

    /**
     * Menghapus permanen SELURUH isi Arsip siswa dengan proteksi transaksi.
     *
     * Cakupan (dikonfirmasi pemilik sistem): HANYA siswa yang ada di arsip
     * (soft delete) beserta riwayat presensi milik siswa tersebut.
     * Siswa AKTIF tidak disentuh dan tabel users TIDAK disentuh sama sekali.
     */
    public function destroyAll(Request $request): RedirectResponse
    {
        // Dua pernyataan konfirmasi wajib dicentang; divalidasi juga di server
        // sehingga request tanpa tanda konfirmasi akan ditolak.
        $request->validate([
            'confirm_permanent' => 'accepted',
            'confirm_all' => 'accepted',
        ], [
            'confirm_permanent.accepted' => 'Centang pernyataan "Saya memahami data yang dihapus permanen tidak dapat dikembalikan."',
            'confirm_all.accepted' => 'Centang pernyataan "Saya yakin ingin menghapus semua data siswa di arsip."',
        ]);

        DB::beginTransaction();
        try {
            $archivedIds = Student::onlyTrashed()->pluck('id');
            $total = $archivedIds->count();

            if ($total > 0) {
                // Riwayat presensi siswa terarsip dihapus lebih dulu (mencegah
                // pelanggaran foreign key), lalu siswa dihapus permanen.
                \App\Models\Attendance::whereIn('student_id', $archivedIds)->delete();
                Student::withTrashed()->whereIn('id', $archivedIds)->forceDelete();
            }

            DB::commit();
            return redirect()->route('admin.students.trash')
                ->with('success', "Berhasil menghapus permanen {$total} siswa di arsip.");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.students.trash')
                ->with('error', 'Gagal menghapus semua data siswa di arsip. Coba lagi.');
        }
    }

    /**
     * Tampilkan halaman arsip (siswa yang dihapus / soft deleted).
     */
    public function trash(): View
    {
        $students = Student::onlyTrashed()
            ->with('schoolClass')
            ->orderBy('deleted_at', 'desc')
            ->paginate(50);

        $classes = SchoolClass::orderBy('name')->get();

        // Jumlah data di arsip, dipakai modal konfirmasi "Hapus Semua (Permanen)".
        $trashedCount = Student::onlyTrashed()->count();

        return view('admin.students.trash', compact('students', 'classes', 'trashedCount'));
    }

    /**
     * Pulihkan siswa dari arsip.
     */
    public function restore($id): RedirectResponse
    {
        // Wajib pakai onlyTrashed(): global scope SoftDeletes otomatis menambahkan
        // "deleted_at is null" ke setiap query, sehingga whereNotNull('deleted_at')
        // menghasilkan query mustahil (selalu kosong) -> firstOrFail() melempar
        // ModelNotFoundException -> halaman 404 saat tombol "Pulihkan" diklik.
        $student = Student::onlyTrashed()->findOrFail($id);
        $student->restore();

        return redirect()->route('admin.students.trash')
            ->with('success', "Data siswa {$student->name} berhasil dipulihkan!");
    }

    /**
     * Hapus permanen siswa dari arsip.
     */
    public function forceDelete($id): RedirectResponse
    {
        // onlyTrashed() => hanya siswa yang ada di arsip yang bisa dihapus permanen
        $student = Student::onlyTrashed()->findOrFail($id);

        DB::beginTransaction();
        try {
            $studentName = $student->name;

            // Hapus seluruh riwayat presensi terkait
            $student->attendances()->delete();

            // Hapus permanen data siswa dari database
            $student->forceDelete();

            DB::commit();
            return redirect()->route('admin.students.trash')
                ->with('success', "Data siswa {$studentName} berhasil dihapus permanen!");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.students.trash')
                ->with('error', 'Gagal menghapus permanen siswa. Silakan coba lagi atau hubungi admin.');
        }
    }
}
