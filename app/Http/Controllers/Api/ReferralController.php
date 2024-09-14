<?php
namespace App\Http\Controllers\Api;

use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Support\Auth\Jwt\JwtAuthenticator;
use Eyika\Atom\Framework\Support\Arr;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use App\Models\Referral;
use App\Models\User;
use Exception;
use LogicException;
use PDOException;

final class ReferralController
{
    public function show(Request $request, string $id)
    {
        $id = $id;
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
            /** @var User $user */
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
}
