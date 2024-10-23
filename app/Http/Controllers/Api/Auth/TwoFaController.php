<?php
namespace App\Http\Controllers\Api\Auth;

use App\Console\Jobs\SendVerifyEmail;
use App\Models\User;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Str;
use Eyika\Atom\Framework\Support\Validator;
use PragmaRX\Google2FA\Google2FA;

final class TwoFaController
{
    public function generate (Request $request, string $mode)
    {
        try {
            $mode = sanitize_data($mode);

            $modes = [User::EMAIL, User::GOOGLE2FA];

            if (!in_array($mode, $modes)) {
                return JsonResponse::badRequest('errors in request', [
                    'mode should be one of email or google2fa'
                ]);
            }
            $user = $request->auth_user;

            if ($mode == User::EMAIL) {
                $code = random_int(100000, 999999);
                if (!$user->update(['email2fa_token' => (string)$code, 'email2fa_expire' => time() + env('EMAIL2FA_MAX_AGE')])) {  //TODO:: this token should be timeed and should expire
                    return JsonResponse::serverError("unable to generate token");
                }

                $mail_job = new SendVerifyEmail(array_merge($user->toArray(), ['email2fa_token' => (string)$code]));
                $mail_job->init()->delay(5)->run();
                //schdule job to send code to the user via email
                return JsonResponse::ok("code sent to user email");
            } else {
                $google2fa = new Google2FA();
                $secret = $google2fa->generateSecretKey(32);

                //store secret in user data
                if (!$user->update(['twofa_secret' => $secret], is_protected: false)) {
                    return JsonResponse::serverError('unable to generate token');
                }
                $text = $google2fa->getQRCodeUrl(
                    env('WEB_APP_NAME'),
                    $user->email,
                    $user->twofa_secret
                );
                $qr = new QRCode();
    
                return JsonResponse::ok("qr url generated", ['image_url' => $qr->render($text)]);
            }
        } catch (Exception $e) {
            return JsonResponse::serverError("something happened try again " . env('APP_ENV') === "local" ? $e->getTraceAsString() : "");
        }
    }

    public function verifyCode(Request $request, string $mode)
    {
        try {
            $body = $request->input();
    
            if ($validated = Validator::validate($body, [
                'code' => 'required|string',
                'is_email_verification' => 'sometimes|bool'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $user = $request->auth_user;

            if ($mode == "email") {
                if ($user->email2fa_token === $body['code'] && $user->email2fa_expire > time()) { //TODO: verify token is not expired
                    $values = ['email2fa_token' => null, 'email2fa_expire' => null];
                    $values['status'] = isset($body['is_email_verification']) ? User::ACTIVE : $user->status;
                    if (!isset($body['is_email_verification']) && !str_contains($user->twofa_types, User::EMAIL))
                        $values['twofa_types'] = strlen($user->twofa_types) < 1 ? $user->twofa_types.User::EMAIL : $user->twofa_types.','.User::EMAIL;
                    
                    $user->update($values);
                    return JsonResponse::ok("code is valid", ['status' => 'validated']);
                } else {
                        return JsonResponse::badRequest("code is not valid", ['status' => 'failed']);
                }
            } else {
                $google2fa = new Google2FA();
                if ($google2fa->verifyKey($user->twofa_secret, $body['code'])) {
                    if (!str_contains($user->twofa_types, User::GOOGLE2FA)) {
                        $values['twofa_types'] = strlen($user->twofa_types) < 1 ? $user->twofa_types.User::GOOGLE2FA : $user->twofa_types.','.User::GOOGLE2FA;
                        $user->update($values);
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
    
    public function twofaonoff(Request $request, string $mode, int $status)
    {
        $mode = sanitize_data($mode);

        $user = $request->auth_user;

        if (!$status) {
            $values['twofa_types'] = str_replace($mode, '', $user->twofa_types);
            $values['twofa_types'] = str_starts_with($values['twofa_types'], ',') ? substr($values['twofa_types'], 1) : $values['twofa_types'];
            $values['twofa_types'] = str_ends_with($values['twofa_types'], ',') ? substr($values['twofa_types'], 0, -1) : $values['twofa_types'];
    
            if (!$user->update($values)) {
                return JsonResponse::serverError("unable to turn off twofa for user");
            }

            return JsonResponse::ok('twofa has been turned off for user', $user);
        } else {
            return JsonResponse::badRequest('feature not yet implemented');
        }
    }
}
