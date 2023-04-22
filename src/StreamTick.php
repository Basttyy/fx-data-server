<?php
namespace Basttyy\FxDataServer;

use DateTime;
use DateTimeImmutable;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\Message;
use React\EventLoop\LoopInterface;

class StreamTick
{
    private $loop;
    private $server;

    public function __construct(LoopInterface $loop, WebSocketServer $server)
    {
        $this->loop = $loop;
        $this->server = $server;
    }

    public function __invoke(ConnectionInterface $conn, array $data)
    {
        // Handle the "myroute" message here
        echo "Received message with data: " . print_r($data, true) . "\n";

        if ($data['action'] === 'subscribe') {
            $this->subscribe($conn, $data);
        } else if ($data['action'] === 'unsubscribe') {
            $this->unsubscribe($conn, $data);
        }
    }

    private function unsubscribe (ConnectionInterface $conn, array $data)
    {
        if (!$ticker = $this->server->getTicker($conn)) {
        }
        $this->loop->cancelTimer($ticker);
        $this->server->setTicker($conn, $ticker);
    }

    private function subscribe (ConnectionInterface $conn, array $data)
    {
        $interval = $data['interval'] >= 0 || $data['interval'] <= 5.1 ? 5 : $data['interval'];
        $line = 0;
        $total = 0;
        $lasttime = 0;
        $datetime = new DateTimeImmutable();

        $ticker = $this->loop->addPeriodicTimer($interval, function () use ($conn, $data, &$line, &$total, &$lasttime, &$datetime) {
            $time = $data['time'];
            $lasttime = $lasttime === 0 ? $data['lasttime'] : $lasttime;
            $datetime = $datetime->setTimestamp($lasttime);
            $file_path = "download/{$data['sym']}/{$datetime->format('YYYY/MM')}/{$datetime->format('YYYY/MM/DD')}--{$datetime->format('HH')}h_ticks.csv";

            if (!$csv_string = file_get_contents($file_path)) {
                echo "unable to get file: ".$file_path.PHP_EOL;
            }

            // Split the string into an array of lines
            $lines = explode("\n", $csv_string);
            while (1) {
                $csv_data = str_getcsv($lines[$line]);
                $datetime = $datetime->createFromFormat("YYYY.MM.DD HH:mm:ss.SSS", $csv_data[0]);
                $unixtime = $datetime->getTimestamp();

                if ($unixtime <= $lasttime) {
                    continue;
                }
                $lasttime = $unixtime;
                break;
            }

            $data['ev'] = 'tick';
            $data['sym'] = $data['sym'];
            $conn->send(json_encode($data));
        });

        $this->server->setTicker($conn, $ticker);
    }
}