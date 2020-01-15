<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\CodeConverter;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use ReflectionProperty;
use RuntimeException;

class CodeConverterTest extends TestCase
{
    public function testConvert()
    {
        $converter = new CodeConverter();
        $this->assertEquals(
            '<?php echo \foo\rand(0, 9);',
            $converter->convert('<?php echo \rand(0, 9);', ['\rand' => 'foo\rand'])
        );
    }

    public function testEmptyFunctionCallMap()
    {
        $converter = new CodeConverter();
        $this->assertEquals(
            '<?php echo \foo\rand(0, 9);',
            $converter->convert('<?php echo \foo\rand(0, 9);', [])
        );
    }

    public function testConvertIfFuncCallNameHasNoResolvedNameAttribute()
    {
        $nameProphecy = $this->prophesize(Name::class);
        $nameHasAttributeMethodProphecy = new MethodProphecy($nameProphecy, 'hasAttribute', [Argument::any()]);
        $nameHasAttributeMethodProphecy->willReturn(false);
        $nameProphecy->addMethodProphecy($nameHasAttributeMethodProphecy);

        $funcCallMock = $this->createMock(FuncCall::class);
        $nameReflectionProperty = new ReflectionProperty(FuncCall::class, 'name');
        $nameReflectionProperty->setValue($funcCallMock, $nameProphecy->reveal());

        $nodeFinderProphecy = $this->prophesize(NodeFinder::class);

        $nodeFinderFindInstanceOfMethodProphecy = new MethodProphecy(
            $nodeFinderProphecy,
            'findInstanceOf',
            [Argument::any(), FuncCall::class]
        );
        $nodeFinderFindInstanceOfMethodProphecy->willReturn([$funcCallMock]);
        $nodeFinderProphecy->addMethodProphecy($nodeFinderFindInstanceOfMethodProphecy);

        /** @var NodeFinder $nodeFinder */
        $nodeFinder = $nodeFinderProphecy->reveal();
        $converter = new CodeConverter(null, null, null, null, $nodeFinder);

        $this->assertEquals(
            '<?php echo \time();',
            $converter->convert('<?php echo \time();', [])
        );
    }

    public function testInvalidCode()
    {
        $this->expectException(RuntimeException::class);
        $this->expectErrorMessage('Code Converter failed to parse the code.');

        $converter = new CodeConverter();
        $converter->convert('<?php Hello World', []);
    }
}
