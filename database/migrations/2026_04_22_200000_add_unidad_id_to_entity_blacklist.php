<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega unidad_id nullable a entity_blacklist.
 * NULL = registro global (sin filtrar por unidad).
 * Sin tocar la migration original.
 */
return new class extends Migration
{
    protected $connection = 'alfa_pg';

    public function up(): void
    {
        Schema::connection($this->connection)->table('entity_blacklist', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_id')
                ->nullable()
                ->after('id')
                ->comment('Unidad de trabajo propietaria. NULL = global.');

            $table->index('unidad_id', 'entity_blacklist_unidad_id_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('entity_blacklist', function (Blueprint $table) {
            $table->dropIndex('entity_blacklist_unidad_id_idx');
            $table->dropColumn('unidad_id');
        });
    }
};
