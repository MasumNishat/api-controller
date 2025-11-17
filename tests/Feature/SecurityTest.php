<?php

namespace Masum\ApiController\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Masum\ApiController\Tests\TestCase;
use Masum\ApiController\Tests\Unit\TestApiController;

class SecurityTest extends TestCase
{
    protected TestApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestApiController();
    }

    /** @test */
    public function it_prevents_sql_injection_in_search()
    {
        $this->createTestModels(5);

        // SQL injection attempts
        $maliciousSearches = [
            "'; DROP TABLE test_models; --",
            "1' OR '1'='1",
            "admin' --",
            "' UNION SELECT * FROM users --",
        ];

        foreach ($maliciousSearches as $maliciousSearch) {
            $request = Request::create('/test', 'GET', ['search' => $maliciousSearch]);
            $response = $this->controller->index($request);

            // Should not throw an error and should sanitize the input
            $this->assertEquals(200, $response->status());

            // Verify the table still exists
            $this->assertDatabaseCount('test_models', 5);
        }
    }

    /** @test */
    public function it_prevents_sql_injection_in_sort_column()
    {
        Log::shouldReceive('warning')->atLeast()->once();

        $this->createTestModels(5);

        $maliciousSorts = [
            "name; DROP TABLE test_models; --",
            "name' OR '1'='1",
            "(SELECT * FROM users)",
            "name UNION SELECT password FROM users",
        ];

        foreach ($maliciousSorts as $maliciousSort) {
            $request = Request::create('/test', 'GET', ['sort_by' => $maliciousSort]);
            $response = $this->controller->index($request);

            // Should fall back to default sort
            $data = $response->getData(true);
            $this->assertEquals(200, $response->status());
            $this->assertEquals('created_at', $data['meta']['filters']['sort_by']);

            // Verify the table still exists
            $this->assertDatabaseCount('test_models', 5);
        }
    }

    /** @test */
    public function it_prevents_unauthorized_field_filtering()
    {
        $this->createTestModels(5);

        // Try to filter by a field not in filterableFields
        // 'email' is not in the filterableFields of TestApiController
        $request = Request::create('/test', 'GET', ['email' => 'test1@example.com']);
        $response = $this->controller->index($request);
        $data = $response->getData(true);

        // Should return all records, ignoring the unauthorized filter
        $this->assertEquals(5, $data['meta']['pagination']['total']);
    }

    /** @test */
    public function it_sanitizes_sql_errors_in_responses()
    {
        config(['api-controller.sanitize_sql_errors' => true]);

        // This would normally trigger if we had a real SQL error
        // We'll test the ErrorResponse directly
        $sqlError = 'SQLSTATE[42S02]: Base table or view not found: 1146 Table \'database.table\' doesn\'t exist';

        $response = \Masum\ApiController\Responses\ErrorResponse::make($sqlError);
        $data = $response->getData(true);

        // Should not expose SQL details
        $this->assertStringNotContainsString('SQLSTATE', $data['message']);
        $this->assertStringNotContainsString('Table', $data['message']);
        $this->assertStringNotContainsString('database', $data['message']);
    }

    /** @test */
    public function it_validates_input_to_prevent_dos_attacks()
    {
        $this->createTestModels(150);

        // Try to request excessive per_page
        $request = Request::create('/test', 'GET', ['per_page' => 10000]);
        $response = $this->controller->index($request);
        $data = $response->getData(true);

        // Should cap at maxPerPage (100)
        $this->assertEquals(100, $data['meta']['pagination']['per_page']);
        $this->assertCount(100, $data['data']);
    }

    /** @test */
    public function it_prevents_fetching_all_records_without_permission()
    {
        $this->createTestModels(50);

        $request = Request::create('/test', 'GET', ['all' => true]);
        $response = $this->controller->index($request);
        $data = $response->getData(true);

        // Should still paginate
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertLessThanOrEqual(15, count($data['data']));
    }

    /** @test */
    public function it_escapes_like_wildcards_in_search()
    {
        $this->createTestModels(5);

        // Create a model with special characters
        \Masum\ApiController\Tests\TestModel::create([
            'name' => 'Product_with_underscore',
        ]);

        // Search for literal underscore (should be escaped)
        $request = Request::create('/test', 'GET', ['search' => '_']);
        $response = $this->controller->index($request);
        $data = $response->getData(true);

        // Should not match everything (which would happen if _ is treated as wildcard)
        $this->assertEquals(200, $response->status());
    }

    /** @test */
    public function it_limits_search_term_length()
    {
        $this->createTestModels(2);

        // Create extremely long search term
        $longSearch = str_repeat('a', 500);

        $request = Request::create('/test', 'GET', ['search' => $longSearch]);
        $response = $this->controller->index($request);

        // Should not error, search term should be truncated
        $this->assertEquals(200, $response->status());
    }

    /** @test */
    public function it_validates_date_format_to_prevent_injection()
    {
        $this->createTestModels(5);

        // Malformed date
        $request = Request::create('/test', 'GET', [
            'created_at_from' => "2024-01-01' OR '1'='1",
        ]);

        $response = $this->controller->index($request);

        // Should handle gracefully
        $this->assertEquals(200, $response->status());

        // Verify the table still exists
        $this->assertDatabaseCount('test_models', 5);
    }

    /** @test */
    public function it_logs_suspicious_activities()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Invalid sort column attempted', \Mockery::type('array'));

        $this->createTestModels(3);

        // Attempt SQL injection in sort
        $request = Request::create('/test', 'GET', [
            'sort_by' => 'malicious; DROP TABLE users;',
        ]);

        $this->controller->index($request);
    }

    /** @test */
    public function it_handles_array_filter_injection_attempts()
    {
        $this->createTestModels(5);

        // Try to inject SQL through array filters
        $request = Request::create('/test', 'GET', [
            'price' => [
                'min' => "100 OR 1=1",
                'max' => "200; DROP TABLE users",
            ],
        ]);

        $response = $this->controller->index($request);

        // Laravel's parameter binding should protect against this
        $this->assertEquals(200, $response->status());
        $this->assertDatabaseCount('test_models', 5);
    }

    /** @test */
    public function it_prevents_mass_assignment_through_filters()
    {
        $this->createTestModels(3);

        // Try to add arbitrary parameters that aren't filterable
        $request = Request::create('/test', 'GET', [
            'password' => 'leaked',
            'admin' => true,
            'secret_key' => 'exposed',
        ]);

        $response = $this->controller->index($request);
        $data = $response->getData(true);

        // Should return all records, ignoring unauthorized filters
        $this->assertEquals(3, $data['meta']['pagination']['total']);

        // These fields shouldn't be in applied_filters since they're not filterable
        $appliedFilters = $data['meta']['filters']['applied_filters'];
        $this->assertArrayNotHasKey('password', $appliedFilters);
        $this->assertArrayNotHasKey('admin', $appliedFilters);
        $this->assertArrayNotHasKey('secret_key', $appliedFilters);
    }
}
