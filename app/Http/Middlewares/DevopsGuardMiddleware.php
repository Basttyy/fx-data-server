<?php

namespace App\Http\Middlewares;

use Eyika\Atom\Framework\Http\Contracts\MiddlewareInterface;
use Eyika\Atom\Framework\Http\Request;

class DevopsGuardMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): bool
    {
        if (!$request->cookie('btfx-devops') || $request->cookie('btfx-devops') !== env('BTFX_DEVOPS_TOKEN')) {
            echo "<h3>Ooop's, you seem lost</h3></br><span>click <a href='https://backtestfx.com'>backtestfx</a> to go to the site</span>";
            return true;
        }

        return false;
    }
}
