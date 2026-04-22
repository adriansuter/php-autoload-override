<?php

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\MockRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MockRegistry::class)]
final class MockRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        MockRegistry::reset();
    }

    // -------------------------------------------------------------------------
    // set / get — per-class
    // -------------------------------------------------------------------------

    #[Test]
    public function getReturnsDefaultWhenNothingIsSet(): void
    {
        $this->assertSame(99, MockRegistry::get('Any\Class', 'time', 99));
    }

    #[Test]
    public function getReturnsNullDefaultWhenNothingIsSet(): void
    {
        $this->assertNull(MockRegistry::get('Any\Class', 'time'));
    }

    #[Test]
    public function setAndGetPerClass(): void
    {
        MockRegistry::set('My\App\Clock', 'time', 1574333284);

        $this->assertSame(1574333284, MockRegistry::get('My\App\Clock', 'time'));
    }

    #[Test]
    public function perClassOverrideDoesNotLeakToOtherClasses(): void
    {
        MockRegistry::set('My\App\Clock', 'time', 1574333284);

        $this->assertNull(MockRegistry::get('My\App\Logger', 'time'));
    }

    #[Test]
    public function perClassOverrideCanStoreNull(): void
    {
        MockRegistry::set('My\App\Clock', 'time', null);

        // has() must return true even when value is null
        $this->assertTrue(MockRegistry::has('My\App\Clock', 'time'));
    }

    #[Test]
    public function laterSetCallOverwritesPreviousValue(): void
    {
        MockRegistry::set('My\App\Clock', 'time', 100);
        MockRegistry::set('My\App\Clock', 'time', 200);

        $this->assertSame(200, MockRegistry::get('My\App\Clock', 'time'));
    }

    // -------------------------------------------------------------------------
    // setGlobal / get — global fallback
    // -------------------------------------------------------------------------

    #[Test]
    public function setGlobalAndGet(): void
    {
        MockRegistry::setGlobal('time', 9999);

        $this->assertSame(9999, MockRegistry::get('My\App\Clock', 'time'));
        $this->assertSame(9999, MockRegistry::get('My\App\Logger', 'time'));
    }

    #[Test]
    public function perClassTakesPriorityOverGlobal(): void
    {
        MockRegistry::setGlobal('time', 1000);
        MockRegistry::set('My\App\Clock', 'time', 2000);

        $this->assertSame(2000, MockRegistry::get('My\App\Clock', 'time'));
        // Other classes still fall back to global
        $this->assertSame(1000, MockRegistry::get('My\App\Logger', 'time'));
    }

    #[Test]
    public function globalFallsBackToDefaultWhenNotSet(): void
    {
        MockRegistry::setGlobal('rand', 42);

        $this->assertNull(MockRegistry::get('My\App\Clock', 'time'));
    }

    // -------------------------------------------------------------------------
    // has
    // -------------------------------------------------------------------------

    #[Test]
    public function hasReturnsFalseWhenNothingIsSet(): void
    {
        $this->assertFalse(MockRegistry::has('My\App\Clock', 'time'));
    }

    #[Test]
    public function hasReturnsTrueForPerClassOverride(): void
    {
        MockRegistry::set('My\App\Clock', 'time', 1574333284);

        $this->assertTrue(MockRegistry::has('My\App\Clock', 'time'));
    }

    #[Test]
    public function hasReturnsTrueForGlobalOverride(): void
    {
        MockRegistry::setGlobal('time', 1574333284);

        $this->assertTrue(MockRegistry::has('My\App\Clock', 'time'));
    }

    #[Test]
    public function hasReturnsFalseForOtherClassWhenOnlyPerClassIsSet(): void
    {
        MockRegistry::set('My\App\Clock', 'time', 1574333284);

        $this->assertFalse(MockRegistry::has('My\App\Logger', 'time'));
    }

    // -------------------------------------------------------------------------
    // reset (class-scoped)
    // -------------------------------------------------------------------------

    #[Test]
    public function resetClassRemovesOnlyThatClassOverrides(): void
    {
        MockRegistry::set('My\App\Clock', 'time', 100);
        MockRegistry::set('My\App\Logger', 'time', 200);

        MockRegistry::reset('My\App\Clock');

        $this->assertFalse(MockRegistry::has('My\App\Clock', 'time'));
        $this->assertTrue(MockRegistry::has('My\App\Logger', 'time'));
    }

    #[Test]
    public function resetClassDoesNotAffectGlobal(): void
    {
        MockRegistry::setGlobal('time', 9999);
        MockRegistry::set('My\App\Clock', 'time', 100);

        MockRegistry::reset('My\App\Clock');

        $this->assertTrue(MockRegistry::has('My\App\Clock', 'time')); // via global
        $this->assertSame(9999, MockRegistry::get('My\App\Clock', 'time'));
    }

    #[Test]
    public function resetClassOnUnknownClassDoesNotThrow(): void
    {
        // Must not throw even if the class was never registered
        MockRegistry::reset('Non\Existent\Class');
        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // reset (full)
    // -------------------------------------------------------------------------

    #[Test]
    public function resetAllClearsPerClassAndGlobal(): void
    {
        MockRegistry::set('My\App\Clock', 'time', 100);
        MockRegistry::setGlobal('rand', 42);

        MockRegistry::reset();

        $this->assertFalse(MockRegistry::has('My\App\Clock', 'time'));
        $this->assertFalse(MockRegistry::has('My\App\Clock', 'rand'));
    }

    // -------------------------------------------------------------------------
    // resetGlobal
    // -------------------------------------------------------------------------

    #[Test]
    public function resetGlobalClearsOnlyGlobal(): void
    {
        MockRegistry::setGlobal('time', 9999);
        MockRegistry::set('My\App\Clock', 'time', 100);

        MockRegistry::resetGlobal();

        $this->assertTrue(MockRegistry::has('My\App\Clock', 'time'));  // per-class intact
        $this->assertSame(100, MockRegistry::get('My\App\Clock', 'time'));

        // Global is gone — other class no longer sees it
        $this->assertFalse(MockRegistry::has('My\App\Logger', 'time'));
    }

    // -------------------------------------------------------------------------
    // Instantiation guard
    // -------------------------------------------------------------------------

    #[Test]
    public function cannotBeInstantiated(): void
    {
        $reflection = new \ReflectionClass(MockRegistry::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());
    }
}
