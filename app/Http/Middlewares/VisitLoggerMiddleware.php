<?php

namespace App\Http\Middlewares;

use App\Models\Visit;
use Exception;
use Eyika\Atom\Framework\Http\Contracts\MiddlewareInterface;
use Eyika\Atom\Framework\Http\Request;
use PDOException;

class VisitLoggerMiddleware implements MiddlewareInterface
{
    public function handle(Request $request): bool
    {
        try {
            if (strtolower($request->server('REQUEST_METHOD')) !== "options") {
                $data['ip'] = $request->server('REMOTE_ADDR') ?? "";
                $data['method'] = $request->server('REQUEST_METHOD') ?? "";
                $data['origin'] = $request->server('HTTP_ORIGIN') ?? "";
                $data['uripath'] = $request->server('REQUEST_URI') ?? "";
    
                if ($request->server('CONTENT_LENGTH') && $request->server('CONTENT_LENGTH') > env('CONTENT_LENGTH_MIN')) {
                    $inputJSON = file_get_contents('php://input');
                    $data['body'] = gzdeflate(serialize(sanitize_data(json_decode($inputJSON, true))));
                }
    
                Visit::getBuilder()->create($data);
            }
        } catch (PDOException $e) {
            // consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
        } catch (Exception $e) {
            // consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
        }
        return false;
    }
}
