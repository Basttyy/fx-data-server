<?php
namespace Basttyy\FxDataServer\Controllers\Api\Auth;
// require_once __DIR__."/../../../libs/helpers.php";

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\Exceptions\NotFoundException;
use Basttyy\FxDataServer\Exceptions\QueryException;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

final class CaptchaController
{
    private $method;

    public function __construct($method = 'generate')
    {
        $this->method = $method;
        // $authMiddleware = new Guard($authenticator);
    }

    public function __invoke()
    {
        switch ($this->method) {
            case 'generate':
                $resp = $this->generate();
                break;
            case 'validate':
                $resp = $this->comparePhrase();
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        return $resp;
    }

    private function generate ()
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

    private function comparePhrase()
    {
        $inputJSON = file_get_contents('php://input');

        $body = sanitize_data(json_decode($inputJSON, true));

        if ($validated = Validator::validate($body, [
            'captcha-phrase' => 'required|string'
        ])) {
            return JsonResponse::badRequest('errors in request', $validated);
        }
        if (isset($_SESSION['captcha-phrase']) && PhraseBuilder::comparePhrases($_SESSION['captcha-phrase'], $body['captcha-phrase'])) {
            return JsonResponse::ok("captcha is valid", ['status' => 'validated']);
        } else {
            JsonResponse::badRequest("captcha is not valid", ['status' => 'failed']);
        }
    }
}
