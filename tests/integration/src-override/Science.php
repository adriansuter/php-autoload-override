<?php

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

class Science
{
    public function crosses($amount): string
    {
        return \str_repeat('x', $amount);
    }
}
