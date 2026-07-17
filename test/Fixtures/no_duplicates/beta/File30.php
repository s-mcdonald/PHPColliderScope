<?php

declare(strict_types=1);

namespace Fixtures\Clean\Ns30;

enum Status30: string
{
    case Active = 'active';
    case Retired = 'retired';
}
