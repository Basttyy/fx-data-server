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
use Basttyy\FxDataServer\Models\TestSession;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class TestSessionController
{
    public function show(Request $request, string $id)
    {
        $id = sanitize_data($id);
        try {
            $user = $request->auth_user;
            $is_admin = Guard::roleIs($user, 'admin');

            if (!$session = TestSession::getBuilder()->find((int)$id))
                return JsonResponse::notFound("unable to retrieve session");
                
            if ($is_admin === false && $user->id != $session->user_id) {
                return JsonResponse::unauthorized("you can't view this session");
            }
            $session = $session->toArray();
            $session['chart'] = is_null($session['chart']) | $session['chart'] === '' ? $session['chart'] : gzuncompress($session['chart']);

            return JsonResponse::ok("session retrieved success", $session);
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
            $is_admin = Guard::roleIs($user, 'admin');

            $builder = TestSession::getBuilder();
            if ($is_admin) {
                $sessions = $builder->all();
            } else {
                $sessions = $builder->findBy("user_id", $user->id, select: TestSession::listkeys);
            }
            if (!$sessions)
                return JsonResponse::ok("no testsession found in list", []);

            return JsonResponse::ok("test sessions retrieved success", $sessions);
        } catch (PDOException $e) {
            logger()->info($e->getMessage(), $e->getTrace());
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            logger()->info($e->getMessage(), $e->getTrace());
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function list_user(Request $request, string $id)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't view this user's test sessions");
            }
            $id = sanitize_data($id);
            $sessions = TestSession::getBuilder()->findBy("user_id", $id, select: TestSession::listkeys);
            
            if (!$sessions)
                return JsonResponse::ok("no testsession found in list", []);

            return JsonResponse::ok("test sessions retrieved success", $sessions);
        } catch (PDOException $e) {
            logger()->info($e->getMessage(), $e->getTrace());
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            logger()->info($e->getMessage(), $e->getTrace());
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
                return JsonResponse::unauthorized("only users can create test session");
            }
            
            $body = sanitize_data($request->input());

            $uis = TestSession::TV. ', '. TestSession::KLINE;

            if ($validated = Validator::validate($body, [
                'starting_bal' => 'required|float',
                'current_bal' => 'required|float',
                'strategy_id' => 'required|int|exist:strategies,id',
                'pairs' => 'required|string',
                'pair' => 'required|string',
                'start_date' => 'required|string',
                'end_date' => 'required|string',
                'chart_timestamp' => 'required|int',
                'chart_ui' => "required|string|in:$uis"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $body['user_id'] = $user->id;
            $body['equity'] = $body['current_bal'];

            if (!$session = TestSession::getBuilder()->create($body)) {
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

    public function update(Request $request, string $id)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("only users can create test session");
            }
            
            $body = sanitize_data($request->input());
            $id = sanitize_data($id);
            $uis = TestSession::TV. ', '. TestSession::KLINE;

            if ($validated = Validator::validate($body, [
                'starting_bal' => 'sometimes|float',
                'current_bal' => 'sometimes|float',
                'equity' => 'sometimes|float',
                'strategy_id' => 'sometimes|int|exist:strategies,id',
                'pairs' => 'sometimes|string',
                'pair' => 'sometimes|string',
                'chart' => 'sometimes|string',
                'chart_timestamp' => 'sometimes|integer',
                'start_date' => 'sometimes|string',
                'end_date' => 'sometimes|string',
                'chart_ui' => "sometimes|string|in:$uis"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (isset($body['chart'])) {
                $body['chart'] = gzcompress($body['chart']);
            }

            if (!$session = TestSession::getBuilder()->update($body, (int)$id)) {
                return JsonResponse::noContent();
            }
            $session = $session->toArray(select: $session->listkeys);

            return JsonResponse::ok("test session updated successfully", $session);
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
                return JsonResponse::unauthorized("only users can create session");
            }
            $id = sanitize_data($id);

            if (!TestSession::getBuilder()->delete((int)$id)) {
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
