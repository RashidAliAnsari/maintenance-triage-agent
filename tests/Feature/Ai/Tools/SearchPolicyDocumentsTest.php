<?php

declare(strict_types=1);

use App\Ai\Tools\SearchPolicyDocuments;
use App\Enums\PolicyDocumentType;
use App\Models\DocumentChunk;
use App\Models\PolicyDocument;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Tools\Request;

it('returns matching excerpts labelled with their document', function () {
    $vector = Embeddings::fakeEmbedding(768);
    Embeddings::fake([[$vector]]);

    $lease = PolicyDocument::factory()->create(['type' => PolicyDocumentType::Lease]);
    DocumentChunk::factory()->for($lease)->create(['embedding' => $vector, 'content' => 'The tenant pays for blocked drains.']);

    $result = app(SearchPolicyDocuments::class)->handle(new Request(['query' => 'blocked drain']));

    expect((string) $result)->toContain('[Lease Agreement]')
        ->toContain('The tenant pays for blocked drains.');
});

it('searches only the requested document type', function () {
    $vector = Embeddings::fakeEmbedding(768);
    Embeddings::fake([[$vector]]);

    $lease = PolicyDocument::factory()->create(['type' => PolicyDocumentType::Lease]);
    $warranty = PolicyDocument::factory()->create(['type' => PolicyDocumentType::Warranty]);

    DocumentChunk::factory()->for($lease)->create(['embedding' => $vector, 'content' => 'Lease clause text.']);
    DocumentChunk::factory()->for($warranty)->create(['embedding' => $vector, 'content' => 'Warranty clause text.']);

    $result = app(SearchPolicyDocuments::class)->handle(
        new Request(['query' => 'boiler', 'document_type' => 'lease'])
    );

    expect((string) $result)
        ->toContain('Lease clause text.')
        ->not->toContain('Warranty clause text.');
});

it('tells the model not to guess when nothing matches', function () {
    Embeddings::fake();

    $result = app(SearchPolicyDocuments::class)->handle(new Request(['query' => 'anything']));

    expect((string) $result)->toContain('Do not guess');
});

it('rejects a request with no query', function () {
    app(SearchPolicyDocuments::class)->handle(new Request([]));
})->throws(ValidationException::class);

it('rejects an unknown document type', function () {
    app(SearchPolicyDocuments::class)->handle(
        new Request(['query' => 'boiler', 'document_type' => 'garage'])
    );
})->throws(ValidationException::class);
