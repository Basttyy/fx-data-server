<?php

namespace Basttyy\FxDataServer\Middlewares;

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\libs\Interfaces\MiddlewareInterface;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Exception;
use PDOException;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): bool
    {
        try {
            if (!$user = Guard::tryToAuthenticate()) {
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
