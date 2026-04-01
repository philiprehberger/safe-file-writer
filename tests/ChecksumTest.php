<?php

declare(strict_types=1);

namespace PhilipRehberger\SafeFileWriter\Tests;

use PhilipRehberger\SafeFileWriter\Checksum;
use PhilipRehberger\SafeFileWriter\SafeFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ChecksumTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/safe-file-writer-checksum-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    #[Test]
    public function compute_returns_sha256_by_default(): void
    {
        $path = $this->tmpDir . '/file.txt';
        file_put_contents($path, 'hello');

        $hash = Checksum::compute($path);

        $this->assertSame(hash('sha256', 'hello'), $hash);
        $this->assertSame(64, strlen($hash));
    }

    #[Test]
    public function compute_with_md5_algorithm(): void
    {
        $path = $this->tmpDir . '/file.txt';
        file_put_contents($path, 'hello');

        $hash = Checksum::compute($path, 'md5');

        $this->assertSame(hash('md5', 'hello'), $hash);
        $this->assertSame(32, strlen($hash));
    }

    #[Test]
    public function verify_returns_true_for_matching_checksum(): void
    {
        $path = $this->tmpDir . '/file.txt';
        file_put_contents($path, 'test content');
        $expected = hash('sha256', 'test content');

        $this->assertTrue(Checksum::verify($path, $expected));
    }

    #[Test]
    public function verify_returns_false_for_mismatched_checksum(): void
    {
        $path = $this->tmpDir . '/file.txt';
        file_put_contents($path, 'test content');

        $this->assertFalse(Checksum::verify($path, 'invalid_hash'));
    }

    #[Test]
    public function compare_files_returns_true_for_identical_files(): void
    {
        $pathA = $this->tmpDir . '/a.txt';
        $pathB = $this->tmpDir . '/b.txt';
        file_put_contents($pathA, 'same content');
        file_put_contents($pathB, 'same content');

        $this->assertTrue(Checksum::compareFiles($pathA, $pathB));
    }

    #[Test]
    public function compare_files_returns_false_for_different_files(): void
    {
        $pathA = $this->tmpDir . '/a.txt';
        $pathB = $this->tmpDir . '/b.txt';
        file_put_contents($pathA, 'content A');
        file_put_contents($pathB, 'content B');

        $this->assertFalse(Checksum::compareFiles($pathA, $pathB));
    }

    #[Test]
    public function safe_file_checksum_convenience_method(): void
    {
        $path = $this->tmpDir . '/file.txt';
        SafeFile::write($path, 'hello');

        $this->assertSame(Checksum::compute($path), SafeFile::checksum($path));
    }

    #[Test]
    public function safe_file_verify_checksum_convenience_method(): void
    {
        $path = $this->tmpDir . '/file.txt';
        SafeFile::write($path, 'hello');
        $expected = hash('sha256', 'hello');

        $this->assertTrue(SafeFile::verifyChecksum($path, $expected));
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $dir . '/' . $item;
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
            } else {
                unlink($itemPath);
            }
        }

        rmdir($dir);
    }
}
