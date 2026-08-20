<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lease>
 */
class LeaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-18 months', '-1 month');

        return [
            'unit_id' => Unit::factory(),
            'tenant_id' => Tenant::factory(),
            'start_date' => $startDate,
            'end_date' => (clone $startDate)->modify('+12 months'),
        ];
    }
}
