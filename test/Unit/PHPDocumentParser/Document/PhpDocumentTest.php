<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit\PHPDocumentParser\Document;

use FsKit\File;
use PHPColliderScope\PHPDocumentParser\Document\PhpDocument;
use PHPColliderScope\PHPDocumentParser\Document\Symbol;
use PHPColliderScope\PHPDocumentParser\Document\SymbolKind;
use PHPUnit\Framework\TestCase;

final class PhpDocumentTest extends TestCase
{
    public function testHasDeclarationsIsFalseForAnEmptyList(): void
    {
        $document = new PhpDocument(
            File::createByFullFilenamePathString('/tmp/Empty.php'),
            null,
            [],
        );

        $this->assertFalse($document->hasDeclarations());
        $this->assertSame([], $document->declarations);
    }

    public function testHasDeclarationsIsTrueWhenSymbolsArePresent(): void
    {
        $file = File::createByFullFilenamePathString('/tmp/Widget.php');

        $document = new PhpDocument(
            $file,
            'App',
            [new Symbol(SymbolKind::ClassType, 'Widget', 'App', $file, 5)],
        );

        $this->assertTrue($document->hasDeclarations());
        $this->assertSame('App', $document->namespace);
        $this->assertSame($file, $document->file);
    }
}
