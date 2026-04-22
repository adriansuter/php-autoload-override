<?php

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\MockRegistry;
use AdrianSuter\Autoload\Override\OverrideFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OverrideFactory::class)]
#[UsesClass(MockRegistry::class)]
final class OverrideFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        MockRegistry::reset();
    }

    // -------------------------------------------------------------------------
    // create / instantiation guard
    // -------------------------------------------------------------------------

    #[Test]
    public function createReturnsFactoryInstance(): void
    {
        $this->assertInstanceOf(OverrideFactory::class, OverrideFactory::create());
    }

    #[Test]
    public function cannotBeInstantiated(): void
    {
        $reflection = new \ReflectionClass(OverrideFactory::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());
    }

    // -------------------------------------------------------------------------
    // forClass / fluent interface
    // -------------------------------------------------------------------------

    #[Test]
    public function forClassReturnsSameInstance(): void
    {
        $factory = OverrideFactory::create();

        $this->assertSame($factory, $factory->forClass('My\App\Clock', ['time' => \time(...)]));
    }

    #[Test]
    public function forClassCanBeChained(): void
    {
        $declarations = OverrideFactory::create()
            ->forClass('My\App\Clock', ['time' => \time(...)])
            ->forClass('My\App\Logger', ['time' => \time(...)])
            ->build();

        $this->assertArrayHasKey('My\App\Clock', $declarations);
        $this->assertArrayHasKey('My\App\Logger', $declarations);
    }

    #[Test]
    public function forClassOverwritesPreviousDeclarationForSameClass(): void
    {
        $declarations = OverrideFactory::create()
            ->forClass('My\App\Clock', ['time' => \time(...)])
            ->forClass('My\App\Clock', ['rand' => \rand(...)])
            ->build();

        // Second call replaces first — only 'rand' remains for this class.
        $this->assertArrayNotHasKey('time', $declarations['My\App\Clock']);
        $this->assertArrayHasKey('rand', $declarations['My\App\Clock']);
    }

    // -------------------------------------------------------------------------
    // build
    // -------------------------------------------------------------------------

    #[Test]
    public function buildReturnsEmptyArrayWhenNoClassRegistered(): void
    {
        $this->assertSame([], OverrideFactory::create()->build());
    }

    #[Test]
    public function buildReturnsClosurePerFunctionName(): void
    {
        $declarations = OverrideFactory::create()
            ->forClass('My\App\Clock', ['time' => \time(...), 'rand' => \rand(...)])
            ->build();

        $this->assertArrayHasKey('time', $declarations['My\App\Clock']);
        $this->assertArrayHasKey('rand', $declarations['My\App\Clock']);
        $this->assertInstanceOf(\Closure::class, $declarations['My\App\Clock']['time']);
        $this->assertInstanceOf(\Closure::class, $declarations['My\App\Clock']['rand']);
    }

    // -------------------------------------------------------------------------
    // Generated closure behaviour (lazy pattern)
    // -------------------------------------------------------------------------

    #[Test]
    public function generatedClosureCallsFallbackWhenNoOverrideIsSet(): void
    {
        $declarations = OverrideFactory::create()
            ->forClass('My\App\Clock', ['time' => fn() => 1574333284])
            ->build();

        $closure = $declarations['My\App\Clock']['time'];

        // No MockRegistry entry -> fallback is called -> returns 1574333284.
        $this->assertSame(1574333284, $closure());
    }

    #[Test]
    public function generatedClosureReturnsMockRegistryValueWhenOverrideIsSet(): void
    {
        MockRegistry::set('My\App\Clock', 'time', 9999);

        $declarations = OverrideFactory::create()
            ->forClass('My\App\Clock', ['time' => fn() => 1574333284])
            ->build();

        $closure = $declarations['My\App\Clock']['time'];

        // MockRegistry entry present -> fallback is NOT called.
        $this->assertSame(9999, $closure());
    }

    #[Test]
    public function generatedClosureForwardsFallbackArguments(): void
    {
        $declarations = OverrideFactory::create()
            ->forClass('My\App\Probability', ['rand' => fn(int $min, int $max) => $min + $max])
            ->build();

        $closure = $declarations['My\App\Probability']['rand'];

        // No MockRegistry entry -> fallback receives (3, 7) -> returns 10.
        $this->assertSame(10, $closure(3, 7));
    }

    #[Test]
    public function generatedClosureIgnoresFallbackArgumentsWhenOverrideIsSet(): void
    {
        MockRegistry::set('My\App\Probability', 'rand', 42);

        $declarations = OverrideFactory::create()
            ->forClass('My\App\Probability', ['rand' => fn(int $min, int $max) => $min + $max])
            ->build();

        $closure = $declarations['My\App\Probability']['rand'];

        // MockRegistry entry present -> fixed value, arguments irrelevant.
        $this->assertSame(42, $closure(3, 7));
    }

    #[Test]
    public function generatedClosuresForDifferentClassesAreIndependent(): void
    {
        MockRegistry::set('My\App\Clock', 'time', 1000);
        // No entry for Logger.

        $declarations = OverrideFactory::create()
            ->forClass('My\App\Clock', ['time' => fn() => 0])
            ->forClass('My\App\Logger', ['time' => fn() => 0])
            ->build();

        $this->assertSame(1000, $declarations['My\App\Clock']['time']());
        $this->assertSame(0, $declarations['My\App\Logger']['time']());
    }
}
