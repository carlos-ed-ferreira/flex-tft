<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('composition_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composition_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->json('board_state')->nullable();
            $table->timestamps();

            $table->unique(['composition_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_levels');
    }
};
