<?php

namespace Masum\ApiController\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    private bool $success;
    private string $message;
    private mixed $data;
    private ?array $errors;
    private ?array $meta;
    private int $statusCode;

    public function __construct(
        bool $success = true,
        string $message = '',
        mixed $data = null,
        ?array $errors = null,
        ?array $meta = null,
        int $statusCode = 200
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->errors = $errors;
        $this->meta = $meta;
        $this->statusCode = $statusCode;
    }

    public static function make(): self
    {
        return new self();
    }

    public function success(bool $success = true): self
    {
        $this->success = $success;
        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function data(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function errors(?array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    public function meta(?array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    public function statusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function toArray(): array
    {
        $response = [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'timestamp' => $this->formatTimestamp(),
        ];

        // Add version if configured
        if (config('api-controller.include_version', false)) {
            $response['version'] = config('api-controller.version', '1.0.0');
        }

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        if ($this->meta !== null) {
            $response['meta'] = $this->meta;
        }

        return $response;
    }

    /**
     * Format timestamp according to configuration.
     *
     * Supports multiple timestamp formats:
     * - iso8601: ISO 8601 format (default)
     * - unix: Unix timestamp (seconds since epoch)
     * - custom: Custom format defined in config
     *
     * @return string|int The formatted timestamp
     */
    protected function formatTimestamp(): string|int
    {
        $format = config('api-controller.response.timestamp_format', 'iso8601');

        return match($format) {
            'iso8601' => now()->toISOString(),
            'unix' => now()->timestamp,
            'custom' => now()->format(config('api-controller.response.custom_timestamp_format', 'Y-m-d H:i:s')),
            default => now()->toISOString(),
        };
    }

    public function toJsonResponse(): JsonResponse
    {
        return response()->json(
            $this->toArray(),
            $this->statusCode
        );
    }
}