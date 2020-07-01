<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

class IntegrationNamespaceTest extends AbstractIntegrationTest
{
    public static function setUpBeforeClass(): void
    {
        require_once(__DIR__ . '/assets/PHPAutoloadOverride.php');
    }

    protected function getOverrideDeclarations(): array
    {
        return [
            \My\Integration\TestNamespaceOverride\Moon::class => [
                'time'
            ],
            'My\\Integration\\TestNamespaceOverride\\' => [
                'substr'
            ],
        ];
    }

    public function testEarth()
    {
        $earth = new \My\Integration\TestNamespaceOverride\Earth();
// Calls substr() which is a local function in the namespace.
        $this->assertEquals('GFE', $earth->substrLocal());
// Calls \substr() > Overridden by FQNS-declaration.
        $GLOBALS['substr_return'] = 'XYZ';
        $this->assertEquals('XYZ', $earth->substrGlobal());
// Calls \time() > No override.
        $GLOBALS['time_return'] = 3;
        $this->assertGreaterThanOrEqual(\time(), $earth->time());
    }

    public function testMoon()
    {
        $moon = new \My\Integration\TestNamespaceOverride\Moon();
// Calls \time() > Overridden by FQCN.
        $GLOBALS['time_return'] = 1;
        $this->assertEquals(1, $moon->time());
// Calls \time()-alias > Overridden by FQCN.
        $GLOBALS['time_return'] = 2;
        $this->assertEquals(2, $moon->timeUseAlias());
// Calls \substr() > Overridden by FQNS-declaration.
        $GLOBALS['substr_return'] = 'ZZZ';
        $this->assertEquals('ZZZ', $moon->substr('AAA', 0, 2));
    }
}
