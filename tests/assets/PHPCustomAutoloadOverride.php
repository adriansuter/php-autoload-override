<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace PHPCustomAutoloadOverride;

/** @noinspection PhpUnused */
function md5(string $str, bool $raw_output = false): string
{
    if (isset($GLOBALS['md5_return'])) {
        $r = $GLOBALS['md5_return'];
        unset($GLOBALS['md5_return']);
        return $r;
    }

    return \md5($str, $raw_output);
}
