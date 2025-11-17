# Usage Examples

This document provides comprehensive examples of using the Laravel API Controller package.

## Table of Contents

1. [Basic Controller](#basic-controller)
2. [Advanced Filtering](#advanced-filtering)
3. [Relationship Searches](#relationship-searches)
4. [Custom Query Logic](#custom-query-logic)
5. [Response Customization](#response-customization)
6. [CRUD Operations](#crud-operations)
7. [Branch Filtering & Multi-Tenancy](#branch-filtering--multi-tenancy)
8. [Model Mismatch Handling](#model-mismatch-handling)
9. [Using handleIndexRequest()](#using-handleindexrequest)
10. [Real-World Examples](#real-world-examples)

## Basic Controller

### Simple Product Controller

```php
<?php

namespace App\Http\Controllers\Api;

use Masum\ApiController\Controllers\ApiController;
use App\Models\Product;

class ProductController extends ApiController
{
    protected $model = Product::class;

    protected array $searchableFields = ['name', 'description', 'sku'];

    protected array $filterableFields = ['category_id', 'brand_id', 'status', 'price', 'created_at'];

    protected string $defaultSort = 'name';

    protected string $defaultDirection = 'asc';

    protected int $defaultPerPage = 20;

    protected int $maxPerPage = 50;
}
```

**Usage:**
```bash
GET /api/products
GET /api/products?search=laptop
GET /api/products?category_id=5&status=active
GET /api/products?sort_by=price&sort_direction=desc
GET /api/products?per_page=10&page=2
```

## Advanced Filtering

### Multiple Filter Types

```php
class OrderController extends ApiController
{
    protected $model = Order::class;

    protected array $filterableFields = [
        'status',
        'customer_id',
        'total',
        'created_at',
        'payment_method'
    ];
}
```

**Filter Examples:**

```bash
# Exact match
GET /api/orders?status=completed

# Array (IN query)
GET /api/orders?status[]=pending&status[]=processing&status[]=completed

# Range filter
GET /api/orders?total[min]=100&total[max]=1000

# Date range
GET /api/orders?created_at_from=2024-01-01&created_at_to=2024-12-31

# Boolean
GET /api/orders?is_paid=true

# Null check
GET /api/orders?cancelled_at=null
GET /api/orders?notes=not_null

# Combined
GET /api/orders?status=completed&total[min]=100&created_at_from=2024-01-01
```

## Relationship Searches

### Searching Related Models

```php
class ProductController extends ApiController
{
    protected $model = Product::class;

    protected array $searchableFields = [
        'name',
        'sku',
        'description',
        'category.name',      // Search in category
        'brand.name',         // Search in brand
        'supplier.company'    // Search in supplier
    ];

    protected function getIndexWith(): array
    {
        return ['category', 'brand', 'supplier'];
    }
}
```

**Usage:**
```bash
# Searches across product name, sku, description AND related category, brand, supplier
GET /api/products?search=electronics
```

## Custom Query Logic

### User-Based Filtering

```php
class TaskController extends ApiController
{
    protected $model = Task::class;

    protected array $filterableFields = ['status', 'priority', 'due_date'];

    protected function getBaseIndexQuery(Request $request): Builder
    {
        $user = $request->user();

        // Admins see all tasks, users see only their tasks
        return $user->isAdmin()
            ? $this->model::query()
            : $this->model::query()->where('user_id', $user->id);
    }

    protected function applyAdditionalConditions(Builder $query, Request $request): Builder
    {
        // Filter by team if specified
        if ($request->has('team_id')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('team_id', $request->team_id);
            });
        }

        // Show only overdue tasks if requested
        if ($request->boolean('overdue')) {
            $query->where('due_date', '<', now())
                  ->where('status', '!=', 'completed');
        }

        return $query;
    }
}
```

### Soft Deletes with Optional Trashed

```php
class PostController extends ApiController
{
    protected $model = Post::class;

    protected function getBaseIndexQuery(Request $request): Builder
    {
        $query = $this->model::query();

        // Include trashed if requested
        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        // Only trashed if requested
        if ($request->boolean('only_trashed')) {
            $query->onlyTrashed();
        }

        return $query;
    }
}
```

## Response Customization

### Transform Response Data

```php
class ProductController extends ApiController
{
    protected function transformIndexData(LengthAwarePaginator|Collection $results, Request $request): array
    {
        $items = $results instanceof LengthAwarePaginator
            ? $results->items()
            : $results->toArray();

        return array_map(function ($product) {
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => [
                    'amount' => $product['price'],
                    'formatted' => '$' . number_format($product['price'], 2),
                    'currency' => 'USD'
                ],
                'stock' => [
                    'quantity' => $product['stock_quantity'],
                    'status' => $product['stock_quantity'] > 0 ? 'in_stock' : 'out_of_stock'
                ],
                'created_at' => $product['created_at']->format('M d, Y'),
            ];
        }, $items);
    }

    protected function getIndexMessage(LengthAwarePaginator|Collection $results): string
    {
        $count = $results->count();

        if ($count === 0) {
            return 'No products found matching your criteria';
        }

        if ($results instanceof LengthAwarePaginator) {
            return "Showing {$results->count()} products out of {$results->total()} total";
        }

        return "Found {$count} products";
    }
}
```

## CRUD Operations

### Complete Resource Controller

```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends ApiController
{
    protected $model = Product::class;

    protected array $searchableFields = ['name', 'sku', 'description'];
    protected array $filterableFields = ['category_id', 'status', 'price'];

    /**
     * Display a listing of products
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleIndexRequest($request);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
        ]);

        try {
            $product = Product::create($validated);

            return $this->created(
                'Product created successfully',
                $product
            );
        } catch (\Exception $e) {
            return $this->error(
                'Failed to create product: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
        return $this->success(
            'Product retrieved successfully',
            $product->load('category', 'images')
        );
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|unique:products,sku,' . $product->id,
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|exists:categories,id',
        ]);

        try {
            $product->update($validated);

            return $this->success(
                'Product updated successfully',
                $product->fresh()
            );
        } catch (\Exception $e) {
            return $this->error(
                'Failed to update product: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        try {
            $product->delete();

            return $this->success(
                'Product deleted successfully',
                null,
                null,
                204
            );
        } catch (\Exception $e) {
            return $this->error(
                'Failed to delete product: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}
```

## Branch Filtering & Multi-Tenancy

### Basic Branch Filtering

For applications with multi-tenant or branch-based access control:

```php
use Illuminate\Database\Eloquent\Builder;

class EquipmentController extends ApiController
{
    protected $model = Equipment::class;

    protected array $searchableFields = ['name', 'serial_no', 'description'];
    protected array $filterableFields = [
        'status',
        'branch_id',
        'location_id',
        'created_at',
    ];

    protected function getBaseIndexQuery(Request $request): Builder
    {
        $query = parent::getBaseIndexQuery($request)
            ->with(['location', 'deviceModel', 'branch']);

        // Apply branch filtering automatically
        // Users see only their branch's equipment, super admins see all
        return $this->applyBranchFilter($query);
    }
}
```

**Usage:**
```bash
# Regular user (branch_id=5) sees only branch 5's equipment
GET /api/equipment

# Super admin sees all equipment
GET /api/equipment
```

### Custom Branch Column

If your table uses a different column name:

```php
protected function getBaseIndexQuery(Request $request): Builder
{
    $query = parent::getBaseIndexQuery($request)
        ->with(['relationships']);

    // Use custom column name
    return $this->applyBranchFilter($query, 'company_branch_id');
}
```

### Branch Access Validation

Check if user can access a specific branch:

```php
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'branch_id' => 'required|exists:branches,id',
        // ...
    ]);

    // Check branch access before creating
    if (!$this->canAccessBranch($validated['branch_id'])) {
        return $this->forbidden('Access denied to this branch');
    }

    $equipment = Equipment::create($validated);

    return $this->created('Equipment created successfully', $equipment);
}

public function update(Request $request, Equipment $equipment): JsonResponse
{
    $validated = $request->validate([
        'branch_id' => 'sometimes|exists:branches,id',
        // ...
    ]);

    // Check if branch is being changed
    if (isset($validated['branch_id']) && !$this->canAccessBranch($validated['branch_id'])) {
        return $this->forbidden('Access denied to this branch');
    }

    $equipment->update($validated);

    return $this->success('Equipment updated successfully', $equipment);
}
```

### Helper Methods Usage

```php
protected function getBaseIndexQuery(Request $request): Builder
{
    // Get user info
    $user = $this->getUser();
    $branchId = $this->getUserBranchId();

    $query = parent::getBaseIndexQuery($request);

    // Super admins bypass all filters
    if ($this->isSuperAdmin()) {
        return $query;
    }

    // Regular users see only their branch
    return $query->where('branch_id', $branchId);
}
```

## Model Mismatch Handling

When working with legacy code or during migrations, you may encounter controllers that expect more fields than the model actually has:

### Identifying Model Mismatches

```php
// Model has only these fillable fields
class LegacyModel extends Model
{
    protected $fillable = ['name', 'type', 'status'];
}

// But controller historically expected many more fields
class LegacyController extends ApiController
{
    protected $model = LegacyModel::class;

    // ❌ Wrong: These fields don't exist in model
    protected array $searchableFields = ['name', 'description', 'code', 'notes'];
    protected array $filterableFields = ['type', 'status', 'category', 'department', 'assigned_to'];
}
```

### Aligning with Model Reality

```php
class LegacyController extends ApiController
{
    protected $model = LegacyModel::class;

    // ✅ Correct: Only fields that actually exist in the model
    protected array $searchableFields = ['name'];  // Only searchable existing fields

    protected array $filterableFields = [
        'type',        // Exists in model
        'status',      // Exists in model
        'created_at',  // Always available (timestamps)
        'updated_at',  // Always available (timestamps)
    ];

    // Document the mismatch for future reference
    // TODO: Model expects additional fields in validation (description, code, notes)
    //       but they don't exist in $fillable array. Consider adding to migration.
}
```

### Validation vs Model Mismatch

Sometimes validation rules reference fields that don't exist in `$fillable`:

```php
public function store(Request $request): JsonResponse
{
    // Validation expects more fields than model has
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'required|string',  // ❌ Not in $fillable
        'code' => 'required|string|unique:table',  // ❌ Not in $fillable
        'type' => 'required|in:type1,type2',  // ✅ In $fillable
    ]);

    // This will silently ignore 'description' and 'code'
    // because they're not in the model's $fillable array
    $model = LegacyModel::create($validated);

    return $this->created('Created successfully', $model);
}
```

**Solution:**

```php
public function store(Request $request): JsonResponse
{
    // Align validation with actual model fields
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'type' => 'required|in:type1,type2',
        'status' => 'nullable|string',
    ]);

    $model = LegacyModel::create($validated);

    return $this->created('Created successfully', $model);
}
```

### Real-World Migration Example

```php
// During migration from local ApiController to package
class PhoneNumberController extends ApiController
{
    protected $model = PhoneNumber::class;

    // Model has: contact_info_id, phone_type, country_code, number
    // Controller historically used: phone_number (not 'number')

    // ❌ Before migration
    protected array $searchableFields = ['phone_number', 'phone_type'];

    // ✅ After migration (aligned with model)
    protected array $searchableFields = ['number', 'phone_type'];  // Changed to match model

    protected array $filterableFields = [
        'contact_info_id',
        'phone_type',
        'country_code',
        'created_at',
        'updated_at',
    ];

    // Note: Field name mismatch documented
    // Model uses 'number', not 'phone_number'
}
```

## Using handleIndexRequest()

The `handleIndexRequest()` method applies filtering, searching, sorting, and pagination to any query:

### Custom Filtered Endpoints

```php
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class ProductController extends ApiController
{
    protected $model = Product::class;

    protected array $searchableFields = ['name', 'sku'];
    protected array $filterableFields = ['category_id', 'price', 'status'];

    /**
     * Get active products only
     */
    public function active(Request $request): JsonResponse
    {
        $query = Product::where('status', 'active')
            ->where('stock', '>', 0);

        // Apply standard filtering, searching, sorting, pagination
        return $this->handleIndexRequest($request, $query);
    }

    /**
     * Get products on sale
     */
    public function onSale(Request $request): JsonResponse
    {
        $query = Product::whereNotNull('sale_price')
            ->where('sale_price', '<', 'regular_price');

        return $this->handleIndexRequest($request, $query);
    }

    /**
     * Get user's own tasks
     */
    public function myTasks(Request $request): JsonResponse
    {
        $query = Task::where('user_id', auth()->id());

        // Still supports ?search=, ?status=, ?sort_by=, etc.
        return $this->handleIndexRequest($request, $query);
    }
}
```

**Usage:**
```bash
# These still support all standard features
GET /api/products/active?search=laptop&price[min]=100&sort_by=price
GET /api/products/on-sale?category_id=5&per_page=20
GET /api/tasks/mine?status=pending&sort_by=due_date&sort_direction=asc
```

### Complex Custom Queries

```php
public function byCategory(Request $request, string $categorySlug): JsonResponse
{
    $query = Product::whereHas('category', function ($q) use ($categorySlug) {
        $q->where('slug', $categorySlug);
    })
    ->where('status', 'published')
    ->with(['category', 'brand', 'images']);

    // Applies search, filters, sort, pagination from request
    return $this->handleIndexRequest($request, $query);
}

public function featured(Request $request): JsonResponse
{
    $query = Product::where('featured', true)
        ->where('status', 'active')
        ->orderBy('featured_priority', 'desc');

    return $this->handleIndexRequest($request, $query);
}

public function recentOrders(Request $request): JsonResponse
{
    $query = Order::where('user_id', auth()->id())
        ->where('created_at', '>=', now()->subDays(30))
        ->with(['items.product', 'customer']);

    return $this->handleIndexRequest($request, $query);
}
```

### With Additional Processing

```php
public function popularProducts(Request $request): JsonResponse
{
    // Start with a custom base query
    $query = Product::where('status', 'active')
        ->where('view_count', '>', 1000)
        ->orWhere('sales_count', '>', 100);

    // Let the package handle the rest
    return $this->handleIndexRequest($request, $query);
}
```

**Routes:**
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);

    // Custom filtered endpoints
    Route::get('products/active', [ProductController::class, 'active']);
    Route::get('products/on-sale', [ProductController::class, 'onSale']);
    Route::get('products/category/{slug}', [ProductController::class, 'byCategory']);
    Route::get('products/featured', [ProductController::class, 'featured']);
});
```

## Real-World Examples

### E-commerce Product Catalog

```php
class CatalogController extends ApiController
{
    protected $model = Product::class;

    protected array $searchableFields = [
        'name',
        'description',
        'sku',
        'brand.name',
        'category.name'
    ];

    protected array $filterableFields = [
        'category_id',
        'brand_id',
        'price',
        'rating',
        'in_stock',
        'created_at'
    ];

    protected function getIndexWith(): array
    {
        return ['category', 'brand', 'images', 'reviews'];
    }

    protected function getBaseIndexQuery(Request $request): Builder
    {
        return $this->model::query()
            ->where('status', 'published')
            ->where('visible', true);
    }

    protected function applyAdditionalConditions(Builder $query, Request $request): Builder
    {
        // Filter by tags
        if ($request->has('tags')) {
            $tags = explode(',', $request->tags);
            $query->whereHas('tags', function($q) use ($tags) {
                $q->whereIn('name', $tags);
            });
        }

        // Filter by rating
        if ($request->has('min_rating')) {
            $query->where('average_rating', '>=', $request->min_rating);
        }

        // On sale items
        if ($request->boolean('on_sale')) {
            $query->whereNotNull('sale_price')
                  ->where('sale_price', '<', 'regular_price');
        }

        // New arrivals (last 30 days)
        if ($request->boolean('new_arrivals')) {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        return $query;
    }

    protected function transformIndexData(LengthAwarePaginator|Collection $results, Request $request): array
    {
        $items = $results instanceof LengthAwarePaginator ? $results->items() : $results->toArray();

        return array_map(function ($product) {
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'price' => [
                    'regular' => $product['regular_price'],
                    'sale' => $product['sale_price'],
                    'formatted' => $product['sale_price']
                        ? '$' . number_format($product['sale_price'], 2)
                        : '$' . number_format($product['regular_price'], 2),
                    'discount_percentage' => $product['sale_price']
                        ? round((($product['regular_price'] - $product['sale_price']) / $product['regular_price']) * 100)
                        : 0
                ],
                'image' => $product['images'][0]['url'] ?? null,
                'rating' => [
                    'average' => $product['average_rating'],
                    'count' => $product['reviews_count']
                ],
                'in_stock' => $product['in_stock'],
                'category' => $product['category']['name'],
                'brand' => $product['brand']['name'],
            ];
        }, $items);
    }
}
```

### Blog/Content Management

```php
class BlogPostController extends ApiController
{
    protected $model = Post::class;

    protected array $searchableFields = [
        'title',
        'content',
        'excerpt',
        'author.name',
        'tags.name'
    ];

    protected array $filterableFields = [
        'status',
        'author_id',
        'category_id',
        'published_at',
        'featured'
    ];

    protected function getBaseIndexQuery(Request $request): Builder
    {
        $query = $this->model::query();

        // Public users see only published posts
        if (!$request->user() || !$request->user()->isAdmin()) {
            $query->where('status', 'published')
                  ->where('published_at', '<=', now());
        }

        return $query;
    }

    protected function getIndexWith(): array
    {
        return ['author', 'category', 'tags', 'featuredImage'];
    }

    protected function applyAdditionalConditions(Builder $query, Request $request): Builder
    {
        // Filter by tag
        if ($request->has('tag')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        // Filter by year/month
        if ($request->has('year')) {
            $query->whereYear('published_at', $request->year);
        }

        if ($request->has('month')) {
            $query->whereMonth('published_at', $request->month);
        }

        return $query;
    }
}
```

### User Management Dashboard

```php
class UserManagementController extends ApiController
{
    protected $model = User::class;

    protected array $searchableFields = [
        'name',
        'email',
        'phone',
        'company.name'
    ];

    protected array $filterableFields = [
        'status',
        'role',
        'created_at',
        'last_login_at',
        'email_verified_at'
    ];

    protected function getIndexWith(): array
    {
        return ['roles', 'permissions', 'profile'];
    }

    protected function applyAdditionalConditions(Builder $query, Request $request): Builder
    {
        // Filter by verification status
        if ($request->has('verified')) {
            $verified = $request->boolean('verified');
            $verified
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at');
        }

        // Filter by activity
        if ($request->has('active_since')) {
            $query->where('last_login_at', '>=', $request->active_since);
        }

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        return $query;
    }
}
```

## Helper Function Examples

### Using Helpers Anywhere

```php
// In a service class
class PaymentService
{
    public function processPayment($order, $paymentMethod)
    {
        try {
            // Process payment logic...

            return success_response('Payment processed successfully', [
                'transaction_id' => $transaction->id,
                'amount' => $order->total,
                'status' => 'completed'
            ]);

        } catch (PaymentException $e) {
            return error_response('Payment failed: ' . $e->getMessage(), null, 402);
        }
    }
}

// In a job
class ProcessReportJob
{
    public function handle()
    {
        $report = $this->generateReport();

        // Send notification with response format
        return success_response('Report generated', [
            'file_url' => $report->url,
            'generated_at' => now()
        ]);
    }
}
```