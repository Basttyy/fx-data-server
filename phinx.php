<?php
require_once __DIR__."\/vendor\/autoload.php";
require_once __DIR__."\/src\/helpers.php";
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$dotenv->required(['DB_ADAPTER', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PORT', 'DB_CHARSET'])->notEmpty();

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'production' => [
            'adapter' => env('DB_ADAPTER'),
            'host' => env('DB_HOST'),
            'name' => env('DB_NAME'),
            'user' => env('DB_USER').'_db',
            'pass' => env('DB_PASS'),
            'port' => env('DB_PORT'),
            'charset' => env('DB_CHARSET'),
        ],
        'development' => [
            'adapter' => env('DB_ADAPTER'),
            'host' => env('DB_HOST'),
            'name' => env('DB_NAME'),
            'user' => env('DB_USER'),
            'pass' => env('DB_PASS'),
            'port' => env('DB_PORT'),
            'charset' => env('DB_CHARSET'),
        ],
        'testing' => [
            'adapter' => env('DB_ADAPTER'),
            'host' => env('DB_HOST'),
            'name' => env('DB_NAME'),
            'user' => env('DB_USER'),
            'pass' => env('DB_PASS'),
            'port' => env('DB_PORT'),
            'charset' => env('DB_CHARSET'),
        ]
    ],
    'version_order' => 'creation'
];
