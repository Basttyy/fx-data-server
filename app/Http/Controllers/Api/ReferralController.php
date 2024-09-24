<?php
namespace App\Http\Controllers\Api;

use App\Models\Model;
use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Support\Auth\Jwt\JwtAuthenticator;
use Eyika\Atom\Framework\Support\Arr;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use App\Models\Referral;
use App\Models\User;
use Exception;
use Eyika\Atom\Framework\Support\Database\Contracts\ModelInterface;
use Eyika\Atom\Framework\Support\Database\Contracts\UserModelInterface;
use Eyika\Atom\Framework\Support\Database\PaginatedData;
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
            $page = $request->query('page');
            $per_page = $request->query('perpage');

            if ($is_admin = Guard::roleIs($user, 'admin')) {
                $data = Referral::getBuilder()->paginate($page, $per_page);
            } else {
                $data = paginate($user->referrals(), Referral::getBuilder(), $page, $per_page);
            }
            
            if (!$data)
                return JsonResponse::ok('no referrals found in list', []);

            $data = $data->toArray('referrals.list');
            $referrals = $data['data'];

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

            $data['referrals'] = $referrals;
            unset($data['data']);
            $data['points'] = $is_admin ? null : $user->points;

            return JsonResponse::ok("referrals retrieved success", $data);
        } catch (PDOException $e) {
            logger()->info('pdo exception '.$e->getMessage(), $e->getTrace());
            return JsonResponse::serverError('we encountered a problem');
        } catch (Exception $e) {
            logger()->info('exception '.$e->getMessage(), $e->getTrace());
            return JsonResponse::serverError('we encountered a problem');
        }
    }
}
