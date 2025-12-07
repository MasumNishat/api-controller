<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The API version to include in responses. This will be included in the
    | response if 'include_version' is set to true.
    |
    */

    'version' => env('API_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Include Version in Responses
    |--------------------------------------------------------------------------
    |
    | Whether to include the API version in response payloads.
    |
    */

    'include_version' => env('API_INCLUDE_VERSION', false),

    /*
    |--------------------------------------------------------------------------
    | Sanitize SQL Errors
    |--------------------------------------------------------------------------
    |
    | When enabled, any error message containing "SQLSTATE" will be replaced
    | with a generic message to avoid exposing database details.
    |
    */

    'sanitize_sql_errors' => env('API_SANITIZE_SQL_ERRORS', true),

    /*
    |--------------------------------------------------------------------------
    | Default Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for API controllers.
    |
    */

    'pagination' => [
        'default_per_page' => env('API_DEFAULT_PER_PAGE', 15),
        'max_per_page' => env('API_MAX_PER_PAGE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Sorting
    |--------------------------------------------------------------------------
    |
    | Default sorting settings for API controllers.
    |
    */

    'sorting' => [
        'default_column' => env('API_DEFAULT_SORT', 'created_at'),
        'default_direction' => env('API_DEFAULT_SORT_DIRECTION', 'desc'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Format
    |--------------------------------------------------------------------------
    |
    | Configure the response format for API responses.
    |
    */

    'response' => [
        'include_timestamp' => true,
        'timestamp_format' => 'iso8601', // iso8601, unix, custom
        'custom_timestamp_format' => 'Y-m-d H:i:s',
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Formatter
    |--------------------------------------------------------------------------
    |
    | The formatter class to use for structuring API responses.
    | Available options:
    |   - \Masum\QueryController\Formatters\DefaultFormatter::class (default)
    |   - \Masum\QueryController\Formatters\JSendFormatter::class
    |   - \Masum\QueryController\Formatters\JsonApiFormatter::class
    |   - Your custom formatter implementing ResponseFormatterInterface
    |
    */

    'formatter' => env(
        'API_RESPONSE_FORMATTER',
        \Masum\QueryController\Formatters\DefaultFormatter::class
    ),

    /*
    |--------------------------------------------------------------------------
    | JSend Formatter Options
    |--------------------------------------------------------------------------
    |
    | Configuration specific to JSend formatter.
    |
    */

    'jsend' => [
        'include_message' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON:API Formatter Options
    |--------------------------------------------------------------------------
    |
    | Configuration specific to JSON:API formatter.
    |
    */

    'jsonapi' => [
        'include_message' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | View Support
    |--------------------------------------------------------------------------
    |
    | Enable view rendering support for controllers that want to return
    | HTML views instead of JSON responses. Useful for Inertia.js, Livewire,
    | or traditional Blade views.
    |
    */

    'views' => [
        'enabled' => env('API_VIEWS_ENABLED', false),

        // Default view path prefix for automatic view resolution
        'path_prefix' => 'api',

        // Auto-detect Inertia requests
        'inertia_enabled' => true,

        // Auto-detect Livewire components
        'livewire_enabled' => true,
    ],

];