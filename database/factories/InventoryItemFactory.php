<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Milkfish', 'Tilapia', 'Salmon', 'Mackerel', 'Bangus', 'Tuna']),
            'species' => $this->faker->randomElement(['Chanos chanos', 'Oreochromis niloticus', 'Salmo salar', 'Scomber scombrus', 'Thunnus']),
            'quantity' => $this->faker->randomFloat(2, 0.5, 50),
            'freshness' => $this->faker->randomElement(['fresh', 'moderate', 'not_fresh']),
            'freshness_percentage' => $this->faker->numberBetween(20, 98),
            'last_scanned_at' => now()->subMinutes($this->faker->numberBetween(10, 2000)),
        ];
    }

    /** A row that counts as low stock under its own (higher) threshold. */
    public function belowOwnThreshold(): static
    {
        return $this->state(fn (): array => [
            'quantity' => 10,
            'min_stock' => 20,
        ]);
    }
}
