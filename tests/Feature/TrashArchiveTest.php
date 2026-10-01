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
 * Regresi halaman Arsip (trash) siswa & guru.
 *
 * Bug yang diperbaiki: tombol "Pulihkan" dan "Hapus Permanen" pada halaman Arsip
 * selalu berakhir di halaman 404. Penyebabnya query memakai
 * `whereNotNull('deleted_at')` tanpa `withTrashed()/onlyTrashed()`, sehingga
 * global scope SoftDeletes menambahkan `deleted_at is null` dan query menjadi
 * mustahil (deleted_at is not null AND deleted_at is null) -> firstOrFail()
 * melempar ModelNotFoundException (HTTP 404).
 */
class TrashArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'semester' => 'Ganjil',
            'start_date' => '2025-07-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Arsip',
            'email' => 'admin.arsip@presensi.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);
    }

    private static int $urutanKelas = 0;

    private function buatKelas(): SchoolClass
    {
        self::$urutanKelas++;

        return SchoolClass::create([
            'name' => '7A-' . self::$urutanKelas,
            'grade' => '7',
            'level' => 'VII',
            'academic_year_id' => $this->academicYear->id,
        ]);
    }

    private function buatSiswa(string $nama = 'Siswa Arsip', string $nis = '900001'): Student
    {
        return Student::create([
            'school_class_id' => $this->buatKelas()->id,
            'name' => $nama,
            'nis' => $nis,
            'nisn' => 'NISN' . $nis,
            'gender' => 'Laki-laki',
            'status' => 'Aktif',
        ]);
    }

    private function buatGuru(string $nama = 'Guru Arsip', string $nip = '198001012010011001'): Teacher
    {
        return Teacher::create([
            'name' => $nama,
            'nip' => $nip,
            'gender' => 'Laki-laki',
        ]);
    }

    // =========================================================================
    // ARSIP SISWA
    // =========================================================================

    public function test_halaman_arsip_siswa_menampilkan_hanya_siswa_yang_diarsipkan(): void
    {
        $aktif = $this->buatSiswa('Siswa Masih Aktif', '900002');
        $arsip = $this->buatSiswa('Siswa Sudah Diarsipkan', '900003');
        $arsip->delete();

        $response = $this->actingAs($this->admin)->get(route('admin.students.trash'));

        $response->assertOk();
        $response->assertSee('Siswa Sudah Diarsipkan');
        $response->assertDontSee('Siswa Masih Aktif');
        $this->assertSoftDeleted('students', ['id' => $arsip->id]);
        $this->assertNotSoftDeleted('students', ['id' => $aktif->id]);
    }

    public function test_admin_dapat_memulihkan_siswa_dari_arsip(): void
    {
        $student = $this->buatSiswa('Siswa Untuk Dipulihkan', '900004');
        $student->delete();
        $this->assertSoftDeleted('students', ['id' => $student->id]);

        $response = $this->actingAs($this->admin)->post(route('admin.students.restore', $student->id));

        $response->assertRedirect(route('admin.students.trash'));
        $response->assertSessionHas('success');
        $this->assertNotSoftDeleted('students', ['id' => $student->id]);
        $this->assertNull($student->fresh()->deleted_at);
    }

    public function test_admin_dapat_menghapus_permanen_siswa_dari_arsip(): void
    {
        $student = $this->buatSiswa('Siswa Untuk Dihapus Permanen', '900005');
        $student->delete();
        $this->assertSoftDeleted('students', ['id' => $student->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.students.force-delete', $student->id));

        $response->assertRedirect(route('admin.students.trash'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_hanya_siswa_di_arsip_yang_bisa_dipulihkan_atau_dihapus_permanen(): void
    {
        $aktif = $this->buatSiswa('Siswa Masih Aktif', '900006');

        // ID tidak dikenal -> 404 (bukan error 500)
        $this->actingAs($this->admin)->post(route('admin.students.restore', 999999))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.students.force-delete', 999999))->assertNotFound();

        // Siswa yang masih aktif (belum diarsipkan) tidak boleh diproses dari arsip
        $this->actingAs($this->admin)->post(route('admin.students.restore', $aktif->id))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.students.force-delete', $aktif->id))->assertNotFound();

        $this->assertNotSoftDeleted('students', ['id' => $aktif->id]);
    }

    public function test_hapus_semua_siswa_menghapus_data_aktif_dan_arsip_secara_permanen(): void
    {
        $aktif = $this->buatSiswa('Siswa Aktif Biasa', '900007');
        $arsip = $this->buatSiswa('Siswa Berada Di Arsip', '900008');
        $arsip->delete();

        $response = $this->actingAs($this->admin)->delete(route('admin.students.destroy-all'));

        $response->assertRedirect(route('admin.students.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('students', ['id' => $aktif->id]);
        $this->assertDatabaseMissing('students', ['id' => $arsip->id]);
    }

    // =========================================================================
    // ARSIP GURU
    // =========================================================================

    public function test_halaman_arsip_guru_menampilkan_hanya_guru_yang_diarsipkan(): void
    {
        $aktif = $this->buatGuru('Guru Masih Aktif', '198001012010011002');
        $arsip = $this->buatGuru('Guru Sudah Diarsipkan', '198001012010011003');
        $arsip->delete();

        $response = $this->actingAs($this->admin)->get(route('admin.teachers.trash'));

        $response->assertOk();
        $response->assertSee('Guru Sudah Diarsipkan');
        $response->assertDontSee('Guru Masih Aktif');
        $this->assertSoftDeleted('teachers', ['id' => $arsip->id]);
        $this->assertNotSoftDeleted('teachers', ['id' => $aktif->id]);
    }

    public function test_admin_dapat_memulihkan_guru_dari_arsip(): void
    {
        $teacher = $this->buatGuru('Guru Untuk Dipulihkan', '198001012010011004');
        $teacher->delete();
        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);

        $response = $this->actingAs($this->admin)->post(route('admin.teachers.restore', $teacher->id));

        $response->assertRedirect(route('admin.teachers.trash'));
        $response->assertSessionHas('success');
        $this->assertNotSoftDeleted('teachers', ['id' => $teacher->id]);
    }

    public function test_admin_dapat_menghapus_permanen_guru_dari_arsip(): void
    {
        $teacher = $this->buatGuru('Guru Untuk Dihapus Permanen', '198001012010011005');
        $teacher->delete();
        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.teachers.force-delete', $teacher->id));

        $response->assertRedirect(route('admin.teachers.trash'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
    }
}
