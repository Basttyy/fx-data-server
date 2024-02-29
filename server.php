<?php

$log_path = $_SERVER["DOCUMENT_ROOT"]."/storage/logs/php_error.log";
ini_set('error_log', $log_path);
require_once __DIR__."/vendor/autoload.php";

use Dotenv\Dotenv;
// Copyright Monwoo 2017, service@monwoo.com
// Enabling CORS in bultin dev to test locally with multiples servers
// used to replace lack of .htaccess support inside php builting webserver.
// call with :
// php -S localhost:20334 -t . server.php  php -S 192.168.0.127:5533 -t . server.php
// header("Access-Control-Allow-Origin: $CORS_ORIGIN_ALLOWED");

// if (!isset($_SERVER['HTTP_AUTHORIZATION']) && isset(apache_request_headers()['Authorization'])) {
//     $_SERVER['HTTP_AUTHORIZATION'] = apache_request_headers()['Authorization'];
// }

function applyCorsHeaders($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization');
}

$customMappings = [
    'js' => 'text/javascript', //'application/javascript',
    'css' => 'text/css',
    'woff2' => 'font/woff2'
];

$dotenv = strtolower(PHP_OS_FAMILY) === 'windows' ? Dotenv::createImmutable(__DIR__."\\") : Dotenv::createImmutable(__DIR__."/");
$dotenv->load();
$dotenv->required(['APP_KEY', 'APP_ENV', 'DB_USER', 'DB_HOST', 'DB_NAME', 'ADMIN_APP_URI', 'USER_APP_URI', 'SERVER_APP_URI', 'FINGERPRINT_MAX_AGE', 'SECRET_TOKEN', 'SHA_TYPE', 'CONTENT_LENGTH_MIN'])->notEmpty();

$server = strtolower($_SERVER['SERVER_SOFTWARE']) ?? "";


if ($_ENV['APP_ENV'] === "local" && (!in_array($server, ['apache', 'nginx', 'litespeed']))) {
    // file_put_contents("php://stdout", "[" . 0 . "] " . "server software is:  $server" . "\n");
    $http_origin = $_SERVER["HTTP_ORIGIN"] ?? "";
    if ($http_origin === $_ENV['USER_APP_URI'] || $http_origin === $_ENV['ADMIN_APP_URI'] || $http_origin === $_ENV['SERVER_APP_URI']) {
        // file_put_contents("php://stdout", "[" . 0 . "] " . "cors header applied: $http_origin" . "\n");
        applyCorsHeaders($http_origin);
    }

    if (preg_match('/\.(?:js|css|svg|ico|woff2|ttf|webp|pdf|png|jpg|json|jpeg|gif)$/', $_SERVER["REQUEST_URI"])) {
        $path = $_SERVER['DOCUMENT_ROOT'].$_SERVER["REQUEST_URI"];
        if (file_exists($path)) {
            $mime = mime_content_type($path);
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (array_key_exists($ext, $customMappings)) {
                $mime = $customMappings[$ext];
            }
            header("Content-Type: $mime", true, 200);
            echo file_get_contents($path);
            return true;
        }

        header("Content-type: application/json", true, 404);
        echo json_encode(["message" => "File Not Found"]);

        return true;
    }
}

if (preg_match('/^.*$/i', $_SERVER["REQUEST_URI"])) {
    //register controllers
    require_once __DIR__.'/src/libs/routes.php';
} else {
    return false; // Let php bultin server serve
}