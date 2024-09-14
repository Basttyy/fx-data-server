<?php

namespace App\Http\Middlewares;

use Eyika\Atom\Framework\Http\Request;

class WebAuthMiddleware {
    public function handle(Request $request) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /devops/login');
            exit();
        }

        return false;
    }
}
