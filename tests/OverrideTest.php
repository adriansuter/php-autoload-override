<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\AutoloadCollection;
use AdrianSuter\Autoload\Override\ClosureHandler;
use AdrianSuter\Autoload\Override\CodeConverter;
use AdrianSuter\Autoload\Override\Override;
use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\MethodProphecy;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

#[CoversClass(Override::class)]
#[UsesClass(AutoloadCollection::class)]
#[RunTestsInSeparateProcesses]
class OverrideTest extends TestCase
{
    use ProphecyTrait;

    public function testSetCodeConverter()
    {
        $converter = $this->createMock(CodeConverter::class);

        Override::setCodeConverter($converter);

        $converterReflectionProperty = new ReflectionProperty(Override::class, 'converter');
        $converterReflectionProperty->setAccessible(true);
        $this->assertEquals($converter, $converterReflectionProperty->getValue());
    }

    public function testApplyApcu()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APC User Cache is not supported.');

        $classLoaderProphecy = $this->prophesize(ClassLoader::class);

        $getApcuPrefixMethodProphecy = new MethodProphecy($classLoaderProphecy, 'getApcuPrefix', []);
        $getApcuPrefixMethodProphecy->willReturn('a');

        $classLoaderProphecy->addMethodProphecy($getApcuPrefixMethodProphecy);

        /** @var ClassLoader $classLoader */
        $classLoader = $classLoaderProphecy->reveal();
        Override::apply($classLoader, []);
    }

    public function testApplyWithUnknownClass()
    {
        $classLoaderProphecy = $this->prophesize(ClassLoader::class);

        $getApcuPrefixMethodProphecy = new MethodProphecy($classLoaderProphecy, 'getApcuPrefix', []);
        $getApcuPrefixMethodProphecy->willReturn(null);

        $classLoaderProphecy->addMethodProphecy($getApcuPrefixMethodProphecy);

        $findFileMethodProphecy = new MethodProphecy($classLoaderProphecy, 'findFile', [Argument::is('ClassName')]);
        $findFileMethodProphecy->willReturn(false);
        $classLoaderProphecy->addMethodProphecy($findFileMethodProphecy);

        /** @var ClassLoader $classLoader */
        $classLoader = $classLoaderProphecy->reveal();
        Override::apply($classLoader, ['ClassName' => []]);

        $this->assertTrue(true);
    }

    public function testApplyWithClassInNonexistentFile()
    {
        $classLoaderProphecy = $this->prophesize(ClassLoader::class);

        $getApcuPrefixMethodProphecy = new MethodProphecy($classLoaderProphecy, 'getApcuPrefix', []);
        $getApcuPrefixMethodProphecy->willReturn(null);

        $classLoaderProphecy->addMethodProphecy($getApcuPrefixMethodProphecy);

        $findFileMethodProphecy = new MethodProphecy($classLoaderProphecy, 'findFile', [Argument::is('ClassName')]);
        $findFileMethodProphecy->willReturn(__DIR__ . '/does-not-exist');
        $classLoaderProphecy->addMethodProphecy($findFileMethodProphecy);

        /** @var ClassLoader $classLoader */
        $classLoader = $classLoaderProphecy->reveal();
        Override::apply($classLoader, ['ClassName' => []]);

        $this->assertTrue(true);
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function classMapDeclarationOrderProvider(): array
    {
        return [
            'class first' => [true],
            'namespace first' => [false],
        ];
    }

    #[DataProvider('classMapDeclarationOrderProvider')]
    public function testApplyClassMapIsIndependentOfDeclarationOrder(bool $classFirst)
    {
        $baseDir = __DIR__ . '/integration/src/TestClassMapOverride';
        $calculatorPath = $baseDir . '/Calculator.php';
        $geometryPath = $baseDir . '/SubNamespace/Geometry.php';

        $classLoaderProphecy = $this->prophesize(ClassLoader::class);
        $classLoaderProphecy->getApcuPrefix()->willReturn(null);
        $classLoaderProphecy->getPrefixesPsr4()->willReturn([]);
        $classLoaderProphecy->getClassMap()->willReturn([
            'My\\Integration\\TestClassMapOverride\\Calculator' => $calculatorPath,
            'My\\Integration\\TestClassMapOverride\\SubNamespace\\Geometry' => $geometryPath,
        ]);
        $classLoaderProphecy
            ->findFile('My\\Integration\\TestClassMapOverride\\Calculator')
            ->willReturn($calculatorPath);

        $classDeclaration = ['My\\Integration\\TestClassMapOverride\\Calculator' => ['cos' => 'ClassNs']];
        $namespaceDeclaration = ['My\\Integration\\TestClassMapOverride\\' => ['cos' => 'NsNs', 'sin' => 'NsNs']];

        /** @var ClassLoader $classLoader */
        $classLoader = $classLoaderProphecy->reveal();
        Override::apply(
            $classLoader,
            $classFirst
                ? $classDeclaration + $namespaceDeclaration
                : $namespaceDeclaration + $classDeclaration
        );

        // The class-specific declaration wins, the namespace declaration fills the gaps.
        $this->assertEquals(
            ['\\cos' => 'ClassNs\\cos', '\\sin' => 'NsNs\\sin'],
            Override::getFunctionCallMap($calculatorPath)
        );

        // Sub-namespaces are not affected by the namespace declaration.
        $this->assertEquals([], Override::getFunctionCallMap($geometryPath));
    }

    public function testGetFunctionCallMapOfNonExistentFile()
    {
        $this->assertEmpty(Override::getFunctionCallMap(__DIR__ . '/this-does-not-exist'));
    }

    public function testAddDirectoryFunctionCallMapOfNonExistentDirectory()
    {
        $reflectionMethod = new ReflectionMethod(Override::class, 'addDirectoryFunctionCallMap');
        $reflectionMethod->setAccessible(true);

        $autoloadCollection = new AutoloadCollection();
        $reflectionMethod->invoke(null, $autoloadCollection, __DIR__ . '/this-does-not-exist', []);
        $this->assertTrue(true);
    }

    public function testAddDirectoryFunctionCallMapMergeArray()
    {
        $reflectionProperty = new ReflectionProperty(Override::class, 'dirFunctionCallMap');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(
            null,
            [
                __DIR__ => ['\cos' => '1', '\tan' => '2']
            ]
        );

        $reflectionMethod = new ReflectionMethod(Override::class, 'addDirectoryFunctionCallMap');
        $reflectionMethod->setAccessible(true);

        $autoloadCollection = new AutoloadCollection();
        $reflectionMethod->invoke(null, $autoloadCollection, __DIR__, ['\sin' => '3', '\tan' => '4']);

        $propertyValue = $reflectionProperty->getValue();
        $this->assertArrayHasKey(__DIR__, $propertyValue);

        $this->assertEquals('1', $propertyValue[__DIR__]['\cos']);
        $this->assertEquals('3', $propertyValue[__DIR__]['\sin']);
        $this->assertEquals('4', $propertyValue[__DIR__]['\tan']);
    }
}
