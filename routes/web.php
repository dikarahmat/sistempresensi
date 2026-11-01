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
    // Guru: tambah/import/template/cetak kartu/edit/hapus. Route statis di atas route berparameter.
    Route::get('/admin/guru/template', [TeacherController::class, 'downloadTemplate'])->name('admin.guru.template');
    Route::post('/admin/guru/import', [TeacherController::class, 'import'])->name('admin.guru.import')->middleware('throttle:10,1');
    Route::post('/admin/guru/print-cards', [TeacherController::class, 'printCards'])->name('admin.guru.print-cards');
    Route::delete('/admin/guru/trash-destroy-all', [TeacherController::class, 'destroyAllTrashed'])->name('admin.guru.trash-destroy-all');
    Route::post('/admin/guru', [TeacherController::class, 'store'])->name('admin.guru.store');
    Route::get('/admin/guru/{teacher}', [TeacherController::class, 'show'])->name('admin.guru.show')->whereNumber('teacher');
    Route::get('/admin/guru/{teacher}/download-card', [TeacherController::class, 'downloadCard'])->name('admin.guru.download-card')->whereNumber('teacher');
    Route::put('/admin/guru/{teacher}', [TeacherController::class, 'update'])->name('admin.guru.update')->whereNumber('teacher');
    Route::delete('/admin/guru/{teacher}', [TeacherController::class, 'destroy'])->name('admin.guru.destroy')->whereNumber('teacher');
    Route::post('/admin/teachers/{id}/restore', [TeacherController::class, 'restore'])->name('admin.teachers.restore')->whereNumber('id');
    Route::get('/admin/pengaturan/jadwal', [SettingController::class, 'index'])->name('admin.pengaturan.jadwal');
    Route::get('/admin/settings', [SettingController::class, 'index'])->name('admin.settings.index');
    Route::get('/admin/accounts', [UserAccountController::class, 'index'])->name('admin.accounts.index');

    // ===== SISWA (admin) =====
    // Route statis di atas, route berparameter ({student}, {id}) di bawah.
    Route::get('/admin/students', [StudentController::class, 'index'])->name('admin.students.index');
    Route::get('/admin/students/template', [StudentController::class, 'downloadTemplate'])->name('admin.students.template');
    Route::get('/admin/students/trash', [StudentController::class, 'trash'])->name('admin.students.trash');
    Route::post('/admin/students/import', [StudentController::class, 'import'])->name('admin.students.import')->middleware('throttle:10,1');
    Route::post('/admin/students/print-cards', [StudentController::class, 'printCards'])->name('admin.students.print-cards');
    Route::delete('/admin/students/destroy-all-active', [StudentController::class, 'destroyAllActive'])->name('admin.students.destroy-all-active');
    Route::delete('/admin/students/trash-destroy-all', [StudentController::class, 'destroyAllTrashed'])->name('admin.students.trash-destroy-all');
    Route::post('/admin/students', [StudentController::class, 'store'])->name('admin.students.store');

    Route::post('/admin/students/{id}/restore', [StudentController::class, 'restore'])->name('admin.students.restore')->whereNumber('id');
    Route::delete('/admin/students/{id}/force-delete', [StudentController::class, 'forceDelete'])->name('admin.students.force-delete')->whereNumber('id');

    Route::get('/admin/students/{student}', [StudentController::class, 'show'])->name('admin.students.show')->whereNumber('student');
    Route::get('/admin/students/{student}/download-card', [StudentController::class, 'downloadCard'])->name('admin.students.download-card')->whereNumber('student');
    Route::get('/admin/students/{student}/card', [StudentController::class, 'card'])->name('admin.students.card')->whereNumber('student');
    Route::put('/admin/students/{student}', [StudentController::class, 'update'])->name('admin.students.update')->whereNumber('student');
    Route::delete('/admin/students/{student}', [StudentController::class, 'destroy'])->name('admin.students.destroy')->whereNumber('student');

    Route::delete('/admin/guru/destroy-all', [TeacherController::class, 'destroyAllActive'])->name('admin.guru.destroy-all');
    Route::delete('/admin/teachers/{teacher}/force-delete', [TeacherController::class, 'forceDelete'])->name('admin.teachers.force-delete');
    Route::get('/admin/teachers/trash', [TeacherController::class, 'trash'])->name('admin.teachers.trash');
    Route::delete('/admin/classes/destroy-all', [SchoolClassController::class, 'destroyAll'])->name('admin.classes.destroy-all');
    Route::get('/admin/kelas', [SchoolClassController::class, 'index'])->name('admin.kelas.index');
    Route::get('/admin/classes', [SchoolClassController::class, 'index'])->name('admin.classes.index');
    Route::get('/admin/absensi', [AttendanceController::class, 'index'])->name('admin.absensi.index');
    Route::get('/admin/absensi/{schoolClass}', [AttendanceController::class, 'showClass'])->name('admin.absensi.show');
    Route::get('/admin/kehadiran', [AttendanceController::class, 'kehadiran'])->name('admin.kehadiran');
    Route::get('/admin/kehadiran/{schoolClass}/detail', [AttendanceController::class, 'kehadiranDetail'])->name('admin.kehadiran.detail');
    Route::get('/admin/kehadiran/student-history/{student}', [AttendanceController::class, 'studentHistory'])->name('admin.kehadiran.student-history');
    Route::get('/admin/rekap', [RekapController::class, 'index'])->name('admin.rekap');
    // Rekap: cetak (POST) & pencarian siswa. Route statis, di bawah rekap index.
    Route::post('/admin/rekap/print', [RekapController::class, 'printRekap'])->name('admin.rekap.print');
    Route::get('/admin/rekap/student-search', [RekapController::class, 'studentSearch'])->name('admin.rekap.student-search');
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
    // Rekap: cetak (POST) & pencarian siswa.
    Route::post('/guru/rekap/print', [RekapController::class, 'printRekap'])->name('guru.rekap.print');
    Route::get('/guru/rekap/student-search', [RekapController::class, 'studentSearch'])->name('guru.rekap.student-search');
    Route::get('/guru/scanner', [ScannerController::class, 'index'])->name('guru.scanner');
    Route::get('/guru/presensi', [ScannerController::class, 'index'])->name('guru.presensi.index');
    Route::get('/guru/absensi/kiosk', [ScannerController::class, 'kiosk'])->name('guru.absensi.kiosk');
    Route::get('/guru/kiosk', [ScannerController::class, 'kiosk'])->name('guru.kiosk');
});

// ============================================================================
// PIKET - middleware role:piket (placeholder; belum ada controller khusus)
// ============================================================================
Route::middleware(['role:piket'])->group(function () {
    Route::get('/piket/dashboard', [AdminDashboardController::class, 'index'])->name('piket.dashboard');

    // Presensi / scanner
    Route::get('/piket/scanner', [ScannerController::class, 'index'])->name('piket.scanner');
    Route::get('/piket/presensi', [ScannerController::class, 'index'])->name('piket.presensi.index');
    Route::get('/piket/absensi/kiosk', [ScannerController::class, 'kiosk'])->name('piket.absensi.kiosk');
    Route::get('/piket/kiosk', [ScannerController::class, 'kiosk'])->name('piket.kiosk');

    // Absensi & Kehadiran (read-only)
    Route::get('/piket/absensi', [AttendanceController::class, 'index'])->name('piket.absensi.index');
    Route::get('/piket/absensi/{schoolClass}', [AttendanceController::class, 'showClass'])->name('piket.absensi.show');
    Route::get('/piket/kehadiran', [AttendanceController::class, 'kehadiran'])->name('piket.kehadiran');
    Route::get('/piket/kehadiran/{schoolClass}/detail', [AttendanceController::class, 'kehadiranDetail'])->name('piket.kehadiran.detail');
    Route::get('/piket/kehadiran/student-history/{student}', [AttendanceController::class, 'studentHistory'])->name('piket.kehadiran.student-history');

    // Rekap
    Route::get('/piket/rekap', [RekapController::class, 'index'])->name('piket.rekap');
    Route::post('/piket/rekap/print', [RekapController::class, 'printRekap'])->name('piket.rekap.print');
    Route::get('/piket/rekap/student-search', [RekapController::class, 'studentSearch'])->name('piket.rekap.student-search');

    // Data Siswa & Kelas (read-only)
    Route::get('/piket/students', [StudentController::class, 'index'])->name('piket.students.index');
    Route::post('/piket/students/print-cards', [StudentController::class, 'printCards'])->name('piket.students.print-cards');
    Route::get('/piket/students/{student}', [StudentController::class, 'show'])->name('piket.students.show')->whereNumber('student');
    Route::get('/piket/students/{student}/download-card', [StudentController::class, 'downloadCard'])->name('piket.students.download-card')->whereNumber('student');
    Route::get('/piket/kelas', [SchoolClassController::class, 'index'])->name('piket.kelas.index');
    Route::get('/piket/classes', [SchoolClassController::class, 'index'])->name('piket.classes.index');
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