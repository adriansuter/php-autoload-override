<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\Autoload\Override\AutoloadCollection;
use PHPUnit\Framework\TestCase;

class AutoloadCollectionTest extends TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddDirectories()
    {
        $autoloadCollection = new AutoloadCollection();
        $autoloadCollection->addDirectory(__DIR__ . '/not-existent');
    }
}
