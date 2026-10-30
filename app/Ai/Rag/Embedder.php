<?php

declare(strict_types=1);

namespace App\Ai\Rag;

use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;

class Embedder
{
    public const DIMENSIONS = 768;

    private const MODEL = 'nomic-embed-text';

    private const DOCUMENT_PREFIX = 'search_document: ';

    private const QUERY_PREFIX = 'search_query: ';

    /**
     * Embed policy chunks for storage, one vector per text in the same order.
     *
     * @param  list<string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedDocuments(array $texts): array
    {
        return $this->embed(array_map(
            fn (string $text): string => self::DOCUMENT_PREFIX.$text,
            $texts
        ));
    }

    /**
     * Embed a search question.
     *
     * @return array<int, float>
     */
    public function embedQuery(string $query): array
    {
        return $this->embed([self::QUERY_PREFIX.$query])[0];
    }

    /**
     * @param  list<string>  $texts
     * @return array<int, array<int, float>>
     */
    private function embed(array $texts): array
    {
        return Embeddings::for($texts)
            ->dimensions(self::DIMENSIONS)
            ->generate(Lab::Ollama, self::MODEL)
            ->embeddings;
    }
}
