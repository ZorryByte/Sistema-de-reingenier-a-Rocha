<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AyudaCatalogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AyudaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('ayuda'))->assertRedirect(route('login'));
    }

    public function test_caja_only_receives_shared_help_content(): void
    {
        $caja = User::factory()->create();

        $response = $this->actingAs($caja)->get(route('ayuda'));

        $response->assertOk();
        $response->assertSee('Crear un pedido u orden');
        $response->assertSee('Registrar una venta');
        $response->assertDontSee('Gestionar respaldos');
        $response->assertDontSee('Google Drive');
        $response->assertDontSee('Solo admin');
        $response->assertDontSee('Para todos');
    }

    public function test_admin_receives_shared_and_restricted_help_content(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('ayuda'));

        $response->assertOk();
        $response->assertSee('Registrar una venta');
        $response->assertSee('Revisar las finanzas');
        $response->assertSee('Proteger y restaurar la información');
    }

    public function test_caja_cannot_open_a_restricted_article(): void
    {
        $caja = User::factory()->create();

        $this->actingAs($caja)
            ->get(route('ayuda', ['tema' => 'gestionar-respaldos']))
            ->assertNotFound();
    }

    public function test_article_page_shows_steps_and_direct_action(): void
    {
        $caja = User::factory()->create();

        $response = $this->actingAs($caja)
            ->get(route('ayuda', ['tema' => 'entregar-pedido']));

        $response->assertOk();
        $response->assertSee('Entregar un pedido');
        $response->assertSee('Abre el pedido y confirma que los productos estén listos.');
        $response->assertSee('Buscar pedido');
    }

    public function test_unknown_article_returns_not_found(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('ayuda', ['tema' => 'tema-inexistente']))
            ->assertNotFound();
    }

    public function test_help_articles_remain_short_and_actionable(): void
    {
        $admin = User::factory()->admin()->create();
        $articles = app(AyudaCatalogo::class)->para($admin);

        foreach ($articles as $article) {
            $this->assertLessThanOrEqual(
                20,
                str_word_count($article['resumen']),
                "El resumen de {$article['slug']} supera 20 palabras.",
            );
            $this->assertGreaterThanOrEqual(3, count($article['pasos']));
            $this->assertLessThanOrEqual(7, count($article['pasos']));
        }
    }
}
