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

];