<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

class AutoloadCollection
{
    /**
     * @var bool[]
     */
    private $paths = [];

    /**
     * @param string $path
     */
    public function addFile(string $path): void
    {
        $path = realpath($path);
        if ($path !== false) {
            $this->paths[$path] = true;
        }
    }

    /**
     * @param string[] $directories
     */
    public function addDirectories(array $directories): void
    {
        foreach ($directories as $directory) {
            if (!\file_exists($directory) || !\is_dir($directory)) {
                continue;
            }

            $directory = \realpath($directory);
            if ($directory === false) {
                continue;
            }

//            $files = new RegexIterator(
//                new RecursiveIteratorIterator(
//                    new RecursiveDirectoryIterator($directory)
//                ),
//                '/^.+\.php$/i',
//                RecursiveRegexIterator::GET_MATCH
//            );
            $files = glob($directory . '/*.php');

            foreach ($files as $file) {
                $this->addFile($file);
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
