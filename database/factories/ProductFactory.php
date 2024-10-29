<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->text,
            'price' => $this->faker->numberBetween(10000, 100000),
            'category_id' => \App\Models\Category::factory(),
            'stock' => $this->faker->numberBetween(10, 100),
        ];
    }

    /**
     * Indicate that the product has no stock.
     */
    public function withoutStock(): ProductFactory
    {
        return $this->state([
            'stock' => 0,
        ]);
    }
}
