<?php

namespace App\Http\Controllers;

use App\Models\Unidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controlador para que los administradores de unidad puedan gestionar
 * los usuarios de sus propias unidades, sin necesitar el rol global 'administrador'.
 */
class GestionarUnidadController extends Controller
{
    /**
     * Lista las unidades que el usuario autenticado puede administrar.
     * Si solo tiene una, redirige directo al panel de esa unidad.
     */
    public function index()
    {
        $unidades = Auth::user()->unidadesAdministradas()->orderBy('descripcion')->get();

        abort_if($unidades->isEmpty(), 403, 'No tiene unidades asignadas para administrar.');

        if ($unidades->count() === 1) {
            return redirect()->route('gestionar-unidad.show', $unidades->first());
        }

        return view('unidad.gestionar', compact('unidades'));
    }

    /**
     * Panel de administración de una unidad concreta.
     * Muestra los usuarios actuales y permite agregar/quitar.
     */
    public function show(Unidad $unidad)
    {
        $this->checkUnitAdmin($unidad);

        $unidad->load('users');
        $associatedIds  = $unidad->users->pluck('id')->toArray();
        $availableUsers = User::whereNotIn('id', $associatedIds)->orderBy('name')->get();

        return view('unidad.panel', compact('unidad', 'availableUsers'));
    }

    /**
     * Asocia un usuario a la unidad (AJAX o redirect).
     */
    public function attachUser(Request $request, Unidad $unidad)
    {
        $this->checkUnitAdmin($unidad);

        $data   = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $userId = (int) $data['user_id'];

        $existing = DB::table('user_unidad')
            ->where('user_id', $userId)
            ->where('unidad_id', $unidad->id)
            ->first();

        if ($existing) {
            if ($existing->deleted_at !== null) {
                DB::table('user_unidad')
                    ->where('user_id', $userId)
                    ->where('unidad_id', $unidad->id)
                    ->update(['deleted_at' => null, 'updated_at' => now()]);
            } else {
                $error = 'El usuario ya está asociado a esta unidad.';
                return $request->wantsJson()
                    ? response()->json(['error' => $error], 422)
                    : back()->with('error', $error);
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

        return $request->wantsJson()
            ? response()->json(['success' => true, 'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]])
            : back()->with('success', "Usuario «{$user->name}» agregado a la unidad.");
    }

    /**
     * Desasocia un usuario de la unidad (soft delete en pivot).
     */
    public function detachUser(Request $request, Unidad $unidad, User $user)
    {
        $this->checkUnitAdmin($unidad);

        DB::table('user_unidad')
            ->where('user_id', $user->id)
            ->where('unidad_id', $unidad->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        return $request->wantsJson()
            ? response()->json(['success' => true])
            : back()->with('success', "Usuario «{$user->name}» removido de la unidad.");
    }

    /**
     * Verifica que el usuario autenticado sea administrador de la unidad dada.
     */
    private function checkUnitAdmin(Unidad $unidad): void
    {
        abort_unless(
            Auth::user()->unidadesAdministradas()->where('unidades.id', $unidad->id)->exists(),
            403,
            'No tiene permisos para administrar esta unidad.'
        );
    }
}
