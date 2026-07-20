<?php

declare(strict_types=1);

namespace PHPColliderScope;

final readonly class CollisionConfig
{
    public function __construct(
        public bool $findClassNamespaceCollision = false,
        public bool $findFunctionNamespaceCollision = false,
    ) {
    }

    public static function default(): self
    {
        return new self(
            findClassNamespaceCollision: true,
            findFunctionNamespaceCollision: true,
        );
    }
}