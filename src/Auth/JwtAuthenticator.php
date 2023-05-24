<?php

namespace Basttyy\FxDataServer\Auth;

use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use React\Promise\Promise;
use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\Exception\ExpiredToken;
use Firebase\JWT\ExpiredException;
use Kreait\Firebase\Exception\Auth\InvalidCustomToken;
use Lcobucci\JWT\UnencryptedToken;

use function React\Async\await;
use function React\Promise\reject;
use function React\Promise\resolve;

final class JwtAuthenticator
{
    private const HEADER_VALUE_PATTERN = "/Bearer\s+(.*)$/i";

    private $encoder;
    private $user;
    private $role;

    // variables used for jwt
    private $key;
    private $iss;
    private $aud;

    public function __construct(JwtEncoder $encoder, User $user, Role $role)
    {
        $this->key = env('JWT_KEY');
        $this->iss = env('JWT_ISS');
        $this->aud = env('JWT_AUD');
        $this->encoder = $encoder;
        $this->user = $user;
        $this->role = $role;
    }

    /**
     * Verify the user role against an (array|string) of role(s)
     * 
     * @param User $request
     * @param array|string $_role
     * @param bool $return_bool
     * 
     * @return PromiseInterface<bool|User>
     */
    public function verifyRole($user, $_role, $return_bool = true)
    {
        if (!is_array($_role)) {
            $_role = [$_role];
        }
        $role = $this->role->where('id', $user->role_id)->first()->then(
            function ($role) use ($_role) {
                //print_r($role);
                if (Arr::exists($_role, $role['name'], true)) {
                    return true;
                }
                return false;
            },
            function ($err) {
                return $err;
            }
        );
        return $role;
    }

    /**
     * validate function
     *
     * @param ServerRequestInterface $request
     * @return bool|User
     */
    public function validate($request)
    {
        $jwt = $this->extractToken($request);
        if (empty($jwt)) {
            return false;
        }

        if (is_null($payload = $this->encoder->decode($jwt))) {
            return false;
        }
        $user = $this->user->fill((array)$payload->data);

        return $user;
    }

    /**
     * validate function for firebase
     *
     * @param ServerRequestInterface $request
     * @param string $firebase_token
     * @return PromiseInterface<bool|string>
     */
    public function validateFirebase($request, $firebase_token)
    {
        $jwt = $this->extractToken($request);
        if (empty($jwt)) {
            return resolve(false);
        }
        if (is_null($payload = $this->encoder->decode($jwt))) {
            return resolve(false);
        }

        $user = $this->user->fill((array)$payload->data);
        return $this->authenticate($user, $firebase_token)->then(
            function ($token){
                return $token;
            },
            function ($ex){
                return $ex;
            }
        );    
    }

    private function extractToken(ServerRequestInterface $request): ?string
    {
        $auth_header = $request->getHeader('Authorization');
        if (empty($auth_header)) {
            return null;
        }

        $auth_token = sanitize_data($auth_header[0]);
        if (empty($auth_token)) {
            return null;
        }

        if (preg_match(self::HEADER_VALUE_PATTERN, $auth_token, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * uses firebase token to authenticate and generate a user's token
     *
     * @param User $user
     * @param string $firebase_token
     * @return PromiseInterface<Exception|string>
     */
    public function authenticate(User $user, string $firebase_token)
    {
        return new Promise(function ($resolve, $reject) use ($user, $firebase_token) {
            $factory = (new Factory)->withServiceAccount(env("FIREBASE_CREDENTIALS"));
            $firebaseAuth = $factory->createAuth();

            try {
                $verifiedIdToken = $firebaseAuth->verifyIdToken($firebase_token, true);
            } catch (InvalidCustomToken | ExpiredException $e) {
                $resolve($e);
                return;
            }

            $uid = $verifiedIdToken->claims()->get('sub');

            try {
                $firebaseAuth->getUser($uid);
            } catch (UserNotFound $e) {
                $resolve($e);
                return;
            }

            $issued_at = time();
            $expiration_time = $issued_at + (60 * 60);      //valid for one hour
            $not_before = $issued_at - 5;

            $token = $this->encoder->encode([
                "iss" => $this->iss,
                "aud" => $this->aud,
                "iat" => $issued_at,
                "nbf" => $not_before,
                "exp" => $expiration_time,
                'data' => [
                    "id" => $user->id,
                    "firstname" => $user->firstname,
                    "lastname" => $user->lastname,
                    "email" => $user->email,
                    "role_id" => $user->role_id,
                ]
            ], $this->key);
            echo ("this is token ".$token);
            $resolve($token);
        });
    }
}
