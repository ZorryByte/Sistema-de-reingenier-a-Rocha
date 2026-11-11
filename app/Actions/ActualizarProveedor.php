<?php

namespace App\Actions;

use App\Models\Proveedor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarProveedor
{
    public function execute(Proveedor $proveedor, array $data): Proveedor
    {
        return DB::transaction(function () use ($proveedor, $data) {
            $nombre = Proveedor::normalizarNombre($data['nombre']);

            // Conserva identidad historica.
            if ($proveedor->compras()->exists() && mb_strtolower($nombre) !== mb_strtolower($proveedor->nombre)) {
                throw ValidationException::withMessages([
                    'nombre' => 'El nombre no puede cambiar porque el proveedor tiene compras.',
                ]);
            }

            $duplicado = Proveedor::query()
                ->whereKeyNot($proveedor->id)
                ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])
                ->exists();

            if ($duplicado) {
                throw ValidationException::withMessages([
                    'nombre' => 'Ya existe otro proveedor con ese nombre.',
                ]);
            }

            try {
                $proveedor->update([
                    'nombre' => $nombre,
                    'telefono' => $data['telefono'] ?? null,
                    'email' => $data['email'] ?? null,
                    'notas' => $data['notas'] ?? null,
                ]);
            } catch (QueryException $exception) {
                if ($this->nombreExiste($proveedor, $nombre)) {
                    throw ValidationException::withMessages([
                        'nombre' => 'Ya existe otro proveedor con ese nombre.',
                    ]);
                }

                throw $exception;
            }

            return $proveedor->fresh();
        });
    }

    private function nombreExiste(Proveedor $proveedor, string $nombre): bool
    {
        return Proveedor::query()
            ->whereKeyNot($proveedor->id)
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])
            ->exists();
    }
}
