<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestNamespaceOverride;

use function time as time_alias;

class Moon
{
    public function now(): int
    {
        return \time();
    }

    public function nowUseAlias(): int
    {
        return time_alias();
    }
}