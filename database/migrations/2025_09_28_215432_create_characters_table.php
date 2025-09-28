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
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('nickname', 255)->nullable();
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->date('dob')->nullable(); // date of birth
            $table->tinyInteger('gender')->unsigned()->default(0); // 0=otro, 1=masculino, 2=femenino, 3=no-binario
            $table->string('nationality', 255)->nullable();
            $table->string('occupation', 255)->nullable();
            $table->string('picture', 255); // Imagen principal
            $table->boolean('status')->default(1); // 1=activo, 0=inactivo
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
            $table->softDeletes(); // Soft delete

            $table->unique('fullname'); // Asegura unicidad de fullname
            // $table->unique('slug');     // Asegura unicidad de slug
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
