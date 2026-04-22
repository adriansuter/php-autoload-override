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
 * - \time() : FQCN — resolves via global MockRegistry fallback
 *
 * @package My\Integration\TestMockRegistry
 */
class Star
{
    public function time(): int
    {
        return \time();
    }
}
