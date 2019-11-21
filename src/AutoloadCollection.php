<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

class AutoloadCollection
{
    /**
     * @var bool[]
     */
    private $paths = [];

    public function addFile(string $path): void
    {
        $this->paths[$path] = true;
    }

    public function addDirectories(array $directories): void
    {
        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                continue;
            }

            $directory = realpath($directory);

            $files = new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory)
                ),
                '/^.+\.php$/i',
                RecursiveRegexIterator::GET_MATCH
            );

            foreach ($files as $file) {
                $this->addFile($file[0]);
            }
        }
    }

    /**
     * @return string[]
     */
    public function getFilePaths(): array
    {
        return array_keys($this->paths);
    }
}
