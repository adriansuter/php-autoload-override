<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\Autoload\Override\Override;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    private static $classLoader;

    public static function setUpBeforeClass()
    {
        self::$classLoader = require(__DIR__ . '/../vendor/autoload.php');
    }

    public function testNamespaceOverride()
    {
        Override::apply(self::$classLoader, [
            \My\Integration\TestNamespaceOverride\Moon::class => [
                // [1]
                'time'
            ],
            'My\\Integration\\TestNamespaceOverride\\' => [
                // [2]
                'substr'
            ],
        ]);

        require_once __DIR__ . '/assets/PHPAutoloadOverride.php';

        $moon = new \My\Integration\TestNamespaceOverride\Moon();

        $GLOBALS['time_return'] = 1;
        $this->assertEquals(1, $moon->now());

        $GLOBALS['time_return'] = 2;
        $this->assertEquals(2, $moon->nowUseAlias());

        $earth = new \My\Integration\TestNamespaceOverride\Earth();

        // The method calls a function that is defined in the namespace itself.
        $this->assertEquals('GFE', $earth->substrLocal());

        // The method calls the global \substr() function which has an override in [2].
        $GLOBALS['substr_return'] = 'XYZ';
        $this->assertEquals('XYZ', $earth->substrGlobal());

        // The method calls the global \time() function which has no override.
        $GLOBALS['time_return'] = 3;
        $this->assertGreaterThanOrEqual(\time(), $earth->now());
    }

    public function testCustomNamespaceOverride()
    {
        Override::apply(self::$classLoader, [
            \My\Integration\TestCustomNamespaceOverride\Hash::class => [
                'md5' => 'PHPCustomAutoloadOverride'
            ]
        ]);

        require_once __DIR__ . '/assets/PHPCustomAutoloadOverride.php';

        $hash = new \My\Integration\TestCustomNamespaceOverride\Hash();
        $GLOBALS['md5_return'] = '---';
        $this->assertEquals('---', $hash->hash('1'));
    }

    public function testClosureOverride()
    {
        $that = $this;

        Override::apply(self::$classLoader, [
            // Test that the class loader can find the file to that class.
            \My\Integration\TestClosureOverride\Clock::class => [
                // [1]
                'time' => function () {
                    return 99;
                }
            ],
            // Test that the class loader can find the file to that class.
            \My\Integration\TestClosureOverride\BigBen::class => [
                // [2]
                'date' => function ($format, $timestamp) use ($that) {
                    $that->assertEquals('h', $format);
                    $that->assertNotEquals(99, $timestamp);
                    $that->assertNotEquals(101, $timestamp);

                    return '6';
                }
            ],
            // Test that Override finds PSR-4 directory belonging to the namespace "My\Integration" and from there the
            // directory to the "TestClosureOverride" sub namespace.
            'My\\Integration\\TestClosureOverride\\' => [
                // [3]
                'date' => function ($format, $timestamp) {
                    return '_' . $format . '_' . $timestamp . '_';
                }
            ],
            // Test that Override finds PSR-4 directory belonging to the namespace "My\Integration" and from there the
            // directory to the "TestClosureOverride\SubSpace" sub namespace.
            'My\\Integration\\TestClosureOverride\\SubSpace\\' => [
                // [4]
                'time' => function () {
                    return 101;
                }
            ]
        ]);

        // Override \time() [1] and \date() [3].
        $clock = new \My\Integration\TestClosureOverride\Clock();
        $this->assertEquals(99, $clock->now());
        $this->assertEquals('_H_99_', $clock->hour());

        // Override \date() [2, not 3, not 4].
        $bigBen = new \My\Integration\TestClosureOverride\BigBen();
        $this->assertEquals('******', $bigBen->hour());

        // Override \date() [3] and \time() [4].
        $digital = new \My\Integration\TestClosureOverride\SubSpace\Digital();
        $this->assertEquals('_i_101_', $digital->minute());

        // Override \date() [3] but not time().
        $mercury = new \My\Integration\TestClosureOverride\Solar\Mercury();
        $this->assertRegExp('/_d.m.Y_\d{3}\d+_/', $mercury->now());
    }

    public function testDouble()
    {
        Override::apply(self::$classLoader, [
            \AdrianSuter\Autoload\Override\Science::class => [
                'str_repeat' => function ($str, $multiplier) {
                    return \str_repeat($str, 2 * $multiplier);
                }
            ],
            'AdrianSuter\\Autoload\\Override\\SubSpace\\' => [
                'str_repeat' => function ($input, $multiplier) {
                    return ':';
                }
            ],
        ]);

        $science = new \AdrianSuter\Autoload\Override\Science();
        $this->assertEquals('xxxx', $science->crosses(2));

        $speech = new \AdrianSuter\Autoload\Override\SubSpace\Speech();
        $this->assertEquals(':', $speech->whisper(2));
    }
}
