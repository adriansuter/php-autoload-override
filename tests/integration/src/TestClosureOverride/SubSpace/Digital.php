<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClosureOverride\SubSpace;

/**
 * Overrides declared for
 * - \time() : FQNS
 * - \rand() : FQCN
 *
 * @package My\Integration\TestClosureOverride\SubSpace
 */
class Digital
{
    public function time(): int
    {
        return \time();
    }

    public function rand(int $min, int $max): int
    {
        return \rand($min, $max);
    }
}
