<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClosureOverride;

use function time as timeAlias;

/**
 * Overrides declared for
 * - \time() : FQCN
 * - \rand() : FQCN
 *
 * @package My\Integration\TestClosureOverride
 */
class Clock
{
    public function time(): int
    {
        return \time();
    }

    public function timeWithAlias(): int
    {
        return timeAlias();
    }

    public function rand(int $min, int $max): int
    {
        return \rand($min, $max);
    }
}
