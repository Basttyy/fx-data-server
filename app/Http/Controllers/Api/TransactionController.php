<?php
namespace App\Http\Controllers\Api;

use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Support\Arr;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use App\Http\Traits\PaymentGateway;
use Eyika\Atom\Framework\Support\Validator;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TempTransactionRef;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use LogicException;
use PDOException;

final class TransactionController
{
    use PaymentGateway;

    public function show(Request $request, Transaction $transaction)
    {
        try {
            return JsonResponse::ok('transaction retrieved success', $transaction->toArray());

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
            $is_admin = Guard::roleIs($user, 'admin');
            $page = $request->query('page');
            $per_page = $request->query('perpage');

            if ($is_admin) {
                if (!$transactions = Transaction::getBuilder()->paginate($page, $per_page))
                    return JsonResponse::ok('no transaction found in list', []);
                $transactions = $transactions->toArray('transactions.list');
            } else {
                $transactions = Transaction::getBuilder()->where('user_id', $user->id)->get();
            }

            return JsonResponse::ok("transaction's retrieved success", $transactions);
        } catch (PDOException $e) {
            logger()->info($e);
            return JsonResponse::serverError('we encountered a problem');
        } catch (Exception $e) {
            logger()->info($e);
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    public function generateTxRef(Request $request)
    {
        try {
            $user = $request->auth_user;
            if (!Guard::roleIs($user, 'user')) {
                return JsonResponse::unauthorized("you can't perform this action");
            }
            if ( $request->hasBody()) {
                $body = $request->input();
    
                if ($validated = Validator::validate($body, [
                    'plan_code' => 'required|string'
                ])) {
                    return JsonResponse::badRequest('errors in request', $validated);
                }
            }

            if (!$temp_trans_ref = TempTransactionRef::getBuilder()->where('user_id', $user->id)->orderBy('created_at', 'DESC')->first()) {

                logger()->info('01 transaction ref is: ');
                if ($this->providerIs('paystack') && $trans = $this->initializeTransaction($user->email, 1000, $body['plan_code'])) {
                    logger()->info('02 transaction ref is: ');
                    if ($trans->status !== true) {
                        return JsonResponse::serverError('something happened please try again');
                    }
                    if (!$temp_trans_ref = TempTransactionRef::getBuilder()->create([
                        'user_id' => $user->id,
                        'tx_ref' => $trans->data->reference,
                        'access_code' => $trans->data->access_code
                    ])) {
                        return JsonResponse::serverError('something happened please try again');
                    }
                } else if ($this->providerIs('flutterwave')) {
                    logger()->info('03 transaction ref is: ');
                    if (!$temp_trans_ref = TempTransactionRef::getBuilder()->create([
                        'user_id' => $user->id,
                        'tx_ref' => transaction_ref()
                    ])) {
                        return JsonResponse::serverError('something happened please try again');
                    }
                    logger()->info('transaction ref is: ', $temp_trans_ref->toArray(false));
                }
                return JsonResponse::ok('transaction ref generated success', [
                    'tx_ref' => $temp_trans_ref->tx_ref,
                    'access_code' => $temp_trans_ref->access_code
                ]);
            }

            return JsonResponse::ok('transaction ref generated success', [
                'tx_ref' => $temp_trans_ref->tx_ref,
                'access_code' => $this->providerIs('flutterwave') ? null : $temp_trans_ref->access_code
            ]);
        } catch (Exception $e) {
            logger()->info($e->getMessage(). ' '. $e->getCode());
            return JsonResponse::serverError('we encountered a problem please try again');
        }
    }

    public function create(Request $request)
    {
        try {
            if ( !$request->hasBody()) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }
            
            $body = $request->input();
            $status = Transaction::success .', '. Transaction::pending .', '. Transaction::cancelled;
            $types = Transaction::inflow . ', ' . Transaction::outflow;

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
            $builder = Transaction::getBuilder();
            if (!$plan = Plan::getBuilder()->find($body['plan_id'])) {
                $builder->rollback();
                return JsonResponse::notFound("unable to retrieve plan");
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
            $user = $request->auth_user;

            $body['third_party_ref'] = $this->providerIs('flutterwave') ? $trans->data->flw_ref : $trans->data->reference;
            $body['user_id'] = $user->id;
            $body['action'] = Transaction::SUBSCRIPTION;
            $builder->beginTransaction();

            if (!$transaction = $builder->create(Arr::except($body, ['plan_id', 'duration']))) {
                $builder->rollback();
                return JsonResponse::serverError('unable to create transaction');
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
                'user_id' => $user->id,
                'plan_id' => $body['plan_id'],
                'expires_at' => Carbon::now()->modify('+' . $durationInterval()),
                'is_canceled' => 0
            ]);
            ///TODO: send success notification to the user about the new subscription he made

            if ($subscription)
                $transaction->update(['subscription_id' => $subscription->id]);

            TempTransactionRef::getBuilder()->where('user_id', $user->id)->delete();

            $transaction->commit();

            return JsonResponse::created('transaction creation successful', $transaction);
        } catch (PDOException $e) {
            $builder->rollback();
            logger()->info($e);
            if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('transaction already exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $builder->rollback();
            logger()->info($e);
            return JsonResponse::serverError('we encountered a problem');
        }
    }

    public function update(Request $request)
    {
        try {
            if (env('PAYMENT_PROVIDER_ENV') !== $request->server('VERIF_HASH')) {
                return JsonResponse::unauthorized();
            }

            if ( !$request->hasBody()) {
                return JsonResponse::badRequest('bad request', 'body is required');
            }

            JsonResponse::ok();

            $body = $request->input();

            $trans = $this->verifyTransaction($body['data']['id']);
            if (!$trans) {
                return true;
            }
            if (!$transaction = Transaction::getBuilder()->where('transaction_id', $body['data']['id'])->first()) {
                if (!$transaction = Transaction::getBuilder()->where('tx_ref', $body['data']['tx_ref'])) {
                    return true;
                }
                if ($trans->status !== 'success' ||
                    $trans->data->amount !== $body['data']['amount'] ||
                    $trans->data->currency !== $body['data']['currency']
                ) {
                    return true;
                }

                if (!$transaction->create([
                        'status' => $trans->status,
                        'user_id' => $transaction->user_id,
                        'transaction_id' => $body['data']['id'],
                        'subscription_id' => $transaction->subscription_id,
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
                $trans->data->amount !== $transaction->amount ||
                $trans->data->currency !== $transaction->currency
            ) {
                return true;
            }

            if (!$transaction->update([ 'status' => $trans->status])) {
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
