
# WebAuditKit

**Open-source website auditing toolkit for developers, SEO professionals, and website owners.**

WebAuditKit is an open-source PHP toolkit for analyzing websites for technical SEO, on-page SEO, metadata, links, images, structured data, and common website issues.

The goal is to provide a transparent, extensible, and developer-friendly auditing engine that can be used independently or integrated into other applications.

> **Project Status:** WebAuditKit is under active development. The public API and functionality may change before the first stable release.

---

## Why WebAuditKit?

Website auditing often requires multiple tools to inspect metadata, links, indexing directives, images, structured data, and other technical signals.

WebAuditKit aims to provide these checks through one open-source auditing engine.

The project is designed around four principles:

- **Open** — auditing logic should be inspectable and extensible.
- **Modular** — individual audit components should be reusable.
- **Developer-friendly** — results should be easy to integrate into applications.
- **Practical** — findings should explain what was detected and why it matters.

---

## Current Capabilities

WebAuditKit already includes foundational components for building secure website audits:

- HTML auditing
- Live URL fetching
- HTTP and HTTPS support
- Redirect handling
- Response-size limits
- Request timeouts
- HTML content-type validation
- HTTP status validation
- URL validation
- SSRF protection
- Localhost blocking
- Private and reserved IP blocking
- Redirect destination validation
- PHPUnit test suite
- Automated GitHub Actions testing
- PHP 8.1+ support
- Composer/PSR-4 autoloading
- High-level PHP API

---

## Planned Audit Features

### On-Page SEO

- Page title detection
- Title length analysis
- Meta description detection
- Meta description length analysis
- Heading structure analysis
- H1 detection
- Multiple H1 detection
- Canonical URL detection
- Meta robots detection
- Indexability checks

### Link Analysis

- Internal link detection
- External link detection
- Broken link detection
- Anchor text analysis
- Redirect detection
- Nofollow attribute detection

### Image Analysis

- Image discovery
- Missing alt attribute detection
- Empty alt attribute detection
- Image URL analysis
- Basic image SEO checks

### Social Metadata

- Open Graph detection
- Open Graph title
- Open Graph description
- Open Graph image
- Twitter/X Card detection

### Structured Data

- JSON-LD detection
- Schema.org markup detection
- Structured data inventory
- Basic structured data validation

### Technical SEO

- HTTPS checks
- HTTP status analysis
- Redirect analysis
- Robots.txt detection
- Sitemap detection
- Canonicalization checks
- URL structure checks
- Indexing directive analysis

### Reporting

Planned reporting capabilities include:

- Human-readable audit results
- Structured array output
- JSON output
- Issue severity
- Passed checks
- Warnings
- Errors
- Audit summaries

---

## Requirements

WebAuditKit currently requires:

- PHP 8.1 or newer
- PHP cURL extension
- PHP DOM extension
- Composer

Development and automated testing currently target supported PHP versions configured by the project's GitHub Actions workflow.

---

## Installation

WebAuditKit is currently under active development and has not yet reached its first stable release.

Once published as a Composer package, the intended installation method will be:

```bash
composer require webauditkit/webauditkit
```

> Until the package is published to a Composer registry, the command above should be considered the planned installation method.

---

## Quick Start

WebAuditKit provides a high-level API through the `WebAuditKit` class.

### Audit Existing HTML

```php
<?php

require 'vendor/autoload.php';

use WebAuditKit\WebAuditKit;

$kit = new WebAuditKit();

$html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Example Website</title>
    <meta
        name="description"
        content="Example website description."
    >
</head>
<body>
    <h1>Example Website</h1>
</body>
</html>
HTML;

$result = $kit->auditHtml(
    $html,
    'https://example.com'
);

print_r($result);
```

HTML can also be analyzed without supplying a source URL:

```php
$result = $kit->auditHtml($html);
```

---

## Audit a Live URL

WebAuditKit includes an HTTP page fetcher for retrieving public HTML documents.

The high-level API is designed to support:

```php
<?php

require 'vendor/autoload.php';

use WebAuditKit\WebAuditKit;

$kit = new WebAuditKit();

$result = $kit->auditUrl(
    'https://example.com'
);

print_r($result);
```

The URL is validated before the HTTP connection is made.

Redirect destinations are also validated before WebAuditKit follows them.

---

## Audit Flow

The current architecture follows this general workflow:

```text
URL
 ↓
Validate URL
 ↓
SSRF Protection
 ↓
Fetch Page
 ↓
Validate Redirects
 ↓
Parse HTML
 ↓
Run Audit Checks
 ↓
Return Structured Results
```

For HTML supplied directly:

```text
HTML
 ↓
Parse HTML
 ↓
Run Audit Checks
 ↓
Return Structured Results
```

---

## Security

Server-side URL fetching creates security risks if arbitrary destinations are accepted without validation.

WebAuditKit therefore includes a URL security layer designed to reduce Server-Side Request Forgery (SSRF) risk.

The current URL guard rejects:

- Invalid URLs
- Unsupported URL schemes
- `localhost`
- `.localhost` hostnames
- IPv4 loopback addresses
- IPv6 loopback addresses
- Private IPv4 networks
- Link-local addresses
- Reserved IP addresses
- Common internal/server metadata destinations through private or reserved address filtering

Only HTTP and HTTPS destinations are supported.

### Redirect Security

WebAuditKit does not rely on unrestricted automatic redirect following.

Redirects are handled explicitly so that each new destination can be validated before another HTTP connection is made.

This prevents a public URL from trivially bypassing the initial URL validation by redirecting to a private or reserved destination.

> Security-sensitive network code should be reviewed carefully before deployment in high-risk or multi-tenant environments. SSRF defense can involve DNS rebinding, resolver behavior, proxy configuration, and infrastructure-specific considerations beyond basic address filtering.

---

## HTTP Fetcher

The HTTP layer currently provides:

- HTTP/HTTPS fetching
- Configurable timeout
- Maximum response-size protection
- HTML content-type validation
- HTTP status validation
- Controlled redirect handling
- Maximum redirect limits
- Custom user agent
- URL security validation

Example low-level usage:

```php
<?php

require 'vendor/autoload.php';

use WebAuditKit\Http\PageFetcher;

$fetcher = new PageFetcher();

$html = $fetcher->fetch(
    'https://example.com'
);
```

---

## Architecture

WebAuditKit uses a modular architecture so networking, security, auditing, and future reporting components can evolve independently.

Current structure:

```text
WebAuditKit
│
├── Public API
│   └── WebAuditKit
│
├── HTTP
│   └── PageFetcher
│
├── Security
│   └── UrlGuard
│
├── Audit Engine
│   └── Auditor
│
└── Tests
    ├── Auditor tests
    ├── PageFetcher tests
    ├── UrlGuard tests
    └── Public API tests
```

Planned expansion:

```text
Audit Engine
│
├── Metadata Checks
├── Heading Checks
├── Link Checks
├── Image Checks
├── Social Metadata Checks
├── Structured Data Checks
└── Technical SEO Checks
```

The goal is to allow contributors to add audit rules without tightly coupling them to HTTP or security infrastructure.

---

## Roadmap

### v0.1 — Core Auditor

- [x] HTTP/HTTPS URL fetching
- [x] URL validation
- [x] Request timeout handling
- [x] Response-size protection
- [x] Redirect handling
- [x] SSRF protection
- [x] Redirect destination validation
- [x] High-level PHP API
- [x] Automated tests
- [x] GitHub Actions CI
- [ ] Expand HTML parsing
- [ ] Page title analysis
- [ ] Meta description analysis
- [ ] Heading analysis
- [ ] Canonical URL detection
- [ ] Robots meta detection
- [ ] Basic audit result model
- [ ] First tagged release

### v0.2 — Links & Images

- [ ] Internal link analysis
- [ ] External link analysis
- [ ] Broken link detection
- [ ] Image detection
- [ ] Missing alt text detection

### v0.3 — Technical SEO

- [ ] HTTP status reporting
- [ ] Advanced redirect analysis
- [ ] Robots.txt analysis
- [ ] Sitemap detection
- [ ] HTTPS checks

### v0.4 — Structured Data & Social

- [ ] JSON-LD detection
- [ ] Schema.org analysis
- [ ] Open Graph analysis
- [ ] Twitter/X Card analysis

### Future

- [ ] CLI
- [ ] JSON reports
- [ ] Configurable audit rules
- [ ] Documentation website
- [ ] Plugin architecture
- [ ] Full-site crawling
- [ ] Performance auditing

---

## Testing

WebAuditKit uses PHPUnit for automated testing.

Run the test suite with:

```bash
composer test
```

or:

```bash
vendor/bin/phpunit tests
```

Tests currently cover areas including:

- Core auditing behavior
- URL validation
- Unsupported URL schemes
- HTTP fetcher configuration
- Invalid timeout configuration
- Response-size configuration
- Localhost rejection
- IPv4 loopback rejection
- IPv6 loopback rejection
- Private network rejection
- Link-local address rejection
- Public IP acceptance
- High-level HTML auditing API

---

## Continuous Integration

WebAuditKit uses GitHub Actions to automatically execute the test suite when code is pushed.

The CI workflow tests the project against multiple supported PHP versions.

This helps identify compatibility problems and regressions before changes are released.

---

## Development

Install dependencies:

```bash
composer install
```

Run tests:

```bash
composer test
```

The project uses PSR-4 autoloading:

```text
WebAuditKit\ → src/
WebAuditKit\Tests\ → tests/
```

---

## Contributing

Contributions are welcome.

There are several ways to contribute:

- Report bugs
- Suggest audit rules
- Improve existing checks
- Improve documentation
- Add tests
- Review pull requests
- Submit bug fixes
- Propose new features
- Improve security and HTTP handling

For significant changes, please open an issue first so the proposed implementation can be discussed.

As the project grows, detailed contribution guidelines will be maintained in `CONTRIBUTING.md`.

---

## Reporting Bugs

When reporting a bug, please include:

1. A clear description of the problem.
2. Steps to reproduce it.
3. Expected behavior.
4. Actual behavior.
5. PHP version and relevant environment information.
6. Error messages or logs where appropriate.

Please remove passwords, API keys, authentication tokens, private URLs, and other sensitive information before submitting an issue.

---

## Feature Requests

Feature requests are welcome.

When suggesting a new audit check, explain:

- What should be checked?
- Why is the check useful?
- What should constitute a pass?
- What should constitute a warning or failure?
- Are there important edge cases?

This helps turn feature requests into well-defined audit rules.

---

## Reporting Security Vulnerabilities

Please do not publicly disclose suspected security vulnerabilities through normal GitHub issues.

Until a dedicated security policy and private reporting process are established, avoid publishing exploit details that could put users at risk.

A formal `SECURITY.md` policy is planned before the first stable release.

---

## Documentation

Documentation will expand alongside the implementation.

Planned documentation includes:

- Getting started
- Installation
- Public API
- CLI usage
- Audit rules
- Configuration
- JSON output format
- Integration examples
- Architecture
- Contributor guide
- Security model

---

## Who Is WebAuditKit For?

WebAuditKit is intended for:

- Web developers
- SEO professionals
- Website administrators
- Technical SEO specialists
- Agencies
- Open-source developers
- Researchers
- Developers building SEO applications

---

## Project Philosophy

WebAuditKit aims to make website auditing more transparent.

An audit result should not simply produce a score. Developers should be able to understand:

1. **What was detected**
2. **Why it may matter**
3. **How the result was determined**

The project therefore prioritizes explainable audit rules and structured findings over opaque scoring.

---

## License

WebAuditKit is released under the **MIT License**.

See the `LICENSE` file for the full license text.

---

## Disclaimer

WebAuditKit provides technical website analysis and diagnostic information.

Audit results should not be interpreted as guarantees of search-engine rankings, traffic, indexing, or website performance. Search engines use many signals and may change their systems over time.

---

## Support the Project

If WebAuditKit is useful to you, you can support its development by:

- Starring the repository
- Reporting bugs
- Suggesting improvements
- Contributing code
- Improving documentation
- Sharing the project with other developers

---

## Maintainer

WebAuditKit is independently developed and maintained as an open-source project.

Contributions and constructive feedback from the community are welcome.

---

**WebAuditKit — Open website auditing for everyone.**
