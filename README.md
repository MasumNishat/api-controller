# Laravel API Controller

[![Latest Version on Packagist](https://img.shields.io/packagist/v/masum/laravel-api-controller.svg?style=flat-square)](https://packagist.org/packages/masum/laravel-api-controller)
[![Total Downloads](https://img.shields.io/packagist/dt/masum/laravel-api-controller.svg?style=flat-square)](https://packagist.org/packages/masum/laravel-api-controller)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/masum/laravel-api-controller/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/masum/laravel-api-controller/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Quality Action Status](https://img.shields.io/github/actions/workflow/status/masum/laravel-api-controller/code-quality.yml?branch=main&label=code%20quality&style=flat-square)](https://github.com/masum/laravel-api-controller/actions?query=workflow%3Acode-quality+branch%3Amain)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%206-brightgreen.svg?style=flat-square)](https://phpstan.org/)
[![License](https://img.shields.io/packagist/l/masum/laravel-api-controller.svg?style=flat-square)](https://packagist.org/packages/masum/laravel-api-controller)
[![PHP Version](https://img.shields.io/packagist/php-v/masum/laravel-api-controller.svg?style=flat-square)](https://packagist.org/packages/masum/laravel-api-controller)

A powerful, secure, and feature-rich Laravel package providing a base API controller with dynamic filtering, searching, sorting, pagination, and standardized JSON responses. Build RESTful APIs faster with consistent response formatting, advanced query capabilities, and enterprise-grade security.

## Features

### Core Features
- **Dynamic Filtering**: Filter by any field with support for exact matches, IN queries, range filters, date ranges, boolean values, and null checks
- **Advanced Search**: Full-text search across multiple fields including relationships with SQL injection prevention
- **Flexible Sorting**: Sort by any column with whitelist validation and configurable defaults
- **Smart Pagination**: Automatic pagination with configurable per-page limits and DoS protection
- **Standardized Responses**: Consistent JSON response format across your API with customizable timestamps

### Security Features (NEW)
- **SQL Injection Prevention**: Comprehensive input sanitization and validation
- **Whitelist-Based Filtering**: Only explicitly allowed fields can be filtered/sorted
- **Mass Assignment Protection**: Secure defaults prevent unauthorized data access
- **Error Message Sanitization**: SQL errors sanitized in production (20+ patterns detected)
- **Input Validation**: Request parameter validation with max length limits
- **DoS Protection**: Configurable pagination limits prevent resource exhaustion

### Developer Experience
- **Response Helpers**: Convenient methods for common HTTP responses (success, error, validation, etc.)
- **Query Hooks**: Extensible query building with multiple override points
- **Optional Traits**: Multi-tenancy and permission helpers available as opt-in traits
- **Global Helper Functions**: Use anywhere in your application
- **Comprehensive Testing**: 78 tests covering unit, feature, and security scenarios
- **Type-Safe**: Full type hints and PHPDoc comments throughout
- **Static Analysis**: PHPStan level 6 compliant
- **CI/CD Ready**: GitHub Actions workflows included

### Framework Support
- **Laravel**: 10.x, 11.x, 12.x
- **PHP**: 8.1, 8.2, 8.3
- **Database**: MySQL, PostgreSQL, SQLite, SQL Server (any Laravel-supported database)

## Installation

Install via Composer:

```bash
composer require masum/laravel-api-controller
```

Publish the configuration file (optional):

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
    /**
     * The Eloquent model class to use for queries.
     */
    protected string $model = Product::class;

    /**
     * Fields that can be searched using the ?search parameter.
     */
    protected array $searchableFields = ['name', 'description', 'sku'];

    /**
     * Fields that can be filtered using query parameters.
     */
    protected array $filterableFields = ['category_id', 'status', 'price', 'created_at'];

    /**
     * Default column to sort by.
     */
    protected string $defaultSort = 'created_at';

    /**
     * Default sort direction.
     */
    protected string $defaultDirection = 'desc';

    // The index() method is already implemented in the base class!
    // It automatically handles filtering, searching, sorting, and pagination.
}
```

### Register Routes

```php
use App\Http\Controllers\Api\ProductController;

Route::get('/api/products', [ProductController::class, 'index']);
```

That's it! Your API endpoint now supports all the features below.

## API Usage Examples

### Basic Retrieval

Get all products with default pagination:

```
GET /api/products
```

### Search

Search across all configured searchable fields:

```
GET /api/products?search=laptop
```

### Filtering

#### Exact Match
```
GET /api/products?status=active
GET /api/products?category_id=5
```

#### Multiple Values (IN Query)
```
GET /api/products?category_id[]=1&category_id[]=2&category_id[]=3
```

#### Range Filters
```
GET /api/products?price[min]=100&price[max]=500
```

#### Date Range Filters
```
GET /api/products?created_at_from=2024-01-01&created_at_to=2024-12-31
```

#### Boolean Filters
```
GET /api/products?featured=true
GET /api/products?in_stock=false
```

#### Null Checks
```
GET /api/products?deleted_at=null        # Only non-deleted
GET /api/products?description=not_null   # Only with description
```

### Sorting

```
GET /api/products?sort_by=price&sort_direction=asc
GET /api/products?sort_by=created_at&sort_direction=desc
```

### Pagination

```
GET /api/products?per_page=20&page=2
```

### Combined Query

All features can be combined:

```
GET /api/products?search=laptop&category_id=5&price[min]=500&sort_by=price&sort_direction=desc&per_page=15&page=1
```

## Response Format

All responses follow a standardized, predictable format:

### Success Response

```json
{
  "success": true,
  "message": "Retrieved 10 of 45 records",
  "data": [
    {
      "id": 1,
      "name": "Gaming Laptop",
      "price": 1299.99,
      "status": "active"
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

## Advanced Usage

### Relationship Search

Search across relationships using dot notation:

```php
protected array $searchableFields = [
    'name',
    'description',
    'category.name',  // Search in related category
    'brand.name'      // Search in related brand
];
```

### Eager Loading Relationships

Override `getIndexWith()` to eager load relationships:

```php
protected function getIndexWith(): array
{
    return ['category', 'brand', 'images'];
}
```

Or for more control, override `getBaseIndexQuery()`:

```php
protected function getBaseIndexQuery(Request $request): Builder
{
    return parent::getBaseIndexQuery($request)
        ->with([
            'category',
            'brand',
            'images',
            'reviews' => function ($query) {
                $query->where('approved', true)->limit(5);
            }
        ]);
}
```

### Custom Query Logic

Add custom base query conditions:

```php
protected function getBaseIndexQuery(Request $request): Builder
{
    return parent::getBaseIndexQuery($request)
        ->where('status', 'active')
        ->whereNotNull('published_at');
}
```

### Request-Specific Conditions

Apply conditions based on the request:

```php
protected function applyAdditionalConditions(Builder $query, Request $request): Builder
{
    // Filter by featured flag if provided
    if ($request->has('featured')) {
        $query->where('featured', true);
    }

    // Customer users only see customer-visible products
    if ($request->user()?->isCustomer()) {
        $query->where('visible_to_customers', true);
    }

    return $query;
}
```

### Data Transformation

Transform the response data before sending:

```php
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

protected function transformIndexData(
    LengthAwarePaginator|Collection $results,
    Request $request
): array {
    $items = $results instanceof LengthAwarePaginator
        ? $results->items()
        : $results->toArray();

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

### Using handleIndexRequest()

For custom endpoints with standard filtering:

```php
public function activeProducts(Request $request): JsonResponse
{
    $query = Product::where('status', 'active')
        ->where('stock', '>', 0);

    // Applies all standard filtering, searching, sorting, and pagination
    return $this->handleIndexRequest($request, $query);
}

public function userOrders(Request $request): JsonResponse
{
    $query = Order::where('user_id', auth()->id());

    return $this->handleIndexRequest($request, $query);
}
```

## Response Helpers

The base controller provides convenient response methods:

```php
// Success responses (200)
return $this->success('Operation successful', $data);

// Created response (201)
return $this->created('Resource created', $data);

// No content response (204)
return $this->noContent('Deleted successfully');

// Paginated response (200)
return $this->paginated($paginator, 'Products retrieved');

// Error responses
return $this->error('Something went wrong', null, 500);

// Validation error (422)
return $this->validationError('Invalid input', $validationErrors);

// Not found (404)
return $this->notFound('Product not found');

// Unauthorized (401)
return $this->unauthorized('Please login');

// Forbidden (403)
return $this->forbidden('Access denied');
```

## Global Helper Functions

Use these helper functions anywhere in your application:

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

// Custom API response builder
return api_response()
    ->success(true)
    ->message('Custom response')
    ->data($data)
    ->meta(['custom' => 'meta'])
    ->statusCode(200)
    ->toJsonResponse();
```

## Multi-Tenancy Support (Optional)

The package includes optional traits for common scenarios like multi-tenancy and permission checking.

### Branch-Based Multi-Tenancy

Use the `HasBranchFiltering` trait:

```php
use Masum\ApiController\Controllers\ApiController;
use Masum\ApiController\Traits\HasBranchFiltering;

class ProductController extends ApiController
{
    use HasBranchFiltering;

    protected function getBaseIndexQuery(Request $request): Builder
    {
        $query = parent::getBaseIndexQuery($request)
            ->with(['category', 'brand']);

        // Automatically filters by user's branch_id
        // Super admins see all records
        return $this->applyBranchFilter($query);

        // Or specify a custom column
        // return $this->applyBranchFilter($query, 'company_branch_id');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([...]);

        // Ensure user can only create in their branch
        if (!$this->canAccessBranch($validated['branch_id'])) {
            return $this->forbidden('Access denied to this branch');
        }

        // ... create logic
    }
}
```

The `HasBranchFiltering` trait provides:
- `applyBranchFilter(Builder $query, string $column = 'branch_id')` - Filters records by user's branch
- `canAccessBranch(?int $branchId)` - Checks if user can access a branch
- `getUserBranchId()` - Gets the authenticated user's branch ID
- Automatically skips filtering for super admins
- Handles null users gracefully

### Permission Checking (Optional)

Use the `HasPermissions` trait:

```php
use Masum\ApiController\Traits\HasPermissions;

class ProductController extends ApiController
{
    use HasPermissions;

    public function store(Request $request): JsonResponse
    {
        if (!$this->hasPermission('create', 'products')) {
            return $this->forbidden('You do not have permission to create products');
        }

        // ... create logic
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->hasRole(['admin', 'manager'])) {
            return $this->forbidden('Only admins and managers can delete products');
        }

        // ... delete logic
    }
}
```

The `HasPermissions` trait provides:
- `hasPermission(string $action, string $resource)` - Checks specific permissions
- `isSuperAdmin()` - Checks if user is super admin
- `hasRole(string|array $roles)` - Checks if user has any of the given roles
- `hasAllRoles(array $roles)` - Checks if user has all given roles

**Note:** These traits expect your User model to have a `role_name` property. Customize them as needed for your application's permission system.

## Configuration

Customize the package behavior by publishing and editing `config/api-controller.php`:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    */
    'version' => env('API_VERSION', '1.0.0'),
    'include_version' => env('API_INCLUDE_VERSION', false),

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'sanitize_sql_errors' => env('API_SANITIZE_SQL_ERRORS', true),

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default_per_page' => env('API_DEFAULT_PER_PAGE', 15),
        'max_per_page' => env('API_MAX_PER_PAGE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sorting Settings
    |--------------------------------------------------------------------------
    */
    'sorting' => [
        'default_column' => env('API_DEFAULT_SORT', 'created_at'),
        'default_direction' => env('API_DEFAULT_SORT_DIRECTION', 'desc'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Format Settings
    |--------------------------------------------------------------------------
    */
    'response' => [
        'include_timestamp' => true,
        'timestamp_format' => 'iso8601', // Options: iso8601, unix, custom
        'custom_timestamp_format' => 'Y-m-d H:i:s', // Used when format is 'custom'
    ],
];
```

### Environment Variables

Configure via `.env` file:

```env
API_VERSION=2.0.0
API_INCLUDE_VERSION=true
API_SANITIZE_SQL_ERRORS=true
API_DEFAULT_PER_PAGE=20
API_MAX_PER_PAGE=100
API_DEFAULT_SORT=created_at
API_DEFAULT_SORT_DIRECTION=desc
```

### Per-Controller Overrides

Each controller can override the default settings:

```php
class ProductController extends ApiController
{
    protected string $model = Product::class;
    protected int $maxPerPage = 50;
    protected int $defaultPerPage = 10;
    protected string $defaultSort = 'name';
    protected string $defaultDirection = 'asc';
}
```

## Security Features

This package includes enterprise-grade security features:

### SQL Injection Prevention

- **Search term sanitization**: Escapes LIKE wildcards (%, _, \\) to prevent SQL injection
- **Sort column validation**: Whitelist-based validation ensures only allowed columns can be sorted
- **Parameter binding**: All queries use Laravel's parameter binding

### Input Validation

- **Max search length**: Search terms limited to 255 characters
- **Filterable field whitelist**: Only explicitly allowed fields can be filtered
- **Sort column whitelist**: Only allowed columns (filterable + searchable + timestamps) can be sorted

### Error Handling

- **SQL error sanitization**: In production, SQL errors are sanitized to prevent information disclosure
- **Comprehensive logging**: All errors logged with full context for debugging
- **User-friendly messages**: Generic error messages shown to users in production

### DoS Protection

- **Max pagination limit**: Prevents requesting unlimited records
- **Required authorization for ?all=true**: Fetching all records requires authentication

### Mass Assignment Protection

- **Secure defaults**: Empty filterableFields array denies all filtering (fail-secure)
- **Explicit whitelisting**: Only listed fields can be filtered

## Testing

The package includes comprehensive test coverage:

```bash
# Run tests
composer test

# Run tests with coverage report
composer test-coverage

# Run static analysis
composer analyse

# Check code style
composer format-check

# Fix code style
composer format

# Run all quality checks
composer all-checks
```

### Test Coverage

- **78 tests** covering:
  - Unit tests for ApiController methods
  - Response class tests
  - Helper function tests
  - Feature tests for complete workflows
  - Security tests (SQL injection, DoS, mass assignment)

### Writing Tests for Your Controllers

```php
use Masum\ApiController\Tests\TestCase;

class ProductControllerTest extends TestCase
{
    /** @test */
    public function it_filters_products_by_status()
    {
        Product::factory()->create(['status' => 'active']);
        Product::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/products?status=active');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.status', 'active');
    }
}
```

## Code Quality

This package maintains high code quality standards:

- **PSR-12 Coding Standards**: Enforced via PHP-CS-Fixer
- **PHPStan Level 6**: Static analysis for type safety
- **Full Type Hints**: All methods and properties strictly typed
- **Comprehensive PHPDoc**: All methods documented with examples
- **CI/CD**: Automated testing via GitHub Actions

## Troubleshooting

### Search not working on relationship fields

**Problem**: Searching on `category.name` doesn't work.

**Solution**: Ensure the relationship is eager loaded:

```php
protected function getBaseIndexQuery(Request $request): Builder
{
    return parent::getBaseIndexQuery($request)
        ->with(['category']); // Must load the relationship
}

protected array $searchableFields = [
    'name',
    'category.name', // Matches the relationship name
];
```

### Filters not working

**Problem**: Filtering by a field doesn't work.

**Solution**: Add the field to `filterableFields`:

```php
protected array $filterableFields = [
    'status',        // Must exist in database
    'category_id',   // Must exist in database
    'created_at',
    'updated_at',
];
```

### Too many SQL queries (N+1 problem)

**Problem**: API response is slow with many queries.

**Solution**: Eager load relationships:

```php
protected function getBaseIndexQuery(Request $request): Builder
{
    return parent::getBaseIndexQuery($request)
        ->with(['category', 'brand', 'images']);
}
```

### Branch filtering not working

**Problem**: Users see records from other branches.

**Solution**: Apply branch filtering in your query:

```php
use Masum\ApiController\Traits\HasBranchFiltering;

class ProductController extends ApiController
{
    use HasBranchFiltering;

    protected function getBaseIndexQuery(Request $request): Builder
    {
        $query = parent::getBaseIndexQuery($request);
        return $this->applyBranchFilter($query);
    }
}
```

## Requirements

- **PHP**: 8.1, 8.2, or 8.3
- **Laravel**: 10.x, 11.x, or 12.x
- **Database**: Any Laravel-supported database (MySQL, PostgreSQL, SQLite, SQL Server)

## Use Cases

This package is perfect for:

- RESTful APIs with consistent response formats
- Admin panels with complex filtering and search
- Mobile app backends
- Data tables with server-side processing
- Microservices with standardized responses
- Multi-tenant applications
- API-first applications
- Enterprise applications requiring security and auditability

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on:

- Development setup
- Running tests
- Code quality standards
- Commit message conventions
- Pull request process

### Development Quick Start

```bash
# Clone the repository
git clone https://github.com/masum/laravel-api-controller.git
cd laravel-api-controller

# Install dependencies
composer install

# Run tests
composer test

# Run all quality checks
composer all-checks
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for information on recent changes.

## Security Vulnerabilities

If you discover a security vulnerability, please email the maintainers at contact@example.com. All security vulnerabilities will be promptly addressed.

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

## Credits

- **Author**: Masum Nishat
- **Contributors**: All contributors who have helped improve this package

## Support

- **Issues**: [GitHub Issues](https://github.com/masum/laravel-api-controller/issues)
- **Discussions**: [GitHub Discussions](https://github.com/masum/laravel-api-controller/discussions)
- **Documentation**: [README.md](README.md)

---

**Built with ❤️ for the Laravel community**
