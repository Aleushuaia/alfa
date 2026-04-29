<?php

namespace App\Services;

use App\Models\Unidad;
use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * Servicio desacoplado para gestionar la unidad de trabajo activa del usuario.
 *
 * La unidad activa se persiste en la sesión bajo la clave SESSION_KEY.
 * No depende de Auth directamente — recibe el usuario como parámetro
 * para facilitar tests y uso en múltiples contextos.
 */
class UnidadActivaService
{
    public const SESSION_KEY = 'active_unidad_id';

    /**
     * Devuelve la unidad activa para el usuario dado.
     *
     * Prioridad:
     *  1. La que está guardada en sesión (si sigue siendo válida para el usuario).
     *  2. La primera unidad del usuario ordenada alfabéticamente.
     *  3. Null si no tiene unidades asignadas.
     *
     * Considera tanto unidades de las que es miembro como las que administra.
     */
    public function get(User $user): ?Unidad
    {
        $accesibles = $user->allAccessibleUnidades();

        $sessionId = Session::get(self::SESSION_KEY);
        if ($sessionId) {
            $unidad = $accesibles->firstWhere('id', (int) $sessionId);
            if ($unidad) {
                return $unidad;
            }
        }

        // Fallback: primera unidad alfabética
        $unidad = $accesibles->first();
        if ($unidad) {
            Session::put(self::SESSION_KEY, $unidad->id);
        }

        return $unidad;
    }

    /**
     * Establece la unidad activa en sesión, validando que el usuario
     * pertenezca a esa unidad (como miembro o como administrador).
     *
     * @return bool  true si se cambió, false si el usuario no tiene acceso.
     */
    public function set(User $user, int $unidadId): bool
    {
        $unidad = $user->allAccessibleUnidades()->firstWhere('id', $unidadId);

        if (!$unidad) {
            return false;
        }

        Session::put(self::SESSION_KEY, $unidad->id);
        return true;
    }

    /**
     * Inicializa la unidad activa justo después del login.
     * Llama a get() que aplica la lógica de prioridad.
     */
    public function initAfterLogin(User $user): void
    {
        $this->get($user);
    }

    /**
     * Limpia la unidad activa de sesión (al hacer logout).
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
