# Getting Started Guide

This guide will help you get started with the Laravel Filament PHPStan Baseline package, from installation to configuration and first analysis.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Basic Configuration](#basic-configuration)
- [Choosing the Right Baselines](#choosing-the-right-baselines)
- [Running Your First Analysis](#running-your-first-analysis)
- [Understanding the Results](#understanding-the-results)
- [Common Issues and Solutions](#common-issues-and-solutions)
- [Next Steps](#next-steps)

## Prerequisites

Before getting started, ensure you have:

- PHP 8.1 or higher
- Composer installed
- A Laravel project (version 10.x or 11.x)
- Basic understanding of PHPStan

## Installation

### Step 1: Install PHPStan

If you haven't already installed PHPStan in your project:

```bash
composer require --dev phpstan/phpstan
```

### Step 2: Install the Baseline Package

```bash
composer require --dev laravel-filament/phpstan-baseline
```

### Step 3: Verify Installation

Check that the package was installed correctly:

```bash
ls vendor/laravel-filament/phpstan-baseline/baselines/
```

You should see various `.neon` files like `laravel-11.neon`, `filament-3.neon`, etc.

## Basic Configuration

### Step 1: Create PHPStan Configuration

Create a `phpstan.neon` file in your project root:

```neon
# phpstan.neon - Basic configuration
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/laravel-11.neon

parameters:
    paths:
        - app/
        - routes/
        - database/
    
    level: 5
    
    # Bootstrap file for Laravel
    bootstrapFiles:
        - bootstrap/app.php
    
    # Ignore missing return types in some cases
    checkMissingIterableValueType: false
```

### Step 2: Start with Level 5

Begin with PHPStan level 5 and gradually increase:

- **Level 0-2**: Basic syntax errors
- **Level 3-5**: Good balance of strictness and practicality
- **Level 6-8**: Very strict, requires more type annotations
- **Level 9**: Maximum strictness

### Step 3: Test the Configuration

Run PHPStan to verify your configuration:

```bash
vendor/bin/phpstan analyse
```

## Choosing the Right Baselines

Select the appropriate baseline files based on your project dependencies:

### Laravel Framework Baselines

```neon
# For Laravel 11.x projects
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/laravel-11.neon

# For Laravel 10.x projects
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/laravel-10.neon

# For strict mode (higher PHPStan levels)
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/laravel-11-strict.neon
```

### Filament Admin Panel Baselines

```neon
# For Filament 3.x
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/filament-3.neon

# For strict Filament analysis
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/filament-3-strict.neon
```

### Livewire Baselines

```neon
# For Livewire 3.x
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/livewire-3.neon

# For strict Livewire analysis
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/livewire-3-strict.neon
```

### Package-Specific Baselines

```neon
# For Spatie packages
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/spatie-packages.neon

# For Laravel Excel
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/laravel-excel.neon
```

### Combined Configuration Example

For a typical Filament admin panel project:

```neon
# phpstan.neon - Full Filament project
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/laravel-11.neon
    - vendor/laravel-filament/phpstan-baseline/baselines/filament-3.neon
    - vendor/laravel-filament/phpstan-baseline/baselines/livewire-3.neon
    - vendor/laravel-filament/phpstan-baseline/baselines/spatie-packages.neon

parameters:
    paths:
        - app/
        - routes/
        - database/
        - config/
    
    level: 6
    
    bootstrapFiles:
        - bootstrap/app.php
    
    excludePaths:
        - app/Console/Kernel.php
        - app/Http/Kernel.php
        - database/migrations/*
    
    # Custom ignores for your specific project
    ignoreErrors:
        - '#your custom patterns here#'
```

## Running Your First Analysis

### Basic Analysis

```bash
vendor/bin/phpstan analyse
```

### Analyze Specific Directories

```bash
vendor/bin/phpstan analyse app/Models
vendor/bin/phpstan analyse app/Filament/Resources
```

### Generate Baseline for Existing Issues

If you have an existing project with many PHPStan errors:

```bash
vendor/bin/phpstan analyse --generate-baseline
```

This creates a `phpstan-baseline.neon` file with your current errors.

### Clear Result Cache

If you make configuration changes:

```bash
vendor/bin/phpstan clear-result-cache
vendor/bin/phpstan analyse
```

## Understanding the Results

### Clean Run Output

When everything passes:
```
Note: Using configuration file phpstan.neon.
 47/47 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

 [OK] No errors
```

### Errors Found

When PHPStan finds issues:
```
------ -----------------------------------------
 Line   app/Http/Controllers/UserController.php  
------ -----------------------------------------
 23     Call to an undefined method User::customMethod().
 45     Parameter $request of method store() expects StoreUserRequest, Request given.
------ -----------------------------------------

 [ERROR] Found 2 errors
```

### What the Baseline Covers

The baseline patterns ignore common Laravel/Filament patterns like:

✅ **Covered by baseline:**
- `User::whereEmail('test@example.com')` - Dynamic where clauses
- `$user->posts` - Eloquent relationships
- `TextInput::make('name')->required()` - Filament method chaining
- `auth()->user()` - Facade methods
- `$request->validated()` - Form request data

❌ **Not covered (you need to fix):**
- Actual typos in method names
- Incorrect parameter types
- Logic errors
- Project-specific patterns

## Common Issues and Solutions

### Issue 1: "Bootstrap file not found"

```
Bootstrap file bootstrap/app.php does not exist.
```

**Solution:**
```neon
# Remove or update bootstrap file path
parameters:
    # bootstrapFiles:
    #     - bootstrap/app.php
```

### Issue 2: "Memory limit exceeded"

```
Fatal error: Allowed memory size exhausted
```

**Solution:**
```bash
php -d memory_limit=2G vendor/bin/phpstan analyse
```

Or in your configuration:
```neon
parameters:
    tmpDir: var/phpstan
    memoryLimitFile: var/phpstan/memory_limit
```

### Issue 3: "Too many errors"

If you get overwhelmed with errors:

1. **Start with a lower level:**
```neon
parameters:
    level: 3  # Instead of 8
```

2. **Exclude problematic paths:**
```neon
parameters:
    excludePaths:
        - database/migrations/*
        - app/Console/Commands/LegacyCommands/*
```

3. **Generate a baseline:**
```bash
vendor/bin/phpstan analyse --generate-baseline
```

### Issue 4: "Baseline patterns not working"

Ensure you're including the right baseline files:

```neon
# Check that includes are at the top level
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/laravel-11.neon

# Not inside parameters section
parameters:
    # includes go above this section, not inside
```

## Next Steps

Once you have PHPStan running successfully:

1. **Gradually Increase Level**
   - Start at level 5
   - Fix any remaining errors
   - Move to level 6, then 7, etc.

2. **Add Custom Rules**
   - Create project-specific baseline patterns
   - Add strict typing where beneficial

3. **Integrate with CI/CD**
   ```bash
   # In your GitHub Actions or similar
   - name: Run PHPStan
     run: vendor/bin/phpstan analyse --error-format=github
   ```

4. **Explore Advanced Features**
   - Custom PHPStan rules
   - Type inference improvements
   - IDE integration

5. **Read Pattern Documentation**
   - Understand why patterns exist: [Pattern Documentation](../patterns/)
   - Learn from examples: [Code Examples](../examples/)
   - Follow best practices: [Usage Guides](../guides/)

## Getting Help

If you encounter issues:

1. **Check the documentation**: Browse the [patterns](../patterns/) and [examples](../examples/)
2. **Search existing issues**: Look at the project's GitHub issues
3. **Create a minimal reproduction**: Isolate the problem in a small test case
4. **Ask for help**: Open an issue with your configuration and error details

## Summary

You should now have:

- ✅ PHPStan installed and configured
- ✅ Appropriate baseline files included
- ✅ Successfully run your first analysis
- ✅ Understanding of what errors are normal vs. concerning
- ✅ A path forward to improve your code quality

The baseline patterns allow you to use PHPStan effectively with Laravel and Filament while maintaining the framework's expressive, dynamic features.