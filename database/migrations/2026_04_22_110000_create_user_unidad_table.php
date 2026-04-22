<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_unidad', function (Blueprint $table) {
            // Pivot sin id, con timestamps y soft deletes
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('unidad_id')->constrained('unidades')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Clave primaria compuesta para evitar duplicados
            $table->primary(['user_id', 'unidad_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_unidad');
    }
};
