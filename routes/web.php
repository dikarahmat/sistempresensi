<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\RekapController;
use App\Http\Controllers\Admin\ScannerController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\UserAccountController;
use App\Http\Controllers\Admin\AcademicYearController;

// Root redirect berdasarkan role (admin/guru/piket) atau ke login.
Route::get('/', [AuthController::class, 'redirectRoot'])->name('root');

// AUTH - Login universal
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// LOCALE - Pengganti bahasa (id/en) via form POST di sidebar
Route::post('/locale/set', function (Request $request) {
    $request->validate(['lang' => ['required', 'string', 'in:id,en']]);
    $request->session()->put('locale', $request->input('lang'));
    return back();
})->name('locale.set');

// ============================================================================
// ADMIN - middleware role:admin
// ============================================================================
Route::middleware(['role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/guru', [TeacherController::class, 'index'])->name('admin.guru.index');
    Route::get('/admin/pengaturan/jadwal', [SettingController::class, 'index'])->name('admin.pengaturan.jadwal');
    Route::get('/admin/settings', [SettingController::class, 'index'])->name('admin.settings.index');
    Route::get('/admin/accounts', [UserAccountController::class, 'index'])->name('admin.accounts.index');
    Route::get('/admin/students', [StudentController::class, 'index'])->name('admin.students.index');
    Route::delete('/admin/students/destroy-all-active', [StudentController::class, 'destroyAllActive'])->name('admin.students.destroy-all-active');
    Route::delete('/admin/guru/destroy-all', [TeacherController::class, 'destroyAllActive'])->name('admin.guru.destroy-all');
    Route::delete('/admin/teachers/{teacher}/force-delete', [TeacherController::class, 'forceDelete'])->name('admin.teachers.force-delete');
    Route::get('/admin/teachers/trash', [TeacherController::class, 'trash'])->name('admin.teachers.trash');
    Route::delete('/admin/classes/destroy-all', [SchoolClassController::class, 'destroyAll'])->name('admin.classes.destroy-all');
    Route::get('/admin/kelas', [SchoolClassController::class, 'index'])->name('admin.kelas.index');
    Route::get('/admin/students/trash', [StudentController::class, 'trash'])->name('admin.students.trash');
    Route::get('/admin/classes', [SchoolClassController::class, 'index'])->name('admin.classes.index');
    Route::get('/admin/absensi', [AttendanceController::class, 'index'])->name('admin.absensi.index');
    Route::get('/admin/absensi/{schoolClass}', [AttendanceController::class, 'showClass'])->name('admin.absensi.show');
    Route::get('/admin/kehadiran', [AttendanceController::class, 'kehadiran'])->name('admin.kehadiran');
    Route::get('/admin/kehadiran/{schoolClass}/detail', [AttendanceController::class, 'kehadiranDetail'])->name('admin.kehadiran.detail');
    Route::get('/admin/rekap', [RekapController::class, 'index'])->name('admin.rekap');
    Route::get('/admin/holidays', [HolidayController::class, 'index'])->name('admin.holidays.index');
    Route::get('/admin/scanner', [ScannerController::class, 'index'])->name('admin.scanner');
    Route::get('/admin/presensi', [ScannerController::class, 'index'])->name('admin.presensi.index');
    Route::get('/admin/absensi/kiosk', [ScannerController::class, 'kiosk'])->name('admin.absensi.kiosk');
    Route::get('/admin/kiosk', [ScannerController::class, 'kiosk'])->name('admin.kiosk');
});

// Academic years - admin only
Route::middleware(['role:admin'])->group(function () {
    Route::get('/admin/academic-years', [AcademicYearController::class, 'index'])->name('admin.academic-years.index');
});

// ============================================================================
// GURU - middleware role:guru (READ-ONLY untuk Siswa & Kelas; tanpa Pengaturan)
// ============================================================================
Route::middleware(['role:guru'])->group(function () {
    Route::get('/guru/dashboard', [AdminDashboardController::class, 'index'])->name('guru.dashboard');
    Route::get('/guru/students', [StudentController::class, 'index'])->name('guru.students.index');
    // Siswa READ-ONLY: lihat detail + cetak/unduh kartu. TANPA store/update/destroy/import.
    // Route statis (print-cards) di atas route berparameter ({student}).
    Route::post('/guru/students/print-cards', [StudentController::class, 'printCards'])->name('guru.students.print-cards');
    Route::get('/guru/students/{student}', [StudentController::class, 'show'])->name('guru.students.show')->whereNumber('student');
    Route::get('/guru/students/{student}/download-card', [StudentController::class, 'downloadCard'])->name('guru.students.download-card')->whereNumber('student');
    Route::get('/guru/students/{student}/card', [StudentController::class, 'card'])->name('guru.students.card')->whereNumber('student');
    Route::get('/guru/kelas', [SchoolClassController::class, 'index'])->name('guru.kelas.index');
    Route::get('/guru/classes', [SchoolClassController::class, 'index'])->name('guru.classes.index');
    Route::get('/guru/absensi', [AttendanceController::class, 'index'])->name('guru.absensi.index');
    Route::get('/guru/absensi/{schoolClass}', [AttendanceController::class, 'showClass'])->name('guru.absensi.show');
    Route::get('/guru/kehadiran', [AttendanceController::class, 'kehadiran'])->name('guru.kehadiran');
    Route::get('/guru/kehadiran/{schoolClass}/detail', [AttendanceController::class, 'kehadiranDetail'])->name('guru.kehadiran.detail');
    Route::get('/guru/kehadiran/student-history/{student}', [AttendanceController::class, 'studentHistory'])->name('guru.kehadiran.student-history');
    Route::get('/guru/rekap', [RekapController::class, 'index'])->name('guru.rekap');
    Route::get('/guru/scanner', [ScannerController::class, 'index'])->name('guru.scanner');
    Route::get('/guru/presensi', [ScannerController::class, 'index'])->name('guru.presensi.index');
    Route::get('/guru/absensi/kiosk', [ScannerController::class, 'kiosk'])->name('guru.absensi.kiosk');
    Route::get('/guru/kiosk', [ScannerController::class, 'kiosk'])->name('guru.kiosk');
});

// ============================================================================
// PIKET - middleware role:piket (placeholder; belum ada controller khusus)
// ============================================================================
Route::middleware(['role:piket'])->group(function () {
    Route::get('/piket/absensi', [AdminDashboardController::class, 'index'])->name('piket.absensi.index');
    Route::get('/piket/dashboard', [AdminDashboardController::class, 'index'])->name('piket.dashboard');
});

// ============================================================================
// KELAS - aksi pengelolaan: ADMIN SAJA. Guru read-only lewat guru.classes.index.
// Route statis HARUS di atas route berparameter ({class}, {id}).
// ============================================================================
Route::middleware(['role:admin'])->group(function () {
    Route::get('/kelas', [SchoolClassController::class, 'index'])->name('classes.index');
    Route::get('/kelas/template', [SchoolClassController::class, 'template'])->name('classes.template');
    Route::get('/kelas/trash', [SchoolClassController::class, 'trash'])->name('classes.trash');
    Route::delete('/kelas/destroy-all', [SchoolClassController::class, 'destroyAll'])->name('classes.destroy-all');
    Route::delete('/kelas/trash-destroy-all', [SchoolClassController::class, 'destroyAllTrashed'])->name('classes.trash-destroy-all');
    Route::post('/kelas/import', [SchoolClassController::class, 'import'])->name('classes.import')->middleware('throttle:10,1');
    Route::post('/kelas', [SchoolClassController::class, 'store'])->name('classes.store');
    Route::put('/kelas/{class}', [SchoolClassController::class, 'update'])->name('classes.update')->whereNumber('class');
    Route::delete('/kelas/{class}', [SchoolClassController::class, 'destroy'])->name('classes.destroy')->whereNumber('class');
    Route::post('/kelas/{id}/restore', [SchoolClassController::class, 'restore'])->name('classes.restore')->whereNumber('id');
    Route::delete('/kelas/{id}/force-delete', [SchoolClassController::class, 'forceDelete'])->name('classes.force-delete')->whereNumber('id');
});