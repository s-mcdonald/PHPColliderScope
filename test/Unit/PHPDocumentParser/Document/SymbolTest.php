<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit\PHPDocumentParser\Document;

use FsKit\File;
use PHPColliderScope\PHPDocumentParser\Document\Symbol;
use PHPColliderScope\PHPDocumentParser\Document\SymbolKind;
use PHPUnit\Framework\TestCase;

final class SymbolTest extends TestCase
{
    public function testFullyQualifiedNamePrependsNamespaceWhenPresent(): void
    {
        $symbol = new Symbol(
            SymbolKind::ClassType,
            'Widget',
            'App\\Models',
            File::createByFullFilenamePathString('/tmp/Widget.php'),
            10,
        );

        $this->assertSame('App\\Models\\Widget', $symbol->fullyQualifiedName());
    }

    public function testFullyQualifiedNameIsJustTheNameWhenNamespaceIsNull(): void
    {
        $symbol = new Symbol(
            SymbolKind::FunctionType,
            'helper',
            null,
            File::createByFullFilenamePathString('/tmp/helper.php'),
            1,
        );

        $this->assertSame('helper', $symbol->fullyQualifiedName());
    }

    public function testFullyQualifiedNameIsJustTheNameWhenNamespaceIsEmptyString(): void
    {
        $symbol = new Symbol(
            SymbolKind::FunctionType,
            'helper',
            '',
            File::createByFullFilenamePathString('/tmp/helper.php'),
            1,
        );

        $this->assertSame('helper', $symbol->fullyQualifiedName());
    }

    public function testCollisionKeyGroupsByKindAndIsCaseInsensitive(): void
    {
        $lower = new Symbol(
            SymbolKind::ClassType,
            'Widget',
            'App',
            File::createByFullFilenamePathString('/tmp/a.php'),
            1,
        );

        $upper = new Symbol(
            SymbolKind::ClassType,
            'WIDGET',
            'App',
            File::createByFullFilenamePathString('/tmp/b.php'),
            2,
        );

        $this->assertSame($lower->collisionKey(), $upper->collisionKey());
        $this->assertSame('type:app\\widget', $lower->collisionKey());
    }

    public function testCollisionKeyDiffersBetweenClassAndFunctionGroups(): void
    {
        $class = new Symbol(
            SymbolKind::ClassType,
            'Widget',
            'App',
            File::createByFullFilenamePathString('/tmp/a.php'),
            1,
        );

        $function = new Symbol(
            SymbolKind::FunctionType,
            'Widget',
            'App',
            File::createByFullFilenamePathString('/tmp/b.php'),
            2,
        );

        $this->assertNotSame($class->collisionKey(), $function->collisionKey());
    }
}
