<?php

namespace Masum\ApiController\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Masum\ApiController\ApiControllerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Get package providers
     */
    protected function getPackageProviders($app): array
    {
        return [
            ApiControllerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup API controller config
        $app['config']->set('api-controller.sanitize_sql_errors', true);
        $app['config']->set('api-controller.pagination.default_per_page', 15);
        $app['config']->set('api-controller.pagination.max_per_page', 100);
        $app['config']->set('api-controller.sorting.default_column', 'created_at');
        $app['config']->set('api-controller.sorting.default_direction', 'desc');
    }

    /**
     * Set up the database
     */
    protected function setUpDatabase(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->integer('price')->default(0);
            $table->boolean('featured')->default(false);
            $table->foreignId('category_id')->nullable();
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Create test model instances
     */
    protected function createTestModels(int $count = 1): array
    {
        $models = [];
        for ($i = 1; $i <= $count; $i++) {
            $models[] = TestModel::create([
                'name' => "Test Model {$i}",
                'email' => "test{$i}@example.com",
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
                'price' => $i * 100,
                'featured' => $i % 3 === 0,
            ]);
        }
        return $models;
    }

    /**
     * Create category instances
     */
    protected function createCategories(int $count = 1): array
    {
        $categories = [];
        for ($i = 1; $i <= $count; $i++) {
            $categories[] = Category::create([
                'name' => "Category {$i}",
            ]);
        }
        return $categories;
    }
}

/**
 * Test Model for unit testing
 */
class TestModel extends Model
{
    protected $table = 'test_models';
    protected $fillable = ['name', 'email', 'status', 'price', 'featured', 'category_id'];
    protected $casts = [
        'featured' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

/**
 * Category Model for testing relationships
 */
class Category extends Model
{
    protected $fillable = ['name'];

    public function testModels()
    {
        return $this->hasMany(TestModel::class);
    }
}
