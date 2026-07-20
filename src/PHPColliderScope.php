<?php

declare(strict_types=1);

namespace PHPColliderScope;

use FsKit\Directory;
use PHPColliderScope\PHPDocumentParser\DeclarationExtractor;
use PHPColliderScope\PHPDocumentParser\Parser;
use PHPColliderScope\PHPDocumentParser\Tokenizers\PhpBuiltinTokenizer;
use PHPColliderScope\PHPDocumentParser\Tokenizers\Tokenizer;

final class PHPColliderScope
{
    private string $workingDirectory;

    public function __construct(string|Directory $directoryPath = __DIR__)
    {
        $this->cd($directoryPath);
    }

    public function cd(string|Directory $directoryPath): void
    {
        if ($directoryPath instanceof Directory) {
            $directoryPath = $directoryPath->path();
        }

        if (!is_dir($directoryPath)) {
            throw new \InvalidArgumentException("Invalid start directory: $directoryPath");
        }

        $this->workingDirectory = $directoryPath;
    }

    public function ls(bool $recursive = false): void
    {
        $directory = Directory::createByFullPathString($this->workingDirectory);

        if (!$directory->exists()) {
            fwrite(STDERR, \sprintf("Directory \"%s\" does not exist.\n", $directory->path()));
            exit(1);
        }

        echo \sprintf("Listing: %s\n\n", $directory->path());

        $listing = $directory->ls(recursive: $recursive);

        foreach ($listing as $entry) {
            $attributes = $entry->attributes();

            printf(
                "%-10s %-40s modified: %s\n",
                $attributes->type()->value,
                $entry->name(),
                $attributes->lastModified() !== null
                    ? date('Y-m-d H:i:s', $attributes->lastModified())
                    : 'n/a'
            );
        }

        \printf(
            "\nTotal: %d files, %d directories\n",
            count($listing->files()),
            count($listing->directories())
        );
    }

    public function checkCollisions(?CollisionConfig $config = null): void
    {
        $directory = Directory::createByFullPathString($this->workingDirectory);

        if (!$directory->exists()) {
            fwrite(STDERR, sprintf("Directory \"%s\" does not exist.\n", $directory->path()));
            exit(1);
        }

        $extractor = new DeclarationExtractor(new Tokenizer( new PhpBuiltinTokenizer()));

        $parser = new Parser($extractor);

        $report = $parser->inspectForCollisions($directory, $config);

        foreach ($parser->skippedFiles() as $skippedPath) {
            fwrite(STDERR, sprintf("Skipped (parse error): %s\n", $skippedPath));
        }

        $documents = $parser->documents();
        $declarationCount = array_sum(array_map(
            static fn ($doc) => count($doc->declarations),
            $documents,
        ));

        printf(
            "Parsed %d .php file(s), %d declaration(s) found.\n\n",
            count($documents),
            $declarationCount,
        );

        if (!$report->hasCollisions()) {
            echo "No collisions found.\n";
            exit(0);
        }

        printf("Found %d collision(s):\n\n", $report->count());

        foreach ($report->collisions() as $collision) {
            printf("  %s\n", $collision->symbolName);

            foreach ($collision->occurrences as $symbol) {
                printf("    - [%s] %s:%d\n", $symbol->kind->value, $symbol->file->path(), $symbol->line);
            }

            echo "\n";
        }
    }
}