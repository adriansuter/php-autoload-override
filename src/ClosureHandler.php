<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

use Closure;
use RuntimeException;

class ClosureHandler
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var Closure[]
     */
    private $closures = [];

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new ClosureHandler();
        }

        return self::$instance;
    }

    /**
     * @param string  $name
     * @param Closure $closure
     */
    function addMethod(string $name, Closure $closure): void
    {
        $this->closures[$name] = $closure;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (isset($this->closures[$name])) {
            return \call_user_func_array(
                $this->closures[$name], $arguments
            );
        }

        throw new RuntimeException(\sprintf('Closure Override %s could not be found.', $name));
    }
}
