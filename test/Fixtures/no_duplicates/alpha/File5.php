<?php

declare(strict_types=1);

namespace Fixtures\Clean\Ns5;

enum Status5: string
{
    case Active = 'active';
    case Retired = 'retired';
}
