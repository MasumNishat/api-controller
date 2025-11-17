<?php

namespace Masum\ApiController\Tests\Feature;

use Illuminate\Http\Request;
use Masum\ApiController\Tests\Category;
use Masum\ApiController\Tests\TestCase;
use Masum\ApiController\Tests\TestModel;
use Masum\ApiController\Tests\Unit\TestApiController;

class ApiControllerFeatureTest extends TestCase
{
    protected TestApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestApiController();
    }

    /** @test */
    public function it_handles_complete_workflow_with_search_filter_sort_and_pagination()
    {
        $categories = $this->createCategories(3);

        // Create mix of products
        for ($i = 1; $i <= 30; $i++) {
            TestModel::create([
                'name' => $i % 3 === 0 ? "Premium Product {$i}" : "Standard Product {$i}",
                'email' => "product{$i}@example.com",
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
                'price' => $i * 50,
                'featured' => $i % 5 === 0,
                'category_id' => $categories[$i % 3]->id,
            ]);
        }

        // Test 1: Search + Filter + Sort + Pagination
        $request = Request::create('/test', 'GET', [
            'search' => 'Premium',
            'status' => 'active',
            'sort_by' => 'price',
            'sort_direction' => 'asc',
            'per_page' => 5,
            'page' => 1,
        ]);

        $response = $this->controller->index($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertLessThanOrEqual(5, count($data['data']));
        $this->assertArrayHasKey('pagination', $data['meta']);

        // Verify sorting (prices should be ascending)
        if (count($data['data']) > 1) {
            $prices = array_column($data['data'], 'price');
            $this->assertEquals($prices, array_values(array_sort($prices)));
        }

        // Test 2: Range filter
        $request = Request::create('/test', 'GET', [
            'price' => ['min' => 500, 'max' => 1000],
        ]);

        $response = $this->controller->index($request);
        $data = $response->getData(true);

        foreach ($data['data'] as $item) {
            $this->assertGreaterThanOrEqual(500, $item['price']);
            $this->assertLessThanOrEqual(1000, $item['price']);
        }
    }

    /** @test */
    public function it_handles_relationship_search_correctly()
    {
        $electronics = Category::create(['name' => 'Electronics']);
        $clothing = Category::create(['name' => 'Clothing']);

        TestModel::create([
            'name' => 'Laptop',
            'category_id' => $electronics->id,
        ]);

        TestModel::create([
            'name' => 'Shirt',
            'category_id' => $clothing->id,
        ]);

        TestModel::create([
            'name' => 'Phone',
            'category_id' => $electronics->id,
        ]);

        // Search by category name
        $request = Request::create('/test?search=Electronics', 'GET', ['search' => 'Electronics']);
        $response = $this->controller->index($request);
        $data = $response->getData(true);

        $this->assertEquals(2, $data['meta']['pagination']['total']);
    }

    /** @test */
    public function it_handles_date_range_filters()
    {
        // Create models at different times
        $old = TestModel::create(['name' => 'Old Product']);
        $old->created_at = now()->subDays(10);
        $old->save();

        $recent = TestModel::create(['name' => 'Recent Product']);

        // Filter for recent items only
        $request = Request::create('/test', 'GET', [
            'created_at_from' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->controller->index($request);
        $data = $response->getData(true);

        $this->assertEquals(1, $data['meta']['pagination']['total']);
        $this->assertEquals('Recent Product', $data['data'][0]['name']);
    }

    /** @test */
    public function it_handles_multiple_filters_simultaneously()
    {
        $this->createTestModels(20);

        $request = Request::create('/test', 'GET', [
            'status' => 'active',
            'featured' => true,
            'price' => ['min' => 200],
        ]);

        $response = $this->controller->index($request);
        $data = $response->getData(true);

        // Verify all filters are applied
        foreach ($data['data'] as $item) {
            $this->assertEquals('active', $item['status']);
            $this->assertTrue((bool)$item['featured']);
            $this->assertGreaterThanOrEqual(200, $item['price']);
        }
    }

    /** @test */
    public function it_returns_appropriate_messages_for_different_scenarios()
    {
        // No records
        $request = Request::create('/test', 'GET');
        $response = $this->controller->index($request);
        $data = $response->getData(true);
        $this->assertEquals('No records found', $data['message']);

        // With records
        $this->createTestModels(25);

        $request = Request::create('/test', 'GET');
        $response = $this->controller->index($request);
        $data = $response->getData(true);
        $this->assertStringContainsString('Retrieved', $data['message']);
        $this->assertStringContainsString('of 25 records', $data['message']);
    }

    /** @test */
    public function it_handles_edge_cases_gracefully()
    {
        $this->createTestModels(5);

        // Empty search term
        $request = Request::create('/test?search=', 'GET', ['search' => '']);
        $response = $this->controller->index($request);
        $this->assertEquals(200, $response->status());

        // Page beyond available pages
        $request = Request::create('/test?page=999', 'GET', ['page' => 999]);
        $response = $this->controller->index($request);
        $data = $response->getData(true);
        $this->assertEquals(200, $response->status());
        $this->assertCount(0, $data['data']);

        // Negative per_page (should use default)
        $request = Request::create('/test?per_page=-1', 'GET', ['per_page' => -1]);
        $response = $this->controller->index($request);
        $this->assertEquals(422, $response->status()); // Validation error
    }

    /** @test */
    public function it_maintains_filter_information_in_meta()
    {
        $this->createTestModels(10);

        $request = Request::create('/test', 'GET', [
            'search' => 'test',
            'status' => 'active',
            'sort_by' => 'price',
            'sort_direction' => 'desc',
        ]);

        $response = $this->controller->index($request);
        $data = $response->getData(true);

        $this->assertEquals('test', $data['meta']['filters']['search']);
        $this->assertEquals('price', $data['meta']['filters']['sort_by']);
        $this->assertEquals('desc', $data['meta']['filters']['sort_direction']);
        $this->assertArrayHasKey('status', $data['meta']['filters']['applied_filters']);
    }
}
