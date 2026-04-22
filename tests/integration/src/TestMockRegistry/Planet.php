<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestMockRegistry;

/**
 * Overrides declared for (via MockRegistry closures):
 * - \time()  : FQCN — per-class, eager fallback pattern
 * - \rand()  : FQCN — per-class, lazy fallback pattern (has + get)
 *
 * @package My\Integration\TestMockRegistry
 */
class Planet
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
