<?php
namespace Basttyy\FxDataServer\Controllers;

use Basttyy\FxDataServer\libs\JsonResponse;

final class NotFoundController
{
    public function __construct() {
        
    }

    public function __invoke()
    {
        $request_uri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        // if (str_contains($request_uri, 'api') && strtolower($_SERVER['REQUEST_METHOD']) !== "options")
            return JsonResponse::notFound("the requested resource is not found");

        // header('Content-Type: text/html', true, 200);
        // echo file_get_contents($_SERVER['DOCUMENT_ROOT']."/index.html");
    }
}