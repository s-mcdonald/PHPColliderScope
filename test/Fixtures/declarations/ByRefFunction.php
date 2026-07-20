<?php

declare(strict_types=1);

namespace Fixtures\Declarations\ByRef;

function &getRef(): int
{
    static $x = 1;

    return $x;
}
