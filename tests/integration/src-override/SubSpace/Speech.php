<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\SubSpace;

/**
 * Overrides declared for
 * - \str_repeat() : FQNS
 *
 * @package AdrianSuter\Autoload\Override\SubSpace
 */
class Speech
{
    public function whisper(int $amount): string
    {
        return \str_repeat('_', $amount);
    }
}
