<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Referral;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Exception;
use Hybridauth\Exception\NotImplementedException;
use LogicException;
use PDOException;

final class ReferralController
{
    public function show(Request $request, string $id)
    {
        $id = sanitize_data($id);
        if (!JwtAuthenticator::validate()) {
            return JsonResponse::unauthorized();
        }
        $user = $request->auth_user;

        $is_user = Guard::roleIs($user, 'user');

        try {
            if (!$referral = Referral::getBuilder()->find((int)$id))
                return JsonResponse::notFound('unable to retrieve referral');

            if ($is_user && ($referral->user_id != $user->id || $referral->referred_user_id != $user->id)) {
                return JsonResponse::unauthorized();
            }

            return JsonResponse::ok('referral retrieved success', $referral->toArray());
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a db problem');
        } catch (LogicException $e) {
            return JsonResponse::serverError('we encountered a runtime problem');
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    public function list(Request $request)
    {
        try {
            $user = $request->auth_user;

            if ($is_admin = Guard::roleIs($user, 'admin')) {
                $referrals = Referral::getBuilder()->all();
            } else {
                $referrals = $user->referrals();
            }
            
            if (!$referrals)
                return JsonResponse::ok('no referrals found in list', []);

            $ids = Arr::map($referrals, function ($referral) {
                return is_array($referral) ? $referral['referred_user_id'] : $referral->referred_user_id;
            });

            $users = User::getBuilder()->where('id', 'IN', $ids)->get(); //because this can be done in one query using whereIn

            foreach ($referrals as $key => $referral) { //this is a bad design and needs to change
                if (is_array($referrals[$key])) {
                    $referrals[$key]['refferedUser'] = array_shift($users);
                    continue;
                }
                $referrals[$key] = $referral->toArray(false);
                $referrals[$key]['refferedUser'] = array_shift($users);
            }

            return JsonResponse::ok("referrals retrieved success", [
                'referrals' => $referrals,
                'points' => $is_admin ? null : $user->points
            ]);
        } catch (PDOException $e) {
            logger()->info('pdo exception '.$e->getMessage(), $e->getTrace());
            return JsonResponse::serverError('we encountered a problem');
        } catch (Exception $e) {
            logger()->info('exception '.$e->getMessage(), $e->getTrace());
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    // public function create(Request $request)
    // {
    //     try {
    //         throw new NotImplementedException('this feature is not implemented');
    //         $user = $request->auth_user;
            
    //         if ( !$request->hasBody()) {
    //             return JsonResponse::badRequest('bad request', 'body is required');
    //         }
            
    //         $body = sanitize_data($request->input());
    //         $status = 'some, values';

    //         if ($validated = Validator::validate($body, [
    //             'foo' => 'required|string',
    //             'bar' => 'sometimes|numeric',
    //             'baz' => "sometimes|string|in:$status",
    //             //add more validation rules here
    //         ])) {
    //             return JsonResponse::badRequest('errors in request', $validated);
    //         }

    //         if (!$referral = Referral::getBuilder()->create($body)) {
    //             return JsonResponse::serverError('unable to create referral');
    //         }

    //         return JsonResponse::created('referral creation successful', $referral);
    //     } catch (PDOException $e) {
    //         if (str_contains($e->getMessage(), 'Duplicate entry'))
    //             return JsonResponse::badRequest('referral already exist');
    //         else $message = 'we encountered a problem';
            
    //         return JsonResponse::serverError($message);
    //     } catch (NotImplementedException $e) {
    //         return JsonResponse::badRequest($e->getMessage());
    //     } catch (Exception $e) {
    //         return JsonResponse::serverError('we encountered a problem');
    //     }
    // }

    // public function update(Request $request, string $id)
    // {
    //     try {
    //         throw new NotImplementedException('this feature is not implemented');

    //         $user = $request->auth_user;
            
    //         if ( !$request->hasBody()) {
    //             return JsonResponse::badRequest('bad request', 'body is required');
    //         }

    //         $id = sanitize_data($id);
            
    //         $body = sanitize_data($request->input());

    //         $status = 'some, values';

    //         if ($validated = Validator::validate($body, [
    //             'foo' => 'sometimes|boolean',
    //             'bar' => 'sometimes|numeric',
    //             'baz' => "sometimes|string|in:$status",
    //             //add more validation rules here
    //         ])) {
    //             return JsonResponse::badRequest('errors in request', $validated);
    //         }

    //         if (!$referral = Referral::getBuilder()->update($body, (int)$id)) {
    //             return JsonResponse::notFound('unable to update referral not found');
    //         }

    //         return JsonResponse::ok('referral updated successfull', $referral->toArray());
    //     } catch (PDOException $e) {
    //         if (str_contains($e->getMessage(), 'Unknown column'))
    //             return JsonResponse::badRequest('column does not exist');
    //         else $message = 'we encountered a problem';
            
    //         return JsonResponse::serverError($message);
    //     } catch (NotImplementedException $e) {
    //         return JsonResponse::badRequest($e->getMessage());
    //     } catch (Exception $e) {
    //         return JsonResponse::serverError('we encountered a problem');
    //     }
    // }

    // public function delete(Request $request, int $id)
    // {
    //     try {
    //         throw new NotImplementedException('this feature is not implemented');

    //         $id = sanitize_data($id);

    //         $user = $request->auth_user;

    //         // Uncomment this for role authorization
    //         // if (!Guard::roleIs($user, 'admin')) {
    //         //     return JsonResponse::unauthorized("you can't delete a referral");
    //         // }

    //         if (!Referral::getBuilder()->delete((int)$id)) {
    //             return JsonResponse::notFound('unable to delete referral or referral not found');
    //         }

    //         return JsonResponse::ok('referral deleted successfull');
    //     } catch (PDOException $e) {
    //         if (str_contains($e->getMessage(), 'Unknown column'))
    //             return JsonResponse::badRequest('column does not exist');
    //         else $message = 'we encountered a problem';
            
    //         return JsonResponse::serverError($message);
    //     } catch (NotImplementedException $e) {
    //         return JsonResponse::badRequest($e->getMessage());
    //     } catch (Exception $e) {
    //         return JsonResponse::serverError('we encountered a problem');
    //     }
    // }
}
