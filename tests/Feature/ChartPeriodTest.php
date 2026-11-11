<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChartPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_distinguishes_an_empty_period_from_a_zero_sale(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->sale($user, 0.03, now()->subDay());

        $this->getJson(route('dashboard.ventas', ['range' => 'hoy']))
            ->assertOk()->assertJsonCount(1, 'items')
            ->assertJsonPath('count', 0)->assertJsonPath('total', 0);

        $this->sale($user, 0, now());
        $this->app['cache']->flush();

        $this->getJson(route('dashboard.ventas', ['range' => 'hoy']))
            ->assertOk()->assertJsonPath('count', 1)->assertJsonPath('total', 0);
        $this->getJson(route('dashboard.ventas', ['range' => 'semana']))
            ->assertOk()->assertJsonPath('count', 2)->assertJsonPath('total', 0.03);
    }

    public function test_finance_summary_exposes_the_record_count_and_total_for_the_range(): void
    {
        $this->freezeTime();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $url = route('finanzas.resumen', ['range' => 'mes']);

        $this->getJson($url)->assertOk()->assertJsonPath('cantidad', 0)->assertJsonPath('total', 0);

        $this->sale($admin, 0, now());
        $this->sale($admin, 0.03, now()->subMonthsNoOverflow(2));

        $this->getJson($url)->assertOk()->assertJsonPath('cantidad', 2)
            ->assertJsonPath('items.5.cantidad', 1)
            ->assertJsonPath('items.5.resultado', 0)
            ->assertJsonPath('total', 0.03);
    }

    private function sale(User $user, float $total, \DateTimeInterface $date): void
    {
        DB::table('ventas')->insert([
            'user_id' => $user->id,
            'estado' => 'pagada',
            'total' => $total,
            'created_at' => $date,
        ]);
    }
}
