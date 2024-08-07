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

    /**
     * Back up an existing file, then atomically write new content.
     *
     * @param string $path Target file path.
     * @param string $content Content to write.
     * @param string|null $backupDir Optional backup directory.
     *
     * @throws FileWriteException
     */
    public static function writeWithBackup(string $path, string $content, ?string $backupDir = null): void
    {
        BackupManager::backup($path, $backupDir);

        self::write($path, $content);
    }

    /**
     * Back up an existing file, then atomically write JSON data.
     *
     * @param string $path Target file path.
     * @param mixed $data Data to encode as JSON.
     * @param string|null $backupDir Optional backup directory.
     *
     * @throws FileWriteException
     */
    public static function writeJsonWithBackup(string $path, mixed $data, ?string $backupDir = null): void
    {
        BackupManager::backup($path, $backupDir);

        self::writeJson($path, $data);
    }

    /**
     * Compute the checksum of a file.
     *
     * @param string $path Path to the file.
     * @param string $algo Hash algorithm (default: sha256).
     *
     * @throws FileReadException
     *
     * @return string The hex-encoded hash.
     */
    public static function checksum(string $path, string $algo = 'sha256'): string
    {
        return Checksum::compute($path, $algo);
    }

    /**
     * Verify that a file matches an expected checksum.
     *
     * @param string $path Path to the file.
     * @param string $expected The expected hex-encoded hash.
     * @param string $algo Hash algorithm (default: sha256).
     *
     * @throws FileReadException
     */
    public static function verifyChecksum(string $path, string $expected, string $algo = 'sha256'): bool
    {
        return Checksum::verify($path, $expected, $algo);
    }

    /**
     * Atomically write multiple files. Writes all to temp files first, then renames all.
     *
     * If any rename fails, previously renamed files are rolled back from their temp backups.
     *
     * @param array<string, string> $files Associative array of path => content.
     *
     * @throws FileWriteException
     */
    public static function writeMany(array $files): void
    {
        if ($files === []) {
            return;
        }

        /** @var array<string, string> $tempFiles Map of target path => temp file path */
        $tempFiles = [];

        /** @var array<string, string> $backups Map of target path => backup temp file path (for existing files) */
        $backups = [];

        try {
            // Phase 1: Write all content to temp files
            foreach ($files as $path => $content) {
                $dir = dirname($path);

                if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                    throw FileWriteException::directoryCreateFailed($dir);
                }

                $tmpFile = tempnam($dir, '.safe_');
                if ($tmpFile === false) {
                    throw FileWriteException::tempFileFailed($path);
                }

                $bytes = file_put_contents($tmpFile, $content, LOCK_EX);
                if ($bytes === false) {
                    unlink($tmpFile);

                    throw FileWriteException::writeFailed($path);
                }

                // Preserve permissions if the target already exists
                if (file_exists($path)) {
                    $perms = fileperms($path);
                    if ($perms !== false) {
                        chmod($tmpFile, $perms);
                    }
                }

                $tempFiles[$path] = $tmpFile;
            }

            // Phase 2: Back up existing files, then rename all temp files to targets
            /** @var array<string, string> $renamed Track successfully renamed files */
            $renamed = [];

            foreach ($tempFiles as $path => $tmpFile) {
                // Back up existing file before overwriting
                if (file_exists($path)) {
                    $backupTmp = tempnam(dirname($path), '.bak_');
                    if ($backupTmp !== false && copy($path, $backupTmp)) {
                        $backups[$path] = $backupTmp;
                    }
                }

                if (! rename($tmpFile, $path)) {
                    // Rollback previously renamed files
                    foreach ($renamed as $renamedPath => $originalTmp) {
                        if (isset($backups[$renamedPath]) && file_exists($backups[$renamedPath])) {
                            rename($backups[$renamedPath], $renamedPath);
                        }
                    }

                    // Clean up remaining temp files
                    foreach ($tempFiles as $targetPath => $tf) {
                        if (! isset($renamed[$targetPath]) && $targetPath !== $path && file_exists($tf)) {
                            unlink($tf);
                        }
                    }

                    // Clean up the failed temp file
                    if (file_exists($tmpFile)) {
                        unlink($tmpFile);
                    }

                    // Clean up remaining backup files
                    foreach ($backups as $backupPath => $bf) {
                        if (! isset($renamed[$backupPath]) && file_exists($bf)) {
                            unlink($bf);
                        }
                    }

                    throw FileWriteException::renameFailed($tmpFile, $path);
                }

                $renamed[$path] = $tmpFile;
            }

            // Phase 3: Clean up backup files on success
            foreach ($backups as $bf) {
                if (file_exists($bf)) {
                    unlink($bf);
                }
            }
        } catch (FileWriteException $e) {
            // Clean up any remaining temp files
            foreach ($tempFiles as $path => $tf) {
                if (file_exists($tf)) {
                    unlink($tf);
                }
            }

            // Clean up any remaining backup files
            foreach ($backups as $bf) {
                if (file_exists($bf)) {
                    unlink($bf);
                }
            }

            throw $e;
        }
    }
}
