<?php
namespace Basttyy\FxDataServer\libs\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class VerifyEmail
{
    private $mail;

    public function __construct(string $address, string $subject)
    {
        $this->mail = new PHPMailer();
        $this->mail->isSMTP();
        $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $this->mail->Host = env('EMAIL_HOST');
        $this->mail->Port = env('EMAIL_PORT');
        $this->mail->SMTPAuth = true;
        $this->mail->Username = env('EMAIL_USERNAME');
        $this->mail->Password = env('EMAIL_PASSWORD');
        $this->mail->From = env('INFO_EMAIL'); //or 'SUPPORT_EMIAL'
        $this->mail->addReplyTo(env('INFO_EMAIL'), env('INFO_EMAIL_NAME'));
        $this->mail->addAddress($address);
        $this->mail->Subject = $subject;
    }

    public static function send(string $address, string $subject)
    {
        new self($address, $subject);

        self::$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
        // self::$mail->AltBody = 'this is a plain text message';

        self::$mail->addAttachment('public/assets/logo.png');

        if (!self::$mail->send()) {
            echo "Mailer Error: " . self::$mail->ErrorInfo;
            return false;
        } else {
            echo "Message Sent!";
            return true;
        }
    }
}