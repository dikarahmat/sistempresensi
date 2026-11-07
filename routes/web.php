<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\RekapController as AdminRekapController;
use App\Http\Controllers\Admin\RekapGuruController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\ScannerController as AdminScannerController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\UserAccountController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================================================
// PUBLIK: root, autentikasi, bahasa
// ============================================================================

// Redirect root ke dashboard sesuai role (admin/guru/piket), atau ke login
Route::get('/', [AuthController::class, 'redirectRoot'])->name('root');

// Autentikasi
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('throttle:10,1');

// Bahasa (id / en) - dipanggil dari pop-up kartu akun di sidebar bersama.
// - POST + CSRF, bukan link GET.
// - Menerima dua bentuk sekaligus: POST /locale/en (parameter URL) atau
//   POST /locale dengan input "lang" di body form.
// - Hanya "id" dan "en" yang diterima; selain itu 404.
// - Pilihan disimpan di session, lalu user dikembalikan ke halaman asal.
Route::post('/locale/{lang?}', function (Request $request, ?string $lang = null) {
    $lang = $request->input('lang', $lang);

    abort_unless(in_array($lang, ['id', 'en'], true), 404);

    $request->session()->put('locale', $lang);

    return redirect()->back();
})->name('locale.set');

// ============================================================================
// 1. GROUP ADMIN
// ============================================================================
Route::prefix('admin')->name('admin.')->middleware(['role:admin'])->group(function () {
    // Utama
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Presensi / Absensi
    Route::get('/absensi', [AttendanceController::class, 'index'])->name('absensi.index');
    Route::get('/absensi-view', [AttendanceController::class, 'index'])->name('absensi');
    Route::get('/presensi', [AttendanceController::class, 'index'])->name('presensi.index');
    // Route statis WAJIB didaftarkan sebelum route berparameter dinamis di bawahnya,
    // supaya /admin/absensi/kiosk tidak ditangkap {schoolClass} (showClass('kiosk') -> TypeError).
    Route::get('/absensi/kiosk', [AdminScannerController::class, 'kiosk'])->name('absensi.kiosk');
    Route::post('/absensi/override', [AttendanceController::class, 'override'])->name('absensi.override')->middleware('throttle:20,1');
    Route::get('/absensi/{schoolClass}', [AttendanceController::class, 'showClass'])->name('absensi.show');

    // ------------------------------------------------------------------
    // KEHADIRAN (dropdown sidebar: Kehadiran Siswa & Kehadiran Guru)
    //   - Kehadiran Siswa : admin.kehadiran.siswa  (/admin/kehadiran/siswa)
    //                       admin.kehadiran        (/admin/kehadiran, alias lama tetap hidup)
    //   - Kehadiran Guru  : admin.kehadiran.guru   (/admin/kehadiran/guru)
    // Route statis WAJIB di atas /kehadiran/{schoolClass}.
    // ------------------------------------------------------------------
    Route::get('/kehadiran', [AttendanceController::class, 'kehadiran'])->name('kehadiran');
    Route::get('/kehadiran/siswa', [AttendanceController::class, 'kehadiran'])->name('kehadiran.siswa');
    Route::get('/kehadiran/guru', [AttendanceController::class, 'kehadiranGuru'])->name('kehadiran.guru');
    Route::get('/kehadiran/guru/{teacher}/history', [AttendanceController::class, 'teacherHistory'])->name('kehadiran.guru-history');
    Route::post('/kehadiran/guru/{teacher}/history/pdf', [AttendanceController::class, 'printGuruHistory'])->name('kehadiran.guru-history.pdf');
    Route::post('/kehadiran/override', [AttendanceController::class, 'override'])->name('kehadiran.override')->middleware('throttle:20,1');
    // Riwayat Presensi per Siswa (akses dari menu Kehadiran)
    Route::get('/kehadiran/siswa/{student}/history', [AttendanceController::class, 'studentHistory'])->name('kehadiran.student-history');
    Route::get('/kehadiran/{schoolClass}', [AttendanceController::class, 'kehadiranDetail'])->name('kehadiran.detail');

    // ------------------------------------------------------------------
    // REKAP (dropdown sidebar: Rekap Siswa & Rekap Guru)
    //   - Rekap Siswa : admin.rekap.siswa (/admin/rekap/siswa)
    //                   admin.rekap       (/admin/rekap, alias lama tetap hidup)
    //   - Rekap Guru  : admin.rekap.guru  (/admin/rekap/guru)
    // ------------------------------------------------------------------
    Route::get('/rekap', [AdminRekapController::class, 'index'])->name('rekap');
    Route::get('/rekap/siswa', [AdminRekapController::class, 'index'])->name('rekap.siswa');
    Route::get('/rekap/export-excel', [AdminRekapController::class, 'exportExcel'])->name('rekap.export-excel')->middleware('throttle:10,1');
    Route::get('/rekap/export-pdf', [AdminRekapController::class, 'exportPdf'])->name('rekap.export-pdf')->middleware('throttle:10,1');
    Route::get('/rekap/student-search', [AdminRekapController::class, 'studentSearch'])->name('rekap.student-search');
    // Rekap Guru (statis, harus di atas route berparameter)
    Route::get('/rekap/guru', [RekapGuruController::class, 'index'])->name('rekap.guru');
    Route::get('/rekap/guru/pdf', [RekapGuruController::class, 'exportPdf'])->name('rekap.guru.pdf')->middleware('throttle:10,1');
    Route::get('/rekap/guru/excel', [RekapGuruController::class, 'exportExcel'])->name('rekap.guru.excel')->middleware('throttle:10,1');
    // Cetak rekap guru lewat modal (POST) -> dilayani RekapGuruController@printRekap
    Route::post('/rekap/guru/print', [RekapGuruController::class, 'printRekap'])->name('rekap.guru.print')->middleware('throttle:rekap-print');
    Route::post('/rekap/print', [AdminRekapController::class, 'printRekap'])->name('rekap.print')->middleware('throttle:rekap-print');

    // Scanner & Mode Gerbang / Kiosk Absensi
    Route::get('/scanner', [AdminScannerController::class, 'index'])->name('scanner');
    Route::get('/kiosk', [AdminScannerController::class, 'kiosk'])->name('kiosk');
    Route::post('/scanner/process', [AdminScannerController::class, 'processScan'])->name('scanner.process')->middleware('throttle:30,1');

    // URL LAMA modul Tahun Ajaran & Hari Libur. Modul ini kini hidup di bawah
    // Pengaturan (admin/settings/...), tapi URL lama tidak boleh mati diam-diam:
    // tetap menjawab 301 (permanen) ke URL baru. Nama route, controller, method,
    // middleware, dan HTTP method modul TIDAK diubah.
    Route::get('academic-years', fn () => redirect()->route('admin.academic-years.index', [], 301));
    Route::get('holidays', fn () => redirect()->route('admin.holidays.index', [], 301));
    Route::get('holidays/template', fn () => redirect()->route('admin.holidays.template', [], 301));

    // ------------------------------------------------------------------
    // PENGATURAN (dropdown sidebar):
    //   1. Informasi Sekolah : admin.settings.index         (/admin/settings)
    //   2. Tahun Ajaran      : admin.academic-years.index   (/admin/settings/academic-years)
    //   3. Hari Libur        : admin.holidays.index         (/admin/settings/hari-libur)
    //   4. Tempat Sampah     : admin.trash.index            (/admin/settings/tempat-sampah)
    // Route statis (toggle-active, template) didaftarkan lebih dulu supaya tidak
    // tertangkap route resource yang berparameter.
    // ------------------------------------------------------------------
    Route::prefix('settings')->group(function () {
        // Master Tahun Ajaran
        Route::post('academic-years/{academic_year}/toggle-active', [AcademicYearController::class, 'toggleActive'])->name('academic-years.toggle-active');
        Route::resource('academic-years', AcademicYearController::class)->except(['show', 'create', 'edit']);

        // Master Hari Libur & Import
        // ->names('holidays') dipakai AGAR nama route tetap admin.holidays.*
        // Hanya URL (path) yang berubah, nama route tidak.
        Route::get('hari-libur/template', [HolidayController::class, 'template'])->name('holidays.template');
        Route::get('hari-libur/import', function () {
            return redirect()->route('admin.holidays.index');
        });
        Route::post('hari-libur/import', [HolidayController::class, 'import'])->name('holidays.import')->middleware('throttle:10,1');
        Route::delete('hari-libur/destroy-all', [HolidayController::class, 'destroyAll'])->name('holidays.destroy-all');
        Route::resource('hari-libur', HolidayController::class)->names('holidays')->except(['show', 'create', 'edit']);

        // Tempat Sampah: pintu masuk dari dropdown Pengaturan.
        // Sementara diarahkan ke Tempat Sampah Siswa (halaman trash siswa/guru/kelas
        // tetap di URL masing-masing). Kalau nanti dibuat halaman gabungan, cukup
        // ganti redirect ini dengan controller-nya; nama route tetap admin.trash.index.
        Route::get('tempat-sampah', fn () => redirect()->route('admin.students.trash'))->name('trash.index');
    });

    // Pengaturan Sistem Dinamis (Informasi Sekolah + jadwal presensi)
    Route::get('/pengaturan/jadwal', [SettingController::class, 'index'])->name('pengaturan.jadwal');
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update')->middleware('throttle:10,1');

    // Master Kelas, Import, & Hapus Semua
    Route::get('/kelas', [SchoolClassController::class, 'index'])->name('kelas.index');
    Route::delete('classes/destroy-all', [SchoolClassController::class, 'destroyAllActive'])->name('classes.destroy-all');
    Route::get('classes/template', [SchoolClassController::class, 'template'])->name('classes.template');
    Route::get('classes/import', function () {
        return redirect()->route('admin.classes.index');
    });
    Route::post('classes/import', [SchoolClassController::class, 'import'])->name('classes.import')->middleware('throttle:10,1');

    // Soft Delete Routes untuk Kelas (Tempat Sampah).
    // Route statis WAJIB didaftarkan SEBELUM Route::resource('classes') di bawah
    // supaya tidak tertangkap parameter {class}. classes.destroy-all di atas
    // tetap utuh untuk aksi "Hapus Semua" di halaman Data Kelas.
    Route::delete('classes/trash-destroy-all', [SchoolClassController::class, 'destroyAllTrashed'])->name('classes.trash-destroy-all');
    Route::get('classes/trash', [SchoolClassController::class, 'trash'])->name('classes.trash');
    Route::post('classes/{id}/restore', [SchoolClassController::class, 'restore'])->name('classes.restore');
    Route::delete('classes/{id}/force-delete', [SchoolClassController::class, 'forceDelete'])->name('classes.force-delete');

    Route::resource('classes', SchoolClassController::class)->except(['show', 'create', 'edit']);

    // Master Siswa, Import, Upload Foto ZIP, & Hapus Semua
    Route::get('/siswa', [AdminStudentController::class, 'index'])->name('siswa.index');
    Route::match(['get', 'post'], '/siswa/generate-qr', [AdminStudentController::class, 'printCards'])->name('siswa.generate-qr');
    Route::delete('students/destroy-all', [AdminStudentController::class, 'destroyAllActive'])->name('students.destroy-all');
    // Hapus massal siswa AKTIF dari toolbar Data Siswa (soft delete -> Tempat Sampah).
    // Route statis WAJIB didaftarkan sebelum Route::resource('students') di bawah
    // supaya tidak tertangkap parameter {student}.
    Route::delete('students/destroy-all-active', [AdminStudentController::class, 'destroyAllActive'])->name('students.destroy-all-active');
    // Kosongkan Tempat Sampah Siswa: hapus PERMANEN seluruh siswa yang sudah
    // soft delete (destroyAllActive = pindahkan ke sampah; destroyAllTrashed =
    // hapus permanen isi sampah). Diberikan konfirmasi di view trash.
    Route::delete('students/trash-destroy-all', [AdminStudentController::class, 'destroyAllTrashed'])->name('students.trash-destroy-all');
    Route::match(['get', 'post'], 'students/print-cards', [AdminStudentController::class, 'printCards'])->name('students.print-cards');
    Route::get('students/template', [AdminStudentController::class, 'downloadTemplate'])->name('students.template');
    Route::post('students/import', [AdminStudentController::class, 'import'])->name('students.import')->middleware('throttle:10,1');
    Route::get('students/{student}/download-card', [AdminStudentController::class, 'downloadCard'])->name('students.download-card');
    // Alias "card" (sama seperti di group guru) -> unduh kartu siswa.
    Route::get('students/{student}/card', [AdminStudentController::class, 'downloadCard'])->name('students.card');
    Route::get('students/{id}/print-card', [AdminStudentController::class, 'printCard'])->name('students.print-card');
    Route::get('students-print-all', [AdminStudentController::class, 'printCard'])->name('students.print-all');

    // Soft Delete Routes Siswa
    Route::get('students/trash', [AdminStudentController::class, 'trash'])->name('students.trash');
    Route::post('students/{id}/restore', [AdminStudentController::class, 'restore'])->name('students.restore');
    Route::delete('students/{id}/force-delete', [AdminStudentController::class, 'forceDelete'])->name('students.force-delete');

    Route::resource('students', AdminStudentController::class)->except(['edit']);

    // Manajemen Data Guru (/admin/guru)
    // Route statis WAJIB di atas Route::resource('guru') supaya tidak tertangkap {teacher}.
    Route::delete('guru/destroy-all', [TeacherController::class, 'destroyAllActive'])->name('guru.destroy-all');
    Route::delete('guru/trash-destroy-all', [TeacherController::class, 'destroyAllTrashed'])->name('guru.trash-destroy-all');
    Route::match(['get', 'post'], 'guru/print-cards', [TeacherController::class, 'printCards'])->name('guru.print-cards');
    Route::get('guru/template', [TeacherController::class, 'downloadTemplate'])->name('guru.template');
    Route::post('guru/import', [TeacherController::class, 'import'])->name('guru.import')->middleware('throttle:10,1');
    Route::get('guru/{teacher}/download-card', [TeacherController::class, 'downloadCard'])->name('guru.download-card');
    Route::resource('guru', TeacherController::class)->parameters(['guru' => 'teacher'])->only(['index', 'show', 'store', 'update', 'destroy']);

    // Kompatibilitas Route Teachers (+ Tempat Sampah Guru)
    Route::delete('teachers/destroy-all', [TeacherController::class, 'destroyAll'])->name('teachers.destroy-all');
    Route::get('teachers/template', [TeacherController::class, 'downloadTemplate'])->name('teachers.template');
    Route::post('teachers/import', [TeacherController::class, 'import'])->name('teachers.import')->middleware('throttle:10,1');

    // WAJIB didaftarkan sebelum Route::resource('teachers') di bawah supaya tidak
    // tertangkap parameter {teacher} pada route DELETE teachers/{teacher}.
    Route::delete('teachers/trash-destroy-all', [TeacherController::class, 'destroyAllTrashed'])->name('teachers.trash-destroy-all');

    Route::resource('teachers', TeacherController::class)->only(['index', 'store', 'update', 'destroy']);

    // Soft Delete Routes untuk Teachers
    Route::get('teachers/trash', [TeacherController::class, 'trash'])->name('teachers.trash');
    Route::post('teachers/{id}/restore', [TeacherController::class, 'restore'])->name('teachers.restore');
    Route::delete('teachers/{id}/force-delete', [TeacherController::class, 'forceDelete'])->name('teachers.force-delete');

    // Manajemen Role & Permission
    Route::get('/roles-permissions', [RolePermissionController::class, 'index'])->name('roles.index');
    Route::post('/roles-permissions', [RolePermissionController::class, 'update'])->name('roles.update');

    // ==========================================================================
    // KELOLA AKUN (khusus ADMIN)
    // Halaman pengelolaan akun login (tambah, edit, reset password).
    // Berada DI DALAM group ini sehingga otomatis terkunci middleware
    // role:admin - role lain (guru, piket) menerima 403.
    // Akun hanya dibuat lewat sini; tidak ada registrasi publik.
    // ==========================================================================
    Route::prefix('kelola-akun')->name('accounts.')->group(function () {
        Route::get('/', [UserAccountController::class, 'index'])->name('index');
        Route::post('/', [UserAccountController::class, 'store'])->name('store');
        Route::put('/{user}/update', [UserAccountController::class, 'update'])->name('update');
        // Endpoint khusus admin: dekripsi password terenkripsi untuk ditampilkan
        // di modal Edit (read-only, hanya dikembalikan sebagai JSON).
        Route::get('/{user}/password', [UserAccountController::class, 'getEncryptedPassword'])->name('password');
        Route::delete('/{user}', [UserAccountController::class, 'destroy'])->name('destroy');
    });
});

// ============================================================================
// 2. GROUP GURU (DASHBOARD GURU)
// ============================================================================
// Memakai controller, view, dan komponen yang SAMA PERSIS dengan admin
// (single source of truth). Yang berbeda hanya hak akses: guru read-only,
// kecuali modul Presensi (scanner & mode gerbang) yang tetap penuh fungsional.
// Route khusus admin (tambah/edit/hapus/import/pengaturan) TIDAK didaftarkan
// di sini, sehingga guru otomatis menerima HTTP 403 bila mencoba mengaksesnya.
// ============================================================================
Route::prefix('guru')->name('guru.')->middleware(['role:guru'])->group(function () {
    // Dashboard (identik dashboard admin)
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // PRESENSI - identik admin (absensi harian, detail kelas, scanner, mode gerbang)
    Route::get('/absensi', [AttendanceController::class, 'index'])->name('absensi.index');
    Route::get('/absensi-view', [AttendanceController::class, 'index'])->name('absensi');
    Route::get('/presensi', [AttendanceController::class, 'index'])->name('presensi.index');
    // Route statis WAJIB didaftarkan sebelum route berparameter dinamis di bawahnya,
    // supaya /guru/absensi/kiosk tidak ditangkap {schoolClass}.
    Route::get('/absensi/kiosk', [AdminScannerController::class, 'kiosk'])->name('absensi.kiosk');
    Route::get('/absensi/{schoolClass}', [AttendanceController::class, 'showClass'])->name('absensi.show');
    Route::get('/scanner', [AdminScannerController::class, 'index'])->name('scanner');
    Route::get('/kiosk', [AdminScannerController::class, 'kiosk'])->name('kiosk');
    Route::post('/scanner/process', [AdminScannerController::class, 'processScan'])->name('scanner.process')->middleware('throttle:30,1');

    // KEHADIRAN SISWA - identik admin tetapi READ-ONLY.
    // Menu guru (Kehadiran Guru) sengaja tidak didaftarkan untuk role guru.
    Route::get('/kehadiran', [AttendanceController::class, 'kehadiran'])->name('kehadiran');
    Route::get('/kehadiran/siswa', [AttendanceController::class, 'kehadiran'])->name('kehadiran.siswa');
    Route::get('/kehadiran/siswa/{student}/history', [AttendanceController::class, 'studentHistory'])->name('kehadiran.student-history');
    Route::get('/kehadiran/{schoolClass}', [AttendanceController::class, 'kehadiranDetail'])->name('kehadiran.detail');

    // REKAP SISWA - identik admin (filter + unduh Excel/PDF).
    // Rekap Guru sengaja tidak didaftarkan untuk role guru.
    Route::get('/rekap', [AdminRekapController::class, 'index'])->name('rekap');
    Route::get('/rekap/siswa', [AdminRekapController::class, 'index'])->name('rekap.siswa');
    Route::get('/rekap/export-excel', [AdminRekapController::class, 'exportExcel'])->name('rekap.export-excel')->middleware('throttle:10,1');
    Route::get('/rekap/export-pdf', [AdminRekapController::class, 'exportPdf'])->name('rekap.export-pdf')->middleware('throttle:10,1');
    Route::get('/rekap/student-search', [AdminRekapController::class, 'studentSearch'])->name('rekap.student-search');
    Route::post('/rekap/print', [AdminRekapController::class, 'printRekap'])->name('rekap.print')->middleware('throttle:rekap-print');

    // SISWA - identik admin, READ-ONLY (detail + cetak kartu & unduh QR)
    Route::get('/siswa', [AdminStudentController::class, 'index'])->name('students.index');
    // Route statis didaftarkan lebih dulu agar tidak tertangkap {student}.
    Route::match(['get', 'post'], '/siswa/print-cards', [AdminStudentController::class, 'printCards'])->name('students.print-cards');
    Route::get('/siswa/{student}/download-card', [AdminStudentController::class, 'downloadCard'])->name('students.download-card');
    Route::get('/siswa/{student}/card', [AdminStudentController::class, 'downloadCard'])->name('students.card');
    Route::get('/siswa/{student}', [AdminStudentController::class, 'show'])->name('students.show');

    // KELAS - identik admin, READ-ONLY (dua nama, sama seperti admin)
    Route::get('/kelas', [SchoolClassController::class, 'index'])->name('kelas.index');
    Route::get('/classes', [SchoolClassController::class, 'index'])->name('classes.index');

    // GURU - cetak kartu presensi guru (dilayani oleh controller yang sama)
    // Nama lengkap route: guru.guru.print-cards dan guru.guru.download-card
    Route::match(['get', 'post'], '/kartu', [TeacherController::class, 'printCards'])->name('guru.print-cards');
    Route::get('/kartu/download/{teacher}', [TeacherController::class, 'downloadCard'])->name('guru.download-card');
});

// ============================================================================
// 3. GROUP GURU PIKET
// ============================================================================
// Akses HANYA KE TIGA MODUL: DASHBOARD, KEHADIRAN, dan PRESENSI (scan QR /
// mode gerbang). Route Rekap (termasuk export/cetak), Siswa, Guru, Kelas,
// Pengaturan, Tahun Ajaran, Hari Libur, Tempat Sampah, Kelola Akun, maupun
// override absensi TIDAK didaftarkan di sini - sehingga piket otomatis
// menerima HTTP 403 untuk SEMUA method (GET/POST/PUT/PATCH/DELETE) bila
// URL diketik manual. View yang dipakai tetap view bersama admin/guru;
// menu disesuaikan lewat partials/sidebar.blade.php & bottom nav.
// ============================================================================
Route::prefix('piket')->name('piket.')->middleware(['role:piket'])->group(function () {
    // DASHBOARD - ringkasan kehadiran + pintasan Presensi & Catatan Kehadiran.
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // PRESENSI - identik admin/guru (absensi harian, detail kelas, scanner, kiosk)
    // Route statis WAJIB didaftarkan sebelum route berparameter dinamis,
    // supaya /piket/absensi/kiosk tidak ditangkap {schoolClass}.
    Route::get('/absensi', [AttendanceController::class, 'index'])->name('absensi.index');
    Route::get('/absensi-view', [AttendanceController::class, 'index'])->name('absensi');
    Route::get('/presensi', [AttendanceController::class, 'index'])->name('presensi.index');
    // Presensi Guru: halaman & simpan status guru khusus panel piket.
    Route::get('/presensi/guru', [AttendanceController::class, 'kehadiranGuru'])->name('presensi.guru');
    Route::post('/presensi/guru/store', [AttendanceController::class, 'storeGuruPresensi'])->name('presensi.guru.store');
    Route::get('/absensi/kiosk', [AdminScannerController::class, 'kiosk'])->name('absensi.kiosk');
    Route::get('/absensi/{schoolClass}', [AttendanceController::class, 'showClass'])->name('absensi.show');
    Route::get('/scanner', [AdminScannerController::class, 'index'])->name('scanner');
    Route::get('/kiosk', [AdminScannerController::class, 'kiosk'])->name('kiosk');
    Route::post('/scanner/process', [AdminScannerController::class, 'processScan'])->name('scanner.process')->middleware('throttle:30,1');

    // KEHADIRAN (dropdown: Siswa & Guru) - READ-ONLY (route override absensi sengaja tidak ada => 403)
    Route::get('/kehadiran', [AttendanceController::class, 'kehadiran'])->name('kehadiran');
    Route::get('/kehadiran/siswa', [AttendanceController::class, 'kehadiran'])->name('kehadiran.siswa');
    Route::get('/kehadiran/guru', [AttendanceController::class, 'kehadiranGuru'])->name('kehadiran.guru');
    Route::get('/kehadiran/guru/{teacher}/history', [AttendanceController::class, 'teacherHistory'])->name('kehadiran.guru-history');
    Route::get('/kehadiran/siswa/{student}/history', [AttendanceController::class, 'studentHistory'])->name('kehadiran.student-history');
    Route::get('/kehadiran/{schoolClass}', [AttendanceController::class, 'kehadiranDetail'])->name('kehadiran.detail');
});

// ============================================================================
// 4. ALIAS KOMPATIBILITAS KELAS (khusus ADMIN)
// ============================================================================
// Versi lama route memakai nama classes.* tanpa prefix admin (URL /kelas/...).
// Dipertahankan HANYA supaya view lama yang masih memanggil route('classes.*')
// tidak error. Setelah semua view memakai admin.classes.*, seluruh blok ini
// boleh dihapus. Route statis tetap di atas route berparameter.
// ============================================================================
Route::middleware(['role:admin'])->prefix('kelas')->name('classes.')->group(function () {
    Route::get('/', [SchoolClassController::class, 'index'])->name('index');
    Route::get('template', [SchoolClassController::class, 'template'])->name('template');
    Route::get('trash', [SchoolClassController::class, 'trash'])->name('trash');
    Route::delete('destroy-all', [SchoolClassController::class, 'destroyAllActive'])->name('destroy-all');
    Route::delete('trash-destroy-all', [SchoolClassController::class, 'destroyAllTrashed'])->name('trash-destroy-all');
    Route::post('import', [SchoolClassController::class, 'import'])->name('import')->middleware('throttle:10,1');
    Route::post('/', [SchoolClassController::class, 'store'])->name('store');
    Route::post('{id}/restore', [SchoolClassController::class, 'restore'])->name('restore')->whereNumber('id');
    Route::delete('{id}/force-delete', [SchoolClassController::class, 'forceDelete'])->name('force-delete')->whereNumber('id');
    Route::put('{class}', [SchoolClassController::class, 'update'])->name('update')->whereNumber('class');
    Route::delete('{class}', [SchoolClassController::class, 'destroy'])->name('destroy')->whereNumber('class');
});
// ============================================================================
// 5. PRESENSI GURU (panel ADMIN)
// ============================================================================
// Piket memakai route sendiri di group piket (piket.presensi.guru), sedangkan
// guru tidak lagi memiliki menu/akses Presensi Guru. Route global ini dikunci
// agar hanya admin yang dapat membuka halaman presensi guru.
// ============================================================================
Route::middleware(['role:admin'])->group(function () {
    Route::get('/presensi/guru', [AttendanceController::class, 'kehadiranGuru'])->name('presensi.guru');
    Route::post('/presensi/guru/store', [AttendanceController::class, 'storeGuruPresensi'])->name('presensi.guru.store');
});
