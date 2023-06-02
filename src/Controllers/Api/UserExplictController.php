<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
use PDOException;

final class UserExplicitController
{
    private $method;
    private $user;
    private $authenticator;

    public function __construct($method = "")
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
            case "request_pass_reset":
                $resp = $this->requestPasswordReset();
                break;
            case "reset_pass":
                $resp = $this->resetPassword();
                break;
            case "change_pass":
                $resp = $this->changePassword();
                break;
            case "change_role":
                $resp = $this->changeRole($id);
                break;
            case "request_email_change":
                $resp = $this->requestEmailChange();
                break;
            case "change_email":
                $resp = $this->changeEmail();
                break;
            case "request_phone_change":
                $resp = $this->requestPhoneChange();
                break;
            case "change_phone":
                $resp = $this->changePhone();
                break;
            default:
                $resp = JsonResponse::notFound("method not found");
        }
        return $resp;
    }

    private function requestPasswordReset()
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
                'email' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$user = $this->user->findByEmail($body['email'])) {
                return JsonResponse::badRequest("email does not exist");
            }

            if ($user instanceof User) {
                $code = implode([rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9)]);
                if (!$user->update(['email2fa_token' => (string)$code, 'email2fa_expire' => time() + env('email2fa_expire')])) {  //TODO:: this token should be timeed and should expire
                    return JsonResponse::serverError("unable to generate token");
                }
                //schdule job to send code to the user via email
                return JsonResponse::ok("code sent to user email");
            }
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    private function resetPassword()
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
                'reset_token' => 'required|string',
                'new_password' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$user = $this->user->findByEmail($body['current_email'])) {
                return JsonResponse::badRequest("email does not exist");
            }

            if ($user instanceof User) {
                if (is_null($user->email2fa_expire) || (!is_null($user->email2fa_expire) && $user->email2fa_expire <= time())) {
                    return JsonResponse::unauthorized('reset token invalid or expired please try again');
                }
                if (is_null($user->email2fa_token) || (!is_null($user->email2fa_token) && $user->email2fa_token !== $body['reset_token'])) {
                    return JsonResponse::badRequest('reset token invalid or expired please try again');
                }
    
                // echo "got to pass login";
                $data = [
                    'password' => $body['new_password'],
                    'email2fa_token' => null,
                    'email2fa_expire' => null
                ];
                if (!$user = $this->user->update($data, $user->id)) {
                    return JsonResponse::serverError("unable reset password");
                }
    
                return JsonResponse::ok("password reset successfully");
            }
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    private function changePassword()
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
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'current_password' => 'required|string',
                'new_password' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!password_verify($body['current_password'], $user->password)) {
                return JsonResponse::badRequest('invalid current password');
            }

            // echo "got to pass login";
            $data['password'] = password_hash($body['new_password'], PASSWORD_BCRYPT);
            if (!$user = $this->user->update($data, $user->id)) {
                return JsonResponse::serverError("unable change password");
            }

            return JsonResponse::ok("password changed successfully");
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

    private function changeRole(string $id)
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

            if (!$this->authenticator->verifyRole($user, 'admin')) {
                return JsonResponse::unauthorized("you can't update this user role");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'role_id' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            // echo "got to pass login";
            if (!$user = $this->user->update(Arr::only($body, 'role_id'), (int)$id)) {
                return JsonResponse::serverError("unable to update user role");
            }

            return JsonResponse::ok("user role updated successfully");
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    private function requestEmailChange()
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
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'email' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$user = $this->user->findByEmail($body['email'])) {
                return JsonResponse::badRequest("email does not exist");
            }

            if ($user instanceof User) {
                $code = implode([rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9)]);
                if (!$user->update(['email2fa_token' => (string)$code, 'email2fa_expire' => time() + env('email2fa_expire')])) {  //TODO:: this token should be timeed and should expire
                    return JsonResponse::serverError("unable to generate token");
                }
                //schdule job to send code to the user via email
                return JsonResponse::ok("code sent to user email");
            }
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    private function changeEmail()
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
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'email_change_token' => 'required|string',
                'new_email' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (isset($body['current_email']) && $body['current_email'] !== $user->email) {
                return JsonResponse::badRequest('invalid current email');
            }

            if (is_null($user->email2fa_expire) || (!is_null($user->email2fa_expire) && $user->email2fa_expire <= time())) {
                return JsonResponse::unauthorized('change token invalid or expired please try again');
            }
            if (is_null($user->email2fa_token) || (!is_null($user->email2fa_token) && $user->email2fa_token !== $body['email_change_token'])) {
                return JsonResponse::badRequest('change token invalid or expired please try again');
            }

            // echo "got to pass login";
            if (!$user = $this->user->update([
                    'email' => $body['new_email'],
                    'email2fa_expire' => null,
                    'email2fa_token' => null
                ], $user->id)) {
                return JsonResponse::serverError("unable change email");
            }

            return JsonResponse::ok("email changed successfully", [
                'data' => $user->toArray()
            ]);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    private function requestPhoneChange()
    {

    }

    private function changePhone()
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
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'password' => 'required|string',
                'new_phone' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$data = $this->user->findBy('phone', $body['current_phone'])) {
                return JsonResponse::badRequest("phone does not exist");
            }
            $user = $this->user->fill($data);

            if ($user instanceof User) {
                /// TODO: this should be use to validate phone change using a code previously sent to the user
                // if (is_null($user->email2fa_expire) || (!is_null($user->email2fa_expire) && $user->email2fa_expire <= time())) {
                //     return JsonResponse::unauthorized('change token invalid or expired please try again');
                // }
                // if (is_null($user->email2fa_token) || (!is_null($user->email2fa_token) && $user->email2fa_token !== $body['email_change_token'])) {
                //     return JsonResponse::badRequest('change token invalid or expired please try again');
                // }
    
                if (!password_verify($body['password'], $user->password)) {
                    return JsonResponse::unauthorized('invalid reset password');
                }
                if (!$user = $this->user->update(['phone' => $body['new_phone']], $user->id)) {
                    return JsonResponse::serverError("unable reset phone");
                }
    
                return JsonResponse::ok("phone changed successfully");
            }
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }
}
