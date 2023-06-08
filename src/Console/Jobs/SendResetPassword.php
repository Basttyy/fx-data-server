<?php
namespace Basttyy\FxDataServer\Console\Jobs;

use Basttyy\FxDataServer\Console\QueueInterface;
use Basttyy\FxDataServer\Console\ShouldQueue;
use Basttyy\FxDataServer\libs\Mail\ResetPassword;

class SendResetPassword implements QueueInterface
{
    use ShouldQueue;

    private $user;
    private $subject;
    private $max_attempts;

    public function __construct(array $user, string $subject = "Reset Request", $max_attempts = 3)
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
        if ($this->job['tries'] > $this->max_attempts)
            return $this->fail();

        if (!ResetPassword::send($this->user['email'], $this->user['firstname'].' '.$this->user['lastname'], $this->subject, $this->user['email2fa_token']))
            return $this->bury(10);

        $this->delete();
    }
}