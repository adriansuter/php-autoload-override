# PHP-Autoload-Override

[![Build Status](https://github.com/adriansuter/php-autoload-override/workflows/Tests/badge.svg?branch=master)](https://github.com/adriansuter/php-autoload-override/actions?query=branch:master)
[![Coverage Status](https://coveralls.io/repos/github/adriansuter/php-autoload-override/badge.svg?branch=master)](https://coveralls.io/github/adriansuter/php-autoload-override?branch=master)
[![Total Downloads](https://poser.pugx.org/adriansuter/php-autoload-override/downloads)](https://packagist.org/packages/adriansuter/php-autoload-override)
[![License](https://poser.pugx.org/adriansuter/php-autoload-override/license)](https://packagist.org/packages/adriansuter/php-autoload-override)

Override global function calls such as `\time()` or `\rand()` inside your classes, so that you can
control them in your tests.

The library is meant for tests. It can technically be used in other scenarios, but we do not
recommend it.

**[Full documentation](https://adriansuter.github.io/php-autoload-override/)**

## Requirements

- PHP 8.2 or later
- Classes loaded by Composer through **PSR-4** or the **classmap** (PSR-0 and PSR-4 fallback directories
  are not supported)

## Installation

```bash
composer require --dev adriansuter/php-autoload-override ^2.0
```

## Quickstart with PHPUnit

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

**1. Register the override in the [bootstrap script](https://phpunit.readthedocs.io/en/latest/configuration.html#the-bootstrap-attribute)**
of your test suite, *before* the class is loaded:

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

Each entry maps a function name to its real implementation (`\rand(...)`). As long as no test sets a
mock value, the real function is called.

**2. Set mock values in the test**, and reset them in `tearDown()`:

```php
use AdrianSuter\Autoload\Override\MockRegistry;

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

        $this->assertSame('blue', (new Probability())->pick(34, 'red', 'blue'));
    }
}
```

The [documentation](https://adriansuter.github.io/php-autoload-override/) also covers overrides for
whole namespaces, custom closures, different values on consecutive calls and replacement functions.

## The override has no effect?

1. **Only fully qualified or imported calls are overridden.** `\time()` and `time()` with
   `use function time;` work. An unqualified `time()` inside a namespace is left untouched.
2. **Register the overrides before the class is loaded**, i.e. in the bootstrap script. Classes that
   are already loaded cannot be changed.
3. **Namespace declarations do not apply to sub-namespaces.** `'My\\App\\'` does not affect
   `My\App\Sub\Foo`.
4. **Disable OPcache** for the tests, otherwise PHP may use the unmodified code:

   ```xml
   <php>
       <ini name="opcache.enable_cli" value="0"/>
       <ini name="opcache.enable" value="0"/>
   </php>
   ```

5. A Composer class loader with an **APCu** prefix is not supported.

See [Known limitations](https://adriansuter.github.io/php-autoload-override/#known-limitations) for
further details.

## License

The PHP-Autoload-Override library is licensed under the MIT license. See [License File](LICENSE) for more information.
