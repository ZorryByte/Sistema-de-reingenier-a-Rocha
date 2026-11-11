<?php

namespace App\Policies;

use App\Models\User;

class ClientePolicy
{
    public function seleccionar(User $user): bool
    {
        return $user->activo && in_array($user->rol, ['admin', 'caja'], true);
    }

    public function administrar(User $user): bool
    {
        return $user->activo && $user->rol === 'admin';
    }
}
