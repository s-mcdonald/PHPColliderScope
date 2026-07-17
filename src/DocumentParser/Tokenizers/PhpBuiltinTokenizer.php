<?php

declare(strict_types=1);

namespace PHPColliderScope\PHPDocumentParser\Tokenizers;

use PHPColliderScope\Contracts\TokenizerInterface;

/**
 * The built in PHP tokenizer
 * 
 * @url https://www.php.net/manual/en/function.token-get-all.php
 */
final class PhpBuiltinTokenizer implements TokenizerInterface
{
    public function __construct()
    {
    }

    public function tokenize(string $source): array
    {
        return token_get_all($source, TOKEN_PARSE);
    }
}
