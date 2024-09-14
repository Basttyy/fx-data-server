<?php
namespace App\Http\Controllers\Api;

use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Support\Arr;
use Eyika\Atom\Framework\Support\Str;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use Eyika\Atom\Framework\Support\Validator;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\User;
use Exception;
use Eyika\Atom\Framework\Support\Database\DB;
use Eyika\Atom\Framework\Support\Facade\Storage;
use LogicException;
use PDOException;

final class UserController
{
    public function show(Request $request, User $user)
    {
        try {
            $__user = $request->auth_user;

            if (!Guard::roleIs($__user, 'admin') || $__user->id !== $user->id) {
                return JsonResponse::unauthorized();
            }

            $subscription = Subscription::getBuilder()->findBy('user_id', $user->id, false); //TODO: we need to add a filter that will ensure the subscription is active

            $_user = Arr::except($user->toArray(), $user->twofainfos);
            $_user['extra']['is_admin'] = Guard::roleIs($user, 'admin');
            $_user['extra']['subscription'] = $subscription ? $subscription : null;
            $_user['extra']['twofa']['enabled'] = strlen($user->twofa_types) > 0;
            $_user['extra']['twofa']['twofa_types'] = $user->twofa_types;
            $_user['extra']['twofa']['twofa_default_type'] = $user->twofa_default_type;

            return JsonResponse::ok("user retrieved success", $_user);
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
            $page = $request->query('page');
            $per_page = $request->query('perpage');

            if (Guard::roleIs($user, 'admin')) {
                $users = $user->paginate($page, $per_page);
            } else {
                $users = $user->where("role_id", 1)->paginate($page, $per_page);
            }
            if (!$users)
                return JsonResponse::ok("no user found in list", []);

            return JsonResponse::ok("users retrieved success", $users->toArray('users.list'));
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
            if ( !$request->hasBody() ) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            $body = $request->input();

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
            
            $_SESSION['email2fa_token'] = Str::random(10);

            $body['password'] = password_hash($body['password'], PASSWORD_BCRYPT);
            $body['username'] = $body['username'] ?? $body['email'];
            $body['email2fa_token'] = $_SESSION['email2fa_token']; //implode([rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9),rand(0,9)]);
            $body['email2fa_expire'] = time() + env('EMAIL2FA_MAX_AGE');

            DB::beginTransaction();
            if (!$user = User::getBuilder()->create($body, false)) {
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

    public function update(Request $request, User $user)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            $auth_user = $request->auth_user;
            $is_admin = Guard::roleIs($auth_user, 'admin');

            if (!$is_admin && $auth_user->id !== $user->id) {
                return JsonResponse::unauthorized("you can't update this user");
            }
            
            $body = $request->input();

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
            if (!$user->update($body, $user->id)) {
                return JsonResponse::serverError("unable to update user");
            }

            if (isset($body['avatar'])) {
                if (empty($user->avatar)) {
                    $body['user_id'] = $user->id;
                    $image = base64_decode($body['logo']);
                    $path = 'uploads/avatars/';
                    
                    $target_file = uniqid(). '.jpg';
                    Storage::put($path . $target_file, $image);
                    $body['logo'] = storage('public')->url($path.$target_file);
                } else {
                    $prev_avatar = $user->avatar;
                    $avatar = base64_decode($body['avatar']);
                    $path = 'uploads/avatars/';

                    $target_file = uniqid(). '.jpg';
                }

                if (Storage::put($path . $target_file, $avatar)) {
                    Storage::delete(str_replace('/storage', '', $prev_avatar));
                    $body['avatar'] = storage('public')->url($path.$target_file);
                }
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

    public function delete(Request $request, User $user)
    {
        try {
            // $is_admin = Guard::roleIs($user, 'admin');

            $auth_user = $request->auth_user;
            if ($auth_user->id !== $user->id) {
                return JsonResponse::unauthorized("you can't delete this user");
            }

            $avatar = $user->avatar;

            if (!$user->delete($user->id)) {
                return JsonResponse::notFound("unable to delete user or user not found");
            }
            Storage::delete(str_replace('/storage', '', $avatar));

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
    
    public function withrawAffilliateEarnings(Request $request, $points)
    {
        /** @var User $user */
        $user = $request->auth_user;
        if (!Guard::roleIs($user, 'user')) {
            return JsonResponse::unauthorized("you are not allowed to exchange points");
        }

        $points = sanitize_data($points);

        $body = [
            'points' => $points
        ];

        if ($validated = Validator::validate($body, [
            'points' => 'required|integer|min:1',
        ])) {
            return JsonResponse::badRequest('errors in request', $validated);
        }

        try {
            if (!$cash = $user->exchangePointsForCash($body['points'])) {
                return JsonResponse::serverError('unable to create transaction');
            }
            return JsonResponse::ok("Exchanged points for \${$cash}");
        } catch (\Exception $e) {

            return JsonResponse::badRequest('unable to exchange point', ['error' => $e->getMessage()]);
        }
    }
}
