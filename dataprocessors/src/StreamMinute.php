<?php
namespace Basttyy\FxDataServer;

use Carbon\Carbon;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\Message;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class StreamMinute
{
    private $loop;
    private $server;

    private $interval = 0;
    private $line_pointer = 0;
    private $_count = 0;
    private $firsttime = 0;
    private $minutes = 0;
    private $timestamp = 0;
    private $timeframe = 0;
    private $open = 0;
    private $close = 0;
    private $high = 0;
    private $low = 0;
    private Carbon $datetime;
    private $lasttime = 0;
    private $lines = array();
    private $done = true;
    private $timestp = 0;

    public function __construct(LoopInterface $loop, WebSocketServer $server)
    {
        $this->loop = $loop;
        $this->server = $server;
        //$this->datetime = new Carbon();
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

    private function subscribeLive (ConnectionInterface $conn, array $data)
    {

    }

    private function unsubscribeLive (ConnectionInterface $conn, array $data)
    {
        
    }

    private function unsubscribe (ConnectionInterface $conn, array $data)
    {
        if (!$ticker = $this->server->getTicker($conn)) {
            echo "ticker is null".PHP_EOL;
            exit(0);
        }
        echo "ticker is not null".PHP_EOL;
        // $this->interval = 0;
        $this->line_pointer = 0;
        $this->_count = 0;
        $this->firsttime = 0;
        $this->minutes = 0;
        $this->timestamp = 0;
        // $this->timeframe = 0;
        $this->open = 0;
        $this->close = 0;
        $this->high = 0;
        $this->low = 0;
        // $this->$datetime ;
        $this->lasttime = 0;
        $this->lines = array();
        $this->done = true;
        $this->timestp = 0;
        $this->loop->cancelTimer($ticker);
        $this->server->setTicker($conn, $ticker);
    }

    private function subscribe (ConnectionInterface $conn, array $data)
    {
        if ($this->interval === 0) {
            $this->interval = $data['interval'] >= 0 || $data['interval'] <= 5.1 ? 5 : $data['interval'];
            $this->timeframe = (float)$data['timeframe'];
        }
        $this->lasttime = $data['lasttime'];
        $this->lines = array();
        $this->done = true;

        $ticker = $this->loop->addPeriodicTimer($this->interval * 0.001, function (TimerInterface $timer) use ($conn, $data) {
            //$time = $data['time'];
            if (!$this->done)
                return;
            $this->done = false;
            // $this->timestp = $this->lasttime;

            if ($this->firsttime === 0) {
                $this->datetime = Carbon::createFromTimestamp($this->lasttime);
                $this->lines = $this->getCsvData($data);
                $this->_count = count($this->lines);
            }

            while (1) {
                if ($this->line_pointer >= $this->_count) {
                    echo "count is $this->_count : linepointer is $this->line_pointer".PHP_EOL;
                    $this->line_pointer = 0;
                    echo "line pointer is now greater than row count".PHP_EOL;
                    echo "getting the next file".PHP_EOL;
                    $this->datetime = $this->datetime->add('hour', 1);
                    echo $this->datetime->format("Y/m/d H:i:s").PHP_EOL;
                    if (!$this->lines = $this->getCsvData($data)) {
                        //failed to get line do something
                        echo "failed to get line, aborting".PHP_EOL;
                        return;
                    }
                    $this->_count = count($this->lines);
                    // return;
                }
                $csv_row = str_getcsv($this->lines[$this->line_pointer]);
                //$datetime = $datetime->createFromFormat("Y-m-d H:i:s.u", $csv_data[0]);
                $unixtime = (float)$csv_row[0];

                //echo "csv time is: $unixtime and last time is $lasttime".PHP_EOL;
                if ($unixtime <= $this->lasttime) {
                    // echo "$csv_row[0] : $unixtime : $lasttime".PHP_EOL;
                    // echo "unixtime less than lasttime".PHP_EOL;
                    $this->line_pointer++;
                    continue;
                }
                // $diff = $minutes-$firsttime;
                // echo "$firsttime : $minutes : $diff : $timeframe".PHP_EOL;
                if ($this->firsttime === 0 || ($this->minutes - $this->firsttime) >= $this->timeframe) {
                    //echo "we got to all".PHP_EOL;
                    $this->firsttime = $unixtime/60;
                    $this->timestamp = $unixtime*1000;
                    $this->open = (float)$csv_row[1];
                    $this->close = (float)$csv_row[1];
                    $this->high = (float)$csv_row[1];
                    $this->low = (float)$csv_row[1];
                    break;
                }
                $this->close = (float)$csv_row[1];
                $this->high = $this->high >= (float) $csv_row[1] ? $this->high : (float) $csv_row[1];
                $this->low = $this->low <= (float) $csv_row[1] ? $this->low : (float) $csv_row[1];
                break;
            }

            $resp = json_encode($dat['data'] = [
                'ev' => 'tick',
                'sym' => $data['ticker'],
                's' => $this->timestamp,
                'o' => $this->open,
                'c' => $this->close,
                'h' => $this->high,
                'l' => $this->low,
            ]);

            //echo "sending data: ".PHP_EOL;
            //echo $resp.PHP_EOL;
            $conn->send($resp);

            $this->minutes = (float)$csv_row[0]/60;
            $this->line_pointer++;
            //echo $datetime->format("Y/m/d H:m:s").PHP_EOL;
            $this->done = true;
        });

        $this->server->setTicker($conn, $ticker);
    }

    private function getCsvData(array $data): array|false
    {
        $file_path = __DIR__."/../download/{$data['ticker']}/{$this->datetime->format('Y/m')}/{$this->datetime->format('Y-m-d')}--{$this->datetime->format('H')}h_ticks.csv";
        echo $file_path.PHP_EOL;

        $timestamp = time();
        if (!$csv_string = file_get_contents($file_path)) {
            echo "unable to get file: ".$file_path.PHP_EOL;
            echo "trying next file".PHP_EOL;
            $this->datetime = $this->datetime->add('hour', 1);
            echo $this->datetime->format("Y/m/d H:i:s").PHP_EOL;
            if ($this->datetime->getTimestamp() - $timestamp >= 0) {
                echo "we have gotten to the present time and we are now abborting".PHP_EOL;
            } 
            $this->getCsvData($data);
        }

        // Split the string into an array of lines
        return explode("\n", $csv_string);
    }
}