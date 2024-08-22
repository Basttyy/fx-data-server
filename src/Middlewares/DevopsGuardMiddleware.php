<?php

namespace Basttyy\FxDataServer\Middlewares;

use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;

class DevopsGuardMiddleware {
    public function handle(Request $request) {
        if (!$request->cookie('btfx-devops') || $request->cookie('btfx-devops') !== env('BTFX_DEVOPS_TOKEN')) {
            echo "<h3>Ooop's, you seem lost</h3></br><span>click <a href='https://backtestfx.com'>backtestfx</a> to go to the site</span>";
            return true;
        }

        return false;
    }
}
