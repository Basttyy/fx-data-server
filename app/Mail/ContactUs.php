<?php
namespace Basttyy\FxDataServer\libs\Mail;

use App\Mail\Mailer;
use Eyika\Atom\Framework\Support\View\Twig;
use PHPMailer\PHPMailer\Exception;

class ContactUs
{
    private static $mail;

    public function __construct(string $replyToEmail, string $replyToFullname)
    {
        self::$mail = Mailer::to(env('SUPPORT_EMAIL_USER'), env('SUPPORT_EMAIL_NAME'))
            ->from(env('NOREPLY_EMAIL_USER'), env('NOREPLY_EMAIL_NAME'))
            ->replyTo($replyToEmail, $replyToFullname);
    }

    public static function send($data = [])
    {
        try {
            new self($data['email'], $data['fullname']);

            $content = ["Hello Support"];
            $content = array_push($content, explode("\n", $data["message"]));
            static::$mail->buildHtml('verify.html', [
                'title' => "User Enquiry",
                'header' => $data['fullname'] . ' ('. $data['subject'].')',
                'sender_email' => $data['email'],  //$sender->email,
                'contents' => $content,
                'links' => []
            ]);

            self::$mail->send($data['subject']);
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