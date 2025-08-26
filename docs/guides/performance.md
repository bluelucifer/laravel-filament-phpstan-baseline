# Performance Optimization Guide

This guide covers how to optimize PHPStan baseline configurations for better performance while maintaining code quality.

## Table of Contents

- [Performance Considerations](#performance-considerations)
- [Baseline Optimization](#baseline-optimization)
- [Configuration Strategies](#configuration-strategies)
- [Benchmarking Tools](#benchmarking-tools)
- [CI/CD Optimization](#cicd-optimization)
- [Common Patterns Analysis](#common-patterns-analysis)
- [Best Practices](#best-practices)

## Performance Considerations

### Factors Affecting Performance

1. **Number of Patterns**: More patterns = longer analysis time
2. **Pattern Complexity**: Complex regex patterns slow down matching
3. **File Size**: Large baseline files take longer to parse
4. **Duplication**: Duplicate patterns across files waste resources
5. **PHPStan Level**: Higher levels require more analysis

### Performance Metrics

When optimizing, consider these metrics:

- **Analysis Time**: Total time for PHPStan to complete
- **Memory Usage**: Peak memory consumption during analysis
- **Error Detection**: Number of legitimate errors found vs. false positives
- **Maintainability**: Ease of updating and managing baselines

## Baseline Optimization

### 1. Remove Duplicate Patterns

Use the pattern analysis tool to identify duplicates:

```bash
php scripts/optimize-patterns.php baselines/
```

**Before optimization:**
```neon
# laravel-11.neon
- '#Cannot access offset .+ on mixed#'

# filament-3.neon  
- '#Cannot access offset .+ on mixed#'

# livewire-3.neon
- '#Cannot access offset .+ on mixed#'
```

**After optimization:**
```neon
# common-patterns.neon
- '#Cannot access offset .+ on mixed#'

# Other files include common-patterns.neon
```

### 2. Optimize Complex Patterns

Simplify overly complex regex patterns:

**Before:**
```neon
- '#Call to an undefined method (Illuminate\\Database\\Eloquent\\Builder|Illuminate\\Database\\Query\\Builder|Illuminate\\Database\\Eloquent\\Relations\\[A-Za-z]+)::[a-zA-Z_][a-zA-Z0-9_]*\(\)#'
```

**After:**
```neon
- '#Call to an undefined method Illuminate\\Database\\(Eloquent\\Builder|Query\\Builder|Eloquent\\Relations\\[A-Za-z]+)::[a-zA-Z_][a-zA-Z0-9_]*\(\)#'
```

### 3. Use Level-Specific Organization

Organize patterns by PHPStan level for better performance:

```neon
# For PHPStan level 0-2 (basic)
includes:
    - baselines/level-0-2.neon

# For PHPStan level 3-5 (moderate)  
includes:
    - baselines/level-0-2.neon
    - baselines/level-3-5.neon

# For PHPStan level 6-8 (strict)
includes:
    - baselines/level-0-2.neon
    - baselines/level-3-5.neon
    - baselines/level-6-8.neon
```

## Configuration Strategies

### Development Configuration (Speed Priority)

```neon
# phpstan-dev.neon - Fast analysis for development
includes:
    - baselines/common-patterns.neon
    - baselines/laravel-11.neon
    
parameters:
    level: 5
    paths:
        - app/
    
    excludePaths:
        - app/Console/Kernel.php
        - database/migrations/*
        
    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
```

### CI/CD Configuration (Balance)

```neon
# phpstan-ci.neon - Balanced for continuous integration
includes:
    - baselines/common-patterns.neon
    - baselines/laravel-11.neon
    - baselines/filament-3.neon
    
parameters:
    level: 6
    paths:
        - app/
        - routes/
        
    tmpDir: var/phpstan
    checkMissingIterableValueType: true
```

### Production Configuration (Strict)

```neon
# phpstan-strict.neon - Maximum quality for releases
includes:
    - baselines/common-patterns.neon
    - baselines/laravel-11-strict.neon
    - baselines/filament-3-strict.neon
    - baselines/livewire-3-strict.neon
    
parameters:
    level: 8
    paths:
        - app/
        - routes/
        - config/
        - database/
    
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
    reportUnmatchedIgnoredErrors: true
```

### Framework-Specific Configurations

```neon
# Laravel-only projects
includes:
    - baselines/common-patterns.neon
    - baselines/laravel-11.neon

# Filament admin projects
includes:
    - baselines/common-patterns.neon
    - baselines/laravel-11.neon
    - baselines/filament-3.neon

# Full-stack Livewire projects
includes:
    - baselines/common-patterns.neon
    - baselines/laravel-11.neon
    - baselines/livewire-3.neon

# Complex applications
includes:
    - baselines/common-patterns.neon
    - baselines/laravel-11.neon
    - baselines/filament-3.neon
    - baselines/livewire-3.neon
    - baselines/spatie-packages.neon
```

## Benchmarking Tools

### Running Performance Benchmarks

Use the included benchmark tool:

```bash
php scripts/performance-benchmark.php
```

This will test different configurations and provide performance metrics.

### Manual Benchmarking

Compare configurations manually:

```bash
# Test current configuration
time vendor/bin/phpstan analyse

# Test optimized configuration
time vendor/bin/phpstan analyse --configuration=phpstan-optimized.neon

# Test with memory profiling
/usr/bin/time -v vendor/bin/phpstan analyse
```

### Measuring Memory Usage

```bash
# Check memory usage
php -d memory_limit=2G vendor/bin/phpstan analyse --memory-limit=2G

# Profile memory usage
php -d memory_limit=2G -d xdebug.mode=profile vendor/bin/phpstan analyse
```

## CI/CD Optimization

### GitHub Actions Example

```yaml
name: PHPStan Analysis

on: [push, pull_request]

jobs:
  phpstan:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          coverage: none
          
      - name: Cache Composer dependencies
        uses: actions/cache@v3
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist
        
      - name: Cache PHPStan results
        uses: actions/cache@v3
        with:
          path: var/phpstan
          key: phpstan-${{ github.sha }}
          restore-keys: phpstan-
          
      - name: Run PHPStan
        run: vendor/bin/phpstan analyse --configuration=phpstan-ci.neon
```

### Parallel Analysis

For large codebases, consider parallel analysis:

```bash
# Split analysis by directory
vendor/bin/phpstan analyse app/Http &
vendor/bin/phpstan analyse app/Models &  
vendor/bin/phpstan analyse app/Services &
wait
```

### Incremental Analysis

Analyze only changed files in CI:

```bash
# Get changed files
CHANGED_FILES=$(git diff --name-only --diff-filter=AM HEAD~1 | grep '\.php$' | tr '\n' ' ')

if [ -n "$CHANGED_FILES" ]; then
    vendor/bin/phpstan analyse $CHANGED_FILES
fi
```

## Common Patterns Analysis

### Identifying Problematic Patterns

Use the analysis tools to find performance bottlenecks:

```bash
# Analyze pattern complexity
php scripts/analyze-patterns.php baselines/

# Find duplicate patterns
php scripts/optimize-patterns.php baselines/
```

### Pattern Optimization Examples

**Complex Pattern:**
```neon
# Before (complexity: 15)
- '#Call to an undefined method (App\\Models\\[A-Za-z]+|Illuminate\\Database\\Eloquent\\[A-Za-z]+|Illuminate\\Database\\Query\\Builder)::[a-zA-Z_][a-zA-Z0-9_]*\(\)#'

# After (complexity: 8)  
- '#Call to an undefined method (App\\Models\\[A-Za-z]+|Illuminate\\Database\\(Eloquent\\[A-Za-z]+|Query\\Builder))::[a-zA-Z_][a-zA-Z0-9_]*\(\)#'
```

**Redundant Patterns:**
```neon
# Before (3 similar patterns)
- '#Call to an undefined method .+Builder::.+#'
- '#Call to an undefined method .+Builder::where[A-Z].+#'  
- '#Call to an undefined method .+Builder::scope[A-Z].+#'

# After (1 comprehensive pattern)
- '#Call to an undefined method .+Builder::(where[A-Z]|scope[A-Z]|[a-z]).+#'
```

## Best Practices

### 1. Incremental Optimization

Start with basic optimization and gradually improve:

1. **Level 1**: Remove obvious duplicates
2. **Level 2**: Simplify complex patterns  
3. **Level 3**: Organize by PHPStan level
4. **Level 4**: Create framework-specific configurations
5. **Level 5**: Add performance monitoring

### 2. Regular Maintenance

- **Weekly**: Check for new duplicate patterns
- **Monthly**: Review pattern complexity
- **Quarterly**: Benchmark performance changes
- **Yearly**: Major reorganization if needed

### 3. Development Workflow

```bash
# Development - fast feedback
composer phpstan-dev

# Pre-commit - moderate checking  
composer phpstan-check

# CI/CD - comprehensive analysis
composer phpstan-ci

# Release - strict validation
composer phpstan-strict
```

### 4. Monitoring Performance

Track performance metrics over time:

```bash
# Add to package.json scripts
{
  "scripts": {
    "phpstan-benchmark": "php scripts/performance-benchmark.php",
    "phpstan-analyze": "php scripts/analyze-patterns.php baselines/",
    "phpstan-optimize": "php scripts/optimize-patterns.php baselines/"
  }
}
```

### 5. Configuration Management

Use environment-specific configurations:

```php
// phpstan-config.php - Dynamic configuration
<?php

$level = $_ENV['PHPSTAN_LEVEL'] ?? 5;
$includes = [];

if ($_ENV['APP_ENV'] === 'production') {
    $includes[] = 'baselines/laravel-11-strict.neon';
} else {
    $includes[] = 'baselines/laravel-11.neon';
}

return [
    'includes' => $includes,
    'parameters' => [
        'level' => $level,
        'paths' => ['app/'],
    ],
];
```

## Performance Tips Summary

1. **Use common baseline files** to reduce duplication
2. **Organize patterns by complexity** and PHPStan level
3. **Cache PHPStan results** in CI/CD pipelines
4. **Exclude non-essential files** during development
5. **Use incremental analysis** for large codebases
6. **Monitor performance metrics** regularly
7. **Choose appropriate configurations** for different environments
8. **Optimize complex regex patterns** when possible
9. **Use parallel analysis** for very large projects
10. **Keep baselines up to date** with framework changes