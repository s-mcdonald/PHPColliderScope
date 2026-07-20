<?php

declare(strict_types=1);

namespace PHPColliderScope\PHPDocumentParser\Report;

use PHPColliderScope\CollisionConfig;
use PHPColliderScope\PHPDocumentParser\Document\PhpDocument;
use PHPColliderScope\PHPDocumentParser\Document\Symbol;

final readonly class CollisionReport
{
    /** @var Collision[] */
    private array $collisions;

    /** @param array<string, PhpDocument> $documents keyed by file path (value doesn't matter) */
    public function __construct(array $documents = [], ?CollisionConfig $config = null)
    {
        $config ??= CollisionConfig::default();

        /** @var array<string, list<Symbol>> $bySymbolKey */
        $bySymbolKey = [];

        foreach ($documents as $document) {
            foreach ($document->declarations as $symbol) {
                if ($symbol->kind->isClassLike() && !$config->findClassNamespaceCollision) {
                    continue;
                }

                if (!$symbol->kind->isClassLike() && !$config->findFunctionNamespaceCollision) {
                    continue;
                }

                $bySymbolKey[$symbol->collisionKey()][] = $symbol;
            }
        }

        $collisions = [];

        foreach ($bySymbolKey as $symbols) {
            if (count($symbols) < 2) {
                continue;
            }

            $collisions[] = new Collision($symbols[0]->fullyQualifiedName(), $symbols);
        }

        $this->collisions = $collisions;
    }

    public function hasCollisions(): bool
    {
        return $this->collisions !== [];
    }

    /**
     * @return Collision[]
     */
    public function collisions(): array
    {
        return $this->collisions;
    }

    public function count(): int
    {
        return count($this->collisions);
    }
}
