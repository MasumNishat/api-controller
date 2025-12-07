<?php

namespace Masum\QueryController\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;

class ErrorResponse
{
    public static function make(
        string $message = 'An error occurred',
        ?array $errors = null,
        int $statusCode = 400,
        ?array $meta = null
    ): JsonResponse {
        // Sanitize SQL error messages if configured
        if (config('query-controller.sanitize_sql_errors', true) && str_contains($message, 'SQLSTATE')) {
            $message = 'Error with your provided data.';
        }

        return ApiResponse::make()
            ->success(false)
            ->message($message)
            ->data(null)
            ->errors($errors)
            ->meta($meta)
            ->statusCode($statusCode)
            ->toJsonResponse();
    }

    public static function validation(
        string $message = 'Validation failed',
        ?array $errors = null,
        ?MessageBag $messageBag = null
    ): JsonResponse {
        $formattedErrors = $errors ?? [];

        if ($messageBag) {
            $formattedErrors = $messageBag->toArray();
        }

        return self::make($message, $formattedErrors, 422);
    }

    public static function unauthorized(
        string $message = 'Unauthorized access'
    ): JsonResponse {
        return self::make($message, null, 401);
    }

    public static function forbidden(
        string $message = 'Access forbidden'
    ): JsonResponse {
        return self::make($message, null, 403);
    }

    public static function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return self::make($message, null, 404);
    }

    public static function methodNotAllowed(
        string $message = 'Method not allowed'
    ): JsonResponse {
        return self::make($message, null, 405);
    }

    public static function conflict(
        string $message = 'Resource conflict'
    ): JsonResponse {
        return self::make($message, null, 409);
    }

    public static function unprocessable(
        string $message = 'Unprocessable entity'
    ): JsonResponse {
        return self::make($message, null, 422);
    }

    public static function tooManyRequests(
        string $message = 'Too many requests',
        int $retryAfter = 60
    ): JsonResponse {
        return self::make($message, null, 429, [
            'retry_after' => $retryAfter
        ]);
    }

    public static function serverError(
        string $message = 'Internal server error'
    ): JsonResponse {
        return self::make($message, null, 500);
    }

    public static function serviceUnavailable(
        string $message = 'Service temporarily unavailable'
    ): JsonResponse {
        return self::make($message, null, 503);
    }
}