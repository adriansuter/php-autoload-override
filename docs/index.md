---
title: PHP Autoload Override
author: "Adrian Suter"
---
This library allows to override fully qualified function calls inside your class methods in order to
be able to mock them during testing.

**NOTE: The library can be used for other scenarios as well. But we recommend to use it for testing purposes
only.**


# Prerequisites

- PHP 7.1 or later
- Composer with PSR-4 (PSR-0 is not supported)


# Installation

```bash
$ composer require adriansuter/php-autoload-override
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

The output would be `1574333284` no matter when you run this script.


# How does it work?

First the PHP-Autoload-Override library collects all classes that would be affected by an override.
Then the library registers a stream wrapper such that it can handle file loading.
Using the standard autoload class loader from composer, the library then loads these affected classes.
The class loader would then load the classes as well as their dependencies. The PHP-Autoload-Override
intercepts the file loading and if it detects an affected class, it loads the source code and modifies the
fully qualified function calls. Of course at the end, the modified source code would be loaded into
the php runtime.


## Usage

TODO: Add a note that namespace defined overrides would only be applied to the namespace - not sub namespaces!


### Use in phpunit

Let us write the `tests/bootstrap.php` file as follows:
```php
<?php declare(strict_types=1);

use AdrianSuter\Autoload\Override\Override;
use Composer\Autoload\ClassLoader;

/** @var ClassLoader $classLoader */
$classLoader = require __DIR__ . '/../vendor/autoload.php';

Override::apply($classLoader, [
    \My\App\Person::class => ['copy'],
]);

require __DIR__ . '/Assets/PhpFunctionOverrides.php';
```

The default namespace in which you should define your php function overrides is
`PHPOverride`. So inside `tests/Assets/PhpFunctionOverrides.php` we would define
```php
<?php declare(strict_types=1);

namespace PHPOverride;

function copy(string $source, string $destination, $context = null): bool
{
    if (isset($GLOBALS['copy_return'])) {
        return $GLOBALS['copy_return'];
    }

    if ($context === null) {
        return \copy($source, $destination);
    }

    return \copy($source, $destination, $context);
}
```

### Custom namespace for the override functions

If the defined functions would all be live in another namespace, then simply add
that namespace to the `apply()`-method.

For example
```php
Override::apply($classLoader, $overrides, 'My\\Library\\Override');
```


## Custom namespace for one specific class only

You can provide a specific namespace for a function override by simply writing
```php
$overrides = [
    \My\App\Person::class => ['copy' => 'My\\Tests\\SpecialOverride'],
];
```

The converter would automatically convert any calls to `\copy()` inside the class
`\My\App\Person` to `\My\Tests\SpecialOverride\copy()`.


# APC User Cache

APC User Cache is not supported.
