<?php
/**
 * Root Namespaced Function Call Override (https://github.com/adriansuter/php-rnfc-override)
 *
 * @license https://github.com/adriansuter/php-rnfc-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\AutoloadOverride\RNFCConverter;
use PHPUnit\Framework\TestCase;

class RNFCConverterTest extends TestCase
{
    public function testA()
    {
        $converter = new RNFCConverter();

        $this->assertEquals(
            '<?php echo \foo\rand(0, 9);',
            $converter->convert('<?php echo \rand(0, 9);', ['\rand' => 'foo\rand'])
        );
    }
}
