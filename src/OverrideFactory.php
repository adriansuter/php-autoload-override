<?php

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

use Composer\Autoload\ClassLoader;

/**
 * Fluent builder for Override declarations using MockRegistry closures.
 *
 * Keeps bootstrap scripts concise by eliminating repeated class names and
 * manual closure boilerplate. The real function implementations are passed
 * as first-class callables; MockRegistry::closures() wraps them in the
 * lazy pattern automatically.
 *
 * Usage:
 *
 *   OverrideFactory::create()
 *       ->forClass(Planet::class, ['time' => \time(...), 'rand' => \rand(...)])
 *       ->forClass(Star::class,   ['time' => \time(...)])
 *       ->apply($classLoader);
 *
 * Or if you need the raw declarations array (e.g. for AbstractIntegrationTestCase):
 *
 *   return OverrideFactory::create()
 *       ->forClass(Planet::class, ['time' => \time(...)])
 *       ->build();
 */
final class OverrideFactory
{
    /** @var array<string, array<string, \Closure>> */
    private array $declarations = [];

    private function __construct()
    {
    }

    public static function create(): static
    {
        return new static();
    }

    /**
     * Register MockRegistry-backed overrides for a single class.
     *
     * Each entry in $fallbacks maps a function name to its real implementation,
     * typically as a first-class callable (e.g. \time(...), \rand(...)).
     *
     * @param class-string $className
     * @param array<string, callable> $fallbacks function name => real implementation
     */
    public function forClass(string $className, array $fallbacks): static
    {
        $this->declarations[$className] = MockRegistry::closures($className, $fallbacks);
        return $this;
    }

    /**
     * Return the assembled declarations array for use with Override::apply().
     *
     * @return array<string, array<string, \Closure>>
     */
    public function build(): array
    {
        return $this->declarations;
    }

    /**
     * Apply all registered overrides directly.
     *
     * Shorthand for Override::apply($classLoader, $this->build()).
     *
     * @codeCoverageIgnore Delegation to Override::apply() and build(), both of which are tested independently.
     */
    public function apply(ClassLoader $classLoader): void
    {
        Override::apply($classLoader, $this->build());
    }
}
