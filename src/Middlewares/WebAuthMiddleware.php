<?php

namespace Basttyy\FxDataServer\Middlewares;

use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;

class WebAuthMiddleware {
    public function handle(Request $request) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /devops/login');
            exit();
        }

        return false;
    }
}
