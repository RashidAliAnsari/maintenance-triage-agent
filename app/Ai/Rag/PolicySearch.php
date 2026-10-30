<?php

declare(strict_types=1);

namespace App\Ai\Rag;

use App\Models\DocumentChunk;
use Illuminate\Support\Collection;

class PolicySearch
{
    public function __construct(private readonly Embedder $embedder) {}

    /**
     * Find the policy chunks closes in meaning to the give query, most similar first.
     *
     * @return Collection<int, DocumentChunk>
     */
    public function search(string $query, int $limit = 3): Collection
    {
        $vector = $this->embedder->embedQuery($query);

        return DocumentChunk::query()
            ->whereVectorSimilarTo('embedding', $vector, minSimilarity: 0.45)
            ->limit($limit)
            ->get();
    }
}
