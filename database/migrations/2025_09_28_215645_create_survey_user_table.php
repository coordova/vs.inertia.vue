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
        Schema::create('survey_user', function (Blueprint $table) {
            $table->primary(['user_id', 'survey_id']); // Clave primaria compuesta
            $table->foreignId('user_id')->constrained('users')->onDelete('no action'); // FK a users
            $table->foreignId('survey_id')->constrained('surveys')->onDelete('no action'); // FK a surveys

            // Campos específicos de la relación
            $table->decimal('progress_percentage', 5, 2)->default(0.00); // Porcentaje completado
            $table->integer('total_votes')->default(0); // Votos totales
            $table->timestamp('completed_at')->nullable(); // Fecha de finalización
            $table->timestamp('started_at')->nullable(); // Fecha de inicio
            $table->timestamp('last_activity_at')->nullable(); // Última actividad
            $table->boolean('is_completed')->default(false); // Completada (1=sí, 0=no)
            $table->integer('completion_time')->nullable(); // Tiempo total (segundos)
            $table->timestamps(); // created_at, updated_at

            // Índice para FK survey
            $table->index(['survey_id']);
            // El índice de FK user se crea implícitamente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_user');
    }
};
