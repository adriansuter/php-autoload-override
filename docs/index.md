---
title: PHP Autoload Override
author: "Adrian Suter"
---
This library allows overriding fully qualified function calls inside your class methods in order to
be able to mock them during testing.

**NOTE: The library can be used for other scenarios as well. But we recommend using it for testing purposes
only.**


# Requirements

- PHP 7.3 or later
- Composer with PSR-4 (PSR-0 is not supported)


# Installation

```bash
$ composer require --dev adriansuter/php-autoload-override 1.5
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

Furthermore say you have a very simple script that consumes that class in the following form
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

After installing the PHP-Autoload-Override library, we would open the bootstrap script of our test suite
(see also [PHPUnit Configuration](https://phpunit.readthedocs.io/en/8.4/configuration.html#the-bootstrap-attribute)).
There we will write the following code

```php
// tests/bootstrap.php

/** @var \Composer\Autoload\ClassLoader $classLoader */
$classLoader = require_once __DIR__ . '/../vendor/autoload.php';

\AdrianSuter\Autoload\Override\Override::apply($classLoader, [
    \My\App\Probability::class => [
        'rand' => function ($min, $max): int {
            if (isset($GLOBALS['rand_return'])) {
                return $GLOBALS['rand_return'];
            }

            return \rand($min, $max);
        }
    ]
]);
```

Now the class `Probability` would be loaded into the PHPUnit runtime such that all function calls to the global scoped 
`rand()` function in the class `Probability` get overridden by the closure given above.

Our test class can now be written as follows.

```php
namespace My\App\Tests;

use My\App\Probability;
use PHPUnit\Framework\TestCase;

final class ProbabilityTest extends TestCase
{
    protected function tearDown()
    {
        if (isset($GLOBALS['rand_return'])) {
            unset($GLOBALS['rand_return']);
        }
    }

    public function testPick()
    {
        $p = new Probability();

        $GLOBALS['rand_return'] = 35;

        $this->assertEquals('blue', $p->pick(34, 'red', 'blue'));
        $this->assertEquals('red', $p->pick(35, 'red', 'blue'));
    }
}
```

The test case `testPick` would call the `pick` method two times. As we have overridden the `\rand` function, we can
control its returned value to be always 35. So the first call checks, if the `else`-block
gets executed. The second one checks, if the `if`-block gets executed. Hooray, 100% code coverage.

Note that this override would only be applied during the unit tests.


# APC User Cache

APC User Cache is not supported.
