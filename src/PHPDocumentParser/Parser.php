<?php

declare(strict_types=1);

namespace PHPColliderScope\PHPDocumentParser;

use FsKit\Directory;
use FsKit\FileSet;
use PHPColliderScope\CollisionConfig;
use PHPColliderScope\PHPDocumentParser\Document\PhpDocument;
use PHPColliderScope\PHPDocumentParser\Report\CollisionReport;

class Parser
{
    /** @var array<string, PhpDocument> keyed by absolute file path */
    private array $documents = [];

    /** @var list<string> paths of files that could not be tokenized */
    private array $skippedFiles = [];

    public function __construct(
        private readonly DeclarationExtractor $extractor
    ) {
    }

    public function inspect(Directory $dir, ?CollisionConfig $config = null): void
    {
        $config ??= CollisionConfig::default();

        $documents = [];
        $skipped = [];

        foreach ($dir->ls(recursive: true)->files() as $file) {
            if (!$config->hasFileExtension($file->extension())) {
                continue;
            }

            try {
                $documents[$file->path()] = $this->extractor->extract($file);
            } catch (\ParseError) {
                $skipped[] = $file->path();
            }
        }

        $this->documents = $documents;
        $this->skippedFiles = $skipped;
    }

    public function inspectFileSet(FileSet $fileSet, ?CollisionConfig $config = null): void
    {
        $config ??= CollisionConfig::default();

        $documents = [];
        $skipped = [];

        foreach ($fileSet as $file) {
            if (!$config->hasFileExtension($file->extension())) {
                continue;
            }

            try {
                $documents[$file->path()] = $this->extractor->extract($file);
            } catch (\ParseError) {
                $skipped[] = $file->path();
            }
        }

        $this->documents = $documents;
        $this->skippedFiles = $skipped;
    }

    /** @return list<string> */
    public function skippedFiles(): array
    {
        return $this->skippedFiles;
    }

    public function documents(): array
    {
        return $this->documents;
    }

    public function inspectForCollisions(Directory $dir, ?CollisionConfig $config = null): CollisionReport
    {
        $config ??= CollisionConfig::default();

        $this->inspect($dir, $config);

        return new CollisionReport($this->documents, $config);
    }

    public function inspectFileSetForCollisions(FileSet $fileSet, ?CollisionConfig $config = null): CollisionReport
    {
        $config ??= CollisionConfig::default();

        $this->inspectFileSet($fileSet, $config);

        return new CollisionReport($this->documents, $config);
    }
}