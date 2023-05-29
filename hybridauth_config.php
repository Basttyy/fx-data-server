<?php

return
[
    "callback" => env('OAUTH_CALLBACK'),
    "providers" => [
        "Google" => [
            "enabled" => false,
            "keys" => ["id" => env('GOOGLE_APP_ID'), "secret" => env('GOOGLE_APP_SECRET')]
        ],
        "Facebook" => [
            "enabled" => true,
            "keys" => ["id" => env('FB_APP_ID'), "secret" => env('FB_APP_SECRET')],
            "trustForwarded" => false
        ],
        "Twitter" => [
            "enabled" => false,
            "keys" => ["id" => env('TWITTER_APP_ID'), "secret" => env('TWITTER_APP_SECRET')]
        ]
    ],
    "debug_mode" => true,
    "debug_file" => "storage/logs/hybridauth_log.txt",
];