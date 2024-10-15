<?php
namespace App\Http\Controllers\Api;

use Eyika\Atom\Framework\Support\Auth\Guard;
use Eyika\Atom\Framework\Http\JsonResponse;
use Eyika\Atom\Framework\Http\Request;
use App\Http\Traits\PaymentGateway;
use App\Models\Subscription;
use Exception;
use Eyika\Atom\Framework\Support\Database\DB;
use LogicException;
use PDOException;

final class SubscriptionController
{
    use PaymentGateway;

    public function show(Subscription $subscription)
    {
        try {
            return JsonResponse::ok("subscription retrieved success", $subscription);

        } catch (PDOException $e) {
            logger()->info($e->getMessage(). '   '.$e->getTraceAsString());
            return JsonResponse::serverError("we encountered a db problem");
        } catch (LogicException $e) {
            logger()->info($e->getMessage(). '   '.$e->getTraceAsString());
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            logger()->info($e->getMessage(). '   '.$e->getTraceAsString());
            return JsonResponse::serverError("we encountered a problem");
        }
    }
    
    public function count(Request $request)
    {
        try {
            $query = $request->query();

            $builder = Subscription::getBuilder();
            if (count($query)) {
                foreach ($query as $k => $v) {
                    $builder->where($k, value: $v);
                }
                $count = $builder->count();
            } else {
                $count = $builder->count();
            }

            if (!$count)
                return JsonResponse::ok("no subscription found in list", 0);

            return JsonResponse::ok("subscriptions count retrieved success", $count);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function list(Request $request)
    {
        try {
            $page = $request->query('page');
            $per_page = $request->query('perpage');
            $subscriptions = Subscription::getBuilder()->paginate($page, $per_page);

            if (!$subscriptions)
                return JsonResponse::ok("no subscription found in list", []);

            return JsonResponse::ok("subscriptions retrieved success", $subscriptions->toArray('admin.subscriptions.list'));
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
    
    public function listUser(Request $request, string $id)
    {
        try {
            $page = $request->query('page');
            $per_page = $request->query('perpage');
            $subscriptions = Subscription::getBuilder()->where("user_id", $id)->paginate($page, $per_page);
            
            if (!$subscriptions)
                return JsonResponse::ok("no subscription found in list", []);

            return JsonResponse::ok("subscriptions retrieved success", $subscriptions->toArray('admin.subscriptions.listuser'));
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
    
    public function listPlan(Request $request, string $id)
    {
        try {
            $page = $request->query('page');
            $per_page = $request->query('perpage');
            $subscriptions = Subscription::getBuilder()->where("plan_id", $id)->paginate($page, $per_page);
            
            if (!$subscriptions)
                return JsonResponse::ok("no subscription found in list", []);

            return JsonResponse::ok("subscriptions retrieved success", $subscriptions->toArray('admin.subscriptions.listplan'));
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function cancel(Request $request, Subscription $subscription)
    {
        try {
            DB::beginTransaction();
            $user = $request->auth_user;

            if ($subscription instanceof Subscription) {
                if ($subscription->user_id === $user->id) { // || Carbon::now()->greaterThanOrEqualTo($subscription->expires_at)
                    return JsonResponse::badRequest('cannot cancel this subscription');
                }
            }

            if (!$this->cancelSubscription($subscription->third_party_id, $subscription->third_party_token))
                return JsonResponse::serverError('unable to cancel subscription');

            $subscription->update('is_canceled', true);
            $this->cancelSubscription($subscription->third_party_id, $subscription->third_party_token);

            DB::commit();
            return JsonResponse::ok("subscription canceled successfully", $subscription);
        } catch (PDOException $e) {
            DB::rollback();
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            DB::rollback();
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    // public function create()
    // {
    //     try {
    //         if (!JwtAuthenticator::validate()) {
    //             return JsonResponse::unauthorized();
    //         }
    //         // Check if the request has a body
    //         if ( !$request->hasBody()) {
    //             //return "body is required" response;
    //             return JsonResponse::badRequest("bad request", "body is required");
    //         }

    //         if (!Guard::roleIs($user, 'user')) {
    //             return JsonResponse::unauthorized("you can't subscribe to a plan");
    //         }
            
    //         $inputJSON = file_get_contents('php://input');

    //         $body = sanitize_data(json_decode($inputJSON, true));

    //         if ($validated = Validator::validate($body, [
    //             'duration' => 'required|integer',
    //             'plan_id' => 'required|integer'
    //         ])) {
    //             return JsonResponse::badRequest('errors in request', $validated);
    //         }
    //         // if (!$this->subscription->findByArray())  TODO: we need to ensure the user does not have an active subscription
    //         if (!$this->plan->find($body['plan_id'])) {
    //             return JsonResponse::notFound("unable to retrieve plan");
    //         }
    //         $durationInterval = function() use ($body) {
    //             if ($this->plan->duration_interval == 'bi-annual')
    //                 return ($body['duration'] * 6).' '.'months';
    //             $plural = $body['duration'] > 1 ? 's' : '';

    //             return $body['duration'] .' '. $this->plan->duration_interval . $plural;
    //         };

    //         $body['total_cost'] = $this->plan->price;
    //         $body['expires_at'] = Carbon::now()->modify('+' . $durationInterval());
    //         // $body['total_cost'] = ($body['duration'] - ($body['duration']/6)) * $this->plan->price;  // Give one month discount for every 6 months subscribed
    //         // $body['expires_at'] = Carbon::now()->modify('+'.$body['duration'].' '.$this->plan->duration_interval. $body['duration'] > 1 ? 's' : '');

    //         ///TODO:  We Still Need To Make Sure The User Had Completed A Payment That Is Worth The Amount Above, Before We Can Create The Subscription

    //         if (!$subscription = $this->subscription->create($body)) {
    //             return JsonResponse::serverError("unable to create subscription");
    //         }

    //         ///TODO: send success notification to the user about the new subscription he made

    //         return JsonResponse::ok("subscription creation successful", $subscription);
    //     } catch (PDOException $e) {
    //         if (env("APP_ENV") === "local")
    //             $message = $e->getMessage();
    //         else if (str_contains($e->getMessage(), 'Duplicate entry'))
    //             return JsonResponse::badRequest('subscription already exist');
    //         else $message = "we encountered a problem";
            
    //         return JsonResponse::serverError($message);
    //     } catch (Exception $e) {
    //         $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
    //         return JsonResponse::serverError("we got some error here".$message);
    //     }
    // }

    // public function update(string $id)
    // {
    //     try {
    //         if (!$user = JwtAuthenticator::validate()) {
    //             return JsonResponse::unauthorized();
    //         }
    //         // Check if the request has a body
    //         if ( !$request->hasBody()) {
    //             //return "body is required" response;
    //             return JsonResponse::badRequest("bad request", "body is required");
    //         }

    //         $id = $id;

    //         if (!Guard::roleIs($user, 'admin')) {
    //             return JsonResponse::unauthorized("you can't update a plan");
    //         }
            
    //         $inputJSON = file_get_contents('php://input');

    //         $body = sanitize_data(json_decode($inputJSON, true));

    //         $status = Plan::DISABLED.', '.Plan::ENABLED;
    //         if ($validated = Validator::validate($body, [
    //             'description' => 'sometimes|string',
    //             'price' => 'sometimes|numeric',
    //             'status' => "sometimes|string|in:$status",
    //             'features' => 'sometimes|string'
    //         ])) {
    //             return JsonResponse::badRequest('errors in request', $validated);
    //         }

    //         if (!$this->plan->update($body, (int)$id)) {
    //             return JsonResponse::notFound("unable to update plan not found");
    //         }

    //         return JsonResponse::ok("plan updated successfull", $this->plan->toArray());
    //     } catch (PDOException $e) {
    //         if (env("APP_ENV") === "local")
    //             $message = $e->getMessage();
    //         else if (str_contains($e->getMessage(), 'Unknown column'))
    //             return JsonResponse::badRequest('column does not exist');
    //         else $message = "we encountered a problem";
            
    //         return JsonResponse::serverError($message);
    //     } catch (Exception $e) {
    //         $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
    //         return JsonResponse::serverError("we got some error here".$message);
    //     }
    // }

    // public function delete(int $id)
    // {
    //     try {
    //         $id = $id;

    //         if (!JwtAuthenticator::validate()) {
    //             return JsonResponse::unauthorized();
    //         }

    //         if (!Guard::roleIs($user, 'admin')) {
    //             return JsonResponse::unauthorized("you can't delete a plan");
    //         }

    //         // echo "got to pass login";
    //         if (!$this->plan->delete((int)$id)) {
    //             return JsonResponse::notFound("unable to delete plan or plan not found");
    //         }

    //         return JsonResponse::ok("plan deleted successfull");
    //     } catch (PDOException $e) {
    //         if (env("APP_ENV") === "local")
    //             $message = $e->getMessage();
    //         else if (str_contains($e->getMessage(), 'Unknown column'))
    //             return JsonResponse::badRequest('column does not exist');
    //         else $message = "we encountered a problem";
            
    //         return JsonResponse::serverError($message);
    //     } catch (Exception $e) {
    //         $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
    //         return JsonResponse::serverError("we got some error here".$message);
    //     }
    // }
}
