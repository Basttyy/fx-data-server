<?php

namespace Basttyy\FxDataServer\Controllers\Api\Auth;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\Exceptions\NotFoundException;
use Basttyy\FxDataServer\Exceptions\QueryException;
use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\Subscription;
use Basttyy\FxDataServer\Models\User;
use Exception;
use Hybridauth\Exception\HttpRequestFailedException;
use Hybridauth\Exception\InvalidAccessTokenException;
use Hybridauth\Exception\InvalidAuthorizationCodeException;
use Hybridauth\Exception\InvalidAuthorizationStateException;
use Hybridauth\Exception\InvalidOauthTokenException;
use Hybridauth\Hybridauth;

final class AuthController
{
    //@param \App\Auth\JwtAuthenticator
    private $authenticator;

    private $user;
    private $subscription;
    private $method;

    public function __construct($method = 'login')
    {
        $this->method = $method;
        $this->user = new User;
        $this->subscription = new Subscription;
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role;
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
    }

    public function __invoke()
    {
        switch ($this->method) {
            case 'login':
                $resp = $this->login();
                break;
            case 'login_oauth':
                $resp = $this->loginOauth();
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

    private function login ()
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
                'email' => 'sometimes|string',
                'password' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            // echo "got to pass login";
            if (!$user = $this->user->findByEmail($body['email'], false)) {
                throw new NotFoundException("user does not exist");
            }
            // echo "user found by email";
            if (!$user instanceof User) {
                throw new NotFoundException("user does not exist");
            }

            if (!$token = $this->authenticator->authenticate($this->user, $body['password'])) {
                return JsonResponse::unauthorized("invalid login details");
            }
            $subscription = $this->subscription->findBy('user_id', $this->user->id, false);
            $is_admin = $this->authenticator->verifyRole($this->user, 'admin');

            $user = Arr::except($this->user->toArray(), $this->user->twofainfos);
            $user['extra']['is_admin'] = $is_admin;
            $user['extra']['subscription'] = $subscription ? $subscription : null;
            $user['extra']['twofa']['enabled'] = strlen($this->user->twofa_types) > 0;
            $user['extra']['twofa']['twofa_types'] = $this->user->twofa_types;
            $user['extra']['twofa']['twofa_default_type'] = $this->user->twofa_default_type;

            return JsonResponse::ok("login successfull", [
                'auth_token' => $token,
                'data' => $user
            ]);
        } catch (NotFoundException $ex) {
            return JsonResponse::unauthorized("the requested user was not found");
        } catch (Exception $e) {
            logger()->error($e->getMessage(), $e->getTrace());
            return JsonResponse::serverError("something happened try again ");
        }
    }

    private function loginOauth()
    {
        if (strtolower($_SERVER['REQUEST_METHOD']) === "options") {
            return JsonResponse::ok();
        }
        try {
            if (isset($_GET['provider'])) {
                $_SESSION['provider'] = $_GET['provider'];
                $provider = $_GET['provider'];
            } else {
                $provider = "facebook"; // $_SESSION['provider'];
            }
    
            $providers = ['facebook', 'twitter', 'google'];
            if (!in_array($provider, $providers)) {
                return JsonResponse::badRequest("invalid provider name", [
                    "requested_provider" => $provider,
                    "available_providers" => $providers
                ]);
            }
            
            $hybridauth = new Hybridauth("{$_SERVER['DOCUMENT_ROOT']}/hybridauth_config.php");  //, null, new DbStorage('SOCIALAUTH::STORAGE'));
            
            $adapter = $hybridauth->authenticate($provider);
    
            // Returns a boolean of whether the user is connected with Twitter
            if (!$adapter->isConnected()) {
                if (env('APP_ENV') === 'local') consoleLog(0, "user is not connected to provider");
                return JsonResponse::unauthorized("unsuccessful social login attempt");
            }
            if (env('APP_ENV') === 'local') consoleLog(0, "user is connected to provider");
        
            // Retrieve the user's profile
            $userProfile = $adapter->getUserProfile();
            
            if (!$user = $this->user->findByArray(['email', 'uuid'], [$userProfile->email, $userProfile->identifier])) {
                if (!$user = $this->user->create([
                    'email' => $userProfile->email,
                    'username' => $userProfile->email,
                    'firstname' => $userProfile->firstName,
                    'lastname' => $userProfile->lastName,
                    'country' => $userProfile->country,
                    'city' => $userProfile->city,
                    'address' => $userProfile->address,
                    'postal_code' => $userProfile->zip,
                    'avatar' => is_null($userProfile->photoURL) ?: $userProfile->photoURL,
                    'status' => is_null($userProfile->emailVerified) ?: User::ACTIVE,
                    'uuid' => $userProfile->identifier,
                    'phone' => $userProfile->phone
                ])) {
                    return JsonResponse::serverError("error creating user please try again");
                }
                
                $usr = Arr::except($user[0], $this->user->twofainfos);
                $subscription = $this->subscription->findBy('user_id', $this->user->id, false);
                
                $usr['extra']['is_admin'] = false;
                $usr['extra']['subscription'] = $subscription ? $subscription : null;
                $usr['extra']['twofa']['enabled'] = strlen($this->user->twofa_types) > 0;
                $usr['extra']['twofa']['twofa_types'] = $this->user->twofa_types;
                $usr['extra']['twofa']['twofa_default_type'] = $this->user->twofa_default_type;

                return JsonResponse::created('user account has been created', [
                    'auth_token' => "social_login:". base64_encode($usr['id']),
                    'data' => $usr
                ]);
            }
                
            $usr = Arr::except($user[0], $this->user->twofainfos);
            $subscription = $this->subscription->findBy('user_id', $this->user->id, false);
            
            $usr['extra']['is_admin'] = false;
            $usr['extra']['subscription'] = $subscription ? $subscription : null;
            $usr['extra']['twofa']['enabled'] = strlen($this->user->twofa_types) > 0;
            $usr['extra']['twofa']['twofa_types'] = $this->user->twofa_types;
            $usr['extra']['twofa']['twofa_default_type'] = $this->user->twofa_default_type;

            return JsonResponse::ok("login successfull", [
                'auth_token' => "social_login:". base64_encode($usr['id']),
                'user' => $usr
            ]);
    
            // Disconnect the adapter (log out)
            // $adapter->disconnect();
            // if (!isset($_GET['provider'])) {
            //     unset($_SESSION['provider']);
            // }
        } catch (InvalidAuthorizationStateException $e) {
            return JsonResponse::unauthorized("the authorization state is invalid or consumed");
        } catch (InvalidAuthorizationCodeException $e) {
            return JsonResponse::unauthorized("invalid authorization code");
        } catch (InvalidAccessTokenException $ea) {
            return JsonResponse::unauthorized("invalid user access token");
        } catch (InvalidOauthTokenException $er) {
            return JsonResponse::unauthorized("invalid provider oauth token");
        } catch (HttpRequestFailedException $e) {
            return JsonResponse::unauthorized("failed to call provider with credentials");
        } catch (Exception $ex) {
            if (env('APP_ENV') === 'local') consoleLog(0, $ex->getMessage(). "   ".$ex->getTraceAsString());
            return JsonResponse::serverError("something happened try again " . $ex->getMessage() . " ". $ex->getTraceAsString());
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
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized("invalid auth token");
            }
            if (!$token = $this->authenticator->authenticate($this->user)) {
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
