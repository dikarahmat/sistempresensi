<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdmin());
    }

    protected function createAdmin(string $username = 'admin'): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'username' => $username,
            'email' => $username . '@test.com',
            'status' => User::STATUS_AKTIF,
        ]);
    }

    public function test_index_renders_for_admin(): void
    {
        User::factory()->create(['role' => User::ROLE_GURU, 'username' => 'gururender', 'email' => 'guru_render@test.com']);

        $this->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertSee('Kelola Akun', false)
            ->assertSee('Tambah', false)
            ->assertSee('dialogHapus', false)
            ->assertSee('gururender', false);
    }

    public function test_store_creates_account_when_role_free(): void
    {
        $this->post(route('admin.accounts.store'), [
            'name' => 'Guru Test',
            'username' => 'guru_test',
            'role' => User::ROLE_GURU,
            'password' => 'password123',
        ])->assertRedirect(route('admin.accounts.index'))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username' => 'guru_test',
            'role' => User::ROLE_GURU,
        ]);
    }

    public function test_store_menyimpan_name_sesuai_username(): void
    {
        $this->post(route('admin.accounts.store'), [
            'name' => 'Guru Test',
            'username' => 'guru_nama',
            'role' => User::ROLE_GURU,
            'password' => 'password123',
        ])->assertRedirect(route('admin.accounts.index'))
          ->assertSessionHas('success');

        // Kolom `name` tidak lagi dipakai untuk tampilan: nilainya mengikuti username.
        $this->assertDatabaseHas('users', ['username' => 'guru_nama', 'name' => 'guru_nama']);
    }

    public function test_tabel_menampilkan_username_dan_status_tanpa_nama(): void
    {
        User::factory()->create([
            'name' => 'Nama Rahasia',
            'username' => 'username_akun',
            'email' => 'username_akun@test.com',
            'role' => User::ROLE_GURU,
            'status' => User::STATUS_AKTIF,
        ]);

        $response = $this->get(route('admin.accounts.index'));

        $response->assertOk();
        $response->assertSee('username_akun', false);
        // Nama tidak lagi ditampilkan, username yang dipakai.
        $response->assertDontSee('Nama Rahasia', false);
        // Kolom STATUS tetap ada (bulatan + teks AKTIF/NONAKTIF).
        $response->assertSee('akun-status', false);
        $response->assertSee('Aktif', false);
    }

    public function test_store_rejects_when_role_taken(): void
    {
        User::factory()->create(['role' => User::ROLE_GURU, 'username' => 'guruexist', 'email' => 'guru_exist@test.com']);

        $this->post(route('admin.accounts.store'), [
            'name' => 'Guru Lain',
            'username' => 'gurulain',
            'role' => User::ROLE_GURU,
            'password' => 'password123',
        ])->assertRedirect(route('admin.accounts.index'))
          ->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['username' => 'gurulain']);
    }

    public function test_store_rejects_duplicate_username(): void
    {
        $existing = User::factory()->create(['email' => 'dupe@test.com', 'username' => 'dupe', 'role' => User::ROLE_GURU]);

        $this->post(route('admin.accounts.store'), [
            'name' => 'Baru',
            'username' => 'dupe',
            'role' => User::ROLE_PIKET,
            'password' => 'password123',
        ])->assertSessionHasErrors(['username']);

        $this->assertDatabaseHas('users', ['id' => $existing->id, 'username' => 'dupe']);
    }

    public function test_store_rejects_invalid_username_format(): void
    {
        // Mengandung spasi.
        $this->post(route('admin.accounts.store'), [
            'name' => 'Baru',
            'username' => 'ada spasi',
            'role' => User::ROLE_PIKET,
            'password' => 'password123',
        ])->assertSessionHasErrors(['username']);

        // Huruf besar tidak boleh.
        $this->post(route('admin.accounts.store'), [
            'name' => 'Baru',
            'username' => 'Admin',
            'role' => User::ROLE_PIKET,
            'password' => 'password123',
        ])->assertSessionHasErrors(['username']);

        // Terlalu pendek.
        $this->post(route('admin.accounts.store'), [
            'name' => 'Baru',
            'username' => 'ab',
            'role' => User::ROLE_PIKET,
            'password' => 'password123',
        ])->assertSessionHasErrors(['username']);
    }

    public function test_store_accepts_valid_username_characters(): void
    {
        $this->post(route('admin.accounts.store'), [
            'name' => 'Piket Test',
            'username' => 'piket.smp_01-2',
            'role' => User::ROLE_PIKET,
            'password' => 'password123',
        ])->assertRedirect(route('admin.accounts.index'))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['username' => 'piket.smp_01-2']);
    }

    public function test_store_rejects_short_password(): void
    {
        $this->post(route('admin.accounts.store'), [
            'name' => 'Guru Test',
            'username' => 'guru_short',
            'role' => User::ROLE_GURU,
            'password' => 'short',
        ])->assertSessionHasErrors(['password']);
    }

    public function test_update_changes_username_and_name_follows_it(): void
    {
        $user = User::factory()->create(['name' => 'Old', 'username' => 'lama', 'email' => 'old@test.com', 'role' => User::ROLE_GURU]);

        $this->put(route('admin.accounts.update', $user), [
            'username' => 'baru',
        ])->assertRedirect(route('admin.accounts.index'))
          ->assertSessionHas('success');

        // Nama tidak lagi diedit: kolom `name` otomatis mengikuti username.
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'baru', 'username' => 'baru']);
    }

    public function test_update_keeps_same_username_for_same_account(): void
    {
        $user = User::factory()->create(['name' => 'Old', 'username' => 'samanya', 'email' => 'old@test.com', 'role' => User::ROLE_GURU]);

        $this->put(route('admin.accounts.update', $user), [
            'username' => 'samanya',
        ])->assertRedirect(route('admin.accounts.index'))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'samanya']);
    }

    public function test_update_rejects_duplicate_username_of_other_account(): void
    {
        User::factory()->create(['username' => 'dipakai', 'email' => 'dipakai@test.com', 'role' => User::ROLE_GURU]);

        $user = User::factory()->create(['username' => 'milik_saya', 'email' => 'saya@test.com', 'role' => User::ROLE_PIKET]);

        $this->put(route('admin.accounts.update', $user), [
            'username' => 'dipakai',
        ])->assertSessionHasErrors(['username']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'milik_saya']);
    }

    public function test_update_changes_password_and_can_login_with_new(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_GURU, 'password' => bcrypt('oldpass'), 'username' => 'guru_upd', 'email' => 'guru_upd@test.com']);

        $this->put(route('admin.accounts.update', $user), [
            'username' => $user->username,
            'password' => 'newpass123',
        ])->assertRedirect(route('admin.accounts.index'));

        $this->post(route('login'), ['login' => 'guru_upd', 'password' => 'newpass123'])
             ->assertRedirect(route('guru.dashboard'));
    }

    public function test_update_empty_password_keeps_old_password(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_GURU, 'password' => bcrypt('oldpass'), 'username' => 'guru_jaga', 'email' => 'guru_jaga@test.com']);

        $this->put(route('admin.accounts.update', $user), [
            'username' => $user->username,
            'password' => '',
        ])->assertRedirect(route('admin.accounts.index'));

        $this->assertTrue(Hash::check('oldpass', $user->fresh()->password));
    }

    public function test_update_ignores_role_change(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_GURU, 'username' => 'guru_role']);

        $this->put(route('admin.accounts.update', $user), [
            'username' => $user->username,
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => User::ROLE_GURU]);
    }

    public function test_destroy_deletes_guru_piket(): void
    {
        $piket = User::factory()->create(['role' => User::ROLE_PIKET, 'username' => 'piket_hapus']);

        $this->delete(route('admin.accounts.destroy', $piket))
             ->assertRedirect(route('admin.accounts.index'))
             ->assertSessionHas('deleted');

        $this->assertModelMissing($piket);
    }

    public function test_destroy_rejects_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'username' => 'admin2', 'email' => 'admin2@test.com']);

        $this->actingAs($this->createAdmin('admin3'));
        $this->delete(route('admin.accounts.destroy', $admin))
             ->assertRedirect(route('admin.accounts.index'))
             ->assertSessionHas('error');

        $this->assertModelExists($admin);
    }

    public function test_destroy_rejects_self(): void
    {
        $self = auth()->user();
        $this->delete(route('admin.accounts.destroy', $self))
             ->assertRedirect(route('admin.accounts.index'))
             ->assertSessionHas('error');

        $this->assertModelExists($self);
    }

    public function test_non_admin_gets_forbidden(): void
    {
        $guru = User::factory()->create(['role' => User::ROLE_GURU, 'username' => 'guru_403']);
        $this->actingAs($guru);

        $this->get(route('admin.accounts.index'))->assertForbidden();
        $this->post(route('admin.accounts.store'), [
            'name' => 'X', 'username' => 'xuser', 'role' => User::ROLE_PIKET, 'password' => 'password123',
        ])->assertForbidden();
    }

    public function test_password_endpoint_returns_decrypted(): void
    {
        $plain = 'rahasia123';
        $user = User::factory()->create([
            'role' => User::ROLE_GURU,
            'username' => 'guru_password',
            'password_terenkripsi' => Schema::hasColumn('users','password_terenkripsi') ? Crypt::encryptString($plain) : null,
        ]);

        $res = $this->get(route('admin.accounts.password', $user));
        $res->assertOk();
        $data = $res->json();
        $this->assertTrue($data['stored'] ?? false);
        $this->assertSame($plain, $data['decrypted'] ?? null);
    }
}
