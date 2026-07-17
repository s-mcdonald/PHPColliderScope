<?php

declare(strict_types=1);

namespace PHPColliderScope\Contracts;

/**
 * A tokenizer strategy: turns PHP source into a token stream.
 *
 * @see Tokenizer for the expected token stream shape.
 */
interface TokenizerInterface
{
    public function tokenize(string $source): array;
}
