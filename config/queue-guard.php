<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for the automatic listener. Even when disabled you can still
    | call QueueGuard::inspect($job) and the assertion helpers directly.
    |
    */
    'enabled' => env('QUEUE_GUARD_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    |
    | The automatic listener only runs in these environments. Production is
    | intentionally excluded: inspection adds a serialize() per dispatch.
    |
    */
    'environments' => ['local', 'testing'],

    /*
    |--------------------------------------------------------------------------
    | Mode
    |--------------------------------------------------------------------------
    |
    | "warn"  - log findings on the "queue-guard" channel (or the default log).
    | "throw" - throw JobFailedGuardException when there is an error-level
    |           finding (warnings are still logged).
    |
    */
    'mode' => env('QUEUE_GUARD_MODE', 'warn'),

    /*
    |--------------------------------------------------------------------------
    | Payload size thresholds (bytes)
    |--------------------------------------------------------------------------
    */
    'payload' => [
        'warn_bytes' => 65_536,    // 64 KB
        'error_bytes' => 262_144,  // 256 KB — Amazon SQS hard limit
    ],

    /*
    |--------------------------------------------------------------------------
    | Detectors
    |--------------------------------------------------------------------------
    |
    | Toggle individual checks. Removing one here disables it entirely.
    |
    */
    'detectors' => [
        'payload_size' => true,
        'unserializable' => true,
        'eager_loaded_relations' => true,
        'binary_string' => true,
        'sensitive_data' => true,
        'transaction_safety' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive property names
    |--------------------------------------------------------------------------
    |
    | Case-insensitive substrings matched against job property names.
    |
    */
    'sensitive_names' => [
        'password', 'passwd', 'secret', 'token', 'api_key', 'apikey',
        'authorization', 'auth_token', 'access_key', 'private_key',
        'card_number', 'cardnumber', 'cvv', 'cvc', 'ssn',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored jobs
    |--------------------------------------------------------------------------
    |
    | Fully-qualified job class names the automatic listener should skip.
    |
    */
    'ignore' => [],

    /*
    |--------------------------------------------------------------------------
    | Graph traversal limits
    |--------------------------------------------------------------------------
    |
    | Safety bounds for walking a job's property graph.
    |
    */
    'limits' => [
        'max_depth' => 6,
        'max_nodes' => 2_000,
    ],

];
