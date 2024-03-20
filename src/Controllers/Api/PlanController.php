<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Plan;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class PlanController
{
    private $method;
    private $user;
    private $authenticator;
    private $plan;

    public function __construct($method = "show")
    {
        $this->method = $method;
        $this->user = new User();
        $this->plan = new Plan();
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
            if (!$this->plan->find((int)$id))
                return JsonResponse::notFound("unable to retrieve plan");

            return JsonResponse::ok("plan retrieved success", $this->plan->toArray());
        } catch (PDOException $e) {
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
            $plans = $this->plan->all();
            if (!$plans)
                return JsonResponse::ok('no plan found in list', []);

            return JsonResponse::ok("plans retrieved success", $plans);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function create()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't create a plan");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            $status = Plan::DISABLED.', '.Plan::ENABLED;
            $intervals = implode(', ', Plan::INTERVALS);

            if ($validated = Validator::validate($body, [
                'name' => 'required|string',
                'description' => 'required|string',
                'price' => 'required|numeric',
                'status' => "sometimes|string|in:$status",
                'features' => 'required|string',
                'duration_interval' => "required|string|in:$intervals"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$plan = $this->plan->create($body)) {
                return JsonResponse::serverError("unable to create plan");
            }

            ///TODO:: send campaign notification to users/subscribers about the new plan

            return JsonResponse::ok("plan creation successful", $plan);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('plan already exist');
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
            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            $id = sanitize_data($id);

            if (!$this->authenticator->verifyRole($user, 'admin')) {
                return JsonResponse::unauthorized("you can't update a plan");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            $status = Plan::DISABLED.', '.Plan::ENABLED;
            $intervals = implode(', ', Plan::INTERVALS);
            
            if ($validated = Validator::validate($body, [
                'name' => 'sometimes|string',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric',
                'status' => "sometimes|string|in:$status",
                'features' => 'sometimes|string',
                'duration_interval' => "sometimes|string|in:$intervals"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$this->plan->update($body, (int)$id)) {
                return JsonResponse::notFound("unable to update plan not found");
            }

            return JsonResponse::ok("plan updated successfull", $this->plan->toArray());
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
            $id = sanitize_data($id);

            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't delete a plan");
            }

            // echo "got to pass login";
            if (!$this->plan->delete((int)$id)) {
                return JsonResponse::notFound("unable to delete plan or plan not found");
            }

            return JsonResponse::ok("plan deleted successfull");
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
