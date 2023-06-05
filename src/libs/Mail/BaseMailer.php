<?php
namespace Basttyy\FxDataServer\libs\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class VerifyEmail extends PHPMailer
{
    /**
     * BaseMailer constructor.
     *
     * @param bool|null $exceptions
     * @param string    $body A default HTML message body
     */
    public function __construct($exceptions, $body = '')
    {
        //Don't forget to do this or other things may not be set correctly!
        parent::__construct($exceptions);
        //Set a default 'From' address
        $this->setFrom('joe@example.com', 'Joe User');
        //Send via SMTP
        $this->isSMTP();
        //Equivalent to setting `Host`, `Port` and `SMTPSecure` all at once
        $this->Host = 'tls://smtp.example.com:587';
        //Set an HTML and plain-text body, import relative image references
        $this->msgHTML($body, './images/');
        //Show debug output
        $this->SMTPDebug = SMTP::DEBUG_SERVER;
        //Inject a new debug output handler
        $this->Debugoutput = static function ($str, $level) {
            echo "Debug level $level; message: $str\n";
        };
    }

    //Extend the send function
    public function send()
    {
        $this->Subject = '[Yay for me!] ' . $this->Subject;
        $r = parent::send();
        echo 'I sent a message with subject ' . $this->Subject;

        return $r;
    }
}