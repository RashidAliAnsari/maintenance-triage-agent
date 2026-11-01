<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Rag\PolicySearch;
use App\Enums\PolicyDocumentType;
use App\Models\DocumentChunk;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SearchPolicyDocuments implements Tool
{
    public function __construct(private readonly PolicySearch $policySearch) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search the property\'s policy documents for clauses relevant to a maintenance request. '
            .'Use it before deciding who is responsible for a repair. '
            .'Returns the most relevant excerpts, each labelled with the document it came from.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        /** @var array{query: string, document_type?: string|null} $validated */
        $validated = $request->validate([
            'query' => ['required', 'string'],
            'document_type' => ['nullable', Rule::enum(PolicyDocumentType::class)],
        ]);

        $type = isset($validated['document_type'])
            ? PolicyDocumentType::from($validated['document_type'])
            : null;

        $chunks = $this->policySearch->search($validated['query'], type: $type);

        if ($chunks->isEmpty()) {
            return 'No relevant policy excerpts were found. Do not guess what the policy says.';
        }

        return $this->format($chunks);
    }

    /**
     * Get the tool's schema definition.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('What to look for, in plain words. Example: "who pays for a toilet blocked by wipes".')
                ->required(),
            'document_type' => $schema->string()
                ->enum(PolicyDocumentType::values())
                ->description('Optional. Search one document only: lease (who is responsible for repairs), '
                    .'management_agreement (spending limits and owner approval), warranty (appliance coverage), '
                    .'rate_sheet (vendor rates), sop (maintenance procedures).'),
        ];
    }

    /**
     * Turn the chunks into a text the model can read, labelled by source document.
     *
     * @param  Collection<int, DocumentChunk>  $chunks
     */
    private function format(Collection $chunks): string
    {
        return $chunks
            ->map(fn (DocumentChunk $chunk) => "[{$chunk->policyDocument->type->label()}]\n{$chunk->content}")
            ->implode("\n\n---\n\n");
    }
}
