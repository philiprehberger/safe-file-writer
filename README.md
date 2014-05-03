# safe-file-writer

[![Tests](https://github.com/philiprehberger/safe-file-writer/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/safe-file-writer/actions/workflows/tests.yml)
[![PHPStan Level 6](https://img.shields.io/badge/PHPStan-level%206-brightgreen.svg)](https://phpstan.org/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Latest Version](https://img.shields.io/packagist/v/philiprehberger/safe-file-writer.svg)](https://packagist.org/packages/philiprehberger/safe-file-writer)

Atomic file writes with temp-file swap and file locking for PHP 8.2+. Framework-agnostic, zero dependencies.

---

## Features

- Atomic writes via temp-file + rename — no partial/corrupt files on crash
- Exclusive locking (`LOCK_EX`) on write and append operations
- Shared locking (`LOCK_SH`) on read operations
- Automatic parent directory creation
- Permission preservation when overwriting existing files
- JSON read/write helpers with `json_encode`/`json_decode`
- Clear exception types for read and write failures
- PHPStan level 6 clean, PSR-12 code style

---

## Requirements

- PHP ^8.2
- No extensions required

---

## Installation

```bash
composer require philiprehberger/safe-file-writer
```

---

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

---

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

---

## Exceptions

| Exception | When thrown |
|---|---|
| `FileWriteException` | Directory creation, temp-file creation, write, or rename failure |
| `FileReadException` | File not found, open failure, lock failure, or read failure |
| `\JsonException` | Invalid JSON on `writeJson()` or `readJson()` |

```php
use PhilipRehberger\SafeFileWriter\Exceptions\FileWriteException;
use PhilipRehberger\SafeFileWriter\Exceptions\FileReadException;

try {
    SafeFile::read('/nonexistent/file.txt');
} catch (FileReadException $e) {
    // "File not found: '/nonexistent/file.txt'."
}
```

---

## Running Tests

```bash
composer install
composer test
```

Static analysis:

```bash
composer phpstan
```

Code style check:

```bash
composer pint
```

Run everything at once:

```bash
composer check
```

---

## License

MIT — see [LICENSE](LICENSE).
