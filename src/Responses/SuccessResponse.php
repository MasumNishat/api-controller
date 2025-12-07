<?php

namespace Masum\QueryController\Responses;

use Illuminate\Http\JsonResponse;

class SuccessResponse
{
    public static function make(
        string $message = 'Operation successful',
        mixed $data = null,
        ?array $meta = null,
        int $statusCode = 200
    ): JsonResponse {
        return ApiResponse::make()
            ->success(true)
            ->message($message)
            ->data($data)
            ->meta($meta)
            ->statusCode($statusCode)
            ->toJsonResponse();
    }

    public static function created(
        string $message = 'Resource created successfully',
        mixed $data = null,
        ?array $meta = null
    ): JsonResponse {
        return self::make($message, $data, $meta, 201);
    }

    public static function accepted(
        string $message = 'Request accepted',
        mixed $data = null,
        ?array $meta = null
    ): JsonResponse {
        return self::make($message, $data, $meta, 202);
    }

    public static function noContent(
        string $message = 'No content'
    ): JsonResponse {
        return self::make($message, null, null, 204);
    }
}