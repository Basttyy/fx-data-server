<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\Console\Jobs\SendVerifyEmail;
use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\Subscription;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class UserController
{
    private $method;
    private $user;
    private $subscription;
    private $authenticator;

    public function __construct($method = "show")
    {
        $this->method = $method;
        $this->user = new User;
        $this->subscription = new Subscription;
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role;
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
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            $is_admin = $this->authenticator->verifyRole($this->user, 'admin');

            if ($is_admin === false && $this->user->id != $id) {
                return JsonResponse::unauthorized("you can't view this user");
            }

            if (!$this->user->find((int)$id))
                return JsonResponse::notFound("unable to retrieve user");
            $subscription = $this->subscription->findBy('user_id', $this->user->id, false); //TODO: we need to add a filter that will ensure the subscription is active

            $user = Arr::except($this->user->toArray(), $this->user->twofainfos);
            $user['extra']['is_admin'] = $is_admin;
            $user['extra']['subscription'] = $subscription ? $subscription : null;
            $user['extra']['twofa']['enabled'] = strlen($this->user->twofa_types) > 0;
            $user['extra']['twofa']['twofa_types'] = $this->user->twofa_types;
            $user['extra']['twofa']['twofa_default_type'] = $this->user->twofa_default_type;

            return JsonResponse::ok("user retrieved success", [
                'data' => $user
            ]);
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
            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            $is_admin = $this->authenticator->verifyRole($user, 'admin');

            if ($is_admin) {
                $users = $this->user->all();
            } else {
                $users = $this->user->findBy("role_id", 1);
            }
            if (!$users)
                return JsonResponse::ok("no user found in list", []);

            return JsonResponse::ok("users retrieved success", [
                'data' => $users
            ]);
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

            $body['password'] = password_hash($body['password'], PASSWORD_BCRYPT);
            $body['username'] = $body['username'] ?? $body['email'];
            $body['email2fa_token'] = implode([rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9)]);
            $body['email2fa_expire'] = time() + env('EMAIL2FA_MAX_AGE');

            if (!$user = $this->user->create($body)) {
                return JsonResponse::serverError("unable to create user");
            }

            $mail_job = new SendVerifyEmail(array_merge($user, ['email2fa_token' => $body['email2fa_token']]));
            $mail_job->init()->delay(5)->run();

            $user['is_admin'] = false;
            $user['subscription'] = null;
            return JsonResponse::ok("user creation successful", $user);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('user already exist');
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

            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            $id = sanitize_data($id);
            $is_admin = $this->authenticator->verifyRole($user, 'admin');

            if (!$is_admin && $user->id !== $id) {
                return JsonResponse::unauthorized("you can't update this user");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'email' => 'sometimes|string',
                'password' => 'sometimes|string',
                'firstname' => 'sometimes|string',
                'lastname' => 'sometimes|string',
                'username' => 'sometimes|string',
                'phone' => 'sometimes|string',
                'level' => 'sometimes|string',
                'country' => 'sometimes|string',
                'city' => 'sometimes|string',
                'address' => 'sometimes|string',
                'postal_code' => 'sometimes|string',
                'avatar' => 'sometimes|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            // echo "got to pass login";
            if (!$user = $this->user->update($body, (int)$id)) {
                return JsonResponse::serverError("unable to update user");
            }

            return JsonResponse::ok("user update successful", [
                'data' => $user->toArray()
            ]);
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

            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            // $is_admin = $this->authenticator->verifyRole($user, 'admin');

            if ($user->id !== $id) {
                return JsonResponse::unauthorized("you can't delete this user");
            }

            // echo "got to pass login";
            if (!$this->user->delete((int)$id)) {
                return JsonResponse::notFound("unable to delete user or user not found");
            }
            
            return JsonResponse::ok("user deleted successfull");

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
