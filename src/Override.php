<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

use Closure;
use Composer\Autoload\ClassLoader;
use RuntimeException;

use function array_merge;
use function array_pop;
use function array_unshift;
use function class_exists;
use function clearstatcache;
use function dirname;
use function explode;
use function implode;
use function is_dir;
use function is_numeric;
use function is_string;
use function realpath;
use function spl_object_hash;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;
use function strlen;
use function substr;
use function trim;

/**
 * @package AdrianSuter\Autoload\Override
 */
class Override
{
    /**
     * @var array<string, array<string, string>>
     */
    private static $fileFunctionCallMap;

    /**
     * @var array<string, array<string, string>>
     */
    private static $dirFunctionCallMap;

    /**
     * @var CodeConverter|null
     */
    private static $converter;


    /**
     * @param CodeConverter $converter
     */
    public static function setCodeConverter(CodeConverter $converter): void
    {
        self::$converter = $converter;
    }

    /**
     * @return CodeConverter
     */
    private static function getCodeConverter(): CodeConverter
    {
        if (self::$converter === null) {
            self::$converter = new CodeConverter();
        }

        return self::$converter;
    }

    /**
     * @param ClassLoader $classLoader
     * @param string[][]|Closure[][] $functionCallMap
     * @param string $overrideNamespace
     */
    public static function apply(
        ClassLoader $classLoader,
        array $functionCallMap,
        string $overrideNamespace = 'PHPAutoloadOverride'
    ): void {
        if ($classLoader->getApcuPrefix() !== null) {
            throw new RuntimeException('APC User Cache is not supported.');
        }

        // Make sure that the stream wrapper class is loaded.
        if (!class_exists(FileStreamWrapper::class)) {
            $classLoader->loadClass(FileStreamWrapper::class);
        }

        // Reset the function call maps.
        self::$fileFunctionCallMap = [];
        self::$dirFunctionCallMap = [];

        // Initialize the collection of files we would force to load (include).
        $autoloadCollection = new AutoloadCollection();

        foreach ($functionCallMap as $fqn => $map) {
            // Build the fqn function call map.
            $fqnFunctionCallMap = self::buildFunctionCallMap($map, $overrideNamespace);

            if (substr($fqn, -1, 1) === '\\') {
                // The given fqn is a namespace.
                $prefixesPsr4 = $classLoader->getPrefixesPsr4();

                $handled = [];
                $popped = [];
                $parts = explode('\\', trim($fqn, '\\'));
                while (!empty($parts)) {
                    $glued = implode('\\', $parts) . '\\';

                    if (isset($prefixesPsr4[$glued])) {
                        $subDir = implode('/', $popped);

                        foreach ($prefixesPsr4[$glued] as $directory) {
                            $dir = realpath($directory . '/' . $subDir);
                            if ($dir === false) {
                                continue;
                            }

                            if (is_dir($dir) && !isset($handled[$dir])) {
                                $handled[$dir] = true;

                                self::addDirectoryFunctionCallMap($autoloadCollection, $dir, $fqnFunctionCallMap);
                            }
                        }
                    }

                    array_unshift($popped, array_pop($parts));
                }

                foreach ($classLoader->getClassMap() as $classMapFqn => $classMapPath) {
                    if (substr($classMapFqn, 0, strlen($fqn)) === $fqn) {
                        $p = realpath($classMapPath);
                        if ($p === false) {
                            continue;
                        }

                        if (isset(self::$fileFunctionCallMap[$p])) {
                            self::$fileFunctionCallMap[$p] = array_merge(
                                $fqnFunctionCallMap,
                                self::$fileFunctionCallMap[$p]
                            );
                        } else {
                            self::$fileFunctionCallMap[$p] = $fqnFunctionCallMap;
                        }
                        $autoloadCollection->addFile($p);
                    }
                }

                //foreach ($classLoader->getFallbackDirsPsr4() as $fallbackDirPsr4) {
                // TODO: Handle this case.
                //}
                continue;
            }

            $filePath = $classLoader->findFile($fqn);
            if ($filePath === false) {
                // No matching file for the FQN could be found in the class loader.
                continue;
            }

            $path = realpath($filePath);
            if ($path === false) {
                // The file could not be found.
                continue;
            }

            self::$fileFunctionCallMap[$path] = $fqnFunctionCallMap;
            $autoloadCollection->addFile($path);
        }

        // Load the classes that are affected by the FQFC-override converter.
        stream_wrapper_unregister('file');
        stream_wrapper_register('file', FileStreamWrapper::class);
        foreach ($autoloadCollection->getFilePaths() as $filePath) {
            /** @noinspection PhpIncludeInspection */
            include_once $filePath;
        }

        stream_wrapper_restore('file');
        clearstatcache();
    }

    /**
     * Build a mapping between root namespaced function calls and their overridden fully qualified name.
     *
     * @param string[]|Closure[] $map
     * @param string $namespace
     *
     * @return array<string, string>
     */
    private static function buildFunctionCallMap(array $map, string $namespace): array
    {
        $functionCallMap = [];
        foreach ($map as $key => $val) {
            if (is_numeric($key) && is_string($val)) {
                $functionCallMap['\\' . $val] = $namespace . '\\' . $val;
            } elseif (is_string($val)) {
                $functionCallMap['\\' . $key] = $val . '\\' . $key;
            } elseif ($val instanceof Closure) {
                $name = $key . '_' . spl_object_hash($val);
                ClosureHandler::getInstance()->addClosure($name, $val);

                $functionCallMap['\\' . $key] = ClosureHandler::class . '::getInstance()->' . $name;
            }
        }

        return $functionCallMap;
    }

    /**
     * @param AutoloadCollection $autoloadCollection
     * @param string $directory
     * @param array<string, string> $fqnFunctionCallMap
     */
    private static function addDirectoryFunctionCallMap(
        AutoloadCollection $autoloadCollection,
        string $directory,
        array $fqnFunctionCallMap
    ): void {
        $directory = realpath($directory);
        if ($directory === false) {
            return;
        }

        if (isset(self::$dirFunctionCallMap[$directory])) {
            self::$dirFunctionCallMap[$directory] = array_merge(
                self::$dirFunctionCallMap[$directory],
                $fqnFunctionCallMap
            );
        } else {
            self::$dirFunctionCallMap[$directory] = $fqnFunctionCallMap;
        }

        $autoloadCollection->addDirectory($directory);
    }

    /**
     * @param string $filePath
     *
     * @return array<string, string>
     */
    public static function getFunctionCallMap(string $filePath): array
    {
        $filePath = realpath($filePath);
        if ($filePath === false) {
            return [];
        }

        $dirPath = dirname($filePath);

        $functionCallMap = [];
        if (isset(self::$dirFunctionCallMap[$dirPath])) {
            $functionCallMap = array_merge($functionCallMap, self::$dirFunctionCallMap[$dirPath]);
        }

        if (isset(self::$fileFunctionCallMap[$filePath])) {
            $functionCallMap = array_merge($functionCallMap, self::$fileFunctionCallMap[$filePath]);
        }

        return $functionCallMap;
    }

    /**
     * Convert the source code using the fqn function call map.
     *
     * @param string $source
     * @param array<string, string> $functionCallMap
     *
     * @return string
     */
    public static function convert(string $source, array $functionCallMap): string
    {
        return self::getCodeConverter()->convert($source, $functionCallMap);
    }
}
