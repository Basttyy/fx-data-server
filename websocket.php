<?php

use Basttyy\FxDataServer\StreamTick;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Basttyy\FxDataServer\WebSocketServer;

require __DIR__ . '/vendor/autoload.php';

$handler = new WebSocketServer();

//init server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $handler
        )
    ),
    5174 // replace with the port number you want to use
);

//event handlers
$stream_tick = new StreamTick($server->loop, $handler);

$handler->setLoop($server->loop);
$handler->ticktack();

$handler->addRoute('tick/stream', $stream_tick);

$server->run();
?>
