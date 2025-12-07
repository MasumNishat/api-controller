<?php

namespace Masum\QueryController\Formatters;

use Masum\QueryController\Contracts\ResponseFormatterInterface;

/**
 * JSend specification formatter
 * @see https://github.com/omniti-labs/jsend
 */
class JSendFormatter implements ResponseFormatterInterface
{
    /**
     * Format a successful response
     * JSend success format: {"status": "success", "data": {...}}
     */
    public function success(
        string $message,
        mixed $data = null,
        ?array $meta = null,
        int $statusCode = 200
    ): array {
        $response = [
            'status' => 'success',
            'data' => $data,
        ];

        // Add message and meta as part of data if needed
        if ($message && config('query-controller.jsend.include_message', true)) {
            $response['message'] = $message;
        }

        if ($meta !== null) {
            $response['data'] = [
                'items' => $data,
                'meta' => $meta,
            ];
        }

        return $response;
    }

    /**
     * Format an error response
     * JSend error format: {"status": "error", "message": "..."}
     * JSend fail format (client error): {"status": "fail", "data": {...}}
     */
    public function error(
        string $message,
        ?array $errors = null,
        int $statusCode = 400,
        ?array $meta = null
    ): array {
        // Use "fail" for client errors (4xx), "error" for server errors (5xx)
        $status = $statusCode >= 500 ? 'error' : 'fail';

        $response = [
            'status' => $status,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['data'] = $errors;
        }

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return $response;
    }

    /**
     * Format a paginated response
     */
    public function paginated(
        $paginator,
        string $message,
        ?array $additionalMeta = null
    ): array {
        // Check if this is actually a paginator or just a collection
        if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator) {
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

            return $this->success($message, $paginator->items(), $meta);
        }

        // Handle Collection (when no pagination is requested)
        $meta = $additionalMeta ?? [];

        return $this->success($message, $paginator->toArray(), $meta);
    }
}
