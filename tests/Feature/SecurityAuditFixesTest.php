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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SecurityAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $guruUser;
    protected Teacher $teacher;
    protected SchoolClass $classA;
    protected SchoolClass $classB;
    protected AcademicYear $academicYear;
    protected int $studentId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Nonaktifkan proteksi CSRF agar request POST/PUT/DELETE pada test
        // benar-benar sampai ke middleware role (diharapkan 403, bukan 419).
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

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
            'name' => 'Guru Pengajar A',
            'email' => '198501012010011001@guru.smppresensipgri.sch.id',
            'password' => Hash::make('password_rahasia_guru'),
            'role' => 'guru',
        ]);

        $this->teacher = Teacher::create([
            'name' => 'Guru Pengajar A',
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

        // Siswa pada kedua kelas (dipakai untuk uji akses lintas kelas).
        Student::create([
            'school_class_id' => $this->classA->id,
            'name' => 'Siswa Kelas A',
            'nis' => '770001',
            'gender' => 'Laki-laki',
            'status' => 'Aktif',
        ]);

        $this->studentId = (int) Student::create([
            'school_class_id' => $this->classB->id,
            'name' => 'Siswa Kelas B',
            'nis' => '770002',
            'gender' => 'Perempuan',
            'status' => 'Aktif',
        ])->id;
    }

    /**
     * 1. DASHBOARD GURU (READ-ONLY): guru boleh melihat SEMUA data seperti admin
     *    (tanpa batasan kelas binaan), namun seluruh endpoint admin-only tetap
     *    diblokir di sisi server dengan HTTP 403.
     */
    public function test_guru_sees_all_data_but_is_blocked_from_admin_endpoints(): void
    {
        // Guru dapat melihat seluruh data tanpa batasan kelas binaan.
        $this->actingAs($this->guruUser)
            ->get(route('guru.dashboard', ['school_class_id' => $this->classB->id]))
            ->assertStatus(200);

        $this->actingAs($this->guruUser)
            ->get(route('guru.students.index', ['class_id' => $this->classB->id]))
            ->assertStatus(200);

        $this->actingAs($this->guruUser)
            ->get(route('guru.classes.index'))
            ->assertStatus(200);

        $this->actingAs($this->guruUser)
            ->get(route('guru.rekap', ['class_id' => $this->classB->id]))
            ->assertStatus(200);

        // Server-side enforcement: halaman admin-only -> 403
        $adminOnlyPages = [
            '/admin/dashboard',
            '/admin/students',
            '/admin/students/create',
            '/admin/students/trash',
            '/admin/guru',
            '/admin/teachers',
            '/admin/teachers/trash',
            '/admin/classes',
            '/admin/settings/academic-years',
            '/admin/settings/hari-libur',
            '/admin/settings',
            '/admin/roles-permissions',
            '/admin/pengaturan/jadwal',
        ];

        foreach ($adminOnlyPages as $url) {
            $this->actingAs($this->guruUser)
                ->get($url)
                ->assertStatus(403, "GET {$url} harus diblokir (403) untuk role guru.");
        }

        // Server-side enforcement: request mutasi (POST/PUT/DELETE) -> 403
        $this->actingAs($this->guruUser)
            ->post('/admin/students', ['name' => 'Hacker', 'nis' => '123456', 'school_class_id' => $this->classA->id, 'gender' => 'Laki-laki'])
            ->assertStatus(403);

        $this->actingAs($this->guruUser)
            ->put('/admin/students/' . $this->studentId, ['name' => 'Hacked', 'nis' => '770002', 'school_class_id' => $this->classB->id, 'gender' => 'Perempuan'])
            ->assertStatus(403);

        $this->actingAs($this->guruUser)
            ->delete('/admin/students/' . $this->studentId)
            ->assertStatus(403);

        $this->actingAs($this->guruUser)
            ->post('/admin/absensi/override', ['student_id' => 1, 'status' => 'Hadir'])
            ->assertStatus(403);

        $this->actingAs($this->guruUser)
            ->post('/admin/classes', ['name' => '9Z'])
            ->assertStatus(403);
    }

    /**
     * 2. VERIFICATION TEST: Pastikan fitur WhatsApp sudah dihapus total dari codebase.
     */
    public function test_whatsapp_feature_is_completely_removed(): void
    {
        // Pastikan class WhatsAppService tidak ada
        $this->assertFalse(class_exists(\App\Services\WhatsAppService::class));

        // Pastikan class SendWhatsAppAttendanceNotificationJob tidak ada
        $this->assertFalse(class_exists(\App\Jobs\SendWhatsAppAttendanceNotificationJob::class));

        // Pastikan tidak ada file WhatsAppService
        $this->assertFileDoesNotExist(app_path('Services/WhatsAppService.php'));

        // Pastikan tidak ada file SendWhatsAppAttendanceNotificationJob
        $this->assertFileDoesNotExist(app_path('Jobs/SendWhatsAppAttendanceNotificationJob.php'));

        // Pastikan tidak ada setting WhatsApp di database
        $this->assertDatabaseMissing('settings', ['key' => 'whatsapp_gateway_status']);
        $this->assertDatabaseMissing('settings', ['key' => 'whatsapp_api_token']);
        $this->assertDatabaseMissing('settings', ['key' => 'whatsapp_sender']);
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
                'nama' => 'Guru Pengajar A (Updated Name)',
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
        $this->assertEquals('Guru Pengajar A (Updated Name)', $this->guruUser->name);
        // Password TIDAK BOLEH berubah/tertimpa
        $this->assertEquals($existingPasswordHash, $this->guruUser->password);
        $this->assertTrue(Hash::check('password_rahasia_guru', $this->guruUser->password));

        // Guru baru dibuat dengan default password NIP
        $newGuruUser = User::where('email', '199001012020011005@guru.smppresensipgri.sch.id')->first();
        $this->assertNotNull($newGuruUser);
        $this->assertTrue(Hash::check('199001012020011005', $newGuruUser->password));
    }

    /**
     * 4. HIGH UI LEAK TEST: Panel guru (read-only) tidak boleh menampilkan tombol
     *    Tambah, Import, Arsip, Edit, maupun Hapus. Hanya CETAK KARTU & DETAIL.
     *    Sebaliknya admin tetap melihat tombol CRUD-nya seperti biasa.
     */
    public function test_guru_readonly_view_does_not_leak_mutation_crud_buttons(): void
    {
        // --- Data Siswa: hanya CETAK KARTU + kolom DETAIL ---
        $siswa = $this->actingAs($this->guruUser)->get(route('guru.students.index'));
        $siswa->assertStatus(200);
        $siswa->assertSee('Cetak Kartu');
        $siswa->assertDontSee('Import Excel');
        $siswa->assertDontSee('Tambah Siswa');
        $siswa->assertDontSee('data-bs-target="#importModal"', false);
        $siswa->assertDontSee('data-bs-target="#addStudentModal"', false);
        $siswa->assertDontSee('students.edit', false);
        $siswa->assertDontSee('students.destroy', false);

        // --- Data Kelas: READ-ONLY ---
        $kelas = $this->actingAs($this->guruUser)->get(route('guru.classes.index'));
        $kelas->assertStatus(200);
        $kelas->assertDontSee('Tambah Kelas');
        $kelas->assertDontSee('data-bs-target="#addClassModal"', false);
        $kelas->assertDontSee('data-bs-target="#importClassModal"', false);
        $kelas->assertDontSee('classes.update', false);
        $kelas->assertDontSee('classes.destroy', false);

        // --- Detail Presensi Kelas: tidak ada tombol/modal ubah status ---
        $presensi = $this->actingAs($this->guruUser)->get(route('guru.absensi.show', $this->classA->id));
        $presensi->assertStatus(200);
        $presensi->assertDontSee('Ubah Presensi Siswa');
        $presensi->assertDontSee('absensi/override', false);

        // --- Sisi admin: tombol CRUD tetap tampil & berfungsi seperti biasa ---
        $adminResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.guru.index'));

        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Tambah Guru');
        $adminResponse->assertSee('Import Excel');
    }
}
