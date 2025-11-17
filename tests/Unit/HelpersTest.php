<?php

namespace Masum\ApiController\Tests\Unit;

use Masum\ApiController\Responses\ApiResponse;
use Masum\ApiController\Tests\TestCase;
use Masum\ApiController\Tests\TestModel;

class HelpersTest extends TestCase
{
    /** @test */
    public function it_creates_api_response_builder()
    {
        $response = api_response();

        $this->assertInstanceOf(ApiResponse::class, $response);
    }

    /** @test */
    public function it_creates_success_response()
    {
        $response = success_response('Success', ['id' => 1]);

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Success', $data['message']);
        $this->assertEquals(['id' => 1], $data['data']);
        $this->assertEquals(200, $response->status());
    }

    /** @test */
    public function it_creates_error_response()
    {
        $response = error_response('Error occurred', ['field' => ['error']], 400);

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Error occurred', $data['message']);
        $this->assertEquals(['field' => ['error']], $data['errors']);
        $this->assertEquals(400, $response->status());
    }

    /** @test */
    public function it_creates_paginated_response()
    {
        $models = $this->createTestModels(20);

        $paginator = TestModel::paginate(10);
        $response = paginated_response($paginator, 'Data retrieved');

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Data retrieved', $data['message']);
        $this->assertCount(10, $data['data']);
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertEquals(20, $data['meta']['pagination']['total']);
        $this->assertEquals(10, $data['meta']['pagination']['per_page']);
    }

    /** @test */
    public function it_creates_paginated_response_with_additional_meta()
    {
        $models = $this->createTestModels(5);

        $paginator = TestModel::paginate(5);
        $response = paginated_response($paginator, 'Data retrieved', ['custom' => 'meta']);

        $data = $response->getData(true);

        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertArrayHasKey('custom', $data['meta']);
        $this->assertEquals('meta', $data['meta']['custom']);
    }

    /** @test */
    public function it_creates_created_response()
    {
        $response = created_response('Resource created', ['id' => 1]);

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Resource created', $data['message']);
        $this->assertEquals(201, $response->status());
    }

    /** @test */
    public function it_creates_validation_error_response()
    {
        $errors = ['name' => ['The name field is required.']];
        $response = validation_error_response('Validation failed', $errors);

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertEquals($errors, $data['errors']);
        $this->assertEquals(422, $response->status());
    }

    /** @test */
    public function it_creates_not_found_response()
    {
        $response = not_found_response('User not found');

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals('User not found', $data['message']);
        $this->assertEquals(404, $response->status());
    }

    /** @test */
    public function it_creates_unauthorized_response()
    {
        $response = unauthorized_response('Please login');

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Please login', $data['message']);
        $this->assertEquals(401, $response->status());
    }

    /** @test */
    public function it_creates_forbidden_response()
    {
        $response = forbidden_response('Access denied');

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Access denied', $data['message']);
        $this->assertEquals(403, $response->status());
    }

    /** @test */
    public function it_creates_server_error_response()
    {
        $response = server_error_response('Server error');

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Server error', $data['message']);
        $this->assertEquals(500, $response->status());
    }
}
