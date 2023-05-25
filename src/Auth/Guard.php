<?php

namespace Basttyy\FxDataServer\Auth;

use Basttyy\FxDataServer\Libraries\Helpers\JsonResponse;
use Basttyy\FxDataServer\Models\User;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

final class Guard
{
    /**
     * Try to validate a user
     * 
     * @param array|string $role
     * @param JwtAuthenticator $authenticator
     * 
     * @return bool|User
     */
    public static function tryToAuthenticate($authenticator)
    {
        return $authenticator->validate();
    }

    /**
     * Verify a user's role using a(n) string/array of roles
     * 
     * @param array|string $role
     * @param User $user
     * @param JwtAuthenticator $authenticator
     * 
     * @return bool|User
     */
    public static function roleIs($role, $user, $authenticator)
    {
        return $authenticator->verifyRole($user, $role);
    }
}
