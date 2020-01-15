<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\AutoloadCollection;
use PHPUnit\Framework\TestCase;

class AutoloadCollectionTest extends TestCase
{
    public function testAddFile()
    {
        $autoloadCollection = new AutoloadCollection();
        $autoloadCollection->addFile(__DIR__ . '/sub/../' . basename(__FILE__));

        $this->assertEquals([realpath(__FILE__)], $autoloadCollection->getFilePaths());
    }

    public function testAddDirectoryNonExistent()
    {
        $autoloadCollection = new AutoloadCollection();
        $autoloadCollection->addDirectory(__DIR__ . '/not-existent');

        $this->assertEmpty($autoloadCollection->getFilePaths());
    }
}
