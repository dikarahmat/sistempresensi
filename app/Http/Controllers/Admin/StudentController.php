<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        // Data siswa hanya 4 kolom: NISN (wajib, 10 digit angka, unik),
        // Nama Lengkap, Kelas, dan Jenis Kelamin.
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
     * Data siswa yang dipakai aplikasi hanya 4 kolom:
     * - NISN : wajib, tepat 10 digit angka, unik (identitas tunggal siswa).
     * - Nama Lengkap, Kelas, Jenis Kelamin: wajib.
     *
     * @param  Student|null  $student  Diisi saat proses Edit (unique mengabaikan id sendiri).
     */
    private function studentRules(?Student $student = null): array
    {
        $nisnUnique = 'unique:students,nisn' . ($student ? ',' . $student->id : '');

        return [
            'school_class_id' => 'required|exists:school_classes,id',
            'name' => 'required|string|max:255',
            'nisn' => 'required|digits:10|' . $nisnUnique,
            'gender' => 'required|in:Laki-laki,Perempuan',
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
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.digits' => 'NISN harus 10 digit angka.',
            'nisn.unique' => 'NISN sudah terdaftar.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin tidak valid.',
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
            'nisn' => 'NISN',
            'gender' => 'jenis kelamin',
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
                $token = $student->nisn ?: $student->qr_token;
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
            $token = $student->nisn ?: $student->qr_token;
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

        // Soft delete: data masih bisa dipulihkan dari halaman Arsip.
        // Dibungkus try/catch supaya bila ada kegagalan sistem, pengguna
        // tetap mendapat pesan yang jelas dan errornya tercatat di log
        // (tidak lagi halaman 500 tanpa penjelasan).
        try {
            $student->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal menghapus data siswa: ' . $e->getMessage(), [
                'student_id' => $student->id,
            ]);

            return $this->backToStudentIndex('Gagal menghapus data siswa. Coba lagi.');
        }

        // Notifikasi hapus memakai flash 'error' (alert-danger/merah), bukan
        // 'success' (hijau) - flash hijau dipakai untuk tambah/ubah data.
        return $this->backToStudentIndex(
            "Data siswa {$studentName} berhasil dihapus. Data bisa dipulihkan dari Tempat Sampah di Pengaturan.",
            'error'
        );
    }

    /**
     * Kembali ke daftar siswa sambil MEMPERTAHANKAN filter & pencarian aktif.
     *
     * Parameter yang sedang dipakai ikut disalin ke query string redirect,
     * sehingga kata kunci pencarian dan filter kelas tidak ikut hilang setelah
     * menghapus data. `withInput()` saja tidak cukup karena hanya mengisi
     * session untuk old(), bukanquerystring.
     */
    private function backToStudentIndex(string $message, string $type = 'success'): RedirectResponse
    {
        $params = [];
        foreach (['search', 'class_id', 'status', 'sort', 'direction', 'per_page'] as $key) {
            if (request()->filled($key)) {
                $params[$key] = request($key);
            }
        }

        $url = route('admin.students.index') . ($params ? '?' . http_build_query($params) : '');

        return redirect($url)->with($type, $message);
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
            'confirm_all.accepted' => 'Centang pernyataan "Saya yakin ingin menghapus semua data siswa di Tempat Sampah."',
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
            // Notifikasi hasil HAPUS sengaja memakai flash 'error' supaya tampil
            // MERAH (alert-danger) sesuai aturan warna notifikasi, bukan hijau.
            return redirect()->route('admin.students.trash')
                ->with('error', "Berhasil menghapus permanen {$total} siswa di Tempat Sampah.");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.students.trash')
                ->with('error', 'Gagal menghapus semua data siswa di Tempat Sampah. Coba lagi.');
        }
    }

    /**
     * Hapus SELURUH data siswa yang masih AKTIF (hapus massal dari toolbar
     * Data Siswa), dengan proteksi transaksi.
     *
     * Berbeda dengan destroyAll() di atas yang mengosongkan Tempat Sampah:
     * method ini memakai SOFT DELETE, jadi seluruh siswa aktif dipindahkan ke
     * Tempat Sampah dan masih bisa dipulihkan dari Pengaturan.
     *
     * Jaminan:
     *  - tabel `users` TIDAK disentuh sama sekali (akun guru/admin aman);
     *  - hanya siswa AKTIF yang dikenai, siswa di Tempat Sampah tidak berubah;
     *  - dua pernyataan konfirmasi wajib dicentang (divalidasi di server).
     */
    public function destroyAllActive(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm_active' => 'accepted',
            'confirm_all' => 'accepted',
        ], [
            'confirm_active.accepted' => 'Centang pernyataan "Saya memahami bahwa seluruh data siswa aktif akan dihapus dan dapat dipulihkan dari Tempat Sampah di Pengaturan."',
            'confirm_all.accepted' => 'Centang pernyataan "Saya yakin ingin menghapus semua data siswa aktif."',
        ]);

        DB::beginTransaction();
        try {
            $total = Student::count();

            if ($total > 0) {
                // Riwayat presensi dibersihkan terlebih dahulu (mencegah
                // pelanggaran foreign key), lalu siswa di-soft-delete sehingga
                // masuk ke Tempat Sampah dan bisa dipulihkan.
                \App\Models\Attendance::whereIn('student_id', Student::query()->select('id'))->delete();
                Student::query()->delete();
            }

            DB::commit();

            return $this->backToStudentIndex(
                "Seluruh data siswa aktif ({$total} data) berhasil dihapus dan dipindahkan ke Tempat Sampah.",
                'error'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return $this->backToStudentIndex('Gagal menghapus seluruh data siswa aktif. Coba lagi.', 'error');
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
            // Notifikasi hasil HAPUS sengaja memakai flash 'error' supaya tampil
            // MERAH (alert-danger) sesuai aturan warna notifikasi, bukan hijau.
            return redirect()->route('admin.students.trash')
                ->with('error', "Data siswa {$studentName} berhasil dihapus permanen!");
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.students.trash')
                ->with('error', 'Gagal menghapus permanen siswa. Silakan coba lagi atau hubungi admin.');
        }
    }
}
