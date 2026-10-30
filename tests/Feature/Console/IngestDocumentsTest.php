<?php

use App\Models\DocumentChunk;
use App\Models\PolicyDocument;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

it('stores every policy document with its chunks embedded', function () {
    Embeddings::fake();

    $this->artisan('documents:ingest')->assertSuccessful();

    expect(PolicyDocument::count())->toBe(5)
        ->and(DocumentChunk::count())->toBe(26)
        ->and(DocumentChunk::whereNull('embedding')->count())->toBe(0);

});

it('can be run repeately without duplicating documents or chunks', function () {
    Embeddings::fake();

    $this->artisan('documents:ingest')->assertSuccessful();
    $this->artisan('documents:ingest')->assertSuccessful();

    expect(PolicyDocument::count())->toBe(5)
        ->and(DocumentChunk::count())->toBe(26)
        ->and(DocumentChunk::whereNull('embedding')->count())->toBe(0);
});

it('send every chunk to embedding model at 768 dimensions', function () {
    Embeddings::fake();

    $this->artisan('documents:ingest')->assertSuccessful();

    Embeddings::assertGenerated(function (EmbeddingsPrompt $prompt) {
        return count($prompt->inputs) === 26
        && $prompt->dimensions === 768
        && $prompt->contains('Blockages caused by improper use');
    });
});
