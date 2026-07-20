<?php

declare(strict_types=1);

namespace Fixtures\Declarations\UseImport;

use function strlen;

function wrapper(): int
{
    return strlen('x');
}
