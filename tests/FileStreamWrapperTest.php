<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\Autoload\Override\FileStreamWrapper;
use PHPUnit\Framework\TestCase;

class FileStreamWrapperTest extends TestCase
{
    protected function tearDown()
    {
        stream_wrapper_restore('file');
    }

    public function testDir()
    {
        stream_wrapper_unregister('file');
        stream_wrapper_register('file', FileStreamWrapper::class);

        $fp = opendir('file://' . __DIR__);
        $this->assertTrue(is_resource($fp));

        $f = readdir($fp);
        $this->assertTrue(is_string($f));

        rewinddir($fp);
        closedir($fp);
    }

    public function testDirOpenDirWithoutContext()
    {
        $fileStreamWrapper = new FileStreamWrapper();
        $this->assertTrue($fileStreamWrapper->dir_opendir(__DIR__, 0));

        // Close the directory stream.
        $fileStreamWrapper->dir_closedir();
    }

    public function testCrudDir()
    {
        $directory = sys_get_temp_dir() . '/fileStreamWrapper';
        $directory2 = sys_get_temp_dir() . '/fileStreamWrapper2';

        if (file_exists($directory) && is_dir($directory)) {
            rmdir($directory);
        }

        if (file_exists($directory2) && is_dir($directory2)) {
            rmdir($directory2);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', FileStreamWrapper::class);

        $this->assertTrue(mkdir('file://' . $directory, 0777, false));
        $this->assertTrue(rename('file://' . $directory, 'file://' . $directory2));
        $this->assertTrue(rmdir('file://' . $directory2));
    }

    public function testStreamCast()
    {
        $fileStreamWrapper = new FileStreamWrapper();
        $this->assertFalse($fileStreamWrapper->stream_cast(0));

        $resource = fopen('php://temp', 'r+');

        $prop = new ReflectionProperty(FileStreamWrapper::class, 'resource');
        $prop->setAccessible(true);
        $prop->setValue($fileStreamWrapper, $resource);

        $this->assertEquals($resource, $fileStreamWrapper->stream_cast(0));

        fclose($resource);
    }
}
