# Installation Guide

## For Local Development

To use this package in your local Laravel project:

### 1. Add the package repository to your main project's composer.json

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./php-packages/api-controller",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "masum/laravel-api-controller": "@dev"
    }
}
```

### 2. Install the package

```bash
composer update masum/laravel-api-controller
```

### 3. (Optional) Publish the config file

```bash
php artisan vendor:publish --tag=api-controller-config
```

## Quick Example

### 1. Create a Controller

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
}
```

### 2. Register Routes

```php
Route::apiResource('products', ProductController::class);
```

### 3. Test the API

```bash
# Get all products
curl http://localhost/api/products

# Search products
curl http://localhost/api/products?search=laptop

# Filter by category
curl http://localhost/api/products?category_id=5

# Sort and paginate
curl http://localhost/api/products?sort_by=price&sort_direction=asc&per_page=20&page=1

# Complex query
curl "http://localhost/api/products?search=laptop&category_id=5&price[min]=100&price[max]=500&per_page=15"
```

## Response Format

All API responses follow this structure:

```json
{
    "success": true,
    "message": "Retrieved 10 of 45 records",
    "data": [
        ...
    ],
    "timestamp": "2024-10-30T10:30:00.000000Z",
    "meta": {
        "pagination": {
            ...
        },
        "filters": {
            ...
        }
    }
}
```

## Using Response Helpers

In your controllers or anywhere in the application:

```php
// Success
return success_response('Product created', $product);

// Error
return error_response('Product not found', null, 404);

// Validation error
return validation_error_response('Invalid data', $validator->errors()->toArray());

// Created
return created_response('Product created', $product);

// Custom response
return api_response()
    ->success(true)
    ->message('Custom message')
    ->data($data)
    ->statusCode(200)
    ->toJsonResponse();
```

## Configuration

After publishing the config file, you can customize:

```php
// config/api-controller.php

return [
    'version' => '1.0.0',
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],
    'sorting' => [
        'default_column' => 'created_at',
        'default_direction' => 'desc',
    ],
];
```

Or use environment variables:

```env
API_VERSION=1.0.0
API_DEFAULT_PER_PAGE=20
API_MAX_PER_PAGE=100
```

## For Publishing to Packagist

If you want to publish this package to Packagist:

1. Create a GitHub repository for the package
2. Push the package code to GitHub
3. Register on https://packagist.org
4. Submit your package URL
5. Install normally via: `composer require masum/laravel-api-controller`

## Advanced Examples

See the [README.md](README.md) for advanced usage examples including:

- Relationship searches
- Eager loading
- Custom query modifications
- Data transformation
- And more!
