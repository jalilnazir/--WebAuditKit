# Changelog

All notable changes to WebAuditKit will be documented in this file.

The format is based on Keep a Changelog, and this project follows Semantic Versioning.

## [Unreleased]

Changes for the next development release will be documented here.

---

## [0.1.0] - 2026-07-27

First development release of WebAuditKit.

This release establishes the core architecture for secure website fetching, HTML auditing, automated testing, and future SEO audit modules.

### Added

- High-level `WebAuditKit` public API
- HTML auditing support
- Live HTTP/HTTPS page fetching
- Configurable HTTP request timeout
- Maximum response-size protection
- HTTP status validation
- HTML content-type validation
- Controlled redirect handling
- Maximum redirect limits
- URL validation
- SSRF protection
- Localhost blocking
- Private IP address blocking
- Reserved IP address blocking
- IPv4 and IPv6 address validation
- Redirect destination security validation
- PHPUnit test suite
- Automated GitHub Actions CI
- PHP 8.1+ support
- Composer and PSR-4 autoloading

### Security

- Added `UrlGuard` for validating server-side request destinations
- Added protection against localhost and private network requests
- Added validation of redirect destinations before connecting
- Restricted network requests to HTTP and HTTPS
