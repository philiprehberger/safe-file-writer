<?php

declare(strict_types=1);

namespace PhilipRehberger\SafeFileWriter;

use PhilipRehberger\SafeFileWriter\Exceptions\FileWriteException;

/**
 * Manages timestamped file backups.
 */
final class BackupManager
{
    /**
     * Create a timestamped backup of an existing file.
     *
     * Returns the backup path, or null if the source file doesn't exist.
     *
     * @param string $path Path to the file to back up.
     * @param string|null $backupDir Optional directory to store the backup. Defaults to the same directory as the source.
     *
     * @throws FileWriteException
     *
     * @return string|null The backup file path, or null if the source doesn't exist.
     */
    public static function backup(string $path, ?string $backupDir = null): ?string
    {
        if (! file_exists($path)) {
            return null;
        }

        $timestamp = date('Y-m-d\THis');
        $basename = basename($path);
        $targetDir = $backupDir ?? dirname($path);

        if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            throw FileWriteException::directoryCreateFailed($targetDir);
        }

        $backupPath = $targetDir . '/' . $basename . '.' . $timestamp . '.bak';

        if (! copy($path, $backupPath)) {
            throw FileWriteException::writeFailed($backupPath);
        }

        return $backupPath;
    }
}
