<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot model para administradores_unidades.
 * Registra la relación usuario-unidad en rol de administrador.
 */
class AdministradorUnidad extends Pivot
{
    protected $table = 'administradores_unidades';

    public $incrementing = false;
}
