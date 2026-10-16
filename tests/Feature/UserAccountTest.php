<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
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

    protected function createAdmin(string $email = 'admin@test.com'): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => $email,
            'status' => User::STATUS_AKTIF,
        ]);
    }

    public function test_index_renders_for_admin(): void
    {
        User::factory()->create(['role' => User::ROLE_GURU, 'email' => 'guru_render@test.com']);

        $this->get(route('admin.accounts.index'))
            ->assertOk()
            ->assertSee('Kelola Akun', false)
            ->assertSee('Tambah', false)
            ->assertSee('dialogHapus', false);
    }

    public function test_store_creates_account_when_role_free(): void
    {
        $this->post(route('admin.accounts.store'), [
            'name' => 'Guru Test',
            'email' => 'guru_test@test.com',
            'role' => User::ROLE_GURU,
            'password' => 'password123',
        ])->assertRedirect(route('admin.accounts.index'))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'guru_test@test.com',
            'role' => User::ROLE_GURU,
        ]);
    }

    public function test_store_rejects_when_role_taken(): void
    {
        User::factory()->create(['role' => User::ROLE_GURU, 'email' => 'guru_exist@test.com']);

        $this->post(route('admin.accounts.store'), [
            'name' => 'Guru Lain',
            'email' => 'guru_lain@test.com',
            'role' => User::ROLE_GURU,
            'password' => 'password123',
        ])->assertRedirect(route('admin.accounts.index'))
          ->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['email' => 'guru_lain@test.com']);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $existing = User::factory()->create(['email' => 'dupe@test.com', 'role' => User::ROLE_GURU]);

        $this->post(route('admin.accounts.store'), [
            'name' => 'Baru',
            'email' => 'dupe@test.com',
            'role' => User::ROLE_PIKET,
            'password' => 'password123',
        ])->assertSessionHasErrors(['email']);
    }

    public function test_store_rejects_short_password(): void
    {
        $this->post(route('admin.accounts.store'), [
            'name' => 'Guru Test',
            'email' => 'guru_short@test.com',
            'role' => User::ROLE_GURU,
            'password' => 'short',
        ])->assertSessionHasErrors(['password']);
    }

    public function test_update_changes_name_and_email(): void
    {
        $user = User::factory()->create(['name' => 'Old', 'email' => 'old@test.com', 'role' => User::ROLE_GURU]);

        $this->put(route('admin.accounts.update', $user), [
            'name' => 'New Name',
            'email' => 'new@test.com',
        ])->assertRedirect(route('admin.accounts.index'))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'email' => 'new@test.com']);
    }

    public function test_update_changes_password_and_can_login_with_new(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_GURU, 'password' => bcrypt('oldpass'), 'email' => 'guru_upd@test.com']);

        $this->put(route('admin.accounts.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'newpass123',
        ])->assertRedirect(route('admin.accounts.index'));

        $this->post(route('login'), ['login' => 'guru_upd@test.com', 'password' => 'newpass123'])
             ->assertRedirect(route('guru.dashboard'));
    }

    public function test_update_ignores_role_change(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_GURU]);

        $this->put(route('admin.accounts.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => User::ROLE_GURU]);
    }

    public function test_destroy_deletes_guru_piket(): void
    {
        $piket = User::factory()->create(['role' => User::ROLE_PIKET]);

        $this->delete(route('admin.accounts.destroy', $piket))
             ->assertRedirect(route('admin.accounts.index'))
             ->assertSessionHas('deleted');

        $this->assertModelMissing($piket);
    }

    public function test_destroy_rejects_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'admin2@test.com']);

        $this->actingAs($this->createAdmin('admin3@test.com'));
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
        $guru = User::factory()->create(['role' => User::ROLE_GURU]);
        $this->actingAs($guru);

        $this->get(route('admin.accounts.index'))->assertForbidden();
        $this->post(route('admin.accounts.store'), [
            'name' => 'X', 'email' => 'x@test.com', 'role' => User::ROLE_PIKET, 'password' => 'password123',
        ])->assertForbidden();
    }

    public function test_password_endpoint_returns_decrypted(): void
    {
        $plain = 'rahasia123';
        $user = User::factory()->create([
            'role' => User::ROLE_GURU,
            'password_terenkripsi' => Schema::hasColumn('users','password_terenkripsi') ? Crypt::encryptString($plain) : null,
        ]);

        $res = $this->get(route('admin.accounts.password', $user));
        $res->assertOk();
        $data = $res->json();
        $this->assertTrue($data['stored'] ?? false);
        $this->assertSame($plain, $data['decrypted'] ?? null);
    }
}