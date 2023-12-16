<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\Feedback;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class FeedbackController
{
    private $method;
    private $user;
    private $authenticator;
    private $feedback;

    public function __construct($method = "show")
    {
        $this->method = $method;
        $this->user = new User();
        $this->feedback = new Feedback();
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
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            $is_admin = $this->authenticator->verifyRole($this->user, 'admin');

            if (!$this->feedback->find((int)$id))
                return JsonResponse::notFound("unable to retrieve feedback");
                
            if ($is_admin === false && $this->feedback->user_id != $this->user->id) {
                return JsonResponse::unauthorized("you can't view this feedback");
            }

            return JsonResponse::ok("feedback retrieved success", $this->feedback->toArray());
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
                $feedbacks = $this->feedback->all();
            } else {
                $feedbacks = $this->feedback->findBy("user_id", $this->user->id);
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

    private function list_user(string $id)
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't view this user's feedbacks");
            }
            $id = sanitize_data($id);
            $feedbacks = $this->feedback->findBy("user_id", $id);
            
            if (!$feedbacks)
                return JsonResponse::ok("no feedback found in list", []);

            return JsonResponse::ok("feedbacks retrieved success", $feedbacks);
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
                return JsonResponse::unauthorized("only users can create feedback");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'title' => 'required|string',
                'description' => 'required|string',
                'pair' => 'required|string',
                'image' => 'required|string',
                'date' => 'sometimes|string',
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $body['user_id'] = $this->user->id;
            $image = base64_decode($body['image']);
            $path = storage_path(). 'files/uploads/feedbacks/';
            
            if (!file_exists($path))
                mkdir($path, 0777, true);
            
            $target_file = uniqid(). '.jpg';
            $body['image'] = "/public/uploads/feedbacks/".$target_file;

            file_put_contents($path . $target_file, $image);

            if (!$feedback = $this->feedback->create($body)) {
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
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
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
            if (!$this->feedback->find($id)) {
                return JsonResponse::notFound('feedback not found');
            }
            if (isset($body['image'])) {
                $prev_image = $this->feedback->image;
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

            if (!$this->feedback->update($body, (int)$id)) {
                return JsonResponse::notFound("unable to update feedback");
            }

            return JsonResponse::ok("feedback updated successfull", $this->feedback->toArray());
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
        return JsonResponse::ok('route not yet implemented');
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'user')) {
                return JsonResponse::unauthorized("only users can create feedback");
            }
            $id = sanitize_data($id);

            // echo "got to pass login";
            if (!$this->feedback->delete((int)$id)) {
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
