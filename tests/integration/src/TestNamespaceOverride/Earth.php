<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestNamespaceOverride;

function substr(string $input, int $start, int $length)
{
    return \substr(\strrev($input), $start, $length);
}

/**
 * Overrides declared for
 * - \substr() : FQNS
 *
 * @package My\Integration\TestNamespaceOverride
 */
class Earth
{
    public function substrLocal(): string
    {
        return substr('ABCDEFG', 0, 3);
    }

    public function substrGlobal(): string
    {
        return \substr('ABCDEFG', 0, 3);
    }

    public function time(): int
    {
        return \time();
    }
}
