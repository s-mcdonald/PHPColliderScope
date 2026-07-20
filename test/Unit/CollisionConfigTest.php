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
}
