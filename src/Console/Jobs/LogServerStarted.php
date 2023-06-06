<?php
namespace Basttyy\FxDataServer\Console\Jobs;

use Basttyy\FxDataServer\Console\QueueInterface;
use Basttyy\FxDataServer\Console\ShouldQueue;

class LogServerStarted implements QueueInterface
{
    use ShouldQueue;

    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
    
    /**
     * handles the queue logic
     * 
     * @return void
     */
    public function handle()
    {
        logger(storage_path()."logs\/console.log")->info($this->message);

        return $this->bury(10);
    }
}