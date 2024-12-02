<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\CodeConverter;
use PhpParser\Parser;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\MethodProphecy;
use RuntimeException;

class CodeConverterTest extends TestCase
{
    use ProphecyTrait;

    public function testConvert(): void
    {
        $converter = new CodeConverter();
        $this->assertEquals(
            '<?php echo \foo\rand(0, 9);',
            $converter->convert('<?php echo \rand(0, 9);', ['\rand' => 'foo\rand'])
        );
    }

    public function testExceptionIfParserReturnsNull(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Code could not be parsed.');

        $parserProphecy = $this->prophesize(Parser::class);

        $parseProphecy = new MethodProphecy($parserProphecy, 'parse', [Argument::any()]);
        $parseProphecy->willReturn(null);

        /** @var Parser $parser */
        $parser = $parserProphecy->reveal();

        $converter = new CodeConverter($parser);
        $converter->convert('<?php echo "1";', []);
    }
}
