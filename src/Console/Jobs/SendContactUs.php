<?php
namespace Basttyy\FxDataServer\Console\Jobs;

use Basttyy\FxDataServer\Console\QueueInterface;
use Basttyy\FxDataServer\Console\ShouldQueue;
use Basttyy\FxDataServer\libs\Mail\ContactUs;
use Basttyy\FxDataServer\libs\Mail\VerifyEmail;
use Exception;

class SendContactUs implements QueueInterface
{
    use ShouldQueue;

    private $data;
    private $subject;
    private $max_attempts;

    public function __construct(array $data, $max_attempts = 3)
    {
        $this->data = $data;
        $this->max_attempts = $max_attempts;
    }
    
    /**
     * handles the queue logic
     * 
     * @return void
     */
    public function handle()
    {
        if (env('APP_ENV') == 'local' || env('SEND_EMAIL_ON_LOCAL') != 'true') {
            return $this->delete();
        }
        try {
            logger(storage_path()."logs/email.log")->info("sending contact us email from {$this->data['email']}");

            if ($this->job['tries'] >= $this->max_attempts)
                return $this->fail();

            if (!ContactUs::send($this->data))
                return $this->bury(10);

            $this->delete();
        } catch (Exception $e) {
            logger(storage_path()."logs/email.log")->error('Caught a ' . get_class($e) . ': ' . $e->getMessage(), $e->getTrace());
        }
    }
}