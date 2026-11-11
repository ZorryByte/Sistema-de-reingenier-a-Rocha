<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_arranque_cargar_pagina_usuarios(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->admin)->get(route('usuarios.index'));

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $response->assertSee('Gestion de Usuarios');
        $response->assertSee('Nuevo Usuario');

        $this->assertLessThan(500, $elapsed, "La carga de la pagina tardo {$elapsed}ms (maximo 500ms)");

        fwrite(STDOUT, "\n[ARRANQUE] Carga de /usuarios: ".round($elapsed, 2)."ms\n");
    }

    public function test_crear_nuevo_usuario(): void
    {
        $start = microtime(true);

        $response = $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Test Integration User',
            'email' => 'integration@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'caja',
            'activo' => '1',
        ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success', 'Usuario creado correctamente.');

        $this->assertDatabaseHas('users', [
            'name' => 'Test Integration User',
            'email' => 'integration@test.com',
            'rol' => 'caja',
            'activo' => true,
        ]);

        $this->assertLessThan(1000, $elapsed, "Crear usuario tardo {$elapsed}ms (maximo 1000ms)");

        fwrite(STDOUT, '[CREAR] Crear nuevo usuario: '.round($elapsed, 2)."ms\n");
    }

    public function test_editar_usuario_existente(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@test.com',
            'rol' => 'caja',
        ]);

        $start = microtime(true);

        $response = $this->actingAs($this->admin)->patch(route('usuarios.update', $user), [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
            'rol' => 'admin',
            'activo' => '1',
        ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('success', 'Usuario actualizado correctamente.');

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@test.com', $user->email);
        $this->assertEquals('admin', $user->rol);

        $this->assertLessThan(1000, $elapsed, "Editar usuario tardo {$elapsed}ms (maximo 1000ms)");

        fwrite(STDOUT, '[EDITAR] Editar usuario existente: '.round($elapsed, 2)."ms\n");
    }

    public function test_editar_usuario_sin_cambiar_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => '$2y$10$original_hash',
        ]);

        $originalPassword = $user->password;

        $start = microtime(true);

        $response = $this->actingAs($this->admin)->patch(route('usuarios.update', $user), [
            'name' => 'New Name',
            'email' => 'test@test.com',
            'password' => '',
            'password_confirmation' => '',
            'rol' => 'caja',
        ]);

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertRedirect(route('usuarios.index'));

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals($originalPassword, $user->password);

        fwrite(STDOUT, '[EDITAR] Editar sin cambiar password: '.round($elapsed, 2)."ms\n");
    }

    public function test_toggle_activo(): void
    {
        $user = User::factory()->create(['activo' => true]);

        $start = microtime(true);

        $response = $this->actingAs($this->admin)->patch(route('usuarios.toggle-activo', $user));

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertRedirect(route('usuarios.index'));

        $user->refresh();
        $this->assertFalse($user->activo);

        fwrite(STDOUT, '[TOGGLE] Toggle activo: '.round($elapsed, 2)."ms\n");
    }

    public function test_flujo_completo_crear_editar_eliminar(): void
    {
        $totalStart = microtime(true);

        // 1. Crear
        $createStart = microtime(true);
        $response = $this->actingAs($this->admin)->post(route('usuarios.store'), [
            'name' => 'Full Flow User',
            'email' => 'fullflow@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'caja',
        ]);
        $createElapsed = (microtime(true) - $createStart) * 1000;
        $response->assertRedirect(route('usuarios.index'));

        $user = User::where('email', 'fullflow@test.com')->first();
        $this->assertNotNull($user);

        // 2. Editar
        $editStart = microtime(true);
        $response = $this->actingAs($this->admin)->patch(route('usuarios.update', $user), [
            'name' => 'Full Flow User Edited',
            'email' => 'fullflow@test.com',
            'rol' => 'admin',
            'activo' => '1',
        ]);
        $editElapsed = (microtime(true) - $editStart) * 1000;
        $response->assertRedirect(route('usuarios.index'));

        $user->refresh();
        $this->assertEquals('Full Flow User Edited', $user->name);
        $this->assertEquals('admin', $user->rol);

        // 3. Eliminar
        $deleteStart = microtime(true);
        $response = $this->actingAs($this->admin)->delete(route('usuarios.delete', $user));
        $deleteElapsed = (microtime(true) - $deleteStart) * 1000;
        $response->assertRedirect(route('usuarios.index'));

        $user->refresh();
        $this->assertFalse($user->activo);

        $totalElapsed = (microtime(true) - $totalStart) * 1000;

        fwrite(STDOUT, "\n[FLUJO COMPLETO]\n");
        fwrite(STDOUT, '  Crear: '.round($createElapsed, 2)."ms\n");
        fwrite(STDOUT, '  Editar: '.round($editElapsed, 2)."ms\n");
        fwrite(STDOUT, '  TOTAL: '.round($totalElapsed, 2)."ms\n");

        $this->assertLessThan(2000, $totalElapsed, "Flujo completo tardo {$totalElapsed}ms (maximo 2000ms)");
    }

    public function test_latencia_carga_con_100_usuarios(): void
    {
        User::factory()->count(100)->create();

        $start = microtime(true);

        $response = $this->actingAs($this->admin)->get(route('usuarios.index'));

        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $response->assertSee('Gestion de Usuarios');

        fwrite(STDOUT, "\n[LATENCIA] Carga con 100 usuarios: ".round($elapsed, 2)."ms\n");

        $this->assertLessThan(800, $elapsed, "Carga con 100 usuarios tardo {$elapsed}ms (maximo 800ms)");
    }
}
