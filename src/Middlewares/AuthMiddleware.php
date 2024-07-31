<?php

namespace Basttyy\FxDataServer\Middlewares;

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\Interfaces\MiddlewareInterface;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
use PDOException;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): bool
    {
        try {
            $user = new User();
            $encoder = new JwtEncoder(env('APP_KEY'));
            $role = new Role();
            $authenticator = new JwtAuthenticator($encoder, $user, $role);
            if (!Guard::tryToAuthenticate($authenticator)) {
                return JsonResponse::unauthorized();
            }
            $user = $user->find($user->id);
            $request->auth_user = $user;
        } catch (PDOException $e) {
            // consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
        } catch (Exception $e) {
            // consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
        }
        return false;
    }
}
