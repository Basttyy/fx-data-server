<?php
namespace App\Mail;

use App\Mail\Mailer;
use Eyika\Atom\Framework\Support\View\Twig;
use PHPMailer\PHPMailer\Exception;

class ResetPassword
{
    private static $mail;

    public function __construct(string $address, string $name)
    {
        self::$mail = Mailer::to($address, $name)
            ->from(env('NOREPLY_EMAIL_USER'), env('NOREPLY_EMAIL_PASSWORD'));
    }

    public static function send(string $email, string $name, string $subject, string $code)
    {
        try {
            new self($email, $name);

            static::$mail->buildHtml('verify.html', [
                'title' => "Password Reset",
                'header' => "Reset Your Password",
                'sender_email' => env('NOREPLY_EMAIL_USER'),  //$sender->email,
                'contents' => [
                    $name ? "Hello $name," : "Hy,",
                    "You or someone requested to change the password of this account",
                    "If it wasn't you please simply disregard this email and ensure not to give this code to anyone. If it was you, then <span style='font-weight: 400;'>use this code <strong>$code</strong> to change your password or click the “Change Password Button” below to Change Your Password.</span>"
                ],
                'links' => [
                    'Change Password' => "https://backtestfx.com/account/change_pass?code=$code"
                ]
            ]);

            self::$mail->send($subject);
            if (env('APP_ENV') === "local") echo "email sent successfully".PHP_EOL;
                logger(storage_path("logs/email.log"))->info("email sent successfully");
            return true;
        } catch (Exception $e) {
            logger(storage_path("logs/email.log"))->error($e->getMessage(), $e->getTrace());
            if (env('APP_ENV') === "local") echo 'Caught a ' . get_class($e) . ': ' . $e->getMessage().PHP_EOL;
            if (env('APP_ENV') === "local") echo $e->getTraceAsString();
            return false;
        }
    }
}