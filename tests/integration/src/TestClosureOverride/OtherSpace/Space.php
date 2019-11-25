<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClosureOverride\OtherSpace;

function time(): int
{
    return 105;
}

/**
 * No overrides declared.
 *
 * @package My\Integration\TestClosureOverride\OtherSpace
 */
class Space
{
    public function time(): int
    {
        return \time();
    }

    public function timeLocal(): int
    {
        return time();
    }
}
