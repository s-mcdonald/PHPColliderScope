<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit\PHPDocumentParser;

use FsKit\Directory;
use FsKit\FileSet;
use PHPColliderScope\PHPDocumentParser\DeclarationExtractor;
use PHPColliderScope\PHPDocumentParser\Parser;
use PHPColliderScope\PHPDocumentParser\Tokenizers\PhpBuiltinTokenizer;
use PHPColliderScope\PHPDocumentParser\Tokenizers\Tokenizer;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../../Fixtures';

    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser(new DeclarationExtractor(new Tokenizer(new PhpBuiltinTokenizer())));
    }

    public function testInspectForCollisionsFindsEveryColliderPair(): void
    {
        $directory = Directory::createByFullPathString(self::FIXTURES . '/contains_duplicates');

        $report = $this->parser->inspectForCollisions($directory);

        $this->assertTrue($report->hasCollisions());
        // 25 files under alpha/, each colliding with its beta/ counterpart.
        $this->assertSame(25, $report->count());
        $this->assertSame([], $this->parser->skippedFiles());
    }

    public function testInspectForCollisionsFindsNothingInTheCleanFixtures(): void
    {
        $directory = Directory::createByFullPathString(self::FIXTURES . '/no_duplicates');

        $report = $this->parser->inspectForCollisions($directory);

        $this->assertFalse($report->hasCollisions());
        $this->assertSame(0, $report->count());

        // 50 files, one class each, plus a same-named function that must
        // not be reported as colliding with its sibling class.
        $this->assertCount(50, $this->parser->documents());
    }

    public function testUnparsableFilesAreSkippedRatherThanThrowing(): void
    {
        $directory = Directory::createByFullPathString(self::FIXTURES . '/invalid');

        $report = $this->parser->inspectForCollisions($directory);

        $this->assertFalse($report->hasCollisions());
        $this->assertCount(1, $this->parser->skippedFiles());
        $this->assertStringEndsWith('Broken.php', $this->parser->skippedFiles()[0]);
        $this->assertSame([], $this->parser->documents());
    }

    public function testInspectFileSetOnlyLooksAtTheSuppliedFiles(): void
    {
        $fileSet = FileSet::createFromDir(self::FIXTURES . '/contains_duplicates/alpha')
            ->addDir(self::FIXTURES . '/contains_duplicates/beta');

        $report = $this->parser->inspectFileSetForCollisions($fileSet);

        $this->assertTrue($report->hasCollisions());
        $this->assertSame(25, $report->count());
    }

    public function testInspectFileSetExcludingADirectoryRemovesItsCollisions(): void
    {
        $fileSet = FileSet::createFromDir(self::FIXTURES . '/contains_duplicates/alpha')
            ->addDir(self::FIXTURES . '/contains_duplicates/beta')
            ->excludeDirectoryFile(self::FIXTURES . '/contains_duplicates/beta');

        $report = $this->parser->inspectFileSetForCollisions($fileSet);

        // With every beta/ file excluded, no alpha/ file has a counterpart
        // left to collide with.
        $this->assertFalse($report->hasCollisions());
    }

    public function testNonPhpFilesAreIgnored(): void
    {
        $directory = Directory::createByFullPathString(self::FIXTURES . '/no_duplicates/alpha');

        $this->parser->inspect($directory);

        foreach ($this->parser->documents() as $path => $document) {
            $this->assertSame('php', strtolower(pathinfo($path, PATHINFO_EXTENSION)));
        }
    }
}
