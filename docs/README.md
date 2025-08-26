# Laravel Filament PHPStan Baseline Documentation

Welcome to the comprehensive documentation for the Laravel Filament PHPStan baseline patterns. This documentation helps you understand, use, and contribute to the baseline configurations.

## Table of Contents

- [Pattern Documentation](#pattern-documentation)
- [Code Examples](#code-examples)
- [Usage Guides](#usage-guides)
- [Contributing](#contributing)

## Pattern Documentation

Detailed explanations of each pattern category:

### Laravel Framework Patterns
- [Eloquent ORM Patterns](patterns/eloquent-orm.md) - Model relationships, queries, and magic methods
- [HTTP & Request Patterns](patterns/http-request.md) - Request handling and validation
- [Laravel Helpers & Facades](patterns/helpers-facades.md) - Helper functions and facade usage
- [Service Container](patterns/service-container.md) - Dependency injection and service resolution
- [Routing & Middleware](patterns/routing-middleware.md) - Route parameters and middleware patterns
- [Database & Migrations](patterns/database-migrations.md) - Schema operations and query building
- [Validation](patterns/validation.md) - Form validation and custom rules
- [Events & Listeners](patterns/events-listeners.md) - Event system patterns
- [Jobs & Queues](patterns/jobs-queues.md) - Background job processing
- [Caching](patterns/caching.md) - Cache operations and storage
- [File Storage](patterns/file-storage.md) - File handling and uploads
- [Authentication & Authorization](patterns/auth.md) - User authentication and authorization

### Filament Admin Panel Patterns
- [Filament Forms](patterns/filament-forms.md) - Form components and state management
- [Filament Tables](patterns/filament-tables.md) - Table columns, filters, and actions
- [Filament Resources](patterns/filament-resources.md) - Resource management and CRUD operations
- [Filament Pages](patterns/filament-pages.md) - Custom pages and lifecycle hooks
- [Filament Actions](patterns/filament-actions.md) - Action callbacks and modals
- [Filament Notifications](patterns/filament-notifications.md) - Notification system
- [Filament Widgets](patterns/filament-widgets.md) - Dashboard widgets and charts
- [Filament Navigation](patterns/filament-navigation.md) - Navigation and breadcrumbs
- [Filament Infolists](patterns/filament-infolists.md) - Information display components
- [Filament Relationships](patterns/filament-relationships.md) - Relation managers
- [Filament Authorization](patterns/filament-authorization.md) - Policy integration

### Additional Patterns
- [Livewire Integration](patterns/livewire-integration.md) - Livewire component patterns
- [Package-Specific Patterns](patterns/package-specific.md) - Spatie packages, Laravel Excel, etc.

## Code Examples

Real-world code examples demonstrating baseline usage:

- [Basic Laravel Application](examples/basic-laravel.md)
- [Filament Admin Panel](examples/filament-admin.md)
- [Complex Data Processing](examples/complex-processing.md)
- [Custom Components](examples/custom-components.md)

## Usage Guides

Step-by-step guides for different scenarios:

- [Getting Started](guides/getting-started.md)
- [Customizing Baselines](guides/customization.md)
- [Performance Tuning](guides/performance.md)
- [Troubleshooting](guides/troubleshooting.md)
- [Migration Guide](guides/migration.md)

## Contributing

Learn how to contribute to the baseline patterns:

- [Pattern Contribution](guides/contributing-patterns.md)
- [Documentation Guidelines](guides/documentation-guidelines.md)
- [Testing Patterns](guides/testing.md)

## Quick Start

1. **Install the package:**
   ```bash
   composer require --dev laravel-filament/phpstan-baseline
   ```

2. **Choose your baseline:**
   ```neon
   includes:
       - vendor/laravel-filament/phpstan-baseline/baselines/laravel-11.neon
       - vendor/laravel-filament/phpstan-baseline/baselines/filament-3.neon
   ```

3. **Run PHPStan:**
   ```bash
   vendor/bin/phpstan analyse
   ```

For more detailed instructions, see the [Getting Started Guide](guides/getting-started.md).