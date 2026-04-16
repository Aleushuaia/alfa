<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: entity_blacklist
 *
 * Tabla de términos que deben ser ignorados por el analizador NLP
 * en futuros análisis. Se ejecuta sobre la conexión 'alfa_pg'
 * (PostgreSQL — contenedor alfa_postgres).
 *
 * Para ejecutar SOLO esta migration:
 *   php artisan migrate --path=database/migrations/2026_03_23_000001_create_entity_blacklist_table.php --database=alfa_pg
 */
return new class extends Migration
{
    /**
     * Usar la conexión PostgreSQL del contenedor alfa_postgres.
     */
    protected $connection = 'alfa_pg';

    // ─────────────────────────────────────────────────────────────────────────
    public function up(): void
    {
        Schema::connection($this->connection)->create('entity_blacklist', function (Blueprint $table) {

            $table->id();

            // ── Término a ignorar ─────────────────────────────────────────
            $table->string('term', 500)
                ->comment('Texto exacto o patrón que debe ignorarse en el análisis.');

            // ── Tipo de entidad ───────────────────────────────────────────
            // NULL = aplicar a cualquier tipo.
            // Valores esperados: PER, ORG, LOC, GPE, DATE, DNI, EMAIL, PHONE, MISC
            $table->string('entity_type', 30)
                ->nullable()
                ->comment('Tipo de entidad NLP: PER, ORG, LOC, DATE, DNI, EMAIL, PHONE, MISC o NULL para todos.');

            // ── Modo de comparación ───────────────────────────────────────
            // exact    → coincidencia exacta (case-insensitive)
            // contains → el término aparece dentro del texto detectado
            // regex    → expresión regular
            $table->enum('match_mode', ['exact', 'contains', 'regex'])
                ->default('exact')
                ->comment('Modo de comparación al filtrar entidades.');

            // ── Sensibilidad al caso ──────────────────────────────────────
            $table->boolean('case_sensitive')
                ->default(false)
                ->comment('Si es true el filtro distingue mayúsculas/minúsculas.');

            // ── Origen / trazabilidad ─────────────────────────────────────
            $table->string('added_by', 150)
                ->nullable()
                ->comment('Usuario o sistema que agregó el término.');

            $table->text('reason')
                ->nullable()
                ->comment('Motivo o contexto por el que se agrega este término a la lista negra.');

            // ── Estado ───────────────────────────────────────────────────
            $table->boolean('active')
                ->default(true)
                ->comment('Permite desactivar un término sin eliminarlo.');

            $table->timestamps(); // created_at, updated_at

            // ── Índices ───────────────────────────────────────────────────
            $table->index(['entity_type', 'active'], 'idx_blacklist_type_active');
            $table->index('match_mode', 'idx_blacklist_match_mode');

            // Unicidad: mismo término + mismo tipo no puede repetirse
            $table->unique(['term', 'entity_type'], 'uq_blacklist_term_type');
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('entity_blacklist');
    }
};
