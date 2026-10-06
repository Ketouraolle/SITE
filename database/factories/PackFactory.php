<?php

namespace Database\Factories;

use App\Models\Pack;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pack>
 */
class PackFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Pack '.fake()->unique()->words(2, true);

        return [
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->sentence(),
            'image_path' => null,
            'price' => null,
            'is_published' => true,
            'is_featured' => false,
            'position' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    public function price(?float $price): static
    {
        return $this->state(fn () => ['price' => $price]);
    }
}
