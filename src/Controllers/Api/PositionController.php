<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Position;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\TestSession;
use Basttyy\FxDataServer\Models\User;
use DateTime;
use Exception;
use LogicException;
use PDOException;

final class PositionController
{
    private $method;
    private $user;
    private $authenticator;
    private $position;
    private $session;

    public function __construct($method = "show")
    {
        $this->method = $method;
        $this->user = new User();
        $this->position = new Position();
        $this->session = new TestSession();
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role();
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
    }

    public function __invoke(string $id = null, string $tporsl = null)
    {
        switch ($this->method) {
            case 'show':
                $resp = $this->show($id);
                break;
            case 'list':
                $resp = $this->list();
                break;
            case 'list_user':
                $resp = $this->list_user($id);
                break;
            case 'create':
                $resp = $this->create();
                break;
            case 'update':
                $resp = $this->update($id);
                break;
            case 'unsetslortp':
                $resp = $this->unsetSLorTP($id, $tporsl);
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
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            $is_admin = $this->authenticator->verifyRole($this->user, 'admin');

            if (!$this->position->find((int)$id))
                return JsonResponse::notFound("unable to retrieve position");

            if (!$is_admin && $this->position->user_id !== $this->user->id) {
                return JsonResponse::unauthorized("you can't view this position");
            }
                
            if ($is_admin === false && $this->position->user_id != $this->user->id) {
                return JsonResponse::unauthorized("you can't view this position");
            }

            return JsonResponse::ok("position retrieved success", $this->position->toArray());
        } catch (PDOException $e) {
            consoleLog(0, $e->getMessage().'  '. $e->getTraceAsString());
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
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            $is_admin = $this->authenticator->verifyRole($this->user, 'admin');
            $query = isset($_GET) ? sanitize_data($_GET): [];

            if ($is_admin) {
                if (count($query))
                    $positions = $this->position->findByArray(array_keys($query), array_values($query), 'OR');
                else
                    $positions = $this->position->all();
            } else {
                if (count($query)) {
                    $keys = array_merge(array_keys($query), ['user_id']);
                    $values = array_merge(array_values($query), [$this->user->id]);
                    $positions = $this->position->findByArray($keys, $values, 'OR');
                } else {
                    $positions = $this->position->findBy("user_id", $this->user->id);
                }
            }
            if (!$positions)
                return JsonResponse::ok("no position found in list", []);

            return JsonResponse::ok("positions retrieved success", $positions);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function list_user(string $id)
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't view this user's positions");
            }
            $id = sanitize_data($id);
            if (!$positions = $this->position->findBy("user_id", $id))
                return JsonResponse::ok("no positions found in list", []);

            return JsonResponse::ok("positions retrieved success", $positions);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
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

            if (!$this->authenticator->verifyRole($this->user, 'user')) {
                return JsonResponse::unauthorized("only users can open a position");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            $actions = Position::BUY.', '.Position::SELL.', '. Position::BUY_LIMIT.', '. Position::BUY_STOP.', '. Position::SELL_LIMIT.', '. Position::SELL_STOP;

            logger()->info("the body is", $body);
            if ($validated = Validator::validate($body, [
                'test_session_id' => 'required|int',
                'action' => "required|string|in:$actions",
                'lotsize' => 'required|float',
                'entrypoint' => 'required|float',
                'stoploss' => 'sometimes|float',
                'takeprofit' => 'sometimes|float',
                'pl' => 'sometimes|double'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (str_contains($body['action'], 'buy')) {
                if (isset($body['takeprofit']) && $body['takeprofit'] <= $body['entrypoint']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['stoploss']) && $body['stoploss'] >= $body['entrypoint']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['takeprofit']) && isset($body['stoploss']) && $body['stoploss'] > $body['takeprofit']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
            }
            if (str_contains($body['action'], 'sell')) {
                if (isset($body['takeprofit']) && $body['takeprofit'] >= $body['entrypoint']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['stoploss']) && $body['stoploss'] <= $body['entrypoint']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
                if (isset($body['takeprofit']) && isset($body['stoploss']) && $body['stoploss'] < $body['takeprofit']) {
                    return JsonResponse::badRequest('invalid entry or takeprofit or stoploss levels');
                }
            }

            if (!$this->session->find($body['test_session_id'])) {
                return JsonResponse::badRequest('selected test session does not exist');
            }

            $body['user_id'] = $this->user->id;
            if (in_array($body['action'], [Position::BUY, Position::SELL])) {
                $date = new DateTime();
                $body['entrytime'] = $date->format('Y-m-d H:i:s.u');
            }
            logger()->info("the body is now", $body);

            if (!$position = $this->position->create($body)) {
                return JsonResponse::serverError("unable to create position");
            }

            return JsonResponse::ok("position creation successful", $position);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('position already exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    private function unsetSLorTP(string $id, string $tporsl)
    {
        try {
            $id = sanitize_data($id);
            $tporsl = sanitize_data($tporsl);
            $types = ['tp', 'sl'];

            if ($validated = Validator::validate(['tporsl' => $tporsl], [
                'tporsl' => "required|string|in:$types"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$this->position->find($id)) {
                return JsonResponse::notFound("position does not exist");
            }

            if ($this->position->user_id !== $this->user->id) {
                return JsonResponse::unauthorized("you can't update this position");
            }

            if (!$this->position->update([$tporsl => null], (int)$id)) {
                return JsonResponse::notFound("unable to unset position $tporsl");
            }

            return JsonResponse::ok("position $tporsl unset successfully", $this->position->toArray());
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

            if (!$this->authenticator->verifyRole($this->user, 'user')) {
                return JsonResponse::unauthorized("only users can update position");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            $id = sanitize_data($id);
            $actions = Position::BUY.', '.Position::SELL.', '. Position::BUY_LIMIT.', '. Position::BUY_STOP.', '. Position::SELL_LIMIT.', '. Position::SELL_STOP;
            $closetypes = Position::SL.', '. Position::TP.', '. Position::BE.', '. Position::MANUAL_CLOSE.', '.Position::CANCEL;

            if ($validated = Validator::validate($body, [
                'action' => "sometimes|string|in:$actions",
                'entrypoint' => 'sometimes|float',
                'exitpoint' => 'sometimes|float',
                'stoploss' => 'sometimes|float',
                'takeprofit' => 'sometimes|float',
                'lotsize' => 'sometimes|float',
                'pips' => 'sometimes|float',
                'pl' => 'sometimes|double',
                'partials' => 'sometimes|string',
                'exittype' => "sometimes|string|in:$closetypes"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$this->position->find($id)) {
                return JsonResponse::notFound("position does not exist");
            }

            if ($this->position->user_id !== $this->user->id) {
                return JsonResponse::unauthorized("you can't update this position");
            }

            if (isset($body['action']) && in_array($body['action'], [Position::BUY, Position::SELL])) {
                $date = new DateTime();
                $body['entrytime'] = $date->format('Y-m-d H:i:s.u');
            }
            if (isset($body['exittype']) && isset($body['exitpoint'])) {
                $date = new DateTime();
                $body['exittime'] = $date->format('Y-m-d H:i:s.u');
            }
            // if (isset($body['pips'])) {
            //     $body['pl'] = 
            // }

            /** there should be a trasaction between here */

            if (!$this->position->update($body, (int)$id)) {
                return JsonResponse::notFound("unable to update position");
            }
            // if (isset($body['exittype']) && isset($body['action']) && in_array($body['action'], [Position::BUY, Position::SELL])) {
            //     if ($this->session->find($this->position->test_session_id)) {
            //         $balance = $this->session->current_bal + $body['pl'];
            //         $this->session->update(['balance' => $balance]);
            //     }
            // }
            /** and here */

            return JsonResponse::ok("position updated successfully", $this->position->toArray());
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

            if (!$this->authenticator->verifyRole($this->user, 'user')) {
                return JsonResponse::unauthorized("only users can create strategy");
            }
            $id = sanitize_data($id);

            if (!$this->position->find($id)) {
                return JsonResponse::notFound("position does not exist");
            }

            if ($this->position->user_id !== $this->user->id) {
                return JsonResponse::unauthorized("you can't update this position");
            }

            if (!$this->position->delete((int)$id)) {
                return JsonResponse::notFound("unable to delete position or position not found");
            }

            return JsonResponse::ok("position deleted successfully");
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
