<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\Autoload\Override\CodeConverter;
use AdrianSuter\Autoload\Override\Override;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class OverrideTest extends TestCase
{
    public function testSetFQFCConverter()
    {
        $converter = $this->createMock(CodeConverter::class);

        Override::setFQFCConverter($converter);
        $this->assertEquals($converter, Override::getFQFCConverter());
    }

    public function testGetFQFCConverter()
    {
        $this->assertInstanceOf(
            CodeConverter::class, Override::getFQFCConverter()
        );
    }
}
