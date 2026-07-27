# Security Policy

Security is an important part of WebAuditKit, particularly because the project can make server-side HTTP requests to user-supplied URLs.

This document explains how to report security vulnerabilities and describes the current security scope of the project.

## Supported Versions

WebAuditKit is currently in early development.

| Version | Supported |
| --- | --- |
| `main` | Yes |
| `0.x` development releases | Best effort |
| Older versions | No |

Until WebAuditKit reaches a stable `1.0.0` release, security fixes may include changes to public APIs when necessary.

## Reporting a Vulnerability

Please **do not publicly disclose security vulnerabilities in normal GitHub issues**.

Public disclosure before a fix is available may put users of the project at risk.

If GitHub private vulnerability reporting is enabled for this repository, please use the repository's private security reporting feature.

When reporting a vulnerability, include as much of the following information as possible:

- A description of the vulnerability
- The affected component
- Steps to reproduce the issue
- The potential security impact
- A minimal proof of concept, when appropriate
- The affected WebAuditKit version or commit
- Suggested mitigation, if known

Please do not include unrelated personal information, credentials, API keys, authentication tokens, or other secrets.

## Security Scope

Security-sensitive areas of WebAuditKit include:

- URL validation
- DNS resolution
- HTTP requests
- HTTPS requests
- Redirect handling
- SSRF protection
- Response-size limits
- Request timeouts
- HTML parsing
- Future crawling functionality

Changes to these components should receive additional review and automated test coverage.

## SSRF Protection

WebAuditKit includes protections designed to reduce Server-Side Request Forgery (SSRF) risk.

The URL security layer currently rejects destinations including:

- Invalid URLs
- Unsupported protocols
- Localhost
- `.localhost` hostnames
- IPv4 loopback addresses
- IPv6 loopback addresses
- Private IP address ranges
- Link-local addresses
- Reserved IP address ranges

Only HTTP and HTTPS URLs are supported.

## Redirect Protection

Redirects are handled explicitly rather than relying on unrestricted automatic redirect following.

Each redirect destination is validated before another HTTP connection is made.

This is intended to prevent an initially public URL from redirecting directly to a private, loopback, link-local, or reserved destination.

## Defense in Depth

Application developers should not treat WebAuditKit's URL validation as their only security boundary.

Deployments that process untrusted URLs should also consider infrastructure-level protections such as:

- Network egress restrictions
- Firewall rules
- Container or process isolation
- Request rate limits
- Resource limits
- DNS security controls
- Application-level authorization
- Logging and monitoring

Security requirements vary between deployment environments.

## Known Security Considerations

SSRF protection is more complex than checking whether an initial hostname resolves to a public IP address.

Areas requiring continued review include:

- DNS rebinding
- DNS changes between validation and connection
- Alternative IP representations
- IPv4/IPv6 behavior
- Redirect edge cases
- Proxy configuration
- Hostname normalization
- URL parsing inconsistencies
- Resource exhaustion
- Large or malicious HTML documents

WebAuditKit will continue strengthening these areas as the project develops.

## Dependency Security

Contributors should avoid unnecessary dependencies.

Dependencies should be maintained and obtained through trusted package sources.

Users should keep Composer dependencies current and review security advisories relevant to their environment.

## Security Tests

Security-related behavior should be covered by automated tests whenever practical.

Current tests include cases involving:

- Invalid URLs
- Unsupported URL schemes
- Localhost
- IPv4 loopback
- IPv6 loopback
- Private network addresses
- Link-local addresses
- Public address validation

Additional security regression tests should be added whenever vulnerabilities or important edge cases are discovered.

## Responsible Disclosure

Please allow reasonable time for a vulnerability to be investigated and fixed before publishing technical details.

Confirmed vulnerabilities may result in:

1. A security fix
2. Additional regression tests
3. Documentation updates
4. A security advisory when appropriate
5. A patched release

## Security Is an Ongoing Process

No URL-fetching library can guarantee protection against every deployment-specific threat.

WebAuditKit aims to provide secure defaults while keeping security-sensitive behavior transparent and testable.

Security improvements and responsible vulnerability reports are welcome.
