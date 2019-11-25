<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

/**
 * Overrides declared for
 * - \str_repeat() : FQCN
 *
 * @package AdrianSuter\Autoload\Override
 */
class Science
{
    public function crosses($amount): string
    {
        return \str_repeat('x', $amount);
    }
}
