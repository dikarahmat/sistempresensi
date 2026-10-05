<?php

namespace Tests\Feature;

use App\Imports\TeachersImport;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseOverhaulSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class MultiRoleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan akun Admin jika belum ada
        User::updateOrCreate(
            ['email' => 'admin@smppresensipgri.sch.id'],
            [
                'name' => 'Administrator SMP PGRI',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // Siapkan akun Guru & Teacher jika belum ada
        $guruUser = User::updateOrCreate(
            ['email' => '198503122010011002@guru.smppresensipgri.sch.id'],
            [
                'name' => 'Budi Santoso, S.Pd.',
                'password' => Hash::make('198503122010011002'),
                'role' => 'guru',
            ]
        );

        Teacher::updateOrCreate(
            ['nip' => '198503122010011002'],
            [
                'user_id' => $guruUser->id,
                'name' => 'Budi Santoso, S.Pd.',
                'gender' => 'Laki-laki',
                'phone' => '081234567890',
            ]
        );

        // Catatan: sistem hanya memiliki dua role, yaitu admin dan guru.
    }

    public function test_login_page_renders_universal_form(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk Sekarang');
        $response->assertSee('Username');
        $response->assertSee('Kata Sandi');
        $response->assertDontSee('btn-tab-choice');
    }

    public function test_admin_can_login_and_is_redirected_to_admin_dashboard(): void
    {
        $response = $this->post('/login/admin', [
            'login' => 'admin@smppresensipgri.sch.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals('admin', auth()->user()->role);
    }

    public function test_guru_can_login_using_nip_and_is_redirected_to_guru_dashboard(): void
    {
        $response = $this->post('/login/guru', [
            'nip' => '198503122010011002',
            'password' => '198503122010011002',
        ]);

        $response->assertRedirect(route('guru.dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals('guru', auth()->user()->role);
    }

    public function test_only_admin_and_guru_roles_are_supported(): void
    {
        // Role di luar admin & guru sudah tidak dikenali sistem (dibersihkan total).
        $this->assertEquals(0, User::whereNotIn('role', ['admin', 'guru'])->count());

        // Root route mengarahkan admin ke /admin/dashboard dan guru ke /guru/dashboard.
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->get('/')->assertRedirect(route('admin.dashboard'));

        $guru = User::where('role', 'guru')->first();
        $this->actingAs($guru)->get('/')->assertRedirect(route('guru.dashboard'));
    }

    public function test_role_security_middleware_aborts_403_for_unauthorized_panels(): void
    {
        $admin = User::where('email', 'admin@smppresensipgri.sch.id')->first();
        $guru = User::where('role', 'guru')->first();

        // 1. Admin tidak boleh masuk ke panel guru -> 403
        $this->actingAs($admin)->get('/guru/dashboard')->assertStatus(403);

        // 2. Guru tidak boleh masuk ke panel admin -> 403
        $this->actingAs($guru)->get('/admin/dashboard')->assertStatus(403);
    }

    public function test_smart_login_endpoint_auto_routes_based_on_credentials(): void
    {
        // 1. Universal login guru via NIP
        $responseGuru = $this->post('/login', [
            'login' => '198503122010011002',
            'password' => '198503122010011002',
        ]);
        $responseGuru->assertRedirect(route('guru.dashboard'));

        $this->post('/logout');

        // 2. Universal login admin via email
        $responseAdmin = $this->post('/login', [
            'login' => 'admin@smppresensipgri.sch.id',
            'password' => 'password123',
        ]);
        $responseAdmin->assertRedirect(route('admin.dashboard'));
    }

    public function test_logout_button_opens_confirmation_modal_instead_of_logging_out_directly(): void
    {
        $admin = User::where('email', 'admin@smppresensipgri.sch.id')->first();

        // Halaman dashboard harus memuat modal konfirmasi Log Out.
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('id="logoutConfirmModal"', false);
        $response->assertSee('logoutConfirmModalLabel');
        $response->assertSee('Log Out');
        $response->assertSee('Apakah Anda yakin untuk Log Out?');
        $response->assertSee('Batal');
        $response->assertSee('Ya, Log Out');

        // Tombol Log Out di sidebar harus memicu modal, bukan mengirim form langsung.
        $response->assertSee('data-bs-target="#logoutConfirmModal"', false);
        $response->assertSee('data-bs-toggle="modal"', false);

        // Membuka halaman TIDAK boleh mengeluarkan user (belum ada konfirmasi).
        $this->assertAuthenticated();

        // Form di dalam modal harus POST ke route logout (bukan GET) & punya CSRF.
        $response->assertSee('action="'.route('logout').'" method="POST"', false);
    }

    public function test_logout_confirmation_modal_is_shared_across_sidebar_pages(): void
    {
        $admin = User::where('email', 'admin@smppresensipgri.sch.id')->first();

        // Modal diletakkan di layout bersama, jadi harus tersedia di SETIAP halaman
        // yang memakai layout sidebar - bukan hanya dashboard.
        foreach ([route('admin.dashboard'), route('admin.settings.index')] as $url) {
            $response = $this->actingAs($admin)->get($url);
            $response->assertOk();
            $response->assertSee('id="logoutConfirmModal"', false);
            $response->assertSee('Apakah Anda yakin untuk Log Out?');
            $response->assertSee('Ya, Log Out');
            $this->assertAuthenticated();
        }
    }

    /**
     * REGRESI TUGAS 1: modal Log Out harus benar-benar terpusat di tengah VIEWPORT.
     *
     * Akar masalah yang diperbaiki: <form> pernah diletakkan LANGSUNG sebagai anak
     * dari .modal-dialog-centered. Bootstrap hanya mengatur align-items (vertikal)
     * pada .modal-dialog-centered dan tidak pernah mengatur justify-content, sehingga
     * <form> sebagai flex item menempel di sisi kiri dialog (flex-start).
     *
     * Test ini mengunci struktur & CSS yang membuat modal terpusat.
     */
    public function test_logout_modal_is_centered_in_viewport(): void
    {
        $admin = User::where('email', 'admin@smppresensipgri.sch.id')->first();
        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->getContent();

        // 1. .modal-content harus anak LANGSUNG dari .modal-dialog (bukan <form>).
        $this->assertMatchesRegularExpression(
            '/<div class="modal-dialog modal-dialog-centered">\s*<div class="modal-content[^"]*">\s*<form /',
            $html,
            '.modal-content harus menjadi anak langsung .modal-dialog, dan <form> berada di dalamnya.'
        );

        // 2. <form> tidak boleh menjadi anak langsung .modal-dialog.
        $this->assertDoesNotMatchRegularExpression(
            '/<div class="modal-dialog modal-dialog-centered">\s*<form /',
            $html,
            '<form> tidak boleh menjadi anak langsung .modal-dialog (menyebabkan modal tidak terpusat).'
        );

        // 3. Overlay area gelap dipusatkan di tengah viewport.
        $this->assertStringContainsString('#logoutConfirmModal.show', $html);
        $this->assertStringContainsString('display: flex !important;', $html);
        $this->assertStringContainsString('justify-content: center;', $html);

        // 4. Modal seragam (desktop 440px, mobile min(100vw-44px, 400px))
        //    dan margin samping 22px di layar kecil.
        $this->assertStringContainsString('max-width: 440px', $html);
        $this->assertStringContainsString('width: min(calc(100vw - 44px), 400px)', $html);
        $this->assertStringContainsString('margin: 22px auto', $html);

        // 5. Overlay menutupi viewport penuh dan TIDAK memakai trik offset.
        $this->assertStringContainsString('#logoutConfirmModal {', $html);
        $this->assertStringContainsString('inset: 0;', $html);
    }

    /**
     * REGRESI TUGAS 1: isi modal harus seimbang (teks & tombol rata tengah,
     * dua tombol sama lebar, tombol logout tetap merah).
     */
    public function test_logout_modal_content_and_buttons_are_balanced(): void
    {
        $admin = User::where('email', 'admin@smppresensipgri.sch.id')->first();
        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->getContent();

        // Teks pertanyaan rata tengah.
        $this->assertStringContainsString('#logoutConfirmModal .modal-body {', $html);

        // Dua tombol sejajar, jarak sama, ukuran konsisten: lebar sama (50%),
        // tinggi sama (46px), radius sama (12px).
        $this->assertStringContainsString('#logoutConfirmModal .modal-footer {', $html);
        $this->assertStringContainsString('#logoutConfirmModal .modal-footer .btn {', $html);
        $this->assertStringContainsString('flex: 1 1 50% !important;', $html);
        $this->assertStringContainsString('height: 42px !important;', $html);
        $this->assertStringContainsString('border-radius: 10px !important;', $html);
        $this->assertStringContainsString('gap: 10px !important;', $html);

        // Tombol BATAL & YA, LOG OUT tetap ada; YA, LOG OUT tetap merah (btn-danger).
        $this->assertStringContainsString('data-bs-dismiss="modal"', $html);
        $this->assertStringContainsString('>Batal</button>', $html);
        $this->assertStringContainsString('btn btn-danger', $html);
        $this->assertStringContainsString('>Ya, Log Out</button>', $html);
    }

    // =====================================================================
    // REGRESI TUGAS 2 - DATA HILANG & LOGIN GAGAL.
    //
    // Kasus yang pernah terjadi: tabel users, teachers, school_classes,
    // academic_years, students, dan settings tiba-tiba kosong, sehingga login
    // admin/guru gagal dengan pesan "Username atau kata sandi yang Anda masukkan
    // salah", padahal migrasi sudah pernah dijalankan.
    //
    //   A. Test WAJIB berjalan di SQLite in-memory, tidak pernah MySQL.
    //   B. Tabel users tidak boleh tersentuh fitur hapus massal, dan akun admin
    //      tidak boleh bisa dihapus.
    //   C & D. Seeder idempotent, konsisten, dan tidak mereset data master.
    //   E & F. Pesan login generik dan tidak ada auto-create akun.
    // =====================================================================

    private function adminUji(): User
    {
        return User::create([
            'name' => 'Admin Uji',
            'username' => 'admin_uji',
            'email' => 'admin.uji@presensi.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'admin',
        ]);
    }

    private function tahunUji(): AcademicYear
    {
        return AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'Ganjil',
            'start_date' => '2025-07-01', 'end_date' => '2025-12-31',
            'is_active' => true,
        ]);
    }

    /**
     * A. TEST TIDAK BOLEH MENYENTUH MYSQL.
     */
    public function test_test_suite_selalu_berjalan_di_sqlite_in_memory(): void
    {
        // Bila guard di tests/TestCase.php bekerja, nilai di sini pasti sqlite.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    /**
     * B. TABEL USERS TERLINDUNGI.
     */
    public function test_akun_admin_tidak_bisa_dihapus_lewat_aplikasi(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@presensi.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        $this->assertTrue($admin->isProtectedAccount());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Akun admin tidak dapat dihapus.');

        $admin->delete();
    }

    public function test_akun_guru_biasa_tetap_bisa_dihapus(): void
    {
        $guru = User::create([
            'name' => 'Guru Biasa',
            'username' => 'guru_biasa',
            'email' => 'guru.biasa@presensi.test',
            'password' => Hash::make('guru123'),
            'role' => 'guru',
        ]);

        $this->assertFalse($guru->isProtectedAccount());

        $guru->delete();

        $this->assertDatabaseMissing('users', ['id' => $guru->id]);
    }

    public function test_hapus_semua_siswa_tidak_menyentuh_tabel_users(): void
    {
        $admin = $this->adminUji();
        $jumlahUserAwal = User::count();

        $class = SchoolClass::create([
            'name' => '7A', 'grade' => '7', 'academic_year_id' => $this->tahunUji()->id,
        ]);
        Student::create([
            'school_class_id' => $class->id, 'name' => 'Siswa Uji', 'nis' => '7701',
            'gender' => 'Laki-laki', 'status' => 'Aktif',
        ]);

        // Tombol "HAPUS SEMUA SISWA (PERMANEN)" -> destroy-all-active:
        // soft-delete massal (masuk Tempat Sampah), butuh 2 checkbox.
        $this->actingAs($admin)->delete(route('admin.students.destroy-all-active'), [
            'confirm_active' => '1',
            'confirm_all' => '1',
        ])->assertRedirect(route('admin.students.index'));

        $this->assertSame(0, Student::count());
        $this->assertSame(1, Student::withTrashed()->count());
        $this->assertDatabaseCount('users', $jumlahUserAwal);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_hapus_semua_guru_dan_kelas_tidak_menyentuh_tabel_users(): void
    {
        $admin = $this->adminUji();
        $jumlahUserAwal = User::count();

        $teacher = Teacher::create([
            'name' => 'Guru Uji', 'nip' => '198001012010011009', 'gender' => 'Laki-laki',
        ]);
        SchoolClass::create([
            'name' => '7B', 'grade' => '7', 'academic_year_id' => $this->tahunUji()->id,
            'teacher_id' => $teacher->id,
        ]);

        // Tombol "HAPUS SEMUA GURU" -> soft-delete massal (masuk Tempat Sampah).
        $this->actingAs($admin)->delete(route('admin.guru.destroy-all'))
            ->assertRedirect(route('admin.guru.index'));
        $this->assertSame(0, Teacher::count());
        $this->assertSame(2, Teacher::withTrashed()->count());

        // Tombol "HAPUS SEMUA KELAS" -> kelas kosong masuk sampah (soft-delete).
        $this->actingAs($admin)->delete(route('admin.classes.destroy-all'))
            ->assertRedirect(route('admin.classes.index'));
        $this->assertSame(0, SchoolClass::count());

        // Tabel users tetap utuh.
        $this->assertDatabaseCount('users', $jumlahUserAwal);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_hapus_permanen_guru_dari_arsip_tidak_menghapus_akun_users(): void
    {
        $admin = $this->adminUji();
        $guruUser = User::create([
            'name' => 'Guru Arsip', 'username' => 'guru_arsip',
            'email' => 'guru.arsip@presensi.test',
            'password' => Hash::make('guru123'), 'role' => 'guru',
        ]);
        $teacher = Teacher::create([
            'name' => 'Guru Arsip', 'nip' => '198001012010011010',
            'gender' => 'Laki-laki', 'user_id' => $guruUser->id,
        ]);
        $teacher->delete(); // masuk arsip

        $this->actingAs($admin)->delete(route('admin.teachers.force-delete', $teacher->id))
            ->assertRedirect(route('admin.teachers.trash'));

        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
        // Akun login guru harus tetap ada agar tidak terkunci.
        $this->assertDatabaseHas('users', ['id' => $guruUser->id]);
    }

    public function test_import_guru_tidak_menghapus_akun_admin(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@presensi.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        $import = new TeachersImport();
        $import->collection(collect([
            ['nama' => 'Guru Baru', 'nip' => '199001012020011099', 'jenis_kelamin' => 'Laki-laki', 'no_hp' => '08111222333'],
        ]));

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertSame(1, $import->getImportedCount());
    }

    /**
     * C & D. SEEDER KONSISTEN, IDEMPOTENT, DAN TIDAK MERESET DATA MASTER.
     */
    public function test_seeder_membuat_akun_admin_dan_guru_dengan_kredensial_konsisten(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'username' => 'admin', 'role' => 'admin', 'email' => 'admin@presensi.com',
        ]);
        $this->assertDatabaseHas('users', [
            'username' => 'guru', 'role' => 'guru', 'email' => 'guru@presensi.com',
        ]);

        $this->assertTrue(Hash::check('admin123', User::where('username', 'admin')->first()->password));
        $this->assertTrue(Hash::check('guru123', User::where('username', 'guru')->first()->password));
    }

    public function test_seeder_lama_tidak_menimpa_password_dengan_nilai_lain(): void
    {
        // Akun admin dari setUp() harus TIDAK tersentuh oleh seeder mana pun.
        $adminLama = User::where('email', 'admin@smppresensipgri.sch.id')->first();
        $hashLama = $adminLama->password;
        $jumlahAwal = User::count();

        $this->seed(DatabaseSeeder::class);
        // UserSeeder & AdminUserSeeder harus memakai kredensial yang sama persis.
        $this->seed(UserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(Hash::check('admin123', User::where('username', 'admin')->first()->password));
        $this->assertTrue(Hash::check('guru123', User::where('username', 'guru')->first()->password));

        // Akun lama tidak ditimpa, dan tidak ada akun admin ganda tambahan
        // dengan password "password123".
        $adminLama->refresh();
        $this->assertSame($hashLama, $adminLama->password);
        $this->assertSame($jumlahAwal + 2, User::count());
    }

    public function test_db_seed_aman_dijalankan_berulang_tanpa_menghapus_data(): void
    {
        $admin = $this->adminUji();

        $class = SchoolClass::create([
            'name' => '7C', 'grade' => '7', 'academic_year_id' => $this->tahunUji()->id,
        ]);
        Student::create([
            'school_class_id' => $class->id, 'name' => 'Siswa Tetap', 'nis' => '7702',
            'gender' => 'Perempuan', 'status' => 'Aktif',
        ]);
        Teacher::create([
            'name' => 'Guru Tetap', 'nip' => '198001012010011011', 'gender' => 'Laki-laki',
        ]);

        $jumlahUserAwal = User::count();

        // Jalankan seluruh seeding berulang kali.
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(DatabaseOverhaulSeeder::class);

        // Data master TIDAK boleh hilang.
        $this->assertDatabaseHas('students', ['nis' => '7702']);
        $this->assertDatabaseHas('teachers', ['nip' => '198001012010011011']);
        $this->assertDatabaseHas('school_classes', ['name' => '7C']);
        $this->assertDatabaseHas('academic_years', ['name' => '2025/2026']);

        // Akun lama tetap ada, hanya bertambah 2 akun default.
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertSame($jumlahUserAwal + 2, User::count());
    }

    /**
     * E & F. PESAN LOGIN & TIDAK ADA AUTO-CREATE.
     */
    public function test_pesan_gagal_login_tetap_umum_dan_tidak_membocorkan_akun(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'login' => 'admin',
            'password' => 'kata-sandi-salah',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');

        $pesan = session('errors')->first('login');

        $this->assertStringContainsString('salah', $pesan);
        $this->assertStringNotContainsString('tidak ditemukan', $pesan);
        $this->assertStringNotContainsString('belum terdaftar', $pesan);
    }

    public function test_pesan_throttle_429_berbahasa_indonesia(): void
    {
        // Route login memakai throttle:5,1 -> percobaan ke-6 diblokir (429).
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['login' => 'salah', 'password' => 'salah']);
        }

        $response = $this->post(route('login'), ['login' => 'salah', 'password' => 'salah']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            session('errors')->first('login')
        );
    }

    public function test_halaman_login_tidak_pernah_membuat_akun_otomatis(): void
    {
        $jumlahAwal = User::count();

        $this->get(route('login'))->assertOk();

        // Membuka halaman login tidak boleh membuat akun apa pun.
        $this->assertSame($jumlahAwal, User::count());
        $this->assertTrue(Schema::hasTable('users'));
    }
}
