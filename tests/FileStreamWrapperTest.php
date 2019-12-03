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
    /**
     * @var string|null
     */
    private $tempFilePath;

    protected function tearDown()
    {
        // Make sure that we restore the default file stream wrapper.
        \stream_wrapper_restore('file');

        $this->deleteTempFile();
    }

    private function registerWrapper(): void
    {
        \stream_wrapper_unregister('file');
        \stream_wrapper_register('file', FileStreamWrapper::class);
    }

    private function createTempFile(bool $registerWrapper = true): void
    {
        $this->tempFilePath = \tempnam(\sys_get_temp_dir(), 'FSW');
        if (false === $this->tempFilePath) {
            throw new RuntimeException('Temporary file could not be created');
        }

        if ($registerWrapper) {
            $this->registerWrapper();
        }
    }

    private function deleteTempFile(): void
    {
        if ($this->tempFilePath === null) {
            return;
        }

        \unlink($this->tempFilePath);
        $this->tempFilePath = null;
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
        $this->createTempFile();

        $this->assertTrue(\touch($this->tempFilePath));
        $this->assertTrue(\touch($this->tempFilePath, \time()));
        $this->assertTrue(\touch($this->tempFilePath, \time(), \time()));
    }

    public function testChown()
    {
        $this->createTempFile(false);

        $stat = \stat($this->tempFilePath);
        $this->assertArrayHasKey('uid', $stat);

        $this->registerWrapper();

        $this->assertIsBool(\chown($this->tempFilePath, $stat['uid']));
    }

    public function testChgrp()
    {
        $this->createTempFile(false);

        $stat = \stat($this->tempFilePath);
        $this->assertArrayHasKey('gid', $stat);

        $this->registerWrapper();

        $this->assertIsBool(\chgrp($this->tempFilePath, $stat['gid']));
    }

    public function testChmod()
    {
        $this->createTempFile();

        $this->assertTrue(\chmod($this->tempFilePath, 0755));
    }

    public function testFlush()
    {
        $this->createTempFile();

        $fp = \fopen($this->tempFilePath, 'r');
        $this->assertTrue(\fflush($fp));
        \fclose($fp);
    }

    public function testSeek()
    {
        $this->createTempFile();

        $fp = \fopen($this->tempFilePath, 'r');
        $this->assertEquals(0, \fseek($fp, 0, SEEK_SET));
        \fclose($fp);
    }

    public function testTruncate()
    {
        $this->createTempFile();

        $fp = \fopen($this->tempFilePath, 'w');
        $this->assertTrue(\ftruncate($fp, 0));
        \fclose($fp);
    }

    public function testWrite()
    {
        $this->createTempFile();

        $fp = \fopen($this->tempFilePath, 'w');
        $this->assertNotFalse(\fwrite($fp, '1234'));
        \fclose($fp);
    }

    public function testLock()
    {
        $this->createTempFile();

        $fp = \fopen($this->tempFilePath, 'w+');
        $this->assertFalse(\stream_supports_lock($fp));
        \fclose($fp);
    }

    public function testSetOption()
    {
        $this->createTempFile();

        $fp = \fopen($this->tempFilePath, 'w+');

        $this->assertIsBool(\stream_set_blocking($fp, true));
        $this->assertIsBool(\stream_set_timeout($fp, 5, 0));
        $this->assertIsNumeric(\stream_set_write_buffer($fp, 2048));

        $this->assertIsBool(\stream_set_blocking($fp, false));
        $this->assertIsNumeric(\stream_set_write_buffer($fp, 0));

        \fclose($fp);
    }

    public function testUnlink()
    {
        $this->createTempFile();

        $this->assertTrue(\unlink($this->tempFilePath));
        $this->tempFilePath = null;
    }

    public function testUrlStat()
    {
        $this->createTempFile();

        $this->assertIsArray(\stat($this->tempFilePath));
        $this->assertIsArray(\lstat($this->tempFilePath));
    }
}
