# PHP Safe File Writer

[![Tests](https://github.com/philiprehberger/safe-file-writer/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/safe-file-writer/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/safe-file-writer.svg)](https://packagist.org/packages/philiprehberger/safe-file-writer)
[![Last updated](https://img.shields.io/github/last-commit/philiprehberger/safe-file-writer)](https://github.com/philiprehberger/safe-file-writer/commits/main)

Atomic file writes with temp-file swap and file locking.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require philiprehberger/safe-file-writer
```

## Usage

### Write a file atomically

```php
use PhilipRehberger\SafeFileWriter\SafeFile;

// Writes to a temp file first, then renames — atomic on POSIX systems
SafeFile::write('/path/to/config.txt', 'key=value');

// Parent directories are created automatically
SafeFile::write('/path/to/deep/nested/file.txt', 'content');
```

### Write JSON

```php
SafeFile::writeJson('/path/to/data.json', [
    'name' => 'Philip',
    'scores' => [10, 20, 30],
]);
// Writes pretty-printed JSON with a trailing newline

// Custom JSON flags
SafeFile::writeJson('/path/to/compact.json', $data, JSON_UNESCAPED_UNICODE);
```

### Append to a file

```php
// Appends with exclusive lock; creates file if it doesn't exist
SafeFile::append('/var/log/app.log', "[2026-03-13] Info: started\n");
SafeFile::append('/var/log/app.log', "[2026-03-13] Info: finished\n");
```

### Read a file

```php
// Reads with a shared lock
$content = SafeFile::read('/path/to/config.txt');
```

### Read JSON

```php
$data = SafeFile::readJson('/path/to/data.json');
// Returns decoded array/object
```

### Check existence

```php
if (SafeFile::exists('/path/to/file.txt')) {
    // ...
}
```

### Delete a file

```php
$deleted = SafeFile::delete('/path/to/file.txt');
// Returns true if deleted, false if file didn't exist
```

### Backup Before Write

```php
use PhilipRehberger\SafeFileWriter\SafeFile;

// Backs up existing file with timestamp, then writes new content atomically
SafeFile::writeWithBackup('/path/to/config.txt', 'new config');
// Creates /path/to/config.txt.2026-03-31T120000.bak before overwriting

// Store backups in a custom directory
SafeFile::writeWithBackup('/path/to/config.txt', 'updated', '/var/backups');

// Same for JSON files
SafeFile::writeJsonWithBackup('/path/to/data.json', ['version' => 2]);
```

### File Checksums

```php
use PhilipRehberger\SafeFileWriter\Checksum;
use PhilipRehberger\SafeFileWriter\SafeFile;

// Compute a SHA-256 checksum (default)
$hash = SafeFile::checksum('/path/to/file.txt');

// Use a different algorithm
$md5 = SafeFile::checksum('/path/to/file.txt', 'md5');

// Verify a file against a known checksum
$valid = SafeFile::verifyChecksum('/path/to/file.txt', $expectedHash);

// Compare two files
$same = Checksum::compareFiles('/path/to/a.txt', '/path/to/b.txt');
```

### Atomic Multi-File Writes

```php
use PhilipRehberger\SafeFileWriter\SafeFile;

// Write multiple files atomically — all or nothing
SafeFile::writeMany([
    '/path/to/config.json' => '{"key": "value"}',
    '/path/to/settings.yaml' => 'debug: true',
    '/path/to/version.txt' => '1.1.0',
]);
// If any file fails to write, previously written files are rolled back
```

## API

| Method | Description | Returns |
|---|---|---|
| `SafeFile::write(string $path, string $content)` | Atomic write via temp-file + rename | `void` |
| `SafeFile::writeJson(string $path, mixed $data, int $flags = ...)` | Atomic JSON write | `void` |
| `SafeFile::writeWithBackup(string $path, string $content, ?string $backupDir = null)` | Backup existing file, then atomic write | `void` |
| `SafeFile::writeJsonWithBackup(string $path, mixed $data, ?string $backupDir = null)` | Backup existing file, then atomic JSON write | `void` |
| `SafeFile::writeMany(array $files)` | Atomic multi-file write with rollback | `void` |
| `SafeFile::append(string $path, string $content)` | Append with exclusive lock | `void` |
| `SafeFile::read(string $path)` | Read with shared lock | `string` |
| `SafeFile::readJson(string $path)` | Read and decode JSON | `mixed` |
| `SafeFile::checksum(string $path, string $algo = 'sha256')` | Compute file checksum | `string` |
| `SafeFile::verifyChecksum(string $path, string $expected, string $algo = 'sha256')` | Verify file against expected checksum | `bool` |
| `SafeFile::exists(string $path)` | Check if file exists | `bool` |
| `SafeFile::delete(string $path)` | Delete file if it exists | `bool` |
| `BackupManager::backup(string $path, ?string $backupDir = null)` | Create timestamped backup of a file | `?string` |
| `Checksum::compute(string $path, string $algo = 'sha256')` | Compute file hash | `string` |
| `Checksum::verify(string $path, string $expected, string $algo = 'sha256')` | Verify file hash matches expected | `bool` |
| `Checksum::compareFiles(string $pathA, string $pathB, string $algo = 'sha256')` | Compare checksums of two files | `bool` |
| `FileWriteException` | Directory creation, temp-file creation, write, or rename failure | — |
| `FileReadException` | File not found, open failure, lock failure, or read failure | — |
| `\JsonException` | Invalid JSON on `writeJson()` or `readJson()` | — |

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## Support

If you find this project useful:

⭐ [Star the repo](https://github.com/philiprehberger/safe-file-writer)

🐛 [Report issues](https://github.com/philiprehberger/safe-file-writer/issues?q=is%3Aissue+is%3Aopen+label%3Abug)

💡 [Suggest features](https://github.com/philiprehberger/safe-file-writer/issues?q=is%3Aissue+is%3Aopen+label%3Aenhancement)

❤️ [Sponsor development](https://github.com/sponsors/philiprehberger)

🌐 [All Open Source Projects](https://philiprehberger.com/open-source-packages)

💻 [GitHub Profile](https://github.com/philiprehberger)

🔗 [LinkedIn Profile](https://www.linkedin.com/in/philiprehberger)

## License

[MIT](LICENSE)
