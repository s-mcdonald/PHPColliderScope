<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit;

use PHPColliderScope\PHPColliderScope;
use PHPUnit\Framework\TestCase;

/**
 * NOTE: PHPColliderScope::checkCollisions() calls exit() on every branch
 * (no collisions, collisions found, and missing directory alike), and
 * ::ls() calls exit() on its missing-directory branch. None of those
 * branches can be exercised here without terminating the PHPUnit process,
 * so only ls()'s non-exiting path is covered below. Coverage for the
 * collision-reporting behaviour itself lives in CheckCommandTest, which
 * goes through Symfony's Command return codes instead of exit().
 */
final class PHPColliderScopeTest extends TestCase
{
    private const string FIXTURES = __DIR__ . '/../Fixtures';

    public function testLsListsFilesInTheWorkingDirectory(): void
    {
        $scope = new PHPColliderScope(self::FIXTURES . '/no_duplicates/alpha');

        ob_start();
        $scope->ls();
        $output = ob_get_clean();

        $this->assertStringContainsString('Listing:', $output);
        $this->assertStringContainsString('File1.php', $output);
        $this->assertStringContainsString('Total: 25 files, 0 directories', $output);
    }

    public function testCdChangesTheDirectoryUsedBySubsequentCalls(): void
    {
        $scope = new PHPColliderScope(self::FIXTURES . '/no_duplicates/alpha');
        $scope->cd(self::FIXTURES . '/no_duplicates/beta');

        ob_start();
        $scope->ls();
        $output = ob_get_clean();

        $this->assertStringContainsString('File26.php', $output);
        $this->assertStringNotContainsString('File1.php', $output);
    }
}
