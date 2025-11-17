<?php

namespace Masum\ApiController\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;

abstract class ApiController extends Controller
{
    /**
     * The model instance for querying
     */
    protected $model;

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
     * Common index method implementation
     */
    public function index(Request $request): JsonResponse
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
    protected function handleIndexRequest(Request $request, ?Builder $query = null): JsonResponse
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
    protected function paginateResults(Builder $query, Request $request): LengthAwarePaginator|Collection
    {
        $perPage = $this->getPerPage($request);

        // If per_page was not provided in the request, return all results
        if (!$request->has('per_page')) {
            return $query->get();
        }

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
     * Return success response
     */
    protected function success(
        string $message = 'Operation successful',
        mixed $data = null,
        ?array $meta = null,
        int $statusCode = 200
    ): JsonResponse {
        return success_response($message, $data, $meta, $statusCode);
    }

    /**
     * Return error response
     */
    protected function error(
        string $message = 'An error occurred',
        ?array $errors = null,
        int $statusCode = 400,
        ?array $meta = null
    ): JsonResponse {
        return error_response($message, $errors, $statusCode, $meta);
    }

    /**
     * Return created response
     */
    protected function created(
        string $message = 'Resource created successfully',
        mixed $data = null
    ): JsonResponse {
        return $this->success($message, $data, null, 201);
    }

    /**
     * Return no content response
     */
    protected function noContent(
        string $message = 'No content'
    ): JsonResponse {
        return $this->success($message, null, null, 204);
    }

    /**
     * Return paginated response
     */
    protected function paginated(
        $paginator,
        string $message = 'Data retrieved successfully',
        ?array $additionalMeta = null
    ): JsonResponse {
        return paginated_response($paginator, $message, $additionalMeta);
    }

    /**
     * Return validation error response
     */
    protected function validationError(
        string $message = 'Validation failed',
        ?array $errors = null
    ): JsonResponse {
        return $this->error($message, $errors, 422);
    }

    /**
     * Return not found response
     */
    protected function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->error($message, null, 404);
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorized(
        string $message = 'Unauthorized access'
    ): JsonResponse {
        return $this->error($message, null, 401);
    }

    /**
     * Return forbidden response
     */
    protected function forbidden(
        string $message = 'Access forbidden'
    ): JsonResponse {
        return $this->error($message, null, 403);
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission(string $action, string $resource): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user && $user->hasPermission($action, $resource);
    }

    /**
     * Check if user can access specific branch
     */
    protected function canAccessBranch(int $branchId): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user && $user->canAccessBranch($branchId);
    }

    /**
     * Get authenticated user
     */
    protected function getUser()
    {
        return \Illuminate\Support\Facades\Auth::user();
    }

    /**
     * Get user's branch ID
     */
    protected function getUserBranchId()
    {
        $user = $this->getUser();
        return $user ? $user->employee->branch_id ?? null : null;
    }

    /**
     * Apply branch filter to query
     */
    protected function applyBranchFilter($query, string $branchColumn = 'branch_id')
    {
        $user = $this->getUser();

        // If user is not super admin, filter by their branch
        if ($user && method_exists($user, 'isSuperAdmin')) {
            try {
                if (!$user->isSuperAdmin()) {
                    $branchId = $this->getUserBranchId();
                    if ($branchId) {
                        $query->where($branchColumn, $branchId);
                    }
                }
            } catch (\Exception $e) {
                // If there's an error checking super admin status, apply branch filter
                $branchId = $this->getUserBranchId();
                if ($branchId) {
                    $query->where($branchColumn, $branchId);
                }
            }
        }

        return $query;
    }
}