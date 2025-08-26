# PHPStan Baseline Documentation

Welcome to the comprehensive documentation for Laravel & Filament PHPStan baseline patterns.

## 📚 Documentation Structure

### Pattern Documentation
Detailed explanations of PHPStan patterns for different packages:

- **Laravel Patterns**
  - [Eloquent ORM](patterns/laravel/eloquent-orm.md) - Model properties, relationships, query builders
  - [HTTP Requests](patterns/laravel/http-requests.md) - Form requests, validation, input handling
  - [Service Container](patterns/laravel/service-container.md) - Dependency injection, facades, helpers

- **Filament Patterns**
  - [Forms](patterns/filament/forms.md) - Form components, validation, lifecycle
  - [Tables](patterns/filament/tables.md) - Table columns, filters, actions
  - [Resources](patterns/filament/resources.md) - Resource configuration, pages, relations

- **Livewire Patterns**
  - [Components](patterns/livewire/components.md) - Component properties, methods, lifecycle
  - [Properties](patterns/livewire/properties.md) - Public properties, computed properties

### Use Cases
Real-world implementation guides:

- [New Project Setup](use-cases/new-project-setup.md) - Step-by-step setup for new projects
- [Legacy Migration](use-cases/legacy-migration.md) - Migrating existing projects to PHPStan
- [CI Integration](use-cases/ci-integration.md) - Adding PHPStan to CI/CD pipelines
- [Team Onboarding](use-cases/team-onboarding.md) - Getting your team up to speed

## 🎯 Quick Reference

### Choosing the Right Baseline

| Your Situation | Recommended Baselines |
|----------------|----------------------|
| New Laravel 11 project | `laravel-11.neon` + `level-0-2.neon` |
| Existing Laravel 10 project | `laravel-10.neon` + `level-3-5.neon` |
| Filament admin panel | `filament-3.neon` + framework baseline |
| Livewire components | `livewire-3.neon` + framework baseline |
| Strict type checking | `*-strict.neon` variants + `level-6-8.neon` |

### Common Commands

```bash
# Install
composer require --dev bluelucifer/laravel-filament-phpstan

# Analyze
vendor/bin/phpstan analyse

# Generate baseline for existing errors
vendor/bin/phpstan analyse --generate-baseline

# Check specific level
vendor/bin/phpstan analyse --level=5

# Clear cache
vendor/bin/phpstan clear-result-cache
```

## 🔍 Understanding Patterns

### Pattern Anatomy

```neon
-
    message: '#^Call to undefined method .+::whereEmail\(\)#'
    paths:
        - app/**/*.php
    count: 5  # Optional: expected occurrences
```

- **message**: Regular expression matching the error
- **paths**: Files where this pattern applies
- **count**: Expected number of occurrences (optional)

### Writing Custom Patterns

1. **Be specific** - Avoid overly broad patterns
2. **Use anchors** - `^` and `$` for exact matches
3. **Document why** - Add comments explaining the pattern
4. **Test thoroughly** - Ensure patterns don't hide real issues

## 📈 Level Progression Strategy

### Level 0-2: Foundation
- Focus on undefined variables and methods
- Add basic type declarations
- Fix obvious issues

### Level 3-5: Improvement
- Add return types to all methods
- Fix mixed type issues
- Add property types

### Level 6-8: Excellence
- Handle nullable types properly
- Add generic types
- Fix all type mismatches

### Level 9-10: Perfection
- Complete type coverage
- No suppressed errors
- Full generic support

## 🤝 Contributing

We welcome contributions! See our [Contributing Guide](../CONTRIBUTING.md) for details.

### How to Contribute Patterns

1. Identify a false positive
2. Write a specific pattern
3. Test with real projects
4. Submit a PR with explanation

## 📊 Pattern Statistics

- **Total Patterns**: 500+
- **Laravel Patterns**: 150+
- **Filament Patterns**: 200+
- **Livewire Patterns**: 100+
- **Community Contributors**: Growing!

## 🔗 Useful Links

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [Larastan Documentation](https://github.com/nunomaduro/larastan)
- [Writing Custom Rules](https://phpstan.org/developing-extensions/rules)
- [Regular Expression Testing](https://regex101.com/)

## ❓ FAQ

**Q: Should I use strict baselines from the start?**
A: No, start with basic baselines and gradually increase strictness.

**Q: Can I use multiple baselines together?**
A: Yes! Combine framework, package, and level baselines as needed.

**Q: How often should I update the baselines?**
A: Update when you upgrade frameworks or encounter new false positives.

**Q: What if a pattern is too broad?**
A: Report it as an issue so we can make it more specific.

## 📝 License

This documentation is part of the Laravel & Filament PHPStan Baseline project, licensed under MIT.