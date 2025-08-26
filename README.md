# Laravel & Filament PHPStan Baseline

🎯 **Community-maintained PHPStan exception patterns for Laravel & Filament projects**

This repository provides curated PHPStan baseline configurations and ignore patterns specifically designed for Laravel and Filament applications, helping you focus on real code issues rather than framework-specific false positives.

## 📋 Table of Contents

- [Why This Exists](#why-this-exists)
- [Quick Start](#quick-start)
- [Available Baselines](#available-baselines)
- [Usage](#usage)
- [Docker Development Environment](#docker-development-environment)
- [Contributing](#contributing)
- [Patterns Documentation](#patterns-documentation)
- [Version Management](#version-management)

## 🤔 Why This Exists

When using PHPStan with Laravel and Filament, you'll encounter many false positives due to:
- Magic methods and properties
- Dynamic query builders
- Livewire components
- Filament resources and fields
- Laravel's service container
- Package-specific patterns

Instead of each project maintaining its own ignore patterns, this repository provides a community-maintained baseline.

## 🚀 Quick Start

### Option 1: Composer Package (Recommended)

```bash
composer require --dev bluelucifer/laravel-filament-phpstan
```

Then add to your `phpstan.neon`:

```yaml
includes:
    - vendor/larastan/larastan/extension.neon
    # Automatically includes detected baselines
    - vendor/bluelucifer/laravel-filament-phpstan/extension.neon
```

Or use automatic detection:

```bash
# Generate config based on installed packages
vendor/bin/generate-phpstan-config

# This creates phpstan-laravel.neon with detected baselines
```

### Option 2: Manual Selection

After installing via Composer, manually choose baselines:

```yaml
# phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon
    # Choose specific baselines
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/laravel-11.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/filament-3.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/livewire-3.neon
```

### Option 3: Direct Download (Without Composer)

```bash
# Download the baselines you need
wget https://raw.githubusercontent.com/bluelucifer/laravel-filament-phpstan-baseline/main/baselines/laravel-11.neon
wget https://raw.githubusercontent.com/bluelucifer/laravel-filament-phpstan-baseline/main/baselines/filament-3.neon
```

## 📦 Available Baselines

### Core Baselines

- **`laravel-11.neon`** - Laravel 11.x framework patterns
- **`laravel-10.neon`** - Laravel 10.x framework patterns  
- **`filament-3.neon`** - Filament v3.x admin panel
- **`filament-2.neon`** - Filament v2.x admin panel
- **`livewire-3.neon`** - Livewire v3.x components

### Package-Specific Baselines

- **`laravel-excel.neon`** - Laravel Excel import/export
- **`laravel-nova.neon`** - Laravel Nova admin panel
- **`spatie-packages.neon`** - Common Spatie packages
- **`laravel-sanctum.neon`** - Laravel Sanctum API auth
- **`laravel-jetstream.neon`** - Laravel Jetstream

### Level-Specific Baselines

- **`level-0-2.neon`** - Levels 0-2 (Basic strictness)
- **`level-3-5.neon`** - Levels 3-5 (Intermediate strictness)
- **`level-6-8.neon`** - Levels 6-8 (Advanced strictness)
- **`level-9-10.neon`** - Levels 9-10 (Maximum strictness)

### Strict Mode Baselines (Additional suppressions for higher levels)

- **`laravel-11-strict.neon`** - Laravel strict mode patterns (levels 3-10)
- **`filament-3-strict.neon`** - Filament strict mode patterns (levels 3-10)
- **`livewire-3-strict.neon`** - Livewire strict mode patterns (levels 3-10)

## 🔧 Usage

### With Composer Package

```bash
# Install the package
composer require --dev bluelucifer/laravel-filament-phpstan

# Option A: Use automatic configuration
vendor/bin/generate-phpstan-config

# Option B: Manual configuration

```yaml
# phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon
    # Add the baselines you need
    - https://raw.githubusercontent.com/bluelucifer/laravel-filament-phpstan-baseline/main/baselines/laravel-11.neon
    - https://raw.githubusercontent.com/bluelucifer/laravel-filament-phpstan-baseline/main/baselines/filament-3.neon

parameters:
    paths:
        - app
        - config
        - database
        - routes
    
    level: 5
    
    # Your project-specific ignores (if any)
    ignoreErrors:
        - '#Your custom pattern#'
```

### Progressive Strictness

Start with permissive baselines and gradually increase strictness:

```yaml
# Level 0-2 (Beginner)
includes:
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/laravel-11.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/filament-3.neon
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/level-0-2.neon

# Level 3-5 (Intermediate)  
includes:
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/level-3-5.neon

# Level 6-8 (Advanced)
includes:
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/level-6-8.neon

# Level 9-10 (Maximum)
includes:
    - vendor/bluelucifer/laravel-filament-phpstan/baselines/level-9-10.neon
```

### Recommended Level Progression

1. **Start (Level 0-2)**: Use basic baselines
2. **Stabilize (Level 3-5)**: Add `level-3-5.neon`
3. **Improve (Level 6-8)**: Switch to `level-6-8.neon`
4. **Perfect (Level 9-10)**: Use `level-9-10.neon` for maximum type safety

## 🐳 Docker Development Environment

We provide a complete Docker-based development environment for contributors and users who want to test baselines locally.

### Quick Start with Docker

```bash
# 1. Clone the repository
git clone https://github.com/bluelucifer/laravel-filament-phpstan-baseline.git
cd laravel-filament-phpstan-baseline

# 2. Run setup script
./scripts/dev-setup.sh

# 3. Start development environment
docker-compose up app
```

### Docker Commands

```bash
# Start development container
docker-compose up app

# Enter container shell
docker-compose run --rm app bash

# Run tests
docker-compose run --rm app composer test

# Test specific baseline
./scripts/test-baseline.sh laravel-11.neon

# Validate all baselines
./scripts/validate-all.sh

# Test with multiple PHP versions
docker-compose --profile multi-php up
```

### Available Services

- **`app`**: Main development environment (PHP 8.2, Composer, Git)
- **`php81`**, **`php82`**, **`php83`**: Multi-version PHP testing
- **`laravel-test`**: Laravel application testing environment
- **`docs`**: Documentation server (nginx on port 8080)

### Environment Configuration

Copy `.env.example` to `.env` and customize:

```bash
# PHP Version (8.1, 8.2, 8.3)
PHP_VERSION=8.2

# PHPStan Configuration
PHPSTAN_LEVEL=8
PHPSTAN_MEMORY_LIMIT=1G

# Development mode
XDEBUG_MODE=off
```

### Development Scripts

| Script | Purpose |
|--------|---------|
| `scripts/dev-setup.sh` | Initial environment setup |
| `scripts/test-baseline.sh` | Test specific baseline file |
| `scripts/validate-all.sh` | Validate all baseline files |

### Docker Profiles

Use Docker Compose profiles for specific scenarios:

```bash
# Multi-PHP version testing
docker-compose --profile multi-php up

# Testing environment
docker-compose --profile testing up

# Documentation server
docker-compose --profile docs up
```

### Troubleshooting Docker

```bash
# Rebuild containers
docker-compose build --no-cache

# Clean up volumes
docker-compose down -v

# Check logs
docker-compose logs app

# Reset permissions
docker-compose run --rm app chown -R developer:developer /workspace
```

## 🤝 Contributing

We welcome contributions! Please help us improve these baselines:

1. **Report False Positives**: Open an issue with your PHPStan error
2. **Submit Patterns**: PR with new ignore patterns
3. **Test & Validate**: Test baselines with your projects
4. **Documentation**: Improve pattern explanations

### Contribution Guidelines

- Include comments explaining why each pattern is needed
- Group patterns by feature/package
- Test patterns with real projects
- Keep patterns as specific as possible

### File Naming Convention

When contributing new baseline files, please follow these naming conventions:

- **Package baselines**: `{package}-{version}.neon` (e.g., `laravel-11.neon`, `filament-3.neon`)
- **Level transition baselines**: `level-{start}-to-{end}.neon` (e.g., `level-0-to-2.neon`)
- Use lowercase and hyphens for consistency
- Version numbers should match major versions only

## 📖 Patterns Documentation

### Laravel Patterns

```yaml
# Eloquent Magic Methods
- '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder#'

# Query Builder Dynamic Where
- '#Call to an undefined method .+::where[A-Z][a-zA-Z]+\(\)#'

# Request Properties
- '#Access to an undefined property Illuminate\\Http\\Request::\$[a-zA-Z0-9_]+#'
```

### Filament Patterns

```yaml
# Form Components Chaining
- '#Call to an undefined method Filament\\Forms\\Components\\[A-Za-z]+::[a-z][a-zA-Z]+\(\)#'

# Resource $record Variable
- '#Variable \$record might not be defined#'
```

### Livewire Patterns

```yaml
# Public Properties
- '#Property .+::\$[a-zA-Z0-9_]+ has no type specified#'

# Computed Properties
- '#Method .+::computed.+ has no return type#'
```

## 🏷️ Version Compatibility

| Laravel | Filament | Livewire | Baseline File |
|---------|----------|----------|---------------|
| 11.x    | 3.x      | 3.x      | Use latest    |
| 10.x    | 3.x      | 3.x      | Use laravel-10 + filament-3 |
| 10.x    | 2.x      | 2.x      | Use laravel-10 + filament-2 |
| 9.x     | 2.x      | 2.x      | Use legacy branch |

## 📊 Statistics

- **Current Version**: 1.0.0
- **Patterns Maintained**: 150+
- **Projects Using**: Growing!
- **Contributors**: Welcome!
- **Last Updated**: 2025-08

## 📋 Version Management

This project follows [Semantic Versioning](https://semver.org/) and maintains a detailed [CHANGELOG.md](CHANGELOG.md) following the [Keep a Changelog](https://keepachangelog.com/) format.

### Version Policy

- **Major (X.0.0)**: Breaking changes, major structural modifications
- **Minor (X.Y.0)**: New baseline files, new features, backward-compatible changes  
- **Patch (X.Y.Z)**: Pattern fixes, bug fixes, documentation updates

### Release Process

All releases are automated via GitHub Actions:

1. **Create Release**: Use the release script or create a git tag
2. **Automated Testing**: CI/CD runs full test suite and validation
3. **GitHub Release**: Automatically created with changelog extraction
4. **Packagist Update**: Composer package automatically updated

### Release Commands

```bash
# Check current version
./scripts/version.sh

# Create a new release (dry run first)
./scripts/release.sh 1.1.0 --dry-run

# Actually create the release
./scripts/release.sh 1.1.0

# Manual git tag (triggers automated release)
git tag -a v1.1.0 -m "Release version 1.1.0"
git push origin v1.1.0
```

### Version History

See [CHANGELOG.md](CHANGELOG.md) for detailed version history and [GitHub Releases](https://github.com/bluelucifer/laravel-filament-phpstan-baseline/releases) for downloadable assets.

## 📄 License

MIT License - Use freely in your projects!

## 🔗 Links

- [PHPStan Documentation](https://phpstan.org/)
- [Larastan Package](https://github.com/nunomaduro/larastan)
- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [Releases](https://github.com/bluelucifer/laravel-filament-phpstan-baseline/releases)
- [Packagist](https://packagist.org/packages/bluelucifer/laravel-filament-phpstan)

---

**Made with ❤️ by the Laravel & Filament community**

⭐ Star this repository if you find it helpful!