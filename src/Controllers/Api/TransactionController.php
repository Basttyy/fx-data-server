<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Traits\Flutterwave;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Plan;
use Basttyy\FxDataServer\Models\Subscription;
use Basttyy\FxDataServer\Models\TempTransactionRef;
use Basttyy\FxDataServer\Models\Transaction;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Carbon\Carbon;
use Exception;
use LogicException;
use PDOException;

final class TransactionController
{
    use Flutterwave;
    private $method;
    private $user;
    private $authenticator;
    private $transaction;
    private $temp_trans_ref;

    public function __construct($method = 'show')
    {
        $this->method = $method;
        $this->user = new User();
        $this->transaction = new Transaction();
        $this->temp_trans_ref = new TempTransactionRef();
        $encoder = new JwtEncoder(env('APP_KEY'));
        $role = new Role();
        $this->authenticator = new JwtAuthenticator($encoder, $this->user, $role);
        // $this->my_config = PackageConfig::setUp(
        //     'FLWSECK_TEST-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX-X',
        //     'FLWPUBK_TEST-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX-X',
        //     'FLWSECK_XXXXXXXXXXXXXXXX',
        //     'staging'
        // );
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
                $resp = $this->update();
                break;
            case 'trans_ref':
                $resp = $this->generateTxRef();
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
            if (!$this->transaction->find((int)$id))
                return JsonResponse::notFound('unable to retrieve transaction');

            return JsonResponse::ok('transaction retrieved success', $this->transaction->toArray());
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
            $is_admin = !$this->authenticator->verifyRole($this->user, 'admin');

            if ($is_admin)
                $transactions = $this->transaction->all(false);
            else
                $transactions = $this->transaction->where('user_id', $this->user->id)->get();

            if (!$transactions)
                return JsonResponse::ok('no transaction found in list', []);

            return JsonResponse::ok("transaction's retrieved success", $transactions);
        } catch (PDOException $e) {
            logger()->info($e);
            return JsonResponse::serverError('we encountered a problem');
        } catch (Exception $e) {
            logger()->info($e);
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function generateTxRef()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            if (!$this->authenticator->verifyRole($this->user, 'user')) {
                return JsonResponse::unauthorized("you can't perform this action");
            }

            if (!$this->temp_trans_ref->where('user_id', $this->user->id)->orderBy('created_at', 'DESC')->first()) {
                $ref = transaction_ref();

                if (!$temp_trans_ref = $this->temp_trans_ref->create([
                    'user_id' => $this->user->id,
                    'tx_ref' => $ref
                ])) {
                    return JsonResponse::serverError('something happened please try again');
                }
                return JsonResponse::ok('transaction ref generated success', [ 'tx_ref' => $temp_trans_ref['tx_ref'] ]);
            }

            return JsonResponse::ok('transaction ref generated success', [ 'tx_ref' => $this->temp_trans_ref->tx_ref ]);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem please try again');
        }
    }

    private function create()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));
            $status = 'successful, pending, cancelled';
            $types = Transaction::SUBSCRIPTION . ', ' . Transaction::WITHDRAWAL;

            if ($validated = Validator::validate($body, [
                'transaction_id' => 'required|numeric',
                'plan_id' => 'required|numeric',
                'tx_ref' => 'required|string',
                'status' => "required|string|in:$status",
                'amount' => 'required|numeric',
                'currency' => 'required|string',
                'type' => "required|string|in:$types",
                'duration' => 'required|int'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if (!$trans = $this->verifyTransaction($body['transaction_id'])) {
                logger()->info('doing A');
                $body['status'] = 'pending';
            } else {
                if ($trans->status !== 'success' ||
                    $trans->data->amount != $body['amount'] ||
                    $trans->data->currency !== $body['currency']
                ) {
                    logger()->info('doing B');
                    $body['status'] = $trans->status == 'cancelled' ? 'cancelled' : 'pending';
                } else {
                    logger()->info('doing C');
                    $body['status'] = 'successful';
                }
            }

            $body['third_party_ref'] = $trans->data->flw_ref;
            $body['user_id'] = $this->user->id;
            $this->transaction->beginTransaction();
            if (!$transaction = $this->transaction->create(Arr::except($body, ['plan_id', 'duration']))) {
                $this->transaction->rollback();
                return JsonResponse::serverError('unable to create transaction');
            }
            if (!$plan = Plan::getBuilder()->find($body['plan_id'])) {
                $this->transaction->rollback();
                return JsonResponse::notFound("unable to retrieve plan");
            }
            $durationInterval = function() use ($body, $plan) {
                if ($plan->duration_interval == 'bi-annual')
                    return ($body['duration'] * 6).' '.'months';
                $plural = $body['duration'] > 1 ? 's' : '';

                return $body['duration'] .' '. $plan->duration_interval . $plural;
            };

            $subscription = Subscription::getBuilder()->create([
                'duration' => 1,
                'total_cost' => $trans->data->amount,
                'user_id' => $this->user->id,
                'plan_id' => $body['plan_id'],
                'expires_at' => Carbon::now()->modify('+' . $durationInterval()),
                'is_canceled' => 0
            ]);
            ///TODO: send success notification to the user about the new subscription he made

            if ($subscription)
                $this->transaction->update(['subscription_id' => $subscription['id']]);

            $this->temp_trans_ref->where('user_id', $this->user->id)->delete();

            $this->transaction->commit();

            return JsonResponse::created('transaction creation successful', $transaction);
        } catch (PDOException $e) {
            logger()->info($e);
            if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('transaction already exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            logger()->info($e);
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    private function update()
    {
        try {
            if (env('FLWV_ENV') !== $_SERVER['HTTP_VERIF_HASH']) {
                return JsonResponse::unauthorized();
            }

            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }

            JsonResponse::ok();

            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            $trans = $this->verifyTransaction($body['data']['id']);
            if (!$trans) {
                return true;
            }
            if (!$this->transaction->where('transaction_id', $body['data']['id'])->first()) {
                if (!$this->transaction->where('tx_ref', $body['data']['tx_ref'])) {
                    return true;
                }
                if ($trans->status !== 'success' ||
                    $trans->data->amount !== $body['data']['amount'] ||
                    $trans->data->currency !== $body['data']['currency']
                ) {
                    return true;
                }

                if (!$this->transaction->create([
                        'status' => $trans->status,
                        'user_id' => $this->transaction->user_id,
                        'transaction_id' => $body['data']['id'],
                        'subscription_id' => $this->transaction->subscription_id,
                        'amount' => $trans->data->amount,
                        'currency' => $trans->data->currency,
                        'tx_ref' => $trans->data->tx_ref,
                        'third_party_ref' => $trans->data->third_party_ref,
                        'type' => $body['event'] === "charge.completed" ? Transaction::SUBSCRIPTION : Transaction::WITHDRAWAL
                    ])) {
                    return true;
                }
            }
            
            if ($trans->status !== 'successful' ||
                $trans->data->amount !== $this->transaction->amount ||
                $trans->data->currency !== $this->transaction->currency
            ) {
                return true;
            }

            if (!$this->transaction->update([ 'status' => $trans->status])) {
                return true;
            }

            return true;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column'))
                return true;
            else $message = 'we encountered a problem';
            
            return true;
        } catch (Exception $e) {
            return true;
        }
    }
}
