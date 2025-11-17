<?php

namespace Masum\ApiController\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Masum\ApiController\Controllers\ApiController;
use Masum\ApiController\Tests\Category;
use Masum\ApiController\Tests\TestCase;
use Masum\ApiController\Tests\TestModel;

class ApiControllerTest extends TestCase
{
    protected TestApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestApiController();
    }

    /** @test */
    public function it_returns_paginated_results_by_default()
    {
        $this->createTestModels(25);

        $request = Request::create('/test', 'GET');
        $response = $this->controller->index($request);

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertCount(15, $data['data']); // Default per_page is 15
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertEquals(25, $data['meta']['pagination']['total']);
    }

    /** @test */
    public function it_prevents_fetching_all_records_without_authorization()
    {
        $this->createTestModels(30);

        $request = Request::create('/test?all=true', 'GET', ['all' => true]);
        $response = $this->controller->index($request);

        $data = $response->getData(true);

        // Should still paginate since canRequestAllRecords returns false
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertCount(15, $data['data']);
    }

    /** @test */
    public function it_validates_per_page_parameter()
    {
        $this->createTestModels(50);

        // Test max per_page limit
        $request = Request::create('/test?per_page=200', 'GET', ['per_page' => 200]);
        $response = $this->controller->index($request);
        $data = $response->getData(true);

        $this->assertEquals(100, $data['meta']['pagination']['per_page']); // Max is 100

        // Test valid per_page
        $request = Request::create('/test?per_page=20', 'GET', ['per_page' => 20]);
        $response = $this->controller->index($request);
        $data = $response->getData(true);

        $this->assertEquals(20, $data['meta']['pagination']['per_page']);
    }

    /** @test */
    public function it_validates_request_parameters()
    {
        $request = Request::create('/test?per_page=invalid', 'GET', ['per_page' => 'invalid']);
        $response = $this->controller->index($request);

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals(422, $response->status());
        $this->assertEquals('Validation failed', $data['message']);
    }

    /** @test */
    public function it_sanitizes_search_terms()
    {
        $this->createTestModels(5);

        // Test with special LIKE characters
        $request = Request::create('/test?search=%test_', 'GET', ['search' => '%test_']);
        $response = $this->controller->index($request);

        $data = $response->getData(true);

        // Should escape the wildcards
        $this->assertTrue($data['success']);
    }

    /** @test */
    public function it_searches_across_multiple_fields()
    {
        $models = $this->createTestModels(5);

        $request = Request::create('/test?search=Model 3', 'GET', ['search' => 'Model 3']);
        $response = $this->controller->index($request);

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['meta']['pagination']['total']);
        $this->assertStringContainsString('Model 3', $data['data'][0]['name']);
    }

    /** @test */
    public function it_searches_in_relationships()
    {
        $categories = $this->createCategories(3);
        $model1 = TestModel::create([
            'name' => 'Product 1',
            'category_id' => $categories[0]->id,
        ]);

        $request = Request::create('/test?search=Category 1', 'GET', ['search' => 'Category 1']);
        $response = $this->controller->index($request);

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['meta']['pagination']['total']);
    }

    /** @test */
    public function it_filters_by_exact_match()
    {
        $this->createTestModels(10);

        $request = Request::create('/test?status=active', 'GET', ['status' => 'active']);
        $response = $this->controller->index($request);

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals(5, $data['meta']['pagination']['total']); // Half are active
    }

    /** @test */
    public function it_filters_with_boolean_values()
    {
        $this->createTestModels(9);

        // Test with string 'true'
        $request = Request::create('/test?featured=true', 'GET', ['featured' => 'true']);
        $response = $this->controller->index($request);
        $data = $response->getData(true);
        $this->assertEquals(3, $data['meta']['pagination']['total']);

        // Test with integer 1
        $request = Request::create('/test?featured=1', 'GET', ['featured' => 1]);
        $response = $this->controller->index($request);
        $data = $response->getData(true);
        $this->assertEquals(3, $data['meta']['pagination']['total']);

        // Test with string '0'
        $request = Request::create('/test?featured=0', 'GET', ['featured' => '0']);
        $response = $this->controller->index($request);
        $data = $response->getData(true);
        $this->assertEquals(6, $data['meta']['pagination']['total']);
    }

    /** @test */
    public function it_filters_with_range_values()
    {
        $this->createTestModels(10);

        // Test with both min and max
        $request = Request::create('/test', 'GET', ['price' => ['min' => 200, 'max' => 500]]);
        $response = $this->controller->index($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals(4, $data['meta']['pagination']['total']); // prices 200, 300, 400, 500

        // Test with only min
        $request = Request::create('/test', 'GET', ['price' => ['min' => 600]]);
        $response = $this->controller->index($request);
        $data = $response->getData(true);
        $this->assertEquals(5, $data['meta']['pagination']['total']); // prices 600-1000

        // Test with only max
        $request = Request::create('/test', 'GET', ['price' => ['max' => 300]]);
        $response = $this->controller->index($request);
        $data = $response->getData(true);
        $this->assertEquals(3, $data['meta']['pagination']['total']); // prices 100, 200, 300
    }

    /** @test */
    public function it_filters_with_null_values()
    {
        TestModel::create(['name' => 'Test 1', 'email' => 'test@example.com']);
        TestModel::create(['name' => 'Test 2', 'email' => null]);

        $request = Request::create('/test?email=null', 'GET', ['email' => 'null']);
        $response = $this->controller->index($request);
        $data = $response->getData(true);

        $this->assertEquals(1, $data['meta']['pagination']['total']);
    }

    /** @test */
    public function it_only_allows_whitelisted_filters()
    {
        $this->createTestModels(5);

        // Try to filter by a non-filterable field
        // The controller's filterableFields doesn't include 'email'
        $request = Request::create('/test?email=test1@example.com', 'GET', ['email' => 'test1@example.com']);
        $response = $this->controller->index($request);
        $data = $response->getData(true);

        // Should return all records since email is not in filterableFields
        $this->assertEquals(5, $data['meta']['pagination']['total']);
    }

    /** @test */
    public function it_validates_sort_column()
    {
        Log::shouldReceive('warning')->once();

        $this->createTestModels(5);

        // Try to sort by invalid column (SQL injection attempt)
        $request = Request::create('/test?sort_by=id;DROP TABLE users--', 'GET', [
            'sort_by' => 'id;DROP TABLE users--'
        ]);

        $response = $this->controller->index($request);
        $data = $response->getData(true);

        // Should fall back to default sort and still work
        $this->assertTrue($data['success']);
        $this->assertEquals('created_at', $data['meta']['filters']['sort_by']);
    }

    /** @test */
    public function it_sorts_by_valid_columns()
    {
        $this->createTestModels(5);

        $request = Request::create('/test?sort_by=name&sort_direction=asc', 'GET', [
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]);

        $response = $this->controller->index($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals('name', $data['meta']['filters']['sort_by']);
        $this->assertEquals('asc', $data['meta']['filters']['sort_direction']);
        $this->assertEquals('Test Model 1', $data['data'][0]['name']);
    }

    /** @test */
    public function it_validates_sort_direction()
    {
        $this->createTestModels(3);

        // Invalid sort direction should fall back to default
        $request = Request::create('/test?sort_direction=invalid', 'GET', [
            'sort_direction' => 'invalid'
        ]);

        $response = $this->controller->index($request);
        $data = $response->getData(true);

        $this->assertEquals('desc', $data['meta']['filters']['sort_direction']);
    }

    /** @test */
    public function it_validates_date_filters()
    {
        $this->createTestModels(3);

        // Invalid date should be ignored
        $request = Request::create('/test?created_at_from=invalid-date', 'GET', [
            'created_at_from' => 'invalid-date'
        ]);

        $response = $this->controller->index($request);
        $data = $response->getData(true);

        // Should return all records since invalid date is ignored
        $this->assertEquals(3, $data['meta']['pagination']['total']);

        // Valid date should work
        $request = Request::create('/test?created_at_from=' . now()->toDateString(), 'GET', [
            'created_at_from' => now()->toDateString()
        ]);

        $response = $this->controller->index($request);
        $data = $response->getData(true);

        $this->assertEquals(3, $data['meta']['pagination']['total']);
    }

    /** @test */
    public function it_handles_exceptions_gracefully()
    {
        Log::shouldReceive('error')->once();

        // Force an exception by using a non-existent model
        $badController = new class extends ApiController {
            protected $model = 'NonExistentModel';
            protected array $searchableFields = ['name'];
            protected array $filterableFields = ['status'];
        };

        $request = Request::create('/test', 'GET');
        $response = $badController->index($request);

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals(500, $response->status());
        $this->assertStringContainsString('Failed to retrieve data', $data['message']);
    }

    /** @test */
    public function it_returns_appropriate_meta_for_empty_results()
    {
        // No models created

        $request = Request::create('/test', 'GET');
        $response = $this->controller->index($request);

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals('No records found', $data['message']);
        $this->assertCount(0, $data['data']);
    }

    /** @test */
    public function it_limits_search_term_length()
    {
        $this->createTestModels(2);

        // Create a very long search term (> 255 characters)
        $longSearch = str_repeat('a', 300);

        $request = Request::create('/test?search=' . $longSearch, 'GET', ['search' => $longSearch]);
        $response = $this->controller->index($request);

        $data = $response->getData(true);

        // Should not error, should truncate the search term
        $this->assertTrue($data['success']);
    }
}

/**
 * Test Controller for Unit Testing
 */
class TestApiController extends ApiController
{
    protected $model = TestModel::class;

    protected array $searchableFields = [
        'name',
        'email',
        'category.name', // Test relationship search
    ];

    protected array $filterableFields = [
        'name',
        'status',
        'price',
        'featured',
        'created_at',
    ];

    protected function getIndexWith(): array
    {
        return ['category'];
    }

    // Override to test that we can't request all records
    protected function canRequestAllRecords(Request $request): bool
    {
        return false;
    }
}
