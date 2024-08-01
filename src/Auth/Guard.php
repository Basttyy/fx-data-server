<?php

namespace Basttyy\FxDataServer\Auth;

use Basttyy\FxDataServer\Models\User;

final class Guard
{
    /**
     * Try to validate a user
     * 
     * @return bool|User
     */
    public static function tryToAuthenticate()
    {
        return JwtAuthenticator::validate();
    }

    /**
     * Verify a user's role using a(n) string/array of roles
     * 
     * @param User $user
     * @param array|string $role
     * 
     * @return bool|User
     */
    public static function roleIs($user, $role)
    {
        return JwtAuthenticator::verifyRole($user, $role);
    }
}
