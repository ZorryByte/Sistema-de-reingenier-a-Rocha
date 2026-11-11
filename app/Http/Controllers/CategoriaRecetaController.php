<?php

namespace App\Http\Controllers;

use App\Actions\ActualizarCategoriaReceta;
use App\Actions\CrearCategoriaReceta;
use App\Actions\EliminarCategoriaReceta;
use App\Models\CategoriaReceta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaRecetaController extends Controller
{
    public function index(): View
    {
        $categorias = CategoriaReceta::withCount('recetas')
            ->orderBy('nombre')
            ->get();

        return view('recetas.categorias.index', compact('categorias'));
    }

    public function store(Request $request, CrearCategoriaReceta $action): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
        ], [
            'nombre.required' => 'El nombre de la categoria es obligatorio.',
            'nombre.max' => 'El nombre de la categoria no debe superar 60 caracteres.',
        ]);

        try {
            $action->execute($validated['nombre']);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('recetas.categorias.index')
            ->with('success', 'Categoria creada correctamente.');
    }

    public function update(Request $request, CategoriaReceta $categoriaReceta, ActualizarCategoriaReceta $action): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
        ], [
            'nombre.required' => 'El nombre de la categoria es obligatorio.',
            'nombre.max' => 'El nombre de la categoria no debe superar 60 caracteres.',
        ]);

        try {
            $action->execute($categoriaReceta, $validated['nombre']);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('recetas.categorias.index')
            ->with('success', 'Categoria actualizada correctamente.');
    }

    public function destroy(CategoriaReceta $categoriaReceta, EliminarCategoriaReceta $action): RedirectResponse
    {
        try {
            $action->execute($categoriaReceta);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('recetas.categorias.index')
            ->with('success', 'Categoria eliminada correctamente. Las recetas asociadas quedaron sin categoria.');
    }
}
