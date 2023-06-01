<?php

require_once __DIR__."/vendor/autoload.php";

use Dotenv\Dotenv;
// Copyright Monwoo 2017, service@monwoo.com
// Enabling CORS in bultin dev to test locally with multiples servers
// used to replace lack of .htaccess support inside php builting webserver.
// call with :
// php -S localhost:20334 -t . server.php  php -S 192.168.0.127:20334 -t . server.php
// header("Access-Control-Allow-Origin: $CORS_ORIGIN_ALLOWED");

function applyCorsHeaders($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
}

$dotenv = strtolower(PHP_OS_FAMILY) === 'windows' ? Dotenv::createImmutable(__DIR__."\\") : Dotenv::createImmutable(__DIR__.'/');
$dotenv->load();
$dotenv->required(['APP_KEY', 'APP_ENV', 'DB_USER', 'DB_HOST', 'DB_NAME', 'ADMIN_APP_URI', 'USER_APP_URI', 'SERVER_APP_URI', 'FINGERPRINT_MAX_AGE', 'SECRET_TOKEN', 'SHA_TYPE'])->notEmpty();

// if (preg_match('/\.(?:png|jpg|jpeg|gif)$/', $_SERVER["REQUEST_URI"])) {
//     consoleLog('info', "Transparent routing for : " . $_SERVER["REQUEST_URI"]);
//     http_response_code(400);
//     header("Content-type: application/json");
//     echo json_encode(["message" => "Bad Request data"]);
if (preg_match('/^.*$/i', $_SERVER["REQUEST_URI"])) {
    $http_origin = $_SERVER['HTTP_ORIGIN'];
    if ($http_origin === $_ENV['USER_APP_URI'] || $http_origin === $_ENV['ADMIN_APP_URI'] || $http_origin === $_ENV['SERVER_APP_URI']) {
        applyCorsHeaders($http_origin);
    }

    //register controllers
    require_once __DIR__.'/src/libs/routes.php';
    consoleLog(0, "request came to server");
} else {
    consoleLog('info', "Not catched by routing, Transparent serving for : "
    . $_SERVER["REQUEST_URI"]);
    return false; // Let php bultin server serve
}