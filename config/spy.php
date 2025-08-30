<?php

if (! function_exists('spy_parse_env_array')) {
    function spy_parse_env_array($key, $default = '')
    {
        return array_filter(array_map('trim', explode(',', env($key, $default))));
    }
}

return [
    /*
    * Enable or disable the spy functionality.
    */
    'enabled' => env('SPY_ENABLED', true),

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
    'exclude_urls' => spy_parse_env_array('SPY_EXCLUDE_URLS'),

    /*
    * Request fields to obfuscate in logs, as a comma-separated list.
    */
    'obfuscates' => spy_parse_env_array('SPY_OBFUSCATES', 'password,token'),

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
    'request_body_exclude_content_types' => spy_parse_env_array('SPY_REQUEST_BODY_EXCLUDE_CONTENT_TYPES', ''),

    /*
    * Content types to exclude from response body logging.
    * Can be set via the SPY_RESPONSE_BODY_EXCLUDE_CONTENT_TYPES env variable (comma-separated).
    */
    // Example: 'video/,audio/,application/pdf,application/zip,application/x-zip-compressed,application/octet-stream'
    'response_body_exclude_content_types' => spy_parse_env_array('SPY_RESPONSE_BODY_EXCLUDE_CONTENT_TYPES', ''),
];
