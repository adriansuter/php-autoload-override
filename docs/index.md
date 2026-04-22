---
title: PHP Autoload Override
author: "Adrian Suter"
---

This library allows overriding fully qualified function calls inside your class methods in order to
be able to mock them during testing.

**NOTE: The library can be used for other scenarios as well. But we recommend using it for testing purposes
only.**

# Requirements

- PHP 8.2 or later
- Composer with PSR-4 (PSR-0 is not supported)

# Installation

```bash
composer require --dev adriansuter/php-autoload-override ^2.0
```

# Simple Example

Say you have the following class `Clock` which contains one method `now()`. That method returns
the result of the php-function `time()` from the global scope (fully qualified function call).

```php
namespace My\App;

class Clock
{
    public function now(): int
    {
        return \time(); // <- Fully qualified function call
    }
}
```

Furthermore, say you have a very simple script that consumes that class in the following form

```php
require_once __DIR__ . '/vendor/autoload.php';

$clock = new \My\App\Clock();
echo $clock->now();
```

Whenever you run this script, the output would be the current unix timestamp. Now if you want to
override the `\time()` function, for example to make sure that the output is always `1574333284`,
you can use the PHP-Autoload-Override library and simply modify your script

```php
/** @var \Composer\Autoload\ClassLoader $classLoader */
$classLoader = require __DIR__ . '/vendor/autoload.php';

\AdrianSuter\Autoload\Override\Override::apply($classLoader, [
    \My\App\Clock::class => [
        'time' => function () {
            return 1574333284;
        }
    ]
]);

$clock = new \My\App\Clock();
echo $clock->now();
```

The output would be `1574333284` no matter the actual timestamp you run this script.

# How does it work?

First the PHP-Autoload-Override library collects all classes that would be affected by an override.
Then the library registers a stream wrapper such that it can handle file loading.
The library instructs the standard autoload class loader from composer to load these affected classes.
The class loader would then load the classes as well as their dependencies. The PHP-Autoload-Override
intercepts the file loading and if it detects an affected class, it loads the source code and modifies the
fully qualified function calls. Of course at the end, the modified source code would be loaded into
the php runtime.

The library uses the [PHP Parser](https://github.com/nikic/PHP-Parser) to find the fully qualified
function calls (global scope) and to perform the code conversion. It tries to leave the format
of the code as untouched as possible.

In our own tests, the coverage report did work as before (the overrides did not disturb the reporting).

# Usage

It is possible to override the fully qualified function calls (global scope) inside one class, or even
for all classes of a specific namespace. Note that sub-namespaces would not be affected.

To define the function calls that should be overridden for a whole namespace,
instead of writing the fully qualified class name as key, simply write the fully
qualified namespace name, e.g.

```php
\AdrianSuter\Autoload\Override\Override::apply($classLoader, [
    'My\\App\\' => [
        'time' => function () {
            return 1574333284;
        }
    ]
]);
```

You can either define a closure as override (see above) or use the well-known namespace technique. This
technique would allow you to define the functions inside a namespace (other than global scope)
and the PHP-Autoload-Override would override the corresponding function calls to use that namespace.
By default, the namespace is `PHPAutoloadOverride`.

```php
\AdrianSuter\Autoload\Override\Override::apply($classLoader, [
    'My\\App\\' => ['time']
]);
```

So the code converter would convert all function calls to `\time()` inside all classes
of the namespace `My\App` into function calls `\PHPAutoloadOverride\time()`. Of course you
would have to define those functions.

You can even customize the default namespace using the third argument of
the `\AdrianSuter\Autoload\Override\Override::apply()` method.

If you would like to set the namespace for one specific function call only, then
you can do that by simply writing it as key-value pair.

```php
\AdrianSuter\Autoload\Override\Override::apply($classLoader, [
    'My\\App\\' => ['time' => 'My\\Special\\Override']
]);
```

# Usage with [PHPUnit](https://phpunit.de/)

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

The class has one method `pick` that takes a probability (between 0 and 100) and two color names as arguments.
The method would then use the `rand` function of the global scope to generate a random number and
if the generated number is smaller equal to the given probability, then the method would return
the first color, otherwise the method would return the second color.

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

Now the class `Probability` would be loaded into the PHPUnit runtime such that all function calls
to the global scoped `rand()` function in the class `Probability` get overridden by the closure
given above.

Our test class can now be written as follows.

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

The two tests cover both branches of `pick()` with a controlled `\rand()` return value of 35.
Note that this override would only be applied during the unit tests.

## Using `OverrideFactory` (recommended shorthand)

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

## When the fallback has side effects

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

## Sharing an override across multiple classes

Use `setGlobal()` to set a fallback that applies to every class:

```php
// Applies to all classes unless a per-class override takes precedence
MockRegistry::setGlobal('time', 1574333284);
```

Reset only the global overrides with `MockRegistry::resetGlobal()`.

# Caching Limitations

**APC User Cache** is not supported.

**OPcache:** The stream wrapper modifies PHP source code at load time. If OPcache is active (including
on the CLI), it may cache the unmodified original opcodes and bypass the override entirely. Disable
it in your `phpunit.xml.dist`:

```xml

<php>
    <ini name="opcache.enable_cli" value="0"/>
    <ini name="opcache.enable" value="0"/>
</php>
```
