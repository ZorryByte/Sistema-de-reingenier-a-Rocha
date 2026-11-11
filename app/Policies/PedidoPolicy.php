<?php

namespace App\Policies;

use App\Models\Pedido;
use App\Models\User;

class PedidoPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, Pedido $pedido): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Pedido $pedido): bool
    {
        return $this->canManage($user);
    }

    public function cancel(User $user, Pedido $pedido): bool
    {
        return $this->canManage($user);
    }

    public function deliver(User $user, Pedido $pedido): bool
    {
        return $this->canManage($user);
    }

    public function markReady(User $user, Pedido $pedido): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $user->activo && in_array($user->rol, ['admin', 'caja'], true);
    }
}
