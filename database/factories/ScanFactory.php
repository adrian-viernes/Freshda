<?php

namespace Database\Factories;

use App\Models\Scan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scan>
 */
class ScanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $freshness = $this->faker->randomElement(['fresh', 'moderate', 'not_fresh']);

        $percentages = [
            'fresh' => $this->faker->numberBetween(80, 98),
            'moderate' => $this->faker->numberBetween(55, 79),
            'not_fresh' => $this->faker->numberBetween(20, 54),
        ];

        return [
            'species' => $this->faker->randomElement(['Milkfish', 'Tilapia', 'Salmon', 'Mackerel', 'Bangus']),
            'species_confidence' => $this->faker->randomFloat(2, 0.6, 0.99),
            'freshness' => $freshness,
            'freshness_percentage' => $percentages[$freshness],
            'freshness_confidence' => $this->faker->randomFloat(2, 0.6, 0.99),
            'source' => $this->faker->randomElement(['single', 'batch']),
        ];
    }
}
