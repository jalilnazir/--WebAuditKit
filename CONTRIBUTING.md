# Contributing to WebAuditKit

Thank you for your interest in contributing to WebAuditKit.

WebAuditKit is an open-source website auditing toolkit focused on technical SEO, on-page analysis, secure URL fetching, and developer-friendly audit results.

Contributions of all sizes are welcome.

## Ways to Contribute

You can help by:

- Fixing bugs
- Adding SEO audit checks
- Improving tests
- Improving documentation
- Reviewing pull requests
- Reporting security issues responsibly
- Suggesting new features
- Improving HTTP and URL handling
- Improving performance
- Adding edge-case coverage

## Getting Started

Fork the repository and clone your fork:

```bash
git clone <your-fork-url>
cd webauditkit
```

Install dependencies:

```bash
composer install
```

Run the test suite:

```bash
composer test
```

All tests should pass before submitting a pull request.

## Development Requirements

WebAuditKit requires:

- PHP 8.1 or newer
- Composer
- PHP cURL extension
- PHP DOM extension

## Project Structure

```text
src/
├── Auditor.php
├── WebAuditKit.php
├── Http/
│   └── PageFetcher.php
└── Security/
    └── UrlGuard.php

tests/
├── AuditorTest.php
├── PageFetcherTest.php
├── UrlGuardTest.php
└── WebAuditKitTest.php
```

## Pull Requests

Create a separate branch for your change:

```bash
git checkout -b feature/example-feature
```

Keep pull requests focused on one logical change whenever possible.

Before submitting a pull request:

1. Run the complete test suite.
2. Add tests for new behavior.
3. Update documentation when necessary.
4. Avoid unrelated formatting changes.
5. Make sure no credentials or sensitive information are committed.

Please explain what your pull request changes and why the change is useful.

## Adding Audit Rules

New audit checks should ideally:

- Have a clearly defined purpose
- Produce predictable results
- Handle malformed or incomplete HTML safely
- Include automated tests
- Avoid unnecessary network requests
- Explain why the detected condition matters

SEO recommendations should avoid presenting uncertain ranking factors as guarantees.

## Testing

Run:

```bash
composer test
```

GitHub Actions also runs the automated test suite for repository changes.

New functionality should normally include corresponding tests.

## Coding Guidelines

Please:

- Use `declare(strict_types=1);`
- Follow the existing namespace structure
- Use meaningful class and method names
- Keep classes focused on a clear responsibility
- Prefer explicit behavior over hidden side effects
- Add type declarations where practical
- Keep security-sensitive code easy to review

## Security-Sensitive Changes

URL fetching, redirects, DNS resolution, request handling, and SSRF protection are security-sensitive areas.

Changes affecting these components should include tests covering relevant edge cases.

Do not intentionally weaken URL validation or private-network protections without a documented technical reason.

## Reporting Bugs

When reporting a bug, include:

- A clear description
- Steps to reproduce
- Expected behavior
- Actual behavior
- PHP version
- Relevant environment information
- Error output when appropriate

Never include passwords, API keys, authentication tokens, or other secrets.

## Feature Requests

For a new audit rule, describe:

- What should be checked
- Why the check is useful
- Expected pass conditions
- Expected warning or failure conditions
- Relevant edge cases

## Commit Messages

Use short, descriptive commit messages.

Examples:

```text
Add canonical URL detection
Fix redirect URL resolution
Add metadata audit tests
Improve URL validation
```

## Backward Compatibility

WebAuditKit is currently in early development.

Breaking API changes may occur before the first stable `1.0.0` release, but unnecessary breaking changes should still be avoided.

## License

By contributing to WebAuditKit, you agree that your contributions will be licensed under the project's MIT License.

## Questions

For general development questions, open a GitHub issue or discussion where appropriate.

Thank you for helping improve WebAuditKit.
