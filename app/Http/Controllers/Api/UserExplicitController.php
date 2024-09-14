<?php
namespace App\Http\Controllers\Api;

use App\Console\Jobs\SendResetPassword;
use App\Console\Jobs\SendVerifyEmail;
use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Support\Auth\Jwt\JwtAuthenticator;
use Eyika\Atom\Framework\Support\Arr;
use Eyika\Atom\Framework\Support\Str;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Validator;
use App\Models\User;
use Exception;
use PDOException;

final class UserExplicitController
{
    public function index(Request $request, string $method = "")
    {
        switch ($method) {
            case "request_pass_reset":
                $resp = $this->requestPasswordReset($request);
                break;
            case "reset_pass":
                $resp = $this->resetPassword($request);
                break;
            case "change_pass":
                $resp = $this->changePassword($request);
                break;
            case "change_role":
                $resp = $this->changeRole($request);
                break;
            case "request_email_verify":
                $resp = $this->requestEmailVerify($request);
                break;
            case "verify_email":
                $resp = $this->verifyEmail($request);
                break;
            case "request_email_change":
                $resp = $this->requestEmailChange($request);
                break;
            case "change_email":
                $resp = $this->changeEmail($request);
                break;
            case "request_phone_change":
                $resp = $this->requestPhoneChange($request);
                break;
            case "change_phone":
                $resp = $this->changePhone($request);
                break;
            default:
                $resp = JsonResponse::notFound("method not found");
        }
        return $resp;
    }

    private function requestPasswordReset(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            
            $body = $request->input();

            if ($validated = Validator::validate($body, [
                'email' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$user = User::getBuilder()->findByEmail($body['email'])) {
                return JsonResponse::ok("if we have this email 111, password reset code should be sent to your email");
            }

            $code = implode([rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9)]);
            if (!$user->update(['email2fa_token' => $code, 'email2fa_expire' => time() + env('EMAIL2FA_MAX_AGE')])) {  //TODO:: this token should be timeed and should expire
                return JsonResponse::serverError("we encountered an error, please try again");
            }
            $job = new SendResetPassword(array_merge($user->toArray(false), ['email2fa_token' => $code]));
            $job->init()->delay(5)->run();
            return JsonResponse::ok("if we have this email, password reset code should be sent to your email");
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

    private function resetPassword(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            
            $body = $request->input();

            if ($validated = Validator::validate($body, [
                'reset_token' => 'required|string',
                'email' => 'required|string',
                'new_password' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$user = User::getBuilder()->findByEmail($body['email'])) {
                return JsonResponse::badRequest("invalid reset data, try again");
            }

            if ($user->email2fa_expire !== null && $user->email2fa_expire <= time()) {
                return JsonResponse::unauthorized('reset token invalid expr or expired please try again');
            }
            if ($user->email2fa_token !== null && $user->email2fa_token !== $body['reset_token']) {
                return JsonResponse::badRequest('reset token invalid or expired please try again');
            }

            // echo "got to pass login";
            $data = [
                'password' => password_hash($body['new_password'], PASSWORD_BCRYPT),
                'email2fa_token' => null,
                'email2fa_expire' => null
            ];
            if (!$user->update($data, $user->id)) {
                return JsonResponse::serverError("unable reset password");
            }

            return JsonResponse::ok("password reset successfully");
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

    private function changePassword(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            if (!$user = JwtAuthenticator::validate()) {
                return JsonResponse::unauthorized();
            }
            
            $body = $request->input();

            if ($validated = Validator::validate($body, [
                'current_password' => 'required|string',
                'new_password' => 'required|string',
                'confirm_new_password' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if ($body['new_password'] != $body['confirm_new_password']) {
                return JsonResponse::badRequest('confirm_new_password and new_password should be the same value');
            }

            if (!password_verify($body['current_password'], $user->password)) {
                return JsonResponse::badRequest('invalid current password');
            }

            // echo "got to pass login";
            $data['password'] = password_hash($body['new_password'], PASSWORD_BCRYPT);
            if (!$user = $user->update($data, $user->id)) {
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

    private function changeRole(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            if (!$user = JwtAuthenticator::validate()) {
                return JsonResponse::unauthorized();
            }

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't update this user role");
            }
            
            $body = $request->input();

            if ($validated = Validator::validate($body, [
                'id' => 'required|string',
                'role_id' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            // echo "got to pass login";
            if (!$user = $user->update(Arr::only($body, 'role_id'), (int)$body['id'])) {
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

    

    /// User should not have to request a code to change email.
    private function requestEmailVerify(Request $request)
    {
        try {
            if (!$user = JwtAuthenticator::validate()) {
                return JsonResponse::unauthorized();
            }

            $code = Str::random(10);
            if (!$user->update(['email2fa_token' => $code, 'email2fa_expire' => time() + env('EMAIL2FA_MAX_AGE')])) {  //TODO:: this token should be timeed and should expire
                return JsonResponse::serverError("unable to generate token");
            }

            $mail_job = new SendVerifyEmail(array_merge($user->toArray(), ['email2fa_token' => $code]));
            $mail_job->init()->delay(5)->run();
            
            return JsonResponse::ok("code sent to user email");
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
    
    private function verifyEmail(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            // if (!JwtAuthenticator::validate()) {
            //     return JsonResponse::unauthorized("please login before attempting to verify email");
            // }
            
            $body = $request->input();

            if ($validated = Validator::validate($body, [
                'email' => 'required|string',
                'email2fa_token' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$user = User::getBuilder()->findByEmail($body['email'], false)) {
                return JsonResponse::badRequest("invalid user data");
            }

            if (is_null($user->email2fa_expire) || is_null($user->email2fa_expire)) {
                $user->update(['email2fa_token' => null, 'email2fa_expire' => null]);
                return JsonResponse::badRequest("invalid or expired token");
            }

            if ($user->email2fa_expire <= time()) {
                $user->update(['email2fa_token' => null, 'email2fa_expire' => null]);
                return JsonResponse::badRequest("invalid or expired token");
            }
            if ($user->email2fa_token !== $body['email2fa_token']) {
                ///TODO: email2fa_token should have maximum tries
                return JsonResponse::badRequest("invalid or expired token");
            }
            $user->update(['status' => User::ACTIVE, 'email2fa_token' => null, 'email2fa_expire' => null]);
            
            return JsonResponse::ok("email verified successfully");
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

    /// User should not have to request a code to change email.
    private function requestEmailChange(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            if (!$user = JwtAuthenticator::validate()) {
                return JsonResponse::unauthorized();
            }
            
            $body = $request->input();

            if ($validated = Validator::validate($body, [
                'email' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$user = $user->findByEmail($body['email'])) {
                return JsonResponse::badRequest("email does not exist");
            }

            if ($user instanceof User) {
                $code = Str::random(10);
                if (!$user->update(['email2fa_token' => (string)$code, 'email2fa_expire' => time() + env('EMAIL2FA_MAX_AGE')])) {  //TODO:: this token should be timeed and should expire
                    return JsonResponse::serverError("unable to generate token");
                }
                
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

    private function changeEmail(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            if (!$user = JwtAuthenticator::validate()) {
                return JsonResponse::unauthorized();
            }
            
            $body = $request->input();

            if ($validated = Validator::validate($body, [
                'password' => 'required|string',
                'email' => 'required|string',
                'new_email' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!password_verify($body['password'], $user->password)) {
                return JsonResponse::unauthorized('invalid credentials, your are not authorized to perform this request');
            }

            if (isset($body['email']) && $body['email'] !== $user->email) {
                return JsonResponse::badRequest('invalid current email');
            }

            // if (is_null($user->email2fa_expire) || (!is_null($user->email2fa_expire) && $user->email2fa_expire <= time())) {
            //     return JsonResponse::unauthorized('change token invalid or expired please try again');
            // }
            // if (is_null($user->email2fa_token) || (!is_null($user->email2fa_token) && $user->email2fa_token !== $body['email_change_token'])) {
            //     return JsonResponse::badRequest('change token invalid or expired please try again');
            // }

            // echo "got to pass login";
            if (!$user = $user->update([
                    'email' => $body['new_email'],
                    // 'email2fa_expire' => null,
                    // 'email2fa_token' => null
                ], $user->id)) {
                return JsonResponse::serverError("unable change email, please try again");
            }

            return JsonResponse::ok("email changed successfully");
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

    private function requestPhoneChange(Request $request)
    {

    }

    private function changePhone(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            if (!$user = JwtAuthenticator::validate()) {
                return JsonResponse::unauthorized();
            }
            
            $body = $request->input();

            if ($validated = Validator::validate($body, [
                'password' => 'required|string',
                'phone' => 'require|string',
                'new_phone' => 'required|string'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$user->find(is_protected: false)) {
                return JsonResponse::badRequest("invalid user data, your are not authorized to perform this request");
            }

            /// TODO: this should be use to validate phone change using a code previously sent to the user
            // if (is_null($user->email2fa_expire) || (!is_null($user->email2fa_expire) && $user->email2fa_expire <= time())) {
            //     return JsonResponse::unauthorized('change token invalid or expired please try again');
            // }
            // if (is_null($user->email2fa_token) || (!is_null($user->email2fa_token) && $user->email2fa_token !== $body['email_change_token'])) {
            //     return JsonResponse::badRequest('change token invalid or expired please try again');
            // }

            if (!password_verify($body['password'], $user->password)) {
                return JsonResponse::unauthorized('invalid credentials, your are not authorized to perform this request');
            }
            
            if (isset($body['phone']) && $body['phone'] !== $user->phone) {
                return JsonResponse::badRequest('invalid current phone');
            }

            if (!$user->update(['phone' => $body['new_phone']], $user->id)) {
                return JsonResponse::serverError("unable change phone number");
            }

            return JsonResponse::ok("phone changed successfully");
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
