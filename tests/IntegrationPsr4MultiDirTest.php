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
class IntegrationPsr4MultiDirTest extends AbstractIntegrationTestCase
{
    protected function getOverrideDeclarations(): array
    {
        return [
            \AdrianSuter\Autoload\Override\Science::class => [
                'str_repeat' => function ($str, $multiplier) {
                    return \str_repeat($str, 2 * $multiplier);
                }
            ],
            'AdrianSuter\\Autoload\\Override\\SubSpace\\' => [
                'str_repeat' => function ($input, $multiplier) {
                    return ':';
                }
            ],
        ];
    }

    public function testScience()
    {
        $science = new \AdrianSuter\Autoload\Override\Science();
// Calls \str_repeat() > Overridden by FQCN-declaration.
        $this->assertEquals('xxxx', $science->crosses(2));
    }

    public function testSpeech()
    {
        $speech = new \AdrianSuter\Autoload\Override\SubSpace\Speech();
// Calls \str_repeat() > Overridden by FQNS-declaration.
        $this->assertEquals(':', $speech->whisper(2));
    }
}
