<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestCustomNamespaceOverride;

/**
 * Overrides declared for
 * - \md5() : FQCN
 *
 * @package My\Integration\TestCustomNamespaceOverride
 */
class Hash
{
    public function hash(string $str): string
    {
        return \md5($str);
    }
}
