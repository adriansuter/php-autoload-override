<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestNamespaceOverride;

use function time as time_alias;

/**
 * Overrides declared for
 * - \substr() : FQNS
 * - \time() : FQCN
 *
 * @package My\Integration\TestNamespaceOverride
 */
class Moon
{
    public function time(): int
    {
        return \time();
    }

    public function timeUseAlias(): int
    {
        return time_alias();
    }

    public function substr(string $input, int $start, int $length): string
    {
        return \substr($input, $start, $length);
    }
}
