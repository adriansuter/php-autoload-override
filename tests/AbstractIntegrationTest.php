<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\Override;
use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;

abstract class AbstractIntegrationTest extends TestCase
{
    /**
     * @var bool
     */
    protected static $overrideApplied;

    /**
     * @var ClassLoader
     */
    protected static $classLoader;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        static::$classLoader = require(__DIR__ . '/../vendor/autoload.php');
        static::$overrideApplied = false;
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        if (!static::$overrideApplied) {
            static::$overrideApplied = true;

            Override::apply(static::$classLoader, $this->getOverrideDeclarations());
        }
    }

    /**
     * Get the override declarations for the current test class.
     *
     * @return array
     */
    abstract protected function getOverrideDeclarations(): array;
}
