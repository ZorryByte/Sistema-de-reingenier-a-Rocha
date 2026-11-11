<?php

namespace App\Http\Controllers;

use App\Support\AyudaCatalogo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AyudaController extends Controller
{
    public function __invoke(Request $request, AyudaCatalogo $catalogo): View
    {
        $articulos = $catalogo->para($request->user());
        $porSlug = collect($articulos)->keyBy('slug');
        $slug = $request->string('tema')->trim()->value();
        $seleccionado = $slug === '' ? null : $porSlug->get($slug);

        if ($slug !== '' && ! $seleccionado) {
            abort(404);
        }

        if ($seleccionado) {
            $seleccionado['relacionados'] = collect($seleccionado['relacionados'] ?? [])
                ->map(fn (string $relatedSlug) => $porSlug->get($relatedSlug))
                ->filter()
                ->values()
                ->all();
        }

        $categorias = collect($articulos)
            ->groupBy('categoria')
            ->map(fn ($items) => $items->values()->all())
            ->all();

        $frecuentes = collect($catalogo->frecuentesPara($request->user()))
            ->map(fn (string $frequentSlug) => $porSlug->get($frequentSlug))
            ->filter()
            ->values()
            ->all();

        $busqueda = collect($articulos)->map(fn (array $articulo) => [
            'title' => $articulo['titulo'],
            'summary' => $articulo['resumen'],
            'searchable' => implode(' ', [
                $articulo['titulo'],
                $articulo['resumen'],
                implode(' ', $articulo['palabrasClave']),
            ]),
            'url' => route('ayuda', ['tema' => $articulo['slug']]),
        ])->values()->all();

        return view('ayuda.index', compact(
            'articulos',
            'busqueda',
            'categorias',
            'frecuentes',
            'seleccionado',
        ));
    }
}
