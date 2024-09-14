<?php

namespace App\Http\Middlewares;

use Exception;
use Eyika\Atom\Framework\Http\Contracts\MiddlewareInterface;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Auth\Guard;
use PDOException;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): bool
    {
        try {
            if (!$user = Guard::tryToAuthenticate()) {
                return JsonResponse::unauthorized();
            }
            $user = $user->find($user->id, false);
            $request->auth_user = $user;
        } catch (PDOException $e) {
            // consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
        } catch (Exception $e) {
            // consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
        }
        return false;
    }
}
