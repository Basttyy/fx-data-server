<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\Console\Jobs\SendVerifyEmail;
use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\DB;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\libs\Str;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Referral;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\Subscription;
use Basttyy\FxDataServer\Models\User;
use Exception;
use LogicException;
use PDOException;

final class UserController
{
    private $user;
    private $subscription;
    private $authenticator;

    public function __construct()
    {
        $this->user = new User;
        $this->subscription = new Subscription;
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role;
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
    }

    public function show(Request $request, string $id)
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

    public function list()
    {
        try {
            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            

            if ($this->authenticator->verifyRole($user, 'admin')) {
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

    public function create()
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
                'email' => 'required|string|not_exist:users,email',
                'password' => 'required|string',
                'firstname' => 'required|string',
                'lastname' => 'required|string',
                'username' => 'sometimes|string|not_exist:users,username',
                'referral_code' => 'sometimes|string|exist:users,referral_code',
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }
            
            $_SESSION['email2fa_token'] = Str::random(6);

            $body['password'] = password_hash($body['password'], PASSWORD_BCRYPT);
            $body['username'] = $body['username'] ?? $body['email'];
            $body['email2fa_token'] = $_SESSION['email2fa_token']; //implode([rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9)]);
            $body['email2fa_expire'] = time() + env('EMAIL2FA_MAX_AGE');

            DB::beginTransaction();
            if (!$user = $this->user->create($body)) {
                DB::rollback();
                return JsonResponse::serverError("unable to create user");
            }

            if (!$user instanceof User) {
                DB::rollback();
                return JsonResponse::serverError('unable to create user');
            }

            if (isset($body['referral_code'])) {
                if ($referrer = User::getBuilder()->where('referral_code', $body['referral_code'])->first()) {
                    Referral::getBuilder()->create([
                        'user_id' => $referrer->id,
                        'referred_user_id' => $user->id,
                    ]);

                    // Award points to the referrer
                    $referrer->points += env('POINTS_PER_REFERRAL') ?? 10; // Award 10 points for each referral
                    $referrer->save();
                }
            }

            $resp = $user->toArray();
            $resp['is_admin'] = false;
            $resp['subscription'] = null;
            DB::commit();
            return JsonResponse::ok("user creation successful", $resp);
        } catch (PDOException $e) {
            DB::rollback();
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('user already exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            DB::rollback();
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    public function update(string $id)
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
                'avatar' => 'sometimes|string',
                'dollar_per_point' => 'sometimes|float'
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

    public function delete(int $id)
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
    
    public function exchangePoints()
    {
        if (!$this->authenticator->validate()) {
            return JsonResponse::unauthorized();
        }

        if (!$this->authenticator->verifyRole($this->user, 'user')) {
            return JsonResponse::unauthorized("you are not allowed to exchange points");
        }

        $inputJSON = file_get_contents('php://input');

        $body = sanitize_data(json_decode($inputJSON, true));

        if ($validated = Validator::validate($body, [
            'points' => 'required|integer|min:1',
        ])) {
            return JsonResponse::badRequest('errors in request', $validated);
        }

        try {
            $cash = $this->user->exchangePointsForCash($body['points']);
            return JsonResponse::ok("Exchanged points for \${$cash}");
        } catch (\Exception $e) {
            return JsonResponse::badRequest('unable to exchange point', ['error' => $e->getMessage()]);
        }
    }
}
