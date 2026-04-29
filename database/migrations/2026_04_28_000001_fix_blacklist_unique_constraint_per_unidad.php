<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix: la constraint original uq_blacklist_term_type era UNIQUE(term, entity_type)
 * sin incluir unidad_id. Esto causaba que intentar agregar el mismo término
 * desde distintas unidades (unidad_id diferente) lanzara una violación de unicidad
 * (error PostgreSQL 23505), que el backend convertía en un HTTP 500.
 *
 * La corrección:
 *  1. Elimina la constraint original.
 *  2. Crea dos índices parciales que permiten exactamente lo que se necesita:
 *     - Un (term, entity_type) único para entradas GLOBALES (unidad_id IS NULL).
 *     - Un (term, entity_type, unidad_id) único para entradas POR UNIDAD.
 *
 * De esta forma cada unidad puede tener su propia blacklist del mismo término,
 * y las entradas globales siguen siendo únicas por (term, entity_type).
 */
return new class extends Migration
{
    protected $connection = 'alfa_pg';

    public function up(): void
    {
        // 1. Eliminar la constraint única original (cubre solo term + entity_type)
        DB::connection('alfa_pg')->statement(
            'ALTER TABLE entity_blacklist DROP CONSTRAINT IF EXISTS uq_blacklist_term_type'
        );

        // 2a. Índice único para entradas GLOBALES (unidad_id IS NULL)
        //     Garantiza que no haya dos entradas globales con el mismo (term, entity_type).
        DB::connection('alfa_pg')->statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_blacklist_term_type_global
             ON entity_blacklist (term, entity_type)
             WHERE unidad_id IS NULL'
        );

        // 2b. Índice único para entradas POR UNIDAD (unidad_id NOT NULL)
        //     Garantiza que dentro de una unidad no haya duplicados de (term, entity_type),
        //     pero sí permite el mismo término en distintas unidades.
        DB::connection('alfa_pg')->statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS uq_blacklist_term_type_unidad
             ON entity_blacklist (term, entity_type, unidad_id)
             WHERE unidad_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        // Restaurar la constraint original (solo posible si no hay filas que la violen)
        DB::connection('alfa_pg')->statement(
            'DROP INDEX IF EXISTS uq_blacklist_term_type_global'
        );
        DB::connection('alfa_pg')->statement(
            'DROP INDEX IF EXISTS uq_blacklist_term_type_unidad'
        );

        DB::connection('alfa_pg')->statement(
            'ALTER TABLE entity_blacklist
             ADD CONSTRAINT uq_blacklist_term_type UNIQUE (term, entity_type)'
        );
    }
};
