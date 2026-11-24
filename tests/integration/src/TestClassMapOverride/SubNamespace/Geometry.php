<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClassMapOverride\SubNamespace;

/**
 * No overrides apply: the FQNS-declaration of the parent namespace does not affect sub-namespaces.
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
