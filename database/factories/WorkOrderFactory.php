<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WorkOrderStatus;
use App\Models\MaintenanceRequest;
use App\Models\Vendor;
use App\Models\WorkOrder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    /**
     * A vendor has been selected but no appointment is booked yet, which is
     * why scheduled_for is null and the migration defaults status to pending.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'maintenance_request_id' => MaintenanceRequest::factory()->assigned(),
            'vendor_id' => Vendor::factory(),
            'scheduled_for' => null,
            'status' => WorkOrderStatus::Pending,
            'notes' => null,
        ];
    }

    /**
     * An appointment has been booked and the tenant notified.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'scheduled_for' => $this->businessHoursSlot(),
            'status' => WorkOrderStatus::Scheduled,
        ]);
    }

    /**
     * The vendor is on site. The slot is in the past because the appointment
     * has already started.
     */
    public function inProgress(): static
    {
        return $this->scheduled()->state(fn (array $attributes): array => [
            'scheduled_for' => $this->businessHoursSlot(past: true),
            'status' => WorkOrderStatus::InProgress,
        ]);
    }

    /**
     * The job is finished and the vendor has written up what they did.
     */
    public function completed(): static
    {
        return $this->scheduled()->state(fn (array $attributes): array => [
            'scheduled_for' => $this->businessHoursSlot(past: true),
            'status' => WorkOrderStatus::Completed,
            'notes' => fake()->randomElement([
                'Replaced the trap and tightened the compression fitting. No further leaks.',
                'Cleared the blockage and tested. Draining normally now.',
                'Replaced the thermostat. Heating cycling correctly on test.',
                'Rewired the affected socket ring. Tested and certified.',
                'Part ordered, second visit required to complete.',
            ]),
        ]);
    }

    /**
     * Called off before the vendor attended. Keeps whatever slot was booked
     * so the cancellation is legible in the audit trail.
     */
    public function cancelled(): static
    {
        return $this->scheduled()->state(fn (array $attributes): array => [
            'status' => WorkOrderStatus::Cancelled,
            'notes' => fake()->randomElement([
                'Tenant resolved the issue themselves before the appointment.',
                'Vendor unavailable, rescheduling with an alternative contractor.',
                'Duplicate of an earlier request for the same fault.',
            ]),
        ]);
    }

    /**
     * A weekday slot inside 09:00-17:00, on the half hour.
     *
     * Vendor availability is derived from work orders rather than stored,
     * so seeded slots have to obey the same working-hours assumption the
     * FindAvailableVendors tool will make.
     */
    private function businessHoursSlot(bool $past = false): CarbonImmutable
    {
        $days = fake()->numberBetween(1, 14);

        $slot = CarbonImmutable::now()
            ->addDays($past ? -$days : $days)
            ->setTime(fake()->numberBetween(9, 16), fake()->randomElement([0, 30]));

        return match ($slot->dayOfWeek) {
            CarbonInterface::SATURDAY => $slot->addDays($past ? -1 : 2),
            CarbonInterface::SUNDAY => $slot->addDays($past ? -2 : 1),
            default => $slot,
        };
    }
}
