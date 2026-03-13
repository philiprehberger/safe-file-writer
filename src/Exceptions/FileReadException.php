<?php

declare(strict_types=1);

namespace PhilipRehberger\SafeFileWriter\Exceptions;

use RuntimeException;

class FileReadException extends RuntimeException
{
    public static function notFound(string $path): self
    {
        return new self("File not found: '{$path}'.");
    }

    public static function openFailed(string $path): self
    {
        return new self("Failed to open file: '{$path}'.");
    }

    public static function lockFailed(string $path): self
    {
        return new self("Failed to acquire lock on: '{$path}'.");
    }

    public static function readFailed(string $path): self
    {
        return new self("Failed to read file: '{$path}'.");
    }
}
