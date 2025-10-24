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
        Schema::create('combinatorics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('surveys')->onDelete('cascade'); // FK a surveys
            $table->foreignId('character1_id')->constrained('characters')->onDelete('cascade'); // FK a characters
            $table->foreignId('character2_id')->constrained('characters')->onDelete('cascade'); // FK a characters
            $table->integer('total_comparisons')->default(0);
            $table->timestamp('last_used_at')->nullable(); // Última vez usada
            $table->boolean('status')->default(1); // 1=activa, 0=no
            $table->timestamps(); // created_at, updated_at

            // Asegura unicidad: no puede haber combinación duplicada para la misma encuesta
            $table->unique(['survey_id', 'character1_id', 'character2_id']);

            // Índices para claves foráneas y búsqueda
            $table->index(['character1_id', 'character2_id']);
            $table->index('last_used_at'); // Crea combinatorics_last_used_at_index
            $table->index(['survey_id', 'status']); // Crea combinatorics_survey_id_status_index
            // Los índices de FK se crean implícitamente por 'constrained()'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combinatorics');
    }
};
