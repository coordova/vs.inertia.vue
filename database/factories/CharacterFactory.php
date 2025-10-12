<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Character>
 */
class CharacterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generar un fullname aleatorio
        $fullname = $this->faker->name();

        return [
            'fullname' => $fullname,
            // Generar un slug único basado en fullname
            'slug' => Str::slug($fullname) . '-' . Str::random(5), // Añadir sufijo aleatorio para unicidad
            'nickname' => $this->faker->optional()->userName(), // Nickname opcional
            'bio' => fake()->paragraphs(rand(3, 5), true), // Biografía opcional
            'dob' => $this->faker->optional()->date('Y-m-d', '-18 years'), // Fecha de nacimiento opcional, antes de hace 18 años
            'gender' => $this->faker->numberBetween(0, 3), // 0=otro, 1=masculino, 2=femenino, 3=no-binario
            'nationality' => $this->faker->country(), // Nacionalidad
            'occupation' => $this->faker->jobTitle(), // Ocupación
            'picture' => 'characters/default.jpg',      // $this->faker->imageUrl(640, 480, 'people', true, 'Character'), // URL de imagen de ejemplo
            'status' => $this->faker->boolean(90), // 90% de probabilidad de ser true (activo)
            'meta_title' => $this->faker->optional()->sentence(),
            'meta_description' => $this->faker->optional()->paragraph(),
            // created_at y updated_at se rellenan automáticamente por Laravel
        ];
    }
}
