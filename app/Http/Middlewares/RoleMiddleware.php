<?php

namespace App\Http\Middlewares;

use Exception;
use Eyika\Atom\Framework\Http\Contracts\MiddlewareInterface;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Auth\Guard;
use PDOException;

class RoleMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, string ...$roles): bool
    {
        try {
            $failed = true;

            foreach ($roles as $role) {
                if (Guard::roleIs($request->auth_user, $role)) {
                    $failed = false;
                    break;
                }
            }
            if ($failed)
                return JsonResponse::unauthorized();
        } catch (PDOException $e) {
            // consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
        } catch (Exception $e) {
            // consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
        }
        return false;
    }
}
