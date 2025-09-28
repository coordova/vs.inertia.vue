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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->char('color', 7)->default('#000000'); // Hex color
            $table->string('icon', 255)->default('tag');
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->boolean('status')->default(0); // 1=activo, 0=inactivo
            $table->boolean('is_featured')->default(0); // 1=destacado, 0=no
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
            $table->softDeletes(); // Soft delete

            $table->unique('name'); // Asegura unicidad de nombre
            // $table->unique('slug'); // Asegura unicidad de slug
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
