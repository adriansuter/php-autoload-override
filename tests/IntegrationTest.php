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
                'time'
            ],
            'My\\Integration\\TestNamespaceOverride\\' => [
                'substr'
            ],
        ]);

        require_once __DIR__ . '/assets/PHPAutoloadOverride.php';

        $moon = new \My\Integration\TestNamespaceOverride\Moon();
        $earth = new \My\Integration\TestNamespaceOverride\Earth();

        $this->setName(__FUNCTION__ . ' (FQCN defined override to default NS)');
        $GLOBALS['time_return'] = 1;
        $this->assertEquals(1, $moon->now());

        $this->setName(__FUNCTION__ . ' (FQCN defined override to default NS, function call uses an alias)');
        $GLOBALS['time_return'] = 2;
        $this->assertEquals(2, $moon->nowUseAlias());

        $this->setName(__FUNCTION__ . ' (FQNS defined override to default NS, but function call uses a local function)');
        $this->assertEquals('GFE', $earth->substrLocal());

        $this->setName(__FUNCTION__ . ' (FQNS defined override to default NS)');
        $GLOBALS['substr_return'] = 'XYZ';
        $this->assertEquals('XYZ', $earth->substrGlobal());

        $this->setName(__FUNCTION__ . ' (No override)');
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

        $this->setName(__FUNCTION__ . ' (No override)');
        $mercury = new \My\Integration\TestClosureOverride\Solar\Mercury();
        $this->assertEquals(\date('d.m.Y', \time()), $mercury->now('d.m.Y'));
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

    public function testA()
    {
        Override::apply(self::$classLoader, [
            \My\Integration\TestClassMapOverride\Calculator::class => [
                'cos' => function (float $arg): float {
                    return \sin($arg);
                },
            ],
            'My\\Integration\\TestClassMapOverride\\' => [
                'cos' => function (float $arg): float {
                    return $arg * 2;
                },
            ]
        ]);

        $calculator = new \My\Integration\TestClassMapOverride\Calculator();
        $this->assertEquals(\sin(\pi() / 2), $calculator->cos(\pi() / 2));

        $geometry = new \My\Integration\TestClassMapOverride\SubNamespace\Geometry();
        $this->assertEquals(1, $geometry->cos(0.5));

        $otherCalculator = new \My\Integration\TestClassMapOverride\OtherCalculator();
        $this->assertEquals(2, $otherCalculator->cos(1));
    }
}
