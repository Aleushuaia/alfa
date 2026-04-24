<?php

namespace App\Http\Controllers;

use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD para asignar / quitar administradores de unidades.
 * Accesible solo para el rol global 'administrador'.
 */
class AdministradorUnidadController extends Controller
{
    /**
     * Lista todas las unidades con sus administradores asignados.
     */
    public function index()
    {
        $unidades = Unidad::with('administradores')
            ->withCount('administradores')
            ->orderBy('descripcion')
            ->get();

        $users = User::orderBy('name')->get();

        return view('admin.administradores-unidades.index', compact('unidades', 'users'));
    }

    /**
     * Asigna un administrador a una unidad (AJAX o redirect).
     */
    public function attach(Request $request)
    {
        $data = $request->validate([
            'user_id'   => ['required', 'integer', 'exists:users,id'],
            'unidad_id' => ['required', 'integer', 'exists:unidades,id'],
        ]);

        $exists = DB::table('administradores_unidades')
            ->where('user_id', $data['user_id'])
            ->where('unidad_id', $data['unidad_id'])
            ->exists();

        if ($exists) {
            $error = 'El usuario ya es administrador de esa unidad.';
            return $request->wantsJson()
                ? response()->json(['error' => $error], 422)
                : back()->with('error', $error);
        }

        DB::table('administradores_unidades')->insert([
            'user_id'    => $data['user_id'],
            'unidad_id'  => $data['unidad_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->wantsJson()) {
            $user = User::find($data['user_id']);
            return response()->json([
                'success' => true,
                'user'    => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            ]);
        }

        return back()->with('success', 'Administrador asignado correctamente.');
    }

    /**
     * Quita un administrador de una unidad (AJAX o redirect).
     */
    public function detach(Request $request, Unidad $unidad, User $user)
    {
        DB::table('administradores_unidades')
            ->where('user_id', $user->id)
            ->where('unidad_id', $unidad->id)
            ->delete();

        return $request->wantsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'Administrador removido correctamente.');
    }
}
