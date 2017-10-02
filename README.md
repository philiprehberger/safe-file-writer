# PHP Safe File Writer

[![Tests](https://github.com/philiprehberger/safe-file-writer/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/safe-file-writer/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/safe-file-writer.svg)](https://packagist.org/packages/philiprehberger/safe-file-writer)
[![License](https://img.shields.io/github/license/philiprehberger/safe-file-writer)](LICENSE)

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

## API

| Method | Description | Returns |
|---|---|---|
| `SafeFile::write(string $path, string $content)` | Atomic write via temp-file + rename | `void` |
| `SafeFile::writeJson(string $path, mixed $data, int $flags = ...)` | Atomic JSON write | `void` |
| `SafeFile::append(string $path, string $content)` | Append with exclusive lock | `void` |
| `SafeFile::read(string $path)` | Read with shared lock | `string` |
| `SafeFile::readJson(string $path)` | Read and decode JSON | `mixed` |
| `SafeFile::exists(string $path)` | Check if file exists | `bool` |
| `SafeFile::delete(string $path)` | Delete file if it exists | `bool` |
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

## License

MIT
