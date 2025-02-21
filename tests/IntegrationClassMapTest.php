<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\AutoloadCollection;
use AdrianSuter\Autoload\Override\ClosureHandler;
use AdrianSuter\Autoload\Override\CodeConverter;
use AdrianSuter\Autoload\Override\FileStreamWrapper;
use AdrianSuter\Autoload\Override\Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(Override::class)]
#[UsesClass(AutoloadCollection::class)]
#[UsesClass(ClosureHandler::class)]
#[UsesClass(CodeConverter::class)]
#[UsesClass(FileStreamWrapper::class)]
class IntegrationClassMapTest extends AbstractIntegrationTestCase
{
    protected function getOverrideDeclarations(): array
    {
        return [
            \My\Integration\TestClassMapOverride\Calculator::class => [
                'cos' => function (float $arg): float {
                    return \sin($arg);
                },
            ],
            'My\\Integration\\TestClassMapOverride\\' => [
                'cos' => function (float $arg): float {
                    return $arg * 2;
                },
            ]
        ];
    }

    public function testCalculator(): void
    {
        $calculator = new \My\Integration\TestClassMapOverride\Calculator();

        // Calls \cos() > Overridden by FQCN-declaration.
        $this->assertEquals(\sin(\pi() / 2), $calculator->cos(\pi() / 2));
    }

    public function testGeometry(): void
    {
        $geometry = new \My\Integration\TestClassMapOverride\SubNamespace\Geometry();

        // Calls \cos() > Overridden by FQNS-declaration.
        $this->assertEquals(1, $geometry->cos(0.5));
    }

    public function testOtherCalculator(): void
    {
        $otherCalculator = new \My\Integration\TestClassMapOverride\OtherCalculator();

        // Calls \cos() > Overridden by FQNS-declaration.
        $this->assertEquals(2, $otherCalculator->cos(1));
    }
}
