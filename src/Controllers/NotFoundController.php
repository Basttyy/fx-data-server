<?php
namespace Basttyy\FxDataServer\Controllers;

use Basttyy\FxDataServer\libs\JsonResponse;

final class NotFoundController
{
    public function index()
    {
        $request_uri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        if (strtolower($_SERVER['REQUEST_METHOD']) !== "options")
            return JsonResponse::notFound("the requested resource is not found");
    }
}