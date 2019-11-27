<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

use AdrianSuter\Autoload\Override\FileStreamWrapper;
use PHPUnit\Framework\TestCase;

final class FileStreamWrapperTest extends TestCase
{
    protected function tearDown()
    {
        // Make sure that we restore the default file stream wrapper.
        \stream_wrapper_restore('file');
    }

    private function registerWrapper(): void
    {
        stream_wrapper_unregister('file');
        stream_wrapper_register('file', FileStreamWrapper::class);
    }

    private function createTempFile(bool $registerWrapper = true): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'FSW');

        if ($registerWrapper) {
            $this->registerWrapper();
        }

        return $filePath;
    }

    private function deleteTempFile(string $filePath): void
    {
        \stream_wrapper_restore('file');
        \unlink($filePath);
    }

    public function testDir()
    {
        $this->registerWrapper();

        $fp = \opendir(__DIR__);
        $this->assertTrue(\is_resource($fp));

        $item = \readdir($fp);
        $this->assertTrue(\is_string($item));

        \rewinddir($fp);
        \closedir($fp);
    }

    public function testDirOpenDirWithoutContext()
    {
        $fileStreamWrapper = new FileStreamWrapper();
        $this->assertTrue($fileStreamWrapper->dir_opendir(__DIR__, 0));

        // Close the directory stream.
        $fileStreamWrapper->dir_closedir();
    }

    public function testMkdirRenameRmdir()
    {
        $directory = \sys_get_temp_dir() . '/fileStreamWrapper';
        $directory2 = \sys_get_temp_dir() . '/fileStreamWrapper2';

        if (\file_exists($directory) && \is_dir($directory)) {
            \rmdir($directory);
        }

        if (\file_exists($directory2) && \is_dir($directory2)) {
            \rmdir($directory2);
        }

        $this->registerWrapper();

        $this->assertTrue(\mkdir($directory, 0755, false));
        $this->assertTrue(\rename($directory, $directory2));
        $this->assertTrue(\rmdir($directory2));
    }

    public function testMkdirRenameRmdirWithoutContext()
    {
        $directory = \sys_get_temp_dir() . '/fileStreamWrapper';
        $directory2 = \sys_get_temp_dir() . '/fileStreamWrapper2';

        if (\file_exists($directory) && \is_dir($directory)) {
            \rmdir($directory);
        }

        if (\file_exists($directory2) && \is_dir($directory2)) {
            \rmdir($directory2);
        }

        $fileStreamWrapper = new FileStreamWrapper();
        $this->assertTrue($fileStreamWrapper->mkdir($directory, 0755, STREAM_MKDIR_RECURSIVE));
        $this->assertTrue($fileStreamWrapper->rename($directory, $directory2));
        $this->assertTrue($fileStreamWrapper->rmdir($directory2, 0));
    }

    public function testStreamCast()
    {
        $fileStreamWrapper = new FileStreamWrapper();
        $this->assertFalse($fileStreamWrapper->stream_cast(0));

        $resource = \fopen('php://temp', 'r+');

        $prop = new ReflectionProperty(FileStreamWrapper::class, 'resource');
        $prop->setAccessible(true);
        $prop->setValue($fileStreamWrapper, $resource);

        $this->assertEquals($resource, $fileStreamWrapper->stream_cast(0));

        \fclose($resource);
    }

    public function testTouch()
    {
        $filePath = $this->createTempFile();

        $this->assertTrue(\touch($filePath));
        $this->assertTrue(\touch($filePath, \time()));
        $this->assertTrue(\touch($filePath, \time(), \time()));

        $this->deleteTempFile($filePath);
    }

    public function testChown()
    {
        $filePath = $this->createTempFile(false);

        $stat = \stat($filePath);
        $this->assertArrayHasKey('uid', $stat);

        $this->registerWrapper();

        $this->assertIsBool(chown($filePath, $stat['uid']));

        $this->deleteTempFile($filePath);
    }

    public function testChgrp()
    {
        $filePath = $this->createTempFile(false);

        $stat = \stat($filePath);
        $this->assertArrayHasKey('gid', $stat);

        $this->registerWrapper();

        $this->assertIsBool(chgrp($filePath, $stat['gid']));

        $this->deleteTempFile($filePath);
    }

    public function testChmod()
    {
        $filePath = $this->createTempFile();

        $this->assertTrue(\chmod($filePath, 0755));

        $this->deleteTempFile($filePath);
    }

    public function testFlush()
    {
        $filePath = $this->createTempFile();

        $fp = \fopen($filePath, 'r');
        $this->assertTrue(\fflush($fp));
        \fclose($fp);

        $this->deleteTempFile($filePath);
    }

    public function testSeek()
    {
        $filePath = $this->createTempFile();

        $fp = \fopen($filePath, 'r');
        $this->assertEquals(0, \fseek($fp, 0, SEEK_SET));
        \fclose($fp);

        $this->deleteTempFile($filePath);
    }

    public function testTruncate()
    {
        $filePath = $this->createTempFile();

        $fp = \fopen($filePath, 'w');
        $this->assertTrue(\ftruncate($fp, 0));
        \fclose($fp);

        $this->deleteTempFile($filePath);
    }

    public function testWrite()
    {
        $filePath = $this->createTempFile();

        $fp = \fopen($filePath, 'w');
        $this->assertNotFalse(\fwrite($fp, '1234'));
        \fclose($fp);

        $this->deleteTempFile($filePath);
    }

    public function testX()
    {
        $filePath = $this->createTempFile();

        $fp = \fopen($filePath, 'w+');

        $this->assertTrue(\stream_supports_lock($fp));
        // $this->assertFalse(\stream_set_blocking($fp, true));

        \flock($fp, LOCK_SH);
        \flock($fp, LOCK_EX);

        \stream_set_timeout($fp, 5, 0);
        \stream_set_write_buffer($fp, 2048);

        \stream_set_blocking($fp, false);
        \stream_set_write_buffer($fp, 0);

        \fclose($fp);

        $this->deleteTempFile($filePath);
    }
}
