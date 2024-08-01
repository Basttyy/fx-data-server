<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\Strategy;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class StrategyController
{
    public function show(Request $request, string $id)
    {
        $id = sanitize_data($id);
        try {
            $user = $request->auth_user;
            $is_admin = Guard::roleIs($user, 'admin');

            if (!$strategy = Strategy::getBuilder()->find((int)$id))
                return JsonResponse::notFound("unable to retrieve strategy");
                
            if ($is_admin === false && $strategy->user_id != $user->id) {
                return JsonResponse::unauthorized("you can't view this strategy");
            }

            return JsonResponse::ok("strategy retrieved success", $strategy->toArray());
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (LogicException $e) {
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
    
    public function list(Request $request)
    {
        try {
            $user = $request->auth_user;

            if (Guard::roleIs($user, 'admin')) {
                $strategies = Strategy::getBuilder()->all();
            } else {
                $strategies = Strategy::getBuilder()->findBy("user_id", $user->id);
            }
            if (!$strategies)
                return JsonResponse::ok("no strategy found in list", []);

            return JsonResponse::ok("strategies retrieved success", $strategies);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function listUser(Request $request, string $id)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't view this user's strategies");
            }
            $id = sanitize_data($id);
            $strategies = Strategy::getBuilder()->findBy("user_id", $id);
            
            if (!$strategies)
                return JsonResponse::ok("no strategy found in list", []);

            return JsonResponse::ok("strategies retrieved success", $strategies);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function create(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("only users can create strategy");
            }
            
            $body = sanitize_data($request->input());

            if ($validated = Validator::validate($body, [
                'name' => 'required|string',
                'description' => 'required|string',
                'logo' => 'sometimes|string',
                'pairs' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $body['user_id'] = $user->id;

            if (!$strategy = Strategy::getBuilder()->create($body)) {
                return JsonResponse::serverError("unable to create strategy");
            }

            return JsonResponse::ok("strategy creation successful", $strategy);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('strategy already exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("only users can create strategy");
            }
            
            $body = sanitize_data($request->input());
            $id = sanitize_data($id);

            if ($validated = Validator::validate($body, [
                'name' => 'sometimes|string',
                'description' => 'sometimes|string',
                'logo' => 'sometimes|string',
                'pairs' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$strategy = Strategy::getBuilder()->update($body, (int)$id)) {
                return JsonResponse::notFound("unable to update strategy");
            }

            return JsonResponse::ok("strategy updated successfull", $strategy->toArray());
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

    public function delete(Request $request, int $id)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("only users can create strategy");
            }
            $id = sanitize_data($id);

            if (!Strategy::getBuilder()->delete((int)$id)) {
                return JsonResponse::notFound("unable to delete strategy or strategy not found");
            }

            return JsonResponse::ok("strategy deleted successfully");
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
