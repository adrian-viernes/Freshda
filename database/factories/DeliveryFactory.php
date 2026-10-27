<?php

namespace Database\Factories;

use App\Models\Delivery;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'supplier_id' => Supplier::factory(),
            'fish_type' => $this->faker->randomElement(['Milkfish', 'Tilapia', 'Salmon', 'Mackerel', 'Bangus', 'Tuna']),
            'delivery_date' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
            'expected_quantity' => $this->faker->randomFloat(2, 5, 120),
            'actual_quantity' => null,
            'status' => Delivery::STATUS_PENDING,
            'inspection_notes' => null,
            'rejection_reason' => null,
            'accepted_at' => null,
            'inventory_item_id' => null,
            'stock_updated_at' => null,
        ];
    }

    /** A delivery still awaiting inspection; $expected is the promised kg. */
    public function pending(float $expected): static
    {
        return $this->state(fn (array $attributes): array => [
            'expected_quantity' => $expected,
            'status' => Delivery::STATUS_PENDING,
        ]);
    }

    public function accepted(float $quantity): static
    {
        return $this->state(fn (array $attributes): array => [
            'actual_quantity' => $quantity,
            'status' => Delivery::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    public function rejected(string $reason): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Delivery::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);
    }
}
