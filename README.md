# Root Namespaced Function Call Override

Root Namespaced Function Call Override for PHP Unit Testing


Let us write the `tests/bootstrap.php` file as follows:
```php
<?php declare(strict_types=1);

use AdrianSuter\AutoloadOverride\RNFCOverride;
use Composer\Autoload\ClassLoader;

/** @var ClassLoader $classLoader */
$classLoader = require __DIR__ . '/../vendor/autoload.php';

$rnfc = [
    \My\Library\ClassName::class => ['copy'],
];

RNFCOverride::run($classLoader, $rnfc);

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

## Custom namespace for the override functions

If the defined functions would all be live in another namespace, then simply add
that namespace to the `run()`-method.

For example
```php
RNFCOverride::run($classLoader, $rnfc, 'My\\Library\\Override');
```


## Custom namespace for one specific class only

You can provide a specific namespace for a function override by simply writing
```php
$rnfc = [
    \My\Library\ClassName::class => ['copy' => 'My\\Tests\\SpecialOverride'],
];
```  

The converter would automatically convert any calls to `\copy()` inside the class
`\My\Library\ClassName` to `\My\Tests\SpecialOverride\copy()`.
