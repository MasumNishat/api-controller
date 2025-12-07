<?php

namespace Masum\QueryController\Managers;

use Masum\QueryController\Contracts\ResponseFormatterInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Contracts\Support\Responsable;

class ResponseManager
{
    protected ResponseFormatterInterface $formatter;
    protected ?string $viewPath = null;
    protected bool $shouldReturnView = false;
    protected $originalPaginator = null;

    public function __construct(ResponseFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Set a custom formatter
     */
    public function setFormatter(ResponseFormatterInterface $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }

    /**
     * Set view path for this response
     */
    public function view(string $viewPath): self
    {
        $this->viewPath = $viewPath;
        $this->shouldReturnView = true;
        return $this;
    }

    /**
     * Return response as JSON (override view if set)
     */
    public function asJson(): self
    {
        $this->shouldReturnView = false;
        return $this;
    }

    /**
     * Create a success response
     */
    public function success(
        string $message = 'Operation successful',
        mixed $data = null,
        ?array $meta = null,
        int $statusCode = 200
    ): JsonResponse|Response|Responsable {
        $responseData = $this->formatter->success($message, $data, $meta, $statusCode);

        return $this->buildResponse($responseData, $statusCode);
    }

    /**
     * Create an error response
     */
    public function error(
        string $message = 'An error occurred',
        ?array $errors = null,
        int $statusCode = 400,
        ?array $meta = null
    ): JsonResponse|Response|Responsable {
        $responseData = $this->formatter->error($message, $errors, $statusCode, $meta);

        return $this->buildResponse($responseData, $statusCode);
    }

    /**
     * Create a created response (201)
     */
    public function created(
        string $message = 'Resource created successfully',
        mixed $data = null
    ): JsonResponse|Response|Responsable {
        return $this->success($message, $data, null, 201);
    }

    /**
     * Create a no content response (204)
     */
    public function noContent(
        string $message = 'No content'
    ): JsonResponse|Response|Responsable {
        return $this->success($message, null, null, 204);
    }

    /**
     * Create a paginated response
     */
    public function paginated(
        $paginator,
        string $message = 'Data retrieved successfully',
        ?array $additionalMeta = null
    ): JsonResponse|Response|Responsable {
        // Store original paginator for view rendering (so users can call ->links())
        $this->originalPaginator = $paginator;

        $responseData = $this->formatter->paginated($paginator, $message, $additionalMeta);

        return $this->buildResponse($responseData, 200);
    }

    /**
     * Create a validation error response (422)
     */
    public function validationError(
        string $message = 'Validation failed',
        ?array $errors = null
    ): JsonResponse|Response|Responsable {
        return $this->error($message, $errors, 422);
    }

    /**
     * Create a not found response (404)
     */
    public function notFound(
        string $message = 'Resource not found'
    ): JsonResponse|Response|Responsable {
        return $this->error($message, null, 404);
    }

    /**
     * Create an unauthorized response (401)
     */
    public function unauthorized(
        string $message = 'Unauthorized access'
    ): JsonResponse|Response|Responsable {
        return $this->error($message, null, 401);
    }

    /**
     * Create a forbidden response (403)
     */
    public function forbidden(
        string $message = 'Access forbidden'
    ): JsonResponse|Response|Responsable {
        return $this->error($message, null, 403);
    }

    /**
     * Build the final response (JSON or View)
     */
    protected function buildResponse(array $responseData, int $statusCode): JsonResponse|Response|Responsable
    {
        // Check if we should return a view
        if ($this->shouldReturnView && $this->viewPath) {
            return $this->renderView($responseData, $statusCode);
        }

        // Default to JSON response
        return response()->json($responseData, $statusCode);
    }

    /**
     * Render view with response data
     */
    protected function renderView(array $responseData, int $statusCode): Response|Responsable
    {
        // If we have a paginator, add it to the view data
        // This allows users to call $paginator->links() in Blade views
        if ($this->originalPaginator !== null) {
            $responseData['paginator'] = $this->originalPaginator;
        }

        // Support for Inertia.js
        if ($this->isInertiaRequest()) {
            return inertia($this->viewPath, $responseData);
        }

        // Support for Livewire (if component exists)
        if (class_exists(\Livewire\Livewire::class) && $this->isLivewireComponent($this->viewPath)) {
            return \Livewire\Livewire::mount($this->viewPath, $responseData);
        }

        // Default Blade view
        return response()->view($this->viewPath, $responseData, $statusCode);
    }

    /**
     * Check if this is an Inertia request
     */
    protected function isInertiaRequest(): bool
    {
        return function_exists('inertia') &&
               request()->header('X-Inertia');
    }

    /**
     * Check if the view path is a Livewire component
     */
    protected function isLivewireComponent(string $path): bool
    {
        if (!class_exists(\Livewire\Livewire::class)) {
            return false;
        }

        // Convert dot notation to class name
        $className = str_replace('.', '\\', $path);
        $className = 'App\\Http\\Livewire\\' . $className;

        return class_exists($className);
    }

    /**
     * Reset view settings for next response
     */
    public function reset(): self
    {
        $this->viewPath = null;
        $this->shouldReturnView = false;
        $this->originalPaginator = null;
        return $this;
    }
}
