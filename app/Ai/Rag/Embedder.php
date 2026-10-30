<?php

declare(strict_types=1);

namespace App\Ai\Rag;

use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;

class Embedder
{
    public const DIMENSIONS = 768;

    private const MODEL = 'nomic-embed-text';

    /**
     * Embed the given texts, returning one vector per text in the same order.
     *
     * @param  list<string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embed(array $texts): array
    {
        return Embeddings::for($texts)
            ->dimensions(self::DIMENSIONS)
            ->generate(Lab::Ollama, self::MODEL)
            ->embeddings;
    }
}
