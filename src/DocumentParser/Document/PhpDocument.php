<?php

declare(strict_types=1);

namespace PHPColliderScope\PHPDocumentParser\Document;

use FsKit\File;

final readonly class PhpDocument
{
    /** @param list<Symbol> $declarations */
    public function __construct(
        public File $file,
        public ?string $namespace,
        public array $declarations,
    ) {
    }

    public function hasDeclarations(): bool
    {
        return $this->declarations !== [];
    }
}
