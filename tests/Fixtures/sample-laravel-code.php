<?php

// Sample Laravel code to test baseline patterns against
// This file contains common Laravel patterns that should be covered by baselines

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SampleLaravelCode
{
    public function testEloquentPatterns()
    {
        // Magic methods on Eloquent Builder
        User::where('active', true)->whereEmail('test@example.com');
        
        // Eloquent scopes
        User::scopeActive($query);
        
        // Model properties
        $user = new User();
        $user->name = 'Test';
        $user->some_dynamic_property = 'value';
    }

    public function testRequestPatterns(Request $request)
    {
        // Request properties
        $name = $request->name;
        $email = $request->email;
        
        // Request input
        $data = $request->input('data');
        $config = config('app.name');
    }

    public function testCollectionPatterns()
    {
        $collection = collect([1, 2, 3]);
        
        // Collection methods
        $result = $collection->map(fn($item) => $item * 2);
        $filtered = $collection->filter(fn($item) => $item > 1);
    }

    public function testFacadePatterns()
    {
        // Cache facade
        Cache::get('key');
        Cache::remember('key', 3600, fn() => 'value');
    }
}

class User extends Model
{
    protected $fillable = ['name', 'email'];
    
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}