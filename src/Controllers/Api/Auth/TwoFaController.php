<?php
namespace Basttyy\FxDataServer\Controllers\Api\Auth;
// require_once __DIR__."\\..\\..\\..\\libs\\helpers.php";

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\Exceptions\NotFoundException;
use Basttyy\FxDataServer\Exceptions\QueryException;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\mysqly;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use DateTime;
use Exception;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use PragmaRX\Google2FA\Google2FA;

final class TwoFaController
{
    private $method;
    private $user;
    private $authenticator;

    public function __construct($method = 'generate')
    {
        $this->method = $method;
        $this->user = new User();
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role();
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
        // $authMiddleware = new Guard($authenticator);
    }

    public function __invoke(string $mode)
    {
        switch ($this->method) {
            case 'generate':
                $resp = $this->generate($mode);
                break;
            case 'validate':
                $resp = $this->verifyCode($mode);
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        return $resp;
    }

    private function generate (string $mode)
    {
        try {
            if (!$user = Guard::tryToAuthenticate($this->authenticator)) {
                return JsonResponse::unauthorized();
            }
            if ($mode == "email") {
                $code = implode([rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9)]);
                if (!$user->update(['email2fa_token' => (string)$code, 'email2fa_max_age' => time() + env('EMAIL2FA_MAX_AGE')])) {  //TODO:: this token should be timeed and should expire
                    return JsonResponse::serverError("unable to generate token");
                }
                //schdule job to send code to the user via email
                return JsonResponse::ok("code sent to user email");
            } else {
                $google2fa = new Google2FA();
                $secret = $google2fa->generateSecretKey();
                consoleLog(0, $secret);
    
                //store secret in user data
                $user = $user->update(['twofa_secret' => $secret]);
    
                if ($user instanceof User) {
                    $text = $google2fa->getQRCodeUrl(
                        env('WEB_APP_NAME'),
                        $user->email,
                        $user->twofa_secret
                    );
                }
    
                $image_url = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl='.$text;
                return JsonResponse::ok("qr url generated", ['image_url' => $image_url]);
            }
        } catch (Exception $e) {
            return JsonResponse::serverError("something happened try again " . env('APP_ENV') === "local" ? $e->getTraceAsString() : "");
        }
    }

    private function verifyCode(string $mode)
    {
        try {            
            if (!$user = Guard::tryToAuthenticate($this->authenticator)) {
                return JsonResponse::unauthorized();
            }

            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
    
            if ($validated = Validator::validate($body, [
                'code' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$user = $user->find()) {
                return JsonResponse::serverError("unable to find logged in user");
            }

            if ($user instanceof User) {
                if ($mode == "email") {
                    if ($user->email2fa_token === $body['code'] && $user->email2fa_max_age > time()) { //TODO: verify token is not expired
                        $user->update(['email2fa_token' => null]);
                        return JsonResponse::ok("code is valid", ['status' => 'validated']);
                    } else {
                         return JsonResponse::badRequest("code is not valid", ['status' => 'failed']);
                    }
                } else {
                    $google2fa = new Google2FA();
                    if ($google2fa->verifyKey($user->twofa_secret, $body['code'])) {
                        return JsonResponse::ok("code is valid", ['status' => 'validated']);
                    } else {
                         return JsonResponse::badRequest("code is not valid", ['status' => 'failed']);
                    }
                }
            }
        } catch (Exception $e) {
            $message = env('APP_ENV') === "local" ? $e->getMessage() . "   " . $e->getTraceAsString() : "";
            consoleLog(0, $message);
            return JsonResponse::serverError("something happened try again ");
        }
    }
}

?>