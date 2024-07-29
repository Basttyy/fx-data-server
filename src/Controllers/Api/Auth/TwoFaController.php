<?php
namespace Basttyy\FxDataServer\Controllers\Api\Auth;

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\Console\Jobs\SendVerifyEmail;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Str;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
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
    }

    public function __invoke(string $mode, int $status = 1)
    {
        switch ($this->method) {
            case 'generate':
                $resp = $this->generate($mode);
                break;
            case 'validate':
                $resp = $this->verifyCode($mode);
                break;
            case 'twofaonoff':
                $resp = $this->twofaonoff($mode, $status);
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        return $resp;
    }

    private function generate (string $mode)
    {
        try {
            if (!Guard::tryToAuthenticate($this->authenticator)) {
                return JsonResponse::unauthorized();
            }
            $mode = sanitize_data($mode);

            $modes = [User::EMAIL, User::GOOGLE2FA];

            if (!in_array($mode, $modes)) {
                return JsonResponse::badRequest('errors in request', [
                    'mode should be one of email or google2fa'
                ]);
            }
            if ($mode == User::EMAIL) {
                $code = Str::random(6); implode([rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9)]);
                if (!$this->user->update(['email2fa_token' => (string)$code, 'email2fa_expire' => time() + env('EMAIL2FA_MAX_AGE')])) {  //TODO:: this token should be timeed and should expire
                    return JsonResponse::serverError("unable to generate token");
                }

                $mail_job = new SendVerifyEmail(array_merge($this->user->toArray(), ['email2fa_token' => (string)$code]));
                $mail_job->init()->delay(5)->run();
                //schdule job to send code to the user via email
                return JsonResponse::ok("code sent to user email");
            } else {
                $google2fa = new Google2FA();
                $secret = $google2fa->generateSecretKey(32);
    
                //store secret in user data
                if (!$this->user->update(['twofa_secret' => $secret], is_protected: false)) {
                    return JsonResponse::serverError('unable to generate token');
                }
                $text = $google2fa->getQRCodeUrl(
                    env('WEB_APP_NAME'),
                    $this->user->email,
                    $this->user->twofa_secret
                );
    
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
            if (!Guard::tryToAuthenticate($this->authenticator)) {
                return JsonResponse::unauthorized();
            }

            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
    
            if ($validated = Validator::validate($body, [
                'code' => 'required|string',
                'is_email_verification' => 'sometimes|bool'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$this->user->find(is_protected: false)) {
                return JsonResponse::serverError("unable to find logged in user");
            }

            if ($mode == "email") {
                if ($this->user->email2fa_token === $body['code'] && $this->user->email2fa_expire > time()) { //TODO: verify token is not expired
                    $values = ['email2fa_token' => null, 'email2fa_expire' => null];
                    $values['status'] = isset($body['is_email_verification']) ? User::ACTIVE : $this->user->status;
                    if (!isset($body['is_email_verification']) && !str_contains($this->user->twofa_types, User::EMAIL))
                        $values['twofa_types'] = strlen($this->user->twofa_types) < 1 ? $this->user->twofa_types.User::EMAIL : $this->user->twofa_types.','.User::EMAIL;
                    
                    $this->user->update($values);
                    return JsonResponse::ok("code is valid", ['status' => 'validated']);
                } else {
                        return JsonResponse::badRequest("code is not valid", ['status' => 'failed']);
                }
            } else {
                $google2fa = new Google2FA();
                if ($google2fa->verifyKey($this->user->twofa_secret, $body['code'])) {
                    if (!str_contains($this->user->twofa_types, User::GOOGLE2FA)) {
                        $values['twofa_types'] = strlen($this->user->twofa_types) < 1 ? $this->user->twofa_types.User::GOOGLE2FA : $this->user->twofa_types.','.User::GOOGLE2FA;
                        $this->user->update($values);
                    }
                    return JsonResponse::ok("code is valid", ['status' => 'validated']);
                } else {
                    return JsonResponse::badRequest("code is not valid", ['status' => 'failed']);
                }
            }
        } catch (Exception $e) {
            if (env('APP_ENV') === "local")
                logger()->info($e->getMessage(), $e->getTrace());
            return JsonResponse::serverError("something happened try again ");
        }
    }
    
    private function twofaonoff(string $mode, int $status)
    {   
        if (!$user = Guard::tryToAuthenticate($this->authenticator)) {
            return JsonResponse::unauthorized();
        }
        $mode = sanitize_data($mode);

        if (!$user = $user->find()) {
            return JsonResponse::serverError("unable to find logged in user");
        }

        if (!$status) {
            $values['twofa_types'] = str_replace($mode, '', $this->user->twofa_types);
            $values['twofa_types'] = str_starts_with($values['twofa_types'], ',') ? substr($values['twofa_types'], 1) : $values['twofa_types'];
            $values['twofa_types'] = str_ends_with($values['twofa_types'], ',') ? substr($values['twofa_types'], 0, -1) : $values['twofa_types'];
    
            if (!$this->user->update($values)) {
                return JsonResponse::serverError("unable to turn off twofa for user");
            }

            return JsonResponse::ok('twofa has been turned off for user', $this->user);
        } else {
            return JsonResponse::badRequest('feature not yet implemented');
        }
    }
}

?>