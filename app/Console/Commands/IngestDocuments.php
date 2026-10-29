<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ai\Rag\MarkdownChunker;
use App\Enums\PolicyDocumentType;
use App\Models\PolicyDocument;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('documents:ingest')]
#[Description('Chunk, embed, and store the policy documents in the database')]
class IngestDocuments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MarkdownChunker $chunker): int
    {
        $files = File::files(database_path('seeders/documents'));

        foreach ($files as $file) {
            $content = $file->getContents();

            $document = PolicyDocument::updateOrCreate(
                ['type' => PolicyDocumentType::from($file->getFilenameWithoutExtension())],
                [
                    'title' => trim(Str::after(Str::before($content, "\n"), '# ')),
                    'content' => $content,
                ]
            );

            $document->chunks()->delete();

            foreach ($chunker->chunk($content) as $chunk) {
                $document->chunks()->create([
                    'content' => $chunk,
                ]);
            }

            $this->info("Ingested document: {$document->title} ({$document->type->label()})");

        }

        return Command::SUCCESS;
    }
}
