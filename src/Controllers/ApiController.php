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

/**
 * Class ApiController
 *
 * A powerful base controller for Laravel APIs with built-in support for:
 * - Dynamic filtering with multiple operators
 * - Full-text search across multiple fields and relationships
 * - Flexible sorting with security validation
 * - Smart pagination with configurable limits
 * - Standardized JSON responses
 * - Security features (SQL injection prevention, input validation)
 *
 * @package Masum\ApiController\Controllers
 * @version 1.0.0
 */
abstract class ApiController extends Controller
{
    // Query parameter names
    protected const PARAM_SEARCH = 'search';
    protected const PARAM_SORT_BY = 'sort_by';
    protected const PARAM_SORT_DIRECTION = 'sort_direction';
    protected const PARAM_PER_PAGE = 'per_page';
    protected const PARAM_PAGE = 'page';
    protected const PARAM_ALL = 'all';

    // Filter suffixes
    protected const SUFFIX_FROM = '_from';
    protected const SUFFIX_TO = '_to';
    protected const SUFFIX_MIN = 'min';
    protected const SUFFIX_MAX = 'max';

    // Filter value constants
    protected const VALUE_TRUE = 'true';
    protected const VALUE_FALSE = 'false';
    protected const VALUE_NULL = 'null';
    protected const VALUE_NOT_NULL = 'not_null';

    // Sort directions
    protected const SORT_ASC = 'asc';
    protected const SORT_DESC = 'desc';

    // Search term limits
    protected const MAX_SEARCH_LENGTH = 255;

    /**
     * The Eloquent model class name for querying.
     *
     * @var string
     */
    protected string $model;

    /**
     * Fields that can be searched using the 'search' query parameter.
     * Supports dot notation for relationship fields (e.g., 'category.name').
     *
     * @var array<string>
     */
    protected array $searchableFields = [];

    /**
     * Fields that can be filtered using query parameters.
     * Only fields in this array can be used for filtering.
     *
     * @var array<string>
     */
    protected array $filterableFields = [];

    /**
     * Default column to sort by when no sort parameter is provided.
     *
     * @var string
     */
    protected string $defaultSort = 'created_at';

    /**
     * Default sort direction ('asc' or 'desc').
     *
     * @var string
     */
    protected string $defaultDirection = self::SORT_DESC;

    /**
     * Maximum number of items that can be requested per page.
     *
     * @var int
     */
    protected int $maxPerPage = 100;

    /**
     * Default number of items per page when not specified.
     *
     * @var int
     */
    protected int $defaultPerPage = 15;

    /**
     * Controller constructor.
     *
     * Initializes controller with configuration values.
     * Child controllers can override properties to use different defaults.
     */
    public function __construct()
    {
        parent::__construct();

        // Use config values if properties haven't been overridden
        if ($this->maxPerPage === 100) {
            $this->maxPerPage = config('api-controller.pagination.max_per_page', 100);
        }

        if ($this->defaultPerPage === 15) {
            $this->defaultPerPage = config('api-controller.pagination.default_per_page', 15);
        }

        if ($this->defaultSort === 'created_at') {
            $this->defaultSort = config('api-controller.sorting.default_column', 'created_at');
        }

        if ($this->defaultDirection === self::SORT_DESC) {
            $this->defaultDirection = config('api-controller.sorting.default_direction', self::SORT_DESC);
        }
    }

    /**
     * Common index method implementation.
     *
     * Handles GET requests with automatic support for search, filters,
     * sorting, and pagination based on query parameters.
     *
     * @param Request $request The HTTP request
     * @return JsonResponse Standard JSON API response
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleIndexRequest($request);
    }

    /**
     * Override this method to modify the base query.
     *
     * Use this to add global scopes, default where clauses, or other
     * modifications that should apply to all index queries.
     *
     * @param Request $request The HTTP request
     * @return Builder The base query builder
     *
     * @example
     * ```php
     * protected function getBaseIndexQuery(Request $request): Builder
     * {
     *     return $this->model::query()
     *         ->where('status', 'published')
     *         ->whereNotNull('published_at');
     * }
     * ```
     */
    protected function getBaseIndexQuery(Request $request): Builder
    {
        return $this->model::query();
    }

    /**
     * Override this method to define relationships to eager load.
     *
     * Prevents N+1 query problems by eager loading relationships.
     *
     * @return array<string> Array of relationship names to load
     *
     * @example
     * ```php
     * protected function getIndexWith(): array
     * {
     *     return ['category', 'tags', 'author'];
     * }
     * ```
     */
    protected function getIndexWith(): array
    {
        return [];
    }

    /**
     * Override this method to add request-specific conditions.
     *
     * Use this for conditions that depend on the request context,
     * such as user-specific filters or dynamic scopes.
     *
     * @param Builder $query The query builder
     * @param Request $request The HTTP request
     * @return Builder The modified query builder
     *
     * @example
     * ```php
     * protected function applyAdditionalConditions(Builder $query, Request $request): Builder
     * {
     *     if ($request->has('featured')) {
     *         $query->where('featured', true);
     *     }
     *
     *     if ($request->user()->isCustomer()) {
     *         $query->where('visible_to_customers', true);
     *     }
     *
     *     return $query;
     * }
     * ```
     */
    protected function applyAdditionalConditions(Builder $query, Request $request): Builder
    {
        return $query;
    }

    /**
     * Override this method to control who can request all records.
     *
     * By default, fetching all records is denied for security.
     * Override with proper authorization logic if needed.
     *
     * @param Request $request The HTTP request
     * @return bool True if user can fetch all records, false otherwise
     *
     * @example
     * ```php
     * protected function canRequestAllRecords(Request $request): bool
     * {
     *     return $request->user()?->isAdmin() ?? false;
     * }
     * ```
     */
    protected function canRequestAllRecords(Request $request): bool
    {
        // By default, deny fetching all records for security
        // Override in child controllers with proper authorization
        return false;
    }

    /**
     * Common search and filter method for index endpoints.
     *
     * Handles the complete request lifecycle:
     * 1. Validates request parameters
     * 2. Builds and executes query with filters
     * 3. Transforms results
     * 4. Returns standardized JSON response
     *
     * @param Request $request The HTTP request
     * @param Builder|null $query Optional custom base query
     * @return JsonResponse Standard JSON API response
     *
     * @throws ValidationException If request parameters are invalid
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
     * Validate request parameters for security.
     *
     * Ensures all query parameters meet expected formats and constraints.
     * Throws ValidationException if validation fails.
     *
     * @param Request $request The HTTP request
     * @return void
     * @throws ValidationException If validation fails
     */
    protected function validateRequestParameters(Request $request): void
    {
        $rules = [
            self::PARAM_PER_PAGE => 'nullable|integer|min:1|max:' . $this->maxPerPage,
            self::PARAM_PAGE => 'nullable|integer|min:1',
            self::PARAM_SORT_BY => 'nullable|string|max:50',
            self::PARAM_SORT_DIRECTION => 'nullable|in:' . self::SORT_ASC . ',' . self::SORT_DESC,
            self::PARAM_SEARCH => 'nullable|string|max:' . self::MAX_SEARCH_LENGTH,
            self::PARAM_ALL => 'nullable|boolean',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Sanitize search term to prevent SQL injection and unexpected behavior.
     *
     * Escapes special LIKE characters (%, _, \) and limits length.
     * Laravel's parameter binding provides primary SQL injection protection,
     * but we add this layer to prevent unintended LIKE wildcard behavior.
     *
     * @param string $searchTerm The raw search term
     * @return string The sanitized search term
     */
    protected function sanitizeSearchTerm(string $searchTerm): string
    {
        // Escape special LIKE characters
        // Laravel's parameter binding protects against SQL injection,
        // but we escape LIKE wildcards to prevent unexpected behavior
        $searchTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);

        // Limit search term length as an additional security measure
        return mb_substr($searchTerm, 0, self::MAX_SEARCH_LENGTH);
    }

    /**
     * Apply search filters to the query.
     *
     * Searches across all fields defined in $searchableFields property.
     * Supports both direct model fields and relationship fields using dot notation.
     *
     * @param Builder $query The query builder
     * @param Request $request The HTTP request
     * @return Builder The modified query builder
     */
    protected function applySearch(Builder $query, Request $request): Builder
    {
        $searchTerm = $request->get(self::PARAM_SEARCH);

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
     * Apply search on relationship fields.
     *
     * @param Builder $query The query builder
     * @param string $field The relationship field in dot notation (e.g., 'category.name')
     * @param string $searchTerm The sanitized search term
     * @return void
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
     * Validate if a date string is valid.
     *
     * @param string $date The date string to validate
     * @return bool True if valid date, false otherwise
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
     * Apply date range filters.
     *
     * Handles _from and _to suffixes for date range filtering.
     * Validates date format before applying to query.
     *
     * @param Builder $query The query builder
     * @param string $key The filter key (e.g., 'created_at_from')
     * @param mixed $value The filter value
     * @return void
     */
    protected function applyDateFilter(Builder $query, string $key, mixed $value): void
    {
        // Validate date format before using in query
        if (!$this->isValidDate($value)) {
            return;
        }

        // Handle created_at_from and created_at_to
        if (str_ends_with($key, self::SUFFIX_FROM) && $value) {
            $column = str_replace(self::SUFFIX_FROM, '', $key);
            $query->whereDate($column, '>=', $value);
        }
        elseif (str_ends_with($key, self::SUFFIX_TO) && $value) {
            $column = str_replace(self::SUFFIX_TO, '', $key);
            $query->whereDate($column, '<=', $value);
        }
    }

    /**
     * Apply individual filter with support for various operators.
     *
     * Supports:
     * - Exact match
     * - IN queries (arrays)
     * - Range filters (min/max)
     * - Boolean values
     * - Null checks
     * - Date ranges
     *
     * @param Builder $query The query builder
     * @param string $key The filter key
     * @param mixed $value The filter value
     * @return void
     */
    protected function applyFilter(Builder $query, string $key, mixed $value): void
    {
        // Handle date range filters first
        if (str_ends_with($key, self::SUFFIX_FROM) || str_ends_with($key, self::SUFFIX_TO)) {
            $this->applyDateFilter($query, $key, $value);
            return;
        }

        // Handle array values for IN queries
        if (is_array($value)) {
            // Check if it's a range filter (min/max)
            if (isset($value[self::SUFFIX_MIN]) && isset($value[self::SUFFIX_MAX])) {
                $query->whereBetween($key, [$value[self::SUFFIX_MIN], $value[self::SUFFIX_MAX]]);
                return;
            }

            // Support partial range filters
            if (isset($value[self::SUFFIX_MIN])) {
                $query->where($key, '>=', $value[self::SUFFIX_MIN]);
                return;
            }

            if (isset($value[self::SUFFIX_MAX])) {
                $query->where($key, '<=', $value[self::SUFFIX_MAX]);
                return;
            }

            // Default: IN query
            $query->whereIn($key, $value);
            return;
        }

        // Handle boolean filters - support multiple formats
        if (in_array($value, [self::VALUE_TRUE, self::VALUE_FALSE, '1', '0', 1, 0, true, false], true)) {
            $boolValue = in_array($value, [self::VALUE_TRUE, '1', 1, true], true);
            $query->where($key, $boolValue);
            return;
        }

        // Handle null values - separate from empty string
        if ($value === self::VALUE_NULL) {
            $query->whereNull($key);
            return;
        }

        if ($value === self::VALUE_NOT_NULL) {
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
     * Check if a field is allowed for filtering.
     *
     * Security check to prevent unauthorized field access.
     * If no filterable fields defined, denies all for security.
     *
     * @param string $field The field name to check
     * @return bool True if field is filterable, false otherwise
     */
    protected function isFilterable(string $field): bool
    {
        // If no filterable fields defined, deny all for security
        if (empty($this->filterableFields)) {
            return false;
        }

        // Remove suffixes like _from, _to, [min], [max] for validation
        $baseField = preg_replace('/' . self::SUFFIX_FROM . '|' . self::SUFFIX_TO . '$/', '', $field);
        $baseField = preg_replace('/\[.*\]/', '', $baseField);

        return in_array($baseField, $this->filterableFields, true);
    }

    /**
     * Apply all filters from request to query.
     *
     * Only applies filters for fields in the $filterableFields whitelist.
     *
     * @param Builder $query The query builder
     * @param Request $request The HTTP request
     * @return Builder The modified query builder
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        $filters = $request->except([
            self::PARAM_SEARCH,
            self::PARAM_SORT_BY,
            self::PARAM_SORT_DIRECTION,
            self::PARAM_PER_PAGE,
            self::PARAM_PAGE,
            self::PARAM_ALL
        ]);

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
     * Validate if a column is allowed for sorting.
     *
     * Uses whitelist approach for security.
     * Only allows sorting by searchable fields, filterable fields,
     * and standard timestamp columns.
     *
     * @param string $column The column name to validate
     * @return bool True if column is allowed for sorting, false otherwise
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
     * Apply sorting to query.
     *
     * Validates sort column against whitelist to prevent SQL injection.
     * Falls back to default sort if invalid column provided.
     *
     * @param Builder $query The query builder
     * @param Request $request The HTTP request
     * @return Builder The modified query builder
     */
    protected function applySorting(Builder $query, Request $request): Builder
    {
        $sortBy = $request->get(self::PARAM_SORT_BY, $this->defaultSort);
        $sortDirection = $request->get(self::PARAM_SORT_DIRECTION, $this->defaultDirection);

        // Validate sort direction
        $sortDirection = in_array(strtolower($sortDirection), [self::SORT_ASC, self::SORT_DESC])
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
     * Paginate query results.
     *
     * Always paginates by default for security (prevents DoS attacks).
     * Requires explicit authorization to fetch all records.
     *
     * @param Builder $query The query builder
     * @param Request $request The HTTP request
     * @return LengthAwarePaginator|Collection The paginated or complete results
     */
    protected function paginateResults(Builder $query, Request $request): LengthAwarePaginator|Collection
    {
        // Check for explicit 'all' request with authorization
        if ($request->boolean(self::PARAM_ALL, false)) {
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
        $page = $request->get(self::PARAM_PAGE, 1);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get per_page value from request with validation.
     *
     * Ensures per_page doesn't exceed maximum allowed.
     *
     * @param Request $request The HTTP request
     * @return int The validated per_page value
     */
    protected function getPerPage(Request $request): int
    {
        $perPage = $request->get(self::PARAM_PER_PAGE, $this->defaultPerPage);

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
     * Transform data before returning.
     *
     * Override this method in child controllers to transform results
     * (e.g., using API Resources or custom formatters).
     *
     * @param LengthAwarePaginator|Collection $results The query results
     * @param Request $request The HTTP request
     * @return array The transformed data
     *
     * @example
     * ```php
     * protected function transformIndexData($results, Request $request): array
     * {
     *     $items = $results instanceof LengthAwarePaginator
     *         ? $results->items()
     *         : $results->toArray();
     *
     *     return ProductResource::collection($items)->resolve();
     * }
     * ```
     */
    protected function transformIndexData(LengthAwarePaginator|Collection $results, Request $request): array
    {
        return $results instanceof LengthAwarePaginator ? $results->items() : $results->toArray();
    }

    /**
     * Get success message for index response.
     *
     * Override this method to customize the response message.
     *
     * @param LengthAwarePaginator|Collection $results The query results
     * @return string The success message
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
     * Get metadata for index response.
     *
     * Includes pagination info and applied filters.
     *
     * @param LengthAwarePaginator|Collection $results The query results
     * @param Request $request The HTTP request
     * @return array The metadata array
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
                    self::PARAM_SEARCH => $request->get(self::PARAM_SEARCH),
                    self::PARAM_SORT_BY => $request->get(self::PARAM_SORT_BY, $this->defaultSort),
                    self::PARAM_SORT_DIRECTION => $request->get(self::PARAM_SORT_DIRECTION, $this->defaultDirection),
                    'applied_filters' => $request->except([
                        self::PARAM_SEARCH,
                        self::PARAM_SORT_BY,
                        self::PARAM_SORT_DIRECTION,
                        self::PARAM_PER_PAGE,
                        self::PARAM_PAGE,
                        self::PARAM_ALL
                    ]),
                ]
            ];
        }

        // For non-paginated results (all data) - no pagination metadata
        return [
            'filters' => [
                self::PARAM_SEARCH => $request->get(self::PARAM_SEARCH),
                self::PARAM_SORT_BY => $request->get(self::PARAM_SORT_BY, $this->defaultSort),
                self::PARAM_SORT_DIRECTION => $request->get(self::PARAM_SORT_DIRECTION, $this->defaultDirection),
                'applied_filters' => $request->except([
                    self::PARAM_SEARCH,
                    self::PARAM_SORT_BY,
                    self::PARAM_SORT_DIRECTION,
                    self::PARAM_PER_PAGE,
                    self::PARAM_PAGE,
                    self::PARAM_ALL
                ]),
            ]
        ];
    }

    // ========================================
    // Response Helper Methods
    // ========================================

    /**
     * Return a success JSON response.
     *
     * @param string $message Success message
     * @param mixed $data Response data
     * @param array|null $meta Additional metadata
     * @param int $statusCode HTTP status code (default: 200)
     * @return JsonResponse
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
     * Return an error JSON response.
     *
     * @param string $message Error message
     * @param array|null $errors Validation or detailed errors
     * @param int $statusCode HTTP status code (default: 400)
     * @param array|null $meta Additional metadata
     * @return JsonResponse
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
     * Return a 201 Created response.
     *
     * @param string $message Success message
     * @param mixed $data Created resource data
     * @return JsonResponse
     */
    protected function created(
        string $message = 'Resource created successfully',
        mixed $data = null
    ): JsonResponse {
        return $this->success($message, $data, null, 201);
    }

    /**
     * Return a 204 No Content response.
     *
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function noContent(
        string $message = 'No content'
    ): JsonResponse {
        return $this->success($message, null, null, 204);
    }

    /**
     * Return a paginated JSON response.
     *
     * @param mixed $paginator Laravel paginator instance
     * @param string $message Success message
     * @param array|null $additionalMeta Additional metadata
     * @return JsonResponse
     */
    protected function paginated(
        mixed $paginator,
        string $message = 'Data retrieved successfully',
        ?array $additionalMeta = null
    ): JsonResponse {
        return paginated_response($paginator, $message, $additionalMeta);
    }

    /**
     * Return a 422 Validation Error response.
     *
     * @param string $message Error message
     * @param array|null $errors Validation errors
     * @return JsonResponse
     */
    protected function validationError(
        string $message = 'Validation failed',
        ?array $errors = null
    ): JsonResponse {
        return $this->error($message, $errors, 422);
    }

    /**
     * Return a 404 Not Found response.
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->error($message, null, 404);
    }

    /**
     * Return a 401 Unauthorized response.
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function unauthorized(
        string $message = 'Unauthorized access'
    ): JsonResponse {
        return $this->error($message, null, 401);
    }

    /**
     * Return a 403 Forbidden response.
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function forbidden(
        string $message = 'Access forbidden'
    ): JsonResponse {
        return $this->error($message, null, 403);
    }

    // ========================================
    // Business Logic Helper Methods (DEPRECATED)
    // ========================================
    // These methods are deprecated. Use the HasPermissions and
    // HasBranchFiltering traits instead for better code organization.
    // ========================================

    /**
     * Check if user has permission.
     *
     * @deprecated Use HasPermissions trait instead
     * @see \Masum\ApiController\Traits\HasPermissions
     *
     * @param string $action Action to check
     * @param string $resource Resource to check
     * @return bool
     */
    protected function hasPermission(string $action, string $resource): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user && $user->hasPermission($action, $resource);
    }

    /**
     * Check if user can access specific branch.
     *
     * @deprecated Use HasBranchFiltering trait instead
     * @see \Masum\ApiController\Traits\HasBranchFiltering
     *
     * @param int $branchId Branch ID to check
     * @return bool
     */
    protected function canAccessBranch(int $branchId): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user && $user->canAccessBranch($branchId);
    }

    /**
     * Get authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function getUser(): mixed
    {
        return \Illuminate\Support\Facades\Auth::user();
    }

    /**
     * Get user's branch ID.
     *
     * @deprecated Use HasBranchFiltering trait instead
     * @see \Masum\ApiController\Traits\HasBranchFiltering
     *
     * @return int|null
     */
    protected function getUserBranchId(): ?int
    {
        $user = $this->getUser();
        return $user ? $user->employee->branch_id ?? null : null;
    }

    /**
     * Apply branch filter to query.
     *
     * @deprecated Use HasBranchFiltering trait instead
     * @see \Masum\ApiController\Traits\HasBranchFiltering
     *
     * @param Builder $query Query builder
     * @param string $branchColumn Branch column name
     * @return Builder
     */
    protected function applyBranchFilter(Builder $query, string $branchColumn = 'branch_id'): Builder
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
