<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClosureOverride;

/**
 * No overrides declared.
 *
 * @package My\Integration\TestClosureOverride
 */
class SubClock extends Clock
{
    public function time(): int
    {
        return \time();
    }

    public function rand(int $min, int $max): int
    {
        return parent::rand($min, $max);
    }
}
