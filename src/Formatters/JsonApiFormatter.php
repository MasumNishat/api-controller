<?php

namespace Masum\QueryController\Formatters;

use Masum\QueryController\Contracts\ResponseFormatterInterface;

/**
 * JSON:API specification formatter
 * @see https://jsonapi.org/format/
 */
class JsonApiFormatter implements ResponseFormatterInterface
{
    /**
     * Format a successful response
     * JSON:API format: {"data": [...], "meta": {...}}
     */
    public function success(
        string $message,
        mixed $data = null,
        ?array $meta = null,
        int $statusCode = 200
    ): array {
        $response = [
            'data' => $data,
        ];

        if ($meta !== null || $message) {
            $response['meta'] = $meta ?? [];

            if ($message && config('query-controller.jsonapi.include_message', true)) {
                $response['meta']['message'] = $message;
            }
        }

        return $response;
    }

    /**
     * Format an error response
     * JSON:API error format: {"errors": [{"status": "400", "title": "...", "detail": "..."}]}
     */
    public function error(
        string $message,
        ?array $errors = null,
        int $statusCode = 400,
        ?array $meta = null
    ): array {
        $errorObject = [
            'status' => (string) $statusCode,
            'title' => $this->getErrorTitle($statusCode),
            'detail' => $message,
        ];

        if ($errors !== null) {
            $errorObject['source'] = $errors;
        }

        $response = [
            'errors' => [$errorObject],
        ];

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

            if ($message) {
                $meta['message'] = $message;
            }

            return [
                'data' => $paginator->items(),
                'meta' => $meta,
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ]
            ];
        }

        // Handle Collection (when no pagination is requested)
        $meta = $additionalMeta ?? [];

        if ($message) {
            $meta['message'] = $message;
        }

        return $this->success($message, $paginator->toArray(), $meta);
    }

    /**
     * Get error title based on status code
     */
    private function getErrorTitle(int $statusCode): string
    {
        return match($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            default => 'Error',
        };
    }
}
