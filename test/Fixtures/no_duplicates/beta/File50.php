<?php

declare(strict_types=1);

namespace Fixtures\Clean\Ns50;

enum Status50: string
{
    case Active = 'active';
    case Retired = 'retired';
}
