<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClosureOverride;

class BigBen extends Clock
{
    public function hour(): string
    {
        return \str_repeat('*', (int)\date('h', \time()));
    }
}
