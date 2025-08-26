# Security Policy

## Supported Versions

We provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| Latest  | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We take the security of our PHPStan baseline configurations seriously. If you discover a security vulnerability, please follow these steps:

### How to Report

1. **DO NOT** open a public issue
2. Email security concerns to the maintainers
3. Include the following information:
   - Type of vulnerability
   - Affected baseline files
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Initial Assessment**: Within 1 week
- **Resolution Timeline**: Depends on severity
  - Critical: 1-2 days
  - High: 3-5 days
  - Medium: 1-2 weeks
  - Low: Next release

### Security Considerations

While PHPStan baselines primarily affect static analysis and don't directly impact runtime security, we consider the following as security concerns:

1. **Overly Permissive Patterns**: Patterns that could hide actual security vulnerabilities
2. **Injection Risks**: Patterns that might allow unsafe input validation to pass
3. **Information Disclosure**: Patterns that might expose sensitive information in error messages

## Best Practices

When using our baselines:

1. **Review Patterns**: Always review what patterns you're suppressing
2. **Audit Regularly**: Periodically audit your PHPStan configuration
3. **Report Issues**: If a pattern seems to hide legitimate security issues, report it
4. **Keep Updated**: Use the latest version of our baselines

## Disclosure Policy

- Security issues will be disclosed after a fix is available
- Credit will be given to reporters (unless anonymity is requested)
- We will coordinate disclosure with affected parties

## Comments on this Policy

If you have suggestions on how this process could be improved, please submit a pull request or open an issue.