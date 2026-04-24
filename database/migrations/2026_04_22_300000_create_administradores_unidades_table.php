<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla pivot que registra qué usuarios son administradores de cada unidad.
 * Un usuario puede administrar múltiples unidades y una unidad puede tener
 * múltiples administradores.
 * No modifica ninguna tabla existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administradores_unidades', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Usuario designado como administrador de la unidad.');

            $table->foreignId('unidad_id')
                ->constrained('unidades')
                ->onDelete('cascade')
                ->comment('Unidad de trabajo que el usuario administra.');

            $table->timestamps();

            $table->primary(['user_id', 'unidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administradores_unidades');
    }
};
