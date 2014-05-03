<?php

declare(strict_types=1);

namespace PhilipRehberger\SafeFileWriter;

use PhilipRehberger\SafeFileWriter\Exceptions\FileReadException;
use PhilipRehberger\SafeFileWriter\Exceptions\FileWriteException;

/**
 * Atomic file operations with locking and temp-file swap.
 */
final class SafeFile
{
    /**
     * Atomically write content to a file using temp-file + rename.
     *
     * @throws FileWriteException
     */
    public static function write(string $path, string $content): void
    {
        $dir = dirname($path);

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw FileWriteException::directoryCreateFailed($dir);
        }

        $tmpFile = tempnam($dir, '.safe_');
        if ($tmpFile === false) {
            throw FileWriteException::tempFileFailed($path);
        }

        try {
            $bytes = file_put_contents($tmpFile, $content, LOCK_EX);
            if ($bytes === false) {
                throw FileWriteException::writeFailed($path);
            }

            // Preserve permissions if the target already exists
            if (file_exists($path)) {
                $perms = fileperms($path);
                if ($perms !== false) {
                    chmod($tmpFile, $perms);
                }
            }

            if (! rename($tmpFile, $path)) {
                throw FileWriteException::renameFailed($tmpFile, $path);
            }
        } catch (FileWriteException $e) {
            // Clean up temp file on failure
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }

            throw $e;
        }
    }

    /**
     * Atomically write JSON data to a file.
     *
     * @throws FileWriteException
     */
    public static function writeJson(string $path, mixed $data, int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): void
    {
        $json = json_encode($data, $flags | JSON_THROW_ON_ERROR);

        self::write($path, $json . "\n");
    }

    /**
     * Append content to a file with exclusive lock.
     *
     * Creates the file if it doesn't exist.
     *
     * @throws FileWriteException
     */
    public static function append(string $path, string $content): void
    {
        $dir = dirname($path);

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw FileWriteException::directoryCreateFailed($dir);
        }

        $result = file_put_contents($path, $content, FILE_APPEND | LOCK_EX);
        if ($result === false) {
            throw FileWriteException::writeFailed($path);
        }
    }

    /**
     * Read file content with a shared lock.
     *
     * @throws FileReadException
     */
    public static function read(string $path): string
    {
        if (! file_exists($path)) {
            throw FileReadException::notFound($path);
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw FileReadException::openFailed($path);
        }

        try {
            if (! flock($handle, LOCK_SH)) {
                throw FileReadException::lockFailed($path);
            }

            $content = stream_get_contents($handle);
            if ($content === false) {
                throw FileReadException::readFailed($path);
            }

            return $content;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * Read and decode a JSON file.
     *
     * @throws FileReadException
     */
    public static function readJson(string $path): mixed
    {
        $content = self::read($path);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Check if a file exists.
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Delete a file if it exists.
     */
    public static function delete(string $path): bool
    {
        if (! file_exists($path)) {
            return false;
        }

        return unlink($path);
    }
}
