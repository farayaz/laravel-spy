<?php

return [
    /*
    * Enable or disable the spy functionality.
    */
    'enabled' => env('SPY_ENABLED', true),

    /*
    * Enable or disable Guzzle injection.
    */
    'guzzle' => [
        'enabled' => env('SPY_GUZZLE_ENABLED', true),
    ],

    /*
    * The database table name for storing HTTP logs.
    */
    'table_name' => env('SPY_TABLE_NAME', 'http_logs'),

    /*
    * The database connection to use.
    */
    'db_connection' => env('SPY_DB_CONNECTION', null),

    /*
    * URLs to exclude from logging, as a comma-separated list.
    */
    'exclude_urls' => spy_csv('SPY_EXCLUDE_URLS'),

    /*
    * Request fields to obfuscate in logs, as a comma-separated list.
    */
    // Example: '*:password,token|domain1.com:api_key|domain2.com:secret'
    'obfuscates' => spy_rule_map('SPY_OBFUSCATES', '*:password,token'),

    /*
    * A mask string used to obfuscate fields in the logs.
    */
    'obfuscation_mask' => env('SPY_OBFUSCATION_MASK', '🫣'),

    /*
    * Number of days to retain logs before cleaning.
    */
    'clean_days' => (int) env('SPY_CLEAN_DAYS', 30),

    /*
    * Content types to exclude from request body logging.
    * Can be set via the SPY_REQUEST_BODY_EXCLUDE_CONTENT_TYPES env variable (comma-separated).
    */
    // Example: 'video/,audio/,application/pdf,application/zip,application/x-zip-compressed,application/octet-stream,multipart/form-data'
    'request_body_exclude_content_types' => spy_csv('SPY_REQUEST_BODY_EXCLUDE_CONTENT_TYPES', ''),

    /*
    * Content types to exclude from response body logging.
    * Can be set via the SPY_RESPONSE_BODY_EXCLUDE_CONTENT_TYPES env variable (comma-separated).
    */
    // Example: 'video/,audio/,application/pdf,application/zip,application/x-zip-compressed,application/octet-stream'
    'response_body_exclude_content_types' => spy_csv('SPY_RESPONSE_BODY_EXCLUDE_CONTENT_TYPES', ''),

    /*
    * Maximum length (in characters) for field values in logs.
    * Values exceeding this limit will be truncated.
    */
    'field_max_length' => (int) env('SPY_FIELD_MAX_LENGTH', 10000),

    /*
    * Maximum number of rows to log for array/collection fields.
    * Arrays exceeding this limit will be truncated.
    */
    'field_max_rows' => (int) env('SPY_FIELD_MAX_ROWS', 10000),

    /*
    * Dashboard settings.
    */
    'dashboard' => [
        // Enable or disable the dashboard.
        'enabled' => env('SPY_DASHBOARD_ENABLED', false),

        // Route prefix for the dashboard.
        'prefix' => env('SPY_DASHBOARD_PREFIX', 'spy'),

        // Middleware(s) to apply to the dashboard routes, as a comma-separated list.
        'middleware' => spy_csv('SPY_DASHBOARD_MIDDLEWARE', 'auth'),
    ],
];
