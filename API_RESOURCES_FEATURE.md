# Laravel API Resources Support

## Overview

The package now has built-in support for Laravel API Resources, making data transformation seamless and Laravel-native.

---

## How It Works

Simply specify your Resource class in the controller, and it will automatically be used for transforming data in the `index()` method.

---

## Basic Usage

### 1. Create Your Resource

```bash
php artisan make:resource ProductResource
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
                'currency' => 'USD',
            ],
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ],
            'in_stock' => $this->stock > 0,
            'stock_count' => $this->stock,
            'images' => $this->images->map(fn($img) => $img->url),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

### 2. Use in Controller

```php
<?php

namespace App\Http\Controllers\Api;

use Masum\QueryController\Controllers\ResourceController;
use App\Models\Product;
use App\Http\Resources\ProductResource;

class ProductController extends ResourceController
{
    protected $model = Product::class;
    protected ?string $resource = ProductResource::class;  // That's it!
    protected array $searchableFields = ['name', 'description'];
    protected array $filterableFields = ['category_id', 'status'];
}
```

### 3. Automatic Transformation

The `index()` method automatically uses your Resource:

```bash
GET /api/products
```

Response:
```json
{
  "success": true,
  "message": "Retrieved 10 of 45 records",
  "data": [
    {
      "id": 1,
      "name": "Laptop",
      "slug": "laptop",
      "price": {
        "amount": 999.99,
        "formatted": "$999.99",
        "currency": "USD"
      },
      "category": {
        "id": 5,
        "name": "Electronics"
      },
      "in_stock": true,
      "stock_count": 25,
      "images": ["https://..."],
      "created_at": "2024-10-30T10:30:00Z",
      "updated_at": "2024-10-30T10:30:00Z"
    }
  ],
  "meta": {
    "pagination": {...}
  }
}
```

---

## For Other Methods

Use resources explicitly in your custom methods:

```php
public function show(int $id)
{
    $product = Product::with('category', 'images')->findOrFail($id);

    return $this->success(
        'Product found',
        new ProductResource($product)
    );
}

public function store(Request $request)
{
    $product = Product::create($request->validated());

    return $this->created(
        'Product created',
        new ProductResource($product)
    );
}
```

---

## Advanced Resource Features

### Conditional Attributes

```php
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,

            // Only for authenticated users
            'cost' => $this->when($request->user()?->isAdmin(), $this->cost),

            // Only when loaded
            'category' => $this->whenLoaded('category', function () {
                return new CategoryResource($this->category);
            }),

            // Only for specific users
            'internal_notes' => $this->when(
                $request->user()?->can('view-internal-notes'),
                $this->internal_notes
            ),
        ];
    }
}
```

### Nested Resources

```php
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
```

### With Additional Meta

```php
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    public function with($request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }
}
```

---

## Resource Collections

For custom collection behavior, create a Resource Collection:

```bash
php artisan make:resource ProductCollection
```

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'links' => [
                'self' => 'link-value',
            ],
            'meta' => [
                'total_value' => $this->collection->sum('price'),
            ],
        ];
    }
}
```

Then in your controller:

```php
protected function transformIndexData($results, Request $request): array
{
    $items = $results instanceof LengthAwarePaginator
        ? $results->items()
        : $results;

    return (new ProductCollection($items))->resolve();
}
```

---

## Benefits of Using API Resources

### 1. **Consistent Data Structure**
All API endpoints return data in the same format.

### 2. **Reusable Transformations**
Define transformation logic once, use everywhere.

### 3. **Conditional Fields**
Show/hide fields based on permissions or context.

### 4. **Nested Resources**
Handle complex relationships elegantly.

### 5. **Type Safety**
IDE autocomplete works perfectly.

### 6. **Laravel Standard**
Uses Laravel's official way of transforming data.

---

## Comparison

### Without Resource (Manual)

```php
protected function transformIndexData($results, Request $request): array
{
    $items = $results instanceof LengthAwarePaginator
        ? $results->items()
        : $results->toArray();

    return array_map(function ($item) {
        return [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => '$' . number_format($item['price'], 2),
            'category' => $item['category']['name'] ?? null,
        ];
    }, $items);
}
```

### With Resource (Automatic)

```php
// Controller
protected ?string $resource = ProductResource::class;

// Resource class - reusable everywhere
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => '$' . number_format($this->price, 2),
            'category' => $this->category?->name,
        ];
    }
}
```

---

## Real-World Example

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
            'description' => $this->description,

            // Price formatting
            'price' => [
                'amount' => $this->price,
                'formatted' => number_format($this->price, 2),
                'with_currency' => '$' . number_format($this->price, 2),
            ],

            // Stock status
            'availability' => [
                'in_stock' => $this->stock > 0,
                'quantity' => $this->stock,
                'status' => $this->stock > 10 ? 'available' : 'limited',
            ],

            // Relationships
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ],

            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'logo' => $this->brand->logo_url,
                ];
            }),

            // Images
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'url' => $image->url,
                        'thumbnail' => $image->thumbnail_url,
                        'alt' => $image->alt_text,
                    ];
                });
            }),

            // Ratings
            'rating' => [
                'average' => round($this->average_rating, 1),
                'count' => $this->ratings_count,
            ],

            // Admin-only fields
            'cost' => $this->when(
                $request->user()?->isAdmin(),
                $this->cost
            ),

            'profit_margin' => $this->when(
                $request->user()?->isAdmin(),
                $this->price - $this->cost
            ),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

---

## Summary

✅ **Set once in controller** - `protected ?string $resource = ProductResource::class`
✅ **Automatic transformation** - Works for `index()` method
✅ **Explicit usage** - Use `new ProductResource($item)` for other methods
✅ **Laravel standard** - Uses official Laravel API Resources
✅ **Full-featured** - Conditional fields, nested resources, meta data
✅ **Type-safe** - IDE autocomplete and type checking

This makes the package even more powerful and aligned with Laravel best practices! 🚀
