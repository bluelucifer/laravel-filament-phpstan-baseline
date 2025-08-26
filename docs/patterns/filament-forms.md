# Filament Forms Patterns

This document explains the PHPStan baseline patterns for Filament's form system, covering form components, state management, and dynamic form building.

## Table of Contents

- [Form Components Method Chaining](#form-components-method-chaining)
- [Form Builder Dynamic Methods](#form-builder-dynamic-methods)
- [Form State and Data](#form-state-and-data)
- [Form Component Callbacks](#form-component-callbacks)
- [Custom Form Components](#custom-form-components)

## Form Components Method Chaining

### Pattern
```neon
- '#Call to an undefined method Filament\\Forms\\Components\\[A-Za-z]+::[a-z][a-zA-Z]+\(\)#'
```

### Why This Pattern Exists

Filament form components use extensive method chaining with fluent interfaces. Many methods are dynamically generated or inherited, making them difficult for PHPStan to analyze statically.

### Code Examples

#### Problematic Code (PHPStan Error)
```php
<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

// PHPStan errors: Undefined methods
$components = [
    TextInput::make('name')
        ->required()
        ->maxLength(255)
        ->autocomplete('name'),
        
    Select::make('status')
        ->options([
            'active' => 'Active',
            'inactive' => 'Inactive',
        ])
        ->default('active')
        ->required(),
        
    Textarea::make('description')
        ->rows(4)
        ->columnSpanFull(),
];
```

#### Working Code (With Baseline)
```php
<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;

// All method chaining works with baseline
$components = [
    TextInput::make('name')
        ->required()
        ->maxLength(255)
        ->autocomplete('name')
        ->placeholder('Enter your name')
        ->helperText('This will be displayed publicly'),
        
    Select::make('category_id')
        ->relationship('category', 'name')
        ->searchable()
        ->preload()
        ->createOptionForm([
            TextInput::make('name')->required(),
        ]),
        
    FileUpload::make('avatar')
        ->image()
        ->avatar()
        ->imageEditor()
        ->circleCropper()
        ->directory('avatars'),
        
    DatePicker::make('published_at')
        ->native(false)
        ->displayFormat('d/m/Y')
        ->closeOnDateSelection(),
];
```

#### Advanced Form Components
```php
<?php

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Section;

$components = [
    Section::make('User Details')
        ->description('Manage user information')
        ->schema([
            TextInput::make('first_name')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('slug', \Str::slug($state));
                }),
        ])
        ->collapsible()
        ->collapsed(false),
        
    Repeater::make('addresses')
        ->schema([
            TextInput::make('street')->required(),
            TextInput::make('city')->required(),
            Select::make('country')
                ->options(['US' => 'United States', 'CA' => 'Canada'])
                ->required(),
        ])
        ->addActionLabel('Add Address')
        ->defaultItems(1)
        ->reorderable()
        ->collapsible(),
        
    Builder::make('content')
        ->blocks([
            Builder\Block::make('heading')
                ->schema([
                    TextInput::make('content')->required(),
                    Select::make('level')
                        ->options([
                            'h1' => 'Heading 1',
                            'h2' => 'Heading 2',
                            'h3' => 'Heading 3',
                        ]),
                ]),
        ])
        ->minItems(1),
];
```

### Best Practices

1. **Use consistent method chaining style:**
```php
<?php

// Preferred - each method on new line
TextInput::make('name')
    ->required()
    ->maxLength(255)
    ->autocomplete('name');

// Also acceptable for short chains
TextInput::make('name')->required();
```

2. **Group related configurations:**
```php
<?php

TextInput::make('email')
    // Validation
    ->required()
    ->email()
    ->unique(User::class)
    // UI
    ->placeholder('Enter email address')
    ->prefixIcon('heroicon-m-envelope')
    // Behavior
    ->live(onBlur: true)
    ->afterStateUpdated(fn ($state, $set) => $set('username', $state));
```

## Form Builder Dynamic Methods

### Pattern
```neon
- '#Call to an undefined method Filament\\Forms\\Builder::.+#'
```

### Why This Pattern Exists

The Filament Forms Builder provides many dynamic methods that are not easily analyzed by PHPStan.

### Code Examples

#### Working Code (With Baseline)
```php
<?php

use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;

public function form(Form $form): Form
{
    return $form
        ->schema([
            TextInput::make('name')->required(),
        ])
        ->statePath('data')
        ->model(User::class)
        ->operation('create');
}
```

## Form State and Data

### Pattern
```neon
- '#Variable \$data might not be defined#'
- '#Cannot access offset .+ on array\|null#'
- '#Parameter \$state of .+ expects .+, mixed given#'
```

### Why This Pattern Exists

Form data and state in Filament can be dynamic and context-dependent, making it difficult for PHPStan to track variable definitions and types.

### Code Examples

#### Problematic Code (PHPStan Error)
```php
<?php

use Filament\Forms\Components\TextInput;

// PHPStan error: $data might not be defined
TextInput::make('name')
    ->default(fn () => $data['user_name'] ?? '');
```

#### Working Code (With Baseline)
```php
<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;

// Works with baseline - accessing form data
TextInput::make('name')
    ->default(function (Get $get, $record) {
        return $record?->name ?? $get('default_name');
    })
    ->live()
    ->afterStateUpdated(function ($state, callable $set, Get $get) {
        if (!$get('slug')) {
            $set('slug', \Str::slug($state));
        }
    });

// Form with conditional logic
TextInput::make('discount_amount')
    ->numeric()
    ->visible(fn (Get $get) => $get('discount_type') === 'fixed')
    ->required(fn (Get $get) => $get('discount_type') === 'fixed');

// Complex state management
Select::make('country')
    ->options(['US' => 'United States', 'CA' => 'Canada'])
    ->live()
    ->afterStateUpdated(function ($state, callable $set) {
        $set('states', []);
        if ($state === 'US') {
            $set('states', ['CA' => 'California', 'NY' => 'New York']);
        }
    });
```

### Best Practices

1. **Always type-hint callback parameters when possible:**
```php
<?php

use App\Models\User;
use Filament\Forms\Get;
use Filament\Forms\Set;

TextInput::make('name')
    ->afterStateUpdated(function (string $state, Set $set, Get $get, ?User $record): void {
        // Now PHPStan knows the types
        if ($record && !$record->slug) {
            $set('slug', \Str::slug($state));
        }
    });
```

2. **Use null coalescing for safe data access:**
```php
<?php

TextInput::make('name')
    ->default(fn (?User $record): string => $record?->name ?? '');
```

## Form Component Callbacks

### Pattern
```neon
- '#Parameter \$get of .+ expects .+, mixed given#'
- '#Parameter \$set of .+ expects .+, mixed given#'
- '#Parameter \$state of .+ expects .+, mixed given#'
```

### Why This Pattern Exists

Form component callbacks receive various parameters that can have mixed types depending on the context and form state.

### Code Examples

#### Working Code (With Baseline)
```php
<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;

// Form with complex interactions
$components = [
    Select::make('product_id')
        ->options(Product::pluck('name', 'id'))
        ->live()
        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
            if ($state) {
                $product = Product::find($state);
                $set('price', $product?->price ?? 0);
                $set('tax_rate', $product?->category?->tax_rate ?? 0);
            }
        }),
        
    TextInput::make('quantity')
        ->numeric()
        ->default(1)
        ->live(onBlur: true)
        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
            $price = $get('price') ?? 0;
            $quantity = (int) ($state ?? 1);
            $set('total', $price * $quantity);
        }),
        
    TextInput::make('discount')
        ->suffix('%')
        ->numeric()
        ->live()
        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
            $total = $get('total') ?? 0;
            $discount = (float) ($state ?? 0);
            $discountAmount = $total * ($discount / 100);
            $set('final_total', $total - $discountAmount);
        }),
];

// Conditional field visibility
$components[] = TextInput::make('custom_field')
    ->visible(function (Get $get): bool {
        return $get('show_advanced') === true;
    })
    ->required(function (Get $get): bool {
        return $get('show_advanced') === true && $get('custom_required') === true;
    });
```

### Best Practices

1. **Use type hints in callbacks:**
```php
<?php

TextInput::make('name')
    ->afterStateUpdated(function (string $state, Set $set): void {
        $set('slug', \Str::slug($state));
    });
```

2. **Handle null states gracefully:**
```php
<?php

TextInput::make('price')
    ->afterStateUpdated(function (?string $state, Set $set): void {
        $price = $state ? (float) $state : 0;
        $set('total', $price * 1.2); // Add tax
    });
```

## Custom Form Components

### Pattern
```neon
- '#Method .+::getFormSchema\(\) return type has no value type#'
```

### Why This Pattern Exists

Custom form components and schema methods often return arrays with complex structures that PHPStan cannot fully analyze.

### Code Examples

#### Problematic Code (PHPStan Error)
```php
<?php

class CustomFormComponent
{
    // PHPStan error: No value type specified for array
    public function getFormSchema(): array
    {
        return [
            TextInput::make('name'),
            // ...
        ];
    }
}
```

#### Working Code (With Baseline)
```php
<?php

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;

class UserFormSchema
{
    /**
     * @return array<Component>
     */
    public function getFormSchema(): array
    {
        return [
            Section::make('Personal Information')
                ->schema([
                    TextInput::make('first_name')
                        ->required()
                        ->maxLength(255),
                        
                    TextInput::make('last_name')
                        ->required()
                        ->maxLength(255),
                        
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(User::class),
                ]),
                
            Section::make('Preferences')
                ->schema([
                    Select::make('timezone')
                        ->options(collect(timezone_identifiers_list())->mapWithKeys(
                            fn (string $timezone): array => [$timezone => $timezone]
                        )),
                        
                    Select::make('language')
                        ->options([
                            'en' => 'English',
                            'es' => 'Spanish',
                            'fr' => 'French',
                        ])
                        ->default('en'),
                ]),
        ];
    }
    
    /**
     * @return array<Component>
     */
    public function getAddressFormSchema(): array
    {
        return [
            TextInput::make('street')
                ->required()
                ->maxLength(255),
                
            TextInput::make('city')
                ->required()
                ->maxLength(255),
                
            Select::make('country')
                ->options([
                    'US' => 'United States',
                    'CA' => 'Canada',
                    'MX' => 'Mexico',
                ])
                ->required()
                ->live()
                ->afterStateUpdated(fn (callable $set) => $set('state', null)),
                
            Select::make('state')
                ->options(function (callable $get): array {
                    return match ($get('country')) {
                        'US' => [
                            'CA' => 'California',
                            'NY' => 'New York',
                            'TX' => 'Texas',
                        ],
                        'CA' => [
                            'ON' => 'Ontario',
                            'BC' => 'British Columbia',
                            'QC' => 'Quebec',
                        ],
                        default => [],
                    };
                })
                ->required()
                ->visible(fn (callable $get): bool => in_array($get('country'), ['US', 'CA'])),
        ];
    }
}

// Usage in Resource or Page
class UserResource extends Resource
{
    public static function form(Form $form): Form
    {
        $formSchema = new UserFormSchema();
        
        return $form
            ->schema($formSchema->getFormSchema())
            ->statePath('data');
    }
}
```

### Best Practices

1. **Always document return types:**
```php
<?php

/**
 * @return array<\Filament\Forms\Components\Component>
 */
public function getFormSchema(): array
{
    return [
        // components...
    ];
}
```

2. **Use static analysis friendly patterns:**
```php
<?php

use Filament\Forms\Components\Component;

class FormBuilder
{
    /**
     * @param array<string, mixed> $config
     * @return array<Component>
     */
    public function buildForm(array $config): array
    {
        $components = [];
        
        foreach ($config as $field => $settings) {
            $components[] = $this->createField($field, $settings);
        }
        
        return $components;
    }
    
    private function createField(string $name, array $settings): Component
    {
        return match ($settings['type'] ?? 'text') {
            'text' => TextInput::make($name),
            'select' => Select::make($name)->options($settings['options'] ?? []),
            default => TextInput::make($name),
        };
    }
}
```

## Summary

Filament's form system is highly dynamic and uses extensive method chaining, making static analysis challenging. The baseline patterns allow for:

- Fluent method chaining on form components
- Dynamic form state management
- Complex form callbacks and interactions
- Custom form component creation

To write more maintainable code:
- Add type hints to callback parameters
- Document custom methods with proper PHPDoc
- Use null-safe operations for form data access
- Consider creating reusable form schema classes