<?php

namespace Tests\Feature;

use App\Imports\TeachersImport;
use App\Jobs\SendWhatsAppAttendanceNotificationJob;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SecurityAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $guruUser;
    protected User $kesiswaanUser;
    protected Teacher $teacher;
    protected SchoolClass $classA;
    protected SchoolClass $classB;
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

        $this->adminUser = User::create([
            'name' => 'Admin SMP',
            'email' => 'admin@smppresensipgri.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->guruUser = User::create([
            'name' => 'Guru Wali Kelas A',
            'email' => '198501012010011001@guru.smppresensipgri.sch.id',
            'password' => Hash::make('password_rahasia_guru'),
            'role' => 'guru',
        ]);

        $this->teacher = Teacher::create([
            'name' => 'Guru Wali Kelas A',
            'nip' => '198501012010011001',
            'user_id' => $this->guruUser->id,
            'gender' => 'Laki-laki',
        ]);

        $this->classA = SchoolClass::create([
            'name' => '7A',
            'grade' => '7',
            'teacher_id' => $this->teacher->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->classB = SchoolClass::create([
            'name' => '7B',
            'grade' => '7',
            'teacher_id' => null, // bukan kelas binaan Guru A
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->kesiswaanUser = User::create([
            'name' => 'Staf Kesiswaan',
            'email' => 'kesiswaan@smppresensipgri.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'kesiswaan',
        ]);
    }

    /**
     * 1. CRITICAL IDOR TEST: Guru HANYA boleh mengakses kelas binaannya sendiri.
     * Mengakses class_id milik kelas lain harus memicu abort(403).
     */
    public function test_guru_cannot_access_unauthorized_class_via_idor(): void
    {
        // Akses kelas miliknya sendiri: Berhasil (200 OK)
        $this->actingAs($this->guruUser)
            ->get(route('guru.dashboard', ['school_class_id' => $this->classA->id]))
            ->assertStatus(200);

        $this->actingAs($this->guruUser)
            ->get(route('guru.students', ['school_class_id' => $this->classA->id]))
            ->assertStatus(200);

        // IDOR Attack: Memaksa mengakses kelas 7B yang bukan kelas binaannya
        $this->actingAs($this->guruUser)
            ->get(route('guru.dashboard', ['school_class_id' => $this->classB->id]))
            ->assertStatus(403);

        $this->actingAs($this->guruUser)
            ->get(route('guru.students', ['school_class_id' => $this->classB->id]))
            ->assertStatus(403);

        $this->actingAs($this->guruUser)
            ->get(route('guru.rekap', ['class_id' => $this->classB->id]))
            ->assertStatus(403);
    }

    /**
     * 2. CRITICAL BOTTLENECK TEST: Notifikasi WhatsApp harus dikirim secara Asynchronous via Queue Job.
     */
    public function test_whatsapp_notification_is_dispatched_via_asynchronous_queue_job(): void
    {
        Queue::fake();

        // Aktifkan WhatsApp gateway di setting
        Setting::set('whatsapp_gateway_status', 'active');
        Setting::set('whatsapp_api_token', 'test_token_123');

        $student = Student::create([
            'school_class_id' => $this->classA->id,
            'nis' => '12345',
            'name' => 'Ahmad Siswa',
            'gender' => 'Laki-laki',
            'parent_phone' => '081234567890',
            'status' => 'active',
            'qr_token' => 'QR-12345',
        ]);

        $this->actingAs($this->adminUser)
            ->postJson(route('admin.scanner.process'), [
                'qr_token' => 'QR-12345',
                'type' => 'masuk',
            ])
            ->assertStatus(200);

        // Pastikan Job ter-dispatch ke queue
        Queue::assertPushed(SendWhatsAppAttendanceNotificationJob::class, function ($job) use ($student) {
            return $job->student->id === $student->id;
        });
    }

    /**
     * 3. HIGH VULNERABILITY TEST: Re-import Excel guru TIDAK BOLEH mereset password user guru yang sudah ada.
     */
    public function test_excel_teacher_import_does_not_overwrite_existing_user_password(): void
    {
        $existingPasswordHash = $this->guruUser->password;

        $import = new TeachersImport();
        $import->collection(collect([
            [
                'nama' => 'Guru Wali Kelas A (Updated Name)',
                'nip' => '198501012010011001',
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Bogor',
                'tanggal_lahir' => '1985-01-01',
                'no_hp' => '08987654321',
            ],
            // Guru Baru
            [
                'nama' => 'Guru Baru S.Pd.',
                'nip' => '199001012020011005',
                'jenis_kelamin' => 'Perempuan',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1990-01-01',
                'no_hp' => '08111222333',
            ],
        ]));

        $this->guruUser->refresh();

        // Nama terupdate
        $this->assertEquals('Guru Wali Kelas A (Updated Name)', $this->guruUser->name);
        // Password TIDAK BOLEH berubah/tertimpa
        $this->assertEquals($existingPasswordHash, $this->guruUser->password);
        $this->assertTrue(Hash::check('password_rahasia_guru', $this->guruUser->password));

        // Guru baru dibuat dengan default password NIP
        $newGuruUser = User::where('email', '199001012020011005@guru.smppresensipgri.sch.id')->first();
        $this->assertNotNull($newGuruUser);
        $this->assertTrue(Hash::check('199001012020011005', $newGuruUser->password));
    }

    /**
     * 4. HIGH UI LEAK TEST: Role Kesiswaan tidak boleh melihat tombol Tambah, Import, Hapus Semua, dan Aksi CRUD pada daftar guru.
     */
    public function test_kesiswaan_teacher_view_does_not_leak_mutation_crud_buttons(): void
    {
        $response = $this->actingAs($this->kesiswaanUser)
            ->get(route('kesiswaan.teachers.index'));

        $response->assertStatus(200);

        // Tombol-tombol mutasi admin tidak boleh ada di halaman kesiswaan
        $response->assertDontSee('Import Excel');
        $response->assertDontSee('Hapus Semua Data Guru');
        $response->assertDontSee('Tambah Guru');
        $response->assertDontSee('data-bs-target="#addTeacherModal"', false);
        $response->assertDontSee('data-bs-target="#importTeacherModal"', false);
        $response->assertDontSee('id="deleteAllTeachersForm"', false);

        // Jika Admin mengakses, tombol mutasi harus tampil
        $adminResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.walikelas.index'));

        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Tambah Guru');
        $adminResponse->assertSee('Import Excel');
    }
}
