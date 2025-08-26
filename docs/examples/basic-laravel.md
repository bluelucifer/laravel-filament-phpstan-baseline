# Basic Laravel Application Example

This example demonstrates how to use the Laravel PHPStan baseline patterns in a typical Laravel application.

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── UserController.php
│   │   └── PostController.php
│   ├── Requests/
│   │   ├── StoreUserRequest.php
│   │   └── UpdatePostRequest.php
│   └── Middleware/
│       └── CheckUserRole.php
├── Models/
│   ├── User.php
│   └── Post.php
├── Services/
│   └── UserService.php
└── Repositories/
    └── PostRepository.php
```

## PHPStan Configuration

```neon
# phpstan.neon
includes:
    - vendor/laravel-filament/phpstan-baseline/baselines/laravel-11.neon

parameters:
    paths:
        - app/
        - routes/
        - database/
    level: 8
    
    ignoreErrors:
        # Project-specific patterns can go here
```

## Model Implementation

### User Model

```php
<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon $email_verified_at
 * @property string $password
 * @property string $remember_token
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<Post> $posts
 * @property-read \App\Models\Profile $profile
 * 
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder recent(int $days = 30)
 * @method static \Illuminate\Database\Eloquent\Builder whereEmail(string $email)
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the posts for the user.
     * 
     * @return HasMany<Post>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include recent users.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
```

### Post Model

```php
<?php
// app/Models/Post.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $title
 * @property string $content
 * @property bool $published
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $published_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\User $author
 * 
 * @method static \Illuminate\Database\Eloquent\Builder published()
 * @method static \Illuminate\Database\Eloquent\Builder byAuthor(\App\Models\User $user)
 */
class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'published',
        'published_at',
    ];

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Get the author of the post.
     * 
     * @return BelongsTo<User>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope a query to only include published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    /**
     * Scope a query to filter posts by author.
     */
    public function scopeByAuthor($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
}
```

## Controllers

### UserController

```php
<?php
// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        // These patterns work with baseline:
        // - Dynamic where clauses (whereEmail)
        // - Eloquent scopes (active, recent)
        // - Query builder methods (orderBy, paginate)
        
        $query = User::query();
        
        if ($request->filled('email')) {
            $query->whereEmail($request->input('email')); // Dynamic where clause
        }
        
        if ($request->boolean('active_only')) {
            $query->active(); // Eloquent scope
        }
        
        if ($request->filled('recent_days')) {
            $days = (int) $request->input('recent_days');
            $query->recent($days); // Eloquent scope with parameter
        }
        
        $users = $query
            ->orderBy('created_at', 'desc') // Query builder method
            ->paginate(15); // Query builder method
        
        return response()->json($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        // Form request validated data works with baseline
        $validated = $request->validated(); // Mixed return type
        
        $user = $this->userService->createUser($validated);
        
        return response()->json($user, 201);
    }

    /**
     * Display the specified user with posts.
     */
    public function show(User $user): JsonResponse
    {
        // Relationship loading and dynamic properties work with baseline
        $user->load([
            'posts' => function ($query) {
                $query->published() // Eloquent scope
                      ->orderBy('published_at', 'desc'); // Query builder method
            }
        ]);
        
        // Access to dynamic properties works with baseline
        $userData = [
            'id' => $user->id,
            'name' => $user->name, // Dynamic property access
            'email' => $user->email, // Dynamic property access
            'posts_count' => $user->posts->count(), // Collection method
            'latest_post' => $user->posts->first(), // Collection method
        ];
        
        return response()->json($userData);
    }
}
```

## Form Requests

### StoreUserRequest

```php
<?php
// app/Http/Requests/StoreUserRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * @return array<string, mixed> // This type works with baseline
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     * 
     * @return array<string, mixed> // This type works with baseline
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.unique' => 'This email address is already taken.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
```

## Services

### UserService

```php
<?php
// app/Services/UserService.php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Create a new user.
     * 
     * @param array<string, mixed> $data
     */
    public function createUser(array $data): User
    {
        // Laravel helper functions work with baseline
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']), // Facade method
            ]);
            
            // Send welcome email
            // app() helper function works with baseline
            $mailer = app('mailer');
            
            return $user;
        });
    }

    /**
     * Get active users with their post counts.
     * 
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getActiveUsersWithPostCounts()
    {
        return User::active() // Eloquent scope
                   ->withCount('posts') // Query builder method
                   ->orderBy('posts_count', 'desc') // Query builder method
                   ->get(); // Collection return
    }

    /**
     * Search users by various criteria.
     * 
     * @param array<string, mixed> $criteria
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function searchUsers(array $criteria)
    {
        $query = User::query();
        
        if (isset($criteria['name'])) {
            // Dynamic where clause works with baseline
            $query->where('name', 'like', "%{$criteria['name']}%");
        }
        
        if (isset($criteria['email'])) {
            // Dynamic where clause works with baseline
            $query->whereEmail($criteria['email']);
        }
        
        if (isset($criteria['created_after'])) {
            $query->where('created_at', '>=', $criteria['created_after']);
        }
        
        return $query->get();
    }
}
```

## Middleware

### CheckUserRole

```php
<?php
// app/Http/Middleware/CheckUserRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     * 
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Auth facade and user properties work with baseline
        $user = auth()->user(); // Facade method
        
        if (!$user || !$user->hasRole($role)) { // Dynamic property/method access
            abort(403, 'Insufficient permissions');
        }
        
        return $next($request);
    }
}
```

## Repository Pattern

### PostRepository

```php
<?php
// app/Repositories/PostRepository.php

namespace App\Repositories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PostRepository
{
    /**
     * Get published posts with authors.
     * 
     * @return \Illuminate\Database\Eloquent\Collection<int, Post>
     */
    public function getPublishedWithAuthors(): Collection
    {
        return Post::published() // Eloquent scope
                   ->with('author') // Eager loading
                   ->orderBy('published_at', 'desc') // Query builder method
                   ->get(); // Collection return
    }

    /**
     * Get posts by author with pagination.
     */
    public function getPostsByAuthor(User $author, int $perPage = 15): LengthAwarePaginator
    {
        return Post::byAuthor($author) // Eloquent scope with parameter
                   ->published() // Eloquent scope
                   ->orderBy('published_at', 'desc') // Query builder method
                   ->paginate($perPage); // Pagination
    }

    /**
     * Search posts by title and content.
     * 
     * @return \Illuminate\Database\Eloquent\Collection<int, Post>
     */
    public function searchPosts(string $term): Collection
    {
        return Post::where('title', 'like', "%{$term}%") // Query builder method
                   ->orWhere('content', 'like', "%{$term}%") // Query builder method
                   ->published() // Eloquent scope
                   ->with('author') // Eager loading
                   ->get(); // Collection return
    }

    /**
     * Get posts query builder for further customization.
     */
    public function query(): Builder
    {
        return Post::query(); // Returns Builder instance
    }
}
```

## Usage in Routes

```php
<?php
// routes/web.php

use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // These route definitions work with baseline
    Route::resource('users', UserController::class);
    Route::resource('posts', PostController::class);
    
    // Custom routes with middleware
    Route::get('dashboard', function () {
        $user = auth()->user(); // Facade method
        $posts = $user->posts() // Relationship method
                     ->published() // Eloquent scope
                     ->latest() // Query builder method
                     ->take(5) // Query builder method
                     ->get(); // Collection return
        
        return view('dashboard', compact('user', 'posts'));
    });
});
```

## Key Points

1. **Dynamic Method Calls**: The baseline handles Laravel's magic methods like `whereEmail()`, scopes like `active()`, and Query Builder methods.

2. **Mixed Return Types**: Form request validation, helper functions, and facade methods that return mixed types are covered.

3. **Relationship Methods**: Both the relationship method calls and property access work seamlessly.

4. **Collection Methods**: All Laravel Collection methods and higher-order proxy methods are supported.

5. **Type Safety**: While using the baseline, you can still add proper type hints and PHPDoc comments for better IDE support and documentation.

This example shows how the Laravel baseline patterns enable you to write idiomatic Laravel code while maintaining PHPStan's static analysis benefits at level 8.