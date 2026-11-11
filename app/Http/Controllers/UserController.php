<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD de usuarios. Solo accesible para rol admin.
 * Usa delete() en vez de destroy() por semantica mas clara.
 */
class UserController extends Controller
{
    /**
     * Lista todos los usuarios con paginacion.
     */
    public function index(): View
    {
        $filtro = request('filtro', 'activos');

        $query = match ($filtro) {
            'inactivos' => User::where('activo', false),
            'todos' => User::query(),
            default => User::where('activo', true),
        };

        $users = $query->latest()->paginate(15);

        return view('users.index', compact('users', 'filtro'));
    }

    /**
     * Show the form for creating a new user.
     * (Modal inline, this is just a fallback)
     */
    public function create(): View
    {
        return view('users.index');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Crea un nuevo usuario.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        User::create($data);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Actualiza un usuario existente.
     * Si no se envia password, se mantiene la actual.
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Elimina un usuario del sistema.
     * No puede eliminarse a si mismo (proteccion contra bloqueo total).
     */
    public function softDelete(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $name = $user->name;
        $user->update(['activo' => false]);

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario '{$name}' desactivado correctamente.");
    }

    /**
     * Activa o desactiva un usuario.
     */
    public function toggleActivo(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $user->update(['activo' => ! $user->activo]);

        $estado = $user->activo ? 'activado' : 'desactivado';

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario '{$user->name}' {$estado} correctamente.");
    }
}
