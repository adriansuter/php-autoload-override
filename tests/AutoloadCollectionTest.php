<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\AutoloadCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AutoloadCollection::class)]
class AutoloadCollectionTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/autoload_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if (is_array($files)) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    #[DataProvider('provideAddFileCases')]
    public function testAddFile(string $filePath, bool $shouldExist, int $expectedCount): void
    {
        if ($shouldExist) {
            file_put_contents($filePath, '<?php');
        }

        $collection = new AutoloadCollection();
        $collection->addFile($filePath);

        $paths = $collection->getFilePaths();

        $this->assertCount($expectedCount, $paths);

        if ($shouldExist) {
            $this->assertContains(realpath($filePath), $paths);
        }
    }

    public static function provideAddFileCases(): array
    {
        $base = sys_get_temp_dir();

        return [
            'existing file' => [
                $base . '/file_exists.php',
                true,
                1,
            ],
            'non-existing file' => [
                $base . '/file_missing.php',
                false,
                0,
            ],
        ];
    }

    #[DataProvider('provideAddDirectoryCases')]
    public function testAddDirectory(array $files, bool $createDir, int $expectedCount): void
    {
        $dir = $this->tempDir;

        if (!$createDir) {
            $dir .= '_invalid';
        } else {
            foreach ($files as $filename => $isPhp) {
                $path = $dir . '/' . $filename;
                file_put_contents($path, $isPhp ? '<?php' : 'text');
            }
        }

        $collection = new AutoloadCollection();
        $collection->addDirectory($dir);

        $this->assertCount($expectedCount, $collection->getFilePaths());
    }

    public static function provideAddDirectoryCases(): array
    {
        return [
            'only php files' => [
                [
                    'a.php' => true,
                    'b.php' => true,
                ],
                true,
                2,
            ],
            'mixed files' => [
                [
                    'a.php' => true,
                    'b.txt' => false,
                    'c.php' => true,
                ],
                true,
                2,
            ],
            'no php files' => [
                [
                    'a.txt' => false,
                    'b.md' => false,
                ],
                true,
                0,
            ],
            'invalid directory' => [
                [],
                false,
                0,
            ],
        ];
    }

    #[DataProvider('provideDuplicateCases')]
    public function testNoDuplicates(int $times): void
    {
        $file = $this->tempDir . '/dup.php';
        file_put_contents($file, '<?php');

        $collection = new AutoloadCollection();

        for ($i = 0; $i < $times; $i++) {
            $collection->addFile($file);
        }

        $this->assertCount(1, $collection->getFilePaths());
    }

    public static function provideDuplicateCases(): array
    {
        return [
            'added once' => [1],
            'added twice' => [2],
            'added multiple times' => [5],
        ];
    }
}
