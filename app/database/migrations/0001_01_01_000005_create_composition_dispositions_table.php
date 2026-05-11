<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('composition_dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composition_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['champion', 'trait', 'item']);
            $table->json('champion_ids')->nullable();
            $table->unsignedTinyInteger('star_level')->nullable();
            $table->string('trait_id')->nullable();
            $table->unsignedTinyInteger('trait_count')->nullable();
            $table->json('item_ids')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_dispositions');
    }
};
