<?php

namespace App\Actions;

use App\Models\Cliente;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class GuardarCliente
{
    public function crear(array $datos): Cliente
    {
        $cliente = Cliente::query()->firstOrCreate(
            ['telefono' => $datos['telefono']],
            ['nombre' => $datos['nombre']],
        );

        if ($cliente->archivado_at !== null) {
            throw ValidationException::withMessages([
                'telefono' => 'Este número pertenece a un perfil archivado. Administración puede reactivarlo.',
            ]);
        }

        return $cliente;
    }

    public function actualizar(Cliente $cliente, array $datos): void
    {
        try {
            $cliente->update($datos);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'telefono' => 'Ya existe un cliente con ese teléfono.',
            ]);
        }
    }

    public function cambiarArchivo(Cliente $cliente, bool $archivar): void
    {
        Cliente::query()->whereKey($cliente->id)
            ->when($archivar, fn ($query) => $query->whereNull('archivado_at'), fn ($query) => $query->whereNotNull('archivado_at'))
            ->update(['archivado_at' => $archivar ? now() : null]);
    }
}
