<?php

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

    public function testClosureOverride()
    {
        $that = $this;

        Override::apply(self::$classLoader, [
            \My\Integration\TestClosureOverride\Clock::class => [
                'time' => function () {
                    return 99;
                }
            ],
            \My\Integration\TestClosureOverride\BigBen::class => [
                'date' => function ($format, $timestamp) use ($that) {
                    $that->assertEquals('h', $format);
                    $that->assertNotEquals(99, $timestamp);
                    $that->assertNotEquals(101, $timestamp);

                    return '6';
                }
            ],
            'My\\Integration\\TestClosureOverride\\' => [
                'date' => function ($format, $timestamp) {
                    return '_' . $format . '_' . $timestamp . '_';
                }
            ],
            'My\\Integration\\TestClosureOverride\\SubSpace\\' => [
                'time' => function () {
                    return 101;
                }
            ]
        ]);

        $clock = new \My\Integration\TestClosureOverride\Clock();
        $this->assertEquals(99, $clock->now());
        $this->assertEquals('_H_99_', $clock->hour());

        $bigBen = new \My\Integration\TestClosureOverride\BigBen();
        $this->assertEquals('******', $bigBen->hour());

        $digital = new \My\Integration\TestClosureOverride\SubSpace\Digital();
        $this->assertEquals('_i_101_', $digital->minute());
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
