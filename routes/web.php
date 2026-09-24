<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ClassPromotionController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\RekapController as AdminRekapController;
use App\Http\Controllers\Admin\ScannerController as AdminScannerController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TeacherController;

use App\Http\Controllers\WaliKelas\WaliKelasPortalController;
use App\Http\Controllers\WaliKelas\AttendanceController as WaliKelasAttendanceController;
use App\Http\Controllers\WaliKelas\ScannerController as WaliKelasScannerController;
use App\Http\Controllers\WaliKelas\StudentController as WaliKelasStudentController;

use App\Http\Controllers\Kesiswaan\KesiswaanDashboardController;
use App\Http\Controllers\Kesiswaan\KesiswaanAttendanceController;
use App\Http\Controllers\Kesiswaan\KesiswaanClassController;
use App\Http\Controllers\Kesiswaan\KesiswaanTeacherController;
use App\Http\Controllers\Kesiswaan\KesiswaanRekapController;
use App\Http\Controllers\Kesiswaan\KesiswaanStudentController;

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root ke dashboard jika login admin/guru/kesiswaan, atau ke login
Route::get('/', function () {
    if (Auth::check()) {
        return match (Auth::user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'guru' => redirect()->route('guru.dashboard'),
            'kesiswaan' => redirect()->route('kesiswaan.dashboard'),
            default => redirect()->route('login'),
        };
    }
    return redirect()->route('login');
});

// Autentikasi
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/login/guru', [AuthController::class, 'loginGuru'])->name('login.guru')->middleware('throttle:5,1');
Route::post('/login/admin', [AuthController::class, 'loginAdmin'])->name('login.admin')->middleware('throttle:5,1');
Route::post('/login/kesiswaan', [AuthController::class, 'loginKesiswaan'])->name('login.kesiswaan')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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
    Route::get('/absensi/{schoolClass}', [AttendanceController::class, 'showClass'])->name('absensi.show');
    Route::get('/kehadiran', [AttendanceController::class, 'kehadiran'])->name('kehadiran');
    Route::get('/kehadiran/{schoolClass}', [AttendanceController::class, 'kehadiranDetail'])->name('kehadiran.detail');
    Route::post('/absensi/override', [AttendanceController::class, 'override'])->name('absensi.override');
    Route::post('/kehadiran/override', [AttendanceController::class, 'override'])->name('kehadiran.override');
    Route::get('/rekap', [AdminRekapController::class, 'index'])->name('rekap');
    Route::get('/rekap/export-excel', [AdminRekapController::class, 'exportExcel'])->name('rekap.export-excel');
    Route::get('/rekap/export-pdf', [AdminRekapController::class, 'exportPdf'])->name('rekap.export-pdf');

    // Scanner & Mode Gerbang / Kiosk Absensi
    Route::get('/scanner', [AdminScannerController::class, 'index'])->name('scanner');
    Route::get('/absensi/kiosk', [AdminScannerController::class, 'kiosk'])->name('absensi.kiosk');
    Route::get('/kiosk', [AdminScannerController::class, 'kiosk'])->name('kiosk');
    Route::post('/scanner/process', [AdminScannerController::class, 'processScan'])->name('scanner.process');

    // Menu Kenaikan Kelas & Mutasi Rombel Massal
    Route::get('/kenaikan-kelas', [ClassPromotionController::class, 'index'])->name('kenaikan-kelas.index');
    Route::post('/kenaikan-kelas/promote', [ClassPromotionController::class, 'promote'])->name('kenaikan-kelas.promote');
    Route::get('/kenaikan-kelas/students/{schoolClass}', [ClassPromotionController::class, 'getStudents'])->name('kenaikan-kelas.students');

    // Master Tahun Ajaran
    Route::post('academic-years/{academic_year}/toggle-active', [AcademicYearController::class, 'toggleActive'])->name('academic-years.toggle-active');
    Route::resource('academic-years', AcademicYearController::class)->except(['show']);

    // Master Hari Libur & Import
    Route::get('holidays/template', [HolidayController::class, 'template'])->name('holidays.template');
    Route::post('holidays/import', [HolidayController::class, 'import'])->name('holidays.import');
    Route::resource('holidays', HolidayController::class)->except(['show']);

    // Master Kelas, Import, & Hapus Semua
    Route::get('/kelas', [SchoolClassController::class, 'index'])->name('kelas.index');
    Route::delete('classes/destroy-all', [SchoolClassController::class, 'destroyAll'])->name('classes.destroy-all');
    Route::get('classes/template', [SchoolClassController::class, 'template'])->name('classes.template');
    Route::post('classes/import', [SchoolClassController::class, 'import'])->name('classes.import');
    Route::resource('classes', SchoolClassController::class)->except(['show']);

    // Master Siswa, Import, Upload Foto ZIP, & Hapus Semua
    Route::get('/siswa', [AdminStudentController::class, 'index'])->name('siswa.index');
    Route::match(['get', 'post'], '/siswa/generate-qr', [AdminStudentController::class, 'printCards'])->name('siswa.generate-qr');
    Route::delete('students/destroy-all', [AdminStudentController::class, 'destroyAll'])->name('students.destroy-all');
    Route::match(['get', 'post'], 'students/print-cards', [AdminStudentController::class, 'printCards'])->name('students.print-cards');
    Route::get('students/template', [AdminStudentController::class, 'downloadTemplate'])->name('students.template');
    Route::post('students/import', [AdminStudentController::class, 'import'])->name('students.import');
    // Route::post('students/upload-photos-zip', [AdminStudentController::class, 'processUploadPhotosZip'])->name('students.upload-photos-zip');
    Route::get('students/{student}/download-qr', [AdminStudentController::class, 'downloadQr'])->name('students.download-qr');
    Route::get('students/{student}/download-card', [AdminStudentController::class, 'downloadCard'])->name('students.download-card');
    Route::get('students/{id}/print-card', [AdminStudentController::class, 'printCard'])->name('students.print-card');
    Route::get('students-print-all', [AdminStudentController::class, 'printCard'])->name('students.print-all');
    Route::resource('students', AdminStudentController::class);

    // Manajemen Data Guru & Wali Kelas (/admin/guru)
    Route::get('/walikelas', [TeacherController::class, 'index'])->name('walikelas.index');
    Route::delete('guru/destroy-all', [TeacherController::class, 'destroyAll'])->name('guru.destroy-all');
    Route::get('guru/template', [TeacherController::class, 'downloadTemplate'])->name('guru.template');
    Route::post('guru/import', [TeacherController::class, 'import'])->name('guru.import');
    Route::resource('guru', TeacherController::class)->parameters(['guru' => 'teacher']);

    // Kompatibilitas Route Teachers
    Route::delete('teachers/destroy-all', [TeacherController::class, 'destroyAll'])->name('teachers.destroy-all');
    Route::get('teachers/template', [TeacherController::class, 'downloadTemplate'])->name('teachers.template');
    Route::post('teachers/import', [TeacherController::class, 'import'])->name('teachers.import');
    Route::resource('teachers', TeacherController::class)->except(['show']);

    // Pengaturan Sistem Dinamis
    Route::get('/pengaturan/jadwal', [SettingController::class, 'index'])->name('pengaturan.jadwal');
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

    // Manajemen Role & Permission
    Route::get('/roles-permissions', [RolePermissionController::class, 'index'])->name('roles.index');
    Route::post('/roles-permissions', [RolePermissionController::class, 'update'])->name('roles.update');
});

// ============================================================================
// 2. GROUP WALI KELAS (GURU)
// ============================================================================
Route::prefix('guru')->name('guru.')->middleware(['role:guru'])->group(function () {
    Route::get('/dashboard', [WaliKelasPortalController::class, 'dashboard'])->name('dashboard');

    // Presensi & Absensi Kelas Binaan
    Route::get('/absensi', [WaliKelasAttendanceController::class, 'index'])->name('absensi.index');
    Route::get('/absensi-view', [WaliKelasAttendanceController::class, 'index'])->name('absensi');
    Route::get('/presensi', [WaliKelasAttendanceController::class, 'index'])->name('presensi.index');
    Route::get('/absensi/{schoolClass}', [WaliKelasAttendanceController::class, 'showClass'])->name('absensi.show');
    Route::post('/absensi/override', [WaliKelasPortalController::class, 'overrideAttendance'])->name('absensi.override');

    // Kehadiran & Override
    Route::get('/kehadiran', [WaliKelasPortalController::class, 'dailyAttendance'])->name('kehadiran');
    Route::post('/kehadiran/override', [WaliKelasPortalController::class, 'overrideAttendance'])->name('kehadiran.override');

    // Scanner Mandiri (Dinonaktifkan: Redirect ke Presensi)
    Route::get('/scanner', [WaliKelasScannerController::class, 'index'])->name('scanner');
    Route::post('/scanner/process', [WaliKelasScannerController::class, 'store'])->name('scanner.process');

    // Siswa Binaan
    Route::get('/students', [WaliKelasStudentController::class, 'index'])->name('students');
    Route::get('/students/{student}', [WaliKelasStudentController::class, 'show'])->name('students.show');
    Route::get('/students/{student}/card', [WaliKelasStudentController::class, 'downloadCard'])->name('students.card');
    Route::get('/students/{student}/download-card', [WaliKelasStudentController::class, 'downloadCard'])->name('students.download-card');
    Route::get('/students/{student}/download-qr', [WaliKelasStudentController::class, 'downloadQr'])->name('students.download-qr');
    Route::match(['get', 'post'], '/students/print-cards', [WaliKelasStudentController::class, 'printCards'])->name('students.print-cards');

    // Rekap
    Route::get('/rekap', [AdminRekapController::class, 'guruIndex'])->name('rekap');
    Route::get('/rekap/export-excel', [AdminRekapController::class, 'guruExportExcel'])->name('rekap.export-excel');
    Route::get('/rekap/export-pdf', [AdminRekapController::class, 'guruExportPdf'])->name('rekap.export-pdf');
});

// ============================================================================
// 3. GROUP KESISWAAN (Strict Read-Only)
// ============================================================================
Route::prefix('kesiswaan')->name('kesiswaan.')->middleware(['role.kesiswaan'])->group(function () {
    Route::get('/dashboard', [KesiswaanDashboardController::class, 'index'])->name('dashboard');

    // Presensi (Identik Admin Absensi Daily & Class)
    Route::get('/absensi', [KesiswaanAttendanceController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/{schoolClass}', [KesiswaanAttendanceController::class, 'showClass'])->name('absensi.show');

    // Kehadiran (Identik Admin Kehadiran & Detail)
    Route::get('/kehadiran', [KesiswaanAttendanceController::class, 'kehadiran'])->name('kehadiran');
    Route::get('/kehadiran/{schoolClass}', [KesiswaanAttendanceController::class, 'kehadiranDetail'])->name('kehadiran.detail');

    // Siswa (Identik Admin Siswa, Strictly Read-Only + Cetak Kartu)
    Route::get('/siswa', [KesiswaanStudentController::class, 'index'])->name('students.index');
    Route::get('/siswa/{student}', [KesiswaanStudentController::class, 'show'])->name('students.show');
    Route::get('/siswa/{student}/card', [KesiswaanStudentController::class, 'downloadCard'])->name('students.card');
    Route::get('/siswa/{student}/download-card', [KesiswaanStudentController::class, 'downloadCard'])->name('students.download-card');
    Route::get('/siswa/{student}/download-qr', [KesiswaanStudentController::class, 'downloadQr'])->name('students.download-qr');
    Route::match(['get', 'post'], '/siswa/print-cards', [KesiswaanStudentController::class, 'printCards'])->name('students.print-cards');

    // Kelas & Wali Kelas (Identik Admin, Strictly Read-Only)
    Route::get('/kelas', [KesiswaanClassController::class, 'index'])->name('classes.index');
    Route::get('/wali-kelas', [KesiswaanTeacherController::class, 'index'])->name('teachers.index');

    // Rekap Presensi Multi-Periode (Identik Admin Rekap)
    Route::get('/rekap', [KesiswaanRekapController::class, 'index'])->name('rekap.index');
    Route::get('/rekap/export-excel', [KesiswaanRekapController::class, 'exportExcel'])->name('rekap.export-excel');
    Route::get('/rekap/export-pdf', [KesiswaanRekapController::class, 'exportPdf'])->name('rekap.export-pdf');
    Route::get('/rekap/export-csv', [KesiswaanRekapController::class, 'exportCsv'])->name('rekap.export-csv');
});