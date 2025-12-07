<?php

namespace Masum\QueryController\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Support\Responsable;
use Masum\QueryController\Managers\ResponseManager;

/**
 * Base controller for handling both API and web requests with automatic
 * filtering, searching, sorting, and pagination capabilities.
 *
 * This controller can return:
 * - JSON responses (for APIs)
 * - Blade views (for traditional web apps)
 * - Inertia.js responses (for SPAs)
 * - Livewire components (for reactive apps)
 */
abstract class ResourceController extends Controller
{
    /**
     * Response manager instance
     */
    protected ?ResponseManager $responseManager = null;

    /**
     * The model instance for querying
     */
    protected $model;

    /**
     * API Resource class for transforming data
     * @var string|null
     */
    protected ?string $resource = null;

    /**
     * Fields that can be searched
     * @var array
     */
    protected array $searchableFields = [];

    /**
     * Fields that can be filtered
     * @var array
     */
    protected array $filterableFields = [];

    /**
     * Default sort column
     * @var string
     */
    protected string $defaultSort = 'created_at';

    /**
     * Default sort direction
     * @var string
     */
    protected string $defaultDirection = 'desc';

    /**
     * Maximum items per page
     * @var int
     */
    protected int $maxPerPage = 100;

    /**
     * Default items per page
     * @var int
     */
    protected int $defaultPerPage = 15;

    /**
     * View path for Blade templates (optional)
     * Example: 'products.index' or 'admin.products.list'
     * @var string|null
     */
    protected ?string $indexView = null;

    /**
     * Inertia component path (optional)
     * Example: 'Products/Index' or 'Admin/Products/List'
     * @var string|null
     */
    protected ?string $indexInertiaComponent = null;

    /**
     * Livewire component class (optional)
     * Example: 'products.index' or App\Http\Livewire\Products\Index::class
     * @var string|null
     */
    protected ?string $indexLivewireComponent = null;

    /**
     * Common index method implementation
     */
    public function index(Request $request): JsonResponse|Response|Responsable
    {
        return $this->handleIndexRequest($request);
    }

    /**
     * Override this method to modify the base query
     */
    protected function getBaseIndexQuery(Request $request): Builder
    {
        return $this->model::query();
    }

    /**
     * Override this method to add eager loading
     */
    protected function getIndexWith(): array
    {
        return [];
    }

    /**
     * Override this method to add additional conditions
     */
    protected function applyAdditionalConditions(Builder $query, Request $request): Builder
    {
        return $query;
    }

    /**
     * Common search and filter method for index endpoints
     */
    protected function handleIndexRequest(Request $request, ?Builder $query = null): JsonResponse|Response|Responsable
    {
        try {
            // Use provided query or create new one from model
            $baseQuery = $query ?? $this->getBaseIndexQuery($request);

            // Apply eager loading
            $baseQuery->with($this->getIndexWith());

            // Apply additional conditions
            $baseQuery = $this->applyAdditionalConditions($baseQuery, $request);

            // Apply search, filters, sorting
            $finalQuery = $this->applySearch($baseQuery, $request);
            $finalQuery = $this->applyFilters($finalQuery, $request);
            $finalQuery = $this->applySorting($finalQuery, $request);

            // Get paginated results
            $results = $this->paginateResults($finalQuery, $request);

            // Transform results if needed
            $data = $this->transformIndexData($results, $request);

            // Determine if we should return a view or JSON response
            $viewPath = $this->determineViewPath($request);

            if ($viewPath && !$request->expectsJson()) {
                // Return view response (Blade, Inertia, or Livewire)
                return $this->response()
                    ->view($viewPath)
                    ->paginated($results, $this->getIndexMessage($results), $this->getIndexMeta($results, $request));
            }

            // Return JSON API response
            return $this->success(
                $this->getIndexMessage($results),
                $data,
                $this->getIndexMeta($results, $request)
            );

        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve data: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Determine which view path to use based on configuration and request type
     */
    protected function determineViewPath(Request $request): ?string
    {
        // Check for Inertia request
        if ($request->header('X-Inertia') && $this->indexInertiaComponent) {
            return $this->indexInertiaComponent;
        }

        // Check for Livewire component
        if ($this->indexLivewireComponent) {
            return $this->indexLivewireComponent;
        }

        // Default to Blade view
        return $this->indexView;
    }

    /**
     * Apply search to query
     */
    protected function applySearch(Builder $query, Request $request): Builder
    {
        $searchTerm = $request->get('search');

        if (!$searchTerm || empty($this->searchableFields)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($searchTerm) {
            foreach ($this->searchableFields as $field) {
                // Handle relationship searches (e.g., 'user.name')
                if (str_contains($field, '.')) {
                    $this->applyRelationSearch($q, $field, $searchTerm);
                } else {
                    $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                }
            }
        });
    }

    /**
     * Apply relation-based search
     */
    protected function applyRelationSearch(Builder $query, string $field, string $searchTerm): void
    {
        [$relation, $column] = explode('.', $field, 2);

        $query->orWhereHas($relation, function (Builder $q) use ($column, $searchTerm) {
            $q->where($column, 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Apply date range filters
     */
    protected function applyDateFilter(Builder $query, string $key, $value): void
    {
        // Handle created_at_from and created_at_to
        if (str_ends_with($key, '_from') && $value) {
            $column = str_replace('_from', '', $key);
            $query->whereDate($column, '>=', $value);
        }
        elseif (str_ends_with($key, '_to') && $value) {
            $column = str_replace('_to', '', $key);
            $query->whereDate($column, '<=', $value);
        }
    }

    /**
     * Apply individual filter with support for operators
     */
    protected function applyFilter(Builder $query, string $key, $value): void
    {
        // Handle date range filters first
        if (str_ends_with($key, '_from') || str_ends_with($key, '_to')) {
            $this->applyDateFilter($query, $key, $value);
            return;
        }

        // Handle array values for IN queries
        if (is_array($value)) {
            // Check if it's a range filter (min/max)
            if (isset($value['min']) && isset($value['max'])) {
                $query->whereBetween($key, [$value['min'], $value['max']]);
            } else {
                $query->whereIn($key, $value);
            }
            return;
        }

        // Handle boolean filters
        if ($value === 'true' || $value === 'false') {
            $query->where($key, $value === 'true');
            return;
        }

        // Handle null values
        if ($value === 'null' || $value === '') {
            $query->whereNull($key);
            return;
        }

        if ($value === 'not_null') {
            $query->whereNotNull($key);
            return;
        }

        // Handle relation filters (e.g., user_id, category_id)
        if (str_ends_with($key, '_id')) {
            $query->where($key, $value);
            return;
        }

        // Default exact match
        $query->where($key, $value);
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        $filters = $request->except(['search', 'sort_by', 'sort_direction', 'per_page', 'page']);

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Check if field is filterable
            if (!empty($this->filterableFields)) {
                // For date range filters, check the base column name
                $baseKey = $key;
                if (str_ends_with($key, '_from') || str_ends_with($key, '_to')) {
                    $baseKey = str_replace(['_from', '_to'], '', $key);
                }

                if (!in_array($baseKey, $this->filterableFields) && !in_array($key, $this->filterableFields)) {
                    continue;
                }
            }

            $this->applyFilter($query, $key, $value);
        }

        return $query;
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting(Builder $query, Request $request): Builder
    {
        $sortBy = $request->get('sort_by', $this->defaultSort);
        $sortDirection = $request->get('sort_direction', $this->defaultDirection);

        // Validate sort direction
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc'])
            ? $sortDirection
            : $this->defaultDirection;

        return $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Paginate results with dynamic per_page
     */
    protected function paginateResults(Builder $query, Request $request): LengthAwarePaginator
    {
        $perPage = $this->getPerPage($request);
        $page = $request->get('page', 1);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get per_page value from request with validation
     */
    protected function getPerPage(Request $request): int
    {
        $perPage = $request->get('per_page', $this->defaultPerPage);

        // Convert to integer
        $perPage = (int) $perPage;

        // Validate against maximum
        if ($perPage > $this->maxPerPage) {
            return $this->maxPerPage;
        }

        // Ensure minimum of 1
        return max(1, $perPage);
    }

    /**
     * Transform data before returning (can be overridden in child controllers)
     */
    protected function transformIndexData(LengthAwarePaginator|Collection $results, Request $request): array
    {
        // Only apply API Resource transformation for JSON API requests
        // For web views (Blade/Inertia/Livewire), pass raw Eloquent models
        if ($this->resource && $request->expectsJson()) {
            $items = $results instanceof LengthAwarePaginator ? $results->items() : $results;

            // Use resource collection
            return $this->resource::collection($items)->resolve();
        }

        // Default transformation - return raw models for web views
        return $results instanceof LengthAwarePaginator ? $results->items() : $results->toArray();
    }

    /**
     * Get success message for index (can be overridden)
     */
    protected function getIndexMessage(LengthAwarePaginator|Collection $results): string
    {
        $count = $results->count();

        if ($count === 0) {
            return 'No records found';
        }

        if ($results instanceof LengthAwarePaginator) {
            $total = $results->total();
            return "Retrieved {$results->count()} of {$total} records";
        }

        return "Retrieved {$count} records";
    }

    /**
     * Get meta data for index response
     */
    protected function getIndexMeta(LengthAwarePaginator|Collection $results, Request $request): array
    {
        $count = $results->count();

        if ($results instanceof LengthAwarePaginator) {
            return [
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                ],
                'filters' => [
                    'search' => $request->get('search'),
                    'sort_by' => $request->get('sort_by', $this->defaultSort),
                    'sort_direction' => $request->get('sort_direction', $this->defaultDirection),
                    'applied_filters' => $request->except(['search', 'sort_by', 'sort_direction', 'per_page', 'page']),
                ]
            ];
        }

        // For non-paginated results (all data) - no pagination metadata
        return [
            'filters' => [
                'search' => $request->get('search'),
                'sort_by' => $request->get('sort_by', $this->defaultSort),
                'sort_direction' => $request->get('sort_direction', $this->defaultDirection),
                'applied_filters' => $request->except(['search', 'sort_by', 'sort_direction', 'per_page', 'page']),
            ]
        ];
    }

    /**
     * Get or create response manager instance
     */
    protected function response(): ResponseManager
    {
        if ($this->responseManager === null) {
            $formatterClass = config(
                'api-controller.formatter',
                \Masum\QueryController\Formatters\DefaultFormatter::class
            );

            $formatter = app($formatterClass);
            $this->responseManager = new ResponseManager($formatter);
        }

        return $this->responseManager;
    }

    /**
     * Return success response
     */
    protected function success(
        string $message = 'Operation successful',
        mixed $data = null,
        ?array $meta = null,
        int $statusCode = 200
    ): JsonResponse|Response|Responsable {
        return $this->response()->success($message, $data, $meta, $statusCode);
    }

    /**
     * Return error response
     */
    protected function error(
        string $message = 'An error occurred',
        ?array $errors = null,
        int $statusCode = 400,
        ?array $meta = null
    ): JsonResponse|Response|Responsable {
        return $this->response()->error($message, $errors, $statusCode, $meta);
    }

    /**
     * Return created response
     */
    protected function created(
        string $message = 'Resource created successfully',
        mixed $data = null
    ): JsonResponse|Response|Responsable {
        return $this->response()->created($message, $data);
    }

    /**
     * Return no content response
     */
    protected function noContent(
        string $message = 'No content'
    ): JsonResponse|Response|Responsable {
        return $this->response()->noContent($message);
    }

    /**
     * Return paginated response
     */
    protected function paginated(
        $paginator,
        string $message = 'Data retrieved successfully',
        ?array $additionalMeta = null
    ): JsonResponse|Response|Responsable {
        return $this->response()->paginated($paginator, $message, $additionalMeta);
    }

    /**
     * Return validation error response
     */
    protected function validationError(
        string $message = 'Validation failed',
        ?array $errors = null
    ): JsonResponse|Response|Responsable {
        return $this->response()->validationError($message, $errors);
    }

    /**
     * Return not found response
     */
    protected function notFound(
        string $message = 'Resource not found'
    ): JsonResponse|Response|Responsable {
        return $this->response()->notFound($message);
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorized(
        string $message = 'Unauthorized access'
    ): JsonResponse|Response|Responsable {
        return $this->response()->unauthorized($message);
    }

    /**
     * Return forbidden response
     */
    protected function forbidden(
        string $message = 'Access forbidden'
    ): JsonResponse|Response|Responsable {
        return $this->response()->forbidden($message);
    }

    /**
     * Get authenticated user
     */
    protected function getUser()
    {
        return \Illuminate\Support\Facades\Auth::user();
    }
}