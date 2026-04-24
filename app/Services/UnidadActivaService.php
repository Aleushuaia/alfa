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
     */
    public function get(User $user): ?Unidad
    {
        $sessionId = Session::get(self::SESSION_KEY);

        if ($sessionId) {
            // Verificar que el usuario siga perteneciendo a esa unidad
            $unidad = $user->unidades()->where('unidades.id', $sessionId)->first();
            if ($unidad) {
                return $unidad;
            }
        }

        // Fallback: primera unidad alfabética
        $unidad = $user->unidades()->orderBy('descripcion')->first();
        if ($unidad) {
            Session::put(self::SESSION_KEY, $unidad->id);
        }

        return $unidad;
    }

    /**
     * Establece la unidad activa en sesión, validando que el usuario
     * pertenezca a esa unidad.
     *
     * @return bool  true si se cambió, false si el usuario no pertenece a ella.
     */
    public function set(User $user, int $unidadId): bool
    {
        $unidad = $user->unidades()->where('unidades.id', $unidadId)->first();

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
