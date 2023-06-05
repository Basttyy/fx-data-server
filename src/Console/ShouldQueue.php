<?php
namespace Basttyy\FxDataServer\Console;

use Cake\Database\Driver\Sqlite;
use Basttyy\FxDataServer\Console\Job_Queue;
use PDO;

trait ShouldQueue
{
    private $delay = 60;

    private $priority = 1024;

    private $job;

    private static Job_Queue $queue;

    public function setJob($job): void
    {
        $this->job = $job;
    }

    public function setQueue(Job_Queue $queue): void
    {
        $this::$queue = $queue;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public static function dispatch (): self
    {
        $me = new self;
        $me::$queue = new Job_Queue('mysql', [
            'mysql' => [
                'table_name' => 'jobs',     //the table that jobs will be stored in
                'use_compression' => true
            ]
        ]);

        $db_name = env('NAME');
        $pdo = new PDO("mysql:dbname=$db_name;host=127.0.0.1", env('USER'), env('PASS'));
        $me::$queue->addQueueConnection($pdo);
        $me::$queue->setPipeline('default');
        $me::$queue->selectPipeline('default');

        return $me;
    }

    public function init (): self
    {
        $this::$queue = new Job_Queue('mysql', [
            'mysql' => [
                'table_name' => 'jobs',     //the table that jobs will be stored in
                'use_compression' => true
            ]
        ]);
        $db_name = env('NAME');
        $pdo = new PDO("mysql:dbname=$db_name;host=127.0.0.1", env('USER'), env('PASS'));
        $this::$queue->addQueueConnection($pdo);
        $this::$queue->setPipeline('default');
        $this::$queue->selectPipeline('default');

        return $this;
    }

    public function delay(int $delay): self
    {
        $this->delay = $delay;
        return $this;
    }

    public function priority(int $prio): self
    {
        $this->priority = $prio;
        return $this;
    }

    public function onQueue(string $pipeline_name): self
    {
        $this::$queue->setPipeline($pipeline_name);
        return $this;
    }

    public function onConnection(PDO|Sqlite $connection): self
    {
        $this::$queue->addQueueConnection($connection);
        return $this;
    }

    private function delete()
    {
        return $this::$queue->deleteJob($this->job);
    }

    /**
     * Release the job to a different Queue
     * 
     * @param int $delay
     * @return void
     */
    private function bury(int $delay = null)
    {
        $id = $this->job['id'];
        unset($this->job);
        $sclass = serialize($this);
        $this->delay = $delay ?? $this->delay;
        return $this::$queue->buryJob(['payload' => $sclass, 'id' => $id], $this->delay);
    }

    public function run(): void
    {
        $sclass = serialize($this);
        $this::$queue->addJob($sclass, $this->delay, $this->priority, $this->delay);
    }
}