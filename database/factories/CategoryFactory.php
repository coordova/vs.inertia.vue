<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->slug(),
            'description' => fake()->sentence(),
            'image' => fake()->imageUrl(),
            'color' => fake()->hexColor(),
            'icon' => fake()->imageUrl(),
            'sort_order' => fake()->numberBetween(0, 1),
            'status' => fake()->numberBetween(0, 1),
            'is_featured' => fake()->numberBetween(0, 1),
            'meta_title' => fake()->sentence(),
            'meta_description' => fake()->sentence(),
            // 'meta_keywords' => fake()->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
