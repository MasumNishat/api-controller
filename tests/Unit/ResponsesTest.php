<?php

namespace Masum\ApiController\Tests\Unit;

use Masum\ApiController\Responses\ApiResponse;
use Masum\ApiController\Responses\ErrorResponse;
use Masum\ApiController\Responses\SuccessResponse;
use Masum\ApiController\Tests\TestCase;

class ResponsesTest extends TestCase
{
    /** @test */
    public function it_creates_api_response_with_builder_pattern()
    {
        $response = ApiResponse::make()
            ->success(true)
            ->message('Test message')
            ->data(['key' => 'value'])
            ->meta(['meta_key' => 'meta_value'])
            ->statusCode(200)
            ->toJsonResponse();

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Test message', $data['message']);
        $this->assertEquals(['key' => 'value'], $data['data']);
        $this->assertEquals(['meta_key' => 'meta_value'], $data['meta']);
        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('timestamp', $data);
    }

    /** @test */
    public function it_includes_timestamp_in_response()
    {
        $response = ApiResponse::make()
            ->success(true)
            ->message('Test')
            ->toJsonResponse();

        $data = $response->getData(true);

        $this->assertArrayHasKey('timestamp', $data);
        $this->assertNotEmpty($data['timestamp']);
    }

    /** @test */
    public function it_includes_version_when_configured()
    {
        config(['api-controller.include_version' => true]);
        config(['api-controller.version' => '2.0.0']);

        $response = ApiResponse::make()
            ->success(true)
            ->message('Test')
            ->toJsonResponse();

        $data = $response->getData(true);

        $this->assertArrayHasKey('version', $data);
        $this->assertEquals('2.0.0', $data['version']);
    }

    /** @test */
    public function it_excludes_version_when_not_configured()
    {
        config(['api-controller.include_version' => false]);

        $response = ApiResponse::make()
            ->success(true)
            ->message('Test')
            ->toJsonResponse();

        $data = $response->getData(true);

        $this->assertArrayNotHasKey('version', $data);
    }

    /** @test */
    public function it_creates_success_response()
    {
        $response = SuccessResponse::make('Operation successful', ['id' => 1]);

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Operation successful', $data['message']);
        $this->assertEquals(['id' => 1], $data['data']);
        $this->assertEquals(200, $response->status());
    }

    /** @test */
    public function it_creates_created_response()
    {
        $response = SuccessResponse::created('Resource created', ['id' => 1]);

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Resource created', $data['message']);
        $this->assertEquals(201, $response->status());
    }

    /** @test */
    public function it_creates_no_content_response()
    {
        $response = SuccessResponse::noContent();

        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals(204, $response->status());
    }

    /** @test */
    public function it_creates_error_response()
    {
        $response = ErrorResponse::make('An error occurred');

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals('An error occurred', $data['message']);
        $this->assertNull($data['data']);
        $this->assertEquals(400, $response->status());
    }

    /** @test */
    public function it_sanitizes_sql_errors()
    {
        config(['api-controller.sanitize_sql_errors' => true]);

        $sqlError = 'SQLSTATE[42S02]: Base table or view not found';
        $response = ErrorResponse::make($sqlError);

        $data = $response->getData(true);

        $this->assertStringNotContainsString('SQLSTATE', $data['message']);
        $this->assertEquals('Error processing your request. Please check your input and try again.', $data['message']);
    }

    /** @test */
    public function it_sanitizes_various_sql_error_patterns()
    {
        config(['api-controller.sanitize_sql_errors' => true]);

        $sqlErrors = [
            'Syntax error or access violation',
            'Unknown column \'test\' in \'field list\'',
            'Table \'users\' doesn\'t exist',
            'Database connection failed',
            'Integrity constraint violation',
            'Foreign key constraint fails',
            'Duplicate entry for key',
        ];

        foreach ($sqlErrors as $error) {
            $response = ErrorResponse::make($error);
            $data = $response->getData(true);

            $this->assertEquals(
                'Error processing your request. Please check your input and try again.',
                $data['message'],
                "Failed to sanitize: {$error}"
            );
        }
    }

    /** @test */
    public function it_does_not_sanitize_when_disabled()
    {
        config(['api-controller.sanitize_sql_errors' => false]);

        $error = 'SQLSTATE[42S02]: Base table not found';
        $response = ErrorResponse::make($error);

        $data = $response->getData(true);

        $this->assertEquals($error, $data['message']);
    }

    /** @test */
    public function it_creates_validation_error_response()
    {
        $errors = ['name' => ['The name field is required.']];
        $response = ErrorResponse::validation('Validation failed', $errors);

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertEquals($errors, $data['errors']);
        $this->assertEquals(422, $response->status());
    }

    /** @test */
    public function it_creates_unauthorized_response()
    {
        $response = ErrorResponse::unauthorized();

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals(401, $response->status());
        $this->assertEquals('Unauthorized access', $data['message']);
    }

    /** @test */
    public function it_creates_forbidden_response()
    {
        $response = ErrorResponse::forbidden();

        $data = $response->getData(true);

        $this->assertEquals(403, $response->status());
        $this->assertEquals('Access forbidden', $data['message']);
    }

    /** @test */
    public function it_creates_not_found_response()
    {
        $response = ErrorResponse::notFound();

        $data = $response->getData(true);

        $this->assertEquals(404, $response->status());
        $this->assertEquals('Resource not found', $data['message']);
    }

    /** @test */
    public function it_creates_too_many_requests_response()
    {
        $response = ErrorResponse::tooManyRequests('Rate limit exceeded', 120);

        $data = $response->getData(true);

        $this->assertEquals(429, $response->status());
        $this->assertEquals('Rate limit exceeded', $data['message']);
        $this->assertEquals(120, $data['meta']['retry_after']);
    }

    /** @test */
    public function it_creates_server_error_response()
    {
        $response = ErrorResponse::serverError();

        $data = $response->getData(true);

        $this->assertEquals(500, $response->status());
        $this->assertEquals('Internal server error', $data['message']);
    }
}
