# Laravel Query Controller

A powerful Laravel base controller with advanced query capabilities for building APIs and web applications faster.

**Key Features:**
- 🔍 Dynamic filtering, searching, sorting & pagination
- 📦 Multiple response formats (Default, JSend, JSON:API)
- 🎨 Support for JSON APIs, Blade views, Inertia.js & Livewire
- 💎 Laravel API Resources support (automatic data transformation)
- ⚡ Hybrid controllers (API + Web in one)
- 🔧 Highly customizable and extensible

---

## Installation

```bash
composer require masum/laravel-query-controller
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=query-controller-config
```

---

## Quick Start

```php
<?php

namespace App\Http\Controllers\Api;

use Masum\QueryController\Controllers\ResourceController;
use App\Models\Product;

class ProductController extends ResourceController
{
    protected $model = Product::class;
    protected array $searchableFields = ['name', 'description', 'sku'];
    protected array $filterableFields = ['category_id', 'status', 'price'];
}
```

That's it! Your controller now supports:

```bash
# List with pagination
GET /api/products?per_page=20&page=2

# Search
GET /api/products?search=laptop

# Filter
GET /api/products?category_id=5&status=active

# Sort
GET /api/products?sort_by=price&sort_direction=asc

# Combine all
GET /api/products?search=laptop&category_id=5&sort_by=price&per_page=20
```

---

## Response Format

### Default Format

```json
{
  "success": true,
  "message": "Retrieved 10 of 45 records",
  "data": [...],
  "timestamp": "2024-10-30T10:30:00Z",
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 45,
      "last_page": 3
    }
  }
}
```

### Other Formats

**JSend:**
```env
API_RESPONSE_FORMATTER="\Masum\QueryController\Formatters\JSendFormatter"
```

**JSON:API:**
```env
API_RESPONSE_FORMATTER="\Masum\QueryController\Formatters\JsonApiFormatter"
```

**Custom:**
```php
// Implement ResponseFormatterInterface
'formatter' => \App\Formatters\CustomFormatter::class
```

---

## Advanced Features

### Relationship Search

```php
protected array $searchableFields = [
    'name',
    'category.name',  // Search in related category
    'brand.name'
];
```

### Eager Loading

```php
protected function getIndexWith(): array
{
    return ['category', 'brand', 'images'];
}
```

### Custom Filtering

```php
protected function applyAdditionalConditions(Builder $query, Request $request): Builder
{
    // Apply tenant filtering
    if ($user = $this->getUser()) {
        $query->where('organization_id', $user->organization_id);
    }

    return $query;
}
```

### Laravel API Resources

Use Laravel's API Resources for data transformation (recommended):

```php
<?php

namespace App\Http\Controllers\Api;

use Masum\QueryController\Controllers\ResourceController;
use App\Models\Product;
use App\Http\Resources\ProductResource;

class ProductController extends ResourceController
{
    protected $model = Product::class;
    protected ?string $resource = ProductResource::class;  // Automatic transformation!
    protected array $searchableFields = ['name', 'sku'];
}
```

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => [
                'amount' => $this->price,
                'formatted' => '$' . number_format($this->price, 2),
            ],
            'category' => $this->category?->name,
            'in_stock' => $this->stock > 0,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

### Manual Transformation

Or override the method for custom transformation:

```php
protected function transformIndexData($results, Request $request): array
{
    $items = $results instanceof LengthAwarePaginator
        ? $results->items()
        : $results->toArray();

    return array_map(fn($item) => [
        'id' => $item['id'],
        'name' => $item['name'],
        'formatted_price' => '$' . number_format($item['price'], 2),
    ], $items);
}
```

---

## View Support

The package automatically handles both API and web requests. Just configure the view path:

### Blade Views

```php
class ProductController extends ResourceController
{
    protected $model = Product::class;
    protected array $searchableFields = ['name', 'sku'];
    protected ?string $indexView = 'products.index';  // That's it!
}
```

```blade
{{-- resources/views/products/index.blade.php --}}
@foreach($data as $product)
    <div>{{ $product['name'] }}</div>
@endforeach

{{-- Laravel pagination --}}
{{ $paginator->links() }}
```

### Inertia.js

```php
class ProductController extends ResourceController
{
    protected $model = Product::class;
    protected array $searchableFields = ['name', 'sku'];
    protected ?string $indexInertiaComponent = 'Products/Index';  // Auto-detected!
}
```

### Livewire

```php
class ProductController extends ResourceController
{
    protected $model = Product::class;
    protected array $searchableFields = ['name', 'sku'];
    protected ?string $indexLivewireComponent = 'products.index';  // Auto-detected!
}
```

### Hybrid (API + Web)

No code needed! The controller automatically:
- Returns JSON when `Accept: application/json` header is present
- Returns the configured view for regular web requests
- All search, filter, sort, and pagination work in both modes!

---

## Filtering

### Exact Match
```
?status=active
```

### Array (IN query)
```
?category_id[]=1&category_id[]=2&category_id[]=3
```

### Range
```
?price[min]=100&price[max]=500
```

### Date Range
```
?created_at_from=2024-01-01&created_at_to=2024-12-31
```

### Boolean
```
?featured=true
```

### Null Checks
```
?deleted_at=null
?description=not_null
```

---

## Response Methods

```php
// Success responses
return $this->success('Success', $data);
return $this->created('Resource created', $data);

// Error responses
return $this->error('Error occurred', $errors);
return $this->validationError('Validation failed', $errors);
return $this->notFound('Resource not found');
return $this->unauthorized('Access denied');
return $this->forbidden('Forbidden');

// Paginated
return $this->paginated($paginator, 'Data retrieved');
```

---

## Configuration

```php
// config/query-controller.php

return [
    // Response formatter
    'formatter' => \Masum\QueryController\Formatters\DefaultFormatter::class,

    // Pagination
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    // Sorting
    'sorting' => [
        'default_column' => 'created_at',
        'default_direction' => 'desc',
    ],

    // Views
    'views' => [
        'enabled' => false,
        'inertia_enabled' => true,
        'livewire_enabled' => true,
    ],
];
```

### Per-Controller Overrides

```php
class ProductController extends ResourceController
{
    protected int $maxPerPage = 50;
    protected int $defaultPerPage = 25;
    protected string $defaultSort = 'name';
    protected string $defaultDirection = 'asc';
}
```

---

## Custom Formatter Example

```php
<?php

namespace App\Formatters;

use Masum\QueryController\Contracts\ResponseFormatterInterface;

class CustomFormatter implements ResponseFormatterInterface
{
    public function success($message, $data = null, $meta = null, $statusCode = 200): array
    {
        return [
            'ok' => true,
            'message' => $message,
            'payload' => $data,
            'metadata' => $meta,
        ];
    }

    public function error($message, $errors = null, $statusCode = 400, $meta = null): array
    {
        return [
            'ok' => false,
            'error' => $message,
            'details' => $errors,
        ];
    }

    public function paginated($paginator, $message, $additionalMeta = null): array
    {
        return $this->success($message, $paginator->items(), [
            'page' => $paginator->currentPage(),
            'total' => $paginator->total(),
        ]);
    }
}
```

---

## Complete CRUD Example

```php
<?php

namespace App\Http\Controllers\Api;

use Masum\QueryController\Controllers\ResourceController;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class ProductController extends ResourceController
{
    protected $model = Product::class;
    protected ?string $resource = ProductResource::class;  // Use Laravel Resources
    protected array $searchableFields = ['name', 'sku'];
    protected array $filterableFields = ['category_id', 'status', 'price'];

    // index() is inherited - supports search, filter, sort, paginate!
    // Automatically uses ProductResource for transformation

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
        ]);

        $product = Product::create($validated);

        // Use resource for single item response
        return $this->created('Product created', new ProductResource($product));
    }

    public function show(int $id)
    {
        $product = Product::with('category')->findOrFail($id);

        // Use resource for single item
        return $this->success('Product found', new ProductResource($product));
    }

    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->validated());

        return $this->success('Product updated', new ProductResource($product));
    }

    public function destroy(int $id)
    {
        Product::findOrFail($id)->delete();
        return $this->success('Product deleted');
    }
}
```

---

## Requirements

- PHP 8.1+
- Laravel 10.x, 11.x, or 12.x

---

## License

MIT License

---

## Support

For issues or questions:
- GitHub Issues: [https://github.com/masum/laravel-query-controller/issues](https://github.com/masum/laravel-query-controller/issues)
