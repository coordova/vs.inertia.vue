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
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade'); // FK a categories
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->tinyInteger('type')->unsigned()->default(0); // 0=pública, 1=privada
            $table->boolean('status')->default(0); // 1=activa, 0=inactiva
            $table->boolean('reverse')->default(0); // 0 : orden 'cual es mejor' (default) | 1 : orden 'cual es peor'
            $table->date('date_start');
            $table->date('date_end');
            $table->string('selection_strategy', 255)->default('cooldown'); // Estrategia de selección
            $table->smallInteger('max_votes_per_user')->unsigned()->default(0); // 0=ilimitado
            $table->boolean('allow_ties')->default(false); // 1=sí, 0=no
            $table->decimal('tie_weight', 3, 2)->default(0.50); // Peso de empate (0.0-1.0)
            $table->boolean('is_featured')->default(0); // 1=sí, 0=no
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->integer('counter')->unsigned()->default(0); // Contador de actualizaciones
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
            $table->softDeletes(); // Soft delete

            $table->index(['date_start']); // Índice para consultas por fecha
            $table->index(['date_end']);   // Índice para consultas por fecha
            // $table->unique('title'); // Opcional: si se quiere unicidad de título global
            // $table->unique('slug');        // Asegura unicidad de slug
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
