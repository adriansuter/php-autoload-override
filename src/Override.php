<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

use Composer\Autoload\ClassLoader;

class Override
{
    /**
     * @var array
     */
    private static $fileFunctionMappings;

    /**
     * @var array
     */
    private static $dirFunctionMappings;

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
     * @param array       $functionMappings
     * @param string      $namespace
     */
    public static function apply(
        ClassLoader $classLoader,
        array $functionMappings,
        string $namespace = 'PHPAutoloadOverride'
    ) {
        // Make sure that the stream wrapper class is loaded.
        if (!class_exists(FileStreamWrapper::class)) {
            $classLoader->loadClass(FileStreamWrapper::class);
        }

        // Reset the function mappings.
        self::$fileFunctionMappings = [];
        self::$dirFunctionMappings = [];

        // Initialize the collection of files we would need to load.
        $autoloadCollection = new AutoloadCollection();

        foreach ($functionMappings as $fqn => $mappings) {
            $funcMappings = self::buildMappings($mappings, $namespace);

            if (\substr($fqn, -1, 1) === '\\') {
                // The given fqn is a namespace.
                $prefixesPsr4 = $classLoader->getPrefixesPsr4();

                $handled = [];
                $popped = [];
                $parts = \explode('\\', trim($fqn, '\\'));
                while (!empty($parts)) {
                    $glued = \implode('\\', $parts) . '\\';

                    if (isset($prefixesPsr4[$glued])) {
                        $subDir = implode('/', $popped);

                        foreach ($prefixesPsr4[$glued] as $directory) {
                            $dir = realpath($directory . '/' . $subDir);
                            if ($dir === false) {
                                continue;
                            }

                            if (is_dir($dir) && !isset($handled[$dir])) {
                                $handled[$dir] = true;
                                //echo $dir . PHP_EOL;
                                self::addNamespaceData([$dir], $funcMappings);
                                $autoloadCollection->addDirectories([$dir]);
                            }
                        }
                    }

                    \array_unshift($popped, \array_pop($parts));
                }
                continue;
            }

            $filePath = $classLoader->findFile($fqn);
            if ($filePath === false) {
                // No matching file for the FQN could be found in the class loader.
                continue;
            }

            $path = \realpath($filePath);
            if ($path === false) {
                // The file could not be found.
                continue;
            }

            self::$fileFunctionMappings[$path] = $funcMappings;
            $autoloadCollection->addFile($path);
        }

        // Load the classes that are affected by the fqfc-override converter.
        \stream_wrapper_unregister('file');
        \stream_wrapper_register('file', FileStreamWrapper::class);
        foreach ($autoloadCollection->getFilePaths() as $file) {
            /** @noinspection PhpIncludeInspection */
            include_once $file;
        }

        \stream_wrapper_restore('file');
        \clearstatcache();
    }

    private static function buildMappings(array $mappings, string $namespace): array
    {
        $fcMappings = [];
        foreach ($mappings as $key => $val) {
            if (\is_numeric($key)) {
                $fcMappings['\\' . $val] = $namespace . '\\' . $val;
            } else {
                if (is_string($val)) {
                    $fcMappings['\\' . $key] = $val . '\\' . $key;
                } elseif ($val instanceof \Closure) {
                    $name = $key . '_' . spl_object_hash($val);
                    ClosureHandler::getInstance()->addMethod($name, $val);

                    $fcMappings['\\' . $key] = ClosureHandler::class . '::getInstance()->' . $name;
                }
            }
        }

        return $fcMappings;
    }

    private static function addNamespaceData(array $directories, array $functionMappings): void
    {
        foreach ($directories as $dir) {
            if (!\file_exists($dir)) {
                continue;
            }

            $dir = \realpath($dir);
            if (isset(self::$dirFunctionMappings[$dir])) {
                self::$dirFunctionMappings[$dir] = \array_merge(
                    self::$dirFunctionMappings[$dir],
                    $functionMappings
                );
            } else {
                self::$dirFunctionMappings[$dir] = $functionMappings;
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
        $filePath = \realpath($filePath);

        $mappings = [];
        foreach (self::$dirFunctionMappings as $dir => $functionMappings) {
            if (\substr($filePath, 0, \strlen($dir)) === $dir) {
                $mappings = \array_merge($mappings, $functionMappings);
            }
        }

        if (isset(self::$fileFunctionMappings[$filePath])) {
            $mappings = \array_merge($mappings, self::$fileFunctionMappings[$filePath]);
        }

        return $mappings;
    }
}
