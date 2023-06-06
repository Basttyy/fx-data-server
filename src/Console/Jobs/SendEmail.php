<?php
namespace Basttyy\FxDataServer\Console\Jobs;

use Basttyy\FxDataServer\Console\QueueInterface;
use Basttyy\FxDataServer\Console\ShouldQueue;
use Basttyy\FxDataServer\libs\Mail\VerifyEmail;
use Basttyy\FxDataServer\Models\User;

class SendMail implements QueueInterface
{
    use ShouldQueue;

    private $subject;
    private $user;
    private $max_attempts;

    public function __construct(array $user, string $subject = "Verify Your Email", $max_attempts = 3)
    {
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
        logger(storage_path()."logs\/console.log")->info("sending verification email to {$this->user['email']}");

        if ($this->job['tries'] > $this->max_attempts)
            return $this->fail();

        if (!VerifyEmail::send($this->user['email'], $this->user['firstname'].' '.$this->user['lastname'], $this->subject, $this->user['email2fa_token']))
            return $this->bury(10);
    }
}