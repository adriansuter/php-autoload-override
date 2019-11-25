<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClassMapOverride\SubNamespace;

/**
 * Overrides declared for
 * - \cos() : FQNS
 *
 * @package My\Integration\TestClassMapOverride\SubNamespace
 */
class Geometry
{
    public function cos(float $arg): float
    {
        return \cos($arg);
    }
}
