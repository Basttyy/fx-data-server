<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Plan;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\Subscription;
use Basttyy\FxDataServer\Models\User;
use Carbon\Carbon;
use Exception;
use LogicException;
use PDOException;

final class SubscriptionController
{
    private $method;
    private $user;
    private $authenticator;
    private $plan;
    private $subscription;

    public function __construct($method = "show")
    {
        $this->method = $method;
        $this->user = new User();
        $this->plan = new Plan();
        $this->subscription = new Subscription;
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
            case 'count':
                $resp = $this->count();
                break;
            case 'list':
                $resp = $this->list();
                break;
            case 'create':
                $resp = $this->create();
                break;
            // case 'update':
            //     $resp = $this->update($id);
            //     break;
            // case 'delete':
            //     $resp = $this->delete($id);
            //     break;
            default:
                $resp = JsonResponse::serverError('bad method call');
        }

        $resp;
    }

    private function show(string $id)
    {
        $id = sanitize_data($id);
        try {
            if (!$this->subscription->find((int)$id))
                return JsonResponse::notFound("unable to retrieve subscription");

            return JsonResponse::ok("subscription retrieved success", $this->subscription->toArray());
        } catch (PDOException $e) {
            consoleLog(0, $e->getMessage(). '   '.$e->getTraceAsString());
            return JsonResponse::serverError("we encountered a db problem");
        } catch (LogicException $e) {
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }
    
    private function count()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            
            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't view subscriptions");
            }
            
            $query = isset($_GET) ? sanitize_data($_GET): [];

            if (count($query)) {
                foreach ($query as $k => $v) {
                    $this->subscription->where($k, value: $v);
                }
                $count = $this->subscription->count();
            } else {
                $count = $this->subscription->count();
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

    private function list()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            
            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't view subscriptions");
            }
            $subscriptions = $this->subscription->all();
            if (!$subscriptions)
                return JsonResponse::ok("no subscription found in list", []);

            return JsonResponse::ok("subscriptions retrieved success", $subscriptions);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    
    private function list_user(string $id)
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }

            if (!$this->authenticator->verifyRole($this->user, 'admin')) {
                return JsonResponse::unauthorized("you can't view subscriptions");
            }
            $id = sanitize_data($id);
            $subscriptions = $this->subscription->findBy("user_id", $id);
            
            if (!$subscriptions)
                return JsonResponse::ok("no subscription found in list", []);

            return JsonResponse::ok("subscriptions retrieved success", $subscriptions);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    private function create()
    {
        try {
            if (!$this->authenticator->validate()) {
                return JsonResponse::unauthorized();
            }
            // Check if the request has a body
            if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            if (!$this->authenticator->verifyRole($this->user, 'user')) {
                return JsonResponse::unauthorized("you can't subscribe to a plan");
            }
            
            $inputJSON = file_get_contents('php://input');

            $body = sanitize_data(json_decode($inputJSON, true));

            if ($validated = Validator::validate($body, [
                'duration' => 'required|integer',
                'plan_id' => 'required|integer'
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }
            // if (!$this->subscription->findByArray())  TODO: we need to ensure the user does not have an active subscription
            if (!$this->plan->find($body['plan_id'])) {
                return JsonResponse::notFound("unable to retrieve plan");
            }
            $durationInterval = function() use ($body) {
                if ($this->plan->duration_interval == 'bi-annual')
                    return ($body['duration'] * 6).' '.'months';
                $plural = $body['duration'] > 1 ? 's' : '';

                return $body['duration'] .' '. $this->plan->duration_interval . $plural;
            };

            $body['total_cost'] = $this->plan->price;
            $body['expires_at'] = Carbon::now()->modify('+' . $durationInterval());
            // $body['total_cost'] = ($body['duration'] - ($body['duration']/6)) * $this->plan->price;  // Give one month discount for every 6 months subscribed
            // $body['expires_at'] = Carbon::now()->modify('+'.$body['duration'].' '.$this->plan->duration_interval. $body['duration'] > 1 ? 's' : '');

            ///TODO:  We Still Need To Make Sure The User Had Completed A Payment That Is Worth The Amount Above, Before We Can Create The Subscription

            if (!$subscription = $this->subscription->create($body)) {
                return JsonResponse::serverError("unable to create subscription");
            }

            ///TODO: send success notification to the user about the new subscription he made

            return JsonResponse::ok("subscription creation successful", $subscription);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('subscription already exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "we encountered a problem";
            return JsonResponse::serverError("we got some error here".$message);
        }
    }

    // private function update(string $id)
    // {
    //     try {
    //         if (!$user = $this->authenticator->validate()) {
    //             return JsonResponse::unauthorized();
    //         }
    //         // Check if the request has a body
    //         if ( $_SERVER['CONTENT_LENGTH'] <= env('CONTENT_LENGTH_MIN')) {
    //             //return "body is required" response;
    //             return JsonResponse::badRequest("bad request", "body is required");
    //         }

    //         $id = sanitize_data($id);

    //         if (!$this->authenticator->verifyRole($user, 'admin')) {
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

    // private function delete(int $id)
    // {
    //     try {
    //         $id = sanitize_data($id);

    //         if (!$this->authenticator->validate()) {
    //             return JsonResponse::unauthorized();
    //         }

    //         if (!$this->authenticator->verifyRole($this->user, 'admin')) {
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
