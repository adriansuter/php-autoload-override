# PHP Autoload Override

This library allows to override fully qualified function calls inside your class methods in order to
be able to mock them during testing.

**NOTE: The library can be used for other scenarios as well. But we recommend to use it for testing purposes
only.**

See also https://adriansuter.github.io/php-autoload-override/.


# Prerequisites

- PHP 7.1 or later
- Composer with PSR-4 (PSR-0 is not supported)


# Installation

```bash
$ composer require adriansuter/php-autoload-override
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

The class has one method `pick` that takes a probability (between 0 and 100) and two color names as arguments.
The method would then use the `rand` function of the global scope to generate a random number and
if the generated number is smaller equal to the given probability, then the method would return 
the first color, otherwise the method would return the second color.

As we cannot control the output of the `rand` function (it is in global scope), we cannot unit test
that method. Well, until now. Using the PHP-Autoload-Override library, it is possible to 
override the `rand` function and therefore control its generated random number.

So, after installing the library into the dev-environment, we would open the bootstrap script of our test suite
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

Now the class `Probability` would be loaded into the testing runtime such that all function calls to the global scoped 
`rand()` function get overridden by the closure given above.

So we can write the test class as follows.

```php
namespace My\App\Tests;

use My\App\Probability;
use PHPUnit\Framework\TestCase;

class ProbabilityTest extends TestCase
{
    protected function tearDown()
    {
        if (isset($GLOBALS['rand_return'])) {
            unset($GLOBALS['rand_return']);
        }
    }

    public function testRand()
    {
        $p = new Probability();

        $GLOBALS['rand_return'] = 35;
        $this->assertEquals('blue', $p->pick(34, 'red', 'blue'));
        $this->assertEquals('red', $p->pick(35, 'red', 'blue'));
    }
}
```

Our test case would call the `pick` method two times. The first one checks, if the `else`-block
gets executed. The second one checks, if the `if`-block gets executed.

Note that this override would only be applied during the unit tests.


## Further Information

You can find more information on the [website](https://adriansuter.github.io/php-autoload-override/).
