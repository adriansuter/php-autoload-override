<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

class IntegrationClosureTest extends AbstractIntegrationTest
{
    protected function getOverrideDeclarations(): array
    {
        return [
            // Test that the class loader can find the file to that class.
            \My\Integration\TestClosureOverride\Clock::class => [
                'time' => function () {

                    return 100;
                },
                'rand' => function (int $min, int $max): int {

                    return $min + $max;
                }
            ],
            \My\Integration\TestClosureOverride\SubSpace\Digital::class => [
                'rand' => function (int $min, int $max): int {

                    return 2 * ($min + $max);
                }
            ],
            // Test that Override finds PSR-4 directory belonging to the namespace "My\Integration" and from there the
            // directory to the "TestClosureOverride" sub namespace.
            'My\\Integration\\TestClosureOverride\\' => [
                'rand' => function (int $min, int $max): int {

                    return 3 * ($min + $max);
                }
            ],
            // Test that Override finds PSR-4 directory belonging to the namespace "My\Integration" and from there the
            // directory to the "TestClosureOverride\SubSpace" sub namespace.
            'My\\Integration\\TestClosureOverride\\SubSpace\\' => [
                'time' => function () {

                    return 101;
                }
            ],
            \My\Integration\TestClosureOverride\OtherSpace\Other::class => [
                'time' => function (): int {

                    return 102;
                }
            ]
        ];
    }

    public function testClock()
    {
        $clock = new \My\Integration\TestClosureOverride\Clock();
// Calls \time() > Overridden by FQCN-declaration.
        $this->assertEquals(100, $clock->time());
// Calls \time()-alias > Overridden by FQCN-declaration.
        $this->assertEquals(100, $clock->timeWithAlias());
// Calls \rand() > Overridden by FQCN-declaration.
        $this->assertEquals(11, $clock->rand(1, 10));
    }

    public function testSubClock()
    {
        $subClock = new \My\Integration\TestClosureOverride\SubClock();
// Calls \time() > No override.
        $this->assertGreaterThanOrEqual(\time(), $subClock->time());
// Parent > Calls \time()-alias > Overridden by FQCN-declaration.
        $this->assertEquals(100, $subClock->timeWithAlias());
// Calls parent > Calls \rand() > Overridden by FQCN-declaration.
        $this->assertEquals(9, $subClock->rand(3, 6));
    }

    public function testDigital()
    {
        $digital = new \My\Integration\TestClosureOverride\SubSpace\Digital();
// Calls \time() > Overridden by FQNS-declaration.
        $this->assertEquals(101, $digital->time());
// Calls \rand() > Overridden by FQCN-declaration.
        $this->assertEquals(22, $digital->rand(1, 10));
    }

    public function testSubDigital()
    {
        $subDigital = new \My\Integration\TestClosureOverride\SubDigital();
// Parent > Calls \time() > Overridden by FQNS-declaration.
        $this->assertEquals(101, $subDigital->time());
// Calls \time() > No override.
        $this->assertGreaterThanOrEqual(\time(), $subDigital->subTime());
// Calls \rand() > Overridden by FQNS.
        $this->assertEquals(33, $subDigital->rand(1, 10));
    }

    public function testSubSpaceClock()
    {
        $subSpaceClock = new \My\Integration\TestClosureOverride\SubSpace\SubSpaceClock();
// Calls \time() > Overridden by FQNS.
        $this->assertEquals(101, $subSpaceClock->time());
// Parent > Calls \time()-alias > Overridden by FQCN-declaration.
        $this->assertEquals(100, $subSpaceClock->timeWithAlias());
// Calls \rand() > No override.
        $rand = $subSpaceClock->rand(1, 10);
        $this->assertGreaterThanOrEqual(1, $rand);
        $this->assertLessThanOrEqual(10, $rand);
    }

    public function testOther()
    {
        $other = new \My\Integration\TestClosureOverride\OtherSpace\Other();
// Calls \time() > Overridden by FQCN-declaration.
        $this->assertEquals(102, $other->time());
// Calls \rand() > No override.
        $rand = $other->rand(1, 10);
        $this->assertGreaterThanOrEqual(1, $rand);
        $this->assertLessThanOrEqual(10, $rand);
    }

    public function testSpace()
    {
        $space = new \My\Integration\TestClosureOverride\OtherSpace\Space();
// Calls \time() > No override.
        $this->assertGreaterThanOrEqual(\time(), $space->time());
// Calls time() which is a local function in the namespace.
        $this->assertEquals(105, $space->timeLocal());
    }
}
