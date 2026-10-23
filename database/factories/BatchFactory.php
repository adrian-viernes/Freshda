<?php

namespace Database\Factories;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sampleCount = $this->faker->numberBetween(5, 15);
        $fresh = $this->faker->numberBetween(1, $sampleCount);
        $moderate = $this->faker->numberBetween(0, $sampleCount - $fresh);
        $notFresh = $sampleCount - $fresh - $moderate;

        $verdict = $fresh / $sampleCount >= 0.7 ? 'FRESH' : ($notFresh / $sampleCount >= 0.5 ? 'NOT FRESH' : 'MODERATE');

        return [
            'sample_count' => $sampleCount,
            'fresh_count' => $fresh,
            'moderate_count' => $moderate,
            'not_fresh_count' => $notFresh,
            'verdict' => $verdict,
            'confidence' => $this->faker->randomFloat(2, 0.7, 0.99),
        ];
    }
}
