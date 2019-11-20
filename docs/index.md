---
title: PHP Autoload Override
author: "Adrian Suter"
---
You need to override native php functions but your code uses fully qualified function calls?

Then this library might be something for you. But beware, **you should use this library in 
development only, most probably for unit testing.**


## Usage

Say you have the following class:
```php
namespace My\App;

class Person
{
    public function whisper(string $text): string
    {
        return \strtolower($text); // <- Fully qualified function call
    }
}
```

Now you can simply write
```php
/** @var \Composer\Autoload\ClassLoader $classLoader */
$classLoader = require __DIR__ . '/vendor/autoload.php';

\AdrianSuter\Autoload\Override\Override::run($classLoader, [
    \My\App\Person::class => [
        'strtolower' => function (string $str): string {
            return \strtoupper($str);
        }
    ]
]);

$stringConverter = new \My\App\Person();
echo $stringConverter->whisper('Person');
```

The output would be
```bash
PERSON
```


### Use in phpunit

Let us write the `tests/bootstrap.php` file as follows:
```php
<?php declare(strict_types=1);

use AdrianSuter\Autoload\Override\Override;
use Composer\Autoload\ClassLoader;

/** @var ClassLoader $classLoader */
$classLoader = require __DIR__ . '/../vendor/autoload.php';

Override::run($classLoader, [
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
that namespace to the `run()`-method.

For example
```php
Override::run($classLoader, $overrides, 'My\\Library\\Override');
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
