# Changelog

All notable changes to `philiprehberger/safe-file-writer` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.0.0] — 2026-03-13

### Added

- `SafeFile::write()` — atomic file writes using temp-file + rename strategy with `LOCK_EX`.
- `SafeFile::writeJson()` — atomic JSON file writes with configurable encoding flags.
- `SafeFile::append()` — append content to a file with exclusive lock.
- `SafeFile::read()` — read file content with a shared lock.
- `SafeFile::readJson()` — read and decode a JSON file with shared lock.
- `SafeFile::exists()` — check if a file exists.
- `SafeFile::delete()` — delete a file if it exists.
- Automatic parent directory creation on write and append.
- Permission preservation when overwriting existing files.
- `FileWriteException` with named constructors for directory, temp-file, write, and rename failures.
- `FileReadException` with named constructors for not-found, open, lock, and read failures.
- PHPUnit 11 test suite.
- PHPStan level 6 configuration.
- Laravel Pint code-style configuration.
- GitHub Actions CI pipeline for PHP 8.2, 8.3, and 8.4.

[Unreleased]: https://github.com/philiprehberger/safe-file-writer/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/philiprehberger/safe-file-writer/releases/tag/v1.0.0
