<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

use function array_keys;
use function file_exists;
use function glob;
use function is_dir;
use function realpath;

/**
 * @package AdrianSuter\Autoload\Override
 */
class AutoloadCollection
{
    /**
     * @var bool[] The keys of this associative array are the file paths.
     */
    private $filePaths = [];

    /**
     * Add a file to the autoload collection.
     *
     * This method would ignore the file if it could not be found.
     *
     * @param string $path The path to the file.
     */
    public function addFile(string $path): void
    {
        $realpath = realpath($path);
        if ($realpath !== false) {
            $this->filePaths[$realpath] = true;
        }
    }

    /**
     * Add a directory, i.e. the php files inside a directory.
     *
     * This method would ignore the directory if it could not be found.
     *
     * @param string $directory The directory.
     */
    public function addDirectory(string $directory): void
    {
        if (
            !file_exists($directory) ||
            !is_dir($directory) ||
            false === ($directory = realpath($directory))
        ) {
            return;
        }

        $files = glob($directory . '/*.php');
        if (is_array($files)) {
            foreach ($files as $file) {
                $this->addFile($file);
            }
        }
    }

    /**
     * Get the file paths.
     *
     * @return string[] The file paths.
     */
    public function getFilePaths(): array
    {
        return array_keys($this->filePaths);
    }
}
