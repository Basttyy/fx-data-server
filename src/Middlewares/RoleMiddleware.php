<?php

namespace Basttyy\FxDataServer\Middlewares;

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\libs\Interfaces\MiddlewareInterface;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Exception;
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
