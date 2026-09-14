<?php

namespace App\Ai\Rag;

class MarkdownChunker
{
    /**
     * Split a markdown document into chunks on H2 (##) heading boundaries.
     *
     * Content before the first H2 — the H1 title and metadata block — is
     * discarded, since it carries no retrievable substance on its own.
     * Horizontal rules (---) are stripped as pure visual separators.
     *
     * @return array<int, string>
     */
    public function chunk(string $markdown): array
    {
        $markdown = preg_replace('/^-{3,}$/m', '', $markdown);

        $sections = preg_split('/^## /m', trim($markdown));

        if ($sections === false) {
            return [];
        }

        array_shift($sections);

        return array_map(
            fn (string $section) => trim("## {$section}"),
            $sections
        );
    }
}
