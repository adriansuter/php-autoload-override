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
use function file_exists;
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

class Override
{
    /**
     * @var array
     */
    private static $fileFunctionCallMappings;

    /**
     * @var array
     */
    private static $dirFunctionCallMappings;

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
    public static function getCodeConverter(): CodeConverter
    {
        if (self::$converter === null) {
            self::setCodeConverter(new CodeConverter());
        }

        return self::$converter;
    }

    /**
     * @param ClassLoader $classLoader
     * @param string[]|Closure[] $functionCallMappings
     * @param string $namespace
     */
    public static function apply(
        ClassLoader $classLoader,
        array $functionCallMappings,
        string $namespace = 'PHPAutoloadOverride'
    ) {
        if ($classLoader->getApcuPrefix() !== null) {
            throw new RuntimeException('APC User Cache is not supported.');
        }

        // Make sure that the stream wrapper class is loaded.
        if (!class_exists(FileStreamWrapper::class)) {
            $classLoader->loadClass(FileStreamWrapper::class);
        }

        // Reset the function call mappings.
        self::$fileFunctionCallMappings = [];
        self::$dirFunctionCallMappings = [];

        // Initialize the collection of files we would force to load (include).
        $autoloadCollection = new AutoloadCollection();

        foreach ($functionCallMappings as $fqn => $mappings) {
            $fqnFunctionCallMappings = self::buildMappings($mappings, $namespace);

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

                                self::addNamespaceData([$dir], $fqnFunctionCallMappings);
                                $autoloadCollection->addDirectory($dir);
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

                        if (isset(self::$fileFunctionCallMappings[$p])) {
                            self::$fileFunctionCallMappings[$p] = array_merge(
                                $fqnFunctionCallMappings,
                                self::$fileFunctionCallMappings[$p]
                            );
                        } else {
                            self::$fileFunctionCallMappings[$p] = $fqnFunctionCallMappings;
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

            self::$fileFunctionCallMappings[$path] = $fqnFunctionCallMappings;
            $autoloadCollection->addFile($path);
        }

        // Load the classes that are affected by the FQFC-override converter.
        stream_wrapper_unregister('file');
        stream_wrapper_register('file', FileStreamWrapper::class);
        foreach ($autoloadCollection->getFilePaths() as $file) {
            /** @noinspection PhpIncludeInspection */
            include_once $file;
        }

        stream_wrapper_restore('file');
        clearstatcache();
    }

    /**
     * @param string[]|Closure[] $mappings
     * @param string $namespace
     *
     * @return array
     */
    private static function buildMappings(array $mappings, string $namespace): array
    {
        $fcMappings = [];
        foreach ($mappings as $key => $val) {
            if (is_numeric($key)) {
                $fcMappings['\\' . $val] = $namespace . '\\' . $val;
            } elseif (is_string($val)) {
                $fcMappings['\\' . $key] = $val . '\\' . $key;
            } elseif ($val instanceof Closure) {
                $name = $key . '_' . spl_object_hash($val);
                ClosureHandler::getInstance()->addClosure($name, $val);

                $fcMappings['\\' . $key] = ClosureHandler::class . '::getInstance()->' . $name;
            }
        }

        return $fcMappings;
    }

    private static function addNamespaceData(array $directories, array $functionMappings): void
    {
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                continue;
            }

            $dir = realpath($dir);
            if (isset(self::$dirFunctionCallMappings[$dir])) {
                self::$dirFunctionCallMappings[$dir] = array_merge(
                    self::$dirFunctionCallMappings[$dir],
                    $functionMappings
                );
            } else {
                self::$dirFunctionCallMappings[$dir] = $functionMappings;
            }
        }
    }

    /**
     * @param string $filePath
     *
     * @return string[]
     */
    public static function getFunctionMappings(string $filePath): array
    {
        $filePath = realpath($filePath);
        $dirPath = dirname($filePath);

        $mappings = [];
        if (isset(self::$dirFunctionCallMappings[$dirPath])) {
            $mappings = array_merge($mappings, self::$dirFunctionCallMappings[$dirPath]);
        }

        if (isset(self::$fileFunctionCallMappings[$filePath])) {
            $mappings = array_merge($mappings, self::$fileFunctionCallMappings[$filePath]);
        }

        return $mappings;
    }
}
