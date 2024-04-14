<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
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
            $transactions = $this->transaction->all();
            if (!$transactions)
                return JsonResponse::ok('no transaction found in list', []);

            return JsonResponse::ok("transaction's retrieved success", $transactions);
        } catch (PDOException $e) {
            return JsonResponse::serverError('we encountered a problem');
        } catch (Exception $e) {
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

            if (!$this->transaction->where('user_id', $this->user->id)->orderBy('created_at', 'DESC')->first()) {
                $ref = transaction_ref();

                if (!$this->temp_trans_ref->create([
                    'user_id' => $this->user->id,
                    'tx_ref' => $ref
                ])) {
                    return JsonResponse::serverError('something happened please try again');
                }
            }

            return JsonResponse::ok('transaction ref generated success', $this->temp_trans_ref);
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
                'third_party_ref' => 'required|string',
                'status' => "required|string|in:$status",
                'amount' => 'required|string',
                'currency' => 'required|string',
                'type' => "required|string|in:$types"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            if ($trans = $this->verifyTransaction($body['transaction_id'])) {
                if ($trans->data->status !== 'successful' ||
                    $trans->data->amount !== $this->transaction->amount ||
                    $trans->data->currency !== $this->transaction->currency
                )
                    $body['status'] = $trans->data->status == 'cancelled' ? 'cancelled' : 'pending';
                else 
                    $body['status'] = $trans->data->status;
            } else {
                $body['status'] = 'pending';
            }

            if (!$transaction = $this->transaction->create($body)) {
                return JsonResponse::serverError('unable to create transaction');
            }
            if (!$plan = Plan::getBuilder()->find($body['plan_id'])) {
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
                'expires_at' => Carbon::now()->modify('+' . $durationInterval())
            ]);
            ///TODO: send success notification to the user about the new subscription he made

            $this->temp_trans_ref->where('user_id', $this->user->id)->delete();

            return JsonResponse::created('transaction creation successful', $transaction);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('transaction already exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
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

            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if (!$this->transaction->where('transaction_id', $body['data']['id'])->first()) {
                return JsonResponse::badRequest();
            }
            
            $trans = $this->verifyTransaction($body['data']['id']);
            if (!$trans || $trans->data->status !== 'successful' ||
                $trans->data->amount !== $this->transaction->amount ||
                $trans->data->currency !== $this->transaction->currency
            ) {
                return JsonResponse::badRequest();
            }

            if (!$this->transaction->update([ 'status' => $trans->data->status])) {
                return JsonResponse::badRequest();
            }

            return JsonResponse::ok();
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column'))
                return JsonResponse::badRequest('column does not exist');
            else $message = 'we encountered a problem';
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            return JsonResponse::serverError('we encountered a problem');
        }
    }
}
