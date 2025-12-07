<?php

namespace Masum\QueryController\Contracts;

interface ResponseFormatterInterface
{
    /**
     * Format a successful response
     */
    public function success(
        string $message,
        mixed $data = null,
        ?array $meta = null,
        int $statusCode = 200
    ): array;

    /**
     * Format an error response
     */
    public function error(
        string $message,
        ?array $errors = null,
        int $statusCode = 400,
        ?array $meta = null
    ): array;

    /**
     * Format a paginated response
     */
    public function paginated(
        $paginator,
        string $message,
        ?array $additionalMeta = null
    ): array;
}
