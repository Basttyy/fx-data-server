<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\Strategy;
use Basttyy\FxDataServer\Models\TestSession;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class TestSessionController
{
    private $method;
    private $user;
    private $authenticator;
    private $strategy;
    private $session;

    public function __construct($method = "show")
    {
        $this->method = $method;
        $this->user = new User();
        $this->session = new TestSession();
        $this->strategy = new Strategy();
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
            case 'list_user':
                $resp = $this->list_user($id);
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
            $is_admin = $this->authenticator->verifyRole($user, 'admin');

            if (!$this->session->find((int)$id))
                return JsonResponse::notFound("unable to retrieve session");
                
            if ($is_admin === false && $user->id != $this->session->user_id) {
                return JsonResponse::unauthorized("you can't view this session");
            }

            return JsonResponse::ok("session retrieved success", $this->strategy->toArray());
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
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            $is_admin = $this->authenticator->verifyRole($this->user, 'admin');

            if ($is_admin) {
                $sessions = $this->session->all();
            } else {
                $sessions = $this->session->findBy("user_id", $this->user->id);
            }
            if (!$sessions)
                return JsonResponse::ok("no testsession found in list", []);

            return JsonResponse::ok("test sessions retrieved success", $sessions);
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
                return JsonResponse::unauthorized("you can't view this user's test sessions");
            }
            $id = sanitize_data($id);
            $sessions = $this->session->findBy("user_id", $id);
            
            if (!$sessions)
                return JsonResponse::ok("no testsession found in list", []);

            return JsonResponse::ok("test sessions retrieved success", $sessions);
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
                return JsonResponse::unauthorized("only users can create strategy");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'starting_bal' => 'required|numeric',
                'current_bal' => 'required|numeric',
                'strategy_id' => 'required|int',
                'pair' => 'required|string',
                'start_date' => 'required|string',
                'end_date' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (isset($body['strategy_id']) && !$this->strategy->find($body['strategy_id'])) {
                return JsonResponse::badRequest('this strategy does not exist');
            }

            $body['user_id'] = $this->user->id;

            if (!$session = $this->session->create($body)) {
                return JsonResponse::serverError("unable to create test session");
            }

            return JsonResponse::ok("test session creation successful", $session);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('test session already exist');
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
                return JsonResponse::unauthorized("only users can create strategy");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            $id = sanitize_data($id);

            if ($validated = Validator::validate($body, [
                'starting_bal' => 'sometimes|string',
                'current_bal' => 'sometimes|string',
                'strategy_id' => 'sometimes|string',
                'pair' => 'sometimes|string',
                'chart' => 'sometimes|string',
                'chart_timestamp' => 'sometimes|integer',
                'start_date' => 'sometimes|string',
                'end_date' => 'sometimes|string',
                'current_date' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }
            if (isset($body['strategy_id']) && !$this->strategy->find($body['strategy_id'])) {
                return JsonResponse::badRequest('this strategy does not exist');
            }

            if (!$this->session->update($body, (int)$id)) {
                return JsonResponse::serverError("unable to update test session");
            }

            return JsonResponse::ok("test session updated successfully", $this->session->toArray());
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
                return JsonResponse::unauthorized("only users can create session");
            }
            $id = sanitize_data($id);

            if (!$this->session->delete((int)$id)) {
                return JsonResponse::notFound("unable to delete test session or session not found");
            }

            return JsonResponse::ok("test session deleted successfully");
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
