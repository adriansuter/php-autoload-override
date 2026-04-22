<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\AutoloadCollection;
use AdrianSuter\Autoload\Override\ClosureHandler;
use AdrianSuter\Autoload\Override\CodeConverter;
use AdrianSuter\Autoload\Override\FileStreamWrapper;
use AdrianSuter\Autoload\Override\MockRegistry;
use AdrianSuter\Autoload\Override\Override;
use AdrianSuter\Autoload\Override\OverrideFactory;
use My\Integration\TestMockRegistry\Planet;
use My\Integration\TestMockRegistry\Star;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(Override::class)]
#[CoversClass(MockRegistry::class)]
#[UsesClass(AutoloadCollection::class)]
#[UsesClass(ClosureHandler::class)]
#[UsesClass(CodeConverter::class)]
#[UsesClass(FileStreamWrapper::class)]
#[UsesClass(OverrideFactory::class)]
class IntegrationMockRegistryTest extends AbstractIntegrationTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        MockRegistry::reset();
    }

    protected function getOverrideDeclarations(): array
    {
        return OverrideFactory::create()
            ->forClass(Planet::class, [
                'time' => \time(...),
                'rand' => \rand(...),
            ])
            ->forClass(Star::class, [
                'time' => \time(...),
            ])
            ->build();
    }

    public function testPerClassOverrideForTime(): void
    {
        MockRegistry::set(Planet::class, 'time', 1574333284);

        $planet = new Planet();
        $this->assertSame(1574333284, $planet->time());
    }

    public function testPerClassOverrideForRandWithLazyPattern(): void
    {
        MockRegistry::set(Planet::class, 'rand', 42);

        $planet = new Planet();
        $this->assertSame(42, $planet->rand(0, 100));
    }

    public function testRealRandIsCalledWhenNoOverrideIsSet(): void
    {
        // No MockRegistry entry — has() returns false, real \rand() is called.
        $planet = new Planet();
        $result = $planet->rand(10, 10);
        $this->assertSame(10, $result);
    }

    public function testGlobalFallbackAppliesToAllClasses(): void
    {
        MockRegistry::setGlobal('time', 9999);

        $planet = new Planet();
        $star = new Star();

        $this->assertSame(9999, $planet->time());
        $this->assertSame(9999, $star->time());
    }

    public function testPerClassTakesPriorityOverGlobal(): void
    {
        MockRegistry::setGlobal('time', 1000);
        MockRegistry::set(Planet::class, 'time', 2000);

        $planet = new Planet();
        $star = new Star();

        $this->assertSame(2000, $planet->time()); // per-class wins
        $this->assertSame(1000, $star->time());   // global fallback
    }

    public function testResetGlobalLeavesPerClassIntact(): void
    {
        MockRegistry::setGlobal('time', 1000);
        MockRegistry::set(Planet::class, 'time', 2000);

        MockRegistry::resetGlobal();

        $planet = new Planet();
        $star = new Star();

        $this->assertSame(2000, $planet->time());          // per-class intact
        $this->assertGreaterThan(0, $star->time());        // global gone → real \time()
        $this->assertNotSame(1000, $star->time());
    }

    public function testResetPerClassFallsBackToRealFunction(): void
    {
        MockRegistry::set(Planet::class, 'time', 1574333284);
        MockRegistry::reset(Planet::class);

        $planet = new Planet();
        $this->assertGreaterThanOrEqual(\time(), $planet->time());
    }
}
