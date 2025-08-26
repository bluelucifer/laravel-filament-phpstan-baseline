# Eloquent ORM Patterns

This document explains the PHPStan baseline patterns for Laravel's Eloquent ORM, helping you understand why certain patterns are ignored and how to work with them effectively.

## Table of Contents

- [Magic Methods on Eloquent Builder](#magic-methods-on-eloquent-builder)
- [Dynamic Where Clauses](#dynamic-where-clauses)
- [Eloquent Scopes](#eloquent-scopes)
- [Eloquent Relationships Generics](#eloquent-relationships-generics)
- [Model Properties](#model-properties)
- [Collection Methods](#collection-methods)

## Magic Methods on Eloquent Builder

### Pattern
```neon
- '#Call to an undefined method Illuminate\\Database\\Eloquent\\Builder#'
- '#Call to an undefined method Illuminate\\Database\\Query\\Builder#'
```

### Why This Pattern Exists

Laravel's Eloquent Builder uses PHP's `__call()` magic method to forward unknown method calls to the underlying Query Builder or apply them as scopes. PHPStan cannot statically analyze these dynamic method calls.

### Code Examples

#### Problematic Code (PHPStan Error)
```php
<?php

use App\Models\User;

// PHPStan error: Call to an undefined method
$users = User::where('active', true)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

#### Working Code (With Baseline)
```php
<?php

use App\Models\User;

// Works with baseline - these methods exist on Query Builder
$users = User::where('active', true)
    ->orderBy('created_at', 'desc')  // Query Builder method
    ->limit(10)                      // Query Builder method
    ->get();                         // Eloquent Builder method

// Also works with relationship queries
$posts = User::find(1)
    ->posts()                        // HasMany relationship
    ->where('published', true)       // Query Builder method
    ->latest()                       // Query Builder method
    ->get();
```

### Best Practices

1. **Use explicit typing when possible:**
```php
<?php

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class UserRepository
{
    public function getActiveUsers(): Builder
    {
        return User::where('active', true);
    }
}
```

2. **Add PHPDoc for complex queries:**
```php
<?php

/**
 * @return \Illuminate\Database\Eloquent\Collection<int, User>
 */
public function getRecentUsers(): Collection
{
    return User::where('created_at', '>=', now()->subDays(30))
        ->orderBy('created_at', 'desc')
        ->get();
}
```

## Dynamic Where Clauses

### Pattern
```neon
- '#Call to an undefined method .+::where[A-Z][a-zA-Z]+\(\)#'
```

### Why This Pattern Exists

Laravel allows dynamic where clauses like `whereEmail()`, `whereName()`, etc., which are generated at runtime based on column names.

### Code Examples

#### Problematic Code (PHPStan Error)
```php
<?php

use App\Models\User;

// PHPStan error: Call to an undefined method whereEmail()
$user = User::whereEmail('john@example.com')->first();
$users = User::whereStatus('active')->whereAge(25)->get();
```

#### Working Code (With Baseline)
```php
<?php

use App\Models\User;

// Works with baseline
$user = User::whereEmail('john@example.com')->first();
$users = User::whereFirstName('John')
    ->whereLastName('Doe')
    ->whereAge(25)
    ->get();

// Equivalent explicit where clauses (alternative approach)
$user = User::where('email', 'john@example.com')->first();
$users = User::where('first_name', 'John')
    ->where('last_name', 'Doe')
    ->where('age', 25)
    ->get();
```

### Best Practices

1. **Use explicit where clauses for better IDE support:**
```php
<?php

// Preferred - explicit and IDE-friendly
User::where('email', $email)->first();

// Works but less IDE support
User::whereEmail($email)->first();
```

2. **Document dynamic methods in model docblocks:**
```php
<?php

/**
 * @method static \Illuminate\Database\Eloquent\Builder whereEmail(string $email)
 * @method static \Illuminate\Database\Eloquent\Builder whereStatus(string $status)
 */
class User extends Model
{
    // ...
}
```

## Eloquent Scopes

### Pattern
```neon
- '#Call to an undefined method .+::scope[A-Z][a-zA-Z]+\(\)#'
```

### Why This Pattern Exists

Eloquent scopes are defined with the `scope` prefix but called without it, making them appear as undefined methods to PHPStan.

### Code Examples

#### Problematic Code (PHPStan Error)
```php
<?php

use App\Models\User;

class User extends Model
{
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

// PHPStan error: Call to an undefined method active()
$users = User::active()->recent(7)->get();
```

#### Working Code (With Baseline)
```php
<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class User extends Model
{
    /**
     * Scope a query to only include active users.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
    
    /**
     * Scope a query to only include recent users.
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

// Works with baseline
$users = User::active()->recent(7)->get();
$recentActiveUsers = User::active()->recent()->paginate(10);
```

### Best Practices

1. **Always type-hint scope parameters:**
```php
<?php

public function scopeByStatus(Builder $query, string $status): Builder
{
    return $query->where('status', $status);
}
```

2. **Document scopes in model docblock:**
```php
<?php

/**
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder recent(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder byStatus(string $status)
 */
class User extends Model
{
    // ...
}
```

## Eloquent Relationships Generics

### Pattern
```neon
- '#Generic type Illuminate\\Database\\Eloquent\\Relations\\(HasMany|HasOne|BelongsTo|BelongsToMany|MorphMany|MorphOne|MorphTo|HasManyThrough|HasOneThrough).+ does not specify all template types#'
```

### Why This Pattern Exists

Laravel 11 requires full generic specification for relationships, but legacy code and some use cases don't provide complete type information.

### Code Examples

#### Problematic Code (PHPStan Error)
```php
<?php

use App\Models\Post;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    // PHPStan error: Generic type not fully specified
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
```

#### Working Code (With Baseline)
```php
<?php

use App\Models\Post;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    /**
     * @return HasMany<Post>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
    
    /**
     * @return HasMany<Post>
     */
    public function publishedPosts(): HasMany
    {
        return $this->hasMany(Post::class)->where('published', true);
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts; // Collection<Post>
```

### Best Practices

1. **Use PHPDoc annotations for relationships:**
```php
<?php

/**
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User>
 */
public function author(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

2. **Consider using Laravel 11's typed relationships:**
```php
<?php

// Laravel 11+ with proper generics (if supported)
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @return HasMany<Post, $this>
 */
public function posts(): HasMany
{
    return $this->hasMany(Post::class);
}
```

## Model Properties

### Pattern
```neon
- '#Access to an undefined property Illuminate\\Database\\Eloquent\\Model::\$[a-zA-Z0-9_]+#'
```

### Why This Pattern Exists

Eloquent models use magic methods to access attributes dynamically, making them appear as undefined properties to PHPStan.

### Code Examples

#### Working Code (With Baseline)
```php
<?php

use App\Models\User;

$user = User::find(1);

// These work with baseline but are dynamic properties
$name = $user->name;
$email = $user->email;
$createdAt = $user->created_at;

// Relationship properties
$posts = $user->posts;
$profile = $user->profile;
```

### Best Practices

1. **Define properties in model docblock:**
```php
<?php

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<Post> $posts
 */
class User extends Model
{
    protected $fillable = ['name', 'email'];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```

2. **Use accessor methods for computed properties:**
```php
<?php

class User extends Model
{
    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

// Usage
$fullName = $user->full_name; // Computed property
```

## Collection Methods

### Pattern
```neon
- '#Call to an undefined method Illuminate\\Support\\Collection::.+#'
- '#Call to an undefined method Illuminate\\Database\\Eloquent\\Collection::.+#'
```

### Why This Pattern Exists

Laravel Collections have many dynamic methods and higher-order proxy methods that PHPStan cannot analyze statically.

### Code Examples

#### Working Code (With Baseline)
```php
<?php

use App\Models\User;

$users = User::all();

// Collection methods work with baseline
$activeUsers = $users->filter(function ($user) {
    return $user->active;
});

$userNames = $users->pluck('name');
$groupedUsers = $users->groupBy('status');

// Higher-order proxy methods
$names = $users->map->name;
$activeUsers = $users->filter->active;
```

### Best Practices

1. **Use type hints in collection callbacks:**
```php
<?php

use App\Models\User;
use Illuminate\Support\Collection;

/** @var Collection<int, User> $users */
$users = User::all();

$activeUsers = $users->filter(function (User $user): bool {
    return $user->active;
});
```

2. **Document collection return types:**
```php
<?php

/**
 * @return \Illuminate\Support\Collection<int, string>
 */
public function getUserNames(): Collection
{
    return User::all()->pluck('name');
}
```

## Summary

These Eloquent ORM patterns handle the dynamic nature of Laravel's ORM system. While they allow flexible and expressive code, they can make static analysis challenging. The baseline patterns help PHPStan understand these common Laravel idioms while maintaining the flexibility that makes Laravel productive.

For the most robust code, consider:
- Adding proper type hints and PHPDoc comments
- Using explicit methods where possible
- Documenting dynamic properties and methods
- Leveraging Laravel's latest typing improvements