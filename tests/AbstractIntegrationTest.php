<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\Autoload\Override\Override;
use PHPUnit\Framework\TestCase;

abstract class AbstractIntegrationTest extends TestCase
{
    private $overrideApplied = false;

    private static $classLoader;

    public static function setUpBeforeClass()
    {
        self::$classLoader = require(__DIR__ . '/../vendor/autoload.php');
    }

    protected function setUp()
    {
        parent::setUp();

        if (!$this->overrideApplied) {
            $this->overrideApplied = true;
            Override::apply(self::$classLoader, $this->getOverrideDeclarations());
        }
    }

    abstract protected function getOverrideDeclarations(): array;
}
