<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SchoolClassUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_school_class_teacher_id(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin_test@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
            'start_date' => '2025-07-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
        ]);

        $teacher1 = Teacher::create([
            'name' => 'Guru 1',
            'nip' => '12345678',
            'gender' => 'Laki-laki',
        ]);

        $teacher2 = Teacher::create([
            'name' => 'Guru 2',
            'nip' => '87654321',
            'gender' => 'Perempuan',
        ]);

        $class = SchoolClass::create([
            'name' => '7A',
            'grade' => '7',
            'level' => 'VII',
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher1->id,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.classes.update', $class->id), [
            'name' => '7A',
            'grade' => '7',
            'teacher_id' => $teacher2->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.classes.index'));

        $this->assertDatabaseHas('school_classes', [
            'id' => $class->id,
            'teacher_id' => $teacher2->id,
        ]);
    }

    public function test_can_set_teacher_id_to_null(): void
    {
        $admin = User::create([
            'name' => 'Admin Test 2',
            'email' => 'admin_test2@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
            'start_date' => '2025-07-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
        ]);

        $teacher1 = Teacher::create([
            'name' => 'Guru 1',
            'nip' => '123456789',
            'gender' => 'Laki-laki',
        ]);

        $class = SchoolClass::create([
            'name' => '7B',
            'grade' => '7',
            'level' => 'VII',
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher1->id,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.classes.update', $class->id), [
            'name' => '7B',
            'grade' => '7',
            'teacher_id' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.classes.index'));

        $this->assertDatabaseHas('school_classes', [
            'id' => $class->id,
            'teacher_id' => null,
        ]);
    }
}
