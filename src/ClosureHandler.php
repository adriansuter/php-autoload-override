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

use function call_user_func_array;
use function sprintf;

/**
 * The ClosureHandler is a Singleton class to handle overrides defined as closures.
 *
 * @package AdrianSuter\Autoload\Override
 */
class ClosureHandler
{
    /**
     * @var self The singleton instance.
     */
    private static $instance;

    /**
     * @var Closure[] A list of closures.
     */
    private $closures = [];

    /**
     * Get the singleton instance.
     *
     * @return self The ClosureHandler.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new ClosureHandler();
        }

        return self::$instance;
    }

    /**
     * Add a closure.
     *
     * This method would overwrite any previously defined closure with the same name.
     *
     * @param string $name The closure name.
     * @param Closure $closure The closure.
     */
    public function addClosure(string $name, Closure $closure): void
    {
        $this->closures[$name] = $closure;
    }

    /**
     * Magic call.
     *
     * @param string $name The method name.
     * @param array<mixed> $arguments The method arguments.
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (isset($this->closures[$name])) {
            return call_user_func_array(
                $this->closures[$name],
                $arguments
            );
        }

        throw new RuntimeException(sprintf('Closure Override "%s" could not be found.', $name));
    }
}
