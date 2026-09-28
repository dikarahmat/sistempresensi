<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Smoke test kesiapan production.
 *
 * Memastikan seluruh route penting per role (admin, guru/walikelas,
 * kesiswaan) terdaftar dan dirender tanpa error 404/500, termasuk
 * alur inti presensi (scanner) dan form CRUD siswa.
 */
class ProductionReadinessSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $guru;
    protected User $kesiswaan;
    protected AcademicYear $year;
    protected SchoolClass $schoolClass;
    protected Student $student;
    protected Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->year = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'Ganjil',
            'start_date' => '2025-07-01', 'end_date' => '2025-12-31', 'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Smoke', 'username' => 'adminsmoke',
            'email' => 'admin.smoke@presensi.test',
            'password' => Hash::make('password123'), 'role' => 'admin',
        ]);
        $this->guru = User::create([
            'name' => 'Guru Smoke', 'username' => 'gurusmoke',
            'email' => 'guru.smoke@presensi.test',
            'password' => Hash::make('password123'), 'role' => 'guru',
        ]);
        $this->kesiswaan = User::create([
            'name' => 'Kesiswaan Smoke', 'username' => 'kesiswaansmoke',
            'email' => 'kesiswaan.smoke@presensi.test',
            'password' => Hash::make('password123'), 'role' => 'kesiswaan',
        ]);

        $this->teacher = Teacher::create([
            'name' => 'Wali Kelas Smoke', 'nip' => '199001012020011001',
            'gender' => 'Laki-laki', 'user_id' => $this->guru->id,
        ]);

        $this->schoolClass = SchoolClass::create([
            'name' => '7A', 'grade' => '7',
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $this->student = Student::create([
            'school_class_id' => $this->schoolClass->id,
            'name' => 'Siswa Smoke', 'nis' => '990001', 'nisn' => '0099000111',
            'gender' => 'Laki-laki', 'status' => 'Aktif',
        ]);
    }

    private function assertPagesRender(User $user, array $routes, array $params = []): void
    {
        foreach ($routes as $name) {
            $response = $this->actingAs($user)->get(route($name, $params[$name] ?? []));
            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Route [{$name}] mengembalikan HTTP {$response->getStatusCode()} untuk role {$user->role}."
            );
        }
    }

    public function test_admin_pages_render_without_errors(): void
    {
        $this->assertPagesRender($this->admin, [
            'admin.dashboard', 'admin.absensi.index', 'admin.absensi', 'admin.presensi.index',
            'admin.absensi.show', 'admin.kehadiran', 'admin.kehadiran.detail', 'admin.rekap',
            'admin.scanner', 'admin.absensi.kiosk', 'admin.kiosk',
            'admin.students.index', 'admin.siswa.index', 'admin.students.create',
            'admin.students.show', 'admin.students.edit', 'admin.students.trash',
            'admin.students.print-cards',
            'admin.guru.index', 'admin.walikelas.index', 'admin.teachers.index', 'admin.teachers.trash',
            'admin.kelas.index', 'admin.classes.index', 'admin.academic-years.index',
            'admin.holidays.index', 'admin.settings.index', 'admin.pengaturan.jadwal',
            'admin.roles.index',
        ], [
            'admin.absensi.show' => [$this->schoolClass->id],
            'admin.kehadiran.detail' => [$this->schoolClass->id],
            'admin.students.show' => [$this->student->id],
            'admin.students.edit' => [$this->student->id],
        ]);
    }

    public function test_guru_pages_render_without_errors(): void
    {
        $this->assertPagesRender($this->guru, [
            'guru.dashboard', 'guru.absensi.index', 'guru.absensi', 'guru.presensi.index',
            'guru.absensi.show', 'guru.kehadiran', 'guru.students', 'guru.students.show',
            'guru.rekap', 'guru.scanner',
        ], [
            'guru.absensi.show' => [$this->schoolClass->id],
            'guru.students.show' => [$this->student->id],
        ]);
    }

    public function test_kesiswaan_pages_render_without_errors(): void
    {
        $this->assertPagesRender($this->kesiswaan, [
            'kesiswaan.dashboard', 'kesiswaan.absensi.index', 'kesiswaan.absensi.show',
            'kesiswaan.kehadiran', 'kesiswaan.kehadiran.detail',
            'kesiswaan.students.index', 'kesiswaan.students.show',
            'kesiswaan.classes.index', 'kesiswaan.teachers.index',
            'kesiswaan.rekap.index',
        ], [
            'kesiswaan.absensi.show' => [$this->schoolClass->id],
            'kesiswaan.kehadiran.detail' => [$this->schoolClass->id],
            'kesiswaan.students.show' => [$this->student->id],
        ]);
    }
}

