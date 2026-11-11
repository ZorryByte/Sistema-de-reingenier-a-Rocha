<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_users_index(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('usuarios.index'));

        $response->assertStatus(200);
        $response->assertSee('Gestion de Usuarios');
        $response->assertSee('Nuevo Usuario');
    }

    public function test_caja_cannot_access_users_index(): void
    {
        $caja = User::factory()->create();

        $response = $this->actingAs($caja)->get(route('usuarios.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'caja',
            'activo' => '1',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success', 'Usuario creado correctamente.');

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'rol' => 'caja',
            'activo' => true,
        ]);
    }

    public function test_admin_can_create_user_as_admin_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Admin Two',
            'email' => 'admin2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'admin',
        ]);

        $response->assertRedirect(route('usuarios.index'));

        $user = User::where('email', 'admin2@example.com')->first();
        $this->assertEquals('admin', $user->rol);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        $response = $this->actingAs($admin)->get(route('usuarios.edit', $user));

        $response->assertStatus(200);
        $response->assertSee('Editar Usuario');

        $response = $this->actingAs($admin)->patch(route('usuarios.update', $user), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'rol' => 'admin',
            'activo' => '1',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success', 'Usuario actualizado correctamente.');

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('new@example.com', $user->email);
        $this->assertEquals('admin', $user->rol);
    }

    public function test_admin_can_update_user_without_changing_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Old Name']);

        $originalPassword = $user->password;

        $response = $this->actingAs($admin)->patch(route('usuarios.update', $user), [
            'name' => 'New Name',
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => 'caja',
        ]);

        $response->assertRedirect(route('usuarios.index'));

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals($originalPassword, $user->password);
    }

    public function test_admin_can_update_user_active_status(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['activo' => true]);

        $response = $this->actingAs($admin)->patch(route('usuarios.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'rol' => $user->rol,
            'activo' => '0',
        ]);

        $response->assertRedirect(route('usuarios.index'));

        $user->refresh();
        $this->assertFalse($user->activo);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('usuarios.delete', $user));

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertFalse($user->activo);
    }

    public function test_admin_cannot_delete_own_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('usuarios.delete', $admin));

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('error', 'No puedes eliminar tu propio usuario.');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['activo' => true]);

        $response = $this->actingAs($admin)->patch(route('usuarios.toggle-activo', $user));

        $response->assertRedirect(route('usuarios.index'));

        $user->refresh();
        $this->assertFalse($user->activo);

        $response = $this->actingAs($admin)->patch(route('usuarios.toggle-activo', $user));

        $user->refresh();
        $this->assertTrue($user->activo);
    }

    public function test_admin_cannot_toggle_own_active_status(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patch(route('usuarios.toggle-activo', $admin));

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('error', 'No puedes desactivar tu propio usuario.');

        $admin->refresh();
        $this->assertTrue($admin->activo);
    }

    public function test_validation_fails_with_duplicate_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'caja',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_validation_fails_with_invalid_email(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'caja',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_validation_fails_with_weak_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirmation' => '123',
            'rol' => 'caja',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_validation_fails_with_invalid_role(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'superadmin',
        ]);

        $response->assertSessionHasErrors('rol');
    }

    public function test_user_with_special_characters_in_name(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => "O'Brien \"Test\" User",
            'email' => 'special@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'caja',
        ]);

        $response->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('users', [
            'name' => "O'Brien \"Test\" User",
            'email' => 'special@example.com',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_users(): void
    {
        $response = $this->get(route('usuarios.index'));

        $response->assertRedirect(route('login'));
    }
}
