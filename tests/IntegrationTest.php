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
