<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClosureOverride;

use My\Integration\TestClosureOverride\SubSpace\Digital;

/**
 * No overrides declared.
 *
 * @package My\Integration\TestClosureOverride
 */
class SubDigital extends Digital
{
    public function subTime(): int
    {
        return \time();
    }

    public function rand(int $min, int $max): int
    {
        return \rand($min, $max);
    }
}
