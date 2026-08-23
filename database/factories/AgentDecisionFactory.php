<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AgentDecision;
use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentDecision>
 */
class AgentDecisionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'maintenance_request_id' => MaintenanceRequest::factory()->assigned(),
            'tool_calls' => [
                ['tool' => 'ClassifyRequest', 'arguments' => ['description' => 'Water leaking under the kitchen sink.']],
                ['tool' => 'SearchPolicyDocuments', 'arguments' => ['query' => 'plumbing repair responsibility', 'document_type' => 'lease']],
                ['tool' => 'DetermineResponsibility', 'arguments' => ['category' => 'plumbing']],
                ['tool' => 'FindAvailableVendors', 'arguments' => ['trade' => 'plumber', 'urgency' => 'routine']],
                ['tool' => 'CreateWorkOrder', 'arguments' => ['vendor_id' => 1]],
                ['tool' => 'NotifyTenant', 'arguments' => ['message' => 'A plumber has been booked for your repair.']],
            ],
            'reasoning' => 'Classified as plumbing, routine urgency. Lease clause 7.2 places pipework repairs with the landlord. Cheapest available plumber assigned within the SLA window and tenant notified.',
            'confidence' => fake()->randomFloat(2, 0.82, 0.97),
            'outcome' => 'assigned',
        ];
    }

    /**
     * The agent stopped and handed the request to a human. The tool sequence
     * is truncated at the point it gave up.
     */
    public function escalated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'maintenance_request_id' => MaintenanceRequest::factory()->escalated(),
            'tool_calls' => [
                ['tool' => 'ClassifyRequest', 'arguments' => ['description' => 'Crack in the bedroom ceiling, getting larger.']],
                ['tool' => 'SearchPolicyDocuments', 'arguments' => ['query' => 'structural damage responsibility', 'document_type' => 'lease']],
                ['tool' => 'DetermineResponsibility', 'arguments' => ['category' => 'structural']],
                ['tool' => 'EscalateToHuman', 'arguments' => ['reason' => 'Responsibility unclear and estimated cost exceeds the $500 approval threshold.']],
            ],
            'reasoning' => 'Policy search returned no clause covering structural cracks, so responsibility could not be determined. Estimated cost of $900 also exceeds the owner approval threshold. Escalating rather than assigning.',
            'confidence' => fake()->randomFloat(2, 0.31, 0.58),
            'outcome' => 'escalated',
        ]);
    }

    /**
     * Classification succeeded but the agent had not yet reached a decision.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'maintenance_request_id' => MaintenanceRequest::factory()->classified(),
            'tool_calls' => [
                ['tool' => 'ClassifyRequest', 'arguments' => ['description' => 'Heating has not worked since Tuesday.']],
                ['tool' => 'SearchPolicyDocuments', 'arguments' => ['query' => 'heating repair responsibility', 'document_type' => 'lease']],
            ],
            'reasoning' => 'Classified as HVAC, urgent. Policy search underway.',
            'confidence' => fake()->randomFloat(2, 0.70, 0.88),
            'outcome' => 'in_progress',
        ]);
    }
}
