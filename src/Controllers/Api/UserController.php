<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\Exceptions\NotFoundException;
use Basttyy\FxDataServer\Exceptions\QueryException;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;

final class UserController
{
    private $method;
    private $user;
    private $authenticator;

    public function __construct($method = "show")
    {
        $this->method = $method;
        $this->user = new User();
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
            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            $is_admin = $this->authenticator->verifyRole($user, 'admin');

            if (!$is_admin || $user->id !== $id) {
                return JsonResponse::unauthorized("you can't view this user");
            }

            if (!$user = $this->user->find((int)$id))
                return JsonResponse::notFound("unable to retrieve user");

            return JsonResponse::ok("user retrieved success", [
                'data' => $user
            ]);
        } catch (QueryException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function list()
    {
        try {
            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            $is_admin = $this->authenticator->verifyRole($user, 'admin');

            if ($is_admin) {
                $users = $this->user->all();
            } else {
                $users = $this->user->findByArray(["role_id"], [1]);
            }
            if (!$users)
                return JsonResponse::notFound("unable to retrieve users");

            return JsonResponse::ok("users retrieved success", [
                'data' => $users
            ]);
        } catch (QueryException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function create()
    {
        try {
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= 0) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'email' => 'required|string',
                'password' => 'required|string',
                'firstname' => 'required|string',
                'lastname' => 'required|string',
                'username' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            // echo "got to pass login";
            if (!$user = $this->user->create($body)) {
                return JsonResponse::serverError("unable to create user")
            }

            return JsonResponse::ok("user creation successfull", [
                'data' => $user
            ]);
        } catch (QueryException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function update(string $id)
    {
        try {
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= 0) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            $id = sanitize_data($id);
            $is_admin = $this->authenticator->verifyRole($user, 'admin');

            if (!$is_admin || $user->id !== $id) {
                return JsonResponse::unauthorized("you can't update this user");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'email' => 'sometimes|string',
                'password' => 'sometimes|string',
                'firstname' => 'sometimes|string',
                'lastname' => 'sometimes|string',
                'username' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            // echo "got to pass login";
            if (!$user = $this->user->update($body, (int)$id)) {
                return JsonResponse::serverError("unable to update user");
            }

            return JsonResponse::ok("user updated successfull", [
                'data' => $user->toArray()
            ]);
        } catch (QueryException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function delete(int $id)
    {
        try {
            $id = sanitize_data($id);

            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            // $is_admin = $this->authenticator->verifyRole($user, 'admin');

            if ($user->id !== $id) {
                return JsonResponse::unauthorized("you can't delete this user");
            }

            // echo "got to pass login";
            if (!$this->user->delete((int)$id)) {
                return JsonResponse::serverError("unable to delete user");
            }

            return JsonResponse::ok("user deleted successfull");
        } catch (QueryException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
}
