<?php
require_once __DIR__.'/router.php';
require_once __DIR__.'/src/TickController.php';
require_once __DIR__.'/src/MinuteController.php';
// Copyright Monwoo 2017, service@monwoo.com
// Enabling CORS in bultin dev to test locally with multiples servers
// used to replace lack of .htaccess support inside php builting webserver.
// call with :
// php -S localhost:20334 -t . server.php  php -S 192.168.0.127:20334 -t . server.php
$CORS_ORIGIN_ALLOWED = "http://127.0.0.1:5173";  // or '*' for all
// header("Access-Control-Allow-Origin: $CORS_ORIGIN_ALLOWED");

consoleLog(0, "request came to server");

function applyCorsHeaders($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
}

if (preg_match('/\.(?:png|jpg|jpeg|gif)$/', $_SERVER["REQUEST_URI"])) {
    consoleLog('info', "Transparent routing for : " . $_SERVER["REQUEST_URI"]);
    http_response_code(400);
    header("Content-type: application/json");
    echo json_encode(["message" => "Bad Request data"]);
} else if (preg_match('/^.*$/i', $_SERVER["REQUEST_URI"])) {
    applyCorsHeaders($CORS_ORIGIN_ALLOWED);

    //register controllers
    get('/download/ticker/$ticker/from/$from/nums/$nums/faster/$faster', $downloadTickData);
    get('/candles/ticker/$ticker/from/$fro/nums/$num/timeframe/$timefram', $getTimeframeCandles);
    get('/download/min/ticker/$ticker/from/$from/incr/$incr/nums/$nums', $downloadMinuteData);
    get('/tickers/query/$query', $searchTicker);
    get('/tickers/query', $searchTicker);
} else {
    consoleLog('info', "Not catched by routing, Transparent serving for : "
    . $_SERVER["REQUEST_URI"]);
    return false; // Let php bultin server serve
}