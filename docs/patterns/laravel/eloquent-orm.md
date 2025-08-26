# Eloquent ORM Patterns

## Overview

Laravel's Eloquent ORM uses magic methods and properties extensively, which PHPStan cannot understand without proper baseline patterns. This document explains common Eloquent-related false positives and their solutions.

## Common Issues and Patterns

### 1. Model Property Access

**Problem:**
```php
$user = User::find(1);
echo $user->email; // PHPStan: Access to undefined property User::$email
```

**Why it happens:**
Eloquent models use magic `__get()` and `__set()` methods to access database columns as properties. PHPStan doesn't know these properties exist.

**Pattern:**
```neon
- message: '#^Access to undefined property Illuminate\\Database\\Eloquent\\Model::\$[a-zA-Z_]+#'
  path: app/Models/*.php
```

### 2. Query Builder Methods

**Problem:**
```php
User::where('active', true)->orderBy('name')->get();
// PHPStan: Call to undefined method User::where()
```

**Why it happens:**
Eloquent forwards method calls to the query builder through magic `__call()` method.

**Pattern:**
```neon
- message: '#^Call to undefined method .+::(where|orderBy|limit|first|get|find)#'
  paths:
    - app/Models/*.php
    - app/Http/Controllers/*.php
```

### 3. Dynamic Where Clauses

**Problem:**
```php
User::whereEmail('admin@example.com')->first();
// PHPStan: Call to undefined method User::whereEmail()
```

**Why it happens:**
Eloquent allows dynamic where clauses using `where{Column}` syntax.

**Pattern:**
```neon
- message: '#^Call to undefined method .+::where[A-Z][a-zA-Z]+\(\)#'
  paths:
    - app/**/*.php
```

### 4. Relationships

**Problem:**
```php
$user->posts()->create(['title' => 'New Post']);
// PHPStan: Call to undefined method User::posts()
```

**Why it happens:**
Relationship methods are defined but return different types based on how they're called.

**Pattern:**
```neon
- message: '#^Call to undefined method .+::(hasMany|belongsTo|hasOne|belongsToMany)#'
  paths:
    - app/Models/*.php
```

### 5. Collection Methods on Relationships

**Problem:**
```php
$user->posts->count();
// PHPStan: Access to undefined property User::$posts
```

**Why it happens:**
Relationships can be accessed as properties (returns Collection) or methods (returns Builder).

**Pattern:**
```neon
- message: '#^Access to undefined property .+::\$[a-zA-Z_]+#'
  paths:
    - app/Models/*.php
```

## Best Practices

1. **Use PHPDoc annotations** when possible:
   ```php
   /**
    * @property string $email
    * @property string $name
    */
   class User extends Model
   ```

2. **Consider using Laravel IDE Helper** to generate helper files

3. **Be specific with patterns** - avoid overly broad patterns that might hide real issues

## Testing Your Patterns

After adding patterns, test them:

```bash
# Run PHPStan with your baseline
vendor/bin/phpstan analyse --configuration=phpstan.neon

# Check if false positives are suppressed
vendor/bin/phpstan analyse --level=5 app/Models
```

## Related Patterns

- [Query Builder](query-builder.md)
- [Collections](collections.md)
- [Model Factories](factories.md)