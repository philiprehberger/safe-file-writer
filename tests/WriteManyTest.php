<?php

declare(strict_types=1);

namespace PhilipRehberger\SafeFileWriter\Tests;

use PhilipRehberger\SafeFileWriter\SafeFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WriteManyTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/safe-file-writer-many-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    #[Test]
    public function write_many_writes_all_files_atomically(): void
    {
        $files = [
            $this->tmpDir . '/a.txt' => 'content A',
            $this->tmpDir . '/b.txt' => 'content B',
            $this->tmpDir . '/c.txt' => 'content C',
        ];

        SafeFile::writeMany($files);

        foreach ($files as $path => $content) {
            $this->assertFileExists($path);
            $this->assertSame($content, file_get_contents($path));
        }
    }

    #[Test]
    public function write_many_creates_parent_directories(): void
    {
        $files = [
            $this->tmpDir . '/deep/nested/a.txt' => 'nested A',
            $this->tmpDir . '/other/dir/b.txt' => 'nested B',
        ];

        SafeFile::writeMany($files);

        foreach ($files as $path => $content) {
            $this->assertFileExists($path);
            $this->assertSame($content, file_get_contents($path));
        }
    }

    #[Test]
    public function write_many_with_empty_array_does_nothing(): void
    {
        SafeFile::writeMany([]);

        // No exception thrown, directory is still clean
        $items = scandir($this->tmpDir);
        $this->assertSame(['.', '..'], $items);
    }

    #[Test]
    public function write_many_with_single_file_works(): void
    {
        $path = $this->tmpDir . '/single.txt';

        SafeFile::writeMany([$path => 'single content']);

        $this->assertFileExists($path);
        $this->assertSame('single content', file_get_contents($path));
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
