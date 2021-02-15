<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\Autoload\Override\FileStreamWrapper;
use Composer\Autoload\ClassLoader;

/** @var ClassLoader $classLoader */
$classLoader = include(__DIR__ . '/../vendor/autoload.php');
$classLoader->loadClass(FileStreamWrapper::class);
