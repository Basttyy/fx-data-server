<?php

use Basttyy\FxDataServer\StreamTick;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Basttyy\FxDataServer\WebSocketServer;
use React\EventLoop\Loop;

require __DIR__ . '/vendor/autoload.php';

$handler = new WebSocketServer();
$loop = Loop::get();

$app = new Ratchet\App('csvtick-streamer.test', 8089, '192.168.0.127', $loop);
$app->route('/', $handler, array('*'));

//init server
// $server = IoServer::factory(
//     new HttpServer(
//         new WsServer(
//             $handler
//         )
//     ),
//     5174 // replace with the port number you want to use
// );



//event handlers
$stream_tick = new StreamTick($loop, $handler);

$handler->setLoop($loop);
// $handler->ticktack();

$handler->addRoute('tick/stream', $stream_tick);

$app->run();
?>
