<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'sku' => Str::upper(Str::random(8)),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(500, 50000),
            'compare_at_price' => null,
            'stock' => fake()->numberBetween(5, 100),
            'is_available' => true,
            'is_published' => true,
            'is_featured' => false,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false, 'published_at' => null]);
    }

    public function unavailable(): static
    {
        return $this->state(fn () => ['is_available' => false]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    public function stock(int $stock): static
    {
        return $this->state(fn () => ['stock' => $stock]);
    }

    public function price(float $price): static
    {
        return $this->state(fn () => ['price' => $price]);
    }
}
