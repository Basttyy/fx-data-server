<?php
namespace Basttyy\FxDataServer\Console;

use Cake\Database\Driver\Sqlite;
use Basttyy\FxDataServer\Console\Job_Queue;
use PDO;
use React\Promise\PromiseInterface;

interface QueueInterface
{
    
    /**
     * handles the queue logic
     * 
     * @return void
     */
    public function handle();

    public function setJob(array $job): void;

    public function setQueue(Job_Queue $queue): void;

    public function init (): self;

    public function delay(int $delay): self;

    public function priority(int $prio): self;

    public static function dispatch(): self;

    public function onQueue(string $pipeline_name): self;

    public function onConnection(PDO|Sqlite $connection): self;

    public function run(): void;
}