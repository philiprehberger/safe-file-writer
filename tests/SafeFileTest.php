<?php

declare(strict_types=1);

namespace PhilipRehberger\SafeFileWriter\Tests;

use PhilipRehberger\SafeFileWriter\Exceptions\FileReadException;
use PhilipRehberger\SafeFileWriter\SafeFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SafeFileTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/safe-file-writer-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    #[Test]
    public function write_and_read_round_trip(): void
    {
        $path = $this->tmpDir . '/hello.txt';

        SafeFile::write($path, 'Hello, world!');

        $this->assertSame('Hello, world!', SafeFile::read($path));
    }

    #[Test]
    public function write_json_and_read_json_round_trip(): void
    {
        $path = $this->tmpDir . '/data.json';
        $data = ['name' => 'Philip', 'scores' => [10, 20, 30]];

        SafeFile::writeJson($path, $data);

        $this->assertSame($data, SafeFile::readJson($path));
    }

    #[Test]
    public function append_to_existing_file(): void
    {
        $path = $this->tmpDir . '/log.txt';

        SafeFile::write($path, "line1\n");
        SafeFile::append($path, "line2\n");

        $this->assertSame("line1\nline2\n", SafeFile::read($path));
    }

    #[Test]
    public function append_creates_file_if_not_exists(): void
    {
        $path = $this->tmpDir . '/new-log.txt';

        SafeFile::append($path, "first line\n");

        $this->assertSame("first line\n", SafeFile::read($path));
    }

    #[Test]
    public function read_non_existent_file_throws_file_read_exception(): void
    {
        $this->expectException(FileReadException::class);
        $this->expectExceptionMessage('File not found');

        SafeFile::read($this->tmpDir . '/does-not-exist.txt');
    }

    #[Test]
    public function delete_existing_file_returns_true(): void
    {
        $path = $this->tmpDir . '/to-delete.txt';
        SafeFile::write($path, 'delete me');

        $this->assertTrue(SafeFile::delete($path));
        $this->assertFalse(file_exists($path));
    }

    #[Test]
    public function delete_non_existent_file_returns_false(): void
    {
        $this->assertFalse(SafeFile::delete($this->tmpDir . '/nope.txt'));
    }

    #[Test]
    public function exists_returns_correct_boolean(): void
    {
        $path = $this->tmpDir . '/check.txt';

        $this->assertFalse(SafeFile::exists($path));

        SafeFile::write($path, 'exists');

        $this->assertTrue(SafeFile::exists($path));
    }

    #[Test]
    public function write_creates_parent_directories(): void
    {
        $path = $this->tmpDir . '/deep/nested/dir/file.txt';

        SafeFile::write($path, 'nested content');

        $this->assertSame('nested content', SafeFile::read($path));
    }

    #[Test]
    public function permissions_preserved_on_overwrite(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Permission tests are not reliable on Windows.');
        }

        $path = $this->tmpDir . '/perms.txt';

        SafeFile::write($path, 'initial');
        chmod($path, 0644);

        SafeFile::write($path, 'updated');

        $this->assertSame(0644, fileperms($path) & 0777);
        $this->assertSame('updated', SafeFile::read($path));
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
