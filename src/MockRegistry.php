<?php

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

/**
 * Registry for mock values used in Override closures.
 *
 * Replaces the $GLOBALS anti-pattern with a scoped, type-safe alternative.
 * Per-class overrides take priority over global fallbacks.
 *
 * Usage in bootstrap:
 *
 *   Override::apply($classLoader, [
 *       Clock::class => [
 *           'time' => function (): int {
 *               return MockRegistry::get(Clock::class, 'time', \time());
 *           }
 *       ]
 *   ]);
 *
 * Usage in test:
 *
 *   MockRegistry::set(Clock::class, 'time', 1574333284);
 *   // ...assert...
 *
 *   // in tearDown():
 *   MockRegistry::reset(Clock::class);
 */
final class MockRegistry
{
    /** @var array<string, mixed> */
    private static array $global = [];

    /** @var array<string, array<string, mixed>> */
    private static array $perClass = [];

    /**
     * Set a per-class override value.
     *
     * @param class-string $className
     */
    public static function set(string $className, string $functionName, mixed $value): void
    {
        self::$perClass[$className][$functionName] = $value;
    }

    /**
     * Set a global fallback override (applies to all classes).
     */
    public static function setGlobal(string $functionName, mixed $value): void
    {
        self::$global[$functionName] = $value;
    }

    /**
     * Get an override value.
     *
     * Resolution order: per-class → global → $default.
     *
     * Note: $default is evaluated eagerly. Use has() + get() separately
     * if the fallback callable has side effects (e.g. an actual \rand() call).
     *
     * @param class-string $className
     */
    public static function get(string $className, string $functionName, mixed $default = null): mixed
    {
        return self::$perClass[$className][$functionName]
            ?? self::$global[$functionName]
            ?? $default;
    }

    /**
     * Check whether an override exists for the given class and function name.
     *
     * Checks per-class registry first, then global. Use this before get()
     * when the fallback has side effects.
     *
     * @param class-string $className
     */
    public static function has(string $className, string $functionName): bool
    {
        return array_key_exists($functionName, self::$perClass[$className] ?? [])
            || array_key_exists($functionName, self::$global);
    }

    /**
     * Reset overrides.
     *
     * Without argument: resets everything (per-class and global).
     * With class name:  resets only overrides for that class.
     *
     * @param class-string|null $className
     */
    public static function reset(?string $className = null): void
    {
        if ($className === null) {
            self::$global = [];
            self::$perClass = [];
        } else {
            unset(self::$perClass[$className]);
        }
    }

    /**
     * Reset only global overrides, leaving all per-class overrides intact.
     */
    public static function resetGlobal(): void
    {
        self::$global = [];
    }

    /**
     * Generate Override-ready closures for a class, backed by MockRegistry.
     *
     * Each closure uses the lazy pattern: the fallback is only invoked when
     * no MockRegistry override is set for that class and function name.
     * Pass real implementations as first-class callables:
     *
     *   MockRegistry::closures(Planet::class, [
     *       'time' => \time(...),
     *       'rand' => \rand(...),
     *   ])
     *
     * Typically called internally by OverrideFactory — use that for bootstrap
     * scripts; call this directly only when you need the closures array without
     * the full builder API.
     *
     * @param class-string $className
     * @param array<string, callable> $fallbacks function name => real implementation
     * @return array<string, \Closure>
     */
    public static function closures(string $className, array $fallbacks): array
    {
        $closures = [];
        foreach ($fallbacks as $functionName => $fallback) {
            $closures[$functionName] = static function () use ($className, $functionName, $fallback) {
                if (MockRegistry::has($className, $functionName)) {
                    return MockRegistry::get($className, $functionName);
                }
                return $fallback(...func_get_args());
            };
        }
        return $closures;
    }

    private function __construct()
    {
    }
}
