<?php

namespace Basttyy\FxDataServer\Controllers\Api\Auth;
// require_once __DIR__."\\..\\..\\..\\libs\\helpers.php";

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\Exceptions\NotFoundException;
use Basttyy\FxDataServer\Exceptions\QueryException;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;

final class LoginController
{
    //@param \App\Auth\JwtAuthenticator
    private $authenticator;

    private $user;

    public function __construct()
    {
        $this->user = new User();
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role();
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
        // $authMiddleware = new Guard($authenticator);
    }

    public function __invoke($method = 'login')
    {
        try {
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= 0) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'email' => 'required|string',
                'password' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            try {
                $user = $this->user->findByEmail($body['email']);;
                if ($user instanceof Exception) {
                    throw $user;
                }

                if (!$user) {
                    throw new NotFoundException("user does not exist");
                }

                if (!$user instanceof User) {
                    throw new NotFoundException("user does not exist");
                }
                if (!$token = $this->authenticator->authenticate($user, $body['password'])) {
                    return JsonResponse::unauthorized("invalid login details");
                }

                return JsonResponse::ok("login successfull", [
                    'auth_token' => $token,
                    'data' => $this->user->toArray(true)
                ]);
            } catch (Exception | NotFoundException | QueryException $ex) {
                if ($ex instanceof NotFoundException) {
                    return JsonResponse::unauthorized();
                } else if ($ex instanceof QueryException) {
                    return JsonResponse::serverError("something happened try again " . $ex->getMessage());
                }

                return JsonResponse::serverError("something happened try again " . $ex->getMessage());
            }
        } catch (Exception $e) {
            return JsonResponse::serverError("something happened try again " . $e->getTraceAsString());
        }
    }
}
