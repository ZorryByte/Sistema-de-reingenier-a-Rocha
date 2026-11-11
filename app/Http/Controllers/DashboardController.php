<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\Widgets\VentasRangoWidget;
use App\Actions\GetDashboardStats;
use App\Services\DashboardCacheInvalidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = (new GetDashboardStats)->execute();

        return view('dashboard', $stats);
    }

    public function ventas(Request $request): JsonResponse
    {
        $range = $request->validate(['range' => 'nullable|in:hoy,semana,mes'])['range'] ?? 'semana';

        $payload = (new VentasRangoWidget)->compute($range);

        return response()->json($payload, 200, [], JSON_NUMERIC_CHECK);
    }

    public static function clearCache(): void
    {
        app(DashboardCacheInvalidator::class)->invalidateAll();
    }
}
