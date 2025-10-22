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
        Schema::create('votes', function (Blueprint $table) {
            $table->id(); // Equivalente a `BIGINT UNSIGNED NOT NULL AUTO_INCREMENT` y define la PK
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // FK a users
            $table->foreignId('combinatoric_id')->constrained('combinatorics')->onDelete('cascade'); // FK a combinatorics
            $table->foreignId('survey_id')->constrained('surveys')->onDelete('cascade'); // FK a surveys
            
            // Columnas para ganador/perdedor, permitiendo NULL para empates
            $table->foreignId('winner_id')->nullable()->constrained('characters')->onDelete('cascade'); // FK a characters (ganador)
            $table->foreignId('loser_id')->nullable()->constrained('characters')->onDelete('cascade'); // FK a characters (perdedor)
            
            $table->decimal('tie_score', 3, 2)->nullable(); // Puntaje de empate (0.0-1.0), futuro uso
            $table->timestamp('voted_at')->useCurrent(); // Fecha de voto, se establece automáticamente al crear
            $table->string('ip_address', 45)->nullable(); // IP del votante
            $table->text('user_agent')->nullable(); // User agent
            $table->boolean('is_valid')->default(true); // 1=válido, 0=no
            $table->text('notes')->nullable(); // Notas adicionales
            $table->timestamps(); // created_at, updated_at

            // Índices para consultas comunes (Laravel crea índices para FK automáticamente, pero los definimos explícitamente por claridad si es necesario)
            $table->index(['voted_at']); // Para consultas temporales
            $table->index(['user_id', 'survey_id']); // Para progreso de usuario por encuesta
            $table->index(['survey_id', 'voted_at']); // Para análisis de votos por encuesta y tiempo
            // Los índices de FK individuales ya están cubiertos por las definiciones de FK
        });
    }

    /* public function ___up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // FK a users
            $table->foreignId('combinatoric_id')->constrained('combinatorics')->onDelete('cascade'); // FK a combinatorics
            $table->foreignId('survey_id')->constrained('surveys')->onDelete('cascade'); // FK a surveys
            $table->foreignId('winner_id')->nullable()->constrained('characters')->onDelete('cascade'); // FK a characters (ganador)
            $table->foreignId('loser_id')->nullable()->constrained('characters')->onDelete('cascade'); // FK a characters (perdedor)
            $table->decimal('tie_score', 3, 2)->nullable(); // Puntaje de empate (0.0-1.0), futuro uso
            $table->timestamp('voted_at')->default(DB::raw('CURRENT_TIMESTAMP')); // Fecha de voto
            $table->string('ip_address', 45)->nullable(); // IP del votante
            $table->text('user_agent')->nullable(); // User agent
            $table->boolean('is_valid')->default(1); // 1=válido, 0=no
            $table->text('notes')->nullable(); // Notas adicionales
            $table->timestamps(); // created_at, updated_at

            // Índices para consultas comunes
            $table->index(['voted_at']); // Para consultas temporales
            $table->index(['user_id', 'survey_id']); // Para progreso de usuario por encuesta
            $table->index(['survey_id', 'voted_at']); // Para análisis de votos por encuesta y tiempo
            // Los índices de FK se crean implícitamente por 'constrained()'
        });
    } */

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
