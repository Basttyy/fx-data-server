<?php
namespace App\Http\Controllers\Api;

use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Validator;
use App\Models\Strategy;
use Exception;
use Eyika\Atom\Framework\Support\Facade\Storage;
use LogicException;
use PDOException;

final class StrategyController
{
    public function show(Request $request, Strategy $strategy)
    {
        try {
            $user = $request->auth_user;
            $is_admin = Guard::roleIs($user, 'admin');
                
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
            
            $body = $request->input();

            if ($validated = Validator::validate($body, [
                'name' => 'required|string',
                'description' => 'required|string',
                'logo' => 'sometimes|string',
                'pairs' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $body['user_id'] = $user->id;
            $image = base64_decode($body['logo']);
            $path = 'uploads/strategies/';
            
            $target_file = uniqid(). '.jpg';
            Storage::put($path . $target_file, $image);
            $body['logo'] = storage('public')->url($path.$target_file);

            if (!$strategy = Strategy::getBuilder()->create($body, false)) {
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

    public function update(Request $request, Strategy $strategy)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

            if ($strategy->user_id != $user->id) {
                return JsonResponse::unauthorized("you can't update this strategy");
            }
            
            $body = $request->input();

            if ($validated = Validator::validate($body, [
                'name' => 'sometimes|string',
                'description' => 'sometimes|string',
                'logo' => 'sometimes|string',
                'pairs' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (isset($body['logo'])) {
                $prev_logo = $strategy->logo;
                $logo = base64_decode($body['logo']);
                $path = 'uploads/strategies/';
                
                $target_file = uniqid(). '.jpg';
    
                if (Storage::put($path . $target_file, $logo)) {
                    Storage::delete(str_replace('/storage', '', $prev_logo));
                    $body['logo'] = storage('public')->url($path.$target_file);
                }
            }

            if (!$strategy = $strategy->update($body, is_protected: false)) {
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

    public function delete(Request $request, Strategy $strategy)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("only users can create strategy");
            }
            if ($strategy->user_id != $user->id) {
                return JsonResponse::unauthorized("you can't delete this strategy");
            }

            $logo = $strategy->logo;

            if (!$strategy->delete()) {
                return JsonResponse::notFound("unable to delete strategy or strategy not found");
            }
            Storage::delete(str_replace('/storage', '', $logo));

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
