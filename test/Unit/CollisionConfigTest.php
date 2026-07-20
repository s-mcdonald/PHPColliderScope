<?php

declare(strict_types=1);

namespace Tests\PHPColliderScope\Unit;

use PHPColliderScope\CollisionConfig;
use PHPUnit\Framework\TestCase;

final class CollisionConfigTest extends TestCase
{
    public function testConstructorDefaultsToFindingNothing(): void
    {
        $config = new CollisionConfig();

        $this->assertFalse($config->findClassNamespaceCollision);
        $this->assertFalse($config->findFunctionNamespaceCollision);
    }

    public function testConstructorAcceptsExplicitFlags(): void
    {
        $config = new CollisionConfig(
            findClassNamespaceCollision: true,
            findFunctionNamespaceCollision: false,
        );

        $this->assertTrue($config->findClassNamespaceCollision);
        $this->assertFalse($config->findFunctionNamespaceCollision);
    }

    public function testDefaultFactoryFindsBothGroups(): void
    {
        $config = CollisionConfig::default();

        $this->assertTrue($config->findClassNamespaceCollision);
        $this->assertTrue($config->findFunctionNamespaceCollision);
    }

    public function testFileExtensionsAlwaysIncludesPhpByDefault(): void
    {
        $config = new CollisionConfig();

        $this->assertSame(['php'], $config->fileExtensions);
        $this->assertTrue($config->hasFileExtension('php'));
    }

    public function testAdditionalFileExtensionsAreAppendedToThePhpDefault(): void
    {
        $config = new CollisionConfig(additionalFileExtensions: ['phpt', 'phtml']);

        $this->assertSame(['php', 'phpt', 'phtml'], $config->fileExtensions);
        $this->assertTrue($config->hasFileExtension('phpt'));
        $this->assertTrue($config->hasFileExtension('phtml'));
    }

    public function testAdditionalFileExtensionsAreNormalizedAndDeduplicated(): void
    {
        $config = new CollisionConfig(additionalFileExtensions: ['.PHTML', 'php', 'PhpT']);

        $this->assertSame(['php', 'phtml', 'phpt'], $config->fileExtensions);
    }

    public function testHasFileExtensionIsCaseInsensitiveAndIgnoresALeadingDot(): void
    {
        $config = new CollisionConfig(additionalFileExtensions: ['phtml']);

        $this->assertTrue($config->hasFileExtension('PHTML'));
        $this->assertTrue($config->hasFileExtension('.phtml'));
        $this->assertFalse($config->hasFileExtension('phps'));
    }

    public function testWithFileExtensionReturnsANewConfigWithTheExtensionAdded(): void
    {
        $original = new CollisionConfig();
        $withPhtml = $original->withFileExtension('phtml');

        $this->assertSame(['php'], $original->fileExtensions);
        $this->assertSame(['php', 'phtml'], $withPhtml->fileExtensions);
        $this->assertNotSame($original, $withPhtml);
    }
}
