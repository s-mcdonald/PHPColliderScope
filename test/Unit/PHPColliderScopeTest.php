<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit;

use PHPColliderScope\PHPColliderScope;
use PHPUnit\Framework\TestCase;

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

    public function testCheckCollisionsReportsCollisionsWithoutExiting(): void
    {
        $scope = new PHPColliderScope(self::FIXTURES . '/contains_duplicates');

        ob_start();
        $scope->checkCollisions();
        $output = ob_get_clean();

        $this->assertStringContainsString('Found 25 collision(s)', $output);
    }
}
