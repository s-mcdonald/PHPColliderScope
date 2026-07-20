<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit\PHPDocumentParser\Tokenizers;

use PHPColliderScope\Contracts\TokenizerInterface;
use PHPColliderScope\PHPDocumentParser\Tokenizers\Tokenizer;
use PHPUnit\Framework\TestCase;

final class TokenizerTest extends TestCase
{
    public function testTokenizeDelegatesToTheInjectedStrategy(): void
    {
        $strategy = new class implements TokenizerInterface {
            public ?string $receivedSource = null;

            public function tokenize(string $source): array
            {
                $this->receivedSource = $source;

                return ['fake', 'token', 'stream'];
            }
        };

        $tokenizer = new Tokenizer($strategy);

        $result = $tokenizer->tokenize('<?php echo 1;');

        $this->assertSame(['fake', 'token', 'stream'], $result);
        $this->assertSame('<?php echo 1;', $strategy->receivedSource);
    }
}
