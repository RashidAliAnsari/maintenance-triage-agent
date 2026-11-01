<?php

declare(strict_types=1);

namespace App\Ai\Rag;

use App\Enums\PolicyDocumentType;
use App\Models\DocumentChunk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PolicySearch
{
    public function __construct(private readonly Embedder $embedder) {}

    /**
     * Find the policy chunks closest in meaning to the given query, most similar first.
     * Pass a type to search only one kind of policy document.
     *
     * @return Collection<int, DocumentChunk>
     */
    public function search(string $query, int $limit = 3, ?PolicyDocumentType $type = null): Collection
    {
        $vector = $this->embedder->embedQuery($query);

        return DocumentChunk::query()
            ->with('policyDocument')
            ->when($type, fn (Builder $chunks, PolicyDocumentType $type) => $chunks->whereRelation('policyDocument', 'type', $type->value))
            ->whereVectorSimilarTo('embedding', $vector, minSimilarity: 0.45)
            ->limit($limit)
            ->get();
    }
}
