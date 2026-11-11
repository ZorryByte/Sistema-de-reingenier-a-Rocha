<?php

namespace App\Actions;

use App\Models\Proveedor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CrearProveedor
{
    public function execute(array $data): Proveedor
    {
        return DB::transaction(function () use ($data) {
            $nombre = Proveedor::normalizarNombre($data['nombre']);

            // Evita proveedores duplicados.
            $existente = $this->buscarPorNombre($nombre);
            if ($existente) {
                return $existente;
            }

            try {
                return Proveedor::create([
                    'nombre' => $nombre,
                    'telefono' => $data['telefono'] ?? null,
                    'email' => $data['email'] ?? null,
                    'notas' => $data['notas'] ?? null,
                ]);
            } catch (QueryException $exception) {
                $existente = $this->buscarPorNombre($nombre);
                if ($existente) {
                    return $existente;
                }

                throw $exception;
            }
        });
    }

    private function buscarPorNombre(string $nombre): ?Proveedor
    {
        return Proveedor::query()
            ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])
            ->first();
    }
}
