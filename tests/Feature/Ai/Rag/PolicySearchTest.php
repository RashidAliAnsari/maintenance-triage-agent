<?php

use App\Ai\Rag\PolicySearch;
use App\Models\DocumentChunk;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function ollamaIsRunning(): bool
{
    try {
        return Http::timeout(1)->get('http://localhost:11434/api/version')->successful();
    } catch (ConnectionException) {
        return false;
    }
}

it('finds the tenant obligations clause for a blocked toilet question', function () {
    $this->artisan('documents:ingest')->assertSuccessful();

    $headings = app(PolicySearch::class)
        ->search('who pays for a toilet blocked by wipes?')
        ->map(fn (DocumentChunk $chunk): string => Str::before($chunk->content, "\n"));

    expect($headings)->toContain('## Clause 13 — Tenant Maintenance Obligations');
})->skip(fn () => ! ollamaIsRunning(), 'Ollama is not running');

it('returns nothing for a question unrelated to any policy', function () {
    $this->artisan('documents:ingest')->assertSuccessful();

    expect(app(PolicySearch::class)->search('best pizza in Lahore'))->toBeEmpty();
})->skip(fn () => ! ollamaIsRunning(), 'Ollama is not running');
