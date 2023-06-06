<?php
namespace Basttyy\FxDataServer\libs\Mail;

use Basttyy\FxDataServer\libs\Templater;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class VerifyEmail
{
    private static $mail;

    public function __construct(string $address, string $name, string $subject, string $html)
    {
        self::$mail = new BaseMailer(true, env('NOREPLY_EMAIL_USER'), env('NOREPLY_EMAIL_PASSWORD'), $html);

        self::$mail->addAddress($address, $name);
        self::$mail->setFrom(env('NOREPLY_EMAIL_USER'), env('NOREPLY_EMAIL_NAME'));

        self::$mail->Subject = $subject; // 'Verify Your Email';
        // $mail->addAttachment(__FILE__, 'images/logo.png');
        // self::$mail->send();
        // echo "email sent successfully".PHP_EOL;
    }

    public static function send(string $address, string $name, string $subject, string $code)
    {
        $html = Templater::view('verify.html', 'src/libs/mail/html/', [
            'title' => "Email Verification",
            'header' => "Email Verification",
            'sender_email' => "hello@backtestfx.com",  //$sender->email,
            'contents' => [
                $name ? "Hello $name," : "Hy,",
                "It seems you lost your password or u wish to change it?",
                "If it wasn't you please disregard this email and make sure you can still login to your account. If it was you, then <span style='font-weight: 400;'>use this code <strong>$code</strong> to Reset Your Password or click the “Reset Now Button” below to Reset your password.</span>"
            ],
            'links' => [
                'Verify Email' => "https://backtestfx.com/account/ressetpass?code=$code"
            ]
        ], true);

        new self($address, $name, $subject, $html);

        try {
            self::$mail->send();
            echo "email sent successfully".PHP_EOL;
            return true;
        } catch (Exception $e) {
            echo 'Caught a ' . get_class($e) . ': ' . $e->getMessage().PHP_EOL;
            echo $e->getTraceAsString();
            return false;
        }
    }
}