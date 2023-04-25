<?php

require 'vendor/autoload.php';

$websocket_url = 'wss://hdf-ds-live-v1.dukascopy.com:8443/hdf';
$username = $_ENV['dukascopy-username'];
$password = $_ENV['dukascopy-password'];
$symbols = [
    'EUR/USD', 'GBP/USD', 'XAUUSD',
];
//Todo convert connection to use this method
// \Ratchet\Client\connect($websocket_url)->then(function ($conn) {
//     $conn->on('message', function)
// })

// Create a websocket connection to the HDF API
$factory = new Factory();
$websocket = $factory->createClient($websocket_url);

//Authenticate with credentials
$auth_message = json_encode([
    'type' => 'AUTH',
    'userName' => $username,
    'password' => $password
]);
$websocket->send($auth_message);

// Subscribe to the tick data feed
$subscription_msg = json_encode([
    'type' => 'SUBSCRIBE_TICK_DATA',
    'symbols' => $symbols,
    'tickTypes' => ['BID', 'ASK'],
    'level' => 'TEN',
    'subscribeMode' => 'Live',
]);
$websocket->send($subscription_msg);

//Recieve and process tick data
//Todo this should be reimplemented according to the ws lib we later use
//Todo this tick data should be broadcated over WAMP
$websocket->on('message', function($message) {
    $tick_data = json_decode($message, true);
    if ($tick_data['type'] == 'PRICE') {
        $symbol = $tick_data['symbol'];
        $bid = $tick_data['bid'];
        $ask = $tick_data['ask'];
        $timestamp = $tick_data['timestamp'];

        //find way to store tick data or send over WAMP pub/sub

        return;
    }
    // do something else if tick_data type is not PRICE
});

$websocket->run();