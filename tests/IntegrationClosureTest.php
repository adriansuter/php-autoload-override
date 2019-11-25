<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

class IntegrationClosureTest extends AbstractIntegrationTest
{
    protected function getOverrideDeclarations(): array
    {
        $that = $this;

        return [
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
            ],
            \My\Integration\TestClosureOverride\Solar\Saturn::class => [
                'rand' => function (int $min, int $max): int {
                    return $min + $max;
                }
            ]
        ];
    }

    public function test1()
    {
        // Override \time() [1] and \date() [3].
        $clock = new \My\Integration\TestClosureOverride\Clock();
        $this->assertEquals(99, $clock->now());
        $this->assertEquals('_H_99_', $clock->hour());
    }

    public function test2()
    {
        // Override \date() [2, not 3, not 4].
        $bigBen = new \My\Integration\TestClosureOverride\BigBen();
        $this->assertEquals('******', $bigBen->hour());

        // Override \date() [3] but not \time().
        $digital = new \My\Integration\TestClosureOverride\SubSpace\Digital();
        $this->assertEquals('01.01.1970 00:01:41', $digital->now());

        $this->setName(__FUNCTION__ . ' (No override)');
        $mercury = new \My\Integration\TestClosureOverride\Solar\Mercury();
        $this->assertEquals(\date('d.m.Y', \time()), $mercury->now('d.m.Y'));

        $this->setName(__FUNCTION__ . ' (Override of \rand(), but not of others as this class is in a sub namespace)');
        $saturn = new \My\Integration\TestClosureOverride\Solar\Saturn();
        $this->assertEquals(\date('d.m.Y', \time()), $saturn->now('d.m.Y'));
        $this->assertEquals(10, $saturn->rand(1, 9));
    }

}
