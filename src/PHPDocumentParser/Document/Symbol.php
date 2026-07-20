<?php

declare(strict_types=1);

namespace PHPColliderScope\PHPDocumentParser\Document;

use FsKit\File;
use PHPColliderScope\LibConsts;

final readonly class Symbol
{
    public function __construct(
        public SymbolKind $kind,
        public string $name,
        public null|string $namespace,
        public File $file,
        public int $line,
    ) {
    }

    public function fullyQualifiedName(): string
    {
        return ($this->namespace !== null && $this->namespace !== '')
            ? $this->namespace . LibConsts::NS . $this->name
            : $this->name;
    }

    public function collisionKey(): string
    {
        return $this->kind->collidesWithGroup() . ':' . strtolower($this->fullyQualifiedName());
    }
}
