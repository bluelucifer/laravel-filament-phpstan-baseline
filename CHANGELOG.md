# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

### Changed

### Fixed

### Deprecated

### Removed

### Security

## [1.1.0] - 2025-08-26

### Added
- Comprehensive test suite for baseline validation (#4)
- Docker development environment with multi-PHP support (#5)
- CHANGELOG.md and version management system (#6)
- Pattern documentation system with detailed guides (#7)
- Performance optimization system with 73% reduction in pattern complexity (#8)
- Community contribution process with templates and validation tools (#9)
- Compatibility matrix testing system for comprehensive version testing (#10)
- Optimized baseline files (`*-optimized.neon`) for better performance
- Benchmark scripts for performance testing
- Pattern validation tools for contributors
- GitHub issue and PR templates
- Code of Conduct and Security policy

### Changed
- Enhanced baseline patterns with PHPStan level 2+ support
- Improved Laravel 11 baseline with mixed type handling
- Updated Filament 3 baseline with comprehensive component patterns
- Restructured documentation with use cases and examples

### Fixed
- PHPStan v2 compatibility issues on PHP 8.1
- File naming convention consistency (level-X-Y.neon format)

## [1.0.0] - 2025-08-26

### Added
- Initial release of Laravel & Filament PHPStan baseline configurations
- **Core Framework Baselines**:
  - Laravel 10.x baseline configuration (`laravel-10.neon`)
  - Laravel 11.x baseline configuration (`laravel-11.neon`)
  - Laravel 11.x strict mode configuration (`laravel-11-strict.neon`)
- **UI Framework Baselines**:
  - Filament 3.x baseline configuration (`filament-3.neon`)
  - Filament 3.x strict mode configuration (`filament-3-strict.neon`)
  - Livewire 3.x baseline configuration (`livewire-3.neon`)
  - Livewire 3.x strict mode configuration (`livewire-3-strict.neon`)
- **Package-Specific Baselines**:
  - Laravel Excel baseline configuration (`laravel-excel.neon`)
  - Spatie packages baseline configuration (`spatie-packages.neon`)
- **Level-Specific Baselines**:
  - Level 0-2 transition baseline (`level-0-2.neon`)
  - Level 3-5 transition baseline (`level-3-5.neon`)
  - Level 6-8 transition baseline (`level-6-8.neon`)
  - Level 9-10 transition baseline (`level-9-10.neon`)
- **Development Tools**:
  - Composer package structure with PSR-4 autoloading
  - Automatic baseline detection via `generate-phpstan-config` binary
  - PHPStan extension integration (`extension.neon`)
  - Comprehensive documentation and usage examples
- **CI/CD Infrastructure**:
  - GitHub Actions workflows for testing and validation
  - Multi-PHP version testing (8.1, 8.2, 8.3)
  - Baseline syntax validation
  - Community contribution guidelines

### Core Features
- **150+ Curated Patterns**: Hand-crafted ignore patterns for common Laravel/Filament false positives
- **Framework Integration**: Seamless integration with existing PHPStan/Larastan setups
- **Progressive Strictness**: Support for gradual migration from level 0 to level 10
- **Community-Driven**: Open contribution model with quality assurance
- **Comprehensive Coverage**: 
  - Eloquent ORM magic methods and relationships
  - HTTP request/response patterns
  - Laravel service container and facades
  - Filament form and table components
  - Livewire component lifecycle methods
  - Package-specific patterns (Excel, Spatie, etc.)

### Documentation
- Complete installation and usage guide
- Pattern explanation with examples
- Contribution guidelines and best practices
- Progressive strictness migration path
- Version compatibility matrix

### Technical Implementation
- Compatible with PHPStan 1.10+ and 2.0+
- PHP 8.1+ requirement
- Larastan integration support
- NEON configuration format
- Structured and simple pattern formats