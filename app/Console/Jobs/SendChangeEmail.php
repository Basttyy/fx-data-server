<?php
namespace App\Console\Jobs;

use Eyika\Atom\Framework\Foundation\Console\Concerns\ShouldQueue;
use Eyika\Atom\Framework\Foundation\Console\Contracts\QueueInterface;
use Basttyy\FxDataServer\libs\Mail\ChangeEmail;
use Exception;

class SendChangeEmail implements QueueInterface
{
    use ShouldQueue;

    private $user;
    private $subject;
    private $max_attempts;

    public function __construct(array $user, string $subject = "BacktestFx Account", $max_attempts = 3)
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->max_attempts = $max_attempts;
    }
    
    /**
     * handles the queue logic
     * 
     * @return void
     */
    public function handle()
    {
        if (env('APP_ENV') == 'local' && env('SEND_EMAIL_ON_LOCAL') != 'true') {
            return $this->delete();
        }
        try {
            logger(storage_path("logs/email.log"))->info("sending change code email to {$this->user['email']}");

            if ($this->job['tries'] >= $this->max_attempts)
                return $this->fail();

            if (!ChangeEmail::send($this->user['email'], $this->user['firstname'].' '.$this->user['lastname'], $this->subject, $this->user['email2fa_token']))
                return $this->bury(10);

            $this->delete();
        } catch (Exception $e) {
            logger(storage_path("logs/email.log"))->error('Caught a ' . get_class($e) . ': ' . $e->getMessage(), $e->getTrace());
        }
    }
}