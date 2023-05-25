<?php

return
[
    "base_url" => "http://fx-data-server.test",
    "providers" => [
        "Google" => [
            "enabled" => true,
            "keys" => ["id" => "", "secret" => ""]
        ],
        "Facebook" => [
            "enabled" => true,
            "keys" => ["id" => "", "secret" => ""],
            "trustForwarded" => false
        ],
        "Twitter" => [
            "enabled" => true,
            "keys" => ["id" => "", "secret" => ""]
        ]
    ],
    "debug_mode" => true,
    "debug_file" => "storage/logs/hybridauth_log.txt",
];