# Laravel API Controller Package

A powerful Laravel package providing a feature-rich base API controller with dynamic filtering, searching, sorting, pagination, and standardized JSON responses. Build RESTful APIs faster with consistent response formatting and advanced query capabilities.

## Features

- **Dynamic Filtering**: Filter by any field with support for:
  - Exact matches
  - Array values (IN queries)
  - Range filters (min/max)
  - Date ranges (_from, _to)
  - Boolean values
  - Null/Not null checks
- **Advanced Search**: Full-text search across multiple fields including relationships
- **Flexible Sorting**: Sort by any column with configurable defaults
- **Smart Pagination**: Automatic pagination with configurable per-page limits
- **Standardized Responses**: Consistent JSON response format across your API
- **Response Helpers**: Convenient methods for common HTTP responses
- **Query Hooks**: Extensible query building with multiple override points
- **Multi-Tenancy Support**: Built-in branch filtering and access control helpers
- **Configurable**: Extensive configuration options
- **Helper Functions**: Global helper functions for quick responses
- **Laravel 10.x & 11.x Support**

## Important Notes

⚠️ **Authorization:** This package does NOT include Laravel's `authorize()` method. You are responsible for implementing your own authorization logic in your controllers. See [Authorization](#authorization) section below.

## Installation

Install via Composer:

```bash
composer require masum/laravel-api-controller
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=api-controller-config
```

## Quick Start

### Basic Usage

Create a controller extending the `ApiController`:

```php
<?php

namespace App\Http\Controllers\Api;

use Masum\ApiController\Controllers\ApiController;
use App\Models\Product;

class ProductController extends ApiController
{
    protected $model = Product::class;

    protected array $searchableFields = ['name', 'description', 'sku'];

    protected array $filterableFields = ['category_id', 'status', 'price', 'created_at'];

    protected string $defaultSort = 'created_at';

    protected string $defaultDirection = 'desc';

    // The index() method is already implemented in the base class!
}
```

### API Endpoints

**GET /api/products**

The `index()` method automatically supports:

#### Basic Retrieval
```
GET /api/products
```

#### Search
```
GET /api/products?search=laptop
```

#### Filtering
```
GET /api/products?category_id=5
GET /api/products?status=active
GET /api/products?price[min]=100&price[max]=500
GET /api/products?created_at_from=2024-01-01&created_at_to=2024-12-31
```

#### Sorting
```
GET /api/products?sort_by=price&sort_direction=asc
```

#### Pagination
```
GET /api/products?per_page=20&page=2
```

#### Combined
```
GET /api/products?search=laptop&category_id=5&sort_by=price&sort_direction=desc&per_page=15
```

## Advanced Usage

### Relationship Search

Search across relationships by using dot notation:

```php
protected array $searchableFields = [
    'name',
    'description',
    'category.name',  // Search in related category
    'brand.name'      // Search in related brand
];
```

### Eager Loading

Override `getIndexWith()` to eager load relationships:

```php
protected function getIndexWith(): array
{
    return ['category', 'brand', 'images'];
}
```

### Custom Query Modifications

Override `getBaseIndexQuery()` for custom query logic:

```php
protected function getBaseIndexQuery(Request $request): Builder
{
    return $this->model::query()
        ->where('status', 'active')
        ->whereNotNull('published_at');
}
```

### Additional Conditions

Override `applyAdditionalConditions()` for request-specific logic:

```php
protected function applyAdditionalConditions(Builder $query, Request $request): Builder
{
    if ($request->has('featured')) {
        $query->where('featured', true);
    }

    if ($request->user()->isCustomer()) {
        $query->where('visible_to_customers', true);
    }

    return $query;
}
```

### Custom Data Transformation

Transform the response data:

```php
protected function transformIndexData(LengthAwarePaginator|Collection $results, Request $request): array
{
    $items = $results instanceof LengthAwarePaginator ? $results->items() : $results->toArray();

    return array_map(function ($item) {
        return [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => number_format($item['price'], 2),
            'formatted_date' => $item['created_at']->format('M d, Y'),
        ];
    }, $items);
}
```

## Response Format

All responses follow a standardized format:

### Success Response

```json
{
  "success": true,
  "message": "Retrieved 10 of 45 records",
  "data": [
    {
      "id": 1,
      "name": "Product 1",
      "price": 99.99
    }
  ],
  "timestamp": "2024-10-30T10:30:00.000000Z",
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 45,
      "last_page": 3,
      "from": 1,
      "to": 15
    },
    "filters": {
      "search": "laptop",
      "sort_by": "created_at",
      "sort_direction": "desc",
      "applied_filters": {
        "category_id": "5"
      }
    }
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Resource not found",
  "data": null,
  "errors": null,
  "timestamp": "2024-10-30T10:30:00.000000Z"
}
```

## Response Helpers

The base controller provides convenient response methods:

```php
// Success responses
return $this->success('Operation successful', $data);
return $this->created('Resource created', $data);
return $this->noContent('Deleted successfully');

// Error responses
return $this->error('Something went wrong');
return $this->validationError('Invalid input', $errors);
return $this->notFound('Product not found');
return $this->unauthorized('Please login');
return $this->forbidden('Access denied');

// Paginated response
return $this->paginated($paginator, 'Products retrieved');
```

## Global Helper Functions

You can also use global helper functions anywhere in your application:

```php
// Success responses
return success_response('Success', $data);
return created_response('Created', $data);

// Error responses
return error_response('Error occurred');
return validation_error_response('Validation failed', $errors);
return not_found_response('Not found');
return unauthorized_response('Unauthorized');
return forbidden_response('Forbidden');
return server_error_response('Server error');

// Paginated response
return paginated_response($paginator, 'Data retrieved');

// Direct API response builder
return api_response()
    ->success(true)
    ->message('Custom response')
    ->data($data)
    ->meta(['custom' => 'meta'])
    ->statusCode(200)
    ->toJsonResponse();
```

## Filter Examples

### Multiple Values (IN Query)

```
GET /api/products?category_id[]=1&category_id[]=2&category_id[]=3
```

### Range Filters

```
GET /api/products?price[min]=100&price[max]=500
```

### Date Range Filters

```
GET /api/products?created_at_from=2024-01-01&created_at_to=2024-12-31
```

### Boolean Filters

```
GET /api/products?featured=true
GET /api/products?in_stock=false
```

### Null Checks

```
GET /api/products?deleted_at=null        # Only non-deleted
GET /api/products?description=not_null   # Only with description
```

## Configuration

Customize the package behavior in `config/api-controller.php`:

```php
return [
    // API version to include in responses
    'version' => '1.0.0',
    'include_version' => false,

    // Sanitize SQL errors for security
    'sanitize_sql_errors' => true,

    // Pagination defaults
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    // Sorting defaults
    'sorting' => [
        'default_column' => 'created_at',
        'default_direction' => 'desc',
    ],

    // Response format
    'response' => [
        'include_timestamp' => true,
        'timestamp_format' => 'iso8601',
    ],
];
```

You can also use environment variables:

```env
API_VERSION=2.0.0
API_INCLUDE_VERSION=true
API_SANITIZE_SQL_ERRORS=true
API_DEFAULT_PER_PAGE=20
API_MAX_PER_PAGE=100
API_DEFAULT_SORT=created_at
API_DEFAULT_SORT_DIRECTION=desc
```

## Override Properties Per Controller

Each controller can override the default settings:

```php
class ProductController extends ApiController
{
    protected $model = Product::class;
    protected int $maxPerPage = 50;
    protected int $defaultPerPage = 10;
    protected string $defaultSort = 'name';
    protected string $defaultDirection = 'asc';
}
```

## Authorization

⚠️ **Important:** This package does NOT include Laravel's `authorize()` method. You are responsible for implementing your own authorization logic.

### Handling Authorization

If your application uses Laravel policies:

```php
public function store(Request $request): JsonResponse
{
    // Option 1: Use Laravel's authorize (if available in your base controller)
    $this->authorize('create', Product::class);

    // Option 2: Use Gate facade
    if (! Gate::allows('create', Product::class)) {
        return $this->forbidden('You are not authorized to create products');
    }

    // Option 3: Manual check
    if (! auth()->user()->can('create', Product::class)) {
        return $this->forbidden('Access denied');
    }

    // Your logic here...
}
```

### Migration Note

When migrating from a local `ApiController` that includes `authorize()`:
- Comment out all `$this->authorize()` calls, or
- Implement `authorize()` in your own base controller that extends this package's `ApiController`

## Helper Methods

The package includes several helper methods for common scenarios:

### User & Authentication

```php
// Get the authenticated user
$user = $this->getUser();

// Get user's branch ID (for multi-tenancy)
$branchId = $this->getUserBranchId();

// Check if user is super admin
if ($this->isSuperAdmin()) {
    // Allow access to all records
}
```

### Multi-Tenancy & Branch Filtering

Perfect for applications with branch-based access control:

```php
use Illuminate\Database\Eloquent\Builder;

protected function getBaseIndexQuery(Request $request): Builder
{
    $query = parent::getBaseIndexQuery($request)
        ->with(['relationships']);

    // Apply branch filtering automatically
    return $this->applyBranchFilter($query);

    // Or specify a custom column
    return $this->applyBranchFilter($query, 'company_branch_id');
}

// Check if user can access a specific branch
if (!$this->canAccessBranch($branchId)) {
    return $this->forbidden('Access denied to this branch');
}
```

The `applyBranchFilter()` method:
- Filters records by user's branch ID
- Skips filtering for super admins
- Handles null users gracefully
- Defaults to 'branch_id' column (customizable)

### Custom Query with handleIndexRequest()

For custom filtering beyond the base query:

```php
public function activeProducts(Request $request): JsonResponse
{
    $query = Product::where('status', 'active')
        ->where('stock', '>', 0);

    // This applies all standard filtering, searching, sorting, and pagination
    return $this->handleIndexRequest($request, $query);
}

public function userTasks(Request $request): JsonResponse
{
    $query = Task::where('user_id', auth()->id());

    return $this->handleIndexRequest($request, $query);
}
```

## Migrating from Local ApiController

**Note**: As of 2025-10-31, the legacy local `app/Http/Controllers/ApiController.php` has been successfully removed from the Fiber Map v2 project. All 55 controllers have been migrated to use this package.

If you're migrating from your own local `App\Http\Controllers\ApiController`:

### Step-by-Step Migration

**1. Update the import:**
```php
// Old
use App\Http\Controllers\ApiController;

// New
use Masum\ApiController\Controllers\ApiController;
use Illuminate\Database\Eloquent\Builder;
```

**2. Add return type to getBaseIndexQuery():**
```php
// Add Builder return type
protected function getBaseIndexQuery(Request $request): Builder
{
    return parent::getBaseIndexQuery($request)->with(['relations']);
}
```

**3. Handle authorization:**
```php
// Comment out authorize() calls
// $this->authorize('create', Product::class);

// Or implement your own authorization
if (!auth()->user()->can('create-products')) {
    return $this->forbidden('Access denied');
}
```

**4. Remove duplicate helper methods:**

If your local controller had these methods, **remove them** (they're now in the package):
- `getUser()`
- `getUserBranchId()`
- `applyBranchFilter()`
- `canAccessBranch()`
- `hasPermission()`
- `isSuperAdmin()`

**5. Update eager loading pattern:**
```php
// Old pattern (still works)
protected function getIndexWith(): array
{
    return ['category', 'brand'];
}

// New pattern (preferred for more control)
protected function getBaseIndexQuery(Request $request): Builder
{
    return parent::getBaseIndexQuery($request)
        ->with(['category', 'brand', 'tag']);
}
```

**6. Align searchable/filterable fields with model:**
```php
// Check your model's fillable fields
protected $fillable = ['name', 'sku', 'price', 'status'];

// Match in controller
protected array $searchableFields = ['name', 'sku'];
protected array $filterableFields = [
    'name',
    'sku',
    'price',
    'status',
    'created_at',
    'updated_at',
];
```

### Migration Checklist

- [ ] Update namespace imports
- [ ] Add `Builder` return type to `getBaseIndexQuery()`
- [ ] Handle/comment out `authorize()` calls
- [ ] Remove duplicate helper methods
- [ ] Align searchableFields with model
- [ ] Align filterableFields with model
- [ ] Test all CRUD endpoints
- [ ] Test search and filtering
- [ ] Test pagination
- [ ] Verify branch filtering (if applicable)

## Troubleshooting

### Common Issues

**Issue: "Call to undefined method authorize()"**

**Cause:** The package doesn't include Laravel's `authorize()` method.

**Solution:**
```php
// Comment out authorize calls
// $this->authorize('create', Product::class);

// Or implement authorization manually
if (!Gate::allows('create', Product::class)) {
    return $this->forbidden('Access denied');
}
```

---

**Issue: "Attempt to read property 'role_name' on null"**

**Cause:** User object is null when calling helper methods.

**Solution:** The package handles null users defensively in helper methods. Ensure you're using authentication middleware on protected routes:
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);
});
```

---

**Issue: "Call to undefined relationship [tags]"**

**Cause:** Using plural relationship name for morphOne relationships.

**Solution:**
```php
// Wrong (for morphOne relationships)
->with(['tags'])

// Correct (singular for morphOne, plural for morphMany)
->with(['tag'])
```

---

**Issue: Search not working on relationship fields**

**Cause:** Relationship not eager loaded or incorrect dot notation.

**Solution:**
```php
// Ensure relationship is loaded
protected function getBaseIndexQuery(Request $request): Builder
{
    return parent::getBaseIndexQuery($request)
        ->with(['category']); // Load the relationship
}

// Use correct dot notation
protected array $searchableFields = [
    'name',
    'category.name', // Matches the relationship name
];
```

---

**Issue: "Too many SQL queries" (N+1 problem)**

**Cause:** Not eager loading relationships.

**Solution:**
```php
protected function getBaseIndexQuery(Request $request): Builder
{
    return parent::getBaseIndexQuery($request)
        ->with([
            'category',
            'brand',
            'images',
            'reviews' => function ($query) {
                $query->limit(5); // Limit nested results
            }
        ]);
}
```

---

**Issue: Filters not working**

**Cause:** Field not in `filterableFields` array or model doesn't have the field.

**Solution:**
```php
// Check model's fillable or database columns
protected array $filterableFields = [
    'status',           // Must exist in database
    'category_id',      // Must exist in database
    'created_at',       // Always available (timestamp)
    'updated_at',       // Always available (timestamp)
];
```

---

**Issue: Branch filtering not working**

**Cause:** Not calling `applyBranchFilter()` in `getBaseIndexQuery()`.

**Solution:**
```php
protected function getBaseIndexQuery(Request $request): Builder
{
    $query = parent::getBaseIndexQuery($request)
        ->with(['relationships']);

    // Add branch filtering
    return $this->applyBranchFilter($query);
}
```

## Testing

```bash
composer test
```

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x

## Use Cases

This package is perfect for:

- Building RESTful APIs with consistent response formats
- Admin panels with complex filtering and search
- Mobile app backends
- Data tables with server-side processing
- Microservices with standardized responses
- API-first applications

## License

MIT License

## Credits

- Masum
- All Contributors

## Support

For issues, questions, or contributions, please visit the GitHub repository.