<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit\PHPDocumentParser\Tokenizers;

use PHPColliderScope\PHPDocumentParser\Tokenizers\PhpBuiltinTokenizer;
use PHPUnit\Framework\TestCase;

final class PhpBuiltinTokenizerTest extends TestCase
{
    public function testTokenizeReturnsTokenGetAllOutput(): void
    {
        $source = "<?php\nclass Foo {}\n";

        $tokens = (new PhpBuiltinTokenizer())->tokenize($source);

        $this->assertSame(token_get_all($source, TOKEN_PARSE), $tokens);
    }

    public function testTokenizeThrowsParseErrorForUnbalancedSource(): void
    {
        $this->expectException(\ParseError::class);

        (new PhpBuiltinTokenizer())->tokenize("<?php\nclass Broken {\n");
    }
}
