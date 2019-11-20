<?php
/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

class FileStreamWrapper
{
    /**
     * @var resource
     */
    public $context;

    /**
     * @var resource|null
     */
    private $resource;

    /**
     * Close directory handle.
     *
     * @return bool
     */
    public function dir_closedir(): bool
    {
        stream_wrapper_restore('file');

        closedir($this->resource);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return true;
    }

    /**
     * Open directory handle.
     *
     * @param string $path
     * @param int    $options
     *
     * @return bool
     */
    public function dir_opendir(string $path, int $options): bool
    {
        stream_wrapper_restore('file');

        if (is_resource($this->context)) {
            $this->resource = opendir($path, $this->context);
        } else {
            $this->resource = opendir($path);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return is_resource($this->resource);
    }

    /**
     * Read entry from directory handle.
     *
     * @return false|string
     */
    public function dir_readdir()
    {
        stream_wrapper_restore('file');

        $r = readdir($this->resource);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Rewind directory handle.
     *
     * @return bool
     */
    public function dir_rewinddir(): bool
    {
        stream_wrapper_restore('file');

        rewinddir($this->resource);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int    $mode
     * @param int    $options
     *
     * @return bool
     */
    public function mkdir(string $path, int $mode, int $options): bool
    {
        stream_wrapper_restore('file');

        $recursive = $options & STREAM_MKDIR_RECURSIVE ? true : false;
        if (is_resource($this->context)) {
            $r = mkdir($path, $mode, $recursive, $this->context);
        } else {
            $r = mkdir($path, $mode, $recursive);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Rename a file or directory.
     *
     * @param string $path_from
     * @param string $path_to
     *
     * @return bool
     */
    public function rename(string $path_from, string $path_to): bool
    {
        stream_wrapper_restore('file');

        if (is_resource($this->context)) {
            $r = rename($path_from, $path_to, $this->context);
        } else {
            $r = rename($path_from, $path_to);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Remove a directory.
     *
     * @param string $path
     * @param int    $options
     *
     * @return bool
     */
    public function rmdir(string $path, int $options): bool
    {
        stream_wrapper_restore('file');

        if (is_resource($this->context)) {
            $r = rmdir($path, $this->context);
        } else {
            $r = rmdir($path);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Retrieve the underlaying resource.
     *
     * @param int $cast_as
     *
     * @return false|resource
     */
    public function stream_cast(int $cast_as)
    {
        if (is_resource($this->resource)) {
            return $this->resource;
        }

        return false;
    }

    /**
     * Close a resource.
     */
    public function stream_close(): void
    {
        stream_wrapper_restore('file');

        fclose($this->resource);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);
    }

    /**
     * Test for end-of-file on a file pointer.
     *
     * @return bool
     */
    public function stream_eof(): bool
    {
        stream_wrapper_restore('file');

        $r = feof($this->resource);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Flush the output
     *
     * @return bool
     */
    public function stream_flush(): bool
    {
        stream_wrapper_restore('file');

        $r = fflush($this->resource);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Advisory file locking.
     *
     * @param int $operation
     *
     * @return bool
     */
    public function stream_lock(int $operation): bool
    {
        stream_wrapper_restore('file');

        // TODO Third param of flock ?
        $r = flock($this->resource, $operation);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Change stream metadata.
     *
     * @param string $path
     * @param int    $option
     * @param mixed  $value
     *
     * @return bool
     */
    public function stream_metadata(string $path, int $option, $value): bool
    {
        stream_wrapper_restore('file');

        $r = false;
        switch ($option) {
            case STREAM_META_TOUCH:
                $r = touch($path, $value[0], $value[1]);
                break;
            case STREAM_META_OWNER_NAME:
            case STREAM_META_OWNER:
                $r = chown($path, $value);
                break;
            case STREAM_META_GROUP_NAME:
            case STREAM_META_GROUP:
                $r = chgrp($path, $value);
                break;
            case STREAM_META_ACCESS:
                $r = chmod($path, $value);
                break;
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Open file or URL.
     *
     * @param string      $path
     * @param string      $mode
     * @param int         $options
     * @param string|null $opened_path
     *
     * @return bool
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        stream_wrapper_restore('file');

        $usePath = $options & STREAM_USE_PATH ? true : false;
        $reportErrors = $options & STREAM_REPORT_ERRORS ? true : false;

        // TODO Implement error reporting as well as opened_path.

        $functionCallMappings = Override::getFunctionMappings($path);

        // Replace the global function calls into local function calls.
        if (!empty($functionCallMappings)) {
            if (is_resource($this->context)) {
                $source = file_get_contents($path, $usePath, $this->context);
            } else {
                $source = file_get_contents($path, $usePath);
            }

            $source = Override::getFQFCConverter()->convert($source, $functionCallMappings);

            $this->resource = tmpfile();
            fwrite($this->resource, $source);
            fseek($this->resource, 0);
        } elseif (is_resource($this->context)) {
            $this->resource = fopen($path, $mode, $usePath, $this->context);
        } else {
            $this->resource = fopen($path, $mode, $usePath);
        }


        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return is_resource($this->resource) ? true : false;
    }

    /**
     * Read from stream.
     *
     * @param int $count
     *
     * @return string
     */
    public function stream_read(int $count): string
    {
        stream_wrapper_restore('file');

        $r = fgets($this->resource, $count);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        if (!is_string($r)) {
            return '';
        }


        return $r;
    }

    /**
     * Seek to specific location in a stream.
     *
     * @param int $offset
     * @param int $whence
     *
     * @return bool
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        stream_wrapper_restore('file');

        $r = fseek($this->resource, $offset, $whence);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r < 0 ? false : true;
    }

    /**
     * Change stream options.
     *
     * @param int $option
     * @param int $arg1
     * @param int $arg2
     *
     * @return bool
     */
    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        // TODO Implement this.

        return false;
    }

    /**
     * Retrieve information about a file resource.
     *
     * @return array
     */
    public function stream_stat(): array
    {
        stream_wrapper_restore('file');

        $r = fstat($this->resource);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Retrieve the current position of a stream.
     *
     * @return int
     */
    public function stream_tell(): int
    {
        stream_wrapper_restore('file');

        $r = fseek($this->resource, 0, SEEK_CUR);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Truncate stream.
     *
     * @param int $new_size
     *
     * @return bool
     */
    public function stream_truncate(int $new_size): bool
    {
        stream_wrapper_restore('file');

        $r = ftruncate($this->resource, $new_size);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Write to stream.
     *
     * @param string $data
     *
     * @return int
     */
    public function stream_write(string $data): int
    {
        stream_wrapper_restore('file');

        $r = fwrite($this->resource, $data);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        if (is_int($r)) {
            return $r;
        }

        return 0;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function unlink(string $path): bool
    {
        stream_wrapper_restore('file');

        $r = unlink($path);

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Retrieve information about a file.
     *
     * @param string $path
     * @param int    $flags
     *
     * @return array|false
     */
    public function url_stat(string $path, int $flags)
    {
        stream_wrapper_restore('file');

        $urlStatLink = $flags & STREAM_URL_STAT_LINK ? true : false;
        $urlStatQuiet = $flags & STREAM_URL_STAT_QUIET ? true : false;

        if ($urlStatLink) {
            $r = $urlStatQuiet ? @lstat($path) : lstat($path);
        } else {
            $r = $urlStatQuiet ? @stat($path) : stat($path);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }
}
