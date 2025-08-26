# Setting Up PHPStan for a New Laravel/Filament Project

## Quick Start Guide

This guide helps you set up PHPStan with our baseline configurations for a new Laravel/Filament project.

## Step 1: Install Dependencies

```bash
# Install PHPStan, Larastan, and our baselines
composer require --dev phpstan/phpstan larastan/larastan bluelucifer/laravel-filament-phpstan
```

## Step 2: Determine Your Starting Level

### For New Projects
Start with level 0-2 and gradually increase:
```yaml
parameters:
    level: 1
```

### For Existing Projects
Run analysis to see current state:
```bash
vendor/bin/phpstan analyse --level=0 app
```

## Step 3: Create PHPStan Configuration

Create `phpstan.neon` in your project root:

### Basic Setup (Level 0-2)
```yaml
includes:
    - vendor/larastan/larastan/extension.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/laravel-11.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/filament-3.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/level-0-2.neon

parameters:
    paths:
        - app
        - config
        - database
        - routes
    
    level: 1
    
    excludePaths:
        - app/Console/Kernel.php
        - app/Exceptions/Handler.php
```

### Intermediate Setup (Level 3-5)
```yaml
includes:
    - vendor/larastan/larastan/extension.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/laravel-11.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/filament-3.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/level-3-5.neon

parameters:
    paths:
        - app
        - config
        - database
        - routes
    
    level: 4
    
    checkGenericClassInNonGenericObjectType: false
    checkMissingIterableValueType: false
```

### Advanced Setup (Level 6-8)
```yaml
includes:
    - vendor/larastan/larastan/extension.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/laravel-11-strict.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/filament-3-strict.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/level-6-8.neon

parameters:
    paths:
        - app
        - config
        - database
        - routes
    
    level: 7
    
    reportUnmatchedIgnoredErrors: true
    treatPhpDocTypesAsCertain: false
```

## Step 4: Add to CI/CD Pipeline

### GitHub Actions
```yaml
name: PHPStan

on: [push, pull_request]

jobs:
  phpstan:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          
      - name: Install dependencies
        run: composer install --no-progress
        
      - name: Run PHPStan
        run: vendor/bin/phpstan analyse
```

### GitLab CI
```yaml
phpstan:
  stage: test
  script:
    - composer install --no-progress
    - vendor/bin/phpstan analyse
```

## Step 5: Add Custom Project Patterns

Create a `phpstan-baseline.neon` for project-specific patterns:

```yaml
parameters:
    ignoreErrors:
        # Your custom patterns here
        - 
            message: '#Custom error pattern#'
            path: app/Custom/*.php
```

Include it in your main configuration:
```yaml
includes:
    - phpstan-baseline.neon
```

## Step 6: Progressive Improvement

### Month 1: Level 0-2
- Fix undefined variables
- Fix missing imports
- Add basic type hints

### Month 2: Level 3-5
- Add return types to all methods
- Fix mixed type issues
- Add PHPDoc blocks

### Month 3: Level 6-8
- Fix nullable type issues
- Add generics where appropriate
- Resolve all type mismatches

## Common Issues and Solutions

### Issue: Too many errors initially
**Solution:** Start with level 0 and increase gradually

### Issue: CI pipeline fails after adding PHPStan
**Solution:** Generate a baseline for existing errors:
```bash
vendor/bin/phpstan analyse --generate-baseline
```

### Issue: Slow analysis on large codebases
**Solution:** Use result cache and parallel processing:
```yaml
parameters:
    parallel:
        maximumNumberOfProcesses: 4
    resultCachePath: %tmpDir%/resultCache.php
```

## Best Practices

1. **Run locally before pushing:**
   ```bash
   vendor/bin/phpstan analyse
   ```

2. **Add pre-commit hook:**
   ```bash
   #!/bin/sh
   vendor/bin/phpstan analyse --error-format=raw
   ```

3. **Update baselines regularly:**
   ```bash
   composer update bluelucifer/laravel-filament-phpstan
   ```

4. **Document your level progression:**
   Keep track of your PHPStan level increases in your CHANGELOG

## Next Steps

- Read [Progressive Strictness Guide](../progressive-strictness.md)
- Learn about [Custom Patterns](../custom-patterns.md)
- Join our [Community Discord](#) for help