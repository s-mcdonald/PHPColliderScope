<?php

declare(strict_types=1);

namespace Fixtures\Declarations\ConstFetch;

class Marker
{
}

function getClassName(): string
{
    return Marker::class;
}
