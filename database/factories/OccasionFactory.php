<?php

namespace Database\Factories;

use App\Models\Occasion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Occasion>
 */
class OccasionFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->sentence(),
            'starts_on' => now()->subDays(3)->toDateString(),
            'ends_on' => now()->addDays(10)->toDateString(),
            'is_active' => true,
            'position' => 0,
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn () => [
            'starts_on' => now()->addDays(20)->toDateString(),
            'ends_on' => now()->addDays(40)->toDateString(),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'starts_on' => now()->subDays(40)->toDateString(),
            'ends_on' => now()->subDays(20)->toDateString(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
