<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\Autoload\Override\ClosureHandler;
use PHPUnit\Framework\TestCase;

final class ClosureHandlerTest extends TestCase
{
    public function testDefault()
    {
        $closureHandler = ClosureHandler::getInstance();
        $closureHandler->addClosure('test', function (): int {
            return 42;
        });

        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals(42, $closureHandler->test());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage  Closure Override "thisIsNotDefined" could not be found.
     */
    public function testUndefinedClosure()
    {
        $closureHandler = ClosureHandler::getInstance();

        /** @noinspection PhpUndefinedMethodInspection */
        $closureHandler->thisIsNotDefined();
    }
}
