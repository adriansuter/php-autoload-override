<?php

declare(strict_types=1);

namespace My\Integration\TestClosureOverride;

class BigBen extends Clock
{
    public function hour(): string
    {
        return \str_repeat('*', (int)\date('h', \time()));
    }
}
