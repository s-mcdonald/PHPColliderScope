<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit\PHPDocumentParser\Report;

use FsKit\File;
use PHPColliderScope\CollisionConfig;
use PHPColliderScope\PHPDocumentParser\Document\PhpDocument;
use PHPColliderScope\PHPDocumentParser\Document\Symbol;
use PHPColliderScope\PHPDocumentParser\Document\SymbolKind;
use PHPColliderScope\PHPDocumentParser\Report\CollisionReport;
use PHPUnit\Framework\TestCase;

final class CollisionReportTest extends TestCase
{
    public function testEmptyDocumentSetHasNoCollisions(): void
    {
        $report = new CollisionReport([]);

        $this->assertFalse($report->hasCollisions());
        $this->assertSame(0, $report->count());
        $this->assertSame([], $report->collisions());
    }

    public function testASingleDeclarationNeverCollidesWithItself(): void
    {
        $file = File::createByFullFilenamePathString('/tmp/Widget.php');
        $document = new PhpDocument($file, 'App', [
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $file, 5),
        ]);

        $report = new CollisionReport(['/tmp/Widget.php' => $document]);

        $this->assertFalse($report->hasCollisions());
    }

    public function testTwoDeclarationsOfTheSameFullyQualifiedNameCollide(): void
    {
        $fileA = File::createByFullFilenamePathString('/tmp/A.php');
        $fileB = File::createByFullFilenamePathString('/tmp/B.php');

        $docA = new PhpDocument($fileA, 'App', [
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $fileA, 5),
        ]);
        $docB = new PhpDocument($fileB, 'App', [
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $fileB, 9),
        ]);

        $report = new CollisionReport([
            '/tmp/A.php' => $docA,
            '/tmp/B.php' => $docB,
        ]);

        $this->assertTrue($report->hasCollisions());
        $this->assertSame(1, $report->count());

        $collision = $report->collisions()[0];
        $this->assertSame('App\\Widget', $collision->symbolName);
        $this->assertCount(2, $collision->occurrences);
    }

    public function testClassAndFunctionWithTheSameNameDoNotCollide(): void
    {
        $file = File::createByFullFilenamePathString('/tmp/Widget.php');

        $document = new PhpDocument($file, 'App', [
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $file, 5),
            new Symbol(SymbolKind::FunctionType, 'Widget', 'App', $file, 12),
        ]);

        $report = new CollisionReport(['/tmp/Widget.php' => $document]);

        $this->assertFalse($report->hasCollisions());
    }

    public function testNamesCollideCaseInsensitively(): void
    {
        $fileA = File::createByFullFilenamePathString('/tmp/A.php');
        $fileB = File::createByFullFilenamePathString('/tmp/B.php');

        $docA = new PhpDocument($fileA, 'App', [
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $fileA, 5),
        ]);
        $docB = new PhpDocument($fileB, 'App', [
            new Symbol(SymbolKind::ClassType, 'WIDGET', 'App', $fileB, 9),
        ]);

        $report = new CollisionReport([
            '/tmp/A.php' => $docA,
            '/tmp/B.php' => $docB,
        ]);

        $this->assertTrue($report->hasCollisions());
        $this->assertSame(1, $report->count());
    }

    public function testInterfaceAndClassWithTheSameNameDoCollide(): void
    {
        // Interfaces and classes both belong to the "type" group, so a
        // class and an interface sharing a fully qualified name are
        // still a real collision (you can't `use` both under one name).
        $fileA = File::createByFullFilenamePathString('/tmp/A.php');
        $fileB = File::createByFullFilenamePathString('/tmp/B.php');

        $docA = new PhpDocument($fileA, 'App', [
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $fileA, 5),
        ]);
        $docB = new PhpDocument($fileB, 'App', [
            new Symbol(SymbolKind::InterfaceType, 'Widget', 'App', $fileB, 9),
        ]);

        $report = new CollisionReport([
            '/tmp/A.php' => $docA,
            '/tmp/B.php' => $docB,
        ]);

        $this->assertTrue($report->hasCollisions());
    }

    /** @return array{0: PhpDocument, 1: PhpDocument} one class collision, one function collision */
    private function mixedCollisionDocuments(): array
    {
        $fileA = File::createByFullFilenamePathString('/tmp/A.php');
        $fileB = File::createByFullFilenamePathString('/tmp/B.php');

        $docA = new PhpDocument($fileA, 'App', [
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $fileA, 5),
            new Symbol(SymbolKind::FunctionType, 'helper', 'App', $fileA, 20),
        ]);
        $docB = new PhpDocument($fileB, 'App', [
            new Symbol(SymbolKind::ClassType, 'Widget', 'App', $fileB, 9),
            new Symbol(SymbolKind::FunctionType, 'helper', 'App', $fileB, 30),
        ]);

        return [$docA, $docB];
    }

    public function testWithNoConfigBothClassAndFunctionCollisionsAreReported(): void
    {
        [$docA, $docB] = $this->mixedCollisionDocuments();

        $report = new CollisionReport(['/tmp/A.php' => $docA, '/tmp/B.php' => $docB]);

        $this->assertSame(2, $report->count());
    }

    public function testConfigCanRestrictToClassCollisionsOnly(): void
    {
        [$docA, $docB] = $this->mixedCollisionDocuments();

        $report = new CollisionReport(
            ['/tmp/A.php' => $docA, '/tmp/B.php' => $docB],
            new CollisionConfig(findClassNamespaceCollision: true, findFunctionNamespaceCollision: false),
        );

        $this->assertSame(1, $report->count());
        $this->assertSame('App\\Widget', $report->collisions()[0]->symbolName);
    }

    public function testConfigCanRestrictToFunctionCollisionsOnly(): void
    {
        [$docA, $docB] = $this->mixedCollisionDocuments();

        $report = new CollisionReport(
            ['/tmp/A.php' => $docA, '/tmp/B.php' => $docB],
            new CollisionConfig(findClassNamespaceCollision: false, findFunctionNamespaceCollision: true),
        );

        $this->assertSame(1, $report->count());
        $this->assertSame('App\\helper', $report->collisions()[0]->symbolName);
    }

    public function testConfigWithBothFlagsDisabledFindsNothing(): void
    {
        [$docA, $docB] = $this->mixedCollisionDocuments();

        $report = new CollisionReport(
            ['/tmp/A.php' => $docA, '/tmp/B.php' => $docB],
            new CollisionConfig(findClassNamespaceCollision: false, findFunctionNamespaceCollision: false),
        );

        $this->assertFalse($report->hasCollisions());
    }
}
