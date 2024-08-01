<?php
namespace Basttyy\FxDataServer\Controllers\Api;

use Basttyy\FxDataServer\Auth\Guard;
use Basttyy\FxDataServer\Auth\JwtAuthenticator;
use Basttyy\FxDataServer\Auth\JwtEncoder;
use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\Geolocation\IP2Location\WebService;
use Basttyy\FxDataServer\libs\JsonResponse;
use Basttyy\FxDataServer\libs\Request;
use Basttyy\FxDataServer\libs\Validator;
use Basttyy\FxDataServer\Models\Plan;
use Basttyy\FxDataServer\Models\Role;
use Basttyy\FxDataServer\Models\User;
use Basttyy\FxDataServer\libs\Traits\Flutterwave;
use Basttyy\FxDataServer\Models\CheapCountry;
use Exception;
use GuzzleHttp\Client;
use LogicException;
use PDOException;

final class PlanController
{
    use Flutterwave;

    public function show(Request $request, string $id)
    {
        $id = sanitize_data($id);
        try {
            if (!$plan = Plan::getBuilder()->find((int)$id))
                return JsonResponse::notFound("unable to retrieve plan");

            return JsonResponse::ok("plan retrieved success", $plan->toArray());
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a db problem");
        } catch (LogicException $e) {
            return JsonResponse::serverError("we encountered a runtime problem");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem");
        }
    }

    public function list(Request $request, string $standard = null)
    {
        try {
            $builder = Plan::getBuilder();
            if (count($request->query())) {

            }
            if (is_null($standard)) {
                $plans = $builder->all();
                return JsonResponse::ok("plans retrieved success", $plans);
            }

            if ($standard == 'low') {
                $plans = $builder->where('for_cheap_regions', 1)->get();
            } else if ($standard == 'high') {
                $plans = $builder->where('for_cheap_regions', 0)->get();
            } else if ($user = JwtAuthenticator::validate()) {
                if (Guard::roleIs($user, 'admin')) {
                    $plans = $builder->all();
                } else {
                    $ischeapcountry = CheapCountry::getBuilder()->where('name', $user->country)->count();

                    $plans = $ischeapcountry ? $builder->where('for_cheap_regions', 1)->get() : $builder->where('for_cheap_regions', 0)->get();
                }
            } else {
                $ipaddress = getenv('HTTP_X_FORWARDED_FOR') ? getenv('HTTP_X_FORWARDED_FOR') : getenv('REMOTE_ADDR');

                // $ws = new \IP2Location\WebService(env('IP2LOC_API_KEY'), 'WS25', false);
                $ws = new WebService(env('IPLOC_API_KEY'));           // Not using SSL for faster response time
                $records = $ws->lookup($ipaddress, language: 'en');

                $ischeapcountry = $records != false ? CheapCountry::getBuilder()->where('name', $records['country_name'])->count() : false;
                $plans = $ischeapcountry ? $builder->where('for_cheap_regions', 1)->get() : $builder->where('for_cheap_regions', 0)->get();
            }
            if (!$plans)
                return JsonResponse::ok('no plan found in list', []);

            $data = ['plans' => $plans, 'standard' => $standard];
            if (isset($ischeapcountry))
                $data['standard'] = $ischeapcountry ? 'low' : 'high';

            return JsonResponse::ok("plans retrieved success", $data);
        } catch (PDOException $e) {
            return JsonResponse::serverError("we encountered a problem ");
        } catch (Exception $e) {
            return JsonResponse::serverError("we encountered a problem ");
        }
    }

    public function create(Request $request)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't create a plan");
            }
            
            $body = sanitize_data($request->input());
            $status = Plan::DISABLED.', '.Plan::ENABLED;
            $intervals = implode(', ', Plan::INTERVALS);

            if ($validated = Validator::validate($body, [
                'name' => 'required|string',
                'description' => 'required|string',
                'price' => 'required|numeric',
                'status' => "sometimes|string|in:$status",
                'features' => 'required|string',
                'for_cheap_regions' => 'required|numeric',
                'currency' => 'required|string',
                'duration_interval' => "required|string|in:$intervals"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $plan = new Plan;
            $plan->beginTransaction();

            $body['price'] = (float)$body['price'];
            $body['for_cheap_regions'] = (int)$body['for_cheap_regions'];

            if (!$plan->create(Arr::except($body, 'currency'))) {
                return JsonResponse::serverError("unable to create plan");
            }

            // \Flutterwave\Flutterwave::bootstrap();

            // $paymentPlansService = new \Flutterwave\Service\PaymentPlan();

            $convertInterval = function() use ($body) {
                if ($body['duration_interval'] === 'day') {
                    return 'daily';
                }
                return $body['duration_interval'].'ly';
            };

            $response = $this->createPaymentPlan($body['price'], $body['currency'], $body['name'], $convertInterval());

            // $payload = new \Flutterwave\Payload();

            // $payload->set('amount', 5000);
            // $payload->set('name', '');
            // $payload->set('interval', $convertInterval());

            // $response = $paymentPlansService->create($payload);

            if ($response->status !== 'success') {
                $plan->rollback();
                return JsonResponse::serverError("unable to create plan");
            }

            if (!$plan->update([
                'plan_token' => $response->data->plan_token,
                'third_party_id' => $response->data->id
            ])) {
                $plan->rollback();
                return JsonResponse::serverError("unable to create plan");
            }

            $plan->commit();

            ///TODO:: send campaign notification to users/subscribers about the new plan

            return JsonResponse::created("plan creation successful", $plan);
        } catch (PDOException $e) {
            if (env("APP_ENV") === "local")
                $message = $e->getMessage();
            else if (str_contains($e->getMessage(), 'Duplicate entry'))
                return JsonResponse::badRequest('plan already exist');
            else $message = "we encountered a problem";
            
            return JsonResponse::serverError($message);
        } catch (Exception $e) {
            $message = env("APP_ENV") === "local" ? $e->getMessage() : "";
            return JsonResponse::serverError("we encountered a problem ".$message);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            // Check if the request has a body
            if ( !$request->hasBody()) {
                //return "body is required" response;
                return JsonResponse::badRequest("bad request", "body is required");
            }

            $id = sanitize_data($id);
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't update a plan");
            }
            
            $body = sanitize_data($request->input());

            $status = Plan::DISABLED.', '.Plan::ENABLED;
            $intervals = implode(', ', Plan::INTERVALS);

            if ($validated = Validator::validate($body, [
                'name' => 'sometimes|string',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric',
                'status' => "sometimes|string|in:$status",
                'for_cheap_regions' => 'sometimes|numeric',
                'features' => 'sometimes|string',
                'duration_interval' => "sometimes|string|in:$intervals"
            ])) {
                return JsonResponse::badRequest('errors in request', $validated);
            }

            $body['price'] = (float)$body['price'];
            $body['for_cheap_regions'] = (int)$body['for_cheap_regions'];

            if (!$plan = Plan::getBuilder()->update($body, (int)$id)) {
                return JsonResponse::notFound("unable to update plan not found");
            }

            return JsonResponse::ok("plan updated successfull", $plan->toArray());
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

    public function delete(Request $request, int $id)
    {
        try {
            $id = sanitize_data($id);
            $user = $request->auth_user;

            if (!Guard::roleIs($user, 'admin')) {
                return JsonResponse::unauthorized("you can't delete a plan");
            }

            // echo "got to pass login";
            if (!Plan::getBuilder()->delete((int)$id)) {
                return JsonResponse::notFound("unable to delete plan or plan not found");
            }

            return JsonResponse::ok("plan deleted successfull");
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
}
