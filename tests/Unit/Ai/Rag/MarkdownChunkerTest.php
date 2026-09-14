<?php

use App\Ai\Rag\MarkdownChunker;

it('splits a markdown document into chunks per H2 heading', function () {
    $markdown = <<<'MD'
    # Document Title

    Some metadata line.

    ---

    ## Clause 12 — Landlord Maintenance Obligations

    The Landlord shall maintain the premises in a habitable condition.

    ---

    ## Clause 13 — Tenant Maintenance Obligations

    The Tenant shall be responsible for damage caused by misuse.
    MD;

    $chunks = (new MarkdownChunker)->chunk($markdown);

    expect($chunks)->toHaveCount(2)
        ->and($chunks[0])->toStartWith('## Clause 12 — Landlord Maintenance Obligations')
        ->and($chunks[1])->toStartWith('## Clause 13 — Tenant Maintenance Obligations');
});

it('keeps H3 subheadings inside their parent H2 chunk', function () {
    $markdown = <<<'MD'
    # SOP

    ---

    ## 1. Urgency Classification

    Every reported defect is assigned one of three urgency levels.

    ### 1.1 Emergency — attend within four hours

    A defect presenting an immediate risk to occupants.

    ### 1.2 Urgent — attend within twenty-four hours

    A defect materially affecting the use of the premises.

    ## 2. Category Assignment

    Requests are categorised by the system affected.
    MD;

    $chunks = (new MarkdownChunker)->chunk($markdown);

    expect($chunks)->toHaveCount(2)
        ->and($chunks[0])
        ->toContain('### 1.1 Emergency')
        ->toContain('### 1.2 Urgent')
        ->and($chunks[1])->toStartWith('## 2. Category Assignment');
});

it('discards the preamble and strips horizontal rules', function () {
    $markdown = <<<'MD'
    # Contractor Rate Sheet

    Northgate Property Management LLC
    Effective January 2026

    ---

    ## Standard Hourly Rates by Trade

    Plumbing: $85 per hour
    MD;

    $chunks = (new MarkdownChunker)->chunk($markdown);

    expect($chunks)->toHaveCount(1)
        ->and($chunks[0])
        ->not->toContain('Northgate Property Management LLC')
        ->not->toContain('---')
        ->toBe("## Standard Hourly Rates by Trade\n\nPlumbing: \$85 per hour");
});

it('returns an empty array when there are no H2 headings', function () {
    $chunks = (new MarkdownChunker)->chunk("# Title\n\nJust a preamble, no sections.");

    expect($chunks)->toBe([]);
});
