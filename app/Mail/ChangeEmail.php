<?php
namespace Basttyy\FxDataServer\libs\Mail;

use App\Mail\Mailer;
use PHPMailer\PHPMailer\Exception;

class ChangeEmail
{
    private static $mail;

    public function __construct(string $address, string $name)
    {
        self::$mail = Mailer::to($address, $name)->from(env('NOREPLY_EMAIL_USER'), env('NOREPLY_EMAIL_NAME'));
    }

    public static function send(string $address, string $name, string $subject, string $code)
    {
        try {
            new static($address, $name);

            static::$mail->buildHtml('verify.html', [
                'title' => "Account Email Change",
                'header' => "Account Email Change",
                'sender_email' => env('NOREPLY_EMAIL_USER'),  //$sender->email,
                'contents' => [
                    $name ? "Hello $name," : "Hy,",
                    "You or someone requested to change your email",
                    "If it wasn't you please simply disregard this email. If it was you, then <span style='font-weight: 400;'>use this code <strong>$code</strong> to approve your email change or click the “Change Email Button” below to Change Your Email.</span>"
                ],
                'links' => [
                    'Change Email' => "https://backtestfx.com/account/change_email?code=$code"
                ]
            ]);

            self::$mail->send($subject);
            // echo "email sent successfully".PHP_EOL;
            logger(storage_path("logs/email.log"))->info("email sent successfully");
            return true;
        } catch (Exception $e) {
            logger(storage_path("logs/email.log"))->error($e->getMessage(), $e->getTrace());
            // echo 'Caught a ' . get_class($e) . ': ' . $e->getMessage().PHP_EOL;
            // echo $e->getTraceAsString();
            return false;
        }
    }
}