<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClosureOverride;

use My\Integration\TestClosureOverride\Traits\ClockTrait;

class ClockWithTrait
{
    use ClockTrait;

    public function getMyTime(): int
    {
        return \time();
    }
}
