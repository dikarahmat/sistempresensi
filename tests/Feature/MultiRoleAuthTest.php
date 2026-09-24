<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // Siapkan akun Kesiswaan jika belum ada
        User::updateOrCreate(
            ['email' => 'kesiswaan@smppresensipgri.sch.id'],
            [
                'name' => 'Staf Bagian Kesiswaan',
                'password' => Hash::make('password123'),
                'role' => 'kesiswaan',
            ]
        );
    }

    public function test_login_page_renders_universal_form(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk Sekarang');
        $response->assertSee('Email / Username');
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

    public function test_kesiswaan_can_login_and_is_redirected_to_kesiswaan_dashboard(): void
    {
        $response = $this->post('/login/kesiswaan', [
            'login' => 'kesiswaan@smppresensipgri.sch.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('kesiswaan.dashboard'));
        $this->assertAuthenticated();
        $this->assertEquals('kesiswaan', auth()->user()->role);
    }

    public function test_role_security_middleware_aborts_403_for_unauthorized_panels(): void
    {
        $admin = User::where('email', 'admin@smppresensipgri.sch.id')->first();
        $guru = User::where('role', 'guru')->first();
        $kesiswaan = User::where('email', 'kesiswaan@smppresensipgri.sch.id')->first();

        // 1. Admin tidak boleh masuk ke panel guru & panel kesiswaan -> 403
        $this->actingAs($admin)->get('/guru/dashboard')->assertStatus(403);
        $this->actingAs($admin)->get('/kesiswaan/dashboard')->assertStatus(403);

        // 2. Guru tidak boleh masuk ke panel admin & panel kesiswaan -> 403
        $this->actingAs($guru)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($guru)->get('/kesiswaan/dashboard')->assertStatus(403);

        // 3. Kesiswaan tidak boleh masuk ke panel admin & panel guru -> 403
        $this->actingAs($kesiswaan)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($kesiswaan)->get('/guru/dashboard')->assertStatus(403);
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

        // 2. Universal login kesiswaan via email
        $responseKesiswaan = $this->post('/login', [
            'login' => 'kesiswaan@smppresensipgri.sch.id',
            'password' => 'password123',
        ]);
        $responseKesiswaan->assertRedirect(route('kesiswaan.dashboard'));

        $this->post('/logout');

        // 3. Universal login admin via email
        $responseAdmin = $this->post('/login', [
            'login' => 'admin@smppresensipgri.sch.id',
            'password' => 'password123',
        ]);
        $responseAdmin->assertRedirect(route('admin.dashboard'));
    }
}
