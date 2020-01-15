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
    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        require_once(__DIR__ . '/assets/PHPAutoloadOverride.php');
    }

    /**
     * @inheritDoc
     */
    protected function getOverrideDeclarations(): array
    {
        return [
            \My\Integration\TestNamespaceOverride\Moon::class => [
                // (C1)
                'time'
            ],
            'My\\Integration\\TestNamespaceOverride\\' => [
                // (N2)
                'substr'
            ],
        ];
    }

    public function testEarth()
    {
        $earth = new \My\Integration\TestNamespaceOverride\Earth();

        // Calls substr() which is a local function in the namespace.
        $this->assertEquals('GFE', $earth->substrLocal());

        // Calls \substr() > Overridden by declaration (N2).
        $GLOBALS['substr_return'] = 'XYZ';
        $this->assertEquals('XYZ', $earth->substrGlobal());

        // Calls \time() > No override.
        $GLOBALS['time_return'] = 3;
        $this->assertGreaterThanOrEqual(\time(), $earth->time());
    }

    public function testMoon()
    {
        $moon = new \My\Integration\TestNamespaceOverride\Moon();

        // Calls \time() > Overridden by declaration (C1).
        $GLOBALS['time_return'] = 1;
        $this->assertEquals(1, $moon->time());

        // Calls \time()-alias > Overridden by declaration (C1).
        $GLOBALS['time_return'] = 2;
        $this->assertEquals(2, $moon->timeUseAlias());

        // Calls \substr() > Overridden by declaration (N2).
        $GLOBALS['substr_return'] = 'ZYX';
        $this->assertEquals('ZYX', $moon->substr('ABC', 0, 2));

        // Calls \substr() > Overridden by declaration (N2), but as
        // $GLOBALS['substr_return'] had been unset in the previous call, the
        // override itself calls the root namespaced substr() function.
        $this->assertEquals('AB', $moon->substr('ABC', 0, 2));
    }
}
