<?php

use App\Ai\Rag\PolicySearch;
use App\Enums\PolicyDocumentType;
use App\Models\DocumentChunk;
use App\Models\PolicyDocument;
use Laravel\Ai\Embeddings;

beforeEach(function () {
    $vector = Embeddings::fakeEmbedding(768);

    Embeddings::fake([[$vector]]);

    $lease = PolicyDocument::factory()->create(['type' => PolicyDocumentType::Lease]);
    $warranty = PolicyDocument::factory()->create(['type' => PolicyDocumentType::Warranty]);

    DocumentChunk::factory()->for($lease)->create(['embedding' => $vector]);
    DocumentChunk::factory()->for($warranty)->create(['embedding' => $vector]);
});

it('only returns chunks from the requested document type', function () {
    $results = app(PolicySearch::class)->search('boiler repair', type: PolicyDocumentType::Lease);

    expect($results->sole()->policyDocument->type)->toBe(PolicyDocumentType::Lease);
});

it('searches every document type when no type is given', function () {
    $results = app(PolicySearch::class)->search('boiler repair');

    expect($results)->toHaveCount(2);
});

it('loads the parent document with each chunk', function () {
    $results = app(PolicySearch::class)->search('boiler repair');

    expect($results->every(fn (DocumentChunk $chunk) => $chunk->relationLoaded('policyDocument')))->toBeTrue();
});
