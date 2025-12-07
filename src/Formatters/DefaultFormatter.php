<?php

namespace Masum\QueryController\Formatters;

use Masum\QueryController\Contracts\ResponseFormatterInterface;

class DefaultFormatter implements ResponseFormatterInterface
{
    /**
     * Format a successful response
     */
    public function success(
        string $message,
        mixed $data = null,
        ?array $meta = null,
        int $statusCode = 200
    ): array {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        // Add version if configured
        if (config('query-controller.include_version', false)) {
            $response['version'] = config('query-controller.version', '1.0.0');
        }

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return $response;
    }

    /**
     * Format an error response
     */
    public function error(
        string $message,
        ?array $errors = null,
        int $statusCode = 400,
        ?array $meta = null
    ): array {
        $response = [
            'success' => false,
            'message' => $message,
            'data' => null,
            'timestamp' => now()->toISOString(),
        ];

        // Add version if configured
        if (config('query-controller.include_version', false)) {
            $response['version'] = config('query-controller.version', '1.0.0');
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
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
