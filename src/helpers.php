<?php

use Masum\QueryController\Responses\ApiResponse;
use Masum\QueryController\Responses\SuccessResponse;
use Masum\QueryController\Responses\ErrorResponse;
use Illuminate\Http\JsonResponse;

if (!function_exists('api_response')) {
    /**
     * Create a new ApiResponse instance
     */
    function api_response(): ApiResponse
    {
        return new ApiResponse();
    }
}

if (!function_exists('success_response')) {
    /**
     * Create a success JSON response
     */
    function success_response(
        string $message = 'Operation successful',
        mixed $data = null,
        ?array $meta = null,
        int $statusCode = 200
    ): JsonResponse {
        return SuccessResponse::make($message, $data, $meta, $statusCode);
    }
}

if (!function_exists('error_response')) {
    /**
     * Create an error JSON response
     */
    function error_response(
        string $message = 'An error occurred',
        ?array $errors = null,
        int $statusCode = 400,
        ?array $meta = null
    ): JsonResponse {
        return ErrorResponse::make($message, $errors, $statusCode, $meta);
    }
}

if (!function_exists('paginated_response')) {
    /**
     * Create a paginated JSON response
     */
    function paginated_response(
        $paginator,
        string $message = 'Data retrieved successfully',
        ?array $additionalMeta = null
    ): JsonResponse {
        $data = $paginator->items();

        $meta = [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]
        ];

        if ($additionalMeta) {
            $meta = array_merge($meta, $additionalMeta);
        }

        return success_response($message, $data, $meta);
    }
}

if (!function_exists('created_response')) {
    /**
     * Create a 201 created response
     */
    function created_response(
        string $message = 'Resource created successfully',
        mixed $data = null
    ): JsonResponse {
        return SuccessResponse::created($message, $data);
    }
}

if (!function_exists('validation_error_response')) {
    /**
     * Create a 422 validation error response
     */
    function validation_error_response(
        string $message = 'Validation failed',
        ?array $errors = null
    ): JsonResponse {
        return ErrorResponse::validation($message, $errors);
    }
}

if (!function_exists('not_found_response')) {
    /**
     * Create a 404 not found response
     */
    function not_found_response(
        string $message = 'Resource not found'
    ): JsonResponse {
        return ErrorResponse::notFound($message);
    }
}

if (!function_exists('unauthorized_response')) {
    /**
     * Create a 401 unauthorized response
     */
    function unauthorized_response(
        string $message = 'Unauthorized access'
    ): JsonResponse {
        return ErrorResponse::unauthorized($message);
    }
}

if (!function_exists('forbidden_response')) {
    /**
     * Create a 403 forbidden response
     */
    function forbidden_response(
        string $message = 'Access forbidden'
    ): JsonResponse {
        return ErrorResponse::forbidden($message);
    }
}

if (!function_exists('server_error_response')) {
    /**
     * Create a 500 server error response
     */
    function server_error_response(
        string $message = 'Internal server error'
    ): JsonResponse {
        return ErrorResponse::serverError($message);
    }
}