<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RequestCategory;
use App\Enums\RequestStatus;
use App\Enums\RequestUrgency;
use App\Enums\Responsibility;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
{
    /**
     * Realistic tenant-written complaints mapped to their correct category,
     * so classification tests have a known expected answer.
     *
     * @var array<string, RequestCategory>
     */
    private const DESCRIPTIONS = [
        'Water is leaking from under the kitchen sink. There is a puddle on the floor and the cabinet is getting soaked.' => RequestCategory::Plumbing,
        'The toilet in the main bathroom will not stop running after flushing.' => RequestCategory::Plumbing,
        'Hot water only lasts about two minutes now, then goes cold.' => RequestCategory::Plumbing,
        'Two of the sockets in the living room have stopped working. The rest are fine.' => RequestCategory::Electrical,
        'The smoke alarm keeps chirping every minute even after I replaced the battery.' => RequestCategory::Electrical,
        'The heating has not worked since Tuesday. It is very cold in the flat.' => RequestCategory::Hvac,
        'Air conditioning is running but blowing warm air only.' => RequestCategory::Hvac,
        'The fridge is making a loud humming noise and the freezer is not staying cold. Food is spoiling.' => RequestCategory::Appliance,
        'Washing machine drains but will not spin, so clothes come out soaking wet.' => RequestCategory::Appliance,
        'There is a crack in the bedroom ceiling that seems to have got bigger over the last month.' => RequestCategory::Structural,
        'The front door does not close properly, it catches on the frame near the top.' => RequestCategory::Structural,
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'tenant_id' => Tenant::factory(),
            'description' => fake()->randomElement(array_keys(self::DESCRIPTIONS)),
            'category' => null,
            'urgency' => null,
            'status' => RequestStatus::Submitted,
            'responsibility' => null,
            'estimated_hours' => null,
            'estimated_cost' => null,
        ];
    }

    /**
     * The agent has run ClassifyRequest: category, urgency and a labour
     * estimate are known. Cost is not, because it depends on vendor rates.
     *
     * Hours are capped at 4 so that the derived cost in assessed() stays
     * under the $500 escalation threshold.
     */
    public function classified(
        ?RequestCategory $category = null,
        ?RequestUrgency $urgency = null,
    ): static {
        return $this->state(fn (array $attributes): array => [
            'category' => $category ?? self::DESCRIPTIONS[$attributes['description']],
            'urgency' => $urgency ?? fake()->randomElement(RequestUrgency::cases()),
            'estimated_hours' => fake()->randomFloat(2, 0.5, 4),
        ]);
    }

    /**
     * Responsibility determined and vendors found, so cost is now derivable:
     * estimated hours multiplied by the cheapest available vendor's rate.
     */
    public function assessed(
        ?Responsibility $responsibility = null,
        ?float $hourlyRate = null,
    ): static {
        return $this->classified()->state(function (array $attributes) use ($responsibility, $hourlyRate): array {
            $hours = (float) ($attributes['estimated_hours'] ?? 2.0);
            $rate = $hourlyRate ?? fake()->randomFloat(2, 45, 95);

            return [
                'responsibility' => $responsibility ?? Responsibility::Landlord,
                'estimated_cost' => round($hours * $rate, 2),
                'status' => RequestStatus::Assessing,
            ];
        });
    }

    /**
     * Handed to a human: responsibility unclear and cost over the $500 threshold.
     */
    public function escalated(): static
    {
        return $this->assessed(Responsibility::Unclear)
            ->state(fn (array $attributes): array => [
                'estimated_hours' => 6.00,
                'estimated_cost' => 900.00,
                'status' => RequestStatus::Escalated,
            ]);
    }

    /**
     * Vendor selection has happened. Pair with a work order at the call site:
     * ->assigned()->has(WorkOrder::factory())
     */
    public function assigned(): static
    {
        return $this->assessed()->state(fn (array $attributes): array => [
            'status' => RequestStatus::Assigned,
        ]);
    }
}
