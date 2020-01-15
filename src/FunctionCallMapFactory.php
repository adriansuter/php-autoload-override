<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

use Closure;

use function get_class;
use function is_numeric;
use function is_string;
use function spl_object_hash;

class FunctionCallMapFactory
{
    /**
     * @var string
     */
    private $defaultOverrideNamespace;

    /**
     * @var ClosureHandler
     */
    private $closureHandler;

    /**
     * FunctionCallMapFactory constructor.
     *
     * @param string         $defaultOverrideNamespace
     * @param ClosureHandler $closureHandler
     */
    public function __construct(string $defaultOverrideNamespace, ClosureHandler $closureHandler)
    {
        $this->defaultOverrideNamespace = $defaultOverrideNamespace;
        $this->closureHandler = $closureHandler;
    }

    /**
     * Build a mapping between root namespaced function calls and their
     * overridden fully qualified name.
     *
     * @param string[]|Closure[] $map
     *
     * @return string[]
     */
    public function createFunctionCallMap(array $map): array
    {
        $functionCallMap = [];
        foreach ($map as $key => $val) {
            if (is_numeric($key)) {
                $functionCallMap['\\' . $val] = $this->defaultOverrideNamespace . '\\' . $val;
            } elseif (is_string($val)) {
                $functionCallMap['\\' . $key] = $val . '\\' . $key;
            } elseif ($val instanceof Closure) {
                $name = $key . '_' . spl_object_hash($val);
                $this->closureHandler->setClosure($name, $val);

                $functionCallMap['\\' . $key] = get_class($this->closureHandler) . '::getInstance()->' . $name;
            }
        }

        return $functionCallMap;
    }
}
