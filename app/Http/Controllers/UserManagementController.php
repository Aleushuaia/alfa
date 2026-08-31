<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'unidades'])->withCount('unidades')->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('admin.users', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('admin.users.index')
            ->with('success', "Usuario «{$user->name}» creado correctamente.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role'     => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->update([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        if (!empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles([$data['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', "Usuario «{$user->name}» actualizado correctamente.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Usuario «{$name}» eliminado correctamente.");
    }

    /**
     * Eliminar varios usuarios a la vez (solo admin).
     */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $ids = collect($data['ids'])
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === auth()->id())
            ->unique()
            ->all();

        if (empty($ids)) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No se seleccionaron usuarios válidos para eliminar.');
        }

        $count = User::whereIn('id', $ids)->count();
        User::whereIn('id', $ids)->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "{$count} usuario(s) eliminado(s) correctamente.");
    }

    /**
     * Resetear contraseña de un usuario (solo admin).
     */
    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', "Contraseña de «{$user->name}» actualizada correctamente.");
    }
}
