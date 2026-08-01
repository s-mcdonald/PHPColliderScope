<?php

declare(strict_types=1);

namespace PHPColliderScope\PHPDocumentParser\Tokenizers;

use PHPColliderScope\Contracts\TokenizerInterface;

final readonly class Tokenizer
{
    public function __construct(private TokenizerInterface $tokenizerStrategy)
    {
    }

    /**
     * @return list<array{0:int,1:string,2:int}|string> a mix of
     */
    public function tokenize(string $source): array
    {
        return $this->tokenizerStrategy->tokenize($source);
    }
}
