<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

class IntegrationCustomNamespaceTest extends AbstractIntegrationTest
{
    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        require_once(__DIR__ . '/assets/PHPCustomAutoloadOverride.php');
    }

    /**
     * @inheritDoc
     */
    protected function getOverrideDeclarations(): array
    {
        return [
            \My\Integration\TestCustomNamespaceOverride\Hash::class => [
                // (C1)
                'md5' => 'PHPCustomAutoloadOverride'
            ]
        ];
    }

    public function testHash()
    {
        $hash = new \My\Integration\TestCustomNamespaceOverride\Hash();

        // Calls \md5() > Overridden by declaration (C1).
        $GLOBALS['md5_return'] = '---';
        $this->assertEquals('---', $hash->hash('1'));

        // Calls \md5() > Overridden by declaration (C1), but as $GLOBALS['md5_return']
        // had been unset in the previous call, the override itself calls the
        // root namespaced md5() function.
        $this->assertEquals('c4ca4238a0b923820dcc509a6f75849b', $hash->hash('1'));
    }
}
