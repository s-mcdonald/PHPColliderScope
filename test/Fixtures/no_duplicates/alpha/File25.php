<?php

declare(strict_types=1);

namespace Fixtures\Clean\Ns25;

enum Status25: string
{
    case Active = 'active';
    case Retired = 'retired';
}
