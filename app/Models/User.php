<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'user_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'user_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(KardexInventario::class, 'user_id');
    }

    // Helpers de rol
    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function esCaja(): bool
    {
        return $this->rol === 'caja';
    }
}
