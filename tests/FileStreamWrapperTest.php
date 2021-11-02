<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override\Tests;

use AdrianSuter\Autoload\Override\FileStreamWrapper;
use InvalidArgumentException;
use PHPUnit\Framework\IncompleteTestError;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

use function chgrp;
use function chmod;
use function chown;
use function closedir;
use function fclose;
use function fflush;
use function file_exists;
use function fopen;
use function fseek;
use function ftruncate;
use function fwrite;
use function is_dir;
use function is_resource;
use function is_string;
use function lstat;
use function mkdir;
use function opendir;
use function readdir;
use function rename;
use function rewinddir;
use function rmdir;
use function stat;
use function stream_set_blocking;
use function stream_set_timeout;
use function stream_set_write_buffer;
use function stream_supports_lock;
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;
use function sys_get_temp_dir;
use function tempnam;
use function time;
use function touch;
use function unlink;

use const STREAM_META_ACCESS;
use const STREAM_META_GROUP;
use const STREAM_META_OWNER;

final class FileStreamWrapperTest extends TestCase
{
    /**
     * @var string|null
     */
    private $tempFilePath;

    protected function tearDown(): void
    {
        // Make sure that we restore the default file stream wrapper.
        @stream_wrapper_restore('file');

        $this->deleteTempFile();
    }

    private function registerWrapper(): void
    {
        stream_wrapper_unregister('file');
        stream_wrapper_register('file', FileStreamWrapper::class);
    }

    private function createTempFile(bool $registerWrapper = true): void
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'FSW');
        if (false === $tempFilePath) {
            throw new RuntimeException('Temporary file could not be created');
        }
        $this->tempFilePath = $tempFilePath;

        if ($registerWrapper) {
            $this->registerWrapper();
        }
    }

    private function deleteTempFile(): void
    {
        if ($this->tempFilePath === null) {
            return;
        }

        unlink($this->tempFilePath);
        $this->tempFilePath = null;
    }

    public function testDir(): void
    {
        $this->registerWrapper();

        $fp = opendir(__DIR__);
        $this->assertTrue(is_resource($fp));
        if (!is_resource($fp)) {
            throw new IncompleteTestError();
        }

        $item = readdir($fp);
        $this->assertTrue(is_string($item));

        rewinddir($fp);
        closedir($fp);
    }

    public function testDirOpenDirWithoutContext(): void
    {
        $fileStreamWrapper = new FileStreamWrapper();
        $this->assertTrue($fileStreamWrapper->dir_opendir(__DIR__, 0));

        // Close the directory stream.
        $fileStreamWrapper->dir_closedir();
    }

    public function testMkdirRenameRmdir(): void
    {
        $directory = sys_get_temp_dir() . '/fileStreamWrapper';
        $directory2 = sys_get_temp_dir() . '/fileStreamWrapper2';

        if (file_exists($directory) && is_dir($directory)) {
            rmdir($directory);
        }

        if (file_exists($directory2) && is_dir($directory2)) {
            rmdir($directory2);
        }

        $this->registerWrapper();

        $this->assertTrue(mkdir($directory, 0755, false));
        $this->assertTrue(rename($directory, $directory2));
        $this->assertTrue(rmdir($directory2));
    }

    public function testMkdirRenameRmdirWithoutContext(): void
    {
        $directory = sys_get_temp_dir() . '/fileStreamWrapper';
        $directory2 = sys_get_temp_dir() . '/fileStreamWrapper2';

        if (file_exists($directory) && is_dir($directory)) {
            rmdir($directory);
        }

        if (file_exists($directory2) && is_dir($directory2)) {
            rmdir($directory2);
        }

        $fileStreamWrapper = new FileStreamWrapper();
        $this->assertTrue($fileStreamWrapper->mkdir($directory, 0755, STREAM_MKDIR_RECURSIVE));
        $this->assertTrue($fileStreamWrapper->rename($directory, $directory2));
        $this->assertTrue($fileStreamWrapper->rmdir($directory2, 0));
    }

    public function testStreamCast(): void
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

    public function testTouch(): void
    {
        $this->createTempFile();

        $this->assertTrue(touch($this->tempFilePath));
        $this->assertTrue(touch($this->tempFilePath, time()));
        $this->assertTrue(touch($this->tempFilePath, time(), time()));
    }

    public function testChown(): void
    {
        $this->createTempFile(false);

        $stat = stat($this->tempFilePath);
        $this->assertArrayHasKey('uid', $stat);

        $this->registerWrapper();

        $this->assertIsBool(chown($this->tempFilePath, $stat['uid']));
    }

    public function testChgrp(): void
    {
        $this->createTempFile(false);

        $stat = stat($this->tempFilePath);
        $this->assertArrayHasKey('gid', $stat);

        $this->registerWrapper();

        $this->assertIsBool(chgrp($this->tempFilePath, $stat['gid']));
    }

    public function testChmod(): void
    {
        $this->createTempFile();

        $this->assertTrue(chmod($this->tempFilePath, 0755));
    }

    public function testStreamMetaDataMetaOwnerWithInvalidThirdParameter(): void
    {
        $fileStreamWrapper = new FileStreamWrapper();
        $this->createTempFile(false);

        $this->expectException(InvalidArgumentException::class);
        $fileStreamWrapper->stream_metadata((string)$this->tempFilePath, STREAM_META_OWNER, [null]);
    }

    public function testStreamMetaDataMetaGroupWithInvalidThirdParameter(): void
    {
        $fileStreamWrapper = new FileStreamWrapper();
        $this->createTempFile(false);

        $this->expectException(InvalidArgumentException::class);
        $fileStreamWrapper->stream_metadata((string)$this->tempFilePath, STREAM_META_GROUP, [null]);
    }

    public function testStreamMetaDataMetaAccessWithInvalidThirdParameter(): void
    {
        $fileStreamWrapper = new FileStreamWrapper();
        $this->createTempFile(false);

        $this->expectException(InvalidArgumentException::class);
        $fileStreamWrapper->stream_metadata((string)$this->tempFilePath, STREAM_META_ACCESS, [null]);
    }

    public function testFlush(): void
    {
        $this->createTempFile();

        $fp = fopen($this->tempFilePath, 'r');
        $this->assertTrue(fflush($fp));
        fclose($fp);
    }

    public function testSeek(): void
    {
        $this->createTempFile();

        $fp = fopen($this->tempFilePath, 'r');
        $this->assertEquals(0, fseek($fp, 0, SEEK_SET));
        fclose($fp);
    }

    public function testTruncate(): void
    {
        $this->createTempFile();

        $fp = fopen($this->tempFilePath, 'w');
        $this->assertTrue(ftruncate($fp, 0));
        fclose($fp);
    }

    public function testWrite(): void
    {
        $this->createTempFile();

        $fp = fopen($this->tempFilePath, 'w');
        $this->assertNotFalse(fwrite($fp, '1234'));
        fclose($fp);
    }

    public function testLock(): void
    {
        $this->createTempFile();

        $fp = fopen($this->tempFilePath, 'w+');
        $this->assertFalse(stream_supports_lock($fp));
        fclose($fp);
    }

    public function testSetOption(): void
    {
        $this->createTempFile();

        $fp = fopen($this->tempFilePath, 'w+');

        $this->assertIsBool(stream_set_blocking($fp, true));
        $this->assertIsBool(stream_set_timeout($fp, 5, 0));
        $this->assertIsNumeric(stream_set_write_buffer($fp, 2048));

        $this->assertIsBool(stream_set_blocking($fp, false));
        $this->assertIsNumeric(stream_set_write_buffer($fp, 0));

        fclose($fp);
    }

    public function testUnlink(): void
    {
        $this->createTempFile();

        $this->assertTrue(unlink($this->tempFilePath));
        $this->tempFilePath = null;
    }

    public function testUrlStat(): void
    {
        $this->createTempFile();

        $this->assertIsArray(stat($this->tempFilePath));
        $this->assertIsArray(lstat($this->tempFilePath));
    }
}
