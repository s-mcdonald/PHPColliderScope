<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit\PHPDocumentParser\Report;

use FsKit\File;
use PHPColliderScope\PHPDocumentParser\Document\Symbol;
use PHPColliderScope\PHPDocumentParser\Document\SymbolKind;
use PHPColliderScope\PHPDocumentParser\Report\Collision;
use PHPUnit\Framework\TestCase;

final class CollisionTest extends TestCase
{
    public function testFilesReturnsDistinctPathsInFirstSeenOrder(): void
    {
        $fileA = File::createByFullFilenamePathString('/tmp/A.php');
        $fileB = File::createByFullFilenamePathString('/tmp/B.php');

        $collision = new Collision('App\\Widget', [
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $fileA, 1),
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $fileB, 3),
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $fileA, 7),
        ]);

        $this->assertSame(['/tmp/A.php', '/tmp/B.php'], $collision->files());
    }

    public function testIsSameFileDuplicateIsTrueWhenAllOccurrencesShareAFile(): void
    {
        $file = File::createByFullFilenamePathString('/tmp/A.php');

        $collision = new Collision('App\\Widget', [
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $file, 1),
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $file, 7),
        ]);

        $this->assertTrue($collision->isSameFileDuplicate());
    }

    public function testIsSameFileDuplicateIsFalseWhenOccurrencesSpanFiles(): void
    {
        $fileA = File::createByFullFilenamePathString('/tmp/A.php');
        $fileB = File::createByFullFilenamePathString('/tmp/B.php');

        $collision = new Collision('App\\Widget', [
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $fileA, 1),
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $fileB, 1),
        ]);

        $this->assertFalse($collision->isSameFileDuplicate());
    }
}
