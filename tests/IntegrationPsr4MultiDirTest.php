<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

class IntegrationPsr4MultiDirTest extends AbstractIntegrationTest
{
    /**
     * @inheritDoc
     */
    protected function getOverrideDeclarations(): array
    {
        return [
            \AdrianSuter\Autoload\Override\Science::class => [
                // (C1)
                'str_repeat' => function ($str, $multiplier): string {
                    return \str_repeat($str, 2 * $multiplier);
                }
            ],
            'AdrianSuter\\Autoload\\Override\\SubSpace\\' => [
                // (N2)
                'str_repeat' => function ($input, $multiplier): string {
                    return ':';
                }
            ],
        ];
    }

    public function testScience()
    {
        $science = new \AdrianSuter\Autoload\Override\Science();

        // Calls \str_repeat() > Overridden by declaration (C1).
        $this->assertEquals('xxxx', $science->crosses(2));
    }

    public function testSpeech()
    {
        $speech = new \AdrianSuter\Autoload\Override\SubSpace\Speech();

        // Calls \str_repeat() > Overridden by declaration (N2).
        $this->assertEquals(':', $speech->whisper(2));
    }
}
