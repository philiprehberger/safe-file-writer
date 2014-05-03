<?php

declare(strict_types=1);

namespace PhilipRehberger\SafeFileWriter\Exceptions;

use RuntimeException;

class FileWriteException extends RuntimeException
{
    public static function directoryCreateFailed(string $dir): self
    {
        return new self("Failed to create directory: '{$dir}'.");
    }

    public static function tempFileFailed(string $path): self
    {
        return new self("Failed to create temp file for: '{$path}'.");
    }

    public static function writeFailed(string $path): self
    {
        return new self("Failed to write to file: '{$path}'.");
    }

    public static function renameFailed(string $from, string $to): self
    {
        return new self("Failed to rename '{$from}' to '{$to}'.");
    }
}
