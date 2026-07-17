<?php

declare(strict_types=1);

namespace Fixtures\Clean\Ns15;

enum Status15: string
{
    case Active = 'active';
    case Retired = 'retired';
}
