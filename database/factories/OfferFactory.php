<?php

namespace Database\Factories;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'occasion_id' => null,
            'name' => 'Offre '.Str::ucfirst(fake()->unique()->word()),
            'description' => fake()->sentence(),
            'discount_percent' => 20,
            'starts_on' => null,
            'ends_on' => null,
            'is_active' => true,
        ];
    }

    public function percent(int $percent): static
    {
        return $this->state(fn () => ['discount_percent' => $percent]);
    }
}
