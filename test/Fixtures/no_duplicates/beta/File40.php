<?php

declare(strict_types=1);

namespace Fixtures\Clean\Ns40;

enum Status40: string
{
    case Active = 'active';
    case Retired = 'retired';
}
