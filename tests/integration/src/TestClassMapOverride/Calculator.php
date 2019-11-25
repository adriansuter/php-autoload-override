<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClassMapOverride;

/**
 * Overrides declared for
 * - \cos() : FQCN
 *
 * @package My\Integration\TestClassMapOverride
 */
class Calculator
{
    public function cos(float $arg): float
    {
        return \cos($arg);
    }
}
