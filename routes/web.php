<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\RekapController as AdminRekapController;
use App\Http\Controllers\Admin\ScannerController as AdminScannerController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TeacherController;

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root ke dashboard sesuai role (admin/guru), atau ke login
Route::get('/', [AuthController::class, 'redirectRoot']);

// Autentikasi
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/login/guru', [AuthController::class, 'loginGuru'])->name('login.guru')->middleware('throttle:5,1');
Route::post('/login/admin', [AuthController::class, 'loginAdmin'])->name('login.admin')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('throttle:10,1');

// ============================================================================
// 1. GROUP ADMIN
// ============================================================================
Route::prefix('admin')->name('admin.')->middleware(['role:admin'])->group(function () {
    // Utama
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Kehadiran & Rekap
    Route::get('/absensi', [AttendanceController::class, 'index'])->name('absensi.index');
    Route::get('/absensi-view', [AttendanceController::class, 'index'])->name('absensi');
    Route::get('/presensi', [AttendanceController::class, 'index'])->name('presensi.index');
    // Route statis WAJIB didaftarkan sebelum route berparameter dinamis di bawahnya,
    // supaya /admin/absensi/kiosk tidak ditangkap {schoolClass} (showClass('kiosk') -> TypeError).
    Route::get('/absensi/kiosk', [AdminScannerController::class, 'kiosk'])->name('absensi.kiosk');
    Route::get('/absensi/{schoolClass}', [AttendanceController::class, 'showClass'])->name('absensi.show');
    Route::get('/kehadiran', [AttendanceController::class, 'kehadiran'])->name('kehadiran');
    Route::get('/kehadiran/{schoolClass}', [AttendanceController::class, 'kehadiranDetail'])->name('kehadiran.detail');
    Route::post('/absensi/override', [AttendanceController::class, 'override'])->name('absensi.override')->middleware('throttle:20,1');
    Route::post('/kehadiran/override', [AttendanceController::class, 'override'])->name('kehadiran.override')->middleware('throttle:20,1');
    Route::get('/rekap', [AdminRekapController::class, 'index'])->name('rekap');
    Route::get('/rekap/export-excel', [AdminRekapController::class, 'exportExcel'])->name('rekap.export-excel')->middleware('throttle:10,1');
    Route::get('/rekap/export-pdf', [AdminRekapController::class, 'exportPdf'])->name('rekap.export-pdf')->middleware('throttle:10,1');
    // Scanner & Mode Gerbang / Kiosk Absensi
    Route::get('/scanner', [AdminScannerController::class, 'index'])->name('scanner');
    Route::get('/kiosk', [AdminScannerController::class, 'kiosk'])->name('kiosk');
    Route::post('/scanner/process', [AdminScannerController::class, 'processScan'])->name('scanner.process')->middleware('throttle:30,1');

    // URL LAMA modul Tahun Ajaran & Hari Libur. Modul ini kini hidup di bawah
    // Pengaturan (admin/settings/...), tapi URL lama tidak boleh mati diam-diam:
    // tetap-answer 301 (permanen) ke URL baru. Nama route, controller, method,
    // middleware, dan HTTP method modul TIDAK diubah.
    Route::get('academic-years', fn () => redirect()->route('admin.academic-years.index', [], 301));
    Route::get('holidays', fn () => redirect()->route('admin.holidays.index', [], 301));
    Route::get('holidays/template', fn () => redirect()->route('admin.holidays.template', [], 301));

    // Pengaturan Sistem + modul yang dikelola dari halaman Pengaturan.
    // Route statis (toggle-active, template) didaftarkan lebih dulu supaya tidak
    // tertangkap route resource yang berparameter.
    Route::prefix('settings')->group(function () {
        // Master Tahun Ajaran
        Route::post('academic-years/{academic_year}/toggle-active', [AcademicYearController::class, 'toggleActive'])->name('academic-years.toggle-active');
        Route::resource('academic-years', AcademicYearController::class)->except(['show', 'create', 'edit']);

        // Master Hari Libur & Import
        // ->names('holidays') dipakai AGAR nama route tetap admin.holidays.*
        // Hanya URL (path) yang berubah, nama route tidak.
        Route::get('hari-libur/template', [HolidayController::class, 'template'])->name('holidays.template');
        Route::post('hari-libur/import', [HolidayController::class, 'import'])->name('holidays.import')->middleware('throttle:10,1');
        Route::resource('hari-libur', HolidayController::class)->names('holidays')->except(['show', 'create', 'edit']);
    });

    // Master Kelas, Import, & Hapus Semua
    Route::get('/kelas', [SchoolClassController::class, 'index'])->name('kelas.index');
    Route::delete('classes/destroy-all', [SchoolClassController::class, 'destroyAll'])->name('classes.destroy-all');
    Route::get('classes/template', [SchoolClassController::class, 'template'])->name('classes.template');
    Route::post('classes/import', [SchoolClassController::class, 'import'])->name('classes.import')->middleware('throttle:10,1');

    // Soft Delete Routes untuk Kelas (Tempat Sampah).
    // Route statis WAJIB didaftarkan SEBELUM Route::resource('classes') di bawah
    // supaya tidak tertangkap parameter {class}.classes.destroy-all di atas
    // tetap utuh untuk aksi "Hapus Semua" di halaman Data Kelas.
    Route::delete('classes/trash-destroy-all', [SchoolClassController::class, 'destroyAllTrashed'])->name('classes.trash-destroy-all');
    Route::get('classes/trash', [SchoolClassController::class, 'trash'])->name('classes.trash');
    Route::post('classes/{id}/restore', [SchoolClassController::class, 'restore'])->name('classes.restore');
    Route::delete('classes/{id}/force-delete', [SchoolClassController::class, 'forceDelete'])->name('classes.force-delete');

    Route::resource('classes', SchoolClassController::class)->except(['show', 'create', 'edit']);

    // Master Siswa, Import, Upload Foto ZIP, & Hapus Semua
    Route::get('/siswa', [AdminStudentController::class, 'index'])->name('siswa.index');
    Route::match(['get', 'post'], '/siswa/generate-qr', [AdminStudentController::class, 'printCards'])->name('siswa.generate-qr');
    Route::delete('students/destroy-all', [AdminStudentController::class, 'destroyAll'])->name('students.destroy-all');
    // Hapus massal siswa AKTIF dari toolbar Data Siswa (soft delete -> Tempat Sampah).
    // Route statis WAJIB didaftarkan sebelum Route::resource('students') di bawah
    // supaya tidak tertangkap parameter {student}. Route students.destroy-all
    // di atas tetap utuh untuk aksi kosongkan Tempat Sampah.
    Route::delete('students/destroy-all-active', [AdminStudentController::class, 'destroyAllActive'])->name('students.destroy-all-active');
    Route::match(['get', 'post'], 'students/print-cards', [AdminStudentController::class, 'printCards'])->name('students.print-cards');
    Route::get('students/template', [AdminStudentController::class, 'downloadTemplate'])->name('students.template');
    Route::post('students/import', [AdminStudentController::class, 'import'])->name('students.import')->middleware('throttle:10,1');
    Route::get('students/{student}/download-qr', [AdminStudentController::class, 'downloadQr'])->name('students.download-qr');
    Route::get('students/{student}/download-card', [AdminStudentController::class, 'downloadCard'])->name('students.download-card');
    Route::get('students/{id}/print-card', [AdminStudentController::class, 'printCard'])->name('students.print-card');
    Route::get('students-print-all', [AdminStudentController::class, 'printCard'])->name('students.print-all');
    
    // Soft Delete Routes
    Route::get('students/trash', [AdminStudentController::class, 'trash'])->name('students.trash');
    Route::post('students/{id}/restore', [AdminStudentController::class, 'restore'])->name('students.restore');
    Route::delete('students/{id}/force-delete', [AdminStudentController::class, 'forceDelete'])->name('students.force-delete');
    
    // Riwayat Presensi per Siswa (akses dari menu Kehadiran)
    Route::get('kehadiran/siswa/{student}/history', [\App\Http\Controllers\Admin\AttendanceController::class, 'studentHistory'])->name('kehadiran.student-history');
    
    Route::resource('students', AdminStudentController::class);

    // Manajemen Data Guru (/admin/guru)

    Route::delete('guru/destroy-all', [TeacherController::class, 'destroyAll'])->name('guru.destroy-all');
    Route::get('guru/template', [TeacherController::class, 'downloadTemplate'])->name('guru.template');
    Route::post('guru/import', [TeacherController::class, 'import'])->name('guru.import')->middleware('throttle:10,1');
    Route::resource('guru', TeacherController::class)->parameters(['guru' => 'teacher'])->only(['index', 'store', 'update', 'destroy']);

    // Kompatibilitas Route Teachers
    Route::delete('teachers/destroy-all', [TeacherController::class, 'destroyAll'])->name('teachers.destroy-all');
    Route::get('teachers/template', [TeacherController::class, 'downloadTemplate'])->name('teachers.template');
    Route::post('teachers/import', [TeacherController::class, 'import'])->name('teachers.import')->middleware('throttle:10,1');

    //WAJIB didaftarkan sebelum Route::resource('teachers') di bawah supaya tidak
    // tertangkap parameter {teacher} pada route DELETE teachers/{teacher}.
    Route::delete('teachers/trash-destroy-all', [TeacherController::class, 'destroyAllTrashed'])->name('teachers.trash-destroy-all');

    Route::resource('teachers', TeacherController::class)->only(['index', 'store', 'update', 'destroy']);

    // Soft Delete Routes untuk Teachers
    Route::get('teachers/trash', [TeacherController::class, 'trash'])->name('teachers.trash');
    Route::post('teachers/{id}/restore', [TeacherController::class, 'restore'])->name('teachers.restore');
    Route::delete('teachers/{id}/force-delete', [TeacherController::class, 'forceDelete'])->name('teachers.force-delete');

    // Pengaturan Sistem Dinamis
    Route::get('/pengaturan/jadwal', [SettingController::class, 'index'])->name('pengaturan.jadwal');
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update')->middleware('throttle:10,1');

    // Manajemen Role & Permission
    Route::get('/roles-permissions', [RolePermissionController::class, 'index'])->name('roles.index');
    Route::post('/roles-permissions', [RolePermissionController::class, 'update'])->name('roles.update');
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

    // KEHADIRAN - identik admin tetapi READ-ONLY
    // (route ubah status presensi / override sengaja tidak didaftarkan => 403)
    Route::get('/kehadiran', [AttendanceController::class, 'kehadiran'])->name('kehadiran');
    Route::get('/kehadiran/siswa/{student}/history', [AttendanceController::class, 'studentHistory'])->name('kehadiran.student-history');
    Route::get('/kehadiran/{schoolClass}', [AttendanceController::class, 'kehadiranDetail'])->name('kehadiran.detail');

    // REKAP - identik admin (filter + unduh Excel/PDF)
    Route::get('/rekap', [AdminRekapController::class, 'index'])->name('rekap');
    Route::get('/rekap/export-excel', [AdminRekapController::class, 'exportExcel'])->name('rekap.export-excel')->middleware('throttle:10,1');
    Route::get('/rekap/export-pdf', [AdminRekapController::class, 'exportPdf'])->name('rekap.export-pdf')->middleware('throttle:10,1');

    // SISWA - identik admin, READ-ONLY (detail + cetak kartu & unduh QR)
    Route::get('/siswa', [AdminStudentController::class, 'index'])->name('students.index');
    // Route statis didaftarkan lebih dulu agar tidak tertangkap {student}.
    Route::match(['get', 'post'], '/siswa/print-cards', [AdminStudentController::class, 'printCards'])->name('students.print-cards');
    Route::get('/siswa/{student}/download-qr', [AdminStudentController::class, 'downloadQr'])->name('students.download-qr');
    Route::get('/siswa/{student}/download-card', [AdminStudentController::class, 'downloadCard'])->name('students.download-card');
    Route::get('/siswa/{student}/card', [AdminStudentController::class, 'downloadCard'])->name('students.card');
    Route::get('/siswa/{student}', [AdminStudentController::class, 'show'])->name('students.show');

    // KELAS - identik admin, READ-ONLY
    Route::get('/kelas', [SchoolClassController::class, 'index'])->name('classes.index');
});