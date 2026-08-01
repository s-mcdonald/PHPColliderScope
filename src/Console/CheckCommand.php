<?php

declare(strict_types=1);

namespace PHPColliderScope\Console;

use FsKit\Directory;
use PHPColliderScope\PHPDocumentParser\DeclarationExtractor;
use PHPColliderScope\PHPDocumentParser\Parser;
use PHPColliderScope\PHPDocumentParser\Tokenizers\PhpBuiltinTokenizer;
use PHPColliderScope\PHPDocumentParser\Tokenizers\Tokenizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'check',
    description: 'Scan a directory tree of .php files for symbol collisions',
)]
final class CheckCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'Directory to scan',
            getcwd() ?: '.',
        );

        $this->addOption(
            'full',
            null,
            InputOption::VALUE_NONE,
            'List every occurrence of each colliding symbol',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $directory = Directory::createByFullPathString((string) $input->getArgument('path'));

        if (!$directory->exists()) {
            $io->error(sprintf('Directory "%s" does not exist.', $directory->path()));

            return Command::INVALID;
        }

        $parser = new Parser(new DeclarationExtractor(new Tokenizer(new PhpBuiltinTokenizer())));

        $report = $parser->inspectForCollisions($directory);

        foreach ($parser->skippedFiles() as $skippedPath) {
            $io->warning(sprintf('Skipped (parse error): %s', $skippedPath));
        }

        if (!$report->hasCollisions()) {
            $io->success('No collisions found.');

            return Command::SUCCESS;
        }

        $affectedFiles = [];

        foreach ($report->collisions() as $collision) {
            foreach ($collision->files() as $path) {
                $affectedFiles[$path] = true;
            }
        }

        $io->writeln(sprintf(
            '<error>%d collision(s), across %d file(s).</error>',
            $report->count(),
            count($affectedFiles),
        ));

        if (!$input->getOption('full')) {
            $io->writeln('Run again with <comment>--full</comment> for the file listing.');

            return Command::FAILURE;
        }

        $documents = $parser->documents();
        $declarationCount = array_sum(array_map(
            static fn ($document) => count($document->declarations),
            $documents,
        ));

        $io->writeln(sprintf(
            'Parsed <info>%d</info> .php file(s), <info>%d</info> declaration(s) found.',
            count($documents),
            $declarationCount,
        ));

        $io->newLine();

        foreach ($report->collisions() as $collision) {
            $io->writeln(sprintf('<error>%s</error>', $collision->symbolName));

            foreach ($collision->occurrences as $symbol) {
                $io->writeln(sprintf(
                    '  - [%s] %s:%d',
                    $symbol->kind->value,
                    $symbol->file->path(),
                    $symbol->line,
                ));
            }

            $io->newLine();
        }

        return Command::FAILURE;
    }
}
