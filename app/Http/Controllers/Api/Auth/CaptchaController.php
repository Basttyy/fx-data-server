<?php
namespace App\Http\Controllers\Api\Auth;

use Exception;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Validator;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

final class CaptchaController
{
    public function generate ()
    {
        try {
            $captcha = new CaptchaBuilder;
            $_SESSION['captcha-phrase'] = $captcha->getPhrase();
            $captcha->build(128, 32);
            header('Content-Type: image/jpeg');
            $captcha->output();

            return true;
        } catch (Exception $e) {
            return JsonResponse::serverError("something happened try again " . env('APP_ENV') === "local" ? $e->getTraceAsString() : "");
        }
    }

    public function comparePhrase(Request $request)
    {
        $body = $request->input();

        if ($validated = Validator::validate($body, [
            'captcha-phrase' => 'required|string'
        ])) {
            return JsonResponse::badRequest('errors in request', $validated);
        }
        logger()->info('phrases are: ', [$_SESSION['captcha-phrase'], $body['captcha-phrase']]);
        if (isset($_SESSION['captcha-phrase']) && PhraseBuilder::comparePhrases($_SESSION['captcha-phrase'], $body['captcha-phrase'])) {
            return JsonResponse::ok("captcha is valid", ['status' => 'validated']);
        } else {
            return JsonResponse::badRequest("captcha is not valid", ['status' => 'failed']);
        }
    }
}
