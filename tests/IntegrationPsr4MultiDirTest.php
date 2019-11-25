<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

class IntegrationPsr4MultiDirTest extends AbstractIntegrationTest
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
