<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'alfa_pg';

    public function up(): void
    {
        Schema::connection('alfa_pg')->create('entity_whitelist', function (Blueprint $table) {
            $table->id();
            $table->string('term', 500);                   // Texto a reconocer como entidad
            $table->string('entity_type', 30)->nullable(); // PER, ORG, LOC, DATE, DNI, EMAIL, PHONE, MISC — null = genérico
            $table->string('added_by', 100)->default('usuario');
            $table->text('reason')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['term', 'entity_type']);
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::connection('alfa_pg')->dropIfExists('entity_whitelist');
    }
};
