<?php

/**
 * PHP Autoload Override (https://github.com/adriansuter/php-autoload-override)
 *
 * @license https://github.com/adriansuter/php-autoload-override/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace AdrianSuter\Autoload\Override;

use RuntimeException;

use function chgrp;
use function chmod;
use function chown;
use function closedir;
use function fclose;
use function feof;
use function fflush;
use function fgets;
use function file_get_contents;
use function fopen;
use function fseek;
use function fstat;
use function ftruncate;
use function fwrite;
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
use function stream_wrapper_register;
use function stream_wrapper_restore;
use function stream_wrapper_unregister;
use function touch;
use function unlink;

// phpcs:disable PSR1.Methods.CamelCapsMethodName
class FileStreamWrapper
{
    /**
     * @var resource|null
     */
    public $context;

    /**
     * @var resource|null|false
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

        if (is_resource($this->resource)) {
            closedir($this->resource);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return true;
    }

    /**
     * Open directory handle.
     *
     * @param string $path
     * @param int $options
     *
     * @return bool
     * @noinspection PhpUnusedParameterInspection
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
     * @noinspection PhpUnused
     */
    public function dir_readdir()
    {
        stream_wrapper_restore('file');

        $r = false;
        if (is_resource($this->resource)) {
            $r = readdir($this->resource);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Rewind directory handle.
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function dir_rewinddir(): bool
    {
        stream_wrapper_restore('file');

        if (is_resource($this->resource)) {
            rewinddir($this->resource);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int $mode
     * @param int $options
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
     * @param int $options
     *
     * @return bool
     * @noinspection PhpUnusedParameterInspection
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
     * Retrieve the underlying resource.
     *
     * @param int $cast_as
     *
     * @return false|resource
     * @noinspection PhpUnusedParameterInspection
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
     * @noinspection PhpUnused
     */
    public function stream_close(): void
    {
        stream_wrapper_restore('file');

        if (is_resource($this->resource)) {
            fclose($this->resource);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);
    }

    /**
     * Test for end-of-file on a file pointer.
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function stream_eof(): bool
    {
        stream_wrapper_restore('file');

        $r = false;
        if (is_resource($this->resource)) {
            $r = feof($this->resource);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Flush the output
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function stream_flush(): bool
    {
        stream_wrapper_restore('file');

        $r = false;
        if (is_resource($this->resource)) {
            $r = fflush($this->resource);
        }

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
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function stream_lock(int $operation): bool
    {
        return false;
    }

    /**
     * Change stream metadata.
     *
     * @param string $path
     * @param int $option
     * @param mixed $value
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function stream_metadata(string $path, int $option, $value): bool
    {
        stream_wrapper_restore('file');

        $r = false;
        switch ($option) {
            case STREAM_META_TOUCH:
                if (!isset($value[0]) || is_null($value[0])) {
                    $r = touch($path);
                } else {
                    $r = touch($path, $value[0], $value[1]);
                }
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
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param string|null $opened_path
     *
     * @return bool
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        stream_wrapper_restore('file');

        $usePath = $options & STREAM_USE_PATH ? true : false;
        // $reportErrors = $options & STREAM_REPORT_ERRORS ? true : false;

        // TODO Implement error reporting as well as opened_path.

        $functionCallMap = Override::getFunctionCallMap($path);

        // Replace the global function calls into local function calls.
        if (!empty($functionCallMap)) {
            $source = file_get_contents($path, $usePath);
            if (!is_string($source)) {
                throw new RuntimeException(sprintf("File `%s` could not be loaded.", $path));
            }
            $source = Override::convert($source, $functionCallMap);

            $this->resource = fopen('php://temp', 'w+');
            if (is_resource($this->resource)) {
                fwrite($this->resource, $source);
                fseek($this->resource, 0);
            }
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
     * @noinspection PhpUnused
     */
    public function stream_read(int $count): string
    {
        stream_wrapper_restore('file');

        $r = false;
        if (is_resource($this->resource)) {
            $r = fgets($this->resource, $count);
        }

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
     * @noinspection PhpUnused
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        stream_wrapper_restore('file');

        $r = -1;
        if (is_resource($this->resource)) {
            $r = fseek($this->resource, $offset, $whence);
        }

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
     * @return bool|int
     * @noinspection PhpUnused
     */
    public function stream_set_option(int $option, int $arg1, ?int $arg2)
    {
        stream_wrapper_restore('file');

        $r = false;
        switch ($option) {
            case STREAM_OPTION_BLOCKING:
                if (is_resource($this->resource)) {
                    $r = stream_set_blocking($this->resource, $arg1 ? true : false);
                }
                break;

            case STREAM_OPTION_READ_TIMEOUT:
                if (is_resource($this->resource)) {
                    $r = stream_set_timeout($this->resource, $arg1, $arg2);
                }
                break;

            case STREAM_OPTION_WRITE_BUFFER:
                switch ($arg1) {
                    case STREAM_BUFFER_NONE:
                        if (is_resource($this->resource)) {
                            $r = stream_set_write_buffer($this->resource, 0);
                        }
                        break;

                    case STREAM_BUFFER_FULL:
                        if (is_resource($this->resource) && is_int($arg2)) {
                            $r = stream_set_write_buffer($this->resource, $arg2);
                        }
                        break;
                }
                break;
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Retrieve information about a file resource.
     *
     * @return array<int|string, int>
     * @noinspection PhpUnused
     */
    public function stream_stat(): array
    {
        stream_wrapper_restore('file');

        $r = [];
        if (is_resource($this->resource)) {
            $r = fstat($this->resource);
            if (!is_array($r)) {
                $r = [];
            }
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Retrieve the current position of a stream.
     *
     * @return int
     * @noinspection PhpUnused
     */
    public function stream_tell(): int
    {
        stream_wrapper_restore('file');

        $r = -1;
        if (is_resource($this->resource)) {
            $r = fseek($this->resource, 0, SEEK_CUR);
        }

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
     * @noinspection PhpUnused
     */
    public function stream_truncate(int $new_size): bool
    {
        stream_wrapper_restore('file');

        $r = false;
        if (is_resource($this->resource)) {
            $r = ftruncate($this->resource, $new_size);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
    }

    /**
     * Write to stream.
     *
     * @param string $data
     *
     * @return int|false
     * @noinspection PhpUnused
     */
    public function stream_write(string $data)
    {
        stream_wrapper_restore('file');

        $r = false;
        if (is_resource($this->resource)) {
            $r = fwrite($this->resource, $data);
        }

        stream_wrapper_unregister('file');
        stream_wrapper_register('file', self::class);

        return $r;
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
     * @param int $flags
     *
     * @return array<int|string, int>|false
     * @noinspection PhpUnused
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
