<?php
namespace Basttyy\FxDataServer;

use Closure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class WebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $tickers;
    protected $routes;
    protected LoopInterface $loop;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->tickers = array();
        $this->routes = array();
    }

    // this can be useful for implementing httpserver
    public function __invoke(RequestInterface $request, ResponseInterface $response)
    {
        echo "got here".PHP_EOL;
        $path = $request->getUri()->getPath();
        if ($path === '/hello') {
            $response->getBody()->write('Hello, World');
            return $response;
        } else {
            $response->getBody()->write('Is Not Found');
            return $response->withStatus(404);
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection in $clients
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Decode the JSON message
        $data = json_decode($msg, true);

        // Check if the message is a valid JSON object
        if (is_array($data)) {
            // Check if the message has a "route" property
            if (isset($data['route'])) {
                // Check if the route exists
                $route = $data['route'];
                if (isset($this->routes[$route])) {
                    // Call the route handler
                    $handler = $this->routes[$route];
                    if (is_callable($handler)) {
                        $handler($from, $data);
                    } else {
                    }
                } else {
                    echo "Unknown route: {$route}\n";
                }
            } else {
                echo "Missing route in message: {$msg}\n";
            }
        } else {
            echo "Invalid JSON message: {$msg}\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Remove the connection from $clients
        $this->loop->cancelTimer($this->getTicker($conn));
        $this->clients->detach($conn);
        echo "Connection closed! ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function getTicker(ConnectionInterface $key): TimerInterface|null
    {
        $data = (array)$this->clients->offsetGet($key);
        return $data['ticker'];
    }

    public function setTicker(ConnectionInterface $key, TimerInterface|null $timer)
    {
        $data = (array)$this->clients->offsetGet($key);
        $data['ticker'] = $timer;
        $this->clients->offsetSet($key, $data);
    }

    public function setLoop(LoopInterface $loop)
    {
        echo "set loop called".PHP_EOL;
        $this->loop = $loop;
    }

    public function getLoop(): LoopInterface
    {
        return $this->loop;
    }

    //sample schedulling of events
    public function ticktack()
    {
        echo "ticktack called".PHP_EOL;
        $this->loop->addPeriodicTimer(0.1, function () {
            echo "we are logging something".PHP_EOL;
        });
    }

    public function addRoute(string $route, callable $handler) {
        $this->routes[$route] = $handler;
    }
}