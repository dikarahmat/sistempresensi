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
 * Memastikan seluruh route penting per role (admin, guru)
 * terdaftar dan dirender tanpa error 404/500, termasuk
 * alur inti presensi (scanner) dan form CRUD siswa.
 */
class ProductionReadinessSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $guru;
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

        $this->teacher = Teacher::create([
            'name' => 'Guru Smoke', 'nip' => '199001012020011001',
            'gender' => 'Laki-laki', 'user_id' => $this->guru->id,
        ]);

        $this->schoolClass = SchoolClass::create([
            'name' => '7A', 'grade' => '7',
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $this->student = Student::create([
            'school_class_id' => $this->schoolClass->id,
            'name' => 'Siswa Smoke', 'nisn' => '0099000111',
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
            'admin.guru.index', 'admin.teachers.index', 'admin.teachers.trash',
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
            'guru.absensi.show', 'guru.absensi.kiosk', 'guru.kiosk',
            'guru.kehadiran', 'guru.kehadiran.detail', 'guru.kehadiran.student-history',
            'guru.students.index', 'guru.students.show', 'guru.classes.index',
            'guru.rekap', 'guru.scanner',
        ], [
            'guru.absensi.show' => [$this->schoolClass->id],
            'guru.kehadiran.detail' => [$this->schoolClass->id],
            'guru.kehadiran.student-history' => [$this->student->id],
            'guru.students.show' => [$this->student->id],
        ]);
    }

    /**
     * Ambil potongan HTML satu kartu (dari awal blok kartu sampai teks "SCAN PRESENSI").
     */
    private function cardHtml(string $html): string
    {
        return \Illuminate\Support\Str::between($html, 'presensi-card-table', 'SCAN PRESENSI');
    }

    public function test_kartu_presensi_urutan_nama_nisn_kelas(): void
    {
        // Siswa kedua dengan nama sangat panjang (2 baris) untuk memastikan ukuran font seragam.
        Student::create([
            'school_class_id' => $this->schoolClass->id,
            'name' => 'Muhammad Abdul Rahman Wahyudi Prabowo Siliwangi',
            'nisn' => '0099000222',
            'gender' => 'Laki-laki',
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.students.print-cards', ['class_id' => $this->schoolClass->id]));
        $response->assertStatus(200);

        $html = (string) $response->getContent();
        $cardA = $this->cardHtml($html);
        // Potongan kartu kedua: mulai setelah "SCAN PRESENSI" kartu pertama.
        $cardB = $this->cardHtml((string) \Illuminate\Support\Str::after($html, 'SCAN PRESENSI'));

        foreach ([$cardA, $cardB] as $card) {
            $this->assertStringContainsString('font-size: 7pt; font-weight: bold; text-transform: uppercase; color: #0f172a; line-height: 1.2;', $card, 'Ukuran & gaya nama harus tetap (7pt, bold, hitam, satu baris) untuk semua kartu.');
            $this->assertStringContainsString('font-size: 6.8pt; font-weight: bold; color: #0f172a; line-height: 1.2;', $card, 'Ukuran & gaya NISN harus tetap (6.8pt, bold, hitam) untuk semua kartu.');
            $this->assertStringContainsString('font-size: 8pt; font-weight: bold; color: #0f172a; line-height: 1.2;', $card, 'Ukuran kelas harus tetap (8pt, bold, hitam).');
            // Jarak antar baris rapat: margin 2px (0,53mm), tanpa wrapper tinggi tetap.
            $this->assertStringContainsString('white-space: nowrap; overflow: hidden; text-overflow: clip;', $card, 'Nama harus satu baris (nowrap) tanpa blok tinggi tetap.');
            $this->assertMatchesRegularExpression('/margin:\s*0\.53mm 0 0 0;/', $card, 'Jarak antar baris harus rapat (margin 2px).');
        }

        // Urutan NAMA -> NISN -> KELAS.
        $posNama = strpos($cardA, 'SISWA SMOKE');
        $posNisn = strpos($cardA, '0099000111');
        // Kelas dicari mulai posisi setelah NISN (base64 logo bisa berisi teks "7A").
        $posKelas = $posNisn !== false ? strpos($cardA, '7A', $posNisn) : false;

        $this->assertNotFalse($posNama, 'Posisi nama tidak ditemukan.');
        $this->assertNotFalse($posNisn, 'Posisi NISN tidak ditemukan.');
        $this->assertNotFalse($posKelas, 'Posisi kelas tidak ditemukan.');
        $this->assertTrue(
            $posNama < $posNisn && $posNisn < $posKelas,
            'Urutan baris kartu harus NAMA -> NISN -> KELAS.'
        );

        // NISN tetap tampil apa adanya sebagai teks 10 digit (angka 0 di depan tetap ada).
        $this->assertStringContainsString('0099000111', $cardA, 'NISN harus tampil apa adanya sebagai teks (angka 0 di depan tetap ada).');
    }

    public function test_kartu_presensi_sembunyikan_baris_nisn_kosong(): void
    {
        $this->student->update(['nisn' => null]);

        $response = $this->actingAs($this->admin)->get(route('admin.students.print-cards', ['class_id' => $this->schoolClass->id]));
        $response->assertStatus(200);

        $card = $this->cardHtml((string) $response->getContent());

        $this->assertStringContainsString('SISWA SMOKE', $card);
        $this->assertStringContainsString('7A', $card);
        $this->assertStringNotContainsString('>-<', $card, 'Baris NISN kosong tidak boleh tampil sebagai "-".');
    }
}

