<?php

namespace Basttyy\FxDataServer\Controllers\Api\Auth;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\Exceptions\NotFoundException;
use Basttyy\FxDataServer\Exceptions\QueryException;
use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
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
use PHPUnit\Event\Telemetry\Info;

final class AuthController
{
    public function login (Request $request)
    {
        try {
            if (!$request->hasBody()) {
                return JsonResponse::badRequest("bad request", "body is required");
            }

            $decoded_obj = $request->input();
            if (isset($decoded_obj['password']) && is_local_postman()) {
                $decoded_obj['password'] = base64_encode($decoded_obj['password']);
            }

            $body = sanitize_data($decoded_obj);

            if ($validated = Validator::validate($body, [
                'email' => 'sometimes|string',
                'password' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$user = User::getBuilder()->where('email', $body['email'])->orWhere('username', $body['email'])->first(false)) {
                throw new NotFoundException("invalid login details");
            }
            // echo "user found by email";
            if (!$user instanceof User) {
                throw new NotFoundException("invalid login details");
            }

            $authenticator = new JwtAuthenticator(new JwtEncoder(env('APP_KEY')), new User, new Role);
            if (!$token = $authenticator->authenticate($user, base64_decode($body['password']))) {
                return JsonResponse::unauthorized("invalid login details");
            }
            $subscription = Subscription::getBuilder()->findBy('user_id', $user->id, false);
            $is_admin = $authenticator->verifyRole($user, 'admin');

            $hidden = [ ...$user::twofainfos, 'password'];
            $_user = Arr::except($user->toArray(), $hidden);
            $_user['extra'] = [
                'is_admin' => $is_admin,
                'subscription' => $subscription ? $subscription : null,
                'twofa' => [
                    'enabled' => strlen($user->twofa_types) > 0,
                    'twofa_types' => $user->twofa_types,
                    'twofa_default_type' => $user->twofa_default_type,
                ]
            ];
            // $_user['extra']['is_admin'] = $is_admin;
            // $_user['extra']['subscription'] = $subscription ? $subscription : null;
            // $_user['extra']['twofa']['enabled'] = strlen($user->twofa_types) > 0;
            // $_user['extra']['twofa']['twofa_types'] = $user->twofa_types;
            // $_user['extra']['twofa']['twofa_default_type'] = $user->twofa_default_type;

            return JsonResponse::ok("login successfull", [
                'auth_token' => $token,
                'data' => $_user
            ]);
        } catch (NotFoundException $ex) {
            return JsonResponse::unauthorized($ex->getMessage());
        } catch (Exception $e) {
            logger()->error($e->getMessage(), $e->getTrace());
            return JsonResponse::serverError("something happened try again ");
        }
    }

    public function loginOauth(Request $request)
    {
        if (strtolower($request->method()) === "options") {
            return JsonResponse::ok();
        }
        try {
            if ($request->query('provider')) {
                $_SESSION['provider'] = $request->query('provider');
                $provider = $request->query('provider');
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
            
            $hybridauth = new Hybridauth("{$request->documentRoot()}/hybridauth_config.php");  //, null, new DbStorage('SOCIALAUTH::STORAGE'));
            
            $adapter = $hybridauth->authenticate($provider);
    
            // Returns a boolean of whether the user is connected with Twitter
            if (!$adapter->isConnected()) {
                if (env('APP_ENV') === 'local') consoleLog(0, "user is not connected to provider");
                return JsonResponse::unauthorized("unsuccessful social login attempt");
            }
            if (env('APP_ENV') === 'local') consoleLog(0, "user is connected to provider");
        
            // Retrieve the user's profile
            $userProfile = $adapter->getUserProfile();
            
            if (!$user = User::getBuilder()->findByArray(['email', 'uuid'], [$userProfile->email, $userProfile->identifier])) {
                if (!$user = User::getBuilder()->create([
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
                /** @var User $user */
                
                $usr = Arr::except($user->toArray(false), $user::twofainfos);
                $subscription = Subscription::getBuilder()->findBy('user_id', $user->id, false);
                
                $usr['extra']['is_admin'] = false;
                $usr['extra']['subscription'] = $subscription ? $subscription : null;
                $usr['extra']['twofa']['enabled'] = strlen($user->twofa_types) > 0;
                $usr['extra']['twofa']['twofa_types'] = $user->twofa_types;
                $usr['extra']['twofa']['twofa_default_type'] = $user->twofa_default_type;

                return JsonResponse::created('user account has been created', [
                    'auth_token' => "social_login:". base64_encode($usr['id']),
                    'data' => $usr
                ]);
            }
                
            $usr = Arr::except($user[0], User::twofainfos);
            $subscription = Subscription::getBuilder()->findBy('user_id', $user[0]['id'], false);
            
            $usr['extra']['is_admin'] = false;
            $usr['extra']['subscription'] = $subscription ? $subscription : null;
            $usr['extra']['twofa']['enabled'] = strlen($user['twofa_types']) > 0;
            $usr['extra']['twofa']['twofa_types'] = $user['twofa_types'];
            $usr['extra']['twofa']['twofa_default_type'] = $user['twofa_default_type'];

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

    public function forgotPassword()
    {

    }

    public function resetPassword()
    {

    }

    public function changePassword()
    {

    }

    public function refreshToken(Request $request)
    {
        try {
            $authenticator = new JwtAuthenticator(new JwtEncoder(env('APP_KEY')), $request->auth_user, new Role);
            if (!$token = $authenticator->authenticate($request->auth_user)) {
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
