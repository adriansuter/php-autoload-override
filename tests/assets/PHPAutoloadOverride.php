<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace PHPAutoloadOverride;

function time(): int
{
    if (isset($GLOBALS['time_return'])) {
        $r = $GLOBALS['time_return'];
        unset($GLOBALS['time_return']);
        return $r;
    }

    return \time();
}

function substr(string $input, int $start, int $length): string
{
    if (isset($GLOBALS['substr_return'])) {
        $r = $GLOBALS['substr_return'];
        unset($GLOBALS['substr_return']);
        return $r;
    }

    return \substr($input, $start, $length);
}
