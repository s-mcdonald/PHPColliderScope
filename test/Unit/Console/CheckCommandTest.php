<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit\Console;

use PHPColliderScope\Console\CheckCommand;
use PHPColliderScope\ExitCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckCommandTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../../Fixtures';

    private function tester(): CommandTester
    {
        $application = new Application();
        $command = new CheckCommand();
        $application->add($command);

        return new CommandTester($application->find('check'));
    }

    public function testFailsWithInvalidWhenDirectoryDoesNotExist(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['path' => self::FIXTURES . '/does_not_exist']);

        $this->assertSame(ExitCode::InvalidDirectory, $exitCode);
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testSucceedsWhenNoCollisionsAreFound(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['path' => self::FIXTURES . '/no_duplicates']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No collisions found.', $tester->getDisplay());
    }

    public function testFailsWithASummaryWhenCollisionsAreFoundWithoutFullOption(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['path' => self::FIXTURES . '/contains_duplicates']);
        $display = $tester->getDisplay();

        $this->assertSame(ExitCode::CollisionsFound, $exitCode);
        $this->assertStringContainsString('25 collision(s)', $display);
        $this->assertStringContainsString('--full', $display);
        // The summary form must not spell out individual occurrences.
        $this->assertStringNotContainsString('declaration(s) found', $display);
    }

    public function testFullOptionListsEveryOccurrence(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute([
            'path' => self::FIXTURES . '/contains_duplicates',
            '--full' => true,
        ]);
        $display = $tester->getDisplay();

        $this->assertSame(ExitCode::CollisionsFound, $exitCode);
        $this->assertStringContainsString('declaration(s) found', $display);
        $this->assertStringContainsString('Collide1', $display);
        $this->assertStringContainsString('.php:', $display);
    }

    public function testWarnsAboutSkippedFilesButStillSucceeds(): void
    {
        $tester = $this->tester();

        $exitCode = $tester->execute(['path' => self::FIXTURES . '/invalid']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Skipped', $tester->getDisplay());
        $this->assertStringContainsString('Broken.php', $tester->getDisplay());
    }

    public function testPathArgumentDefaultsToTheCurrentWorkingDirectory(): void
    {
        $command = new CheckCommand();
        $definition = $command->getDefinition();

        $this->assertSame(getcwd() ?: '.', $definition->getArgument('path')->getDefault());
    }
}
