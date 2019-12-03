<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\Autoload\Override\CodeConverter;
use AdrianSuter\Autoload\Override\Override;
use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class OverrideTest extends TestCase
{
    public function testSetFQFCConverter()
    {
        $converter = $this->createMock(CodeConverter::class);

        Override::setCodeConverter($converter);
        $this->assertEquals($converter, Override::getCodeConverter());
    }

    public function testGetFQFCConverter()
    {
        $this->assertInstanceOf(
            CodeConverter::class, Override::getCodeConverter()
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage APC User Cache is not supported.
     */
    public function testApplyApcu()
    {
        $classLoaderProphecy = $this->prophesize(ClassLoader::class);

        $getApcuPrefixMethodProphecy = new \Prophecy\Prophecy\MethodProphecy($classLoaderProphecy, 'getApcuPrefix', []);
        $getApcuPrefixMethodProphecy->willReturn('a');

        $classLoaderProphecy->addMethodProphecy($getApcuPrefixMethodProphecy);

        /** @var ClassLoader $classLoader */
        $classLoader = $classLoaderProphecy->reveal();
        Override::apply($classLoader, []);
    }
}
