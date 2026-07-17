<?php

declare(strict_types=1);

namespace Fixtures\Clean\Ns20;

enum Status20: string
{
    case Active = 'active';
    case Retired = 'retired';
}
