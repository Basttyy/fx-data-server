<?php
namespace App\Http\Controllers;

use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;

final class NotFoundController
{
    public function index(Request $request)
    {
        $request_uri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        if (strtolower($_SERVER['REQUEST_METHOD']) !== "options")
            return JsonResponse::notFound("the requested resource is not found");
    }
}