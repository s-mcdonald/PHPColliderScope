<?php

declare(strict_types=1);

namespace Fixtures\Clean\Ns10;

enum Status10: string
{
    case Active = 'active';
    case Retired = 'retired';
}
