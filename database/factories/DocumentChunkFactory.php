<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentChunk;
use App\Models\PolicyDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentChunk>
 */
class DocumentChunkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'policy_document_id' => PolicyDocument::factory(),
            'content' => fake()->paragraph(),
            'embedding' => null, // You can generate a random embedding if needed
        ];
    }

    public function embedded(): static
    {
        return $this->state(fn (array $attributes) => [
            'embedding' => array_map(
                fn () => fake()->randomFloat(6, -1, 1),
                range(1, 768),
            ),
        ]);
    }
}
