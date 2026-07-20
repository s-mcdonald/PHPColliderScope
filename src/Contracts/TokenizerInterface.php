<?php

declare(strict_types=1);

namespace PHPColliderScope\Contracts;

/**
 * @see Tokenizer for the expected token stream shape.
 */
interface TokenizerInterface
{
    public function tokenize(string $source): array;
}
