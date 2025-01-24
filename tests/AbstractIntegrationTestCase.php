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

abstract class AbstractIntegrationTestCase extends TestCase
{
    private bool $overrideApplied = false;

    private static ClassLoader $classLoader;

    public static function setUpBeforeClass(): void
    {
        self::$classLoader = require(__DIR__ . '/../vendor/autoload.php');
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->overrideApplied) {
            $this->overrideApplied = true;
            Override::apply(self::$classLoader, $this->getOverrideDeclarations());
        }
    }

    abstract protected function getOverrideDeclarations(): array;
}
