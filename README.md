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

The class has one method `pick` that takes a probability (between 0 and 100) and two color names
as arguments. It uses the global `\rand()` function to generate a random number and returns one of
the two colors depending on whether that number is within the given probability.

### The problem

As we cannot control the output of `\rand()`, we cannot unit test that method deterministically.
Using the PHP-Autoload-Override library, it is possible to intercept the `\rand()` call and
control its return value.

### The solution

Open the bootstrap script of your test suite (see also
[PHPUnit Configuration](https://phpunit.readthedocs.io/en/latest/configuration.html#the-bootstrap-attribute))
and register the override using `MockRegistry`:

```php
// tests/bootstrap.php

use AdrianSuter\Autoload\Override\MockRegistry;
use AdrianSuter\Autoload\Override\Override;
use My\App\Probability;

/** @var \Composer\Autoload\ClassLoader $classLoader */
$classLoader = require_once __DIR__ . '/../vendor/autoload.php';

Override::apply($classLoader, [
    Probability::class => [
        'rand' => function (int $min, int $max): int {
            return MockRegistry::get(Probability::class, 'rand', \rand($min, $max));
        }
    ]
]);
```

`MockRegistry::get()` resolves in this order: per-class override → global fallback → `$default`.
When no override is set the real `\rand()` is called, so non-test code is unaffected.

> **Note:** Using `$GLOBALS` inside override closures still works and remains fully supported.
> `MockRegistry` is a cleaner alternative, not a replacement — existing code does not need to be
> migrated.

The test class:

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

`MockRegistry::reset(Probability::class)` in `tearDown()` ensures overrides never leak between
tests. Call `MockRegistry::reset()` without arguments to clear everything at once.

### Using `OverrideFactory` (recommended shorthand)

For most test suites, `OverrideFactory` reduces the bootstrap to a concise fluent declaration.
It wraps `MockRegistry::closures()` internally and passes the result directly to `Override::apply()`:

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

Each value in the `forClass()` array is the real fallback implementation as a
[first-class callable](https://www.php.net/manual/en/functions.first_class_callable_syntax.php)
(`\rand(...)`). The generated closure uses the lazy pattern automatically — the real `\rand()` is
only called when no `MockRegistry` entry is set.

For multiple classes:

```php
OverrideFactory::create()
    ->forClass(Clock::class,       ['time' => \time(...)])
    ->forClass(Probability::class, ['rand' => \rand(...)])
    ->apply($classLoader);
```

If you need the raw declarations array (e.g. for an `AbstractIntegrationTestCase`), use `build()`
instead of `apply()`:

```php
protected function getOverrideDeclarations(): array
{
    return OverrideFactory::create()
        ->forClass(Probability::class, ['rand' => \rand(...)])
        ->build();
}
```

### When the fallback has side effects

`MockRegistry::get()` evaluates `$default` eagerly. If the real function has side effects or is
expensive, use `has()` to guard it:

```php
'rand' => function (int $min, int $max): int {
    if (MockRegistry::has(Probability::class, 'rand')) {
        return MockRegistry::get(Probability::class, 'rand');
    }
    return \rand($min, $max);
}
```

### Sharing an override across multiple classes

Use `setGlobal()` to set a fallback that applies to every class:

```php
// Applies to all classes unless a per-class override takes precedence
MockRegistry::setGlobal('time', 1574333284);
```

Reset only the global overrides with `MockRegistry::resetGlobal()`.

Note that this override would only be applied during the unit tests.

## Learn More

- [PHP-Autoload-Override Website](https://adriansuter.github.io/php-autoload-override/)

## License

The PHP-Autoload-Override library is licensed under the MIT license. See [License File](LICENSE) for more information.
