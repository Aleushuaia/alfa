<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo EntityBlacklist
 *
 * Representa un término que debe ser excluido (ignorado) por el
 * analizador NLP en futuros análisis de texto.
 *
 * Utiliza la conexión PostgreSQL 'alfa_pg' (contenedor alfa_postgres).
 *
 * Columnas relevantes:
 *   - term           : Texto a ignorar (ej: "Juan García")
 *   - entity_type    : Tipo NLP del término (PER, ORG, LOC, DATE, DNI, EMAIL, PHONE, MISC)
 *                      NULL = ignorar sin importar el tipo
 *   - match_mode     : 'exact' | 'contains' | 'regex'
 *   - case_sensitive : Si true, el filtro distingue mayúsculas/minúsculas
 *   - added_by       : Usuario o sistema que lo agregó
 *   - reason         : Motivo por el que se agregó
 *   - active         : Si false, el término está desactivado pero no eliminado
 */
class EntityBlacklist extends Model
{
    // ── Conexión y tabla ──────────────────────────────────────────────────────────
    protected $connection = 'alfa_pg';
    protected $table      = 'entity_blacklist';

    // ── Asignación masiva segura ──────────────────────────────────────────────
    protected $fillable = [
        'term',
        'entity_type',
        'match_mode',
        'case_sensitive',
        'added_by',
        'reason',
        'active',
    ];

    // ── Casts de tipos ────────────────────────────────────────────────────────
    protected $casts = [
        'case_sensitive' => 'boolean',
        'active'         => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    // ── Valores por defecto ───────────────────────────────────────────────────
    protected $attributes = [
        'match_mode'     => 'exact',
        'case_sensitive' => false,
        'active'         => true,
    ];

    // ── Scope: sólo entradas activas ──────────────────────────────────────────
    /**
     * Retorna sólo los registros activos (active = true).
     * Uso: EntityBlacklist::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
