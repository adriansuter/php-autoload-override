<?php
/**
 * Root Namespaced Function Call Override (https://github.com/adriansuter/php-rnfc-override)
 *
 * @license https://github.com/adriansuter/php-rnfc-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\RNFCOverride;

use Composer\Autoload\ClassLoader;

class RNFCOverride
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
     * @var RNFCConverter|null
     */
    private static $converter;


    /**
     * @param RNFCConverter $converter
     */
    public static function setFQFCConverter(RNFCConverter $converter): void
    {
        self::$converter = $converter;
    }

    /**
     * @return RNFCConverter
     */
    public static function getFQFCConverter(): RNFCConverter
    {
        if (self::$converter === null) {
            self::setFQFCConverter(new RNFCConverter());
        }

        return self::$converter;
    }

    /**
     * @param ClassLoader $classLoader
     * @param array       $functionMappings
     * @param string      $namespace
     */
    public static function run(ClassLoader $classLoader, array $functionMappings, string $namespace = 'PHPOverride')
    {
        // Make sure that the stream wrapper class is loaded.
        $classLoader->loadClass(RNFCFileStreamWrapper::class);

        // Reset the function mappings.
        self::$fileFunctionMappings = [];
        self::$dirFunctionMappings = [];

        // Initialize the collection of includes (load and convert).
        $includeCollection = new RNFCIncludeCollection();

        foreach ($functionMappings as $fqn => $mappings) {
            $funcMappings = self::buildMappings($mappings, $namespace);

            if (\substr($fqn, -1, 1) === '\\') {
                // The given fqn is a namespace.
                $prefixesPsr4 = $classLoader->getPrefixesPsr4();
                $parts = \explode('\\', $fqn);
                while (!empty($parts)) {
                    $glued = \implode('\\', $parts) . '\\';

                    if (isset($prefixesPsr4[$glued])) {
                        self::addNamespaceData($prefixesPsr4[$glued], $funcMappings);
                        $includeCollection->addDirectories($prefixesPsr4[$glued]);
                    }

                    \array_pop($parts);
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
            $includeCollection->addFile($path);
        }

        // Load the classes that are affected by the fqfc-override converter.
        \stream_wrapper_unregister('file');
        \stream_wrapper_register('file', RNFCFileStreamWrapper::class);
        foreach ($includeCollection->getFilePaths() as $file) {
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
                $fcMappings['\\' . $key] = $val . '\\' . $key;
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
