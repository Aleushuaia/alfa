<?php

namespace App\Http\Controllers;

use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UnidadController extends Controller
{
    public function index()
    {
        $unidades = Unidad::withCount('users')->orderBy('descripcion')->get();

        return view('admin.unidades.index', compact('unidades'));
    }

    public function create()
    {
        return view('admin.unidades.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'descripcion' => ['required', 'string', 'max:150', 'unique:unidades,descripcion'],
        ]);

        $unidad = Unidad::create($data);

        return redirect()->route('admin.unidades.show', $unidad)
            ->with('success', "Unidad «{$unidad->descripcion}» creada correctamente.");
    }

    public function show(Unidad $unidad)
    {
        $unidad->load('users');
        $associatedIds = $unidad->users->pluck('id')->toArray();
        $availableUsers = User::whereNotIn('id', $associatedIds)->orderBy('name')->get();

        return view('admin.unidades.show', compact('unidad', 'availableUsers'));
    }

    public function edit(Unidad $unidad)
    {
        return view('admin.unidades.edit', compact('unidad'));
    }

    public function update(Request $request, Unidad $unidad)
    {
        $data = $request->validate([
            'descripcion' => ['required', 'string', 'max:150', Rule::unique('unidades', 'descripcion')->ignore($unidad->id)],
        ]);

        $unidad->update($data);

        return redirect()->route('admin.unidades.show', $unidad)
            ->with('success', "Unidad «{$unidad->descripcion}» actualizada correctamente.");
    }

    public function destroy(Unidad $unidad)
    {
        if ($unidad->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la unidad porque tiene usuarios asociados.');
        }

        $desc = $unidad->descripcion;
        $unidad->delete();

        return redirect()->route('admin.unidades.index')
            ->with('success', "Unidad «{$desc}» eliminada correctamente.");
    }

    /**
     * Asociar un usuario a la unidad (JSON o redirect).
     */
    public function attachUser(Request $request, Unidad $unidad)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $userId = (int) $data['user_id'];

        $existing = DB::table('user_unidad')
            ->where('user_id', $userId)
            ->where('unidad_id', $unidad->id)
            ->first();

        if ($existing) {
            if ($existing->deleted_at !== null) {
                // Restaurar registro soft-deleted
                DB::table('user_unidad')
                    ->where('user_id', $userId)
                    ->where('unidad_id', $unidad->id)
                    ->update(['deleted_at' => null, 'updated_at' => now()]);
            } else {
                $error = 'El usuario ya está asociado a esta unidad.';
                if ($request->wantsJson()) {
                    return response()->json(['error' => $error], 422);
                }
                return back()->with('error', $error);
            }
        } else {
            DB::table('user_unidad')->insert([
                'user_id'    => $userId,
                'unidad_id'  => $unidad->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $user = User::findOrFail($userId);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'user'    => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }

        return back()->with('success', "Usuario «{$user->name}» agregado a la unidad.");
    }

    /**
     * Desasociar un usuario de la unidad (JSON o redirect).
     */
    public function detachUser(Request $request, Unidad $unidad, User $user)
    {
        DB::table('user_unidad')
            ->where('user_id', $user->id)
            ->where('unidad_id', $unidad->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', "Usuario «{$user->name}» removido de la unidad.");
    }
}
