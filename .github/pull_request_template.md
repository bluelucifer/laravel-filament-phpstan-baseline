# Pull Request

## Description

<!-- Provide a brief description of the changes in this PR -->

## Type of Change

Please delete options that are not relevant.

- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update
- [ ] Baseline configuration update
- [ ] Performance improvement
- [ ] Refactoring (no functional changes)

## Related Issues

<!-- Link to any related issues using "Fixes #123" or "Closes #123" -->

Fixes #(issue_number)

## Changes Made

<!-- Describe the changes made in this PR -->

- [ ] Added/updated baseline configuration files
- [ ] Updated documentation
- [ ] Added tests
- [ ] Updated CI/CD workflows
- [ ] Other: 

## Baseline Configuration Details

<!-- If this PR includes baseline configuration changes, please provide details -->

### Affected Baselines
- [ ] Laravel 10 baseline
- [ ] Laravel 11 baseline
- [ ] Filament 3 baseline
- [ ] Livewire 3 baseline
- [ ] Spatie packages baseline
- [ ] Custom/new baseline: 

### PHPStan Levels Tested
- [ ] Level 0-2
- [ ] Level 3-5
- [ ] Level 6-8
- [ ] Level 9-10

### Package Versions Tested
- Laravel: 
- Filament: 
- Livewire: 
- PHPStan: 
- Other packages: 

## Testing

<!-- Describe how you tested your changes -->

### Test Environment
- PHP Version: 
- Laravel Version: 
- Filament Version: 
- PHPStan Version: 

### Test Results
- [ ] All existing tests pass
- [ ] New tests added and pass
- [ ] Manual testing completed
- [ ] Baseline configurations tested against real projects
- [ ] No new PHPStan errors introduced
- [ ] Performance impact assessed

### Test Commands Run
```bash
# List the commands you ran to test your changes
composer install
vendor/bin/phpstan analyse --configuration=baselines/your-baseline.neon
```

## Screenshots/Output

<!-- If applicable, add screenshots or command output -->

```
<!-- Paste PHPStan output or other relevant command output here -->
```

## Checklist

### Code Quality
- [ ] My code follows the project's coding standards
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] My changes generate no new warnings or errors

### Documentation
- [ ] I have updated the documentation accordingly
- [ ] I have updated the README.md if needed
- [ ] I have added/updated baseline configuration documentation

### Testing
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
- [ ] I have tested the baseline configurations manually

### Compatibility
- [ ] My changes maintain backward compatibility
- [ ] I have considered the impact on different Laravel versions
- [ ] I have considered the impact on different Filament versions
- [ ] I have tested with multiple PHPStan levels

## Additional Notes

<!-- Add any additional notes, concerns, or context about this PR -->

## Breaking Changes

<!-- If this PR includes breaking changes, describe them here -->

- None

## Migration Guide

<!-- If this PR requires users to change their configuration, provide guidance -->

<!-- If no migration is needed, you can remove this section -->

---

By submitting this pull request, I confirm that:
- [ ] I have read and agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md)
- [ ] I have read the [Contributing Guide](CONTRIBUTING.md)
- [ ] My contribution is made under the terms of the project license