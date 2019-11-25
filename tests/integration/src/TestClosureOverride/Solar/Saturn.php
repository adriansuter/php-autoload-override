<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace My\Integration\TestClosureOverride\Solar;

class Saturn
{
    public function now(string $format): string
    {
        return \date($format, \time());
    }

    public function rand(int $min, int $max): int
    {
        return \rand($min, $max);
    }
}
