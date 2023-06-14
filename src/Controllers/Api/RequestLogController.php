<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\Models\RequestLog;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class RequestLogController
{
    private $method;
    private $user;
    private $authenticator;
    private $log;

    public function __construct($method = "show")
    {
        $this->method = $method;
        $this->user = new User();
        $this->log = new RequestLog();
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role();
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
    }

    public function __invoke(string $id = null)
    {
        switch ($this->method) {
            case 'show':
                $resp = $this->show($id);
                break;
            case 'list':
                $resp = $this->list();
                break;
            case 'create':
                $resp = $this->create();
                break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        $resp;
    }

    private function show(string $id)
    {
        $id = sanitize_data($id);
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }

            if (!$this->log->find((int)$id))
                return JsonResponse::notFound("unable to retrieve log");

            return JsonResponse::ok("log retrieved success", $this->log->toArray());
        } catch (PDOException $e) {
            consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
            return JsonResponse::serverError("we encountered a db problem");
        } catch (LogicException $e) {
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function list()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }

            $logs = $this->log->all();
            if (!$logs)
                return JsonResponse::notFound("unable to retrieve logs");

            return JsonResponse::ok("logs retrieved success", $logs);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function create()
    {
        try {            
            $data['ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
            $data['method'] = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : "";
            $data['origin'] = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "";
            $data['uripath'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "";

            if ( $_SERVER['CONTENT_LENGTH'] > env('CONTENT_LENGTH_MIN')) {
                $inputJSON = file_get_contents('php://input');
                $data['body'] = gzdeflate(serialize(sanitize_data(json_decode($inputJSON, true))));
            }

            $this->log->create($data);
        } catch (PDOException $e) {
            consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
        } catch (Exception $e) {
            consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
        }
    }
}
