# PHP-Autoload-Override

[![Build Status](https://github.com/adriansuter/php-autoload-override/workflows/Tests/badge.svg?branch=master)](https://github.com/adriansuter/php-autoload-override/actions?query=branch:master)
[![Coverage Status](https://coveralls.io/repos/github/adriansuter/php-autoload-override/badge.svg?branch=master)](https://coveralls.io/github/adriansuter/php-autoload-override?branch=master)
[![Total Downloads](https://poser.pugx.org/adriansuter/php-autoload-override/downloads)](https://packagist.org/packages/adriansuter/php-autoload-override)
[![License](https://poser.pugx.org/adriansuter/php-autoload-override/license)](https://packagist.org/packages/adriansuter/php-autoload-override)

This library allows overriding fully qualified function calls inside your class methods in order to
be able to mock them during testing.

**NOTE: The library can be used for other scenarios as well. But we recommend using it for testing purposes
only.**

[PHP-Autoload-Override Website](https://adriansuter.github.io/php-autoload-override/)

## Requirements

- PHP 8.2 or later
- Composer with PSR-4 (PSR-0 is not supported)

## Installation

```bash
composer require --dev adriansuter/php-autoload-override ^2.0
```

## Usage with [PHPUnit](https://phpunit.de/)

Say we want to unit test the following class `Probability`.

```php
namespace My\App;

class Probability
{
    public function pick(int $probability, string $color1, string $color2): string
    {
        if (\rand(1, 100) <= $probability) {
            return $color1;
        } else {
            return $color2;
        }
    }
}
```

The class uses `\rand()` from the global scope. Because we cannot control its output, we cannot
test `pick()` deterministically — until we override it.

### Setting up the bootstrap

Open the [bootstrap script](https://phpunit.readthedocs.io/en/latest/configuration.html#the-bootstrap-attribute)
of your test suite and register the override. The recommended approach uses `OverrideFactory`:

```php
// tests/bootstrap.php

use AdrianSuter\Autoload\Override\OverrideFactory;
use My\App\Probability;

/** @var \Composer\Autoload\ClassLoader $classLoader */
$classLoader = require_once __DIR__ . '/../vendor/autoload.php';

OverrideFactory::create()
    ->forClass(Probability::class, ['rand' => \rand(...)])
    ->apply($classLoader);
```

Each entry in `forClass()` maps a function name to its real implementation, written as a
[first-class callable](https://www.php.net/manual/en/functions.first_class_callable_syntax.php).
`OverrideFactory` generates the override closure automatically: when a test sets a mock value via
`MockRegistry::set()`, that value is returned; otherwise the real `\rand()` is called. No mock
value is registered initially, so non-test code is unaffected.

For multiple classes, chain `forClass()` calls:

```php
OverrideFactory::create()
    ->forClass(Clock::class,       ['time' => \time(...)])
    ->forClass(Probability::class, ['rand' => \rand(...)])
    ->apply($classLoader);
```

If you need the raw declarations array instead (e.g. for an `AbstractIntegrationTestCase`),
use `build()` instead of `apply()`:

```php
protected function getOverrideDeclarations(): array
{
    return OverrideFactory::create()
        ->forClass(Probability::class, ['rand' => \rand(...)])
        ->build();
}
```

### Writing the test

Set a mock value with `MockRegistry::set()` before calling the code under test, and reset it in
`tearDown()` so it does not affect other tests:

```php
namespace My\App\Tests;

use AdrianSuter\Autoload\Override\MockRegistry;
use My\App\Probability;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProbabilityTest extends TestCase
{
    protected function tearDown(): void
    {
        MockRegistry::reset(Probability::class);
    }

    #[Test]
    public function pickReturnsSecondColorWhenRandExceedsProbability(): void
    {
        MockRegistry::set(Probability::class, 'rand', 35);

        $p = new Probability();
        $this->assertSame('blue', $p->pick(34, 'red', 'blue'));
    }

    #[Test]
    public function pickReturnsFirstColorWhenRandMeetsProbability(): void
    {
        MockRegistry::set(Probability::class, 'rand', 35);

        $p = new Probability();
        $this->assertSame('red', $p->pick(35, 'red', 'blue'));
    }
}
```

`MockRegistry::reset(Probability::class)` clears only the overrides for that class. Call
`MockRegistry::reset()` without arguments to clear all registered overrides at once.

Note that these overrides are only applied during the unit tests.

### Sharing an override across multiple classes

`MockRegistry::set()` registers an override for one specific class. To register a fallback that
applies to every class, use `setGlobal()`:

```php
MockRegistry::setGlobal('time', 1574333284);
```

If a class also has a per-class override for the same function, the per-class value takes
priority. Reset only the global overrides with `MockRegistry::resetGlobal()`.

### Using `Override::apply()` directly

If you register overrides via `Override::apply()` directly rather than using `OverrideFactory`,
you write the closure yourself. `MockRegistry::get()` takes three arguments: the class name, the
function name, and a default that is returned when no mock is registered:

```php
Override::apply($classLoader, [
    Probability::class => [
        'rand' => function (int $min, int $max): int {
            return MockRegistry::get(Probability::class, 'rand', \rand($min, $max));
        }
    ]
]);
```

Be aware that the third argument — `\rand($min, $max)` — is evaluated on every call, even when a
mock value is set. This is harmless for `\rand()`, but if the real function is expensive or has
side effects that must be avoided when a mock is active, guard the call with `MockRegistry::has()`:

```php
'rand' => function (int $min, int $max): int {
    if (MockRegistry::has(Probability::class, 'rand')) {
        return MockRegistry::get(Probability::class, 'rand');
    }
    return \rand($min, $max);
}
```

> **Note:** Using `$GLOBALS` inside override closures still works and remains fully supported.
> `MockRegistry` is a cleaner alternative, not a replacement — existing code does not need to be
> migrated.

## Learn More

- [PHP-Autoload-Override Website](https://adriansuter.github.io/php-autoload-override/)

## License

The PHP-Autoload-Override library is licensed under the MIT license. See [License File](LICENSE) for more information.
