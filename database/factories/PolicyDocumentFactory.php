<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PolicyDocumentType;
use App\Models\PolicyDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyDocument>
 */
class PolicyDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'type' => $this->faker->randomElement(PolicyDocumentType::cases()),
            'content' => $this->faker->paragraphs(5, true),
        ];
    }
}
