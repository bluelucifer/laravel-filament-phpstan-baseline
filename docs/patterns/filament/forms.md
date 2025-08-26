# Filament Forms Patterns

## Overview

Filament's form builder uses a fluent interface with method chaining, which can cause PHPStan to report false positives. This document covers common form-related patterns.

## Common Issues and Patterns

### 1. Form Component Chaining

**Problem:**
```php
TextInput::make('name')
    ->required()
    ->maxLength(255)
    ->placeholder('Enter your name');
// PHPStan: Call to undefined method required(), maxLength(), etc.
```

**Why it happens:**
Form components use method chaining with dynamic methods that PHPStan cannot infer.

**Pattern:**
```neon
- message: '#^Call to undefined method Filament\\Forms\\Components\\[A-Za-z]+::[a-z][a-zA-Z]+\(\)#'
  paths:
    - app/Filament/**/*.php
```

### 2. Conditional Display Methods

**Problem:**
```php
TextInput::make('email')
    ->visible(fn ($get) => $get('type') === 'business')
    ->hidden(fn ($get) => $get('type') === 'personal');
```

**Why it happens:**
Visibility methods accept closures with dynamic parameters.

**Pattern:**
```neon
- message: '#^Parameter \$get of anonymous function has no type specified#'
  paths:
    - app/Filament/**/*.php
```

### 3. Validation Rules

**Problem:**
```php
TextInput::make('age')
    ->numeric()
    ->minValue(18)
    ->maxValue(100);
```

**Why it happens:**
Validation methods are dynamically added to components.

**Pattern:**
```neon
- message: '#^Call to undefined method .+::(numeric|minValue|maxValue|email|url)#'
  paths:
    - app/Filament/**/*.php
```

### 4. Relationship Fields

**Problem:**
```php
Select::make('category_id')
    ->relationship('category', 'name')
    ->searchable()
    ->preload();
```

**Why it happens:**
Relationship methods modify the component's behavior dynamically.

**Pattern:**
```neon
- message: '#^Call to undefined method .+::relationship\(\)#'
  paths:
    - app/Filament/**/*.php
```

### 5. Layout Components

**Problem:**
```php
Grid::make()
    ->columns(2)
    ->schema([
        TextInput::make('first_name'),
        TextInput::make('last_name'),
    ]);
```

**Why it happens:**
Layout components have schema methods that PHPStan doesn't recognize.

**Pattern:**
```neon
- message: '#^Call to undefined method .+::(columns|schema|columnSpan)#'
  paths:
    - app/Filament/**/*.php
```

## Form Builder Lifecycle

### Creation Lifecycle Hooks

```php
Forms\Components\TextInput::make('slug')
    ->afterStateUpdated(function ($state, $set) {
        $set('slug', Str::slug($state));
    })
    ->dehydrateStateUsing(fn ($state) => Str::slug($state));
```

**Patterns for lifecycle hooks:**
```neon
- message: '#^Call to undefined method .+::(afterStateUpdated|beforeStateDehydrated|dehydrateStateUsing)#'
  paths:
    - app/Filament/**/*.php
```

## Best Practices

1. **Use proper imports** to help PHPStan understand types:
   ```php
   use Filament\Forms\Components\TextInput;
   use Filament\Forms\Components\Select;
   ```

2. **Add return type hints** to form schema methods:
   ```php
   protected function getFormSchema(): array
   {
       return [
           TextInput::make('name'),
       ];
   }
   ```

3. **Type hint closure parameters** when possible:
   ```php
   ->visible(function (callable $get): bool {
       return $get('type') === 'business';
   })
   ```

## Testing Form Patterns

```bash
# Test form components
vendor/bin/phpstan analyse app/Filament/Resources/*/Forms

# Test with higher strictness
vendor/bin/phpstan analyse --level=8 app/Filament
```

## Related Documentation

- [Filament Tables](tables.md)
- [Filament Resources](resources.md)
- [Filament Actions](actions.md)