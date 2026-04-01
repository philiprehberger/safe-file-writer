<?php

declare(strict_types=1);

namespace PhilipRehberger\SafeFileWriter;

use PhilipRehberger\SafeFileWriter\Exceptions\FileReadException;

/**
 * File checksum computation and verification.
 */
final class Checksum
{
    /**
     * Compute the hash of a file.
     *
     * @param string $path Path to the file.
     * @param string $algo Hash algorithm (default: sha256).
     *
     * @throws FileReadException
     *
     * @return string The hex-encoded hash.
     */
    public static function compute(string $path, string $algo = 'sha256'): string
    {
        if (! file_exists($path)) {
            throw FileReadException::notFound($path);
        }

        $hash = hash_file($algo, $path);

        if ($hash === false) {
            throw FileReadException::readFailed($path);
        }

        return $hash;
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
    public static function verify(string $path, string $expected, string $algo = 'sha256'): bool
    {
        return hash_equals($expected, self::compute($path, $algo));
    }

    /**
     * Compare checksums of two files.
     *
     * @param string $pathA Path to the first file.
     * @param string $pathB Path to the second file.
     * @param string $algo Hash algorithm (default: sha256).
     *
     * @throws FileReadException
     */
    public static function compareFiles(string $pathA, string $pathB, string $algo = 'sha256'): bool
    {
        return hash_equals(self::compute($pathA, $algo), self::compute($pathB, $algo));
    }
}
