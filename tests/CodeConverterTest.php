<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\CodeConverter;
use PHPUnit\Framework\TestCase;

class CodeConverterTest extends TestCase
{
    public function testA()
    {
        $converter = new CodeConverter();
        $this->assertEquals(
            '<?php echo \foo\rand(0, 9);',
            $converter->convert('<?php echo \rand(0, 9);', ['\rand' => 'foo\rand'])
        );
    }
}
