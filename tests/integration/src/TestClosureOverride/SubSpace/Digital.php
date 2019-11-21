<?php

declare(strict_types=1);

namespace My\Integration\TestClosureOverride\SubSpace;

class Digital
{
    public function minute(): string
    {
        return \date('i', \time());
    }
}
