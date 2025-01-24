<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\ClosureHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ClosureHandler::class)]
final class ClosureHandlerTest extends TestCase
{
    public function testDefault(): void
    {
        $closureHandler = ClosureHandler::getInstance();
        $closureHandler->addClosure(
            'test',
            function (): int {
                return 42;
            }
        );

        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals(42, $closureHandler->test());
    }

    public function testUndefinedClosure(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Closure Override "thisIsNotDefined" could not be found.');

        $closureHandler = ClosureHandler::getInstance();

        /** @noinspection PhpUndefinedMethodInspection */
        $closureHandler->thisIsNotDefined();
    }
}
