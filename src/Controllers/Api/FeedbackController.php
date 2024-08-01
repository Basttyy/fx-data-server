<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Feedback;
use Exception;
use LogicException;
use PDOException;

final class FeedbackController
{
    public function show(Request $request, string $id)
    {
        $id = sanitize_data($id);
        try {
            $user = $request->auth_user;
            $is_admin = Guard::roleIs($user, 'admin');

            if (!$feedback = Feedback::getBuilder()->find((int)$id))
                return JsonResponse::notFound("unable to retrieve feedback");
                
            if ($is_admin === false && $feedback->user_id != $user->id) {
                return JsonResponse::unauthorized("you can't view this feedback");
            }

            return JsonResponse::ok("feedback retrieved success", $feedback->toArray());
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
                $feedbacks = Feedback::getBuilder()->all();
            } else {
                $feedbacks = Feedback::getBuilder()->findBy("user_id", $user->id);
            }
            if (!$feedbacks)
                return JsonResponse::ok("no feedback found in list", []);

            return JsonResponse::ok("feedbacks retrieved success", $feedbacks);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function list_user(Request $request, string $id)
    {
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't view this user's feedbacks");
            }
            $id = sanitize_data($id);
            $feedbacks = Feedback::getBuilder()->findBy("user_id", $id);
            
            if (!$feedbacks)
                return JsonResponse::ok("no feedback found in list", []);

            return JsonResponse::ok("feedbacks retrieved success", $feedbacks);
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
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("only users can create feedback");
            }
            
            $body = sanitize_data($request->input());

            if ($validated = Validator::validate($body, [
                'title' => 'required|string',
                'description' => 'required|string',
                'pair' => 'required|string',
                'image' => 'required|string',
                'date' => 'sometimes|string',
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $body['user_id'] = $user->id;
            $image = base64_decode($body['image']);
            $path = storage_path(). 'files/uploads/feedbacks/';
            
            if (!file_exists($path))
                mkdir($path, 0777, true);
            
            $target_file = uniqid(). '.jpg';
            $body['image'] = "/public/uploads/feedbacks/".$target_file;

            file_put_contents($path . $target_file, $image);

            if (!$feedback = Feedback::getBuilder()->create($body)) {
                return JsonResponse::serverError("unable to create feedback");
            }

            return JsonResponse::created("feedback creation successful", $feedback);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('feedback already exist');
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
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            
            $body = sanitize_data($request->input());
            $id = sanitize_data($id);
            $status = Feedback::PENDING.', '.Feedback::REOPENED.', '.Feedback::RESOLVED.', '.Feedback::RESOLVING.', '.Feedback::STALED;

            if ($validated = Validator::validate($body, [
                'title' => 'sometimes|string',
                'description' => 'sometimes|string',
                'pair' => 'sometimes|string',
                'image' => 'sometimes|string',
                'date' => 'sometimes|string',
                'status' => "sometimes|string|in:$status"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }
            if (!$feedback = Feedback::getBuilder()->find($id)) {
                return JsonResponse::notFound('feedback not found');
            }
            if (isset($body['image'])) {
                $prev_image = $feedback->image;
                $image = base64_decode($body['image']);
                $path = storage_path(). 'files/uploads/feedbacks/';
                
                if (!file_exists($path))
                    mkdir($path, 0777, true);
                
                $target_file = uniqid(). '.jpg';
    
                if (file_put_contents($path . $target_file, $image)) {
                    unlink(storage_path().'files'. str_replace('/storage', '', $prev_image));
                    $body['image'] = "/public/uploads/feedbacks/".$target_file;
                }
            }

            if (!$feedback->update($body, (int)$id)) {
                return JsonResponse::notFound("unable to update feedback");
            }

            return JsonResponse::ok("feedback updated successfull", $feedback->toArray());
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
        return JsonResponse::ok('route not yet implemented');
        try {
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("only users can create feedback");
            }
            $id = sanitize_data($id);

            if (!Feedback::getBuilder()->delete((int)$id)) {
                return JsonResponse::notFound("unable to delete feedback or feedback not found");
            }

            return JsonResponse::ok("feedback deleted successfully");
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
