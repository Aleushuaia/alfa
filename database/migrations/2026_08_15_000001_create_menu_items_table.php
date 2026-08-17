<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();           // slug: 'pdf-extractor'
            $table->string('label');                   // 'PDF de imagen a texto'
            $table->string('section');                 // 'Procesamiento de Texto'
            $table->string('icon');                    // 'fas fa-file-alt'
            $table->string('route_name');              // 'pdf-extractor.index'
            $table->string('route_pattern')->nullable(); // 'pdf-extractor*'
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
