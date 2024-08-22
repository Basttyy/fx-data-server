<?php

return [
    /*
    |--------------------------------------------------------------------------
    | storage settings
    |--------------------------------------------------------------------------
    |
    | The env key for pattern and storage path with a default value
    |
    */
    'local' => storage_path('local'),
    's3' => [
        'bucket' => 'your-s3-bucket-name',
        'region' => 'your-s3-region',
        'key' => 'your-s3-access-key',
        'secret' => 'your-s3-secret-key',
    ]
];