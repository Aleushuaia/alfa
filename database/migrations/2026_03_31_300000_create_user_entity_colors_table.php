<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'alfa_pg';

    public function up(): void
    {
        Schema::connection($this->connection)->create('user_entity_colors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('entity_type', 30);   // PER, ORG, LOC, DATE, DNI, EMAIL, PHONE, MISC
            $table->string('color', 9);           // hex color e.g. #ffcccc
            $table->timestamps();

            $table->unique(['user_id', 'entity_type']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('user_entity_colors');
    }
};
