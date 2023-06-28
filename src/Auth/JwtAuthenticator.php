<?php

namespace Basttyy\FxDataServer\Auth;

use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Hybridauth\Adapter\AdapterInterface;
use Hybridauth\Hybridauth;

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
     * @param User $user
     * @param array|string $_role
     * @param bool $return_bool
     * 
     * @return bool|User
     */
    public function verifyRole($user, $_role, $return_bool = true)
    {
        if (!is_array($_role)) {
            $_role = [$_role];
        }
        if (!$role = $this->role->orderBy()->findBy('id', $user->role_id)) {
            return false;
        }
        if (Arr::exists($_role, $role[0]['name'], true)) {
            return true;
        }
        return false;
    }

    /**
     * validate function
     *
     * @return bool|User
     */
    public function validate()
    {
        $jwt = $this->extractToken();
        if (empty($jwt)) {
            return false;
        }

        if (str_contains($jwt, "social_login:")) {
            $providers = ['facebook']; //, 'twitter', 'google'];
            $hybridauth = new Hybridauth("{$_SERVER['DOCUMENT_ROOT']}/../hybridauth_config.php");  //, null, new DbStorage('SOCIALAUTH::STORAGE'));

            foreach ($providers as $provider) {
                if ($hybridauth->isConnectedWith($provider)) {
                    $adapter = $hybridauth->getAdapter($provider);
                    break;
                }
                $adapter = null;
            }
            if ($adapter instanceof AdapterInterface) {
                if (!$this->user->find((int)base64_decode(str_replace('social_login:', '', $jwt)))) {
                    return false;
                }
                $user_profile = $adapter->getUserProfile();
                if ($this->user->uuid !== $user_profile->identifier && $this->user->email !== $user_profile->email) {
                    return false;
                }
                return $this->user;
            }
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
     * @param string $social_token
     * @return bool|string
     */
    public function validateSocial($social_token)
    {
        $jwt = $this->extractToken();
        if (empty($jwt)) {
            return false;
        }
        if (is_null($payload = $this->encoder->decode($jwt))) {
            return false;
        }

        $user = $this->user->fill((array)$payload->data);
        return $this->authenticate($user, $social_token);
    }

    private function extractToken(): ?string
    {
        // print_r($_SERVER);
        if (!isset($_SERVER['HTTP_AUTHORIZATION']))
            return null;
        $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
        if (empty($auth_header)) {
            return null;
        }

        $auth_token = sanitize_data($auth_header);
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
     * @param string $password_or_token
     * @return string|bool
     */
    public function authenticate(User $user, string $password_or_token)
    {
        if (!password_verify($password_or_token, $user->password)) {
            return false;
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
        //echo ("this is token ".$token);
        return $token;
    }
}
