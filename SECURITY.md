# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 0.5.x   | Yes       |
| < 0.5   | No        |

## Reporting a Vulnerability

If you discover a security vulnerability in this plugin, please report it responsibly.

**Do NOT open a public GitHub issue for security vulnerabilities.**

### How to Report

Use [GitHub Security Advisories](https://github.com/lermnote/admin-config/security/advisories/new) to submit a private vulnerability report.

### What to Include

- A description of the vulnerability and its potential impact.
- Steps to reproduce or a proof-of-concept (if possible).
- The affected version(s).
- Any suggested fix (optional).

### Response Timeline

- **Acknowledgement:** within 48 hours.
- **Initial assessment:** within 7 days.
- **Resolution target:** within 30 days for critical issues.

### Scope

The following are in scope:

- PHP source code in `src/`
- REST API endpoints
- Classic admin rendering and submission handling
- Block editor panel runtime

The following are out of scope:

- Documentation site (`docs/`)
- CI/CD configuration
- Development tooling and test fixtures

## Security Best Practices for Consumers

When using field types that store raw content (e.g., `code_editor`), always escape output on the frontend:

```php
// Always escape stored code_editor values before rendering.
echo esc_html( get_option( 'my_settings' )['custom_css'] ?? '' );
```

The plugin sanitizes and escapes all output within the WordPress admin, but downstream themes and plugins are responsible for their own output escaping.
