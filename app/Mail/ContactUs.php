<?php
namespace Basttyy\FxDataServer\libs\Mail;

use App\Mail\Mailer;
use Eyika\Atom\Framework\Support\View\Twig;
use PHPMailer\PHPMailer\Exception;

class ContactUs
{
    private static $mail;

    public function __construct(string $html, $data = [])
    {
        self::$mail = new Mailer(true, env(['NOREPLY_EMAIL_USER']), env(['NOREPLY_EMAIL_PASSWORD']), $html);

        self::$mail->addAddress(env('SUPPORT_EMAIL_USER'), env('SUPPORT_EMAIL_NAME'));
        self::$mail->setFrom(env('NOREPLY_EMAIL_USER'), env('NOREPLY_EMAIL_NAME'));
        self::$mail->addReplyTo($data['email'], $data['fullname']);

        self::$mail->Subject = $data['subject']; // 'Verify Your Email';
        // $mail->addAttachment(__FILE__, 'images/logo.png');
        // self::$mail->send();
        // echo "email sent successfully".PHP_EOL;
    }

    public static function send($data = [])
    {
        try {
            $content = ["Hello Support"];
            $content = array_push($content, explode("\n", $data["message"]));
            $html = Twig::make('verify.html', '/Mail/html/', [
                'title' => "User Enquiry",
                'header' => $data['fullname'] . ' ('. $data['subject'].')',
                'sender_email' => $data['email'],  //$sender->email,
                'contents' => $content,
                'links' => []
            ], true);
    
            new self($html, $data);

            self::$mail->send();
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