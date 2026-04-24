<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo EntityWhitelist
 *
 * Representa un término que fue añadido manualmente por el usuario
 * para que sea reconocido como entidad en futuros análisis de texto.
 *
 * Utiliza la misma conexión PostgreSQL 'alfa_pg' que EntityBlacklist.
 *
 * Columnas:
 *   - term        : Texto a reconocer (ej: "Tribunal Superior de Córdoba")
 *   - entity_type : Tipo NLP sugerido (PER, ORG, LOC, DATE, DNI, EMAIL, PHONE, MISC)
 *                   NULL = sin tipo específico
 *   - added_by    : Usuario que lo agregó
 *   - reason      : Motivo por el que se añadió
 *   - active      : Si false, no se usa pero tampoco se elimina
 */
class EntityWhitelist extends Model
{
    protected $connection = 'alfa_pg';
    protected $table      = 'entity_whitelist';

    protected $fillable = [
        'term',
        'entity_type',
        'added_by',
        'reason',
        'active',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'active' => true,
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Unidad de trabajo propietaria de este término.
     * NULL = registro global (sin unidad específica).
     */
    public function unidad()
    {
        return $this->belongsTo(\App\Models\Unidad::class, 'unidad_id');
    }
}
