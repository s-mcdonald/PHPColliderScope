<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit\PHPDocumentParser;

use FsKit\File;
use PHPColliderScope\PHPDocumentParser\DeclarationExtractor;
use PHPColliderScope\PHPDocumentParser\Document\SymbolKind;
use PHPColliderScope\PHPDocumentParser\Tokenizers\PhpBuiltinTokenizer;
use PHPColliderScope\PHPDocumentParser\Tokenizers\Tokenizer;
use PHPUnit\Framework\TestCase;

final class DeclarationExtractorTest extends TestCase
{
    private DeclarationExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new DeclarationExtractor(new Tokenizer(new PhpBuiltinTokenizer()));
    }

    public function testExtractsClassInterfaceTraitEnumAndFunctionDeclarations(): void
    {
        $document = $this->extract('AllKinds.php');

        $this->assertSame('Fixtures\\Declarations\\AllKinds', $document->namespace);
        $this->assertCount(5, $document->declarations);

        $byName = [];
        foreach ($document->declarations as $symbol) {
            $byName[$symbol->name] = $symbol;
        }

        $this->assertSame(SymbolKind::ClassType, $byName['Alpha']->kind);
        $this->assertSame(SymbolKind::InterfaceType, $byName['Beta']->kind);
        $this->assertSame(SymbolKind::TraitType, $byName['Gamma']->kind);
        $this->assertSame(SymbolKind::EnumType, $byName['Delta']->kind);
        $this->assertSame(SymbolKind::FunctionType, $byName['helper']->kind);

        $this->assertSame(
            'Fixtures\\Declarations\\AllKinds\\Alpha',
            $byName['Alpha']->fullyQualifiedName(),
        );
        $this->assertSame(7, $byName['Alpha']->line);
    }

    public function testHandlesDeeplyQualifiedNamespaces(): void
    {
        $document = $this->extract('QualifiedNamespace.php');

        $this->assertSame('Fixtures\\Declarations\\Qualified\\Deeply\\Nested', $document->namespace);
        $this->assertCount(1, $document->declarations);
        $this->assertSame(
            'Fixtures\\Declarations\\Qualified\\Deeply\\Nested\\Widget',
            $document->declarations[0]->fullyQualifiedName(),
        );
    }

    public function testNamespaceBlockFormResetsBetweenBlocks(): void
    {
        $document = $this->extract('NamespaceBlockForm.php');

        $this->assertCount(2, $document->declarations);

        $inBlock = $document->declarations[0];
        $inGlobal = $document->declarations[1];

        $this->assertSame('Fixtures\\Declarations\\Block\\InBlock', $inBlock->fullyQualifiedName());
        $this->assertSame('InGlobal', $inGlobal->fullyQualifiedName());
        $this->assertNull($inGlobal->namespace);
    }

    public function testClassConstantFetchIsNotMistakenForADeclaration(): void
    {
        $document = $this->extract('ClassConstantFetch.php');

        $names = array_map(static fn ($symbol) => $symbol->name, $document->declarations);

        $this->assertCount(2, $document->declarations);
        $this->assertSame(['Marker', 'getClassName'], $names);
    }

    public function testAnonymousClassBodyDoesNotLeakAMethodAsAGlobalFunction(): void
    {
        $document = $this->extract('AnonymousClass.php');

        // Only the enclosing `makeAnon` function should surface: the
        // anonymous class itself has no name to record, and its method
        // must not be mistaken for a second global function.
        $this->assertCount(1, $document->declarations);
        $this->assertSame('makeAnon', $document->declarations[0]->name);
        $this->assertSame(SymbolKind::FunctionType, $document->declarations[0]->kind);
    }

    public function testUseFunctionImportIsNotADeclaration(): void
    {
        $document = $this->extract('UseFunctionImport.php');

        $this->assertCount(1, $document->declarations);
        $this->assertSame('wrapper', $document->declarations[0]->name);
    }

    public function testByRefFunctionNameIsReadCorrectly(): void
    {
        $document = $this->extract('ByRefFunction.php');

        $this->assertCount(1, $document->declarations);
        $this->assertSame('getRef', $document->declarations[0]->name);
        $this->assertSame(SymbolKind::FunctionType, $document->declarations[0]->kind);
    }

    public function testStringInterpolationBracesDoNotUnbalanceBraceDepth(): void
    {
        $document = $this->extract('StringInterpolationBraces.php');

        // If `{$name}` confused the brace-depth tracking, either the
        // `greet` method would leak out as a bogus global function, or
        // `AfterInterpolation` would fail to be detected as top-level.
        $names = array_map(static fn ($symbol) => $symbol->name, $document->declarations);

        $this->assertSame(['Greeter', 'AfterInterpolation'], $names);
    }

    public function testFileWithNoDeclarationsProducesAnEmptyList(): void
    {
        $document = $this->extract('NoDeclarations.php');

        $this->assertFalse($document->hasDeclarations());
        $this->assertNull($document->namespace);
    }

    private function extract(string $fixtureFilename): \PHPColliderScope\PHPDocumentParser\Document\PhpDocument
    {
        $path = __DIR__ . '/../../Fixtures/declarations/' . $fixtureFilename;

        return $this->extractor->extract(File::createByFullFilenamePathString($path));
    }
}
