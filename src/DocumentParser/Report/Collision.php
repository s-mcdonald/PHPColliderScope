<?php

declare(strict_types=1);

namespace PHPColliderScope\PHPDocumentParser\Report;

use PHPColliderScope\PHPDocumentParser\Document\Symbol;

final readonly class Collision
{
    /** @param list<Symbol> $occurrences always has 2+ entries */
    public function __construct(
        public string $symbolName,
        public array $occurrences,
    ) {
    }

    /** @return list<string> distinct file paths involved, in first-seen order */
    public function files(): array
    {
        $paths = array_map(
            static fn (Symbol $symbol): string => $symbol->file->path(),
            $this->occurrences,
        );

        return array_values(array_unique($paths));
    }

    public function isSameFileDuplicate(): bool
    {
        return count($this->files()) === 1;
    }
}
