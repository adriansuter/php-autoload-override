<?php

declare(strict_types=1);

namespace My\Integration\TestClosureOverride;

class Clock
{
    public function now(): int
    {
        return \time();
    }

    public function hour(): string
    {
        return \date('H', \time());
    }
}
