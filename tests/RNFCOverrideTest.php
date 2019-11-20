<?php
/**
 * Root Namespaced Function Call Override (https://github.com/adriansuter/php-rnfc-override)
 *
 * @license https://github.com/adriansuter/php-rnfc-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\AutoloadOverride\RNFCConverter;
use AdrianSuter\AutoloadOverride\RNFCOverride;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class RNFCOverrideTest extends TestCase
{
    public function testSetFQFCConverter()
    {
        $converter = $this->createMock(RNFCConverter::class);

        RNFCOverride::setFQFCConverter($converter);
        $this->assertEquals($converter, RNFCOverride::getFQFCConverter());
    }

    public function testGetFQFCConverter()
    {
        $this->assertInstanceOf(
            RNFCConverter::class, RNFCOverride::getFQFCConverter()
        );
    }
}
