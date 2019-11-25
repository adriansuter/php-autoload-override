<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClosureOverride\SubSpace;

class Digital
{
    public function now(): string
    {
        return \date('d.m.Y H:i:s', \time());
    }
}
