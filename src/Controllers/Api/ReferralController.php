<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\JsonResponse;
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
    private $method;
    private $user;
    private $authenticator;
    private $referral;

    public function __construct($method = 'show')
    {
        $this->method = $method;
        $this->user = new User();
        $this->referral = new Referral();
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
        if (!$this->authenticator->validate()) {
            return JsonResponse::unauthorized();
        }

        $is_user = $this->authenticator->verifyRole($this->user, 'user');

        try {
            if (!$this->referral->find((int)$id))
                return JsonResponse::notFound('unable to retrieve referral');

            if ($is_user && ($this->referral->user_id != $this->user->id || $this->referral->referred_user_id != $this->user->id)) {
                return JsonResponse::unauthorized();
            }

            return JsonResponse::ok('referral retrieved success', $this->referral->toArray());
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a db problem');
        } catch (LogicException $e) {
            return JsonResponse::serverError('we encountered a runtime problem');
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function list()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if ($is_admin = $this->authenticator->verifyRole($this->user, 'admin')) {
                $referrals = $referrals = $this->referral->all();
            } else {
                $referrals = $this->user->referrals();
            }
            
            if (!$referrals)
                return JsonResponse::ok('no referrals found in list', []);

            $ids = Arr::map($referrals, function ($referral) {
                return is_array($referral) ? $referral['referred_user_id'] : $referral->referred_user_id;
            });

            logger()->info("id's are: ", $ids);
            $users = User::getBuilder()->where('id', 'IN', $ids)->get(); //because this can be done in one query using whereIn
            logger()->info("users are: ", $users);

            $_referrals = [];
            foreach ($referrals as $key => $referral) { //this is a bad design and needs to change
                logger()->info("key is $key: referrals is: ". json_encode($referral));
                if (is_array($referrals[$key])) {
                    $referrals[$key]['refferedUser'] = array_shift($users);
                    continue;
                }
                $referrals[$key] = $referral->toArray(false);
                $referrals[$key]['refferedUser'] = array_shift($users);
            }
            $this->user->find($this->user->id);

            return JsonResponse::ok("referrals retrieved success", [
                'referrals' => $referrals,
                'points' => $is_admin ? null : $this->user->points
            ]);
        } catch (PDOException $e) {
            logger()->info('pdo exception '.$e->getMessage(), $e->getTrace());
            return JsonResponse::serverError('we encountered a problem');
        } catch (Exception $e) {
            logger()->info('exception '.$e->getMessage(), $e->getTrace());
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function create()
    {
        try {
            throw new NotImplementedException('this feature is not implemented');
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            $status = 'some, values';

            if ($validated = Validator::validate($body, [
                'foo' => 'required|string',
                'bar' => 'sometimes|numeric',
                'baz' => "sometimes|string|in:$status",
                //add more validation rules here
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$referral = $this->referral->create($body)) {
                return JsonResponse::serverError('unable to create referral');
            }

            return JsonResponse::created('referral creation successful', $referral);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('referral already exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (NotImplementedException $e) {
            return JsonResponse::badRequest($e->getMessage());
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function update(string $id)
    {
        try {
            throw new NotImplementedException('this feature is not implemented');

            if (!$user = $this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }

            $id = sanitize_data($id);
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            $status = 'some, values';

            if ($validated = Validator::validate($body, [
                'foo' => 'sometimes|boolean',
                'bar' => 'sometimes|numeric',
                'baz' => "sometimes|string|in:$status",
                //add more validation rules here
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$this->referral->update($body, (int)$id)) {
                return JsonResponse::notFound('unable to update referral not found');
            }

            return JsonResponse::ok('referral updated successfull', $this->referral->toArray());
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (NotImplementedException $e) {
            return JsonResponse::badRequest($e->getMessage());
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function delete(int $id)
    {
        try {
            throw new NotImplementedException('this feature is not implemented');

            $id = sanitize_data($id);

            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            // Uncomment this for role authorization
            // if (!$this->authenticator->verifyRole($this->user, 'admin')) {
            //     return JsonResponse::unauthorized("you can't delete a referral");
            // }

            if (!$this->referral->delete((int)$id)) {
                return JsonResponse::notFound('unable to delete referral or referral not found');
            }

            return JsonResponse::ok('referral deleted successfull');
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (NotImplementedException $e) {
            return JsonResponse::badRequest($e->getMessage());
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }
}
