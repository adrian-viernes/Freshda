<?php

namespace Database\Factories;

use App\Models\AppNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppNotification>
 */
class AppNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['fresh', 'warning', 'error', 'info', 'success']),
            'title' => $this->faker->words(4, true),
            'body' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['scans', 'system', 'inventory']),
            'page' => $this->faker->randomElement(['history', 'inventory', 'scan', 'batch', 'deliveries']),
            'is_read' => $this->faker->boolean(30),
        ];
    }
}
