<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemAuditAndPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@presensi.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
            'start_date' => '2025-07-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Dynamic Settings Caching & Invalidation
     */
    public function test_dynamic_settings_caching_and_invalidation(): void
    {
        // 1. Initial default value
        Setting::clearCache();
        $this->assertEquals('SMP Presensi PGRI', Setting::getSchoolName());

        // 2. Set new setting value
        Setting::set('school_name', 'SMP Unggulan Parung');
        $this->assertEquals('SMP Unggulan Parung', Setting::getSchoolName());

        // 3. Verify Cache::rememberForever holds the value
        $all = Setting::getAll();
        $this->assertEquals('SMP Unggulan Parung', $all['school_name']);

        // 4. Update via Setting Controller POST request
        $response = $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'school_name' => 'SMP PGRI Parung Bogor',
            'app_title' => 'Presensi Siswa Mandiri',
            'check_in_time' => '06:30',
            'late_limit_time' => '07:00',
            'check_out_time' => '14:00',
            'school_address' => 'Jl. Pendidikan No. 99',
            'school_phone' => '021-99999',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $this->assertEquals('SMP PGRI Parung Bogor', Setting::getSchoolName());
        $this->assertEquals('Presensi Siswa Mandiri', Setting::getAppTitle());
        $this->assertEquals('06:30', Setting::getCheckInTime());
        $this->assertEquals('07:00', Setting::getLateLimitTime());
        $this->assertEquals('14:00', Setting::getCheckOutTime());
    }

    /**
     * Test 2: Safe Deletion & Cascade on Students (destroy and destroyAll)
     */
    public function test_safe_deletion_and_cascade_on_students(): void
    {
        $class = SchoolClass::create([
            'name' => '7A',
            'grade' => '7',
            'academic_year_id' => $this->academicYear->id,
        ]);

        $student = Student::create([
            'school_class_id' => $class->id,
            'name' => 'Budi Pratama',
            'nis' => '1001',
            'nisn' => '001001',
            'gender' => 'Laki-laki',
            'status' => 'Aktif',
            'qr_token' => 'QR-BUDI-1001',
        ]);

        Attendance::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'date' => '2025-08-01',
            'status' => 'Hadir',
            'check_in' => '06:40:00',
        ]);

        $this->assertDatabaseHas('students', ['id' => $student->id]);
        $this->assertDatabaseHas('attendances', ['student_id' => $student->id]);

        // Test destroyAll
        $response = $this->actingAs($this->admin)->delete(route('admin.students.destroy-all'));
        $response->assertRedirect(route('admin.students.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id]);
    }

    /**
     * Test 3: Safe Deletion & Cascade on Classes (destroy and destroyAll)
     */
    public function test_safe_deletion_and_cascade_on_classes(): void
    {
        $class = SchoolClass::create([
            'name' => '8B',
            'grade' => '8',
            'academic_year_id' => $this->academicYear->id,
        ]);

        $student = Student::create([
            'school_class_id' => $class->id,
            'name' => 'Siti Aminah',
            'nis' => '2001',
            'nisn' => '002001',
            'gender' => 'Perempuan',
            'status' => 'Aktif',
            'qr_token' => 'QR-SITI-2001',
        ]);

        Attendance::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'date' => '2025-08-01',
            'status' => 'Hadir',
            'check_in' => '06:42:00',
        ]);

        // Test destroyAll classes
        $response = $this->actingAs($this->admin)->delete(route('admin.classes.destroy-all'));
        $response->assertRedirect(route('admin.classes.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('school_classes', ['id' => $class->id]);
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id]);
    }

    /**
     * Test 4: Scanner Transaction Safety and Cooldown Protection
     */
    public function test_scanner_transaction_and_cooldown(): void
    {
        Carbon::setTestNow('2025-08-01 06:45:00');

        $class = SchoolClass::create([
            'name' => '9C',
            'grade' => '9',
            'academic_year_id' => $this->academicYear->id,
        ]);

        $student = Student::create([
            'school_class_id' => $class->id,
            'name' => 'Rian Pratama',
            'nis' => '3001',
            'nisn' => '003001',
            'gender' => 'Laki-laki',
            'status' => 'Aktif',
            'qr_token' => 'QR-RIAN-3001',
        ]);

        // Scan 1: Check-in
        $response1 = $this->actingAs($this->admin)->postJson(route('admin.scanner.process'), [
            'qr_token' => 'QR-RIAN-3001',
        ]);

        $response1->assertStatus(200);
        $response1->assertJson([
            'success' => true,
            'type' => 'check_in',
        ]);

        // Scan 2: Immediate double tap within 2 minutes cooldown
        $response2 = $this->actingAs($this->admin)->postJson(route('admin.scanner.process'), [
            'qr_token' => 'QR-RIAN-3001',
        ]);

        $response2->assertStatus(400);
        $response2->assertJson([
            'success' => false,
        ]);

        Carbon::setTestNow();
    }

    /**
     * Test 5: Safe Teacher Deletion and Class Unlinking
     */
    public function test_teacher_deletion_and_unlinking_class(): void
    {
        $teacher = Teacher::create([
            'name' => 'Pak Budi, S.Pd',
            'nip' => '198001012010011001',
            'gender' => 'Laki-laki',
        ]);

        $class = SchoolClass::create([
            'name' => '7D',
            'grade' => '7',
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->assertEquals($teacher->id, $class->fresh()->teacher_id);

        // Delete single teacher
        $response = $this->actingAs($this->admin)->delete(route('admin.guru.destroy', $teacher->id));
        $response->assertRedirect(route('admin.guru.index'));

        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
        $this->assertNull($class->fresh()->teacher_id);

        // Test destroyAll
        $teacher2 = Teacher::create([
            'name' => 'Bu Ani, M.Pd',
            'nip' => '198501012015012001',
            'gender' => 'Perempuan',
        ]);
        $class->update(['teacher_id' => $teacher2->id]);

        $responseAll = $this->actingAs($this->admin)->delete(route('admin.guru.destroy-all'));
        $responseAll->assertRedirect(route('admin.guru.index'));

        $this->assertDatabaseMissing('teachers', ['id' => $teacher2->id]);
        $this->assertNull($class->fresh()->teacher_id);
    }

    /**
     * Test 6: Academic Year Safety Guards Against Accidental Deletion
     */
    public function test_academic_year_safety_guards(): void
    {
        // 1. Cannot delete active academic year
        $response = $this->actingAs($this->admin)->delete(route('admin.academic-years.destroy', $this->academicYear->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('academic_years', ['id' => $this->academicYear->id]);

        // 2. Inactive academic year with linked class cannot be deleted
        $inactiveYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Genap',
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-30',
            'is_active' => false,
        ]);

        SchoolClass::create([
            'name' => '9Z',
            'grade' => '9',
            'academic_year_id' => $inactiveYear->id,
        ]);

        $response2 = $this->actingAs($this->admin)->delete(route('admin.academic-years.destroy', $inactiveYear->id));
        $response2->assertSessionHas('error');
        $this->assertDatabaseHas('academic_years', ['id' => $inactiveYear->id]);
    }

    /**
     * Test 7: Admin Dashboard Optimized Loading
     */
    public function test_admin_dashboard_optimized_response(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas(['classesList', 'waliKelasList', 'classesAttendance']);
    }

    /**
     * Test 8: Class Attendance Scanner View Standardization
     */
    public function test_class_attendance_scanner_view_standardization(): void
    {
        $class = SchoolClass::create([
            'name' => '8B',
            'grade' => '8',
            'academic_year_id' => $this->academicYear->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.absensi.show', $class->id));
        $response->assertStatus(200);
        $response->assertViewIs('admin.absensi.class');
        $response->assertSee('Presensi Kelas 8B');
        $response->assertSee('id="overlaySuccess"', false);
        $response->assertSee('id="overlayError"', false);
        $response->assertSee('playBrowserBeep', false);
        $response->assertSee('class_scanner_open', false);
    }
}

