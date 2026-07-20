<?php

declare(strict_types=1);

namespace Fixtures\Declarations\Anon;

function makeAnon(): object
{
    return new class {
        public function method(): int
        {
            return 1;
        }
    };
}
