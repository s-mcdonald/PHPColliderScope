<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit\PHPDocumentParser\Document;

use PHPColliderScope\PHPDocumentParser\Document\SymbolKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SymbolKindTest extends TestCase
{
    #[DataProvider('classLikeKinds')]
    public function testClassLikeKindsReportAsClassLike(SymbolKind $kind): void
    {
        $this->assertTrue($kind->isClassLike());
        $this->assertSame('type', $kind->collidesWithGroup());
    }

    public function testFunctionTypeIsNotClassLike(): void
    {
        $this->assertFalse(SymbolKind::FunctionType->isClassLike());
        $this->assertSame('function', SymbolKind::FunctionType->collidesWithGroup());
    }

    /** @return list<array{0: SymbolKind}> */
    public static function classLikeKinds(): array
    {
        return [
            [SymbolKind::ClassType],
            [SymbolKind::InterfaceType],
            [SymbolKind::TraitType],
            [SymbolKind::EnumType],
        ];
    }
}
