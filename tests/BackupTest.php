<?php

declare(strict_types=1);

namespace PhilipRehberger\SafeFileWriter\Tests;

use PhilipRehberger\SafeFileWriter\BackupManager;
use PhilipRehberger\SafeFileWriter\SafeFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BackupTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/safe-file-writer-backup-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    #[Test]
    public function backup_creates_timestamped_file_in_same_directory(): void
    {
        $path = $this->tmpDir . '/original.txt';
        file_put_contents($path, 'original content');

        $backupPath = BackupManager::backup($path);

        $this->assertNotNull($backupPath);
        $this->assertStringStartsWith($this->tmpDir . '/original.txt.', $backupPath);
        $this->assertStringEndsWith('.bak', $backupPath);
        $this->assertFileExists($backupPath);
        $this->assertSame('original content', file_get_contents($backupPath));
    }

    #[Test]
    public function backup_creates_file_in_custom_backup_directory(): void
    {
        $path = $this->tmpDir . '/source.txt';
        $backupDir = $this->tmpDir . '/backups';
        file_put_contents($path, 'source content');

        $backupPath = BackupManager::backup($path, $backupDir);

        $this->assertNotNull($backupPath);
        $this->assertStringStartsWith($backupDir . '/source.txt.', $backupPath);
        $this->assertFileExists($backupPath);
        $this->assertSame('source content', file_get_contents($backupPath));
    }

    #[Test]
    public function backup_returns_null_when_source_does_not_exist(): void
    {
        $result = BackupManager::backup($this->tmpDir . '/nonexistent.txt');

        $this->assertNull($result);
    }

    #[Test]
    public function write_with_backup_creates_backup_then_writes_new_content(): void
    {
        $path = $this->tmpDir . '/data.txt';
        SafeFile::write($path, 'old content');

        SafeFile::writeWithBackup($path, 'new content');

        $this->assertSame('new content', SafeFile::read($path));

        // Verify a backup file was created
        $files = glob($this->tmpDir . '/data.txt.*.bak');
        $this->assertNotEmpty($files);
        $this->assertSame('old content', file_get_contents($files[0]));
    }

    #[Test]
    public function write_json_with_backup_works_correctly(): void
    {
        $path = $this->tmpDir . '/data.json';
        $oldData = ['version' => 1];
        $newData = ['version' => 2];

        SafeFile::writeJson($path, $oldData);
        SafeFile::writeJsonWithBackup($path, $newData);

        $this->assertSame($newData, SafeFile::readJson($path));

        // Verify a backup file was created with old content
        $files = glob($this->tmpDir . '/data.json.*.bak');
        $this->assertNotEmpty($files);
        $this->assertSame($oldData, json_decode(file_get_contents($files[0]), true));
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
