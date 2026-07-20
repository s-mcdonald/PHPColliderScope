<?php

declare(strict_types=1);

namespace PHPColliderScope;

final class ExitCode
{
    public const int NoCollisions = 0;
    public const int InvalidDirectory = 1;
    public const int CollisionsFound = 2;
}