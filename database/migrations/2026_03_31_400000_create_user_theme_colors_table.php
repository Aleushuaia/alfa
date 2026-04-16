<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'alfa_pg';

    public function up(): void
    {
        Schema::connection($this->connection)->create('user_theme_colors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('theme_mode', 10);     // 'light' or 'dark'
            $table->string('color_key', 30);       // accent, accent2, body_bg, card_bg, sidebar_bg
            $table->string('color_value', 9);      // hex e.g. #6366f1
            $table->timestamps();

            $table->unique(['user_id', 'theme_mode', 'color_key']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('user_theme_colors');
    }
};
