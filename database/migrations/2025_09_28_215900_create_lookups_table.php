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
        Schema::create('lookups', function (Blueprint $table) {
            $table->id();
            $table->string('category', 255);
            $table->string('code', 255);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Campo JSON para datos adicionales
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes(); // Opcional: si se quiere soft delete para lookups

            // Asegura unicidad de combinación categoría-código
            $table->unique(['category', 'code']);

            // Índices para búsquedas comunes
            $table->index(['category']);
            $table->index(['code']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lookups');
    }
};
