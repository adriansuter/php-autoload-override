---
title: PHP Autoload Override
author: "Adrian Suter"
---

Override global function calls such as `\time()` or `\rand()` inside your classes, so that you can
control them in your tests.

```php
// Production code
public function pick(int $probability, string $color1, string $color2): string
{
    return \rand(1, 100) <= $probability ? $color1 : $color2;
}

// Test
MockRegistry::set(Probability::class, 'rand', 35);
$this->assertSame('blue', $probability->pick(34, 'red', 'blue'));
```

The library is meant for tests. It can technically be used in other scenarios, but we do not
recommend it.

**Contents**

- [Requirements](#requirements)
- [Installation](#installation)
- [Quickstart with PHPUnit](#quickstart-with-phpunit)
- [What can be overridden?](#what-can-be-overridden)
- [Working with MockRegistry](#working-with-mockregistry)
- [Using Override::apply() directly](#using-overrideapply-directly)
- [Troubleshooting](#troubleshooting)
- [Known limitations](#known-limitations)
- [How does it work?](#how-does-it-work)
- [API overview](#api-overview)
- [Migrating from $GLOBALS](#migrating-from-globals)

# Requirements

- PHP 8.2 or later
- Classes loaded by Composer through **PSR-4** or the **classmap** (PSR-0 and PSR-4 fallback directories
  are not supported)

# Installation

```bash
composer require --dev adriansuter/php-autoload-override ^2.0
```

# Quickstart with PHPUnit

Say we want to test the following class `Probability`:

```php
namespace My\App;

class Probability
{
    public function pick(int $probability, string $color1, string $color2): string
    {
        if (\rand(1, 100) <= $probability) {
            return $color1;
        }

        return $color2;
    }
}
```

The class calls `\rand()`. Because we cannot control its result, we cannot test `pick()`
deterministically — until we override it.

## 1. Register the override in the bootstrap script

Open the [bootstrap script](https://phpunit.readthedocs.io/en/latest/configuration.html#the-bootstrap-attribute)
of your test suite:

```php
// tests/bootstrap.php

use AdrianSuter\Autoload\Override\OverrideFactory;
use My\App\Probability;

/** @var \Composer\Autoload\ClassLoader $classLoader */
$classLoader = require __DIR__ . '/../vendor/autoload.php';

OverrideFactory::create()
    ->forClass(Probability::class, ['rand' => \rand(...)])
    ->apply($classLoader);
```

Each entry in `forClass()` maps a function name to its real implementation, written as a
[first-class callable](https://www.php.net/manual/en/functions.first_class_callable_syntax.php)
(`\rand(...)`). As long as no test sets a mock value, the real function is called.

For multiple classes, chain `forClass()` calls:

```php
OverrideFactory::create()
    ->forClass(Clock::class,       ['time' => \time(...)])
    ->forClass(Probability::class, ['rand' => \rand(...), 'mt_rand' => \mt_rand(...)])
    ->apply($classLoader);
```

> **Important:** The override must be registered *before* the class is loaded for the first time.
> The bootstrap script is the right place for that.

## 2. Set mock values in the test

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

Reset the mock values in `tearDown()`, so that they do not leak into other tests.

# What can be overridden?

## Fully qualified or imported function calls only

Only function calls that PHP resolves to the global function at compile time are overridden:

```php
namespace My\App;

use function time;

class Clock
{
    public function a(): int { return \time(); } // overridden (fully qualified)
    public function b(): int { return time(); }  // overridden (imported with "use function")
}

class OtherClock
{
    public function c(): int { return time(); }  // NOT overridden (unqualified, no import)
}
```

An unqualified call inside a namespace (`time()` without `\` and without `use function`) is left
untouched. If your code base uses unqualified calls, either add the leading backslash or import the
functions. Tools like PHP-CS-Fixer can do that automatically (`native_function_invocation`).

## A single class or a whole namespace

Overrides can be declared for one class (key: the class name) or for all classes of a namespace
(key: the namespace with a trailing backslash):

```php
use AdrianSuter\Autoload\Override\Override;

Override::apply($classLoader, [
    \My\App\Clock::class => ['time' => fn (): int => 1574333284], // only this class
    'My\\App\\'          => ['rand' => fn (): int => 4],          // all classes in My\App
]);
```

- **Sub-namespaces are not affected.** The declaration `'My\\App\\'` does not apply to
  `My\App\Sub\Foo`. Add a separate declaration for each sub-namespace.
- **Class declarations take precedence.** If a function is overridden both for a class and for its
  namespace, the class declaration wins. Functions declared only for the namespace still apply to the
  class.

# Working with MockRegistry

`MockRegistry` stores the mock values that the closures generated by `OverrideFactory` return.

| Method | Description |
|---|---|
| `MockRegistry::set($class, $function, $value)` | Mock value for one class. |
| `MockRegistry::setGlobal($function, $value)` | Fallback for all classes. A per-class value takes precedence. |
| `MockRegistry::has($class, $function)` | Whether a per-class or global value is set. |
| `MockRegistry::get($class, $function, $default)` | The per-class value, else the global value, else `$default`. |
| `MockRegistry::reset($class)` | Removes the values of one class. |
| `MockRegistry::reset()` | Removes all values (per-class and global). |
| `MockRegistry::resetGlobal()` | Removes the global values only. |

The global fallback still requires an override to be registered for the class (e.g. with
`forClass()`). It only provides the value.

```php
MockRegistry::setGlobal('time', 1574333284);
```

# Using Override::apply() directly

`OverrideFactory` covers the common case: return a fixed mock value or call the real function.
For anything else, register your own closures with `Override::apply()`.

## Custom closures

```php
// tests/bootstrap.php

use AdrianSuter\Autoload\Override\MockRegistry;
use AdrianSuter\Autoload\Override\Override;
use My\App\Probability;

/** @var \Composer\Autoload\ClassLoader $classLoader */
$classLoader = require __DIR__ . '/../vendor/autoload.php';

Override::apply($classLoader, [
    Probability::class => [
        'rand' => function (int $min, int $max): int {
            if (MockRegistry::has(Probability::class, 'rand')) {
                return MockRegistry::get(Probability::class, 'rand');
            }

            return \rand($min, $max);
        },
    ],
]);
```

Prefer `has()` + `get()` over `MockRegistry::get(Probability::class, 'rand', \rand($min, $max))`:
the default argument is evaluated on every call, even if a mock value is set.

## Different values on consecutive calls

A mock value set with `MockRegistry::set()` is returned on every call. To return different values,
store a callable and let your closure invoke it:

```php
// bootstrap
Override::apply($classLoader, [
    Probability::class => [
        'rand' => function (int $min, int $max): int {
            if (MockRegistry::has(Probability::class, 'rand')) {
                return (MockRegistry::get(Probability::class, 'rand'))($min, $max);
            }

            return \rand($min, $max);
        },
    ],
]);

// test
$values = [10, 90];
MockRegistry::set(Probability::class, 'rand', function () use (&$values): int {
    return \array_shift($values);
});
```

## Combining the factory with other declarations

`build()` returns the declarations of the factory as an array, which you can combine with your own
declarations. Call `Override::apply()` **once** with all declarations:

```php
Override::apply(
    $classLoader,
    OverrideFactory::create()
        ->forClass(Probability::class, ['rand' => \rand(...)])
        ->build()
    + [
        'My\\App\\Legacy\\' => ['time' => fn (): int => 1574333284],
    ]
);
```

## Replacement functions in a namespace

Instead of a closure, you can let the calls point to a function of your own. List the function name
without a key, and define the function in the namespace `PHPAutoloadOverride`:

```php
// tests/bootstrap.php
namespace PHPAutoloadOverride;

function time(): int
{
    return 1574333284;
}
```

```php
\AdrianSuter\Autoload\Override\Override::apply($classLoader, [
    'My\\App\\' => ['time'], // \time() becomes \PHPAutoloadOverride\time()
]);
```

The default namespace can be changed with the third argument of `Override::apply()`. To use a
different namespace for one function only, write it as value:

```php
\AdrianSuter\Autoload\Override\Override::apply($classLoader, [
    'My\\App\\' => ['time' => 'My\\Special\\Override'], // \My\Special\Override\time()
]);
```

# Troubleshooting

If an override has no effect, check the following:

1. **Is the call fully qualified or imported?** `\time()` and `time()` with `use function time;` work,
   an unqualified `time()` inside a namespace does not. See
   [What can be overridden?](#what-can-be-overridden).
2. **Was the class loaded before `apply()`?** Classes that are already loaded cannot be changed.
   Register the overrides in the bootstrap script, before any test code runs.
3. **Is the class in a sub-namespace?** Namespace declarations do not apply to sub-namespaces.
4. **Is the class loaded by Composer through PSR-4 or the classmap?** Other loaders are not supported.
5. **Is OPcache enabled?** See [Known limitations](#known-limitations).
6. **Did you reset the mock value?** Check `tearDown()` and `MockRegistry::setGlobal()` values from other
   tests.

# Known limitations

**OPcache:** The library modifies the source code while it is loaded. With OPcache enabled (also on the
CLI), PHP may use the cached, unmodified code. Disable it in your `phpunit.xml.dist`:

```xml
<php>
    <ini name="opcache.enable_cli" value="0"/>
    <ini name="opcache.enable" value="0"/>
</php>
```

**APCu:** A Composer class loader with an APCu prefix is not supported. `Override::apply()` throws an
exception.

**By-reference parameters:** Closures cannot pass by-reference arguments back to the caller. Overriding
e.g. `preg_match($pattern, $subject, $matches)` with a closure leaves `$matches` unset, and PHP emits
a warning. Use
[replacement functions in a namespace](#replacement-functions-in-a-namespace) with a by-reference
parameter instead.

**`null` as mock value:** `MockRegistry::get()` treats a per-class value of `null` as "not set" and
falls back to the global value.

# How does it work?

1. `Override::apply()` collects the files of all affected classes from the Composer class loader.
2. It temporarily registers a stream wrapper for `file://` and loads these files.
3. While loading, the stream wrapper uses [PHP Parser](https://github.com/nikic/PHP-Parser) to replace
   the overridden function calls, e.g. `\rand(1, 100)` becomes a call to the registered closure.
   The format of the code is preserved as far as possible.
4. The stream wrapper is removed again. All other files are loaded as usual.

In our own tests, the overrides did not disturb the code coverage reports.

# API overview

| Class | Method | Purpose |
|---|---|---|
| `OverrideFactory` | `create()` | Starts a new builder. |
| | `forClass(string $class, array $fallbacks)` | Overrides for one class, backed by `MockRegistry`. `$fallbacks` maps function names to the real implementation. |
| | `apply(ClassLoader $loader)` | Registers the overrides. |
| | `build(): array` | Returns the declarations for `Override::apply()`. |
| `Override` | `apply(ClassLoader $loader, array $declarations, string $namespace = 'PHPAutoloadOverride')` | Registers the overrides. Keys are class names or namespaces (trailing `\`), values map function names to closures or namespaces. |
| `MockRegistry` | see [Working with MockRegistry](#working-with-mockregistry) | Stores mock values. |

# Migrating from $GLOBALS

Earlier versions of this documentation used `$GLOBALS` inside the override closures to pass mock
values. This still works and does not need to be migrated. `MockRegistry` and `OverrideFactory` are
the cleaner alternative for new code:

```php
// Before (bootstrap)
Override::apply($classLoader, [
    Probability::class => [
        'rand' => function ($min, $max): int {
            if (isset($GLOBALS['rand_return'])) {
                return $GLOBALS['rand_return'];
            }

            return \rand($min, $max);
        },
    ],
]);
// Before (test)
$GLOBALS['rand_return'] = 35;      // and unset($GLOBALS['rand_return']) in tearDown()

// After (bootstrap)
OverrideFactory::create()
    ->forClass(Probability::class, ['rand' => \rand(...)])
    ->apply($classLoader);
// After (test)
MockRegistry::set(Probability::class, 'rand', 35);  // and MockRegistry::reset() in tearDown()
```
