<?php

namespace Basttyy\FxDataServer\Controllers\Api\Auth;
// require_once __DIR__."\\..\\..\\..\\libs\\helpers.php";

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\Exceptions\NotFoundException;
use Basttyy\FxDataServer\Exceptions\QueryException;
use Basttyy\FxDataServer\libs\DbStorage;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
use Hybridauth\Hybridauth;

final class AuthController
{
    //@param \App\Auth\JwtAuthenticator
    private $authenticator;

    private $user;
    private $method;

    public function __construct($method = 'login')
    {
        $this->method = $method;
        $this->user = new User();
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role();
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
        // $authMiddleware = new Guard($authenticator);
    }

    public function __invoke(string $provider = "")
    {
        switch ($this->method) {
            case 'login':
                $resp = $this->login($provider);
                break;
            case 'forgot_pass':
                $resp = $this->forgotPassword();
                break;
            case 'change_pass':
                $resp = $this->changePassword();
                break;
            case 'reset_pass':
                $resp = $this->resetPassword();
                break;
            case 'refresh_token':
                $resp = $this->refreshToken();
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        return $resp;
    }

    private function login (string $provider)
    {
        try {
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= 0) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            $login_methods = ['password_grant', 'token_grant'];
            $str = implode(', ', $login_methods);

            if ($validated = Validator::validate($body, [
                'email' => 'required|string',
                'password' => 'required|string',
                // 'login_method' => "sometimes|string|in:$str",
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            try {
                if (isset($body['login_method']) && $body['login_method'] === $login_methods[1]) {
                    $providers = ['facebook', 'twitter', 'google'];
                    if ($provider === "" || !in_array($provider, $providers)) {
                        return JsonResponse::badRequest("invalide provider name", [
                            "request_provider" => $provider,
                            "available_providers" => $providers
                        ]);
                    }
                    
                    $hybridauth = new Hybridauth("{$_SERVER['DOCUMENT_ROOT']}/hybridauth_config.php");
                    $adapter = $hybridauth->authenticate($provider);

                    // Returns a boolean of whether the user is connected with Twitter
                    $isConnected = $adapter->isConnected();
                
                    // Retrieve the user's profile
                    $userProfile = $adapter->getUserProfile();

                    // Inspect profile's public attributes
                    var_dump($userProfile);

                    // Disconnect the adapter (log out)
                    $adapter->disconnect();
                } else if (isset($body['login_method']) && $body['login_method'] === $login_methods[0]) {
                    if (!$user = $this->user->findByEmail($body['email'])) {
                        throw new NotFoundException("user does not exist");
                    }
    
                    if (!$user instanceof User) {
                        throw new NotFoundException("user does not exist");
                    }
    
                    if (!$token = $this->authenticator->authenticate($this->user, $body['password'])) {
                        return JsonResponse::unauthorized("invalid login details");
                    }
    
                    return JsonResponse::ok("login successfull", [
                        'auth_token' => $token,
                        'data' => $this->user->toArray(true)
                    ]);
                } else {                    
                        $hybridauth = new Hybridauth("{$_SERVER['DOCUMENT_ROOT']}/hybridauth_config.php", null, new DbStorage('SOCIALAUTH::STORAGE'));
                        $adapter = $hybridauth->authenticate($provider);

                        // Returns a boolean of whether the user is connected with Twitter
                        $isConnected = $adapter->isConnected();
                    
                        // Retrieve the user's profile
                        $userProfile = $adapter->getUserProfile();

                        // Inspect profile's public attributes
                        var_dump($userProfile);

                        // Disconnect the adapter (log out)
                        $adapter->disconnect();
                }
            } catch (Exception | NotFoundException | QueryException $ex) {
                if ($ex instanceof NotFoundException) {
                    return JsonResponse::unauthorized();
                } else if ($ex instanceof QueryException) {
                    return JsonResponse::serverError("something happened try again " . env('APP_ENV') === "local" ? $ex->getTraceAsString() : "");
                }

                return JsonResponse::serverError("something happened try again " . env('APP_ENV') === "local" ? $ex->getMessage() : "");
            }
        } catch (Exception $e) {
            return JsonResponse::serverError("something happened try again " . env('APP_ENV') === "local" ? $e->getTraceAsString() : "");
        }
    }

    private function forgotPassword()
    {

    }

    private function resetPassword()
    {

    }

    private function changePassword()
    {

    }

    private function refreshToken()
    {
        // $keys =  array_keys($request->getHeaders());
        // $headers =  Arr::flatten($request->getHeaders());
        // $headers =  array_combine($keys, $headers);

        // if ($validated = Validator::validate($headers, [
        //     'firebase_token' => 'required|string'
        // ])) {
        //     return JsonResponse::badRequest('errors in request', $validated);
        // }
        // if ($validated = Validator::validate($_SERVER, [
        //     'refresh_token' => 'required|string'
        // ])) {
        //     return JsonResponse::badRequest('errors in request', $validated);
        // }
        try {
            if (!$token = $this->authenticator->validate()) {
                return JsonResponse::unauthorized("invalid auth token");
            }
            return JsonResponse::ok("refresh token success", [
                'auth_token' => $token
            ]);
        } catch (QueryException $e) {
            return JsonResponse::serverError("something happened try again" . env('APP_ENV') === "local" ? $e->getTraceAsString() : "");
        } catch (Exception $e) {
            return JsonResponse::serverError("something happened try again" . env('APP_ENV') === "local" ? $e->getTraceAsString() : "");
        }
    }
}
