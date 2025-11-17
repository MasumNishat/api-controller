<?php

namespace Masum\ApiController\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
     * Override this method to control who can request all records
     */
    protected function canRequestAllRecords(Request $request): bool
    {
        // By default, deny fetching all records for security
        // Override in child controllers with proper authorization
        return false;
    }

    /**
     * Common search and filter method for index endpoints
     */
    protected function handleIndexRequest(Request $request, ?Builder $query = null): JsonResponse
    {
        try {
            // Validate request parameters
            $this->validateRequestParameters($request);

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

        } catch (ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            // Log the full error for debugging
            Log::error('Failed to retrieve data in ' . static::class, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['password', 'token']), // Exclude sensitive data
            ]);

            // Return sanitized error message to user
            $message = config('app.debug')
                ? 'Failed to retrieve data: ' . $e->getMessage()
                : 'Failed to retrieve data. Please try again later.';

            return $this->error($message, null, 500);
        }
    }

    /**
     * Validate request parameters for security
     */
    protected function validateRequestParameters(Request $request): void
    {
        $rules = [
            'per_page' => 'nullable|integer|min:1|max:' . $this->maxPerPage,
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|max:50',
            'sort_direction' => 'nullable|in:asc,desc',
            'search' => 'nullable|string|max:255',
            'all' => 'nullable|boolean',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Sanitize search term to prevent SQL injection
     */
    protected function sanitizeSearchTerm(string $searchTerm): string
    {
        // Escape special LIKE characters
        // Laravel's parameter binding protects against SQL injection,
        // but we escape LIKE wildcards to prevent unexpected behavior
        $searchTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);

        // Limit search term length as an additional security measure
        return mb_substr($searchTerm, 0, 255);
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

        // Sanitize search term
        $searchTerm = $this->sanitizeSearchTerm($searchTerm);

        return $query->where(function (Builder $q) use ($searchTerm) {
            foreach ($this->searchableFields as $field) {
                // Handle relationship searches (e.g., 'user.name')
                if (str_contains($field, '.')) {
                    $this->applyRelationSearch($q, $field, $searchTerm);
                } else {
                    // Laravel's query builder uses parameter binding for LIKE queries
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
            // Laravel's query builder uses parameter binding
            $q->where($column, 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Validate if a date string is valid
     */
    protected function isValidDate(string $date): bool
    {
        try {
            $parsed = \Carbon\Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Apply date range filters
     */
    protected function applyDateFilter(Builder $query, string $key, $value): void
    {
        // Validate date format before using in query
        if (!$this->isValidDate($value)) {
            return;
        }

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
                return;
            }

            // Support partial range filters
            if (isset($value['min'])) {
                $query->where($key, '>=', $value['min']);
                return;
            }

            if (isset($value['max'])) {
                $query->where($key, '<=', $value['max']);
                return;
            }

            // Default: IN query
            $query->whereIn($key, $value);
            return;
        }

        // Handle boolean filters - support multiple formats
        if (in_array($value, ['true', 'false', '1', '0', 1, 0, true, false], true)) {
            $boolValue = in_array($value, ['true', '1', 1, true], true);
            $query->where($key, $boolValue);
            return;
        }

        // Handle null values - separate from empty string
        if ($value === 'null') {
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
     * Check if a field is filterable
     */
    protected function isFilterable(string $field): bool
    {
        // If no filterable fields defined, deny all for security
        if (empty($this->filterableFields)) {
            return false;
        }

        // Remove suffixes like _from, _to, [min], [max] for validation
        $baseField = preg_replace('/(_from|_to)$/', '', $field);
        $baseField = preg_replace('/\[.*\]/', '', $baseField);

        return in_array($baseField, $this->filterableFields, true);
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        $filters = $request->except(['search', 'sort_by', 'sort_direction', 'per_page', 'page', 'all']);

        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }

            // Check if field is filterable - security check
            if (!$this->isFilterable($key)) {
                continue;
            }

            $this->applyFilter($query, $key, $value);
        }

        return $query;
    }

    /**
     * Validate if a column is allowed for sorting
     */
    protected function validateSortColumn(string $column): bool
    {
        // Whitelist approach: build list of allowed columns
        $allowedColumns = array_unique(array_merge(
            $this->filterableFields,
            $this->searchableFields,
            ['id', 'created_at', 'updated_at']
        ));

        return in_array($column, $allowedColumns, true);
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

        // Validate sort column to prevent SQL injection
        if (!$this->validateSortColumn($sortBy)) {
            // Log suspicious activity
            Log::warning('Invalid sort column attempted', [
                'column' => $sortBy,
                'ip' => $request->ip(),
                'controller' => static::class,
            ]);

            // Fall back to default sort
            $sortBy = $this->defaultSort;
        }

        return $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Paginate results with dynamic per_page
     */
    protected function paginateResults(Builder $query, Request $request): LengthAwarePaginator|Collection
    {
        // Check for explicit 'all' request with authorization
        if ($request->boolean('all', false)) {
            if ($this->canRequestAllRecords($request)) {
                return $query->get();
            }

            // If not authorized, log and fall through to pagination
            Log::warning('Unauthorized attempt to fetch all records', [
                'ip' => $request->ip(),
                'controller' => static::class,
            ]);
        }

        // Always paginate for security (prevent DoS attacks)
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
                    'applied_filters' => $request->except(['search', 'sort_by', 'sort_direction', 'per_page', 'page', 'all']),
                ]
            ];
        }

        // For non-paginated results (all data) - no pagination metadata
        return [
            'filters' => [
                'search' => $request->get('search'),
                'sort_by' => $request->get('sort_by', $this->defaultSort),
                'sort_direction' => $request->get('sort_direction', $this->defaultDirection),
                'applied_filters' => $request->except(['search', 'sort_by', 'sort_direction', 'per_page', 'page', 'all']),
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
