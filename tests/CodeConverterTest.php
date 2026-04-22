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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(CodeConverter::class)]
class CodeConverterTest extends TestCase
{
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

        $parser = $this->createMock(Parser::class);
        $parser->method('parse')->willReturn(null);

        $converter = new CodeConverter($parser);
        $converter->convert('<?php echo "1";', []);
    }

    public function testContinueBranchWhenNotFullyQualified(): void
    {
        $code = <<<'PHP'
<?php
$func = 'rand';
echo $func(1, 2);
PHP;

        $converter = new CodeConverter();
        $result = $converter->convert($code, ['\rand' => 'foo\rand']);

        $this->assertStringContainsString('$func(1, 2)', $result);
    }

    public function testReturnOriginalCodeWhenNoOverrides(): void
    {
        $code = '<?php echo strlen("abc");';

        $converter = new CodeConverter();
        $result = $converter->convert($code, ['\rand' => 'foo\rand']);

        $this->assertSame($code, $result);
    }
}
