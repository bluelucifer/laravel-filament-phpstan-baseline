# Contributing to Laravel & Filament PHPStan Baseline

Thank you for your interest in contributing! This project aims to maintain high-quality PHPStan baseline configurations for the Laravel and Filament communities.

## How to Contribute

### 1. Reporting False Positives

If PHPStan is reporting errors for valid Laravel/Filament code:

1. Open an issue with:
   - The exact PHPStan error message
   - Your PHPStan level
   - Laravel/Filament/Package versions
   - A code snippet that triggers the error
   - Your current phpstan.neon configuration

2. Label the issue with the appropriate tags:
   - `laravel` for Laravel framework patterns
   - `filament` for Filament-specific patterns
   - `livewire` for Livewire components
   - `package` for third-party packages

### 2. Submitting New Patterns

When submitting a PR with new ignore patterns:

```yaml
# ❌ Bad - Too generic
- '#Call to an undefined method .+::.+#'

# ✅ Good - Specific to framework feature
- '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder::where[A-Z][a-zA-Z]+\(\)#'
```

**Requirements:**
- Include comments explaining why the pattern is needed
- Reference the Laravel/Filament documentation if applicable
- Test the pattern with a minimal reproducible example
- Ensure patterns are as specific as possible

### 3. Pattern Organization

Place patterns in the appropriate section:

```yaml
parameters:
    ignoreErrors:
        # ============================================
        # Section Name (e.g., Eloquent ORM Patterns)
        # ============================================
        
        # Brief explanation of why this pattern exists
        - '#Your pattern here#'
```

### 4. Testing Patterns

Before submitting:

1. Test with a fresh Laravel/Filament project
2. Verify the pattern matches only intended errors
3. Check it doesn't hide real issues
4. Test across different PHPStan levels (0-9)

### 5. Documentation

Update documentation when adding patterns:

- Add examples in the README
- Update version compatibility if needed
- Document any special requirements

## Development Setup

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-pattern`
3. Make your changes
4. Test with real projects
5. Submit a PR with clear description

## Testing Your Changes

### Local Testing

```bash
# In your Laravel/Filament project
cd your-project

# Reference your local baseline
# Update phpstan.neon
includes:
    - /path/to/your/fork/baselines/laravel-11.neon
    
# Run PHPStan
./vendor/bin/phpstan analyse
```

### Integration Testing

Test against these scenarios:
- Fresh Laravel installation
- Laravel with Filament
- Laravel with Livewire
- Laravel with common packages
- Different PHPStan levels (0, 3, 5, 7, 9)

## Pull Request Guidelines

### PR Title Format

```
feat: Add patterns for [Feature/Package]
fix: Correct pattern for [Issue]
docs: Update [Documentation]
chore: [Maintenance task]
```

### PR Description Template

```markdown
## Description
Brief description of changes

## Patterns Added/Modified
- Pattern 1: Explanation
- Pattern 2: Explanation

## Testing
- [ ] Tested with Laravel 11.x
- [ ] Tested with Filament 3.x
- [ ] Tested with PHPStan level 5
- [ ] Patterns are specific enough
- [ ] Added comments to patterns

## Related Issues
Fixes #123
```

## Code of Conduct

- Be respectful and inclusive
- Focus on constructive feedback
- Help newcomers get started
- Share knowledge and best practices

## Recognition

Contributors will be:
- Listed in the README
- Mentioned in release notes
- Given credit in commit messages

## Questions?

- Open a discussion for questions
- Join Laravel/Filament Discord communities
- Check existing issues first

## License

By contributing, you agree that your contributions will be licensed under the MIT License.