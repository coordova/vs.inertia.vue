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
        Schema::create('character_survey', function (Blueprint $table) {
            $table->primary(['character_id', 'survey_id']); // Clave primaria compuesta
            $table->foreignId('character_id')->constrained('characters')->onDelete('cascade'); // FK a characters
            $table->foreignId('survey_id')->constrained('surveys')->onDelete('no action'); // FK a surveys

            // Campos específicos de la relación
            $table->integer('survey_matches')->default(0);
            $table->integer('survey_wins')->default(0);
            $table->integer('survey_losses')->default(0);
            $table->integer('survey_ties')->unsigned()->default(0);
            $table->boolean('is_active')->default(1); // Activo en encuesta
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->timestamps(); // created_at, updated_at

            // Índice para FK survey
            // El índice de FK character se crea implícitamente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_survey');
    }
};
