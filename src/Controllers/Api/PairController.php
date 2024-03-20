<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\Pair;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class PairController
{
    private $method;
    private $user;
    private $authenticator;
    private $pair;

    public function __construct($method = "show")
    {
        $this->method = $method;
        $this->user = new User();
        $this->pair = new Pair();
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role();
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
    }

    public function __invoke(string $id = null, string $query = null)
    {
        switch ($this->method) {
            case 'show':
                $resp = $this->show($id);
                break;
            case 'list':
                $resp = $this->list();
                break;
            case 'listonlypair':
                $resp = $this->listonlypair();
                break;
            case 'query':
                $resp = $this->query($id);
                break;
            case 'create':
                $resp = $this->create();
                break;
            case 'update':
                $resp = $this->update($id);
                break;
            case 'delete':
                $resp = $this->delete($id);
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
            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->pair->find((int)$id))
                return JsonResponse::notFound("unable to retrieve pair");

            return JsonResponse::ok("pair retrieved success", $this->pair->toArray(select: $this->pair->pairinfos));
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (LogicException $e) {
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
    
    private function list()
    {
        try {
            if (!$pairs = $this->pair->all())
                return JsonResponse::ok("no pair found in list", []);

            return JsonResponse::ok("pairs retrieved success", $pairs);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
  
    private function listonlypair()
    {
        try {
            if (!$pairs = $this->pair->all(select: $this->pair->pairinfos))
                return JsonResponse::ok("no pair found in list", []);

            return JsonResponse::ok("pairs retrieved success", $pairs);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function query(string $id)
    {
        try {
            $params = sanitize_data($_GET);
            $status = Pair::DISABLED.', '.Pair::ENABLED;

            if ($validated = Validator::validate($params, [
                'name' => 'sometimes|string',
                'searchstring' => 'sometimes|string',
                'exchange' => 'sometimes|string',
                'market' => 'sometimes|string',
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }
            if ($id == 0)
                $select = [];
            else if ($id == 1)
                $select = $this->pair->pairinfos;
            else
                $select = $this->pair->symbolinfos;

            // if (!$pairs = $this->pair->findByArray(array_keys($params), array_values($params), select: $select))
            $searchstring = '';
            $pairs = [];
            if (isset($params['searchstring'])) {
                $searchstring = $params['searchstring'];
                if (!$pairs1 = $this->pair->whereLike('name', "$searchstring")
                            ->orWhereLike('short_name', "$searchstring")
                            ->orWhereLike('ticker', "$searchstring")
                            ->orWhereLike('description', "$searchstring")
                            ->orWhere('status', "$searchstring")
                            // ->orWhere('history_start', $searchstring)
                            // ->orWhere('history_end', $searchstring)
                            ->all(select: $select))
                    return JsonResponse::ok("no piar found in list1", []);
                
                while ($pair = current($pairs1)) {
                    $push = true;
                    if ((isset($params['exchange'])) && $params['exchange'] != '' && $pair['exchange'] != $params['exchange']) {
                        $push = false;
                    }
                    if ((isset($params['market'])) && $params['market'] != '' && $pair['market'] != $params['market']) {
                        $push = false;
                    }
    
                    if ($push)
                        array_push($pairs, $pair);
                    next($pairs1);
                }
            } else if (isset($name)) {
                if (!$pairs = $this->pair->where('name', $params['name'])->all())
                    return JsonResponse::ok("no piar found in list2", []);
            }

            if (!count($pairs))
                return JsonResponse::ok("no piar found in list3", []);

            return JsonResponse::ok("pairs retrieved success", $pairs);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem ".$e->getMessage());
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem ".$e->getMessage());
        }
    }

    private function create()
    {
        try {
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            $status = Pair::DISABLED.', '.Pair::ENABLED;
            $markets = Pair::FX.', '.Pair::COMODITY.', '.Pair::CRYPTO.', '.Pair::STOCKS.', '.Pair::INDEX;

            if ($validated = Validator::validate($body, [
                'name' => 'required|string',
                'description' => 'required|string',
                'status' => "sometimes|string|in:$status",
                'dollar_per_pip' => 'required|numeric',
                'history_start' => 'required|string',
                'history_end' => 'required|string',
                'exchange' => 'sometimes|string',
                'market' => "required|in:$markets",
                'short_name' => 'required|string',
                'ticker' => 'required|string',
                'timezone' => 'required|string|in:'.Pair::TIMEZONES,
                'min_move' => 'required|string',
                'price_precision' => 'required|int',
                'volume_precision' => 'required|int',
                'price_currency' => 'required|string',
                'type' => 'sometimes|string',
                'logo' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$pair = $this->pair->create($body, select: $this->pair->pairinfos)) {
                return JsonResponse::serverError("unable to create pair");
            }

            return JsonResponse::ok("pair creation successful", $pair);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('pair already exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    private function update(string $id)
    {
        try {
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            $id = sanitize_data($id);
            $status = Pair::DISABLED.', '.Pair::ENABLED;
            $markets = Pair::FX.', '.Pair::COMODITY.', '.Pair::CRYPTO.', '.Pair::STOCKS.', '.Pair::INDEX;

            if ($validated = Validator::validate($body, [
                'name' => 'sometimes|string',
                'description' => 'sometimes|string',
                'status' => "sometimes|string|in:$status",
                'dollar_per_pip' => 'sometimes|numeric',
                'history_start' => 'sometimes|string',
                'history_end' => 'sometimes|string',
                'exchange' => 'sometimes|string',
                'market' => "sometimes|in:$markets",
                'short_name' => 'sometimes|string',
                'ticker' => 'sometimes|string',
                'timezone' => 'required|string|in:'.Pair::TIMEZONES,
                'min_move' => 'sometimes|string',
                'price_precision' => 'sometimes|int',
                'volume_precision' => 'sometimes|int',
                'price_currency' => 'sometimes|string',
                'type' => 'sometimes|string',
                'logo' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            // echo "got to pass login";
            if (!$this->pair->update($body, (int)$id)) {
                return JsonResponse::notFound("unable to update pair");
            }

            return JsonResponse::ok("pair updated successfully", $this->pair->toArray(select: $this->pair->pairinfos));
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    private function delete(int $id)
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you don't have this permission");
            }
            $id = sanitize_data($id);

            if (!$this->pair->delete((int)$id)) {
                return JsonResponse::notFound("unable to delete pair or pair not found");
            }

            return JsonResponse::ok("pair deleted successfully");
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }
}
