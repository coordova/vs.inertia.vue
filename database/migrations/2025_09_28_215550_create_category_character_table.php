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
        Schema::create('category_character', function (Blueprint $table) {
            $table->primary(['category_id', 'character_id']); // Clave primaria compuesta
            $table->foreignId('category_id')->constrained('categories')->onDelete('no action'); // FK a categories
            $table->foreignId('character_id')->constrained('characters')->onDelete('no action'); // FK a characters

            // Campos específicos de la relación
            $table->integer('elo_rating')->default(1200);
            $table->integer('matches_played')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0.00); // Porcentaje
            $table->integer('highest_rating')->default(1200);
            $table->integer('lowest_rating')->default(1200);
            $table->decimal('rating_deviation', 8, 2)->default(0.00); // Futuro para Glicko?
            $table->timestamp('last_match_at')->nullable(); // Última partida
            $table->boolean('is_featured')->default(0); // Destacado en categoría
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->boolean('status')->default(1); // Estado en esta categoría
            $table->timestamps(); // created_at, updated_at

            // Índices
            $table->index(['category_id', 'elo_rating']); // Para ordenar personajes por ELO en categoría
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_character');
    }
};
